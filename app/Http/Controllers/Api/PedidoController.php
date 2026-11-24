<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PedidoController extends Controller
{
    // LISTAR PEDIDOS
    public function index(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $pedidos = Pedido::with(['cliente', 'detalles', 'cuotas', 'pagos'])
            ->when($usuario->role !== 'admin', function ($query) use ($usuario) {
                $query->whereHas('cliente', function ($q) use ($usuario) {
                    $q->where('user_id', $usuario->id);
                });
            })
            ->latest()
            ->get();

        return response()->json($pedidos);
    }

    // MOSTRAR UN PEDIDO
    public function show(Request $request, $id)
    {
        $pedido = Pedido::with([
            'cliente',
            'detalles',
            'cuotas',
            'pagos'
        ])->findOrFail($id);

        if (!$this->puedeVer($request, $pedido)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return response()->json($pedido);
    }

    // CREAR PEDIDO
    public function store(Request $request)
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'tipo_pedido' => 'required|in:normal,personalizado',
            'cliente_id' => 'nullable|exists:clientes,id',
            'cliente' => 'nullable|array',
            'cliente.nombre' => 'required_without:cliente_id|string|max:255',
            'cliente.apellido' => 'required_without:cliente_id|string|max:255',
            'cliente.telefono' => 'required_without:cliente_id|string|max:30',
            'cliente.email' => 'nullable|email|max:255',
            'cliente.direccion' => 'required_without:cliente_id|string',
        ]);

        if ($usuario && $usuario->role !== 'admin') {
            $cliente = Cliente::where('user_id', $usuario->id)->first();

            if (!$cliente) {
                return response()->json([
                    'message' => 'No se encontró el perfil del cliente.'
                ], 422);
            }
        } elseif ($usuario && $usuario->role === 'admin') {
            if (empty($datos['cliente_id'])) {
                return response()->json([
                    'message' => 'Debe indicar el cliente del pedido.'
                ], 422);
            }

            $cliente = Cliente::findOrFail($datos['cliente_id']);
        } else {
            if (empty($datos['cliente'])) {
                return response()->json([
                    'message' => 'Debe proporcionar los datos del cliente.'
                ], 422);
            }

            $cliente = Cliente::create($datos['cliente']);
        }

        $pedido = Pedido::create([
            'cliente_id' => $cliente->id,
            'tipo_pedido' => $datos['tipo_pedido'],
            'fecha_pedido' => now(),
            'total' => 0,
            'anticipo_requerido' => 0,
            'estado' => 'pendiente_pago',
            'codigo_seguimiento' => strtoupper(Str::random(12)),
        ]);

        return response()->json([
            'message' => 'Pedido creado correctamente.',
            'pedido' => $pedido
        ], 201);
    }

    // ACTUALIZAR ESTADO DEL PEDIDO (ADMIN)
    public function actualizarEstado(Request $request, $id)
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $datos = $request->validate([
            'estado' => 'required|in:pendiente_pago,anticipo_recibido,solicitado_proveedor,recibido_tienda,listo_entrega,entregado,cancelado',
        ]);

        $pedido = Pedido::findOrFail($id);

        if ($pedido->estado === 'cancelado') {
            return response()->json([
                'message' => 'El pedido ya está cancelado.'
            ], 422);
        }

        $pedido->estado = $datos['estado'];
        $pedido->save();

        return response()->json([
            'message' => 'Estado actualizado correctamente.',
            'pedido' => $pedido
        ]);
    }

    // CANCELAR PEDIDO
    public function cancelar(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);

        if (!$this->puedeVer($request, $pedido)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if (in_array($pedido->estado, ['entregado', 'cancelado'])) {
            return response()->json([
                'message' => 'No se puede cancelar este pedido.'
            ], 422);
        }

        $datos = $request->validate([
            'motivo_cancelacion' => 'required|string|max:1000',
        ]);

        $pedido->update([
            'estado' => 'cancelado',
            'motivo_cancelacion' => $datos['motivo_cancelacion'],
        ]);

        return response()->json([
            'message' => 'Pedido cancelado correctamente.',
            'pedido' => $pedido
        ]);
    }

    // CONSULTAR PEDIDO POR CÓDIGO DE SEGUIMIENTO
    public function seguimiento($codigo)
    {
        $pedido = Pedido::with(['detalles', 'cuotas'])
            ->where('codigo_seguimiento', $codigo)
            ->firstOrFail();

        return response()->json([
            'codigo_seguimiento' => $pedido->codigo_seguimiento,
            'estado' => $pedido->estado,
            'tipo_pedido' => $pedido->tipo_pedido,
            'fecha_pedido' => $pedido->fecha_pedido,
            'detalles' => $pedido->detalles,
            'cuotas' => $pedido->cuotas,
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