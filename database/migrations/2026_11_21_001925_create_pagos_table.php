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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pedido_id')
                ->constrained('pedidos')
                ->cascadeOnDelete();

            $table->foreignId('cuota_id')
                ->nullable()
                ->constrained('cuotas')
                ->nullOnDelete();

            $table->foreignId('usuario_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->decimal('monto', 10, 2);

            $table->enum('metodo_pago', [
                'transferencia',
                'efectivo'
            ]);

            $table->dateTime('fecha_pago');

            $table->string('referencia')->nullable();
            $table->string('comprobante')->nullable();

            $table->enum('estado', [
                'pendiente_verificacion',
                'confirmado',
                'rechazado'
            ])->default('pendiente_verificacion');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
