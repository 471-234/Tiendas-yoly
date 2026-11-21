<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->restrictOnDelete();

            $table->enum('tipo_pedido', [
                'normal',
                'personalizado'
            ]);

            $table->dateTime('fecha_pedido');

            $table->decimal('total', 10, 2);

            $table->decimal('anticipo_requerido', 10, 2)
                ->default(0);

            $table->enum('estado', [
                'pendiente_pago',
                'anticipo_recibido',
                'solicitado_proveedor',
                'recibido_tienda',
                'listo_entrega',
                'entregado',
                'cancelado'
            ])->default('pendiente_pago');
            $table->string('codigo_seguimiento', 30)->unique();
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
