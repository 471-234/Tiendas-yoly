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
        Schema::create('cuotas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pedido_id')
                ->constrained('pedidos')
                ->cascadeOnDelete();

            $table->unsignedInteger('numero_cuota');

            $table->decimal('monto', 10, 2);

            $table->date('fecha_vencimiento');

            $table->enum('estado', [
                'pendiente',
                'parcial',
                'pagada'
            ])->default('pendiente');

            $table->timestamps();

            $table->unique([
                'pedido_id',
                'numero_cuota'
            ]);
    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuotas');
    }
};
