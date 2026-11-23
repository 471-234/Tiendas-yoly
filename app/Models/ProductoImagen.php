<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductoImagen extends Model
{
    use HasFactory;

    protected $table = 'producto_imagenes';

    protected $fillable = [
        'producto_id',
        'ruta_imagen',
        'es_principal',
    ];

    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
        ];
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}