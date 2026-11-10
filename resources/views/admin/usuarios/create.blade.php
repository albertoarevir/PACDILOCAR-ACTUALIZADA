@extends('layouts.admin')

@section('content')
    <div class="row">
        <h2 style="margin-left: 33px">Nuevo usuario</h2>
    </div>
    <div class="row">
        <div class="col-md-6" style="margin-left: 30px">
            <div class="card card-outline card-primary">
                
                <div class="card-body">
                    <form action="{{url('/admin/usuarios/create')}}" method="post">
                        @csrf
                    <div class="col-md-12">    
                            <div class="row">                            
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Rut</label>
                                        <input type="text" value="{{ old('Rut', $rut ?? request('Rut', request('rut'))) }}" name="Rut" class="form-control" maxlength="12" required>
                                        @error('Rut')
                                        <small style="color: red">{{$message}}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="">Nombre del usuario (Apellidos y Nombres)</label>
                                        <input type="text" value="{{ old('name', $name ?? request('name', request('Nombre', request('nombres')))) }}" name="name" class="form-control" required>
                                        @error('name')
                                        <small style="color: red">{{$message}}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="">Departamento</label>
                                        <select name="departamento_id" id="Dotacion" class="form-control @error('departamento_id') is-invalid @enderror" required>
                                            <option value="">-- Seleccione Departamento --</option>
                                            @foreach ($departamentos as $departamento)
                                                <option value="{{ $departamento->id }}" {{ old('departamento_id') == $departamento->id ? 'selected' : '' }}>
                                                    {{ $departamento->detalle }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('departamento_id')
                                            <small style="color: red">{{$message}}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>
        
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <a href="{{url('admin/usuarios')}}" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-floppy2"></i> Guardar registro</button>
                            </div>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script @cspNonce>
        $(function() {
            function buscarFuncionario(rut) {
                var $nameInput = $('input[name="name"]');
                if (rut.length >= 8 && $nameInput.val().trim() === '') {
                    $.ajax({
                        url: '{{ url('/Autenti/sync') }}/' + encodeURIComponent(rut),
                        type: 'POST',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function(resp) {
                            if (resp.apellidos_nombres || resp.nombre_completo) {
                                $nameInput.val(resp.apellidos_nombres || resp.nombre_completo);
                            } else if (resp.funcionario) {
                                var f = resp.funcionario;
                                var nombreApe = (f.Apellidos ? f.Apellidos + ', ' : '') + (f.Nombres || '');
                                if (nombreApe.trim()) {
                                    $nameInput.val(nombreApe.trim());
                                }
                            }

                            if (resp.dotacion) {
                                var dotApi = resp.dotacion.trim().toUpperCase();
                                var $depSelect = $('#Dotacion');
                                if (!$depSelect.val()) {
                                    $depSelect.find('option').each(function() {
                                        var optTxt = $(this).text().trim().toUpperCase();
                                        if (optTxt && (optTxt === dotApi || dotApi.indexOf(optTxt) !== -1 || optTxt.indexOf(dotApi) !== -1)) {
                                            $depSelect.val($(this).val());
                                            return false;
                                        }
                                    });
                                }
                            }
                        }
                    });
                }
            }

            $('input[name="Rut"]').on('blur change', function() {
                buscarFuncionario($(this).val().trim());
            });

            // Si al cargar la página ya viene un Rut pero el campo name está vacío, buscarlo automáticamente
            var initialRut = $('input[name="Rut"]').val().trim();
            if (initialRut.length >= 8 && $('input[name="name"]').val().trim() === '') {
                buscarFuncionario(initialRut);
            }
        });
    </script>
@endsection
