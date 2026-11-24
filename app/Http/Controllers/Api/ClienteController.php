<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cliente;


class ClienteController extends Controller
{
    // Consultar todos los clientes
    public function index()
    {
        $clientes = Cliente::with('usuario')->get();

        return response()->json($clientes, 200);
    }

    // Consultar un cliente
    public function show($id)
    {
        $cliente = Cliente::with(['usuario', 'pedidos'])->find($id);

        if (!$cliente) {
            return response()->json([
                'mensaje' => 'Cliente no encontrado'
            ], 404);
        }

        return response()->json($cliente, 200);
    }

    // Actualizar información de un cliente
    public function update(Request $request, $id)
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return response()->json([
                'mensaje' => 'Cliente no encontrado'
            ], 404);
        }

        $datos = $request->validate([
            'nombre' => 'sometimes|string',
            'apellido' => 'sometimes|string',
            'telefono' => 'sometimes|string|max:20',
            'email' => 'nullable|email',
            'direccion' => 'nullable|string',
        ]);

        $cliente->update($datos);

        return response()->json([
            'mensaje' => 'Cliente actualizado correctamente',
            'cliente' => $cliente
        ], 200);
    }

    // Eliminar un cliente
    public function destroy($id)
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return response()->json([
                'mensaje' => 'Cliente no encontrado'
            ], 404);
        }

        if ($cliente->pedidos()->exists()) {
            return response()->json([
                'mensaje' => 'No se puede eliminar un cliente que tiene pedidos registrados'
            ], 409);
        }

        $cliente->delete();

        return response()->json([
            'mensaje' => 'Cliente eliminado correctamente'
        ], 200);
    }
}