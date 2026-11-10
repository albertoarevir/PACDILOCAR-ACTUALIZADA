@extends('layouts.admin')

@section('content')
    <script src="{{ asset('AutentificaTic/js/axios.min.js') }}"></script>

    <div class="row">
        <h2 style="font-size: 25px; margin-bottom: 3px; margin-left: 35px;">
            <strong>Registro de Usuarios en AutentificaTic</strong>
        </h2>
    </div>
    <br>
    <div class="row">
        <div class="col-md-6" style="margin-left: 30px">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <img src="{{ asset('AutentificaTic/images/carabineros.png') }}"
                             style="height: 30px; margin-right: 10px;">
                        Habilitar funcionario en AutentificaTic
                    </h3>
                    <span class="badge badge-info float-right" style="font-size: 13px;">
                        <i class="fas fa-globe"></i> pacdilocar.carabineros.cl
                    </span>
                </div>
                <div class="card-body">
                    <p class="text-muted" style="font-size: 14px;">
                        <strong>Paso 1:</strong> Ingrese el RUN del funcionario para vincularlo a la plataforma <strong>pacdilocar.carabineros.cl</strong> en la API institucional.
                        Una vez vinculado, podrá crearlo en el sistema PAC para asignarle su rol y departamento.
                    </p>

                    <form name="form1" method="POST" onsubmit="return false;">
                        @csrf
                        <div class="form-group">
                            <label for="rut_funcionario">
                                RUN del funcionario <small class="text-muted">(sin puntos ni guion, ej: 12345678K)</small>
                                <span id="loadingRut" style="display: none; font-size: 13px;" class="text-primary font-weight-normal ml-2">
                                    <i class="fas fa-spinner fa-spin"></i> Consultando datos y dotación en API...
                                </span>
                            </label>
                            <div class="input-group">
                                <input name="rut_funcionario" id="rut_funcionario"
                                       type="text" class="form-control"
                                       placeholder="Ej: 12345678K"
                                       maxlength="12"
                                       onchange="checkRut(this, 'rut_funcionario')"
                                       required>
                                <div class="input-group-append">
                                    <button type="button" id="btnBuscarRut" class="btn btn-outline-primary" title="Consultar datos y dotación en la API institucional">
                                        <i class="fas fa-search"></i> Buscar en API
                                    </button>
                                </div>
                            </div>
                            <div class="invalid-feedback">
                                <span id="rut_error"></span>
                            </div>
                        </div>

                        {{-- Vista previa inmediata de datos extraídos desde la API institucional --}}
                        <div id="previewFuncionario" class="card bg-light border-primary mb-3 shadow-sm" style="display: none;">
                            <div class="card-body p-2" style="font-size: 13px;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-primary"><i class="fas fa-id-card"></i> Datos obtenidos desde la API Institucional:</strong>
                                    <span class="badge badge-success" id="badgeApiDotacion">
                                        <i class="fas fa-check-circle"></i> Dotación API
                                    </span>
                                </div>
                                <div id="previewContenido" class="text-dark"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="dotacion_funcionario">Dotación del funcionario <small class="text-muted">(ingrese la repartición o dotación real del funcionario)</small></label>
                            <div class="input-group">
                                <input name="dotacion_funcionario" id="dotacion_funcionario"
                                       type="text" class="form-control"
                                       list="departamentosList"
                                       placeholder="Ingrese dotación / repartición (ej: Comisaría, Prefectura o Departamento)...">
                                <datalist id="departamentosList">
                                    @foreach(\App\Models\Departamento::orderBy('detalle')->get() as $d)
                                        <option value="{{ $d->detalle }}">
                                    @endforeach
                                </datalist>
                                <div class="input-group-append">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-building text-muted"></i>
                                    </span>
                                </div>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> La API institucional solo entrega nombres, grado y código en consultas de RUN. Ingrese aquí la dotación real del funcionario o se actualizará cuando inicie sesión con Clave Única.
                            </small>
                        </div>

                        <div id="resultadoBox" style="display: none;" class="mt-3"></div>

                        <hr>
                        <div class="row">
                            <div class="col-md-12 d-flex justify-content-between">
                                <a href="{{ url('/admin') }}" class="btn btn-secondary">Cancelar</a>
                                <button type="button" id="btnHabilitar" class="btn btn-primary">
                                    <i class="bi bi-person-check"></i> Habilitar en AutentificaTic
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script @cspNonce>
        function esDotacionInvalidaJs(val) {
            if (!val) return true;
            var s = val.toString().trim().toUpperCase()
                .replace(/Á/g, 'A').replace(/É/g, 'E').replace(/Í/g, 'I').replace(/Ó/g, 'O').replace(/Ú/g, 'U');
            var invalidas = ['DOTACION', 'DOTACION OFICIAL', 'PAC-DILOCAR', 'PAC DILOCAR', 'PACDILOCAR', 'SIN ASIGNAR', 'SIN ASIGNACION', 'PENDIENTE', 'S/D', 'S/N', 'SIN DOTACION', 'N/A', 'NULL', 'UNDEFINED', ''];
            return invalidas.indexOf(s) !== -1 || s.indexOf('PENDIENTE') !== -1;
        }

        var ultimoRutConsultado = '';

        function consultarDatosApi(rutVal, forzar) {
            if (!rutVal || rutVal.length < 8) return;
            if (!forzar && rutVal === ultimoRutConsultado) return;
            ultimoRutConsultado = rutVal;

            var loadingEl = document.getElementById('loadingRut');
            if (loadingEl) loadingEl.style.display = 'inline-block';

            axios.post('{{ url('/Autenti/sync') }}/' + encodeURIComponent(rutVal), {}, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                }
            }).then(function(resp) {
                var data = resp.data;
                var nombre = data.nombre_completo || data.apellidos_nombres;
                var dotacion = data.dotacion;
                var grado = data.grado;
                var codigo = data.codigo;

                if (!nombre && data.funcionario) {
                    var f = data.funcionario;
                    nombre = (f.Apellidos ? f.Apellidos + ', ' : '') + (f.Nombres || '');
                    if (!dotacion) dotacion = f.Dotacion;
                    if (!grado) grado = f.Grado;
                    if (!codigo) codigo = f.Codigo;
                }

                if (nombre || dotacion) {
                    var prevBox = document.getElementById('previewFuncionario');
                    var prevCont = document.getElementById('previewContenido');
                    var badgeDot = document.getElementById('badgeApiDotacion');

                    var dotValida = !esDotacionInvalidaJs(dotacion);
                    var dotTxt = dotValida
                        ? dotacion
                        : '<span class="text-muted font-italic">No provista por API externa (ingrese dotación abajo o se asignará al iniciar sesión)</span>';

                    if (badgeDot) {
                        badgeDot.className = dotValida ? 'badge badge-success' : 'badge badge-secondary';
                        badgeDot.innerHTML = dotValida
                            ? '<i class="fas fa-check-circle"></i> Dotación registrada'
                            : '<i class="fas fa-edit"></i> Ingreso manual / Inicio sesión';
                    }

                    if (prevCont) {
                        prevCont.innerHTML =
                            '<div><strong>Funcionario:</strong> ' + (nombre || 'Identificado en API') + '</div>' +
                            '<div>' +
                            (grado ? '<strong>Grado:</strong> ' + grado + ' | ' : '') +
                            (codigo && codigo !== 'S/C' ? '<strong>Código:</strong> ' + codigo + ' | ' : '') +
                            '<strong>Dotación:</strong> <span class="text-primary font-weight-bold">' + dotTxt + '</span>' +
                            '</div>' +
                            (data.already_in_pac ? '<div class="text-success mt-1"><i class="fas fa-check-double"></i> Registrado en sistema PAC' + (data.pac_habilitado ? ' (Habilitado)' : ' (No habilitado)') + '</div>' : '');
                    }
                    if (prevBox) prevBox.style.display = 'block';

                    var inputDot = document.getElementById('dotacion_funcionario');
                    if (inputDot) {
                        if (dotValida) {
                            inputDot.value = dotacion;
                        } else if (!inputDot.value) {
                            inputDot.value = '';
                        }
                    }
                }
            }).catch(function(err) {
                // Silencioso
            }).finally(function() {
                if (loadingEl) loadingEl.style.display = 'none';
            });
        }

        // Búsqueda automática al ingresar RUN
        var inputRut = document.getElementById('rut_funcionario');
        if (inputRut) {
            inputRut.addEventListener('blur', function() {
                var rutVal = this.value.trim().toUpperCase().replace(/[^0-9K]/g, '');
                consultarDatosApi(rutVal, false);
            });
            inputRut.addEventListener('change', function() {
                var rutVal = this.value.trim().toUpperCase().replace(/[^0-9K]/g, '');
                consultarDatosApi(rutVal, true);
            });
            inputRut.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    var rutVal = this.value.trim().toUpperCase().replace(/[^0-9K]/g, '');
                    consultarDatosApi(rutVal, true);
                }
            });
        }

        var btnBuscar = document.getElementById('btnBuscarRut');
        if (btnBuscar) {
            btnBuscar.addEventListener('click', function() {
                var rutVal = inputRut.value.trim().toUpperCase().replace(/[^0-9K]/g, '');
                if (!rutVal || rutVal.length < 8) {
                    alert('Debe ingresar un RUN valido (al menos 8 digitos).');
                    inputRut.focus();
                    return;
                }
                consultarDatosApi(rutVal, true);
            });
        }

        document.getElementById('btnHabilitar').addEventListener('click', function() {
            var rutVal = inputRut.value.trim().toUpperCase().replace(/[^0-9K]/g, '');
            if (!rutVal || rutVal.length < 8) {
                alert('Debe ingresar un RUN valido (al menos 8 digitos, sin puntos ni guion).');
                inputRut.focus();
                return;
            }

            var inputDot = document.getElementById('dotacion_funcionario');
            var dotacionVal = inputDot ? inputDot.value.trim() : '';

            var btn = this;
            var originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Validando en AutentificaTic...';

            var resBox = document.getElementById('resultadoBox');
            resBox.style.display = 'none';
            resBox.innerHTML = '';

            // La llamada a AutentificaTic la realiza el servidor (AutentiController@store) vinculando el DNS pacdilocar.carabineros.cl
            axios.post('{{ route('registerUser.store', [], false) }}', {
                rut_funcionario: rutVal,
                dotacion_funcionario: dotacionVal
            }, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                }
            }).then(function(response) {
                var data = response.data;
                var nombreCompleto = data.apellidos_nombres || data.nombre_completo || '';
                var detalleFuncionario = '';
                var u = data.funcionario || data.user;

                if (!nombreCompleto && u) {
                    var ape = (u.Apellidos || u.apellido_paterno || u.apellidos || '') + (u.apellido_materno ? ' ' + u.apellido_materno : '');
                    var nom = (u.Nombres || u.primer_nombre || u.nombres || '') + (u.segundo_nombre ? ' ' + u.segundo_nombre : '');
                    if (ape.trim() && nom.trim()) {
                        nombreCompleto = (ape.trim() + ', ' + nom.trim()).trim();
                    } else {
                        nombreCompleto = (ape.trim() || nom.trim()).trim();
                    }
                }

                if (nombreCompleto || u) {
                    var dotacionTxt = data.dotacion || (u && (u.dotacion || u.Dotacion)) || dotacionVal || '';
                    if (esDotacionInvalidaJs(dotacionTxt)) {
                        dotacionTxt = dotacionVal || (u && u.departamento ? u.departamento : '');
                    }
                    detalleFuncionario = '<div class="mt-2 text-dark" style="font-size: 14px;">' +
                        '<strong>Funcionario:</strong> ' + (nombreCompleto || 'Identificado') +
                        (u && (u.grado || u.Grado) ? ' | <strong>Grado:</strong> ' + (u.grado || u.Grado) : '') +
                        (u && (u.codigo || u.Codigo) && (u.codigo || u.Codigo) !== 'S/C' ? ' | <strong>Código:</strong> ' + (u.codigo || u.Codigo) : '') +
                        (dotacionTxt ? ' | <strong>Dotación:</strong> <span class="badge badge-primary" style="font-size: 13px;">' + dotacionTxt + '</span>' : '') +
                        '</div>';
                }

                var createUrl = data.create_url || ('{{ route('admin.usuarios.create') }}?Rut=' + encodeURIComponent(data.rut || rutVal) +
                    (nombreCompleto ? '&name=' + encodeURIComponent(nombreCompleto) : ''));

                // Ocultar previsualización
                var prevBox = document.getElementById('previewFuncionario');
                if (prevBox) prevBox.style.display = 'none';

                // Si el RUT ya existe y está ingresado en AutentificaTic
                if (data.already_exists) {
                    resBox.className = 'alert alert-warning mt-3';
                    var alertHtml = '<div style="font-size: 15px;"><strong><i class="fas fa-exclamation-triangle"></i> ' +
                        data.message + '</strong></div>';

                    if (data.detalle) {
                        alertHtml += '<div class="text-dark mt-1" style="font-size: 13px;">' + data.detalle + '</div>';
                    }

                    alertHtml += detalleFuncionario;

                    if (!data.already_in_pac) {
                        alertHtml += '<hr class="my-2">' +
                            '<div class="d-flex justify-content-between align-items-center mt-2">' +
                            '   <span class="text-muted" style="font-size: 13px;">El funcionario ya está en AutentificaTic. Si aún no lo crea en el sistema PAC:</span>' +
                            '   <a href="' + createUrl + '" class="btn btn-sm btn-primary">' +
                            '       <i class="fas fa-user-plus"></i> Paso 2: Crear usuario en PAC' +
                            '   </a>' +
                            '</div>';
                    } else {
                        if (data.pac_habilitado === false) {
                            alertHtml += '<hr class="my-2">' +
                                '<div class="text-danger mt-2" style="font-size: 13px;">' +
                                '   <i class="fas fa-user-slash"></i> Este usuario cuenta con cuenta en el sistema PAC pero se encuentra <strong>No Habilitado</strong>.' +
                                '</div>';
                        } else {
                            alertHtml += '<hr class="my-2">' +
                                '<div class="text-success mt-2" style="font-size: 13px;">' +
                                '   <i class="fas fa-check-double"></i> Este usuario ya cuenta con cuenta activa en el sistema PAC.' +
                                '</div>';
                        }
                    }

                    resBox.innerHTML = alertHtml;
                    resBox.style.display = 'block';
                    inputRut.value = '';
                    if (inputDot) inputDot.value = '';
                    return;
                }

                // Caso registro nuevo exitoso por primera vez
                resBox.className = 'alert alert-success mt-3';
                resBox.innerHTML = '<div style="font-size: 15px;"><strong><i class="fas fa-check-circle"></i> ' +
                    (data.message || 'Usuario habilitado correctamente en AutentificaTic.') + '</strong></div>' +
                    detalleFuncionario +
                    '<hr class="my-2">' +
                    '<div class="d-flex justify-content-between align-items-center mt-2">' +
                    '   <span class="text-muted" style="font-size: 13px;">Siguiente paso: cree la cuenta local para asignarle rol y permisos.</span>' +
                    '   <a href="' + createUrl + '" class="btn btn-sm btn-success text-white">' +
                    '       <i class="fas fa-user-plus"></i> Paso 2: Crear usuario en PAC' +
                    '   </a>' +
                    '</div>';
                resBox.style.display = 'block';
                inputRut.value = '';
                if (inputDot) inputDot.value = '';
            }).catch(function(error) {
                var msg = (error.response && error.response.data && error.response.data.message)
                    ? error.response.data.message
                    : 'Error inesperado al habilitar el usuario en AutentificaTic.';
                resBox.className = 'alert alert-danger mt-3';
                resBox.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + msg;
                resBox.style.display = 'block';

                if (error.response && (error.response.status === 401 || error.response.status === 419)) {
                    setTimeout(function() {
                        window.location.href = '{{ rtrim(config('app.url'), '/') }}/';
                    }, 2500);
                }
            }).finally(function() {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        });
    </script>

    <script src="{{ asset('AutentificaTic/js/main.js') }}"></script>

@endsection
