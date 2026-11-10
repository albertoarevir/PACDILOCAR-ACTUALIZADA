@extends('layouts.admin')
@section('content')
    <div class="row">
        <h2 style="font-size: 25px color:rgb(9, 9, 9); margin-bottom: 3; margin-left: 35px;"><strong> Formulario de Ingreso
                Plan Anual de Compras </strong></h2>
    </div>
    <br>
    <div class="row">
        <div class="col-md-11" style="margin-left: 20px">
            <div class="card card-outline card-primary">
                <div class="card-body">
                    <form id="formPac" action="{{ url('/pac') }}" method="post">
                        @csrf
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="year">Año:</label>
                                        <select class="form-control" name="year" id="year"
                                            @error('year') is-invalid @enderror" required>
                                            <option value="">Seleccione un año</option>
                                            @php
                                                $currentYear = date('Y');
                                                $years = range($currentYear, $currentYear - 1);
                                            @endphp
                                            @foreach ($years as $year)
                                                <option value="{{ $year }}"
                                                    {{ old('year') == $year ? 'selected' : '' }}>
                                                    {{ $year }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Departamento</label>
                                        <select name="departamento" id="departamento" class="form-control"
                                            @error('departamento') is-invalid @enderror required>
                                            <option value="">-- Ingrese Departamento --</option>
                                            @foreach ($departamentos as $departamento)
                                                <option value="{{ $departamento->id }}"
                                                    {{ old('departamento') == $departamento->id ? 'selected' : '' }}>
                                                    {{ $departamento->detalle }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="">Especie o servicio</label>
                                        <select name="especie" id="especie"
                                            class="form-control @error('especie') is-invalid @enderror" required>
                                            <option value="">-- Ingrese la especie correspondiente --</option>
                                        </select>
                                        @error('especie')
                                            <small style="color: red">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Subtitulo Presupuestario</label>
                                        <select name="clasificador" id="clasificador" class="form-control"
                                            @error('clasificador') is-invalid @enderror" required>
                                            <option value="">-- Seleccione un clasificador --</option>
                                            @foreach ($clasificadors as $clasificador)
                                                <option value="{{ $clasificador->codigo_id }}"
                                                    {{ old('clasificador') == $clasificador->codigo_id ? 'selected' : '' }}>
                                                    {{ $clasificador->codigo_id }} - {{ $clasificador->detalle }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('clasificador')
                                            <small style="color: red">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Item - Presupuestario</label>
                                        <select name="codigo_id" id="codigo" class="form-control"
                                            @error('codigo_id') is-invalid @enderror required>
                                            <option value="">Selecciona un clasificador primero</option>
                                        </select>
                                        @error('codigo_id')
                                            <small style="color: red">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="">Cantidad/Litros/Kilos</label>
                                        <input type="text" id="cantidad" class="form-control" name="cantidad"
                                            value="{{ old('cantidad') }}" maxlength="7" required>
                                        @error('cantidad')
                                            <small style="color: red">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="">Asignación Inicial</label>
                                        <input type="text" id="presupuesto"
                                            class="form-control @error('presupuesto') is-invalid @enderror"
                                            name="presupuesto"
                                            value="{{ old('presupuesto') ? number_format((int) str_replace('.', '', old('presupuesto')), 0, ',', '.') : '' }}"
                                            maxlength="15"
                                            placeholder="Ej: 1.500.000"
                                            required>
                                        @error('presupuesto')
                                            <small style="color: red">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="fuente_financiamiento">Fuente de Financiamiento</label>
                                        <select name="fuente_financiamiento" id="fuente_financiamiento" class="form-control"
                                            @error('fuente_financiamiento') is-invalid @enderror required>
                                            <option value="">-- Ingrese Fuente de Financiamiento --</option>
                                            @foreach ($fuentes as $fuente)
                                                <option value="{{ $fuente->id }}"
                                                    {{ old('fuente_financiamiento') == $fuente->id ? 'selected' : '' }}>
                                                    {{ $fuente->detalle }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('fuente_financiamiento')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="">Estado del proyecto</label>
                                        <select name="estado_id" id="estado_id"
                                            class="form-control @error('estado_id') is-invalid @enderror" required>
                                            <option value="">-- Ingrese el estado actual --</option>
                                            @foreach ($estados as $estado)
                                                <option value="{{ $estado->id }}"
                                                    {{ old('estado_id') == $estado->id ? 'selected' : '' }}>
                                                    {{ $estado->detalle }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('estado_id')
                                            <small style="color: red">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="observacion">Observacion (Registrar informacion para clarificar el
                                            estado del registro)</label>
                                        <textarea name="observacion" class="form-control" rows="8">{{ old('observacion') }}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="form-group">
                                        <label for="estado_modificacion">Ingreso inicial Pac y/o Modificacion</label>
                                        <select name="estado_modificacion" id="estado_modificacion" class="form-control"
                                            @error('estado_modificacion') is-invalid @enderror required>
                                            <option value="">-- Ingrese Estado de Modificacion --</option>
                                            @foreach ($estados_modificacion as $estado_modificacion)
                                                <option value="{{ $estado_modificacion->id }}"
                                                    {{ old('estado_modificacion') == $estado_modificacion->id ? 'selected' : '' }}>
                                                    {{ $estado_modificacion->detalle }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('estado_modificacion')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                    <br>
                                    <br>
                                    {{-- Bloques Informativos Presupuestarios ERP-SAP --}}
                                    <div class="row mt-3">
                                        <!-- 1. MONTO ACTUALIZADO SAP -->
                                        <div class="col-md-4">
                                            <div class="text-center p-2 border" style="background-color: #f8f9fa; border-radius: 4px; min-height: 90px; display: flex; flex-direction: column; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                                                <label class="text-success d-block mb-1 font-weight-bold" style="font-size: 11px; line-height: 1.2;">
                                                    MONTO ACTUALIZADO SAP (<span id="sap_depto_nombre">UNIDAD</span>)
                                                </label>
                                                <h5 class="text-bold mb-0 text-dark" id="box_monto_sap" style="font-size: 18px;">
                                                    $ 0
                                                </h5>
                                                <small class="text-muted mt-1" id="box_sap_sync" style="font-size: 9px; display: none;"></small>
                                            </div>
                                        </div>

                                        <!-- 2. TOTAL COMPROMETIDO SEGÚN ÍTEM Y DEPTO -->
                                        <div class="col-md-4">
                                            <div class="text-center p-2 border bg-dark" style="border-radius: 4px; min-height: 90px; display: flex; flex-direction: column; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.15);">
                                                <label class="text-white d-block mb-1 font-weight-bold" style="font-size: 11px; line-height: 1.2;">
                                                    TOTAL COMPROMETIDO (SEGÚN ÍTEM Y DEPTO)
                                                </label>
                                                <h5 class="text-bold text-warning mb-0" id="box_total_comprometido" style="font-size: 18px;">
                                                    $ 0
                                                </h5>
                                            </div>
                                        </div>

                                        <!-- 3. SALDO DISPONIBLE -->
                                        <div class="col-md-4">
                                            <div class="text-center p-2 border bg-info" id="box_saldo_container" style="border-radius: 4px; min-height: 90px; display: flex; flex-direction: column; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.15); transition: background-color 0.3s ease;">
                                                <label class="text-white d-block mb-1 font-weight-bold" style="font-size: 11px; line-height: 1.2;">
                                                    SALDO DISPONIBLE
                                                </label>
                                                <h5 class="text-bold text-white mb-0" id="box_saldo_disponible" style="font-size: 18px;">
                                                    $ 0
                                                </h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="box_alerta_sap" class="mt-2" style="display: none;"></div>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <a href="{{ url('pac/') }}" class="btn btn-success">Volver al listado</a>
                                <a href="{{ url('pac/create') }}" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-floppy2"></i> Guardar
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

        var oldDepartamento = @json(old('departamento', ''));
        var oldEspecie      = @json(old('especie', ''));
        var oldClasificador = @json(old('clasificador', ''));
        var oldCodigo       = @json(old('codigo_id', ''));

        // ── Asignacion Inicial: solo numeros con puntos de miles (formato chileno) ──
        var presupuestoInput = document.getElementById('presupuesto');

        function formatMiles(input) {
            var raw = input.value.replace(/[^0-9]/g, '');
            if (raw === '') { input.value = ''; return; }
            input.value = parseInt(raw, 10).toString()
                .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        if (presupuestoInput) {
            presupuestoInput.addEventListener('input', function () { formatMiles(this); });
        }

        // ── Cantidad: solo digitos ──
        var cantidadInput = document.getElementById('cantidad');
        if (cantidadInput) {
            cantidadInput.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }

        // ── Quitar puntos de miles antes de enviar para que el servidor reciba numero limpio ──
        var formPac = document.getElementById('formPac');
        if (formPac && presupuestoInput) {
            formPac.addEventListener('submit', function () {
                presupuestoInput.value = presupuestoInput.value.replace(/\./g, '');
            });
        }

        // ── Consulta en tiempo real de Presupuesto SAP según Item Presupuestario y Departamento ──
        function actualizarDatosSap() {
            var deptoEl  = document.getElementById('departamento');
            var codigoEl = document.getElementById('codigo');
            var yearEl   = document.getElementById('year');

            var deptoId   = deptoEl ? deptoEl.value : '';
            var codigoVal = codigoEl ? codigoEl.value : '';
            var yearVal   = yearEl ? yearEl.value : '';

            var boxMonto   = document.getElementById('box_monto_sap');
            var boxComp    = document.getElementById('box_total_comprometido');
            var boxSaldo   = document.getElementById('box_saldo_disponible');
            var deptoLabel = document.getElementById('sap_depto_nombre');
            var saldoCont  = document.getElementById('box_saldo_container');
            var alertaDiv  = document.getElementById('box_alerta_sap');
            var syncEl     = document.getElementById('box_sap_sync');

            if (!deptoId || !codigoVal) {
                if (boxMonto) boxMonto.textContent = '$ 0';
                if (boxComp) boxComp.textContent = '$ 0';
                if (boxSaldo) boxSaldo.textContent = '$ 0';
                if (deptoLabel) deptoLabel.textContent = 'UNIDAD';
                if (saldoCont) {
                    saldoCont.classList.remove('bg-danger');
                    saldoCont.classList.add('bg-info');
                }
                if (alertaDiv) alertaDiv.style.display = 'none';
                if (syncEl) syncEl.style.display = 'none';
                return;
            }

            if (boxMonto) boxMonto.innerHTML = '<span class="spinner-border spinner-border-sm text-success" role="status"></span>';
            if (boxComp) boxComp.innerHTML = '<span class="spinner-border spinner-border-sm text-warning" role="status"></span>';
            if (boxSaldo) boxSaldo.innerHTML = '<span class="spinner-border spinner-border-sm text-light" role="status"></span>';

            var url = '{{ route("get-presupuesto-sap") }}?departamento_id=' + encodeURIComponent(deptoId) +
                      '&codigo=' + encodeURIComponent(codigoVal) +
                      '&year=' + encodeURIComponent(yearVal);

            fetch(url)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (boxMonto) boxMonto.textContent = data.monto_sap_formateado;
                    if (boxComp) boxComp.textContent = data.total_comprometido_formateado;
                    if (boxSaldo) boxSaldo.textContent = data.saldo_disponible_formateado;
                    if (deptoLabel) deptoLabel.textContent = data.departamento_nombre || 'UNIDAD';

                    if (data.saldo_disponible < 0) {
                        if (saldoCont) {
                            saldoCont.classList.remove('bg-info');
                            saldoCont.classList.add('bg-danger');
                        }
                        if (alertaDiv) {
                            alertaDiv.innerHTML = '<div class="alert alert-danger py-1 px-2 mb-0 text-center font-weight-bold" style="font-size: 11px;"><i class="bi bi-exclamation-triangle-fill"></i> ADVERTENCIA: Presupuesto excedido para este ítem</div>';
                            alertaDiv.style.display = 'block';
                        }
                    } else {
                        if (saldoCont) {
                            saldoCont.classList.remove('bg-danger');
                            saldoCont.classList.add('bg-info');
                        }
                        if (alertaDiv) {
                            if (!data.existe) {
                                alertaDiv.innerHTML = '<div class="alert alert-warning py-1 px-2 mb-0 text-center" style="font-size: 11px;"><i class="bi bi-info-circle"></i> No registra presupuesto cargado en SAP para este ítem y departamento</div>';
                                alertaDiv.style.display = 'block';
                            } else {
                                alertaDiv.style.display = 'none';
                            }
                        }
                    }

                    if (data.updated_at && syncEl) {
                        syncEl.textContent = 'Sincronizado: ' + data.updated_at;
                        syncEl.style.display = 'block';
                    } else if (syncEl) {
                        syncEl.style.display = 'none';
                    }
                })
                .catch(function (err) {
                    console.error('Error al consultar presupuesto SAP:', err);
                    if (boxMonto) boxMonto.textContent = '$ 0';
                    if (boxComp) boxComp.textContent = '$ 0';
                    if (boxSaldo) boxSaldo.textContent = '$ 0';
                });
        }

        var codigoSelect = document.getElementById('codigo');
        if (codigoSelect) {
            codigoSelect.addEventListener('change', actualizarDatosSap);
        }

        var deptoSelect = document.getElementById('departamento');
        if (deptoSelect) {
            deptoSelect.addEventListener('change', function () {
                actualizarDatosSap();
            });
        }

        var yearSelect = document.getElementById('year');
        if (yearSelect) {
            yearSelect.addEventListener('change', actualizarDatosSap);
        }

        // ── Departamento -> Especie (AJAX) ──
        $('#departamento').on('change', function () {
            var departamentoId = $(this).val();
            $.ajax({
                type: 'GET',
                url: '{{ route("get-especies") }}',
                data: { departamento: departamentoId },
                success: function (data) {
                    $('#especie').empty();
                    $('#especie').append('<option value="">Seleccione una especie o servicio</option>');
                    $.each(data, function (index, value) {
                        var sel = (oldEspecie !== '' && String(value.id) === String(oldEspecie))
                            ? ' selected' : '';
                        $('#especie').append(
                            '<option value="' + value.id + '"' + sel + '>' + value.detalle + '</option>'
                        );
                    });
                }
            });
        });

        // ── Clasificador -> Item Presupuestario ──
        function cargarCodigos() {
            var clasificadorId = document.getElementById('clasificador').value;
            if (clasificadorId) {
                fetch('{{ route("get-codigos") }}?clasificador=' + clasificadorId)
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var sel = document.getElementById('codigo');
                        sel.innerHTML = '<option value="">Seleccione un codigo</option>';
                        data.forEach(function (codigo) {
                            var selected = (oldCodigo !== '' && String(codigo.codigopre) === String(oldCodigo))
                                ? ' selected' : '';
                            sel.innerHTML += '<option value="' + codigo.codigopre + '"' + selected + '>' +
                                codigo.codigopre + ' - ' + codigo.detalle + '</option>';
                        });
                        actualizarDatosSap();
                    })
                    .catch(function (e) { console.error('Error al cargar codigos:', e); });
            } else {
                document.getElementById('codigo').innerHTML =
                    '<option value="">Selecciona un clasificador primero</option>';
                actualizarDatosSap();
            }
        }

        document.getElementById('clasificador').addEventListener('change', cargarCodigos);

        // ── Restaurar selects dinamicos si la pagina vuelve con errores de validacion ──
        if (oldDepartamento !== '') {
            $('#departamento').trigger('change');
        }
        if (oldClasificador !== '') {
            cargarCodigos();
        }

    });
    </script>
@endsection
