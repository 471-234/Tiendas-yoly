<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ProductoImagenController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\Api\DetallePedidoController;
use App\Http\Controllers\Api\CuotaController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\AceptacionTerminoController;

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

// Seguimiento de pedidos
Route::get('/pedidos/seguimiento/{codigo}', [PedidoController::class, 'seguimiento']);

// REGISTRO DE PEDIDOS Y ACEPTACIÓN DE TÉRMINOS
Route::post('/pedidos', [PedidoController::class, 'store']);
Route::post('/aceptaciones-terminos', [AceptacionTerminoController::class, 'store']);

// RUTAS PARA USUARIOS AUTENTICADOS
Route::middleware('auth:sanctum')->group(function () {

    // CLIENTES
    Route::get('/clientes', [ClienteController::class, 'index']);
    Route::get('/clientes/{id}', [ClienteController::class, 'show']);

    // PEDIDOS
    Route::get('/pedidos', [PedidoController::class, 'index']);
    Route::get('/pedidos/{id}', [PedidoController::class, 'show']);
    Route::put('/pedidos/{id}/cancelar', [PedidoController::class, 'destroy']);

    // DETALLES DE PEDIDOS
    Route::get('/pedidos/{pedido_id}/detalles', [DetallePedidoController::class, 'porPedido']);
    Route::get('/detalles', [DetallePedidoController::class, 'index']);
    Route::get('/detalles/{id}', [DetallePedidoController::class, 'show']);
    Route::post('/detalles', [DetallePedidoController::class, 'store']);
    Route::put('/detalles/{id}', [DetallePedidoController::class, 'update']);
    Route::delete('/detalles/{id}', [DetallePedidoController::class, 'destroy']);

    // CUOTAS
    Route::get('/cuotas', [CuotaController::class, 'index']);
    Route::get('/cuotas/{id}', [CuotaController::class, 'show']);

    // PAGOS
    Route::get('/pagos', [PagoController::class, 'index']);
    Route::get('/pagos/{id}', [PagoController::class, 'show']);
    Route::post('/pagos', [PagoController::class, 'store']);

    // ACEPTACIONES DE TÉRMINOS
    Route::get('/aceptaciones-terminos', [AceptacionTerminoController::class, 'index']);
    Route::get('/aceptaciones-terminos/{id}', [AceptacionTerminoController::class, 'show']);
});

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

   // Route::get('/clientes', [ClienteController::class, 'index']);
   // Route::get('/clientes/{id}', [ClienteController::class, 'show']);
    Route::put('/clientes/{id}', [ClienteController::class, 'update']);
    Route::delete('/clientes/{id}', [ClienteController::class, 'destroy']);

   // PEDIDOS
    Route::put('/pedidos/{id}', [PedidoController::class, 'update']);

    // CUOTAS
    Route::post('/cuotas', [CuotaController::class, 'store']);
    Route::put('/cuotas/{id}', [CuotaController::class, 'update']);
    Route::delete('/cuotas/{id}', [CuotaController::class, 'destroy']);

    // PAGOS: CONFIRMAR O RECHAZAR
    Route::put('/pagos/{id}', [PagoController::class, 'update']);
    Route::delete('/pagos/{id}', [PagoController::class, 'destroy']);


});