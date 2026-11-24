<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Categoria;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    //LISTAR CATEGORIAS
    public function index()
    {
        $categorias = Categoria::with('productos')->get();

        return response()->json($categorias);
    }

    // CREAR CATEGORIA
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias,nombre',
            'descripcion' => 'nullable|string',
            'estado' => 'sometimes|boolean',
        ]);

        $categoria = Categoria::create($datos);

        return response()->json([
            'mensaje' => 'Categoría creada correctamente',
            'categoria' => $categoria
        ], 201);
    }

    // MOSTRAR UNA CATEGORIA
    public function show($id)
    {
        $categoria = Categoria::with('productos')->find($id);

        if (!$categoria) {
            return response()->json([
                'mensaje' => 'Categoría no encontrada'
            ], 404);
        }

        return response()->json($categoria);
    }

    // ACTUALIZAR CATEGORIA
    public function update(Request $request, $id)
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'mensaje' => 'Categoría no encontrada'
            ], 404);
        }

        $datos = $request->validate([
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('categorias', 'nombre')->ignore($categoria->id),
            ],
            'descripcion' => 'sometimes|nullable|string',
            'estado' => 'sometimes|boolean',
        ]);

        $categoria->update($datos);

        return response()->json([
            'mensaje' => 'Categoría actualizada correctamente',
            'categoria' => $categoria
        ]);
    }

    // ELIMINAR CATEGORIA
    public function destroy($id)
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'mensaje' => 'Categoría no encontrada'
            ], 404);
        }

        if ($categoria->productos()->exists()) {
            return response()->json([
                'mensaje' => 'No se puede eliminar porque tiene productos asociados'
            ], 409);
        }

        $categoria->delete();

        return response()->json([
            'mensaje' => 'Categoría eliminada correctamente'
        ]);
    }
}