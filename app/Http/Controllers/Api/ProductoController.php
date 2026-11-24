<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Producto;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    // Consultar todos los productos
    public function index()
    {
        $productos = Producto::with(['categoria', 'imagenes'])->get();

        return response()->json($productos, 200);
    }

    // Registrar un producto
    public function store(Request $request)
    {
        $datos = $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'cantidad' => 'required|integer|min:0',
            'condicion' => ['required', Rule::in(['nuevo', 'segunda_mano'])],
            'estado' => ['sometimes', Rule::in(['disponible', 'agotado', 'inactivo'])],
        ]);

        if ($datos['cantidad'] == 0) {
            $datos['estado'] = 'agotado';
        }

        $producto = Producto::create($datos);

        return response()->json([
            'mensaje' => 'Producto registrado correctamente',
            'producto' => $producto
        ], 201);
    }

    // Consultar un producto
    public function show($id)
    {
        $producto = Producto::with(['categoria', 'imagenes'])->find($id);

        if (!$producto) {
            return response()->json([
                'mensaje' => 'Producto no encontrado'
            ], 404);
        }

        return response()->json($producto, 200);
    }

    // Actualizar un producto
    public function update(Request $request, $id)
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'mensaje' => 'Producto no encontrado'
            ], 404);
        }

        $datos = $request->validate([
            'categoria_id' => 'sometimes|exists:categorias,id',
            'nombre' => 'sometimes|string',
            'descripcion' => 'nullable|string',
            'precio' => 'sometimes|numeric|min:0',
            'cantidad' => 'sometimes|integer|min:0',
            'condicion' => ['sometimes', Rule::in(['nuevo', 'segunda_mano'])],
            'estado' => ['sometimes', Rule::in(['disponible', 'agotado', 'inactivo'])],
        ]);

        if (isset($datos['cantidad']) && $datos['cantidad'] == 0) {
            $datos['estado'] = 'agotado';
        } elseif (isset($datos['cantidad']) && $datos['cantidad'] > 0
            && ($producto->estado === 'agotado')) {
            $datos['estado'] = 'disponible';
        }

        $producto->update($datos);

        return response()->json([
            'mensaje' => 'Producto actualizado correctamente',
            'producto' => $producto
        ], 200);
    }

    // Eliminar un producto
    public function destroy($id)
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'mensaje' => 'Producto no encontrado'
            ], 404);
        }

        if ($producto->detallesPedido()->exists()) {
            return response()->json([
                'mensaje' => 'No se puede eliminar porque está asociado a pedidos'
            ], 409);
        }

        $producto->delete();

        return response()->json([
            'mensaje' => 'Producto eliminado correctamente'
        ], 200);
    }
}
