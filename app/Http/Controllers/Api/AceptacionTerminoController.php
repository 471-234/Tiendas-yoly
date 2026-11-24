<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AceptacionTermino;
use App\Models\Pedido;
use Illuminate\Http\Request;

class AceptacionTerminoController extends Controller
{
    // LISTAR ACEPTACIONES
    public function index(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $aceptaciones = AceptacionTermino::with(['cliente', 'pedido'])
            ->when($usuario->role !== 'admin', function ($query) use ($usuario) {
                $query->whereHas('cliente', function ($q) use ($usuario) {
                    $q->where('user_id', $usuario->id);
                });
            })
            ->latest()
            ->get();

        return response()->json($aceptaciones);
    }

    // MOSTRAR UNA ACEPTACIÓN
    public function show(Request $request, $id)
    {
        $aceptacion = AceptacionTermino::with(['cliente', 'pedido'])
            ->findOrFail($id);

        if (!$this->puedeVer($request, $aceptacion)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return response()->json($aceptacion);
    }

    // REGISTRAR ACEPTACIÓN
    public function store(Request $request)
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'pedido_id' => 'required|exists:pedidos,id',
            'codigo_seguimiento' => 'nullable|string|max:30',
            'version_terminos' => 'required|string|max:255',
            'texto_terminos' => 'required|string',
        ]);

        $pedido = Pedido::findOrFail($datos['pedido_id']);

        if ($usuario && $usuario->role === 'admin') {
            // El administrador puede registrar la aceptación para el pedido.
        } elseif ($usuario) {
            $esPropietario = $pedido->cliente()
                ->where('user_id', $usuario->id)
                ->exists();

            if (!$esPropietario) {
                return response()->json(['message' => 'No autorizado.'], 403);
            }
        } else {
            // Para invitados se verifica el código de seguimiento.
            if (
                empty($datos['codigo_seguimiento']) ||
                $pedido->codigo_seguimiento !== $datos['codigo_seguimiento']
            ) {
                return response()->json([
                    'message' => 'Código de seguimiento incorrecto.'
                ], 403);
            }
        }

        $existe = AceptacionTermino::where('pedido_id', $pedido->id)
            ->where('version_terminos', $datos['version_terminos'])
            ->exists();

        if ($existe) {
            return response()->json([
                'message' => 'Esta versión de términos ya fue aceptada para el pedido.'
            ], 422);
        }

        $aceptacion = AceptacionTermino::create([
            'cliente_id' => $pedido->cliente_id,
            'pedido_id' => $pedido->id,
            'version_terminos' => $datos['version_terminos'],
            'texto_terminos' => $datos['texto_terminos'],
            'fecha_aceptacion' => now(),
        ]);

        return response()->json([
            'message' => 'Términos aceptados correctamente.',
            'aceptacion' => $aceptacion
        ], 201);
    }

    private function puedeVer(Request $request, AceptacionTermino $aceptacion): bool
    {
        $usuario = $request->user();

        if (!$usuario) {
            return false;
        }

        if ($usuario->role === 'admin') {
            return true;
        }

        return $aceptacion->cliente()
            ->where('user_id', $usuario->id)
            ->exists();
    }
}