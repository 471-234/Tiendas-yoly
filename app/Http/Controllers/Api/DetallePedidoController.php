<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetallePedido;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DetallePedidoController extends Controller
{
    // LISTAR DETALLES DE UN PEDIDO
    public function index(Request $request, $pedido_id)
    {
        $pedido = Pedido::findOrFail($pedido_id);

        if (!$this->puedeModificar($request, $pedido)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return response()->json($pedido->detalles);
    }

    // MOSTRAR UN DETALLE
    public function show(Request $request, $id)
    {
        $detalle = DetallePedido::with('producto', 'pedido')->findOrFail($id);

        if (!$this->puedeModificar($request, $detalle->pedido)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return response()->json($detalle);
    }

    // AGREGAR DETALLE
    public function store(Request $request)
    {
        $datos = $request->validate([
            'pedido_id' => 'required|exists:pedidos,id',
            'producto_id' => 'nullable|exists:productos,id',
            'nombre_producto' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'cantidad' => 'required|integer|min:1',
            'precio_unitario' => 'required|numeric|min:0.01',
            'referencia_imagen' => 'nullable|string|max:255',
            'enlace_referencia' => 'nullable|url|max:2000',
        ]);

        $pedido = Pedido::findOrFail($datos['pedido_id']);

        if (!$this->puedeModificar($request, $pedido)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if (in_array($pedido->estado, ['cancelado', 'entregado'])) {
            return response()->json([
                'message' => 'No se pueden modificar los detalles de este pedido.'
            ], 422);
        }

        return DB::transaction(function () use ($datos, $pedido) {
            $datos['subtotal'] = $datos['cantidad'] * $datos['precio_unitario'];

            $detalle = DetallePedido::create($datos);

            $this->actualizarTotal($pedido);

            return response()->json([
                'message' => 'Detalle agregado correctamente.',
                'detalle' => $detalle,
                'nuevo_total' => $pedido->fresh()->total,
            ], 201);
        });
    }

    // ACTUALIZAR DETALLE
    public function update(Request $request, $id)
    {
        $detalle = DetallePedido::findOrFail($id);
        $pedido = $detalle->pedido;

        if (!$this->puedeModificar($request, $pedido)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if (in_array($pedido->estado, ['cancelado', 'entregado'])) {
            return response()->json([
                'message' => 'No se puede modificar este pedido.'
            ], 422);
        }

        $datos = $request->validate([
            'producto_id' => 'nullable|exists:productos,id',
            'nombre_producto' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'cantidad' => 'sometimes|required|integer|min:1',
            'precio_unitario' => 'sometimes|required|numeric|min:0.01',
            'referencia_imagen' => 'nullable|string|max:255',
            'enlace_referencia' => 'nullable|url|max:2000',
        ]);

        return DB::transaction(function () use ($datos, $detalle, $pedido) {
            $cantidad = $datos['cantidad'] ?? $detalle->cantidad;
            $precio = $datos['precio_unitario'] ?? $detalle->precio_unitario;

            $datos['subtotal'] = $cantidad * $precio;

            $detalle->update($datos);

            $this->actualizarTotal($pedido);

            return response()->json([
                'message' => 'Detalle actualizado correctamente.',
                'detalle' => $detalle->fresh(),
                'nuevo_total' => $pedido->fresh()->total,
            ]);
        });
    }

    // ELIMINAR DETALLE
    public function destroy(Request $request, $id)
    {
        $detalle = DetallePedido::findOrFail($id);
        $pedido = $detalle->pedido;

        if (!$this->puedeModificar($request, $pedido)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if (in_array($pedido->estado, ['cancelado', 'entregado'])) {
            return response()->json([
                'message' => 'No se puede modificar este pedido.'
            ], 422);
        }

        return DB::transaction(function () use ($detalle, $pedido) {
            $detalle->delete();

            $this->actualizarTotal($pedido);

            return response()->json([
                'message' => 'Detalle eliminado correctamente.',
                'nuevo_total' => $pedido->fresh()->total,
            ]);
        });
    }

    private function actualizarTotal(Pedido $pedido): void
    {
        $total = $pedido->detalles()->sum('subtotal');

        $pedido->total = $total;

        $pedido->anticipo_requerido = $pedido->tipo_pedido === 'personalizado'
            ? $total * 0.50
            : 0;

        $pedido->save();
    }

    private function puedeModificar(Request $request, Pedido $pedido): bool
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