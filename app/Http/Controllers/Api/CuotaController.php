<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cuota;
use App\Models\Pedido;
use Illuminate\Http\Request;

class CuotaController extends Controller
{
    // LISTAR CUOTAS
    public function index(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $cuotas = Cuota::with('pedido')
            ->when($usuario->role !== 'admin', function ($query) use ($usuario) {
                $query->whereHas('pedido.cliente', function ($q) use ($usuario) {
                    $q->where('user_id', $usuario->id);
                });
            })
            ->get();

        return response()->json($cuotas);
    }

    // MOSTRAR CUOTA
    public function show(Request $request, $id)
    {
        $cuota = Cuota::with('pedido', 'pagos')->findOrFail($id);

        if (!$this->puedeVer($request, $cuota->pedido)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return response()->json($cuota);
    }

    // CREAR CUOTA (ADMIN)
    public function store(Request $request)
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $datos = $request->validate([
            'pedido_id' => 'required|exists:pedidos,id',
            'numero_cuota' => 'required|integer|min:1',
            'monto' => 'required|numeric|min:0.01',
            'fecha_vencimiento' => 'required|date',
        ]);

        $pedido = Pedido::findOrFail($datos['pedido_id']);

        $totalCuotas = $pedido->cuotas()->sum('monto');

        if (($totalCuotas + $datos['monto']) > $pedido->total) {
            return response()->json([
                'message' => 'El total de las cuotas no puede superar el total del pedido.'
            ], 422);
        }

        $cuota = Cuota::create($datos);

        return response()->json([
            'message' => 'Cuota creada correctamente.',
            'cuota' => $cuota
        ], 201);
    }

    // ACTUALIZAR CUOTA (ADMIN)
    public function update(Request $request, $id)
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $cuota = Cuota::findOrFail($id);

        if ($cuota->pagos()->exists()) {
            return response()->json([
                'message' => 'No se puede modificar una cuota que ya tiene pagos registrados.'
            ], 422);
        }

        $datos = $request->validate([
            'monto' => 'sometimes|required|numeric|min:0.01',
            'fecha_vencimiento' => 'sometimes|required|date',
        ]);

        $nuevoMonto = $datos['monto'] ?? $cuota->monto;
        $otrasCuotas = $cuota->pedido->cuotas()
            ->where('id', '!=', $cuota->id)
            ->sum('monto');

        if (($otrasCuotas + $nuevoMonto) > $cuota->pedido->total) {
            return response()->json([
                'message' => 'El total de las cuotas no puede superar el total del pedido.'
            ], 422);
        }

        $cuota->update($datos);

        return response()->json([
            'message' => 'Cuota actualizada correctamente.',
            'cuota' => $cuota
        ]);
    }

    // ELIMINAR CUOTA (ADMIN)
    public function destroy(Request $request, $id)
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $cuota = Cuota::findOrFail($id);

        if ($cuota->pagos()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar una cuota que tiene pagos registrados.'
            ], 422);
        }

        $cuota->delete();

        return response()->json([
            'message' => 'Cuota eliminada correctamente.'
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