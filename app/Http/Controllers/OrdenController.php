<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Modalidad;
use App\Models\Pac;
use App\Models\PagoFactura;
use App\Models\Tipocompra;
use Illuminate\Http\Request;
use App\Models\EstadoCompra;
use App\Models\EstadoLicitacion;
use App\Models\Orden;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class OrdenController extends Controller
{
    /**
     * Garantiza que la tabla pago_facturas exista en PostgreSQL sin alterar otras tablas.
     */
    private function asegurarTablaPagoFacturas(): void
    {
        if (!Schema::hasTable('pago_facturas')) {
            Schema::create('pago_facturas', function (Blueprint $table) {
                $table->bigIncrements('id_pago');
                $table->unsignedBigInteger('id_proyecto');
                $table->unsignedBigInteger('id_licitacion')->nullable();
                $table->unsignedBigInteger('id_orden_compra');
                $table->string('numero_oficio', 100);
                $table->date('fecha_oficio');
                $table->unsignedSmallInteger('tipo_documento_id');
                $table->string('rut_proveedor', 15);
                $table->string('razon_social', 255);
                $table->string('numero_factura', 50);
                $table->date('fecha_factura');
                $table->decimal('monto_factura', 15, 0);
                $table->timestamps();

                $table->foreign('id_proyecto')
                    ->references('id')
                    ->on('pacs')
                    ->onDelete('cascade');

                $table->foreign('id_licitacion')
                    ->references('id')
                    ->on('modalidads')
                    ->onDelete('set null');

                $table->foreign('id_orden_compra')
                    ->references('id')
                    ->on('ordens')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Verifica si el estado seleccionado corresponde a "Enviada a Pago" / "Enviado a Pago".
     */
    private function esEstadoEnviadaAPago($estadoId): bool
    {
        if (!$estadoId) {
            return false;
        }
        $estado = EstadoCompra::find($estadoId);
        if (!$estado) {
            return false;
        }
        $detalle = strtolower(trim($estado->detalle));
        return in_array($detalle, ['enviada a pago', 'enviado a pago'], true);
    }

    /**
     * Extrae, normaliza y valida el listado de facturas (una o varias por oficio)
     * enviadas desde el modal "Antecedentes para pago Departamento L.8.".
     */
    private function obtenerYValidarFacturasPago(Request $request, bool $exigirAlMenosUna = true): array
    {
        $facturas = [];

        // 1. Revisar si vienen múltiples facturas acumuladas en JSON desde el modal
        $json = $request->input('facturas_l8_json');
        if (!empty($json)) {
            $decodificadas = json_decode($json, true);
            if (is_array($decodificadas)) {
                foreach ($decodificadas as $item) {
                    if (is_array($item)) {
                        $facturas[] = [
                            'numero_oficio'     => strtoupper(trim((string) ($item['numero_oficio'] ?? ''))),
                            'fecha_oficio'      => trim((string) ($item['fecha_oficio'] ?? '')),
                            'tipo_documento_id' => (int) ($item['tipo_documento_id'] ?? 0),
                            'rut_proveedor'     => PagoFactura::normalizarRutConGuion($item['rut_proveedor'] ?? ''),
                            'razon_social'      => strtoupper(trim((string) ($item['razon_social'] ?? ''))),
                            'numero_factura'    => str_replace('.', '', trim((string) ($item['numero_factura'] ?? ''))),
                            'fecha_factura'     => trim((string) ($item['fecha_factura'] ?? '')),
                            'monto_factura'     => str_replace(['.', ','], ['', '.'], (string) ($item['monto_factura'] ?? '')),
                        ];
                    }
                }
            }
        }

        // 2. Si además hay una factura digitada en los inputs actuales del modal que no fue agregada al JSON, incluirla
        $numeroFacturaActual = str_replace('.', '', trim((string) $request->input('numero_factura')));
        $montoFacturaActual  = str_replace(['.', ','], ['', '.'], (string) $request->input('monto_factura'));
        $fechaFacturaActual  = trim((string) $request->input('fecha_factura'));

        if ($numeroFacturaActual !== '' || $montoFacturaActual !== '' || $fechaFacturaActual !== '') {
            $candidata = [
                'numero_oficio'     => strtoupper(trim((string) $request->input('numero_oficio'))),
                'fecha_oficio'      => trim((string) $request->input('fecha_oficio')),
                'tipo_documento_id' => (int) $request->input('tipo_documento_id'),
                'rut_proveedor'     => PagoFactura::normalizarRutConGuion($request->input('rut_proveedor')),
                'razon_social'      => strtoupper(trim((string) $request->input('razon_social'))),
                'numero_factura'    => $numeroFacturaActual,
                'fecha_factura'     => $fechaFacturaActual,
                'monto_factura'     => $montoFacturaActual,
            ];

            $yaExisteEnLista = false;
            foreach ($facturas as $f) {
                if (
                    $f['numero_oficio'] === $candidata['numero_oficio'] &&
                    $f['numero_factura'] === $candidata['numero_factura'] &&
                    (string) $f['monto_factura'] === (string) $candidata['monto_factura']
                ) {
                    $yaExisteEnLista = true;
                    break;
                }
            }

            if (!$yaExisteEnLista) {
                $facturas[] = $candidata;
            }
        }

        if (empty($facturas)) {
            if ($exigirAlMenosUna) {
                throw ValidationException::withMessages([
                    'numero_oficio' => 'Debe registrar al menos una factura en los Antecedentes para pago Departamento L.8.',
                ]);
            }
            return [];
        }

        // Validar cada fila de factura que se va a insertar y que la suma no supere el Monto O/C
        $montoOrdenRef = (float) str_replace(['.', ','], ['', '.'], (string) $request->input('monto', 0));
        $sumaFacturasNuevas = 0;

        foreach ($facturas as $idx => $f) {
            $numFila = $idx + 1;
            if ($f['numero_oficio'] === '') {
                throw ValidationException::withMessages(['numero_oficio' => "El N° de Oficio es obligatorio (Factura #{$numFila})."]);
            }
            if ($f['fecha_oficio'] === '') {
                throw ValidationException::withMessages(['fecha_oficio' => "La Fecha de Oficio es obligatoria (Factura #{$numFila})."]);
            }
            if (!in_array($f['tipo_documento_id'], [1, 2, 3, 4, 5], true)) {
                throw ValidationException::withMessages(['tipo_documento_id' => "Seleccione un Tipo de Documento válido (Factura #{$numFila})."]);
            }
            if (!preg_match('/^[0-9]+-[0-9Kk]$/', $f['rut_proveedor'])) {
                throw ValidationException::withMessages(['rut_proveedor' => "El RUT del Proveedor debe ir sin puntos y con guion (Factura #{$numFila})."]);
            }
            if ($f['razon_social'] === '') {
                throw ValidationException::withMessages(['razon_social' => "La Razón Social es obligatoria (Factura #{$numFila})."]);
            }
            if ($f['numero_factura'] === '') {
                throw ValidationException::withMessages(['numero_factura' => "El N° de Factura es obligatorio (Factura #{$numFila})."]);
            }
            if ($f['fecha_factura'] === '') {
                throw ValidationException::withMessages(['fecha_factura' => "La Fecha de Factura es obligatoria (Factura #{$numFila})."]);
            }
            if (!is_numeric($f['monto_factura']) || (float) $f['monto_factura'] <= 0) {
                throw ValidationException::withMessages(['monto_factura' => "El Monto de la Factura debe ser mayor a 0 (Factura #{$numFila})."]);
            }

            $sumaFacturasNuevas += (float) $f['monto_factura'];
            if ($montoOrdenRef > 0 && $sumaFacturasNuevas > $montoOrdenRef) {
                throw ValidationException::withMessages([
                    'monto_factura' => 'El monto acumulado de las facturas ($ ' . number_format($sumaFacturasNuevas, 0, ',', '.') . ') no puede superar el Monto de la Orden de Compra ($ ' . number_format($montoOrdenRef, 0, ',', '.') . ').',
                ]);
            }
        }

        return $facturas;
    }

    /**
     * Inserta cada factura como una fila independiente en la tabla pago_facturas,
     * manteniendo los mismos datos de proyecto, licitación, orden de compra, oficio y proveedor.
     */
    private function guardarPagosFacturas(Orden $orden, array $facturas): void
    {
        if (empty($facturas)) {
            return;
        }

        $this->asegurarTablaPagoFacturas();

        foreach ($facturas as $factura) {
            PagoFactura::create([
                'id_proyecto'       => $orden->id_proyecto,
                'id_licitacion'     => $orden->id_licitacion,
                'id_orden_compra'   => $orden->id,
                'numero_oficio'     => $factura['numero_oficio'],
                'fecha_oficio'      => $factura['fecha_oficio'],
                'tipo_documento_id' => (int) $factura['tipo_documento_id'],
                'rut_proveedor'     => $factura['rut_proveedor'],
                'razon_social'      => $factura['razon_social'],
                'numero_factura'    => $factura['numero_factura'],
                'fecha_factura'     => $factura['fecha_factura'],
                'monto_factura'     => $factura['monto_factura'],
            ]);
        }
    }

    /**
     * Guardado ejecutivo e inmediato en base de datos al hacer clic en "Guardar"
     * dentro del modal de Antecedentes para pago Departamento L.8.
     */
    public function guardarFacturaAjax(Request $request, $id)
    {
        $this->asegurarTablaPagoFacturas();

        $orden    = Orden::findOrFail($id);
        $user     = auth()->user();
        $isAdmin  = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;
        $pacOwner = Pac::find($orden->id_proyecto);

        if (!$isAdmin && $pacOwner && (int) $pacOwner->departamento_id !== (int) $user->departamento_id) {
            return response()->json(['ok' => false, 'message' => 'No tienes permiso para modificar esta orden.'], 403);
        }

        $numeroOficio    = strtoupper(trim((string) $request->input('numero_oficio')));
        $fechaOficio     = trim((string) $request->input('fecha_oficio'));
        $tipoDocumentoId = (int) $request->input('tipo_documento_id');
        $rutProveedor    = PagoFactura::normalizarRutConGuion($request->input('rut_proveedor'));
        $razonSocial     = strtoupper(trim((string) $request->input('razon_social')));
        $numeroFactura   = str_replace('.', '', trim((string) $request->input('numero_factura')));
        $fechaFactura    = trim((string) $request->input('fecha_factura'));
        $montoFactura    = str_replace(['.', ','], ['', '.'], (string) $request->input('monto_factura'));

        if (
            $numeroOficio === '' ||
            $fechaOficio === '' ||
            !in_array($tipoDocumentoId, [1, 2, 3, 4, 5], true) ||
            !preg_match('/^[0-9]+-[0-9Kk]$/', $rutProveedor) ||
            $razonSocial === '' ||
            $numeroFactura === '' ||
            $fechaFactura === '' ||
            !is_numeric($montoFactura) ||
            (float) $montoFactura <= 0
        ) {
            return response()->json([
                'ok'      => false,
                'message' => 'Por favor verifique que todos los campos del oficio y la factura estén completos y sean válidos.',
            ], 422);
        }

        $montoOrdenTotal = (float) str_replace(['.', ','], ['', '.'], (string) ($request->input('monto_oc') ?? $orden->monto));
        $sumaYaRegistrada = (float) $orden->pagoFacturas()->sum('monto_factura');
        $saldoDisponible = max(0, $montoOrdenTotal - $sumaYaRegistrada);

        if ((float) $montoFactura > $montoOrdenTotal || ($sumaYaRegistrada + (float) $montoFactura) > $montoOrdenTotal) {
            return response()->json([
                'ok'      => false,
                'message' => 'El monto ingresado ($ ' . number_format((float) $montoFactura, 0, ',', '.') . ') más las facturas ya pagadas ($ ' . number_format($sumaYaRegistrada, 0, ',', '.') . ') supera el Monto de la Orden de Compra ($ ' . number_format($montoOrdenTotal, 0, ',', '.') . '). Saldo disponible por pagar: $ ' . number_format($saldoDisponible, 0, ',', '.') . '.',
            ], 422);
        }

        $nuevaFactura = DB::transaction(function () use (
            $orden,
            $request,
            $numeroOficio,
            $fechaOficio,
            $tipoDocumentoId,
            $rutProveedor,
            $razonSocial,
            $numeroFactura,
            $fechaFactura,
            $montoFactura
        ) {
            if ($request->filled('estado_id') && $this->esEstadoEnviadaAPago($request->input('estado_id'))) {
                $orden->estado_id = (int) $request->input('estado_id');
                $orden->save();
            }

            return PagoFactura::create([
                'id_proyecto'       => $orden->id_proyecto,
                'id_licitacion'     => $orden->id_licitacion,
                'id_orden_compra'   => $orden->id,
                'numero_oficio'     => $numeroOficio,
                'fecha_oficio'      => $fechaOficio,
                'tipo_documento_id' => $tipoDocumentoId,
                'rut_proveedor'     => $rutProveedor,
                'razon_social'      => $razonSocial,
                'numero_factura'    => $numeroFactura,
                'fecha_factura'     => $fechaFactura,
                'monto_factura'     => $montoFactura,
            ]);
        });

        return response()->json([
            'ok'      => true,
            'factura' => [
                'id_pago'           => $nuevaFactura->id_pago,
                'numero_oficio'     => $nuevaFactura->numero_oficio,
                'fecha_oficio_ymd'  => Carbon::parse($nuevaFactura->fecha_oficio)->format('Y-m-d'),
                'tipo_documento_id' => $nuevaFactura->tipo_documento_id,
                'rut_proveedor'     => $nuevaFactura->rut_proveedor,
                'razon_social'      => $nuevaFactura->razon_social,
                'numero_factura'    => $nuevaFactura->numero_factura,
                'fecha_factura'     => Carbon::parse($nuevaFactura->fecha_factura)->format('d-m-Y'),
                'fecha_factura_ymd' => Carbon::parse($nuevaFactura->fecha_factura)->format('Y-m-d'),
                'monto_factura'     => number_format((float) $nuevaFactura->monto_factura, 0, ',', '.'),
                'monto_raw'         => (float) $nuevaFactura->monto_factura,
            ],
            'total_facturas' => $orden->pagoFacturas()->count(),
            'suma_facturas'  => (float) $orden->pagoFacturas()->sum('monto_factura'),
        ]);
    }

    /**
     * Modifica vía AJAX una factura ya registrada en la tabla pago_facturas sin salir del modal.
     */
    public function actualizarFacturaAjax(Request $request, $id_pago)
    {
        $this->asegurarTablaPagoFacturas();

        $pagoFactura = PagoFactura::findOrFail($id_pago);
        $orden       = Orden::findOrFail($pagoFactura->id_orden_compra);
        $user        = auth()->user();
        $isAdmin     = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;
        $pacOwner    = Pac::find($orden->id_proyecto);

        if (!$isAdmin && $pacOwner && (int) $pacOwner->departamento_id !== (int) $user->departamento_id) {
            return response()->json(['ok' => false, 'message' => 'No tienes permiso para modificar esta factura.'], 403);
        }

        $numeroOficio    = strtoupper(trim((string) $request->input('numero_oficio')));
        $fechaOficio     = trim((string) $request->input('fecha_oficio'));
        $tipoDocumentoId = (int) $request->input('tipo_documento_id');
        $rutProveedor    = PagoFactura::normalizarRutConGuion($request->input('rut_proveedor'));
        $razonSocial     = strtoupper(trim((string) $request->input('razon_social')));
        $numeroFactura   = str_replace('.', '', trim((string) $request->input('numero_factura')));
        $fechaFactura    = trim((string) $request->input('fecha_factura'));
        $montoFactura    = str_replace(['.', ','], ['', '.'], (string) $request->input('monto_factura'));

        if (
            $numeroOficio === '' ||
            $fechaOficio === '' ||
            !in_array($tipoDocumentoId, [1, 2, 3, 4, 5], true) ||
            !preg_match('/^[0-9]+-[0-9Kk]$/', $rutProveedor) ||
            $razonSocial === '' ||
            $numeroFactura === '' ||
            $fechaFactura === '' ||
            !is_numeric($montoFactura) ||
            (float) $montoFactura <= 0
        ) {
            return response()->json([
                'ok'      => false,
                'message' => 'Por favor verifique que todos los campos del oficio y la factura estén completos y sean válidos.',
            ], 422);
        }

        $montoOrdenTotal = (float) str_replace(['.', ','], ['', '.'], (string) ($request->input('monto_oc') ?? $orden->monto));
        $sumaOtrasFacturas = (float) $orden->pagoFacturas()->where('id_pago', '!=', $pagoFactura->id_pago)->sum('monto_factura');
        $saldoDisponible = max(0, $montoOrdenTotal - $sumaOtrasFacturas);

        if ((float) $montoFactura > $montoOrdenTotal || ($sumaOtrasFacturas + (float) $montoFactura) > $montoOrdenTotal) {
            return response()->json([
                'ok'      => false,
                'message' => 'El monto modificado ($ ' . number_format((float) $montoFactura, 0, ',', '.') . ') más las demás facturas registradas ($ ' . number_format($sumaOtrasFacturas, 0, ',', '.') . ') supera el Monto de la Orden de Compra ($ ' . number_format($montoOrdenTotal, 0, ',', '.') . '). Saldo máximo permitido: $ ' . number_format($saldoDisponible, 0, ',', '.') . '.',
            ], 422);
        }

        $pagoFactura->update([
            'numero_oficio'     => $numeroOficio,
            'fecha_oficio'      => $fechaOficio,
            'tipo_documento_id' => $tipoDocumentoId,
            'rut_proveedor'     => $rutProveedor,
            'razon_social'      => $razonSocial,
            'numero_factura'    => $numeroFactura,
            'fecha_factura'     => $fechaFactura,
            'monto_factura'     => $montoFactura,
        ]);

        return response()->json([
            'ok'      => true,
            'factura' => [
                'id_pago'           => $pagoFactura->id_pago,
                'numero_oficio'     => $pagoFactura->numero_oficio,
                'fecha_oficio_ymd'  => Carbon::parse($pagoFactura->fecha_oficio)->format('Y-m-d'),
                'tipo_documento_id' => $pagoFactura->tipo_documento_id,
                'rut_proveedor'     => $pagoFactura->rut_proveedor,
                'razon_social'      => $pagoFactura->razon_social,
                'numero_factura'    => $pagoFactura->numero_factura,
                'fecha_factura'     => Carbon::parse($pagoFactura->fecha_factura)->format('d-m-Y'),
                'fecha_factura_ymd' => Carbon::parse($pagoFactura->fecha_factura)->format('Y-m-d'),
                'monto_factura'     => number_format((float) $pagoFactura->monto_factura, 0, ',', '.'),
                'monto_raw'         => (float) $pagoFactura->monto_factura,
            ],
            'total_facturas' => $orden->pagoFacturas()->count(),
            'suma_facturas'  => (float) $orden->pagoFacturas()->sum('monto_factura'),
        ]);
    }

    /**
     * Elimina vía AJAX una factura registrada en la tabla pago_facturas sin salir del modal.
     */
    public function eliminarFacturaAjax(Request $request, $id_pago)
    {
        $this->asegurarTablaPagoFacturas();

        $pagoFactura = PagoFactura::findOrFail($id_pago);
        $orden       = Orden::findOrFail($pagoFactura->id_orden_compra);
        $user        = auth()->user();
        $isAdmin     = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;
        $pacOwner    = Pac::find($orden->id_proyecto);

        if (!$isAdmin && $pacOwner && (int) $pacOwner->departamento_id !== (int) $user->departamento_id) {
            return response()->json(['ok' => false, 'message' => 'No tienes permiso para eliminar esta factura.'], 403);
        }

        $pagoFactura->delete();

        return response()->json([
            'ok'             => true,
            'id_pago'        => (int) $id_pago,
            'total_facturas' => $orden->pagoFacturas()->count(),
            'suma_facturas'  => (float) $orden->pagoFacturas()->sum('monto_factura'),
        ]);
    }

    public function index()
    {
        $user     = auth()->user();
        $dotacion = $user->departamento_id;
        $isAdmin  = $user->hasRole('ADMINISTRADOR') || (int) $dotacion === 7;

        if ($isAdmin) {
            $ordenes = Orden::with('estadocompra', 'pac.especie', 'licitacion')->orderBy('id', 'desc')->get();
        } else {
            $ordenes = Orden::whereHas('pac', function ($query) use ($dotacion) {
                $query->where('departamento_id', $dotacion);
            })->with('estadocompra', 'pac.especie', 'licitacion')->orderBy('id', 'desc')->get();
        }

        $ordenes->transform(function ($orden) {
            $fechaActual       = Carbon::now();
            $fechaRegistro     = Carbon::parse($orden->updated_at);
            $diasTranscurridos = $fechaActual->diffInDays($fechaRegistro);

            if ($orden->estado_id == 1 && $diasTranscurridos >= 20) {
                $orden->auditoria = '<span style="color: red;"><strong>Han pasado ' . $diasTranscurridos . ' dias sin actualizar</strong></span>';
            } else {
                $orden->auditoria = '<span style="color: green;"><strong>Sin observacion</strong></span>';
            }
            return $orden;
        });

        $pac = Pac::first();

        if ($pac === null) {
            return view('ordenes.index', ['mensaje' => 'NO HAY REGISTROS']);
        }

        $ordenesCount = $ordenes ? $ordenes->count() : 0;

        if ($ordenesCount == 0) {
            return view('ordenes.index', ['pac' => $pac, 'ordenesCount' => $ordenesCount, 'mensaje' => 'NO HAY REGISTROS']);
        }

        return view('ordenes.index', ['ordenes' => $ordenes, 'pac' => $pac, 'ordenesCount' => $ordenesCount]);
    }

    public function create(Request $request, $pac, $modalidad, $numero, $id_mod)
    {
        $this->asegurarTablaPagoFacturas();

        $pac_id         = $pac;
        $modalidades    = Modalidad::all();
        $tipocompras    = Tipocompra::all();
        $estados        = EstadoLicitacion::all();
        $estadocompras  = EstadoCompra::all();
        $tiposDocumento = PagoFactura::TIPOS_DOCUMENTO;

        $modalidad_obj = Modalidad::find($id_mod);
        $id_mod        = $modalidad_obj ? $modalidad_obj->id : null;

        return view('ordenes.create', compact(
            'pac_id',
            'id_mod',
            'tipocompras',
            'estados',
            'modalidades',
            'numero',
            'modalidad',
            'estadocompras',
            'tiposDocumento'
        ));
    }

    public function store(Request $request, $pac_id, $id_mod)
    {
        $pac     = Pac::findOrFail($pac_id);
        $user    = auth()->user();
        $isAdmin = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;

        if (!$isAdmin && (int) $pac->departamento_id !== (int) $user->departamento_id) {
            abort(403, 'No tienes permiso para crear ordenes en este PAC.');
        }

        $montoLimpio = str_replace(['.', ','], ['', '.'], $request->monto);

        $request->merge([
            'numero' => strtoupper(str_replace('/', '-', $request->numero)),
            'monto'  => $montoLimpio,
        ]);

        $request->validate([
            'numero'            => 'required|string|max:15|regex:/^[A-Z0-9-]+$/',
            'estado_id'         => 'required|exists:estado_compras,id',
            'observacion'       => 'nullable|string|max:1000',
            'monto'             => 'required|numeric|min:0',
            'fecha_seguimiento' => 'required|date',
        ], [
            'numero.regex'  => 'El numero de orden solo permite letras, numeros y guiones (-).',
            'numero.max'    => 'El numero de orden no puede superar los 15 caracteres.',
            'monto.numeric' => 'El monto debe ser un valor numerico.',
        ]);

        $requierePago = $this->esEstadoEnviadaAPago($request->estado_id);
        $facturasParaGuardar = [];

        if ($requierePago) {
            $facturasParaGuardar = $this->obtenerYValidarFacturasPago($request, true);
        }

        DB::transaction(function () use ($request, $pac_id, $id_mod, $facturasParaGuardar) {
            $orden                    = new Orden();
            $orden->numero            = $request->numero;
            $orden->estado_id         = $request->estado_id;
            $orden->observacion       = $request->observacion;
            $orden->monto             = $request->monto;
            $orden->id_proyecto       = $pac_id;
            $orden->id_licitacion     = $id_mod;
            $orden->fecha_seguimiento = $request->fecha_seguimiento;
            $orden->save();

            if (!empty($facturasParaGuardar)) {
                $this->guardarPagosFacturas($orden, $facturasParaGuardar);
            }
        });

        return redirect()->route('ordenes.index')->with('success', 'Orden de compra guardada correctamente.');
    }

    public function edit($id)
    {
        $this->asegurarTablaPagoFacturas();

        $orden    = Orden::findOrFail($id);
        $user     = auth()->user();
        $isAdmin  = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;
        $pacOwner = Pac::find($orden->id_proyecto);

        if (!$isAdmin && $pacOwner && (int) $pacOwner->departamento_id !== (int) $user->departamento_id) {
            abort(403, 'No tienes permiso para editar esta orden.');
        }

        $pac            = $pacOwner;
        $tipocompras    = Tipocompra::all();
        $estados        = EstadoLicitacion::all();
        $modalidad      = Modalidad::find($orden->id_licitacion);
        $estadocompras  = EstadoCompra::all();
        $tiposDocumento = PagoFactura::TIPOS_DOCUMENTO;
        $pagosFacturas  = $orden->pagoFacturas()->orderBy('id_pago', 'asc')->get();
        $pagoFactura    = $pagosFacturas->last();

        return view('ordenes.edit', compact(
            'orden',
            'tipocompras',
            'estados',
            'pac',
            'modalidad',
            'estadocompras',
            'tiposDocumento',
            'pagoFactura',
            'pagosFacturas'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'numero' => strtoupper(str_replace('/', '-', $request->numero))
        ]);

        $request->validate([
            'numero'            => 'required|string|max:15|regex:/^[A-Z0-9-]+$/',
            'estado_id'         => 'required|exists:estado_compras,id',
            'observacion'       => 'nullable',
            'monto'             => 'required',
            'fecha_seguimiento' => 'nullable',
        ], [
            'numero.regex' => 'El numero de orden solo permite letras, numeros y guiones (-).',
            'numero.max'   => 'El numero de orden no puede superar los 15 caracteres.',
        ]);

        $monto    = str_replace(['.', ','], ['', '.'], $request->monto);
        $orden    = Orden::findOrFail($id);
        $user     = auth()->user();
        $isAdmin  = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;
        $pacOwner = Pac::find($orden->id_proyecto);

        if (!$isAdmin && $pacOwner && (int) $pacOwner->departamento_id !== (int) $user->departamento_id) {
            abort(403, 'No tienes permiso para modificar esta orden.');
        }

        $requierePago        = $this->esEstadoEnviadaAPago($request->estado_id);
        $facturasParaGuardar = [];

        if ($requierePago) {
            $yaTieneFacturasEnBD = $orden->pagoFacturas()->exists();
            $facturasParaGuardar = $this->obtenerYValidarFacturasPago($request, !$yaTieneFacturasEnBD);
        }

        DB::transaction(function () use ($orden, $request, $monto, $facturasParaGuardar) {
            $orden->numero            = $request->numero;
            $orden->estado_id         = $request->estado_id;
            $orden->observacion       = $request->observacion;
            $orden->monto             = $monto;
            $orden->fecha_seguimiento = $request->fecha_seguimiento;
            // id_proyecto e id_licitacion son inmutables una vez creada la orden
            $orden->save();

            if (!empty($facturasParaGuardar)) {
                $this->guardarPagosFacturas($orden, $facturasParaGuardar);
            }
        });

        return redirect()->route('ordenes.index')->with('mensaje', 'Se actualizo el registro correctamente');
    }

    public function destroy($id)
    {
        $orden    = Orden::findOrFail($id);
        $user     = auth()->user();
        $isAdmin  = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;
        $pacOwner = Pac::find($orden->id_proyecto);

        if (!$isAdmin && $pacOwner && (int) $pacOwner->departamento_id !== (int) $user->departamento_id) {
            abort(403, 'No tienes permiso para eliminar esta orden.');
        }

        $orden->delete();

        return redirect()->route('ordenes.index')->with('mensaje', 'Se elimino el registro correctamente');
    }
}
