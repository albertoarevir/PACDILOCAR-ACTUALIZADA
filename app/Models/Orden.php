<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orden extends Model
{
    use HasFactory; // Agrega HasFactory si no lo tienes
    protected $fillable = [
        'id',
        'modalidad',
        'numero',
        'estado_id',
        'estadocompras_id',
        'observacion',
        'monto',
        'id_proyecto',       // FK a Pac
        'id_licitacion',     // FK a Modalidad
        'fecha_seguimiento',
    ];

    public function estado()
    {
        return $this->belongsTo(EstadoLicitacion::class, 'estado_id'); // Asegúrate que 'estado_id' en ordens apunta a EstadoLicitacion
    }

    public function licitacion()
    {
        return $this->belongsTo(Modalidad::class, 'id_licitacion');
    }
    
    public function pac()
    {
        return $this->belongsTo(Pac::class, 'id_proyecto');
    }
    
    public function estadocompra()
    {
        return $this->belongsTo(EstadoCompra::class, 'estado_id'); // Si el nombre de la FK es 'estadocompras_id'
                                                                           // Si la FK es 'estado_id' y apunta a EstadoCompra, hay conflicto con EstadoLicitacion
    }

    // Esta relación está probablemente mal definida si 'id_proyecto' apunta a Pac.
    // Una orden tiene un departamento a través de su PAC.
    public function departamento()
    {
        return $this->hasOneThrough(
            Departamento::class,
            Pac::class,
            'id', // Foreign key on Pac table...
            'id', // Foreign key on Departamento table...
            'id_proyecto', // Local key on Orden table...
            'departamento_id' // Local key on Pac table...
        );
        // O más simplemente:
        // return $this->pac->departamento(); // Esto si ya cargaste 'pac'
    }

    public function pagoFacturas()
    {
        return $this->hasMany(PagoFactura::class, 'id_orden_compra');
    }
}