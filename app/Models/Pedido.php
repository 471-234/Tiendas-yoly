<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';

    protected $fillable = [
        'cliente_id',
        'codigo_seguimiento',
        'tipo_pedido',
        'fecha_pedido',
        'total',
        'anticipo_requerido',
        'estado',
        'motivo_cancelacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_pedido' => 'datetime',
            'total' => 'decimal:2',
            'anticipo_requerido' => 'decimal:2',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    public function cuotas()
    {
        return $this->hasMany(Cuota::class);
    }

    public function aceptaciones()
    {
        return $this->hasMany(AceptacionTermino::class);
    }
}
