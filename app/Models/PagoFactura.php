<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoFactura extends Model
{
    use HasFactory;

    protected $table = 'pago_facturas';
    protected $primaryKey = 'id_pago';

    /**
     * Catálogo de Tipos de Documento (se almacena solo el ID en tipo_documento_id).
     */
    public const TIPOS_DOCUMENTO = [
        1 => 'Factura',
        2 => 'Carta de Crédito',
        3 => 'Pago Impuesto',
        4 => 'Gasto Bancario',
        5 => 'Gastos Comunes',
    ];

    protected $fillable = [
        'id_proyecto',
        'id_licitacion',
        'id_orden_compra',
        'numero_oficio',
        'fecha_oficio',
        'tipo_documento_id',
        'rut_proveedor',
        'razon_social',
        'numero_factura',
        'fecha_factura',
        'monto_factura',
    ];

    protected $casts = [
        'fecha_oficio'      => 'date',
        'fecha_factura'     => 'date',
        'tipo_documento_id' => 'integer',
        'monto_factura'     => 'float',
    ];

    /**
     * Obtiene la descripción del tipo de documento a partir de su ID.
     */
    public function getTipoDocumentoNombreAttribute(): string
    {
        return self::TIPOS_DOCUMENTO[$this->tipo_documento_id] ?? 'Desconocido';
    }

    /**
     * Normaliza el RUT del proveedor: sin puntos y con guion (ej: 76123456-K).
     */
    public static function normalizarRutConGuion(?string $rut): string
    {
        $limpio = strtoupper(preg_replace('/[^0-9kK]/', '', (string) $rut));
        if (strlen($limpio) < 2) {
            return $limpio;
        }
        return substr($limpio, 0, -1) . '-' . substr($limpio, -1);
    }

    public function pac()
    {
        return $this->belongsTo(Pac::class, 'id_proyecto');
    }

    public function licitacion()
    {
        return $this->belongsTo(Modalidad::class, 'id_licitacion');
    }

    public function orden()
    {
        return $this->belongsTo(Orden::class, 'id_orden_compra');
    }
}

