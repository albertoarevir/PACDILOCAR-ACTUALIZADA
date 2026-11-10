@extends('layouts.admin')
@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="card card-outline card-primary" style="margin-left: 50px;">
            <div class="card-body badge-btn" >
                <h2 style="font-size: 18px; color:rgb(8, 8, 8)">
                    <strong>ID Identificador del Proyecto N°:</strong>
                </h2>
                <h2 style="font-size: 18px; color:rgb(8, 8, 8); margin-bottom: 3;">
                   {{ $pac_id ? str_pad($pac_id, 4, '0', STR_PAD_LEFT) : 'N/A' }}
                </h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-outline card-primary" style="margin-left: 35px">
            <div class="card-body badge-btn">
                <h2 style="font-size: 18px; color:rgb(8, 8, 8)">
                   <strong> Modalidad de Compra:</strong>
                </h2>
                @if ($modalidad)
                <h2 style="font-size: 18px; color:rgb(8, 8, 8); margin-bottom: 3;">
                    {{ $modalidad }}
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
        <div class="card card-outline card-primary" style="margin-left: 35px">
            <div class="card-body badge-btn">
                <h2 style="font-size: 18px; color:rgb(5, 5, 5)">
                    <strong>N° identificador de Licitación:</strong>
                </h2>
                @if ($numero)
                    <h2 style="font-size: 18px; color:rgb(10, 10, 10); margin-bottom: 3;">
                        {{ $numero }}
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
<h2 style="font-size: 25px; margin-left: 35px;"><strong>Formulario de ingreso Ordenes de Compras/según Id de la licitación</strong></h2>
    <div class="row">
        <div class="col-md-9" style="margin-left: 30px">
            <div class="card card-outline card-primary">

                <div class="card-body">
                    <form action="{{ route('ordenes.store', ['pac_id' => $pac_id, 'id_mod' => $id_mod]) }}" method="post" id="formOrden">
                        @csrf
                        <input type="hidden" name="facturas_l8_json" id="facturas_l8_json" value="{{ old('facturas_l8_json', '[]') }}">

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="numero_oc">Id Orden de Compra</label>
                                    <input type="text" id="numero_oc" value="{{ old('numero') }}" name="numero" maxlength="15"
                                        class="form-control" required pattern="^[A-Z0-9-]+$" 
                                        oninput="this.value = this.value.toUpperCase()">
                                    @error('numero')
                                        <small style="color: red">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="monto">Monto O/C</label>
                                    <input type="text" id="monto"
                                        class="form-control @error('monto') is-invalid @enderror"
                                        name="monto"
                                        value="{{ old('monto') ? number_format((int) str_replace('.', '', old('monto')), 0, ',', '.') : '' }}"
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
                                    <label for="estado_id">Estado Orden de Compra</label>
                                    <select name="estado_id" id="estado_id"
                                        class="form-control @error('estado_id') is-invalid @enderror" required>
                                        <option value="">-- Ingrese el estado actual --</option>
                                        @foreach ($estadocompras as $estado)
                                            <option value="{{ $estado->id }}"
                                                {{ old('estado_id') == $estado->id ? 'selected' : '' }}>
                                                {{ $estado->detalle }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('estado_id')
                                        <small style="color: red">{{ $message }}</small>
                                    @enderror

                                    <div id="contenedorBotonPagoL8" class="mt-2" style="display: none;">
                                        <button type="button" class="btn btn-sm btn-info" id="btnReabrirModalPagoL8">
                                            <i class="bi bi-receipt"></i> Antecedentes Pago L.8.
                                        </button>
                                        <span id="badgePagoL8Confirmado" class="badge badge-success ml-1" style="display: none;">
                                            0 registradas
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_seguimiento">Fecha estimativa seguimiento</label>
                                    <input type="date" id="fecha_seguimiento" value="{{ old('fecha_seguimiento') }}" name="fecha_seguimiento"
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
                                    <textarea name="observacion" id="observacion" class="form-control" rows="5">{{ old('observacion') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <input type="hidden" id="id_licitacion" class="form-control" name="id_licitacion"
                                        value="{{ $id_mod }}" readonly>
                                </div>
                            </div>
                        </div>

                        {{-- =======================================================================
                             MODAL: Antecedentes para pago Departamento L.8.
                             ======================================================================= --}}
                        @php
                            $listaTiposDocumento = $tiposDocumento ?? \App\Models\PagoFactura::TIPOS_DOCUMENTO;
                        @endphp
                        <div class="modal fade" id="modalPagoL8" tabindex="-1" role="dialog" aria-labelledby="modalPagoL8Label" aria-hidden="true" data-backdrop="static" data-keyboard="false">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
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
                                                        value="{{ old('numero_oficio') }}"
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
                                                        value="{{ old('fecha_oficio') }}"
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
                                                            <option value="{{ $idTipo }}" {{ (string) old('tipo_documento_id') === (string) $idTipo ? 'selected' : '' }}>
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
                                                        value="{{ old('rut_proveedor') }}"
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
                                                        value="{{ old('razon_social') }}"
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
                                                        value="{{ old('numero_factura') }}"
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
                                                        value="{{ old('fecha_factura') }}"
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
                                                        value="{{ old('monto_factura') ? number_format((int) str_replace('.', '', old('monto_factura')), 0, ',', '.') : '' }}"
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

                                        {{-- Resumen de facturas registradas en este oficio --}}
                                        <div id="contenedorTablaFacturasL8" class="mt-3" style="display: none;">
                                            <h6 class="font-weight-bold text-primary">
                                                <i class="bi bi-check2-all"></i> Facturas registradas en esta Orden de Compra:
                                            </h6>
                                            <div class="table-responsive" style="max-height: 180px; overflow-y: auto;">
                                                <table class="table table-sm table-bordered table-striped mb-0">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>N° Oficio</th>
                                                            <th>RUT Proveedor</th>
                                                            <th>N° Factura</th>
                                                            <th>Fecha Factura</th>
                                                            <th class="text-right">Monto Factura</th>
                                                            <th class="text-center" style="width: 110px;">Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tbodyFacturasL8"></tbody>
                                                </table>
                                            </div>
                                        </div>

                                        {{-- Fila resumen: Monto O/C, Suma Facturas por Pagar y Diferencia por Pagar --}}
                                        <div class="row mt-3 pt-3 border-top bg-light rounded p-2 text-center" style="font-size: 16px; font-weight: bold;">
                                            <div class="col-md-4">
                                                <span class="text-dark">Monto Orden de Compra:</span><br>
                                                <span id="resumenMontoOCL8" class="text-primary" style="font-size: 18px; font-weight: bold;">
                                                    $ 0
                                                </span>
                                            </div>
                                            <div class="col-md-4">
                                                <span class="text-dark">Facturas por Pagar:</span><br>
                                                <span id="resumenSumaFacturasL8" class="text-success" style="font-size: 18px; font-weight: bold;">
                                                    $ 0
                                                </span>
                                            </div>
                                            <div class="col-md-4">
                                                <span class="text-dark">Diferencia por Pagar:</span><br>
                                                <span id="resumenDiferenciaL8" class="text-danger" style="font-size: 18px; font-weight: bold;">
                                                    $ 0
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
                                <a href="{{ url('ordenes/') }}" class="btn btn-success">Volver al listado de
                                    licitaciones</a>
                                <button type="submit" class="btn btn-primary" id="btnSubmitOrden"><i class="bi bi-floppy2"></i> Guardar
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
        var inputFacturasJson = document.getElementById('facturas_l8_json');
        var contenedorTablaFacturasL8 = document.getElementById('contenedorTablaFacturasL8');
        var tbodyFacturasL8 = document.getElementById('tbodyFacturasL8');
        var resumenMontoOCL8 = document.getElementById('resumenMontoOCL8');
        var resumenSumaFacturasL8 = document.getElementById('resumenSumaFacturasL8');
        var resumenDiferenciaL8 = document.getElementById('resumenDiferenciaL8');
        var avisoModoEdicionFacturaL8 = document.getElementById('avisoModoEdicionFacturaL8');
        var textoModoEdicionFacturaL8 = document.getElementById('textoModoEdicionFacturaL8');
        var btnCancelarEdicionFacturaL8 = document.getElementById('btnCancelarEdicionFacturaL8');
        var errorMontoFacturaExcedidoL8 = document.getElementById('errorMontoFacturaExcedidoL8');
        var indiceFacturaEnEdicion = null;

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

        var listaFacturasL8 = [];
        try {
            if (inputFacturasJson && inputFacturasJson.value) {
                var parsed = JSON.parse(inputFacturasJson.value);
                if (Array.isArray(parsed)) {
                    listaFacturasL8 = parsed;
                }
            }
        } catch (err) {
            listaFacturasL8 = [];
        }

        var estadoAnteriorId = '';

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
            var sumaFacturas = 0;
            listaFacturasL8.forEach(function (item) {
                sumaFacturas += parseInt(String(item.monto_factura || '0').replace(/[^0-9]/g, ''), 10) || 0;
            });
            var diferencia = montoOC - sumaFacturas;

            if (resumenMontoOCL8) {
                resumenMontoOCL8.textContent = formatSignedMiles(montoOC);
            }
            if (resumenSumaFacturasL8) {
                resumenSumaFacturasL8.textContent = formatSignedMiles(sumaFacturas);
            }
            if (resumenDiferenciaL8) {
                resumenDiferenciaL8.textContent = formatSignedMiles(diferencia);
            }
        }

        function obtenerMontoOCActual() {
            if (!montoInput || !montoInput.value) return 0;
            return parseInt(montoInput.value.replace(/[^0-9]/g, ''), 10) || 0;
        }

        function obtenerSumaOtrasFacturasL8() {
            var suma = 0;
            listaFacturasL8.forEach(function (item, idx) {
                if (indiceFacturaEnEdicion !== null && indiceFacturaEnEdicion === idx) {
                    return;
                }
                suma += parseInt(String(item.monto_factura || '0').replace(/[^0-9]/g, ''), 10) || 0;
            });
            return suma;
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
            var sumaOtras = obtenerSumaOtrasFacturasL8();
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

        // ── 2. Sincronizar lista de facturas en el JSON oculto y tabla visual ──
        function sincronizarFacturasL8() {
            if (inputFacturasJson) {
                inputFacturasJson.value = JSON.stringify(listaFacturasL8);
            }

            if (badgePagoL8Confirmado) {
                if (listaFacturasL8.length > 0) {
                    badgePagoL8Confirmado.textContent = listaFacturasL8.length + ' registrada(s)';
                    badgePagoL8Confirmado.style.display = 'inline-block';
                } else {
                    badgePagoL8Confirmado.style.display = 'none';
                }
            }

            if (contenedorTablaFacturasL8 && tbodyFacturasL8) {
                tbodyFacturasL8.innerHTML = '';
                if (listaFacturasL8.length > 0) {
                    contenedorTablaFacturasL8.style.display = 'block';
                    listaFacturasL8.forEach(function (item, idx) {
                        var tr = document.createElement('tr');
                        if (indiceFacturaEnEdicion === idx) {
                            tr.classList.add('table-warning');
                        }
                        tr.innerHTML =
                            '<td>' + (idx + 1) + '</td>' +
                            '<td>' + item.numero_oficio + '</td>' +
                            '<td>' + item.rut_proveedor + '</td>' +
                            '<td>' + item.numero_factura + '</td>' +
                            '<td>' + item.fecha_factura + '</td>' +
                            '<td class="text-right">$ ' + formatNumberMiles(item.monto_factura) + '</td>' +
                            '<td class="text-center">' +
                                '<button type="button" class="btn btn-xs btn-warning btn-editar-factura-l8 mr-1" data-index="' + idx + '" title="Modificar factura">' +
                                    '<i class="bi bi-pencil-square"></i>' +
                                '</button>' +
                                '<button type="button" class="btn btn-xs btn-danger btn-quitar-factura-l8" data-index="' + idx + '" title="Eliminar factura">' +
                                    '<i class="bi bi-trash"></i>' +
                                '</button>' +
                            '</td>';
                        tbodyFacturasL8.appendChild(tr);
                    });
                } else {
                    contenedorTablaFacturasL8.style.display = 'none';
                }
            }
            actualizarResumenMontosL8();
        }

        function salirModoEdicionFacturaL8() {
            indiceFacturaEnEdicion = null;
            if (avisoModoEdicionFacturaL8) {
                avisoModoEdicionFacturaL8.style.display = 'none';
            }
            var btnG = document.getElementById('btnGuardarPagoL8');
            if (btnG) {
                btnG.disabled = false;
                btnG.innerHTML = '<i class="bi bi-floppy2"></i> Guardar';
            }
            if (errorMontoFacturaExcedidoL8) {
                errorMontoFacturaExcedidoL8.style.display = 'none';
                errorMontoFacturaExcedidoL8.textContent = '';
            }
            sincronizarFacturasL8();
        }

        if (btnCancelarEdicionFacturaL8) {
            btnCancelarEdicionFacturaL8.addEventListener('click', function () {
                salirModoEdicionFacturaL8();
                limpiarSoloCamposFactura();
            });
        }

        if (tbodyFacturasL8) {
            tbodyFacturasL8.addEventListener('click', function (e) {
                var btnEditar = e.target.closest('.btn-editar-factura-l8');
                var btnQuitar = e.target.closest('.btn-quitar-factura-l8');

                if (btnEditar) {
                    var idxEdit = parseInt(btnEditar.getAttribute('data-index'), 10);
                    if (!isNaN(idxEdit) && idxEdit >= 0 && idxEdit < listaFacturasL8.length) {
                        var item = listaFacturasL8[idxEdit];
                        indiceFacturaEnEdicion = idxEdit;

                        if (camposModal.numero_oficio) camposModal.numero_oficio.value = item.numero_oficio || '';
                        if (camposModal.fecha_oficio) camposModal.fecha_oficio.value = item.fecha_oficio || '';
                        if (camposModal.tipo_documento_id) camposModal.tipo_documento_id.value = item.tipo_documento_id || '';
                        if (camposModal.rut_proveedor) camposModal.rut_proveedor.value = item.rut_proveedor || '';
                        if (camposModal.razon_social) camposModal.razon_social.value = item.razon_social || '';
                        if (camposModal.numero_factura) camposModal.numero_factura.value = item.numero_factura || '';
                        if (camposModal.fecha_factura) camposModal.fecha_factura.value = item.fecha_factura || '';
                        if (camposModal.monto_factura) camposModal.monto_factura.value = formatNumberMiles(item.monto_factura || '');

                        if (avisoModoEdicionFacturaL8 && textoModoEdicionFacturaL8) {
                            textoModoEdicionFacturaL8.textContent = 'Modificando Factura #' + (idxEdit + 1) + ' (N° Factura: ' + item.numero_factura + ')';
                            avisoModoEdicionFacturaL8.style.display = 'block';
                        }
                        var btnG = document.getElementById('btnGuardarPagoL8');
                        if (btnG) {
                            btnG.innerHTML = '<i class="bi bi-check2-circle"></i> Actualizar';
                        }
                        sincronizarFacturasL8();
                        if (camposModal.numero_factura) {
                            camposModal.numero_factura.focus();
                        }
                    }
                    return;
                }

                if (btnQuitar) {
                    var idx = parseInt(btnQuitar.getAttribute('data-index'), 10);
                    if (!isNaN(idx) && idx >= 0 && idx < listaFacturasL8.length) {
                        if (indiceFacturaEnEdicion === idx) {
                            salirModoEdicionFacturaL8();
                            limpiarSoloCamposFactura();
                        }
                        listaFacturasL8.splice(idx, 1);
                        sincronizarFacturasL8();
                    }
                }
            });
        }

        // ── 3. Detectar si el estado seleccionado corresponde a "Enviada a Pago" / "Enviado a Pago" ──
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
            var btnG = document.getElementById('btnGuardarPagoL8');
            if (btnG) {
                btnG.disabled = false;
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
            var btnG = document.getElementById('btnGuardarPagoL8');
            if (btnG) {
                btnG.disabled = false;
            }
        }

        function camposFacturaEstanVacios() {
            var nf = camposModal.numero_factura ? camposModal.numero_factura.value.trim() : '';
            var ff = camposModal.fecha_factura ? camposModal.fecha_factura.value.trim() : '';
            var mf = camposModal.monto_factura ? camposModal.monto_factura.value.trim() : '';
            return nf === '' && ff === '' && mf === '';
        }

        function finalizarModalYContinuar() {
            limpiarSoloCamposFactura();
            cerrarModalPagoL8();
            if (formOrden && formOrden.checkValidity()) {
                if (montoInput) {
                    montoInput.value = montoInput.value.replace(/\./g, '');
                }
                formOrden.submit();
            } else if (formOrden) {
                setTimeout(function () {
                    formOrden.reportValidity();
                }, 250);
            }
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

        // ── 4. Eventos del Select Estado Orden de Compra ──
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

        // ── 5. Botones del Modal: Guardar, Limpiar, Cancelar ──
        var btnGuardarPagoL8 = document.getElementById('btnGuardarPagoL8');
        var btnLimpiarPagoL8 = document.getElementById('btnLimpiarPagoL8');
        var btnCancelarPagoL8 = document.getElementById('btnCancelarPagoL8');
        var btnCerrarXModalPagoL8 = document.getElementById('btnCerrarXModalPagoL8');

        if (btnGuardarPagoL8) {
            btnGuardarPagoL8.addEventListener('click', function () {
                // Si ya registró facturas antes, dejó los 3 campos de factura en blanco y hace clic en Guardar, finalizar ejecutivamente
                if (indiceFacturaEnEdicion === null && listaFacturasL8.length > 0 && camposFacturaEstanVacios()) {
                    finalizarModalYContinuar();
                    return;
                }

                if (!validarCamposPagoL8()) {
                    return;
                }

                var nuevaFactura = {
                    numero_oficio: camposModal.numero_oficio.value.trim().toUpperCase(),
                    fecha_oficio: camposModal.fecha_oficio.value.trim(),
                    tipo_documento_id: camposModal.tipo_documento_id.value,
                    rut_proveedor: formatearRutSinPuntosConGuion(camposModal.rut_proveedor.value),
                    razon_social: camposModal.razon_social.value.trim().toUpperCase(),
                    numero_factura: camposModal.numero_factura.value.replace(/\./g, '').trim(),
                    fecha_factura: camposModal.fecha_factura.value.trim(),
                    monto_factura: camposModal.monto_factura.value.replace(/\./g, '').trim()
                };

                if (indiceFacturaEnEdicion !== null && indiceFacturaEnEdicion >= 0 && indiceFacturaEnEdicion < listaFacturasL8.length) {
                    listaFacturasL8[indiceFacturaEnEdicion] = nuevaFactura;
                    salirModoEdicionFacturaL8();
                    limpiarSoloCamposFactura();
                    return;
                }

                listaFacturasL8.push(nuevaFactura);
                sincronizarFacturasL8();

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Factura registrada',
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
                        if (deseaMas) {
                            limpiarSoloCamposFactura();
                            setTimeout(function () {
                                if (camposModal.numero_factura) {
                                    camposModal.numero_factura.focus();
                                }
                            }, 150);
                        } else {
                            finalizarModalYContinuar();
                        }
                    });
                } else {
                    var confirmarMas = window.confirm('Factura registrada. ¿Desea ingresar otra factura para este oficio?');
                    if (confirmarMas) {
                        limpiarSoloCamposFactura();
                        if (camposModal.numero_factura) {
                            camposModal.numero_factura.focus();
                        }
                    } else {
                        finalizarModalYContinuar();
                    }
                }
            });
        }

        if (btnLimpiarPagoL8) {
            btnLimpiarPagoL8.addEventListener('click', function () {
                limpiarTodosCamposPagoL8();
            });
        }

        function manejarCancelarModalPagoL8() {
            cerrarModalPagoL8();
            if (listaFacturasL8.length === 0 && estadoSelect) {
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

        // ── 6. Envío del Formulario Principal ──
        if (formOrden) {
            formOrden.addEventListener('submit', function (e) {
                if (esEstadoEnviadaAPago() && listaFacturasL8.length === 0) {
                    if (!validarCamposPagoL8()) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        abrirModalPagoL8();
                        return false;
                    }
                    listaFacturasL8.push({
                        numero_oficio: camposModal.numero_oficio.value.trim().toUpperCase(),
                        fecha_oficio: camposModal.fecha_oficio.value.trim(),
                        tipo_documento_id: camposModal.tipo_documento_id.value,
                        rut_proveedor: formatearRutSinPuntosConGuion(camposModal.rut_proveedor.value),
                        razon_social: camposModal.razon_social.value.trim().toUpperCase(),
                        numero_factura: camposModal.numero_factura.value.replace(/\./g, '').trim(),
                        fecha_factura: camposModal.fecha_factura.value.trim(),
                        monto_factura: camposModal.monto_factura.value.replace(/\./g, '').trim()
                    });
                    sincronizarFacturasL8();
                }

                if (montoInput) {
                    montoInput.value = montoInput.value.replace(/\./g, '');
                }
                if (camposModal.monto_factura) {
                    camposModal.monto_factura.value = camposModal.monto_factura.value.replace(/\./g, '');
                }
            });
        }

        sincronizarFacturasL8();
        actualizarVisibilidadControlesPagoL8();
        @if ($errors->has('numero_oficio') || $errors->has('fecha_oficio') || $errors->has('tipo_documento_id') || $errors->has('rut_proveedor') || $errors->has('razon_social') || $errors->has('numero_factura') || $errors->has('fecha_factura') || $errors->has('monto_factura'))
            abrirModalPagoL8();
        @endif
    });

    $(document).ready(function() {
        $('#formOrden').submit(function(e) {
            if (e.isDefaultPrevented()) {
                return;
            }
            $(this).find('button[type="submit"]').prop('disabled', true).text('Procesando...');
        });
    });
    </script>
@endsection
