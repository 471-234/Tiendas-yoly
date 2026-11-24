<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ProductoImagenController;
use App\Http\Controllers\Api\ClienteController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// RUTAS PUBLICAS DE AUTENTICACION
Route::post('/registro', [AuthController::class, 'registro']);
Route::post('/login', [AuthController::class, 'login']);

// Cerrar sesión
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');


//  CONSULTAS PUBLICAS DEL CATALOGO

// Categorías
Route::get('/categorias', [CategoriaController::class, 'index']);
Route::get('/categorias/{id}', [CategoriaController::class, 'show']);

// Productos
Route::get('/productos', [ProductoController::class, 'index']);
Route::get('/productos/{id}', [ProductoController::class, 'show']);

// Imágenes de productos
Route::get('/productos/{producto_id}/imagenes', [ProductoImagenController::class, 'index']);
Route::get('/imagenes/{id}', [ProductoImagenController::class, 'show']);



// RUTAS EXCLUSIVAS PARA ADMINISTRADORES


Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    // CATEGORIAS


    Route::post('/categorias', [CategoriaController::class, 'store']);
    Route::put('/categorias/{id}', [CategoriaController::class, 'update']);
    Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy']);


    // PRODUCTOS E INVENTARIO
  

    Route::post('/productos', [ProductoController::class, 'store']);
    Route::put('/productos/{id}', [ProductoController::class, 'update']);
    Route::delete('/productos/{id}', [ProductoController::class, 'destroy']);

   
    // IMAGENES DE PRODUCTOS
  
    Route::post('/imagenes', [ProductoImagenController::class, 'store']);
    Route::delete('/imagenes/{id}', [ProductoImagenController::class, 'destroy']);

    // CLIENTES

    Route::get('/clientes', [ClienteController::class, 'index']);
    Route::get('/clientes/{id}', [ClienteController::class, 'show']);
    Route::put('/clientes/{id}', [ClienteController::class, 'update']);
    Route::delete('/clientes/{id}', [ClienteController::class, 'destroy']);

});