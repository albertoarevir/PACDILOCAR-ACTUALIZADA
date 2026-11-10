<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modalidad extends Model
{
    use HasFactory; // Agrega HasFactory si no lo tienes
    protected $fillable = [
        'id',
        'modalidad',
        'numero',
        'estado_id',
        'observacion',
        'id_proyecto',
        //       'pac_id', // Si esta columna realmente existe y es la FK a `pacs.id`, ¡úsalas!
        // 'id_proyecto', // Si esta es la verdadera FK a `pacs.id`, entonces tu fillable debería reflejarlo.
    ];
    protected $table = 'modalidads'; // Asegúrate de que el nombre de la tabla sea correcto

    public function estado()
    {
        return $this->belongsTo(EstadoLicitacion::class, 'estado_id');
    }

    public function pac()
    {
        // ¡VERIFICA ESTO! Si tu tabla `modalidads` tiene `pac_id` como FK a `pacs.id`, usa 'pac_id'.
        // Si tiene `id_proyecto` como FK a `pacs.id`, usa 'id_proyecto'.
        // Basado en tu `fillable` que tiene `pac_id` y tu comentario `#pac_id`, lo más probable es que sea `pac_id`.
        return $this->belongsTo(Pac::class, 'id_proyecto'); 
        // Si no tienes `pac_id` y sí `id_proyecto` en la tabla, entonces mantén:
        // return $this->belongsTo(Pac::class, 'id_proyecto');
    }

    public function ordenes()
    {
        // ¡PROBLEMA RESUELTO AQUÍ! Elimina el ->where() de la definición de la relación.
        // La relación `hasMany` ya sabe cómo encontrar las órdenes relacionadas a esta modalidad por 'id_licitacion'.
        return $this->hasMany(Orden::class, 'id_licitacion', 'id');
    }

    public function pagoFacturas()
    {
        return $this->hasMany(PagoFactura::class, 'id_licitacion', 'id');
    }
}