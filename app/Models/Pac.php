<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pac extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'year',
        'departamento',
        'especie',
        'cantidad',
        'total_presupuesto', 
        'clasificador',
        'codigo',
        'unidad_compra',
        'estado',
        'estado_id',
        'observaciones',
        'estado_modificacion',
    ];

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function especie()
    {
        return $this->belongsTo(Especie::class, 'especie_id');
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    public function modalidades()
    {
        return $this->hasMany(Modalidad::class, 'id_proyecto');
    }

    public function estadocompra()
    {
        return $this->hasOne(EstadoCompra::class, 'id', 'estado_id');
    }

    public function ordenes()
    {
        return $this->hasMany(Orden::class, 'id_proyecto');
    }

    public function infoPresupuesto()
    {
        return $this->belongsTo(\App\Models\Presupuesto::class, 'codigo', 'item');
    }

    public function pagoFacturas()
    {
        return $this->hasMany(PagoFactura::class, 'id_proyecto');
    }
} // <-- Solo una llave aquí para cerrar la clase