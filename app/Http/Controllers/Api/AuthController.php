<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Cliente;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    // REGISTRO DE CLIENTES
    public function registro(Request $request)
    {
        $datos = $request->validate([
            'name' => 'required|string',
            'apellido' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'telefono' => 'required|string|max:20',
            'direccion' => 'nullable|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $resultado = DB::transaction(function () use ($datos) {

            $usuario = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'password' => Hash::make($datos['password']),
                'role' => 'cliente',
            ]);

            Cliente::create([
                'user_id' => $usuario->id,
                'nombre' => $datos['name'],
                'apellido' => $datos['apellido'],
                'telefono' => $datos['telefono'],
                'email' => $datos['email'],
                'direccion' => $datos['direccion'] ?? null,
            ]);

            return $usuario;
        });

        return response()->json([
            'mensaje' => 'Cuenta creada correctamente.',
            'usuario' => $resultado,
        ], 201);
    }

    // INICIO DE SESION
    public function login(Request $request)
    {
        $datos = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $usuario = User::where('email', $datos['email'])->first();

        if (!$usuario || !Hash::check($datos['password'], $usuario->password)) {
            return response()->json([
                'mensaje' => 'Credenciales incorrectas.',
            ], 401);
        }

        $token = $usuario->createToken('token-tienda-yoly')->plainTextToken;

        return response()->json([
            'mensaje' => 'Inicio de sesión exitoso.',
            'usuario' => $usuario,
            'token' => $token,
            'tipo_token' => 'Bearer',
        ]);
    }

    // CERRAR SESION 
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'mensaje' => 'Sesión cerrada correctamente.',
        ]);
    }
}


