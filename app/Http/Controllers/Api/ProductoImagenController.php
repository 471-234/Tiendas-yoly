<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductoImagen;
use App\Models\Producto;
use Illuminate\Support\Facades\Storage;

class ProductoImagenController extends Controller
{
    // Consultar todas las imágenes de un producto
    public function index($producto_id)
    {
        $producto = Producto::find($producto_id);

        if (!$producto) {
            return response()->json([
                'mensaje' => 'Producto no encontrado'
            ], 404);
        }

        return response()->json(
            $producto->imagenes,
            200
        );
    }

    // Registrar una imagen
    public function store(Request $request)
    {
        $datos = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'imagen' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'es_principal' => 'sometimes|boolean',
        ]);

        $ruta = $request->file('imagen')->store('productos', 'public');

        if (!empty($datos['es_principal']) && $datos['es_principal']) {
            ProductoImagen::where('producto_id', $datos['producto_id'])
                ->update(['es_principal' => false]);
        }

        $imagen = ProductoImagen::create([
            'producto_id' => $datos['producto_id'],
            'ruta_imagen' => $ruta,
            'es_principal' => $datos['es_principal'] ?? false,
        ]);

        return response()->json([
            'mensaje' => 'Imagen registrada correctamente',
            'imagen' => $imagen
        ], 201);
    }

    // Consultar una imagen
    public function show($id)
    {
        $imagen = ProductoImagen::find($id);

        if (!$imagen) {
            return response()->json([
                'mensaje' => 'Imagen no encontrada'
            ], 404);
        }

        return response()->json($imagen, 200);
    }

    // Eliminar una imagen
    public function destroy($id)
    {
        $imagen = ProductoImagen::find($id);

        if (!$imagen) {
            return response()->json([
                'mensaje' => 'Imagen no encontrada'
            ], 404);
        }

        Storage::disk('public')->delete($imagen->ruta_imagen);

        $imagen->delete();

        return response()->json([
            'mensaje' => 'Imagen eliminada correctamente'
        ], 200);
    }
}