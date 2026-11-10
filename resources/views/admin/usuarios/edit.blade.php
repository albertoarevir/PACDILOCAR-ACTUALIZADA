@extends('layouts.admin')

@section('content')
    <div class="row">
        <h2 style="margin-left: 33px">Modificar Usuario: {{$usuario->name}}</h2>
    </div>
    <div class="row">
        <div class="col-md-6" style="margin-left: 30px">
            <div class="card card-outline card-success">
                
                <div class="card-body">
                    <form action="{{url('admin/usuarios', $usuario->id)}}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="col-md-12">
                            <div class="row">                            
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Rut</label>
                                        <input type="text" value="{{old('Rut', $usuario->Rut)}}" name="Rut" class="form-control" maxlength="12" required>
                                        @error('Rut')
                                        <small style="color: red">{{$message}}</small>
                                        @enderror
                                    </div>
                                </div>
                        
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="">Nombre del usuario</label>
                                        <input type="text" value="{{old('name', $usuario->name)}}" name="name" class="form-control" required>
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
                                        <select name="departamento_id" id="departamento_id" class="form-control @error('departamento_id') is-invalid @enderror" required>
                                            <option value="">-- Seleccione Departamento --</option>
                                            @foreach ($departamentos as $departamento)
                                                <option value="{{ $departamento->id }}" {{ old('departamento_id', $usuario->departamento_id) == $departamento->id ? 'selected' : '' }}>
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
                                    <button type="submit" class="btn btn-success"><i class="bi bi-floppy2"></i> Actualizar usuario</button>
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
            $('input[name="Rut"]').on('blur', function() {
                var rut = $(this).val().trim();
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
                        }
                    });
                }
            });
        });
    </script>
@endsection
