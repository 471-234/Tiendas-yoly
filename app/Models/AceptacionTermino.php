<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class AceptacionTermino extends Model
{
    use HasFactory;

    protected $table = 'aceptaciones_terminos';

    protected $fillable = [
        'cliente_id',
        'pedido_id',
        'version_terminos',
        'texto_terminos',
        'fecha_aceptacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_aceptacion' => 'datetime',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }
}
