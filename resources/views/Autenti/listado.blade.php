@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-md-11" style="margin-left: 30px">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h2 style="font-size: 26px; font-weight: bold; margin-bottom: 2px;">
                    Personal Habilitado en AutentificaTic
                </h2>
                <span class="text-muted" style="font-size: 14px;">
                    Funcionarios autorizados para acceder a través del DNS <strong>pacdilocar.carabineros.cl</strong>
                </span>
            </div>
            <div>
                <button type="button" id="btnSyncAll" class="btn btn-outline-primary shadow-sm mr-2" title="Extraer y actualizar dotaciones oficiales de todos los funcionarios desde la API institucional">
                    <i class="fas fa-sync-alt"></i> Sincronizar Dotaciones desde API
                </button>
                <button type="button" class="btn btn-outline-danger shadow-sm mr-2" data-toggle="modal" data-target="#modalEliminarRut" title="Eliminar / Desvincular funcionario directamente en AutentificaTic API">
                    <i class="fas fa-user-minus"></i> Desvincular por RUN
                </button>
                <a href="{{ route('registerUser.form') }}" class="btn btn-primary shadow-sm">
                    <i class="bi bi-person-plus-fill"></i> Habilitar nuevo funcionario
                </a>
            </div>
        </div>
        <br>

        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    <img src="{{ asset('AutentificaTic/images/carabineros.png') }}"
                         style="height: 28px; margin-right: 8px;">
                    Listado Oficial de Funcionarios AutentificaTic
                </h3>
                <span class="badge badge-info float-right" style="font-size: 13px;">
                    <i class="fas fa-globe"></i> pacdilocar.carabineros.cl
                </span>
            </div>

            <div class="card-body" style="font-size: 14px; color:rgb(12, 13, 14); margin-bottom: 0;">
                <table id="tablaAutentificatic" class="table table-striped table-hover table-bordered" style="width: 100%;">
                    <thead style="background-color: #c0c0c0">
                        <tr>
                            <th style="width: 4%; text-align: center;">N°</th>
                            <th style="width: 11%;">Rut</th>
                            <th style="width: 10%;">Código</th>
                            <th style="width: 25%;">Apellidos y Nombres</th>
                            <th style="width: 18%;">Dotación</th>
                            <th style="width: 12%;">Grado</th>
                            <th style="width: 10%; text-align: center;">Estado PAC</th>
                            <th style="width: 10%; text-align: center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $num = 1; @endphp
                        @foreach($funcionarios as $func)
                            @php
                                $rutNorm = \App\Models\User::normalizarRut($func->Rut);
                                $usuarioPac = isset($usuariosPac) ? ($usuariosPac->get($rutNorm) ?? null) : null;
                                $tieneCuentaPac = $usuarioPac !== null || in_array($rutNorm, $usuariosRuts ?? []);
                                $estaHabilitado = $usuarioPac ? filter_var($usuarioPac->habilitado, FILTER_VALIDATE_BOOLEAN) : false;
                                $nombreParaCrear = $func->nombre_completo !== 'Sin Nombre' ? $func->nombre_completo : ($func->Nombres ?: '');
                                $createUrl = route('admin.usuarios.create') . '?Rut=' . urlencode($rutNorm) . '&name=' . urlencode($nombreParaCrear);
                                // 1. Dotación real del funcionario traída desde la API institucional (guardada en BD)
                                // 2. Si no tiene en API, departamento asignado localmente en PAC
                                // 3. Fallback: Dotación institucional de la sesión (layouts/admin.blade.php línea 115)
                                $dotacionOficial = (!empty($func->Dotacion) && !\App\Models\Funcionario::esDotacionInvalida($func->Dotacion))
                                    ? $func->Dotacion
                                    : ($usuarioPac && $usuarioPac->departamento && !\App\Models\Funcionario::esDotacionInvalida($usuarioPac->departamento->detalle)
                                        ? $usuarioPac->departamento->detalle
                                        : 'Pendiente (se actualiza al iniciar sesión)');
                            @endphp
                            <tr id="fila-{{ $rutNorm }}">
                                <td style="text-align: center;">{{ $num++ }}</td>
                                <td>
                                    <strong>{{ $func->Rut }}</strong>
                                </td>
                                <td>
                                    <span class="badge badge-secondary col-codigo" style="font-size: 12px;">
                                        {{ $func->Codigo ?: 'S/C' }}
                                    </span>
                                </td>
                                <td class="col-nombres">
                                    {{ $func->nombre_completo }}
                                </td>
                                <td class="col-dotacion">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="txt-dotacion">{{ $dotacionOficial }}</span>
                                        <button type="button"
                                                class="btn btn-xs btn-outline-secondary ml-1 btn-edit-dotacion"
                                                data-rut="{{ $rutNorm }}"
                                                data-nombre="{{ $nombreParaCrear }}"
                                                data-dotacion="{{ $dotacionOficial !== 'Pendiente (se actualiza al iniciar sesión)' ? $dotacionOficial : '' }}"
                                                title="Modificar dotación de {{ $nombreParaCrear }}">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="col-grado">
                                    {{ $func->Grado ?: 'S/G' }}
                                </td>
                                <td style="text-align: center;">
                                    @if($tieneCuentaPac)
                                        @if($estaHabilitado)
                                            <span class="badge badge-success" style="font-size: 11px;">
                                                <i class="fas fa-check-circle"></i> Activo en PAC
                                            </span>
                                        @else
                                            <span class="badge badge-danger" style="font-size: 11px;" title="Usuario registrado en PAC pero no habilitado">
                                                <i class="fas fa-user-slash"></i> No Habilitado en PAC
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge badge-warning text-dark" style="font-size: 11px;" title="Falta crear la cuenta local en PAC">
                                            <i class="fas fa-clock"></i> Pendiente en PAC
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <div class="btn-group" role="group">
                                        {{-- Botón para editar dotación --}}
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-sm btn-edit-dotacion"
                                                data-rut="{{ $rutNorm }}"
                                                data-nombre="{{ $nombreParaCrear }}"
                                                data-dotacion="{{ $dotacionOficial !== 'Pendiente (se actualiza al iniciar sesión)' ? $dotacionOficial : '' }}"
                                                title="Modificar dotación de {{ $nombreParaCrear }}">
                                            <i class="fas fa-building"></i>
                                        </button>

                                        {{-- Botón para sincronizar datos frescos desde AutentificaTic --}}
                                        <button type="button"
                                                class="btn btn-outline-info btn-sm btn-sync"
                                                data-rut="{{ $rutNorm }}"
                                                title="Sincronizar datos desde AutentificaTic">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>

                                        {{-- Si no tiene cuenta en PAC, ofrecer crearla directamente --}}
                                        @if(!$tieneCuentaPac)
                                            <a href="{{ $createUrl }}"
                                               class="btn btn-outline-success btn-sm"
                                               title="Crear cuenta local en PAC para este funcionario">
                                                <i class="fas fa-user-plus"></i>
                                            </a>
                                        @endif

                                        {{-- Botón para eliminar/desvincular en AutentificaTic API --}}
                                        <button type="button"
                                                class="btn btn-outline-danger btn-sm btn-delete-autenti"
                                                data-rut="{{ $rutNorm }}"
                                                data-nombre="{{ $nombreParaCrear }}"
                                                title="Eliminar y desvincular de AutentificaTic API">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <script @cspNonce>
                    $(function() {
                        $("#tablaAutentificatic").DataTable({
                            "pageLength": 10,
                            "language": {
                                "emptyTable": "No hay funcionarios registrados en AutentificaTic",
                                "info": "Mostrando _START_ a _END_ de _TOTAL_ Funcionarios",
                                "infoEmpty": "Mostrando 0 a 0 de 0 Funcionarios",
                                "infoFiltered": "(Filtrado de _MAX_ total Funcionarios)",
                                "thousands": ",",
                                "lengthMenu": "Mostrar _MENU_ Funcionarios",
                                "loadingRecords": "Cargando...",
                                "processing": "Procesando...",
                                "search": "Buscador:",
                                "zeroRecords": "Sin resultados encontrados",
                                "paginate": {
                                    "first": "Primero",
                                    "last": "Último",
                                    "next": "Siguiente",
                                    "previous": "Anterior"
                                }
                            },
                            "responsive": true,
                            "lengthChange": true,
                            "autoWidth": false,
                            buttons: [{
                                extend: 'collection',
                                text: 'Exportar',
                                orientation: 'landscape',
                                buttons: [{
                                    extend: 'excel',
                                    title: 'Personal_Autentificatic_PAC'
                                }, {
                                    extend: 'print',
                                    text: 'Imprimir'
                                }]
                            }]
                        }).buttons().container().appendTo('#tablaAutentificatic_wrapper .col-md-6:eq(0)');

                        function esDotacionInvalida(val) {
                            if (!val) return true;
                            var s = val.toString().trim().toUpperCase()
                                .replace(/Á/g, 'A').replace(/É/g, 'E').replace(/Í/g, 'I').replace(/Ó/g, 'O').replace(/Ú/g, 'U');
                            var invalidas = ['DOTACION', 'DOTACION OFICIAL', 'PAC-DILOCAR', 'PAC DILOCAR', 'PACDILOCAR', 'SIN ASIGNAR', 'SIN ASIGNACION', 'PENDIENTE', 'S/D', 'S/N', 'SIN DOTACION', 'N/A', 'NULL', 'UNDEFINED', ''];
                            return invalidas.indexOf(s) !== -1 || s.indexOf('PENDIENTE') !== -1;
                        }

                        // Sincronización masiva de Dotaciones desde la API institucional
                        $('#btnSyncAll').on('click', function() {
                            var $btnAll = $(this);
                            var originalHtml = $btnAll.html();
                            $btnAll.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sincronizando dotaciones...');

                            var syncBtns = $('.btn-sync').toArray();
                            var total = syncBtns.length;
                            var index = 0;
                            var actualizados = 0;

                            function syncNext() {
                                if (index >= total) {
                                    $btnAll.prop('disabled', false).html(originalHtml);
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Dotaciones actualizadas',
                                        text: 'Se han sincronizado las dotaciones desde la API institucional (' + actualizados + ' funcionarios procesados).',
                                        timer: 2500,
                                        showConfirmButton: false
                                    });
                                    return;
                                }

                                var $b = $(syncBtns[index]);
                                var rut = $b.data('rut');
                                var icon = $b.find('i');
                                icon.addClass('fa-spin');

                                $.ajax({
                                    url: '{{ url('/Autenti/sync') }}/' + rut,
                                    method: 'POST',
                                    data: { _token: '{{ csrf_token() }}' },
                                    success: function(resp) {
                                        if (resp.funcionario) {
                                            var f = resp.funcionario;
                                            var $fila = $('#fila-' + rut);
                                            $fila.find('.col-codigo').text(f.Codigo || 'S/C');
                                            var nombreApe = (f.Apellidos ? f.Apellidos + ', ' : '') + (f.Nombres || '');
                                            $fila.find('.col-nombres').text(nombreApe || 'Sin Nombre');
                                            var dVal = resp.dotacion || f.Dotacion;
                                            if (esDotacionInvalida(dVal)) {
                                                dVal = 'Pendiente (se actualiza al iniciar sesión)';
                                            }
                                            if ($fila.find('.txt-dotacion').length) {
                                                $fila.find('.txt-dotacion').text(dVal);
                                            } else {
                                                $fila.find('.col-dotacion').text(dVal);
                                            }
                                            $fila.find('.btn-edit-dotacion').data('dotacion', dVal !== 'Pendiente (se actualiza al iniciar sesión)' ? dVal : '');
                                            $fila.find('.col-grado').text(f.Grado || 'S/G');
                                            actualizados++;
                                        }
                                    },
                                    complete: function() {
                                        icon.removeClass('fa-spin');
                                        index++;
                                        syncNext();
                                    }
                                });
                            }

                            syncNext();
                        });

                        // Sincronización individual en tiempo real vía AJAX
                        $(document).on('click', '.btn-sync', function() {
                            var btn = $(this);
                            var rut = btn.data('rut');
                            var icon = btn.find('i');
                            
                            icon.addClass('fa-spin');
                            btn.prop('disabled', true);

                            $.ajax({
                                url: '{{ url('/Autenti/sync') }}/' + rut,
                                method: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}'
                                },
                                success: function(resp) {
                                    if (resp.funcionario) {
                                        var f = resp.funcionario;
                                        var $fila = $('#fila-' + rut);
                                        $fila.find('.col-codigo').text(f.Codigo || 'S/C');
                                        var nombreApe = (f.Apellidos ? f.Apellidos + ', ' : '') + (f.Nombres || '');
                                        $fila.find('.col-nombres').text(nombreApe || 'Sin Nombre');
                                        var dVal = resp.dotacion || f.Dotacion;
                                        if (esDotacionInvalida(dVal)) {
                                            dVal = 'Pendiente (se actualiza al iniciar sesión)';
                                        }
                                        if ($fila.find('.txt-dotacion').length) {
                                            $fila.find('.txt-dotacion').text(dVal);
                                        } else {
                                            $fila.find('.col-dotacion').text(dVal);
                                        }
                                        $fila.find('.btn-edit-dotacion').data('dotacion', dVal !== 'Pendiente (se actualiza al iniciar sesión)' ? dVal : '');
                                        $fila.find('.col-grado').text(f.Grado || 'S/G');
                                    }
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Datos actualizados',
                                        text: resp.message || 'Funcionario y dotación sincronizados con AutentificaTic.',
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                },
                                error: function(xhr) {
                                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                        ? xhr.responseJSON.message
                                        : 'No se pudo sincronizar el funcionario con la API institucional.';
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error de sincronización',
                                        text: msg
                                    });
                                },
                                complete: function() {
                                    icon.removeClass('fa-spin');
                                    btn.prop('disabled', false);
                                }
                            });
                        });

                        // Eliminar/desvincular funcionario de la tabla en Autentificatic API
                        $(document).on('click', '.btn-delete-autenti', function() {
                            var btn = $(this);
                            var rut = btn.data('rut');
                            var nombre = btn.data('nombre') || ('RUN ' + rut);

                            Swal.fire({
                                title: '¿Eliminar usuario en Autentificatic?',
                                html: 'Se eliminará el acceso de <strong>' + nombre + '</strong> (RUN: <code>' + rut + '</code>) en <strong>Autentificatic API</strong> para la plataforma <strong>pacdilocar.carabineros.cl</strong>.<br><br><small class="text-danger"><i class="fas fa-exclamation-circle"></i> Esta acción no se puede deshacer.</small>',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#6c757d',
                                confirmButtonText: '<i class="fas fa-trash-alt"></i> Sí, eliminar en Autentificatic',
                                cancelButtonText: 'Cancelar'
                            }).then(function(result) {
                                if (result.isConfirmed) {
                                    Swal.fire({
                                        title: 'Eliminando en Autentificatic API...',
                                        text: 'Comunicándose con el servidor institucional...',
                                        allowOutsideClick: false,
                                        didOpen: function() {
                                            Swal.showLoading();
                                        }
                                    });

                                    $.ajax({
                                        url: '{{ url('/Autenti/usuario') }}/' + rut,
                                        type: 'DELETE',
                                        data: {
                                            _token: '{{ csrf_token() }}'
                                        },
                                        success: function(resp) {
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Usuario Eliminado',
                                                text: resp.message || 'El funcionario ha sido desvinculado exitosamente de Autentificatic API.',
                                                timer: 2500,
                                                showConfirmButton: false
                                            });

                                            var tabla = $('#tablaAutentificatic').DataTable();
                                            var $fila = $('#fila-' + rut);
                                            if ($fila.length) {
                                                tabla.row($fila).remove().draw(false);
                                            }
                                        },
                                        error: function(xhr) {
                                            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                                ? xhr.responseJSON.message
                                                : 'Ocurrió un error al intentar eliminar el funcionario en AutentificaTic.';
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error al eliminar',
                                                text: msg
                                            });
                                        }
                                    });
                                }
                            });
                        });

                        // Formulario manual de eliminación por RUN en modal
                        $('#formEliminarRutManual').on('submit', function(e) {
                            e.preventDefault();
                            var rut = $('#rutEliminarInput').val().trim();
                            if (!rut) return;

                            $('#modalEliminarRut').modal('hide');

                            Swal.fire({
                                title: '¿Confirmar eliminación en Autentificatic?',
                                html: 'Se solicitará la eliminación del RUN <strong>' + rut + '</strong> en Autentificatic API para pacdilocar.carabineros.cl.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#6c757d',
                                confirmButtonText: '<i class="fas fa-trash-alt"></i> Sí, eliminar',
                                cancelButtonText: 'Cancelar'
                            }).then(function(result) {
                                if (result.isConfirmed) {
                                    Swal.fire({
                                        title: 'Procesando eliminación...',
                                        text: 'Llamando a Autentificatic API...',
                                        allowOutsideClick: false,
                                        didOpen: function() {
                                            Swal.showLoading();
                                        }
                                    });

                                    $.ajax({
                                        url: '{{ route('autenti.eliminarUsuario.post') }}',
                                        type: 'POST',
                                        data: {
                                            _token: '{{ csrf_token() }}',
                                            rut: rut
                                        },
                                        success: function(resp) {
                                            $('#rutEliminarInput').val('');
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Eliminación Exitosa',
                                                text: resp.message || 'El usuario fue eliminado de Autentificatic API.',
                                                timer: 2500,
                                                showConfirmButton: false
                                            });

                                            if (resp.rut) {
                                                var tabla = $('#tablaAutentificatic').DataTable();
                                                var $fila = $('#fila-' + resp.rut);
                                                if ($fila.length) {
                                                    tabla.row($fila).remove().draw(false);
                                                }
                                            }
                                        },
                                        error: function(xhr) {
                                            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                                ? xhr.responseJSON.message
                                                : 'No se pudo eliminar el RUN en Autentificatic API.';
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error de Autentificatic',
                                                text: msg
                                            });
                                        }
                                    });
                                }
                            });
                        });
                        // Abrir modal de edición de dotación
                        $(document).on('click', '.btn-edit-dotacion', function() {
                            var rut = $(this).data('rut');
                            var nombre = $(this).data('nombre');
                            var dotacion = $(this).data('dotacion');

                            $('#editDotacionRut').val(rut);
                            $('#editDotacionRutDisplay').text('RUN: ' + rut);
                            $('#editDotacionNombre').text(nombre);
                            $('#inputNuevaDotacion').val(dotacion);
                            $('#modalEditarDotacion').modal('show');
                        });

                        // Guardar dotación vía AJAX
                        $('#btnGuardarDotacion').on('click', function() {
                            var rut = $('#editDotacionRut').val();
                            var dotacion = $('#inputNuevaDotacion').val().trim();
                            if (!dotacion) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Campo Requerido',
                                    text: 'Por favor ingrese la dotación o repartición del funcionario.'
                                });
                                return;
                            }

                            var btn = $(this);
                            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

                            $.ajax({
                                url: '{{ url('/Autenti/funcionario') }}/' + encodeURIComponent(rut) + '/dotacion',
                                type: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    dotacion: dotacion
                                },
                                success: function(resp) {
                                    $('#modalEditarDotacion').modal('hide');
                                    var $fila = $('#fila-' + rut);
                                    if ($fila.find('.txt-dotacion').length) {
                                        $fila.find('.txt-dotacion').text(resp.dotacion);
                                    } else {
                                        $fila.find('.col-dotacion').text(resp.dotacion);
                                    }
                                    $fila.find('.btn-edit-dotacion').data('dotacion', resp.dotacion);
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Dotación Actualizada',
                                        text: resp.message,
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                },
                                error: function(xhr) {
                                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error al actualizar dotación.';
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: msg
                                    });
                                },
                                complete: function() {
                                    btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Dotación');
                                }
                            });
                        });
                    });
                </script>

            </div>
        </div>
    </div>
</div>

<!-- Modal Eliminar / Desvincular Usuario en AutentificaTic API -->
<div class="modal fade" id="modalEliminarRut" tabindex="-1" role="dialog" aria-labelledby="modalEliminarRutLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalEliminarRutLabel">
                    <i class="fas fa-user-times mr-2"></i> Eliminar Usuario en Autentificatic API
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEliminarRutManual">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning" style="font-size: 13px;">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Atención:</strong> Esta acción invocará directamente el endpoint institucional 
                        <code>DELETE /api/institutional-app-user-from-external-app</code> de Autentificatic API para revocar el control de acceso en 
                        <strong>pacdilocar.carabineros.cl</strong>.
                    </div>
                    <div class="form-group">
                        <label for="rutEliminarInput" class="font-weight-bold">RUN del Funcionario a Desvincular:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            </div>
                            <input type="text" 
                                   id="rutEliminarInput" 
                                   name="rut" 
                                   class="form-control" 
                                   placeholder="Ej: 12345678-9 o 123456789" 
                                   maxlength="12" 
                                   required>
                        </div>
                        <small class="form-text text-muted">Ingrese el RUN sin puntos, con o sin guión.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger" id="btnConfirmarEliminarManual">
                        <i class="fas fa-trash-alt"></i> Eliminar de Autentificatic
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modificar Dotación del Funcionario -->
<div class="modal fade" id="modalEditarDotacion" tabindex="-1" role="dialog" aria-labelledby="modalEditarDotacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-primary">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalEditarDotacionLabel">
                    <i class="fas fa-building mr-2"></i> Modificar Dotación del Funcionario
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEditarDotacion" onsubmit="return false;">
                <input type="hidden" id="editDotacionRut">
                <div class="modal-body">
                    <div class="form-group mb-2">
                        <label class="text-muted mb-0" style="font-size: 13px;">Funcionario:</label>
                        <div id="editDotacionNombre" class="font-weight-bold text-dark" style="font-size: 15px;"></div>
                        <span id="editDotacionRutDisplay" class="badge badge-secondary"></span>
                    </div>
                    <hr class="my-2">
                    <div class="form-group">
                        <label for="inputNuevaDotacion" class="font-weight-bold">Dotación / Repartición oficial:</label>
                        <div class="input-group">
                            <input type="text" id="inputNuevaDotacion" class="form-control" list="listDepartamentosModal" placeholder="Ej: 3RA. COM. SANTIAGO CENTRAL o Departamento L.2.">
                            <datalist id="listDepartamentosModal">
                                @foreach(\App\Models\Departamento::orderBy('detalle')->get() as $d)
                                    <option value="{{ $d->detalle }}">
                                @endforeach
                            </datalist>
                            <div class="input-group-append">
                                <span class="input-group-text bg-white"><i class="fas fa-building text-muted"></i></span>
                            </div>
                        </div>
                        <small class="form-text text-muted">Puede seleccionar un departamento de la lista o escribir el nombre de la unidad / comisaría / repartición donde pertenece el funcionario.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnGuardarDotacion">
                        <i class="fas fa-save"></i> Guardar Dotación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
