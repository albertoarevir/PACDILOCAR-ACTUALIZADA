@extends('layouts.admin')
@section('content')
    <div class="content" style="margin-left: 20px">
        <h2>Ingresar Nueva Especie o Servicio</h2>

        @if ($message = Session::get('mensaje'))
            <script @cspNonce>
                Swal.fire({
                    title: "Buen trabajo !!",
                    text: "{{ $message }}",
                    icon: "success"
                });
            </script>
        @endif

        <div class="row">
            <div class="col-md-6">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><b>Registrar Nueva Especie/Servicio</b></h3>
                    </div>
                    <div class="card-body" style="display: block;">
                        <form action="{{ route('especies.store') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="departamento_id">Departamento</label>
                                <select name="departamento_id" id="departamento_id" class="form-control @error('departamento_id') is-invalid @enderror" required>
                                    @if ($departamentos->count() > 1)
                                        <option value="">Seleccione un departamento</option>
                                    @endif
                                    @foreach ($departamentos as $departamento)
                                        <option value="{{ $departamento->id }}" {{ (old('departamento_id') == $departamento->id || $departamentos->count() === 1) ? 'selected' : '' }}>
                                            {{ $departamento->detalle }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('departamento_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="detalle">Detalle de Especie/Servicio</label>
                                <input type="text" name="detalle" id="detalle" class="form-control @error('detalle') is-invalid @enderror" value="{{ old('detalle') }}" style="text-transform: uppercase" oninput="this.value = this.value.toUpperCase()" required>
                                @error('detalle')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <a href="{{ route('especies.index') }}" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Especie/Servicio</button>
                            </div>
                        </form>
                    </div>
                    </div>
                </div>
        </div>

        
    </div>
@endsection
