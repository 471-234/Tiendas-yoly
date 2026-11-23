<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'descripcion',
        'precio',
        'cantidad',
        'condicion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'cantidad' => 'integer',
        ];
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function imagenes()
    {
        return $this->hasMany(ProductoImagen::class);
    }

    public function detallesPedido()
    {
        return $this->hasMany(DetallePedido::class);
    }
}
