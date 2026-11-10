@extends('layouts.admin')

@section('content')
    <div class="row">
        <h2  style="margin-left: 30px">Usuario: {{$usuario->email}}</h2>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card card-danger"  style="margin-left: 30px">
                <div class="card-header"> 
                    <h3 class="card-title">¿Esta seguro de eliminar este registro?</h3>

                </div>
                <div class="card-body">
                    <div class="alert alert-warning" style="font-size: 13px;">
                        <i class="fas fa-info-circle mr-1"></i>
                        Al confirmar la eliminación de este usuario en PAC, también será <strong>eliminado y desvinculado automáticamente de Autentificatic API</strong> para mantener la consistencia institucional.
                    </div>
                    <form action="{{url('/admin/usuarios', $usuario->id)}}" method="post">
                        @csrf
                        @method('DELETE')
                        <div class="row">
                            
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="">Rut</label>
                                    <input type="text" value="{{$usuario->Rut}}" name="Rut" class="form-control" maxlength="9" disabled>
                                    @error('Rut')
                                    <small style="color: red">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="">Código</label>
                                    <input type="text" value="{{$usuario->Codigo}}" name="Codigo" class="form-control" disabled>
                                    @error('Codigo')
                                    <small style="color: red">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="">Grado</label>
                                    <input type="text" value="{{$usuario->Grado}}" name="Grado" class="form-control" disabled>
                                    @error('Grado')
                                    <small style="color: red">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="">Nombre del usuario</label>
                                    <input type="text" value="{{$usuario->name}}" name="name" class="form-control" disabled>
                                    @error('name')
                                    <small style="color: red">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="">Email</label>
                                    <input type="email" value="{{$usuario->email}}" name="email" class="form-control" disabled>
                                    @error('email')
                                    <small style="color: red">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                       
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="">Fecha Caducidad</label>
                                    <input type="date" value="{{$usuario->Fecha_caducidad}}"  name="Fecha_caducidad" class="form-control" disabled>
                                </div>
                            </div>
                        </div>


                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <a href="{{url('admin/usuarios')}}" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-danger"><i class="bi bi-floppy2"></i> Eliminar registro</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
