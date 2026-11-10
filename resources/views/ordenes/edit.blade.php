@extends('layouts.admin')
@section('content')
    <div class="row">
        <div class="col-md-2">
            <div class="card card-outline card-success" style="margin-left: 50px;">
                <div class="card-body badge-btn">
                    <h2 style="font-size: 18px; color:rgb(8, 8, 8)">
                        <strong>ID del Proyecto N°:</strong>
                    </h2>
                    <h2 style="font-size: 18px; color:rgb(8, 8, 8); margin-bottom: 3;">
                        {{ $orden ? str_pad($orden->id_proyecto, 4, '0', STR_PAD_LEFT) : 'N/A' }}
                    </h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-outline card-success" style="margin-left: 10px">
                <div class="card-body badge-btn">
                    <h2 style="font-size: 18px; color:rgb(8, 8, 8)">
                        <strong> Modalidad de Compra:</strong>
                    </h2>
                    @if ($orden->licitacion)
                        <h2 style="font-size: 18px; color:rgb(8, 8, 8); margin-bottom: 3;">
                            {{ $orden->licitacion->modalidad }}
                        </h2>
                    @else
                        <h2 style="font-size: 18px; color:rgb(8, 8, 8); margin-bottom: 3;">
                            <strong>No registra tipo de licitación</strong>
                        </h2>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card card-outline card-success" style="margin-left: 10px">
                <div class="card-body badge-btn">
                    <h2 style="font-size: 18px; color:rgb(8, 8, 8)">
                        <strong> N° de licitación:</strong>
                    </h2>
                    @if ($orden->licitacion)
                        <h2 style="font-size: 18px; color:rgb(8, 8, 8); margin-bottom: 3;">
                            {{ $orden->licitacion->numero }}
                        </h2>
                    @else
                        <h2 style="font-size: 18px; color:rgb(8, 8, 8); margin-bottom: 3;">
                            <strong>No registra tipo de licitación</strong>
                        </h2>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-outline card-success" style="margin-left: 10px">
                <div class="card-body badge-btn">
                    <h2 style="font-size: 18px; color:rgb(5, 5, 5)">
                        <strong>Especies o Servicios:</strong>
                    </h2>
                    @if ($orden->pac->especie)
                        <h2 style="font-size: 18px; color:rgb(10, 10, 10); margin-bottom: 3;">
                            {{ $orden->pac->especie->detalle }}
                        </h2>
                    @else
                        <h2 style="font-size: 18px; color:rgb(8, 8, 8); margin-bottom: 3;">
                            <strong>No registra tipo de licitación</strong>
                        </h2>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <br>
    <div class="row" style="margin-left: 2px">
        <h2 style="font-size: 30px; color:rgb(12, 12, 12); margin-bottom: 3; margin-left: 35px;"><strong>Formulario de
                Modificación "ORDEN DE COMPRAS"</strong></h2>
    </div>
    <br>

    @php
        $cantidadFacturasBD = isset($pagosFacturas) ? $pagosFacturas->count() : 0;
        $sumaFacturasBD = isset($pagosFacturas) ? (float) $pagosFacturas->sum('monto_factura') : 0;
        $montoOrdenInicial = isset($orden->monto) ? (float) str_replace('.', '', (string) $orden->monto) : 0;
        $diferenciaPorPagarInicial = $montoOrdenInicial - $sumaFacturasBD;
    @endphp

    <div class="row">
        <div class=col-md-10 style="margin-left: 30px">
            <div class="card card-outline card-success">
                <div class="card-body">
                    <form action="{{ route('ordenes.update', $orden->id) }}" method="post" id="formOrden">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id_proyecto" value="{{ $orden->id_proyecto }}">
                        <input type="hidden" name="id_licitacion" value="{{ $orden->id_licitacion }}">
                        <input type="hidden" name="facturas_l8_json" id="facturas_l8_json" value="{{ old('facturas_l8_json', '[]') }}">

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="numero">Número Orden de Compra</label>
                                    <input type="text"
                                        value="{{ old('numero', $orden->numero ?? '') }}" name="numero" id="numero"
                                        maxlength="15"
                                        class="form-control @error('numero') is-invalid @enderror"
                                        placeholder="EJ: OC-12345" required
                                        oninput="this.value = this.value.toUpperCase().replace(/\//g, '-').replace(/[^A-Z0-9-]/g, '')">

                                    @error('numero')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="monto">Monto O/C</label>
                                    <input type="text" id="monto"
                                        class="form-control @error('monto') is-invalid @enderror"
                                        name="monto"
                                        value="{{ old('monto') ? number_format((int) str_replace('.', '', old('monto')), 0, ',', '.') : (isset($orden->monto) ? number_format((int) str_replace('.', '', $orden->monto), 0, ',', '.') : '') }}"
                                        maxlength="15"
                                        placeholder="Ej: 1.500.000"
                                        required>
                                    @error('monto')
                                        <small style="color: red">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estado_id">Estado del registro</label>
                                    <select name="estado_id" id="estado_id"
                                        class="form-control @error('estado_id') is-invalid @enderror" required>
                                        <option value="">-- Ingrese el estado actual --</option>
                                        @foreach ($estadocompras as $estado)
                                            <option value="{{ $estado->id }}"
                                                {{ (string) old('estado_id', $orden->estado_id) === (string) $estado->id ? 'selected' : '' }}>
                                                {{ $estado->detalle }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('estado_id')
                                        <small style="color: red">{{ $message }}</small>
                                    @enderror
                                    @if ($orden->estado_id == null)
                                        <small style="color: red">No se encontró el registro de estado seleccionado.</small>
                                    @endif

                                    <div id="contenedorBotonPagoL8" class="mt-2" style="display: none;">
                                        <button type="button" class="btn btn-sm btn-info" id="btnReabrirModalPagoL8">
                                            <i class="bi bi-receipt"></i> Antecedentes Pago L.8.
                                        </button>
                                        <span id="badgePagoL8Confirmado" class="badge badge-success ml-1" style="{{ $cantidadFacturasBD > 0 ? 'display: inline-block;' : 'display: none;' }}">
                                            {{ $cantidadFacturasBD > 0 ? $cantidadFacturasBD . ' registrada(s)' : '' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_seguimiento">Fecha estimativa seguimiento</label>
                                    <input type="date" id="fecha_seguimiento" value="{{ old('fecha_seguimiento', $orden->fecha_seguimiento) }}" name="fecha_seguimiento"
                                        class="form-control" required>
                                    @error('fecha_seguimiento')
                                        <small style="color: red">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="observacion">Observación (Registrar información para clarificar el estado
                                        del registro)</label>
                                    <textarea name="observacion" id="observacion" class="form-control" rows="8">{{ old('observacion', $orden->observacion) }}</textarea>
                                </div>
                            </div>
                        </div>

                        {{-- =======================================================================
                             MODAL: Antecedentes para pago Departamento L.8.
                             ======================================================================= --}}
                        @php
                            $listaTiposDocumento = $tiposDocumento ?? \App\Models\PagoFactura::TIPOS_DOCUMENTO;
                            $valorFechaOficio = old('fecha_oficio', isset($pagoFactura->fecha_oficio) ? \Carbon\Carbon::parse($pagoFactura->fecha_oficio)->format('Y-m-d') : '');
                            $valorFechaFactura = old('fecha_factura', '');
                            $valorMontoFacturaRaw = old('monto_factura', '');
                            $valorMontoFactura = $valorMontoFacturaRaw !== '' ? number_format((int) str_replace('.', '', (string) $valorMontoFacturaRaw), 0, ',', '.') : '';
                        @endphp
                        <div class="modal fade" id="modalPagoL8" tabindex="-1" role="dialog" aria-labelledby="modalPagoL8Label" aria-hidden="true" data-backdrop="static" data-keyboard="false">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header bg-success text-white">
                                        <h5 class="modal-title font-weight-bold" id="modalPagoL8Label">
                                            Antecedentes para pago Departamento L.8.
                                        </h5>
                                        <button type="button" class="close text-white" id="btnCerrarXModalPagoL8" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>

                                    <div class="modal-body">
                                        <br><br>

                                        <div id="alertaValidacionPagoL8" class="alert alert-danger" style="display: none;">
                                            Por favor complete correctamente todos los campos del formulario de pago antes de guardar.
                                        </div>

                                        <div id="avisoModoEdicionFacturaL8" class="alert alert-warning py-2 px-3 mb-3" style="display: none;">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span>
                                                    <i class="bi bi-pencil-square"></i>
                                                    <strong id="textoModoEdicionFacturaL8">Modificando factura seleccionada...</strong>
                                                </span>
                                                <button type="button" class="btn btn-xs btn-secondary" id="btnCancelarEdicionFacturaL8">
                                                    Cancelar modificación
                                                </button>
                                            </div>
                                        </div>

                                        <div class="row">
                                            {{-- 1. N° de Oficio --}}
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="numero_oficio">N° de Oficio</label>
                                                    <input type="text"
                                                        name="numero_oficio"
                                                        id="numero_oficio"
                                                        value="{{ old('numero_oficio', $pagoFactura->numero_oficio ?? '') }}"
                                                        maxlength="100"
                                                        class="form-control @error('numero_oficio') is-invalid @enderror"
                                                        placeholder="Ingrese N° de Oficio">
                                                    @error('numero_oficio')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                            </div>

                                            {{-- 2. Fecha Oficio --}}
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="fecha_oficio">Fecha Oficio</label>
                                                    <input type="date"
                                                        name="fecha_oficio"
                                                        id="fecha_oficio"
                                                        value="{{ $valorFechaOficio }}"
                                                        class="form-control @error('fecha_oficio') is-invalid @enderror">
                                                    @error('fecha_oficio')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                            </div>

                                            {{-- 3. Tipo Documento (guarda solo el ID) --}}
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="tipo_documento_id">Tipo Documento</label>
                                                    <select name="tipo_documento_id"
                                                        id="tipo_documento_id"
                                                        class="form-control @error('tipo_documento_id') is-invalid @enderror">
                                                        <option value="">-- Seleccione Tipo Documento --</option>
                                                        @foreach ($listaTiposDocumento as $idTipo => $nombreTipo)
                                                            <option value="{{ $idTipo }}" {{ (string) old('tipo_documento_id', $pagoFactura->tipo_documento_id ?? '') === (string) $idTipo ? 'selected' : '' }}>
                                                                {{ $nombreTipo }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('tipo_documento_id')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            {{-- 4. Rut Proveedor (sin puntos pero con guion) --}}
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="rut_proveedor">Rut Proveedor</label>
                                                    <input type="text"
                                                        name="rut_proveedor"
                                                        id="rut_proveedor"
                                                        value="{{ old('rut_proveedor', $pagoFactura->rut_proveedor ?? '') }}"
                                                        maxlength="12"
                                                        class="form-control @error('rut_proveedor') is-invalid @enderror"
                                                        placeholder="Ej: 76123456-K">
                                                    <small class="form-text text-muted">Sin puntos y con guion (Ej: 12345678-9)</small>
                                                    @error('rut_proveedor')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                            </div>

                                            {{-- 5. Razón Social --}}
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="razon_social">Razón Social</label>
                                                    <input type="text"
                                                        name="razon_social"
                                                        id="razon_social"
                                                        value="{{ old('razon_social', $pagoFactura->razon_social ?? '') }}"
                                                        maxlength="255"
                                                        class="form-control @error('razon_social') is-invalid @enderror"
                                                        placeholder="Nombre o Razón Social del Proveedor"
                                                        oninput="this.value = this.value.toUpperCase()">
                                                    @error('razon_social')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            {{-- 6. N° Factura (sin puntos) --}}
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="numero_factura">N° Factura</label>
                                                    <input type="text"
                                                        name="numero_factura"
                                                        id="numero_factura"
                                                        value="{{ old('numero_factura', '') }}"
                                                        maxlength="50"
                                                        class="form-control @error('numero_factura') is-invalid @enderror"
                                                        placeholder="Sin puntos">
                                                    @error('numero_factura')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                            </div>

                                            {{-- 7. Fecha Factura --}}
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="fecha_factura">Fecha Factura</label>
                                                    <input type="date"
                                                        name="fecha_factura"
                                                        id="fecha_factura"
                                                        value="{{ $valorFechaFactura }}"
                                                        class="form-control @error('fecha_factura') is-invalid @enderror">
                                                    @error('fecha_factura')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                            </div>

                                            {{-- 8. Monto Factura --}}
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="monto_factura">Monto Factura</label>
                                                    <input type="text"
                                                        name="monto_factura"
                                                        id="monto_factura"
                                                        value="{{ $valorMontoFactura }}"
                                                        maxlength="15"
                                                        class="form-control @error('monto_factura') is-invalid @enderror"
                                                        placeholder="Ej: 1.500.000">
                                                    <small id="errorMontoFacturaExcedidoL8" class="text-danger font-weight-bold" style="display: none;"></small>
                                                    @error('monto_factura')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Listado unificado de facturas registradas en la base de datos para esta OC --}}
                                        <div id="contenedorFacturasRegistradasBD" class="mt-3" style="{{ $cantidadFacturasBD > 0 ? 'display: block;' : 'display: none;' }}">
                                            <h6 class="font-weight-bold text-success">
                                                <i class="bi bi-check2-all"></i> Facturas registradas en esta Orden de Compra (<span id="contadorFacturasBD">{{ $cantidadFacturasBD }}</span>):
                                            </h6>
                                            <div class="table-responsive" style="max-height: 180px; overflow-y: auto;">
                                                <table class="table table-sm table-bordered table-striped mb-0">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>ID Pago</th>
                                                            <th>N° Oficio</th>
                                                            <th>RUT Proveedor</th>
                                                            <th>N° Factura</th>
                                                            <th>Fecha Factura</th>
                                                            <th class="text-right">Monto Factura</th>
                                                            <th class="text-center" style="width: 110px;">Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tbodyFacturasRegistradasBD">
                                                        @if (isset($pagosFacturas))
                                                            @foreach ($pagosFacturas as $pf)
                                                                <tr data-id-pago="{{ $pf->id_pago }}"
                                                                    data-numero-oficio="{{ $pf->numero_oficio }}"
                                                                    data-fecha-oficio="{{ $pf->fecha_oficio ? \Carbon\Carbon::parse($pf->fecha_oficio)->format('Y-m-d') : '' }}"
                                                                    data-tipo-documento-id="{{ $pf->tipo_documento_id }}"
                                                                    data-rut-proveedor="{{ $pf->rut_proveedor }}"
                                                                    data-razon-social="{{ $pf->razon_social }}"
                                                                    data-numero-factura="{{ $pf->numero_factura }}"
                                                                    data-fecha-factura="{{ $pf->fecha_factura ? \Carbon\Carbon::parse($pf->fecha_factura)->format('Y-m-d') : '' }}"
                                                                    data-monto-factura="{{ (int) $pf->monto_factura }}">
                                                                    <td>{{ $pf->id_pago }}</td>
                                                                    <td class="celda-oficio">{{ $pf->numero_oficio }}</td>
                                                                    <td class="celda-rut">{{ $pf->rut_proveedor }}</td>
                                                                    <td class="celda-factura">{{ $pf->numero_factura }}</td>
                                                                    <td class="celda-fecha">{{ $pf->fecha_factura ? \Carbon\Carbon::parse($pf->fecha_factura)->format('d-m-Y') : '' }}</td>
                                                                    <td class="text-right celda-monto">$ {{ number_format((float) $pf->monto_factura, 0, ',', '.') }}</td>
                                                                    <td class="text-center">
                                                                        <button type="button" class="btn btn-xs btn-warning btn-editar-factura-bd mr-1" title="Modificar factura">
                                                                            <i class="bi bi-pencil-square"></i>
                                                                        </button>
                                                                        <button type="button" class="btn btn-xs btn-danger btn-eliminar-factura-bd" title="Eliminar factura">
                                                                            <i class="bi bi-trash"></i>
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        {{-- Fila resumen: Monto O/C, Suma Facturas por Pagar y Diferencia por Pagar --}}
                                        <div class="row mt-3 pt-3 border-top bg-light rounded p-2 text-center" style="font-size: 16px; font-weight: bold;">
                                            <div class="col-md-4">
                                                <span class="text-dark">Monto Orden de Compra:</span><br>
                                                <span id="resumenMontoOCL8" class="text-primary" style="font-size: 18px; font-weight: bold;">
                                                    $ {{ number_format($montoOrdenInicial, 0, ',', '.') }}
                                                </span>
                                            </div>
                                            <div class="col-md-4">
                                                <span class="text-dark">Facturas por Pagar:</span><br>
                                                <span id="resumenSumaFacturasL8" class="text-success" style="font-size: 18px; font-weight: bold;">
                                                    $ {{ number_format($sumaFacturasBD, 0, ',', '.') }}
                                                </span>
                                            </div>
                                            <div class="col-md-4">
                                                <span class="text-dark">Diferencia por Pagar:</span><br>
                                                <span id="resumenDiferenciaL8" class="{{ $diferenciaPorPagarInicial < 0 ? 'text-danger' : 'text-danger' }}" style="font-size: 18px; font-weight: bold;">
                                                    $ {{ number_format($diferenciaPorPagarInicial, 0, ',', '.') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-primary" id="btnGuardarPagoL8">
                                            <i class="bi bi-floppy2"></i> Guardar
                                        </button>
                                        <button type="button" class="btn btn-warning" id="btnLimpiarPagoL8">
                                            <i class="bi bi-eraser"></i> Limpiar
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="btnCancelarPagoL8">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="row">
                            <div class="col-md-9">
                                <a href="{{ url('ordenes/') }}" class="btn btn-success">Volver al listado</a>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-floppy2"></i> Actualizar
                                    registro</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script @cspNonce>
        document.addEventListener('DOMContentLoaded', function () {
            var formOrden = document.getElementById('formOrden');
            var montoInput = document.getElementById('monto');
            var estadoSelect = document.getElementById('estado_id');
            var contenedorBotonPagoL8 = document.getElementById('contenedorBotonPagoL8');
            var btnReabrirModalPagoL8 = document.getElementById('btnReabrirModalPagoL8');
            var badgePagoL8Confirmado = document.getElementById('badgePagoL8Confirmado');
            var alertaValidacionPagoL8 = document.getElementById('alertaValidacionPagoL8');
            var contenedorFacturasRegistradasBD = document.getElementById('contenedorFacturasRegistradasBD');
            var tbodyFacturasRegistradasBD = document.getElementById('tbodyFacturasRegistradasBD');
            var contadorFacturasBD = document.getElementById('contadorFacturasBD');
            var resumenMontoOCL8 = document.getElementById('resumenMontoOCL8');
            var resumenSumaFacturasL8 = document.getElementById('resumenSumaFacturasL8');
            var resumenDiferenciaL8 = document.getElementById('resumenDiferenciaL8');
            var avisoModoEdicionFacturaL8 = document.getElementById('avisoModoEdicionFacturaL8');
            var textoModoEdicionFacturaL8 = document.getElementById('textoModoEdicionFacturaL8');
            var btnCancelarEdicionFacturaL8 = document.getElementById('btnCancelarEdicionFacturaL8');
            var cantidadExistenteEnBD = {{ (int) $cantidadFacturasBD }};
            var sumaFacturasPagadas = {{ (float) $sumaFacturasBD }};
            var urlGuardarFacturaAjax = "{{ route('ordenes.guardarFacturaAjax', $orden->id) }}";
            var urlBaseFacturaAjax = "{{ url('ordenes/pago-factura') }}";
            var csrfToken = "{{ csrf_token() }}";
            var idPagoEnEdicion = null;
            var montoOriginalEnEdicion = 0;
            var errorMontoFacturaExcedidoL8 = document.getElementById('errorMontoFacturaExcedidoL8');

            // Campos del modal de Pago L.8.
            var camposModal = {
                numero_oficio: document.getElementById('numero_oficio'),
                fecha_oficio: document.getElementById('fecha_oficio'),
                tipo_documento_id: document.getElementById('tipo_documento_id'),
                rut_proveedor: document.getElementById('rut_proveedor'),
                razon_social: document.getElementById('razon_social'),
                numero_factura: document.getElementById('numero_factura'),
                fecha_factura: document.getElementById('fecha_factura'),
                monto_factura: document.getElementById('monto_factura')
            };

            var estadoAnteriorId = estadoSelect ? estadoSelect.value : '';

            // ── 1. Formateadores de números y RUT ──
            function formatNumberMiles(numStr) {
                var raw = String(numStr || '').replace(/[^0-9]/g, '');
                if (raw === '') return '';
                return parseInt(raw, 10).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            function formatMiles(input) {
                if (!input) return;
                input.value = formatNumberMiles(input.value);
            }

            function formatearRutSinPuntosConGuion(valor) {
                var limpio = (valor || '').toUpperCase().replace(/[^0-9K]/g, '');
                if (limpio.length <= 1) {
                    return limpio;
                }
                var cuerpo = limpio.slice(0, -1).replace(/[^0-9]/g, '');
                var dv = limpio.slice(-1);
                return cuerpo + '-' + dv;
            }

            function formatSignedMiles(num) {
                var entero = Math.round(Number(num) || 0);
                var signo = entero < 0 ? '-' : '';
                var absStr = Math.abs(entero).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                return signo + '$ ' + absStr;
            }

            function actualizarResumenMontosL8() {
                var montoOC = 0;
                if (montoInput && montoInput.value) {
                    montoOC = parseInt(montoInput.value.replace(/[^0-9]/g, ''), 10) || 0;
                }
                var diferencia = montoOC - sumaFacturasPagadas;

                if (resumenMontoOCL8) {
                    resumenMontoOCL8.textContent = formatSignedMiles(montoOC);
                }
                if (resumenSumaFacturasL8) {
                    resumenSumaFacturasL8.textContent = formatSignedMiles(sumaFacturasPagadas);
                }
                if (resumenDiferenciaL8) {
                    resumenDiferenciaL8.textContent = formatSignedMiles(diferencia);
                }
            }

            function obtenerMontoOCActual() {
                if (!montoInput || !montoInput.value) return 0;
                return parseInt(montoInput.value.replace(/[^0-9]/g, ''), 10) || 0;
            }

            function obtenerSumaOtrasFacturas() {
                if (idPagoEnEdicion) {
                    return Math.max(0, sumaFacturasPagadas - (Number(montoOriginalEnEdicion) || 0));
                }
                return sumaFacturasPagadas;
            }

            function validarMontoFacturaEnVivo() {
                if (!camposModal.monto_factura) return true;
                var valorStr = camposModal.monto_factura.value.replace(/\./g, '').trim();
                var btnG = document.getElementById('btnGuardarPagoL8');

                if (valorStr === '') {
                    if (errorMontoFacturaExcedidoL8) {
                        errorMontoFacturaExcedidoL8.style.display = 'none';
                        errorMontoFacturaExcedidoL8.textContent = '';
                    }
                    camposModal.monto_factura.classList.remove('is-invalid');
                    if (btnG) btnG.disabled = false;
                    return true;
                }

                var montoDigitado = parseInt(valorStr, 10) || 0;
                var montoOC = obtenerMontoOCActual();
                var sumaOtras = obtenerSumaOtrasFacturas();
                var saldoDisponible = Math.max(0, montoOC - sumaOtras);

                if (montoDigitado > montoOC || (sumaOtras + montoDigitado) > montoOC) {
                    var msg = sumaOtras > 0
                        ? 'El monto ingresado (' + formatSignedMiles(montoDigitado) + ') más las facturas ya ingresadas (' + formatSignedMiles(sumaOtras) + ') supera el Monto O/C (' + formatSignedMiles(montoOC) + '). Máximo permitido: ' + formatSignedMiles(saldoDisponible) + '.'
                        : 'El monto de la factura (' + formatSignedMiles(montoDigitado) + ') no puede ser mayor que el Monto de la Orden de Compra (' + formatSignedMiles(montoOC) + ').';

                    camposModal.monto_factura.classList.add('is-invalid');
                    if (errorMontoFacturaExcedidoL8) {
                        errorMontoFacturaExcedidoL8.textContent = msg;
                        errorMontoFacturaExcedidoL8.style.display = 'block';
                    }
                    if (alertaValidacionPagoL8) {
                        alertaValidacionPagoL8.textContent = msg;
                        alertaValidacionPagoL8.style.display = 'block';
                    }
                    if (btnG) btnG.disabled = true;
                    return false;
                }

                camposModal.monto_factura.classList.remove('is-invalid');
                if (errorMontoFacturaExcedidoL8) {
                    errorMontoFacturaExcedidoL8.style.display = 'none';
                    errorMontoFacturaExcedidoL8.textContent = '';
                }
                if (alertaValidacionPagoL8) {
                    alertaValidacionPagoL8.style.display = 'none';
                }
                if (btnG) btnG.disabled = false;
                return true;
            }

            if (montoInput) {
                montoInput.addEventListener('input', function () {
                    formatMiles(this);
                    actualizarResumenMontosL8();
                    validarMontoFacturaEnVivo();
                });
                if (montoInput.value) { formatMiles(montoInput); }
            }

            if (camposModal.monto_factura) {
                camposModal.monto_factura.addEventListener('input', function () {
                    formatMiles(this);
                    validarMontoFacturaEnVivo();
                });
                if (camposModal.monto_factura.value) { formatMiles(camposModal.monto_factura); }
            }

            if (camposModal.rut_proveedor) {
                camposModal.rut_proveedor.addEventListener('input', function () {
                    this.value = formatearRutSinPuntosConGuion(this.value);
                });
            }

            if (camposModal.numero_factura) {
                camposModal.numero_factura.addEventListener('input', function () {
                    this.value = this.value.replace(/\./g, '').trim();
                });
            }

            function actualizarBadgeTotal() {
                if (badgePagoL8Confirmado) {
                    if (cantidadExistenteEnBD > 0) {
                        badgePagoL8Confirmado.textContent = cantidadExistenteEnBD + ' registrada(s)';
                        badgePagoL8Confirmado.style.display = 'inline-block';
                    } else {
                        badgePagoL8Confirmado.style.display = 'none';
                    }
                }
                if (contadorFacturasBD) {
                    contadorFacturasBD.textContent = cantidadExistenteEnBD;
                }
                if (contenedorFacturasRegistradasBD) {
                    contenedorFacturasRegistradasBD.style.display = cantidadExistenteEnBD > 0 ? 'block' : 'none';
                }
            }

            // ── 2. Detectar si el estado seleccionado corresponde a "Enviada a Pago" / "Enviado a Pago" ──
            function esEstadoEnviadaAPago() {
                if (!estadoSelect || !estadoSelect.options[estadoSelect.selectedIndex]) {
                    return false;
                }
                var texto = estadoSelect.options[estadoSelect.selectedIndex].text.trim().toLowerCase();
                return texto === 'enviada a pago' || texto === 'enviado a pago';
            }

            function abrirModalPagoL8() {
                if (alertaValidacionPagoL8) {
                    alertaValidacionPagoL8.style.display = 'none';
                }
                actualizarResumenMontosL8();
                $('#modalPagoL8').modal('show');
            }

            function cerrarModalPagoL8() {
                $('#modalPagoL8').modal('hide');
            }

            function limpiarSoloCamposFactura() {
                ['numero_factura', 'fecha_factura', 'monto_factura'].forEach(function (key) {
                    if (camposModal[key]) {
                        camposModal[key].value = '';
                        camposModal[key].classList.remove('is-invalid');
                    }
                });
                if (errorMontoFacturaExcedidoL8) {
                    errorMontoFacturaExcedidoL8.style.display = 'none';
                    errorMontoFacturaExcedidoL8.textContent = '';
                }
                if (alertaValidacionPagoL8) {
                    alertaValidacionPagoL8.style.display = 'none';
                }
                if (btnGuardarPagoL8) {
                    btnGuardarPagoL8.disabled = false;
                }
            }

            function limpiarTodosCamposPagoL8() {
                Object.keys(camposModal).forEach(function (key) {
                    if (camposModal[key]) {
                        camposModal[key].value = '';
                        camposModal[key].classList.remove('is-invalid');
                    }
                });
                if (errorMontoFacturaExcedidoL8) {
                    errorMontoFacturaExcedidoL8.style.display = 'none';
                    errorMontoFacturaExcedidoL8.textContent = '';
                }
                if (alertaValidacionPagoL8) {
                    alertaValidacionPagoL8.style.display = 'none';
                }
                if (btnGuardarPagoL8) {
                    btnGuardarPagoL8.disabled = false;
                }
            }

            function camposFacturaEstanVacios() {
                var nf = camposModal.numero_factura ? camposModal.numero_factura.value.trim() : '';
                var ff = camposModal.fecha_factura ? camposModal.fecha_factura.value.trim() : '';
                var mf = camposModal.monto_factura ? camposModal.monto_factura.value.trim() : '';
                return nf === '' && ff === '' && mf === '';
            }

            function validarCamposPagoL8() {
                var esValido = true;

                Object.keys(camposModal).forEach(function (key) {
                    var campo = camposModal[key];
                    if (!campo) return;

                    var valor = (campo.value || '').trim();
                    var campoValido = valor !== '';

                    if (key === 'rut_proveedor' && campoValido) {
                        campo.value = formatearRutSinPuntosConGuion(valor);
                        campoValido = /^[0-9]{7,8}-[0-9K]$/.test(campo.value);
                    }

                    if (key === 'numero_factura' && campoValido) {
                        campo.value = valor.replace(/\./g, '');
                        campoValido = campo.value !== '';
                    }

                    if (key === 'monto_factura' && campoValido) {
                        var montoLimpio = parseInt(valor.replace(/\./g, ''), 10);
                        campoValido = !isNaN(montoLimpio) && montoLimpio > 0;
                    }

                    if (!campoValido) {
                        campo.classList.add('is-invalid');
                        esValido = false;
                    } else {
                        campo.classList.remove('is-invalid');
                    }
                });

                if (alertaValidacionPagoL8) {
                    if (!esValido) {
                        alertaValidacionPagoL8.textContent = 'Por favor complete correctamente todos los campos del formulario de pago antes de guardar.';
                        alertaValidacionPagoL8.style.display = 'block';
                    } else {
                        alertaValidacionPagoL8.style.display = 'none';
                    }
                }

                if (esValido && !validarMontoFacturaEnVivo()) {
                    esValido = false;
                }

                return esValido;
            }

            function actualizarVisibilidadControlesPagoL8() {
                if (esEstadoEnviadaAPago()) {
                    if (contenedorBotonPagoL8) {
                        contenedorBotonPagoL8.style.display = 'block';
                    }
                } else {
                    if (contenedorBotonPagoL8) {
                        contenedorBotonPagoL8.style.display = 'none';
                    }
                }
            }

            // ── 3. Eventos del Select Estado del Registro ──
            if (estadoSelect) {
                estadoSelect.addEventListener('focus', function () {
                    estadoAnteriorId = this.value;
                });

                estadoSelect.addEventListener('change', function () {
                    actualizarVisibilidadControlesPagoL8();
                    if (esEstadoEnviadaAPago()) {
                        abrirModalPagoL8();
                    } else {
                        estadoAnteriorId = this.value;
                    }
                });
            }

            if (btnReabrirModalPagoL8) {
                btnReabrirModalPagoL8.addEventListener('click', function () {
                    abrirModalPagoL8();
                });
            }

            // ── 4. Botones del Modal: Guardar / Actualizar (Inmediato en BD), Modificar fila, Eliminar fila ──
            var btnGuardarPagoL8 = document.getElementById('btnGuardarPagoL8');
            var btnLimpiarPagoL8 = document.getElementById('btnLimpiarPagoL8');
            var btnCancelarPagoL8 = document.getElementById('btnCancelarPagoL8');
            var btnCerrarXModalPagoL8 = document.getElementById('btnCerrarXModalPagoL8');

            function salirModoEdicionFactura() {
                idPagoEnEdicion = null;
                montoOriginalEnEdicion = 0;
                if (avisoModoEdicionFacturaL8) {
                    avisoModoEdicionFacturaL8.style.display = 'none';
                }
                if (btnGuardarPagoL8) {
                    btnGuardarPagoL8.disabled = false;
                    btnGuardarPagoL8.innerHTML = '<i class="bi bi-floppy2"></i> Guardar';
                }
                if (errorMontoFacturaExcedidoL8) {
                    errorMontoFacturaExcedidoL8.style.display = 'none';
                    errorMontoFacturaExcedidoL8.textContent = '';
                }
                if (tbodyFacturasRegistradasBD) {
                    var filas = tbodyFacturasRegistradasBD.querySelectorAll('tr');
                    filas.forEach(function (f) { f.classList.remove('table-warning'); });
                }
            }

            if (btnCancelarEdicionFacturaL8) {
                btnCancelarEdicionFacturaL8.addEventListener('click', function () {
                    salirModoEdicionFactura();
                    limpiarSoloCamposFactura();
                });
            }

            // Delegación de eventos en la tabla para Modificar y Eliminar sin salir del modal
            if (tbodyFacturasRegistradasBD) {
                tbodyFacturasRegistradasBD.addEventListener('click', function (e) {
                    var btnEditar = e.target.closest('.btn-editar-factura-bd');
                    var btnEliminar = e.target.closest('.btn-eliminar-factura-bd');

                    if (btnEditar) {
                        var tr = btnEditar.closest('tr');
                        if (!tr) return;

                        idPagoEnEdicion = tr.getAttribute('data-id-pago');
                        montoOriginalEnEdicion = parseInt(tr.getAttribute('data-monto-factura') || '0', 10) || 0;
                        var filas = tbodyFacturasRegistradasBD.querySelectorAll('tr');
                        filas.forEach(function (f) { f.classList.remove('table-warning'); });
                        tr.classList.add('table-warning');

                        if (camposModal.numero_oficio) camposModal.numero_oficio.value = tr.getAttribute('data-numero-oficio') || '';
                        if (camposModal.fecha_oficio) camposModal.fecha_oficio.value = tr.getAttribute('data-fecha-oficio') || '';
                        if (camposModal.tipo_documento_id) camposModal.tipo_documento_id.value = tr.getAttribute('data-tipo-documento-id') || '';
                        if (camposModal.rut_proveedor) camposModal.rut_proveedor.value = tr.getAttribute('data-rut-proveedor') || '';
                        if (camposModal.razon_social) camposModal.razon_social.value = tr.getAttribute('data-razon-social') || '';
                        if (camposModal.numero_factura) camposModal.numero_factura.value = tr.getAttribute('data-numero-factura') || '';
                        if (camposModal.fecha_factura) camposModal.fecha_factura.value = tr.getAttribute('data-fecha-factura') || '';
                        if (camposModal.monto_factura) {
                            camposModal.monto_factura.value = formatNumberMiles(tr.getAttribute('data-monto-factura') || '');
                        }

                        Object.keys(camposModal).forEach(function (k) {
                            if (camposModal[k]) camposModal[k].classList.remove('is-invalid');
                        });
                        if (alertaValidacionPagoL8) alertaValidacionPagoL8.style.display = 'none';

                        if (avisoModoEdicionFacturaL8 && textoModoEdicionFacturaL8) {
                            textoModoEdicionFacturaL8.textContent = 'Modificando Factura ID #' + idPagoEnEdicion + ' (N° Factura: ' + (tr.getAttribute('data-numero-factura') || '') + ')';
                            avisoModoEdicionFacturaL8.style.display = 'block';
                        }
                        if (btnGuardarPagoL8) {
                            btnGuardarPagoL8.innerHTML = '<i class="bi bi-check2-circle"></i> Actualizar';
                        }
                        if (camposModal.numero_factura) {
                            camposModal.numero_factura.focus();
                        }
                        return;
                    }

                    if (btnEliminar) {
                        var trDel = btnEliminar.closest('tr');
                        if (!trDel) return;
                        var idPagoDel = trDel.getAttribute('data-id-pago');
                        var numFactDel = trDel.getAttribute('data-numero-factura') || '';

                        var ejecutarEliminacion = function () {
                            fetch(urlBaseFacturaAjax + '/' + idPagoDel, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                }
                            })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                if (!data || !data.ok) {
                                    if (alertaValidacionPagoL8) {
                                        alertaValidacionPagoL8.textContent = (data && data.message) ? data.message : 'No se pudo eliminar la factura.';
                                        alertaValidacionPagoL8.style.display = 'block';
                                    }
                                    return;
                                }
                                if (String(idPagoEnEdicion) === String(idPagoDel)) {
                                    salirModoEdicionFactura();
                                    limpiarSoloCamposFactura();
                                }
                                trDel.remove();
                                cantidadExistenteEnBD = typeof data.total_facturas !== 'undefined' ? Number(data.total_facturas) : Math.max(0, cantidadExistenteEnBD - 1);
                                sumaFacturasPagadas = typeof data.suma_facturas !== 'undefined' ? Number(data.suma_facturas) : 0;
                                actualizarBadgeTotal();
                                actualizarResumenMontosL8();
                            });
                        };

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: '¿Eliminar la factura N° ' + numFactDel + '?',
                                text: 'Se eliminará inmediatamente de la base de datos.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#6c757d',
                                confirmButtonText: 'Sí, eliminar',
                                cancelButtonText: 'Cancelar'
                            }).then(function (result) {
                                if (result && (result.isConfirmed || result.value)) {
                                    ejecutarEliminacion();
                                }
                            });
                        } else if (window.confirm('¿Eliminar la factura N° ' + numFactDel + '?')) {
                            ejecutarEliminacion();
                        }
                    }
                });
            }

            if (btnGuardarPagoL8) {
                btnGuardarPagoL8.addEventListener('click', function () {
                    // Si no está editando una fila, ya guardó facturas antes y dejó los 3 campos de factura en blanco, cerrar ejecutivamente
                    if (!idPagoEnEdicion && cantidadExistenteEnBD > 0 && camposFacturaEstanVacios()) {
                        cerrarModalPagoL8();
                        return;
                    }

                    if (!validarCamposPagoL8()) {
                        return;
                    }

                    var payload = {
                        _token: csrfToken,
                        estado_id: estadoSelect ? estadoSelect.value : '',
                        monto_oc: obtenerMontoOCActual(),
                        numero_oficio: camposModal.numero_oficio.value.trim().toUpperCase(),
                        fecha_oficio: camposModal.fecha_oficio.value.trim(),
                        tipo_documento_id: camposModal.tipo_documento_id.value,
                        rut_proveedor: formatearRutSinPuntosConGuion(camposModal.rut_proveedor.value),
                        razon_social: camposModal.razon_social.value.trim().toUpperCase(),
                        numero_factura: camposModal.numero_factura.value.replace(/\./g, '').trim(),
                        fecha_factura: camposModal.fecha_factura.value.trim(),
                        monto_factura: camposModal.monto_factura.value.replace(/\./g, '').trim()
                    };

                    var esEdicion = !!idPagoEnEdicion;
                    var urlDestino = esEdicion ? (urlBaseFacturaAjax + '/' + idPagoEnEdicion) : urlGuardarFacturaAjax;
                    var metodoHttp = esEdicion ? 'PUT' : 'POST';

                    btnGuardarPagoL8.disabled = true;
                    btnGuardarPagoL8.innerHTML = '<i class="bi bi-hourglass-split"></i> ' + (esEdicion ? 'Actualizando...' : 'Guardando...');

                    fetch(urlDestino, {
                        method: metodoHttp,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(function (resp) {
                        return resp.json();
                    })
                    .then(function (data) {
                        btnGuardarPagoL8.disabled = false;
                        btnGuardarPagoL8.innerHTML = esEdicion ? '<i class="bi bi-check2-circle"></i> Actualizar' : '<i class="bi bi-floppy2"></i> Guardar';

                        if (!data || !data.ok) {
                            if (alertaValidacionPagoL8) {
                                alertaValidacionPagoL8.textContent = (data && data.message) ? data.message : 'Error al procesar la factura.';
                                alertaValidacionPagoL8.style.display = 'block';
                            }
                            return;
                        }

                        if (esEdicion && tbodyFacturasRegistradasBD && data.factura) {
                            var trEditada = tbodyFacturasRegistradasBD.querySelector('tr[data-id-pago="' + data.factura.id_pago + '"]');
                            if (trEditada) {
                                trEditada.setAttribute('data-numero-oficio', data.factura.numero_oficio);
                                trEditada.setAttribute('data-fecha-oficio', data.factura.fecha_oficio_ymd);
                                trEditada.setAttribute('data-tipo-documento-id', data.factura.tipo_documento_id);
                                trEditada.setAttribute('data-rut-proveedor', data.factura.rut_proveedor);
                                trEditada.setAttribute('data-razon-social', data.factura.razon_social);
                                trEditada.setAttribute('data-numero-factura', data.factura.numero_factura);
                                trEditada.setAttribute('data-fecha-factura', data.factura.fecha_factura_ymd);
                                trEditada.setAttribute('data-monto-factura', data.factura.monto_raw);

                                var cOficio = trEditada.querySelector('.celda-oficio');
                                var cRut = trEditada.querySelector('.celda-rut');
                                var cFact = trEditada.querySelector('.celda-factura');
                                var cFecha = trEditada.querySelector('.celda-fecha');
                                var cMonto = trEditada.querySelector('.celda-monto');
                                if (cOficio) cOficio.textContent = data.factura.numero_oficio;
                                if (cRut) cRut.textContent = data.factura.rut_proveedor;
                                if (cFact) cFact.textContent = data.factura.numero_factura;
                                if (cFecha) cFecha.textContent = data.factura.fecha_factura;
                                if (cMonto) cMonto.textContent = '$ ' + data.factura.monto_factura;
                            }

                            salirModoEdicionFactura();
                            limpiarSoloCamposFactura();
                            cantidadExistenteEnBD = data.total_facturas || cantidadExistenteEnBD;
                            sumaFacturasPagadas = typeof data.suma_facturas !== 'undefined' ? Number(data.suma_facturas) : sumaFacturasPagadas;
                            actualizarBadgeTotal();
                            actualizarResumenMontosL8();
                            return;
                        }

                        // Agregar inmediatamente la nueva fila a la tabla de facturas registradas en BD
                        if (tbodyFacturasRegistradasBD && data.factura) {
                            var tr = document.createElement('tr');
                            tr.setAttribute('data-id-pago', data.factura.id_pago);
                            tr.setAttribute('data-numero-oficio', data.factura.numero_oficio);
                            tr.setAttribute('data-fecha-oficio', data.factura.fecha_oficio_ymd || '');
                            tr.setAttribute('data-tipo-documento-id', data.factura.tipo_documento_id || '');
                            tr.setAttribute('data-rut-proveedor', data.factura.rut_proveedor);
                            tr.setAttribute('data-razon-social', data.factura.razon_social || '');
                            tr.setAttribute('data-numero-factura', data.factura.numero_factura);
                            tr.setAttribute('data-fecha-factura', data.factura.fecha_factura_ymd || '');
                            tr.setAttribute('data-monto-factura', data.factura.monto_raw || 0);

                            tr.innerHTML =
                                '<td>' + data.factura.id_pago + '</td>' +
                                '<td class="celda-oficio">' + data.factura.numero_oficio + '</td>' +
                                '<td class="celda-rut">' + data.factura.rut_proveedor + '</td>' +
                                '<td class="celda-factura">' + data.factura.numero_factura + '</td>' +
                                '<td class="celda-fecha">' + data.factura.fecha_factura + '</td>' +
                                '<td class="text-right celda-monto">$ ' + data.factura.monto_factura + '</td>' +
                                '<td class="text-center">' +
                                    '<button type="button" class="btn btn-xs btn-warning btn-editar-factura-bd mr-1" title="Modificar factura">' +
                                        '<i class="bi bi-pencil-square"></i>' +
                                    '</button>' +
                                    '<button type="button" class="btn btn-xs btn-danger btn-eliminar-factura-bd" title="Eliminar factura">' +
                                        '<i class="bi bi-trash"></i>' +
                                    '</button>' +
                                '</td>';
                            tbodyFacturasRegistradasBD.appendChild(tr);
                        }

                        cantidadExistenteEnBD = data.total_facturas || (cantidadExistenteEnBD + 1);
                        if (typeof data.suma_facturas !== 'undefined') {
                            sumaFacturasPagadas = Number(data.suma_facturas) || 0;
                        } else if (data.factura && data.factura.monto_raw) {
                            sumaFacturasPagadas += Number(data.factura.monto_raw) || 0;
                        }
                        actualizarBadgeTotal();
                        actualizarResumenMontosL8();

                        // Preguntar ejecutivamente si desea ingresar otra factura
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Factura guardada correctamente',
                                text: '¿Desea ingresar otra factura para este oficio?',
                                icon: 'success',
                                showCancelButton: true,
                                confirmButtonColor: '#28a745',
                                cancelButtonColor: '#007bff',
                                confirmButtonText: 'Sí, ingresar otra factura',
                                cancelButtonText: 'No, finalizar',
                                allowOutsideClick: false,
                                allowEscapeKey: false
                            }).then(function (result) {
                                var deseaMas = !!(result && (result.isConfirmed || result.value));
                                limpiarSoloCamposFactura();
                                if (deseaMas) {
                                    setTimeout(function () {
                                        if (camposModal.numero_factura) {
                                            camposModal.numero_factura.focus();
                                        }
                                    }, 150);
                                } else {
                                    cerrarModalPagoL8();
                                }
                            });
                        } else {
                            var confirmarMas = window.confirm('Factura guardada. ¿Desea ingresar otra factura para este oficio?');
                            limpiarSoloCamposFactura();
                            if (confirmarMas) {
                                if (camposModal.numero_factura) {
                                    camposModal.numero_factura.focus();
                                }
                            } else {
                                cerrarModalPagoL8();
                            }
                        }
                    })
                    .catch(function () {
                        btnGuardarPagoL8.disabled = false;
                        btnGuardarPagoL8.innerHTML = '<i class="bi bi-floppy2"></i> Guardar';
                        if (alertaValidacionPagoL8) {
                            alertaValidacionPagoL8.textContent = 'Error de conexión al guardar la factura.';
                            alertaValidacionPagoL8.style.display = 'block';
                        }
                    });
                });
            }

            if (btnLimpiarPagoL8) {
                btnLimpiarPagoL8.addEventListener('click', function () {
                    limpiarTodosCamposPagoL8();
                });
            }

            function manejarCancelarModalPagoL8() {
                cerrarModalPagoL8();
                if (cantidadExistenteEnBD === 0 && estadoSelect) {
                    estadoSelect.value = estadoAnteriorId || '';
                    actualizarVisibilidadControlesPagoL8();
                }
            }

            if (btnCancelarPagoL8) {
                btnCancelarPagoL8.addEventListener('click', manejarCancelarModalPagoL8);
            }

            if (btnCerrarXModalPagoL8) {
                btnCerrarXModalPagoL8.addEventListener('click', manejarCancelarModalPagoL8);
            }

            // ── 5. Envío del Formulario Principal ──
            if (formOrden) {
                formOrden.addEventListener('submit', function (e) {
                    var hayDatosFacturaEnInputs = !camposFacturaEstanVacios();

                    if (esEstadoEnviadaAPago() && cantidadExistenteEnBD === 0 && !hayDatosFacturaEnInputs) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        validarCamposPagoL8();
                        abrirModalPagoL8();
                        return false;
                    }

                    if (esEstadoEnviadaAPago() && hayDatosFacturaEnInputs && !validarCamposPagoL8()) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        abrirModalPagoL8();
                        return false;
                    }

                    if (montoInput) {
                        montoInput.value = montoInput.value.replace(/\./g, '');
                    }
                    if (camposModal.monto_factura) {
                        camposModal.monto_factura.value = camposModal.monto_factura.value.replace(/\./g, '');
                    }
                });
            }

            actualizarBadgeTotal();
            actualizarVisibilidadControlesPagoL8();
            @if ($errors->has('numero_oficio') || $errors->has('fecha_oficio') || $errors->has('tipo_documento_id') || $errors->has('rut_proveedor') || $errors->has('razon_social') || $errors->has('numero_factura') || $errors->has('fecha_factura') || $errors->has('monto_factura'))
                abrirModalPagoL8();
            @endif
        });

        $(function() {
            $("#example1").DataTable({
                "pageLength": 5,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Registros",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Registros",
                    "infoFiltered": "(Filtrado de _MAX_ total Registros)",
                    "infoPostFix": "",
                    "thousands": ",",
                    "lengthMenu": "Mostrar _MENU_ Registros",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscador:",
                    "zeroRecords": "Sin resultados encontrados",
                    "paginate": {
                        "first": "Primero",
                        "last": "Ultimo",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                buttons: [{
                        extend: 'collection',
                        text: 'Reportes',
                        orientation: 'landscape',
                        buttons: [{
                            extend: 'excel',
                            excelNumberFormat: '#.##0.000',
                            excelNumberFormatOptions: {
                                thousandsSeparator: '.',
                                decimalSeparator: '.'
                            }
                        }, {
                            text: 'Imprimir',
                            extend: 'print'
                        }]
                    }
                ],
            }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
        });
    </script>

    <script @cspNonce>
        $('.formulario-eliminar').submit(function(e) {
            e.preventDefault();

            Swal.fire({
                title: "¿Estas seguro ?",
                text: "Eliminarás el registro de la base de datos",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Sí, eliminarlo.",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.value) {
                    this.submit();
                }
            });
        });
    </script>
@endsection
