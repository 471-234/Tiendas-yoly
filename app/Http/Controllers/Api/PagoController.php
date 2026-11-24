<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Cuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PagoController extends Controller
{
    // LISTAR PAGOS
    public function index(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $pagos = Pago::with(['pedido', 'cuota', 'usuario'])
            ->when($usuario->role !== 'admin', function ($query) use ($usuario) {
                $query->whereHas('pedido.cliente', function ($q) use ($usuario) {
                    $q->where('user_id', $usuario->id);
                });
            })
            ->latest()
            ->get();

        return response()->json($pagos);
    }

    // MOSTRAR PAGO
    public function show(Request $request, $id)
    {
        $pago = Pago::with(['pedido', 'cuota', 'usuario'])->findOrFail($id);

        if (!$this->puedeVer($request, $pago->pedido)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return response()->json($pago);
    }

    // REGISTRAR PAGO
    public function store(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json(['message' => 'Debe iniciar sesión para registrar un pago.'], 401);
        }

        $datos = $request->validate([
            'pedido_id' => 'required|exists:pedidos,id',
            'cuota_id' => 'nullable|exists:cuotas,id',
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|in:transferencia,efectivo',
            'fecha_pago' => 'required|date',
            'referencia' => 'nullable|string|max:255',
            'comprobante_archivo' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $pedido = Pedido::findOrFail($datos['pedido_id']);

        if (!$this->puedeVer($request, $pedido)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if ($pedido->estado === 'cancelado') {
            return response()->json([
                'message' => 'No se pueden registrar pagos para un pedido cancelado.'
            ], 422);
        }

        if (!empty($datos['cuota_id'])) {
            $cuota = Cuota::findOrFail($datos['cuota_id']);

            if ($cuota->pedido_id != $pedido->id) {
                return response()->json([
                    'message' => 'La cuota no pertenece a este pedido.'
                ], 422);
            }

            $pagosCuota = $cuota->pagos()
                ->whereIn('estado', ['confirmado', 'pendiente_verificacion'])
                ->sum('monto');

            if (($pagosCuota + $datos['monto']) > $cuota->monto) {
                return response()->json([
                    'message' => 'El monto supera el saldo de la cuota.'
                ], 422);
            }
        }

        $pagosPedido = $pedido->pagos()
            ->whereIn('estado', ['confirmado', 'pendiente_verificacion'])
            ->sum('monto');

        if (($pagosPedido + $datos['monto']) > $pedido->total) {
            return response()->json([
                'message' => 'El monto supera el saldo del pedido.'
            ], 422);
        }

        $comprobante = null;

        if ($request->hasFile('comprobante_archivo')) {
            $comprobante = $request->file('comprobante_archivo')
                ->store('comprobantes', 'public');
        }

        $pago = Pago::create([
            'pedido_id' => $pedido->id,
            'cuota_id' => $datos['cuota_id'] ?? null,
            'usuario_id' => $usuario->id,
            'monto' => $datos['monto'],
            'metodo_pago' => $datos['metodo_pago'],
            'fecha_pago' => $datos['fecha_pago'],
            'referencia' => $datos['referencia'] ?? null,
            'comprobante' => $comprobante,
            'estado' => 'pendiente_verificacion',
        ]);

        return response()->json([
            'message' => 'Pago registrado y pendiente de verificación.',
            'pago' => $pago
        ], 201);
    }

    // CONFIRMAR PAGO (ADMIN)
    public function confirmar(Request $request, $id)
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return DB::transaction(function () use ($id) {
            $pago = Pago::with('pedido', 'cuota')
                ->lockForUpdate()
                ->findOrFail($id);

            if ($pago->estado !== 'pendiente_verificacion') {
                return response()->json([
                    'message' => 'Este pago ya fue procesado.'
                ], 422);
            }

            $pago->estado = 'confirmado';
            $pago->save();

            // ACTUALIZAR ESTADO DE LA CUOTA
            if ($pago->cuota_id) {
                $cuota = Cuota::whereKey($pago->cuota_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $totalPagado = $cuota->pagos()
                    ->where('estado', 'confirmado')
                    ->sum('monto');

                if ($totalPagado >= $cuota->monto) {
                    $cuota->estado = 'pagada';
                } elseif ($totalPagado > 0) {
                    $cuota->estado = 'parcial';
                } else {
                    $cuota->estado = 'pendiente';
                }

                $cuota->save();
            }

            // ACTUALIZAR ESTADO DEL PEDIDO SEGÚN EL ANTICIPO
            $pedido = Pedido::whereKey($pago->pedido_id)
                ->lockForUpdate()
                ->firstOrFail();

            $totalPagadoPedido = $pedido->pagos()
                ->where('estado', 'confirmado')
                ->sum('monto');

            if (
                $pedido->anticipo_requerido > 0 &&
                $totalPagadoPedido >= $pedido->anticipo_requerido &&
                $pedido->estado === 'pendiente_pago'
            ) {
                $pedido->estado = 'anticipo_recibido';
                $pedido->save();
            }

            return response()->json([
                'message' => 'Pago confirmado correctamente.',
                'pago' => $pago->fresh(),
                'total_pagado_pedido' => $totalPagadoPedido,
                'pedido' => $pedido->fresh(),
            ]);
        });
    }

    // RECHAZAR PAGO (ADMIN)
    public function rechazar(Request $request, $id)
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $pago = Pago::findOrFail($id);

        if ($pago->estado !== 'pendiente_verificacion') {
            return response()->json([
                'message' => 'Este pago ya fue procesado.'
            ], 422);
        }

        $pago->estado = 'rechazado';
        $pago->save();

        return response()->json([
            'message' => 'Pago rechazado correctamente.',
            'pago' => $pago
        ]);
    }

    private function puedeVer(Request $request, Pedido $pedido): bool
    {
        $usuario = $request->user();

        if (!$usuario) {
            return false;
        }

        if ($usuario->role === 'admin') {
            return true;
        }

        return $pedido->cliente()
            ->where('user_id', $usuario->id)
            ->exists();
    }
}