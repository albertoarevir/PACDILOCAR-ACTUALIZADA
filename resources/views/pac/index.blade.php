@extends('layouts.admin')
@use ('App\Models\Orden')
@section('content')
    <div class="row">
        <div class="col-md-11" style="margin-left: 30px">
            <h2 style="font-size: 30px; color:rgb(9, 9, 9); margin-bottom: 3; margin-left: 7px;">
                <strong>Administración proceso "Plan Anual de Compras"</strong>
            </h2>
            <br>
            <div class="card card-outline card-primary">
                <div class="card-header">

                    @can('INGRESAR PROYECTO')
                        <div class="card-tools">
                            <a href="{{ url('pac/create') }}" style="background-color: #206113" class="btn btn-secondary"
                                style="font-size: 12px; color:rgb(240, 242, 245); margin-bottom: 0;">
                                <strong> Registrar nuevo Proyecto de adquisición</strong>
                            </a>
                        </div>
                    @endcan

                </div>
                <div class="card-body" style="font-size: 14px; color:rgb(12, 13, 14); margin-bottom: 0;">

                    <div class="card mb-4 shadow-sm">
                        <div class="card-body bg-light">
                            <form method="GET" action="{{ url('pac') }}" class="row g-2 align-items-end">
                                <div class="col-md-1">
                                    <label class="form-label small fw-bold">Año</label>
                                    <input type="number" name="year" class="form-control form-control-sm"
                                        placeholder="2026" value="{{ request('year') }}">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Departamento</label>
                                    <select name="departamento_id" class="form-control form-control-sm">
                                        <option value="">-- Todos --</option>
                                        @foreach ($departamentos as $dep)
                                            <option value="{{ $dep->id }}"
                                                {{ request('departamento_id') == $dep->id ? 'selected' : '' }}>
                                                {{ $dep->detalle }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Clasificador</label>
                                    <input type="text" name="clasificador" class="form-control form-control-sm"
                                        placeholder="Buscar..." value="{{ request('clasificador') }}">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Item Presup.</label>
                                    <input type="text" name="codigo" class="form-control form-control-sm"
                                        placeholder="Código..." value="{{ request('codigo') }}">
                                </div>

                                {{--
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Especie</label>
                                    <select name="especie_id" class="form-control form-control-sm">
                                        <option value="">-- Todas --</option>
                                        @foreach ($especies as $esp)
                                            <option value="{{ $esp->id }}"
                                                {{ request('especie_id') == $esp->id ? 'selected' : '' }}>
                                                {{ $esp->detalle }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                --}}
                                <div class="col-md-3">
                                    <div class="d-flex gap-1">
                                        <button type="submit" class="btn btn-primary btn-sm flex-fill">🔍 Filtrar</button>
                                        <a href="{{ url('pac') }}"
                                            class="btn btn-outline-secondary btn-sm flex-fill">Limpiar</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>




                    <table id="example1" class="table table-striped table-hover table-bordered" style="width: 100%; ">
                        <thead style="background-color: #206113">
                            <tr>
                                <!-- <th style="width: 2%;">N° Orden</th>-->
                                <th style="width: 5%; color: #fff;">Año</th>
                                <th style="width: 8%;color: #fff;">Departamento responsable</th>
                                <th style="width: 3%;color: #fff;">Id del Proyecto</th>
                                <th style="width: 8%;color: #fff;">Cantidad Licitaciones</th>
                                <th style="width: 6%;color: #fff;">Cantidad O/C</th>
                                <th style="width: 6%;color: #fff;">Subitulo Presupuestario</th>
                                <th style="width: 6%;color: #fff;">Item Presupuestario</th>
                                <th style="width: 20%;color: #fff;">Especie/Servicio</th>
                                <th style="width: 4%;color: #fff;">Cantidad</th>
                                <th style="width: 9%;color: #fff;">Presupuesto Asignado Inicial</th>
                                <th style="width: 9%;color: #fff;">Presupuesto Devengado</th>
                                <th style="width: 6%;color: #fff;">Porcentaje ejecución</th>
                                {{-- <th style="width: 10%;color: #fff;">Item-Presupuestario</th> --}}
                                {{--  <th style="width: 10%;color: #fff;">Unidad de Compra</th> --}}
                                <th style="width: 5%;color: #fff;">Estado del proyecto</th>
                                {{-- <th style="width: 5%;color: #fff;">Estado Licitación</th>
                                <th style="width: 5%;color: #fff;">Estado Orden de Compra</th> --}}
                                {{-- <th style="width: 8%; color:#fff;">Fecha creación registro</th> --}}
                                {{-- <th style="width: 8%;color: #fff;">Fecha actualización</th> --}}
                                <!--<th style="width: 8%;">Auditoría</th> >--><!-- Nueva columna -->
                                <th style="width: 8%; color: #fff; text-align:center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $num = 1;
                            ?>
                            @foreach ($pacs->sortByDesc('id') as $pac)
                                @php
                                    $monto_total = (float) $pac->ordenes
                                        ->filter(fn($o) => strtolower(trim($o->estadocompra->detalle ?? '')) !== 'orden de compra cancelada')
                                        ->sum('monto');
                                    $total_ordenes = $pac->ordenes->count();
                                    $pac->monto_total = $monto_total;
                                @endphp
                                <tr>
                                    <!-- <td style="text-align: center">{{ $num++ }}</td>-->
                                    <td style="text-align: center">{{ $pac->year }}</td>
                                    <td>{{ $pac->departamento->detalle }}</td>
                                    <td><strong>{{ str_pad($pac->id, 4, '0', STR_PAD_LEFT) }}</strong></td>
                                    <td>
                                        <button
                                            class="btn btn-{{ $pac->modalidades->count() > 0 ? 'warning' : 'danger' }} btn-sm btn-rounded"
                                            data-toggle="modal" data-target="#licitacionModal-{{ $pac->id }}">
                                            <strong> {{ $pac->modalidades->count() }} Licitación(es)</strong>
                                        </button>
                                    </td>

                                    <td>
                                        {{--  <button
                                            class="btn btn-{{ $pac->ordenes->count() > 0 ? 'warning' : 'danger' }} btn-sm btn-rounded"
                                            data-toggle="modal"
                                            data-target="#ordenModal-{{ $pac->id }}">
                                            {{ $pac->ordenes->count() }} Ordenes(s)
                                        </button> --}}
                                        <center><strong>{{ $pac->ordenes->count() }} Orden(s)</strong></center>
                                    </td>

                                    <td>{{ $pac->clasificador }}</td>
                                    {{--  <td>{{ $pac->item_presupuestario }}</td> --}}

                                    <td>
                                        <strong>{{ $pac->codigo }}</strong>
                                        <br>
                                        <button type="button" class="btn btn-xs btn-primary mt-1" data-toggle="modal"
                                            data-target="#modalSAP{{ $pac->id }}"
                                            style="font-size: 10px; padding: 1px 5px;">
                                            <i class="bi bi-search"></i> Ver SAP
                                        </button>
                                        {{-- Modal para el SAP   --}}
                                        <div class="modal fade" id="modalSAP{{ $pac->id }}" tabindex="-1"
                                            role="dialog">
                                            <div class="modal-dialog modal-sm" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-navy">
                                                        <h6 class="modal-title">Datos Presupuestarios ERP-SAP</h6>
                                                        <button type="button" class="close text-white"
                                                            data-dismiss="modal">&times;</button>
                                                    </div>
                                                    <div class="modal-body text-left" style="font-size: 13px;">
                                                        @if ($pac->infoPresupuesto)
                                                            <p><strong>Año SAP:</strong> {{ $pac->infoPresupuesto->year }}
                                                            </p>
                                                            <p><strong>Clasificador:</strong>
                                                                {{ $pac->infoPresupuesto->clasificador }}</p>
                                                            <p><strong>Item:</strong> {{ $pac->infoPresupuesto->item }}</p>
                                                            <p><strong>Departamento:</strong> {{ $pac->infoPresupuesto->departamento->detalle ?? 'N/A' }}
                                                            </p>
                                                            <hr>


                                                            <div class="text-center bg-light p-2 border">
                                                                <label class="text-success d-block mb-0">
                                                                    MONTO ACTUALIZADO SAP
                                                                    ({{ $pac->departamento->abreviatura ?? 'UNIDAD' }})
                                                                </label>
                                                                <h5 class="text-bold">
                                                                    $
                                                                    {{ number_format($pac->infoPresupuesto->monto ?? 0, 0, ',', '.') }}
                                                                </h5>
                                                            </div>

                                                            <center class="mb-2">
                                                                <small class="text-muted">
                                                                    @if ($pac->infoPresupuesto)
                                                                        {{-- Mostramos la fecha con hora y minutos como pediste --}}
                                                                        <strong> Última Sincronización:
                                                                        {{ \Carbon\Carbon::parse($pac->infoPresupuesto->updated_at)->format('d-m-Y g:i a') }}</strong>

                                                                    @else
                                                                        <span class="text-danger font-weight-bold">SIN
                                                                            PRESUPUESTO ASIGNADO EN SAP</span>
                                                                    @endif
                                                                </small>
                                                            </center>

                                                            <div class="text-center bg-dark p-2 border mt-2"
                                                                style="border-radius: 4px;">
                                                                <label class="text-white d-block mb-0 small">TOTAL
                                                                    COMPROMETIDO (SEGÚN ÍTEM Y DEPTO)</label>
                                                                <h5 class="text-bold text-warning mb-0">
                                                                    $
                                                                    {{ number_format($pac->total_comprometido_item, 0, ',', '.') }}
                                                                </h5>
                                                            </div>

                                                            <div class="text-center p-2 border mt-2 {{ $pac->saldo_disponible < 0 ? 'bg-danger' : 'bg-info' }}"
                                                                style="border-radius: 4px;">
                                                                <label class="text-white d-block mb-0 small">SALDO
                                                                    DISPONIBLE</label>
                                                                <h5 class="text-bold text-white mb-0">
                                                                    $
                                                                    {{ number_format($pac->saldo_disponible, 0, ',', '.') }}
                                                                </h5>
                                                            </div>

                                                            @if ($pac->saldo_disponible < 0)
                                                                <div class="text-center mt-1">
                                                                    <small class="text-danger text-bold">⚠️ ADVERTENCIA:
                                                                        Presupuesto excedido</small>
                                                                </div>
                                                            @endif

                                                            @if ($pac->infoPresupuesto->observaciones)
                                                                <p class="mt-2 small text-muted"><strong>Obs:</strong>
                                                                    {{ $pac->infoPresupuesto->observaciones }}</p>
                                                            @endif
                                                        @else
                                                            <div class="alert alert-warning small">
                                                                <i class="bi bi-exclamation-triangle"></i> No existe
                                                                información en la tabla Presupuestos para el ítem
                                                                <strong>{{ $pac->codigo }}</strong>.
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary btn-xs"
                                                            data-dismiss="modal">Cerrar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>




                                    {{-- <td>{{ $pac->codigo }}</td> --}}


                                    <td>{{ $pac->especie->detalle }}</td>
                                    <td>{{ $pac->cantidad }}</td>


                                    <td>$ {{ number_format($pac->presupuesto, 0, '.', '.') }}</td>
                                    <td>$ {{ number_format($pac->monto_total, 0, '.', '.') }}</td>

                                    <td style="text-align: left; width: 200px;">
                                        <strong>
                                            {{ $pac->presupuesto == 0 ? 'No hay presupuesto' : number_format(($pac->monto_total / $pac->presupuesto) * 100, 2) }}
                                            %
                                        </strong>
                                        <div class="progress"
                                            style="height: 10px; width: 100%;
                                              margin-left: 0; padding-left: 0; display: inline-block; position: relative;
                                              left: 0; background-color: #dbe72b; border-radius: 2px;">
                                            <div class="progress-bar"
                                                style="background-color: {{ $pac->monto_total >= $pac->presupuesto ? 'red' : 'green' }}; width: {{ $pac->presupuesto == 0 ? '0%' : ($pac->monto_total / $pac->presupuesto) * 100 }}%;
                                                 height: 20px; border-radius: 5px;">
                                            </div>
                                        </div>
                                        <span
                                            style="font-size: 12px; color: {{ $pac->monto_total > $pac->presupuesto ? 'red' : 'black' }}">
                                            {{ $pac->monto_total > $pac->presupuesto ? 'Excedido en:' : ' Disponible:' }} $
                                            {{ number_format($pac->presupuesto - $pac->monto_total) }}
                                        </span>
                                    </td>
                                    {{-- <td>{{ $pac->codigo }}</td> --}}
                                    {{--  <td>{{ $pac->unidadcompra }}</td> --}}
                                    <td>
                                        <button
                                            class="btn btn-{{ $pac->estado->detalle == 'Aprobado Dilocar'
                                                ? 'success'
                                                : ($pac->estado->detalle == 'Rechazado Dilocar'
                                                    ? 'secondary'
                                                    : ($pac->estado->detalle == 'Terminado'
                                                        ? 'danger'
                                                        : 'warning')) }} btn-sm btn-rounded">
                                            {{ $pac->estado->detalle }}
                                        </button>
                                    </td>
                                    {{-- <td>{{ $pac->created_at }}</td> --}}
                                    {{--  <td>{{ $pac->updated_at }}</td> --}}
                                    <!-- <td>{!! $pac->auditoria !!}</td>--><!-- Mostrar el mensaje de auditoría -->

                                    <td style="text-align: center">
                                        <div class="btn-group float-right" role="group" aria-label="Basic example">

                                            @can('INGRESAR LICITACION')
                                                @if ($pac->estado->detalle == 'Aprobado Dilocar')
                                                    <a href="{{ route('modalidad.create', ['pac' => $pac->id, 'id_mod' => $pac->id_mod]) }}"
                                                        class="btn btn-info btn-sm" title="Ingreso de Licitaciones">
                                                        <i class="bi bi-arrow-up-left-square"></i>
                                                    </a>
                                                @endif
                                            @endcan

                                            @can('MODIFICAR PROYECTO')
                                                <a href="{{ url('pac/' . $pac->id . '/edit') }}" type="button"
                                                    class="btn btn-success btn-sm" title="Editar registro"><i
                                                        class="bi bi-pencil"></i>
                                                </a>
                                            @endcan

                                            @can('ELIMINAR PROYECTO')
                                                <form action="{{ url('pac', $pac->id) }}" method="post"
                                                    class="d-inline formulario-eliminar">
                                                    @csrf
                                                    {{ method_field('DELETE') }}
                                                    <button class="btn btn-danger btn-sm" title="Eliminar de la base de datos"
                                                        type="submit">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                </form>
                                            @endcan

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- MODAL 1 - > llamada de target = licitacioModal - Este codigo corresponde al modal que abre desde el index principal de registro de formulario de seguimiento de licitaciones de compra --}}
                   
                    @foreach ($pacs->sortByDesc('id') as $pac)
                        @if (auth()->user()->departamento_id == 7 || $pac->departamento->id == auth()->user()->departamento_id)
                            @php
                                $total_ordenes = 0;
                            @endphp
                            <div class="modal fade" id="licitacionModal-{{ $pac->id }}" tabindex="-1"
                                role="dialog" aria-labelledby="licitacionModalLabel-{{ $pac->id }}"
                                aria-hidden="true" style="max-width: 1500px; margin-left: 50px;">
                                <div class="modal-dialog modal-xl" role="document"
                                    style="max-width: 1500px; margin-left: 50px;">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="licitacionModalLabel-{{ $pac->id }}"
                                                style="font-size: 26px;">Formulario de seguimiento:</h5>
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="container-fluid">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <h3 class="text-center">N° Identificador de Proyecto:
                                                            {{ str_pad($pac->id, 4, '0', STR_PAD_LEFT) }}</h3>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="card">

                                                            <div class="card-body">
                                                                <table id="example2"
                                                                    class="table table-striped table-hover table-bordered"
                                                                    style="width: 100%;">
                                                                    <thead style="background-color: #44afcc">
                                                                        <tr>
                                                                            <th style="width: 2%;">N° Orden</th>
                                                                            <th style="width: 4%"> Número de Licitación
                                                                            </th>
                                                                            <th style="width: 2%;">Cantidad Ordenes de
                                                                                Compras
                                                                            </th>
                                                                            <th style="width: 15%"> Especie o Servicio</th>
                                                                            <th style="width: 15%;">Modalidad de compra
                                                                            </th>
                                                                            <th style="width: 5%;">Estado</th>
                                                                            <th style="width: 20%;">Fecha ingreso creación
                                                                                registro</th>
                                                                            <th style="width: 2%;">Fecha última
                                                                                actualización
                                                                            </th>
                                                                            <th style="width: 2%; text-align:center">Acción
                                                                            </th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php
                                                                        $num = 1;
                                                                        ?>
                                                                        @foreach ($pac->modalidades as $modalidad)
                                                                            @if (auth()->user()->departamento_id == 7 || $modalidad->pac->departamento->id == auth()->user()->departamento_id)
                                                                                @php
                                                                                    $total_ordenes += $modalidad->ordenes->count();
                                                                                @endphp
                                                                                <tr>
                                                                                    <td style="text-align: center">
                                                                                        {{ $num++ }}</td>
                                                                                    {{--   <td><strong>{{ str_pad($modalidad->id_proyecto, 4, '0', STR_PAD_LEFT) }}</strong>
                                                                                  </td> --}}

                                                                                    <td>{{ $modalidad->numero }}</td>
                                                                                    <td>

                                                                                        <button
                                                                                            class="btn btn-{{ $modalidad->ordenes()->count() > 0 ? 'warning' : 'danger' }} btn-sm btn-rounded"
                                                                                            data-toggle="modal"
                                                                                            data-target="#ordenModal-{{ $pac->id }}-{{ $modalidad->id }}">
                                                                                            {{ $modalidad->ordenes()->count() }}
                                                                                            Ordenes(s)
                                                                                        </button>

                                                                                    </td>
                                                                                    {{--  <td>
                                                                        <button
                                                                            class="btn btn-{{ $modalidad->ordenes->count() > 0 ? 'warning' : 'danger' }} btn-sm btn-rounded">
                                                                            {{ $modalidad->ordenes->count() }}
                                                                            Orden(es)
                                                                        </button>
                                                                    </td> --}}
                                                                                    <td>
                                                                                        @if ($modalidad->pac && $modalidad->pac->especie)
                                                                                            {{ $modalidad->pac->especie->detalle }}
                                                                                        @else
                                                                                            N/A
                                                                                        @endif
                                                                                    </td>
                                                                                    </td>
                                                                                    <td>{{ $modalidad->modalidad }}</td>

                                                                                    <td>
                                                                                        <button
                                                                                            class="btn btn-{{ $modalidad->estado->detalle == 'Adjudicada'
                                                                                                ? 'success'
                                                                                                : ($modalidad->estado->detalle == 'Suspendida' || $modalidad->estado->detalle == 'Revocada'
                                                                                                    ? 'danger'
                                                                                                    : 'warning') }} btn-sm btn-rounded">
                                                                                            {{ $modalidad->estado->detalle }}
                                                                                        </button>
                                                                                    </td>

                                                                                    <td>{{ $modalidad->created_at }}</td>
                                                                                    <td>{{ $modalidad->updated_at }}</td>
                                                                                    <td style="text-align: center">
                                                                                        <div class="btn-group float-right"
                                                                                            role="group"
                                                                                            aria-label="Basic example">


                                                                                            @can('INGRESAR ORDEN DE COMPRA')
                                                                                                <a href="{{ route('ordenes.create', ['id_mod' => $modalidad->id, 'pac' => $pac->id, 'modalidad' => $modalidad->modalidad, 'numero' => urlencode($modalidad->numero)]) }}"
                                                                                                    class="btn btn-warning btn-sm"
                                                                                                    title="Ingreso de Orden de compras">
                                                                                                    <i class="bi bi-eye"></i>
                                                                                                </a>
                                                                                            @endcan

                                                                                            @can('MODIFICAR LICITACION')
                                                                                                <a href="{{ url('modalidad/' . $modalidad->id . '/edit') }}"
                                                                                                    type="button"
                                                                                                    class="btn btn-success btn-sm"
                                                                                                    title="Modificar registro"><i
                                                                                                        class="bi bi-pencil"></i>
                                                                                                </a>
                                                                                            @endcan

                                                                                            @can('ELIMINAR LICITACION')
                                                                                                <form
                                                                                                    action="{{ route('modalidad.destroy', $modalidad->id) }}"
                                                                                                    method="post"
                                                                                                    class="d-inline formulario-eliminar">
                                                                                                    @csrf
                                                                                                    {{ method_field('DELETE') }}
                                                                                                    <button
                                                                                                        class="btn btn-danger btn-sm"
                                                                                                        title="Eliminar registro"
                                                                                                        type="submit">
                                                                                                        <i
                                                                                                            class="bi bi-trash-fill"></i>
                                                                                                    </button>
                                                                                                </form>
                                                                                            @endcan
                                                                                        </div>
                                                                                    </td>
                                                                                </tr>
                                                                            @endif
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                                <p>Cantidad de órdenes de compra: {{ $total_ordenes }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-dismiss="modal">Cerrar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach

                    {{-- Modal 2 - > llamada de targe = ordenesModal - Este codigo corresponde al modal de formulario de seguimiento de ordenes de compras --}}
                    @foreach ($pacs->sortByDesc('id') as $pac)
                        <div class="modal fade" id="ordenesModal-{{ $pac->id }}" tabindex="-1" role="dialog"
                            aria-labelledby="ordenesModalLabel-{{ $pac->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-xl" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="ordenesModalLabel-{{ $pac->id }}"
                                            style="font-size: 26px;">Formulario de seguimiento</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="container-fluid">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h3 class="text-center">N° Identificador de Proyecto:
                                                        {{ str_pad($pac->id, 4, '0', STR_PAD_LEFT) }}</h3>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <table id="example2"
                                                                class="table table-striped table-hover table-bordered"
                                                                style="width: 100%;">
                                                                <thead style="background-color: #44afcc">
                                                                    <tr>
                                                                        <th style="width: 2%;">N° Orden</th>
                                                                        {{--   <th style="width: 2%"> Id Proyecto</th> --}}
                                                                        <th style="width: 2%;">Numero Licitación</th>
                                                                        <th style="width: 5%"> Especie o Servicio</th>
                                                                        <th style="width: 15%;">Modalidad de compra</th>
                                                                        <th style="width: 4%;">Numero Orden de Compra</th>
                                                                        <th style="width: 4%;">Monto $$</th>
                                                                        <th style="width: 5%;">Estado</th>
                                                                        {{-- <th style="width: 19%">Observación</th> --}}
                                                                        <th style="width: 2%;">Fecha ingreso O/C</th>
                                                                        <th style="width: 2%;">Fecha última actualización
                                                                        </th>
                                                                        {{-- <th style="width: 5%;">Seguimiento de Auditoría al proceso</th> <!-- Nueva columna --> --}}

                                                                        <th style="width: 2%; text-align:center">Acción
                                                                        </th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    $num = 1;
                                                                    ?>
                                                                    @foreach ($pac->modalidades as $modalidad)
                                                                        @foreach ($modalidad->ordenes->sortByDesc('id_licitacion') as $orden)
                                                                            <tr>
                                                                                <td style="text-align: center">
                                                                                    {{ $num++ }}</td>
                                                                                {{-- <td><strong>{{ str_pad($orden->id_proyecto, 4, '0', STR_PAD_LEFT) }}</strong></td> --}}
                                                                                @if ($orden->licitacion)
                                                                                    <td><strong>{{ $orden->licitacion->numero }}</strong>
                                                                                    </td>
                                                                                @else
                                                                                    <td>No hay licitación</td>
                                                                                @endif
                                                                                <td>{{ $orden->pac->especie->detalle }}
                                                                                </td>
                                                                                <td>{{ $orden->modalidad }}</td>
                                                                                <td>{{ $orden->numero }}</td>
                                                                                <td>$
                                                                                    {{ number_format((float) $orden->monto, 0, '.', '.') }}
                                                                                </td>

                                                                                <td>
                                                                                    @if ($pac->estado)
                                                                                        <button
                                                                                            class="btn btn-{{ $pac->estado->detalle == 'Aprobado Dilocar'
                                                                                                ? 'success'
                                                                                                : ($pac->estado->detalle == 'Rechazado Dilocar'
                                                                                                    ? 'danger'
                                                                                                    : 'warning') }} btn-sm btn-rounded">
                                                                                            {{ $pac->estado->detalle }}
                                                                                        </button>
                                                                                    @else
                                                                                        <button
                                                                                            class="btn btn-danger btn-sm btn-rounded">
                                                                                            Sin estado
                                                                                        </button>
                                                                                    @endif
                                                                                </td>
                                                                                {{-- <td><pre style="font-size: 14px;">{{ $modalidad->observacion}}</pre></td> --}}
                                                                                <td>{{ $orden->created_at }}</td>
                                                                                <td>{{ $orden->updated_at }}</td>
                                                                                {{-- <td>{!! $orden->auditoria !!}</td>  <!-- Mostrar el mensaje de auditoría --> --}}
                                                                                <td style="text-align: center">
                                                                                    <div class="btn-group float-right"
                                                                                        role="group"
                                                                                        aria-label="Basic example">
                                                                                        {{-- <a href="{{ route('ordenes.create', ['pac_id' => $pac->id]) }}"
                                                                class="btn btn-warning btn-sm" title="Ingreso de Orden de compras">
                                                                <i class="bi bi-eye"></i>
                                                            </a>     --}}
                                                                                        <a href="{{ url('ordenes/' . $orden->id . '/edit') }}"
                                                                                            type="button"
                                                                                            class="btn btn-success btn-sm"
                                                                                            title="Modificar registro"><i
                                                                                                class="bi bi-pencil"></i></a>
                                                                                        <form
                                                                                            action="{{ route('ordenes.destroy', $orden) }}"
                                                                                            method="post"
                                                                                            class="d-inline formulario-eliminar">
                                                                                            @csrf
                                                                                            {{ method_field('DELETE') }}
                                                                                            <button
                                                                                                class="btn btn-danger btn-sm"
                                                                                                title="Eliminar registro"
                                                                                                type="submit">
                                                                                                <i
                                                                                                    class="bi bi-trash-fill"></i>
                                                                                            </button>
                                                                                        </form>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-dismiss="modal">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Model 3 - > Este codigo abre el modal ordenModal desde licitacioModal para el seguimiento de ordenes de compras --}}
                    @foreach ($pacs as $pac)
                        @foreach ($pac->modalidades as $modalidad)
                            @php
                                $ordenesModal = $modalidad->ordenes()->get();
                            @endphp
                            <div class="modal fade" id="ordenModal-{{ $pac->id }}-{{ $modalidad->id }}"
                                tabindex="-1" role="dialog"
                                aria-labelledby="ordenModalLabel-{{ $pac->id }}-{{ $modalidad->id }}"
                                aria-hidden="true">
                                <div class="modal-dialog modal-xl" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="container-fluid">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <h3 class="text-center">N° Identificador de la Licitacion:
                                                            {{ str_pad($modalidad->numero, 4, '0', STR_PAD_LEFT) }}</h3>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="card">

                                                            <div class="card-body">

                                                                <table
                                                                    id="example3-{{ $pac->id }}-{{ $modalidad->id }}"
                                                                    class="table table-striped table-hover table-bordered"
                                                                    style="width: 100%;">
                                                                    <thead style="background-color: #44afcc">
                                                                        <tr>
                                                                            <th style="width: 1%;">N° Orden</th>
                                                                            {{--   <th style="width: 2%"> Id Proyecto</th> --}}
                                                                            {{-- <th style="width: 2%;">Numero Licitación</th> --}}

                                                                            <th style="width: 5%"> Especie o Servicio</th>
                                                                            <th style="width: 13%">Modalidad de compra</th>
                                                                            <th style="width: 8%;">Numero Orden de Compra
                                                                            </th>
                                                                            <th style="width: 8%;">Monto $$</th>
                                                                            <th style="width: 5%;">Estado</th>
                                                                            {{-- <th style="width: 19%">Observación</th> --}}
                                                                            <th style="width: 2%;">Fecha ingreso O/C</th>
                                                                            <th style="width: 2%;">Fecha última
                                                                                actualización
                                                                            </th>
                                                                            {{-- <th style="width: 5%;">Seguimiento de Auditoría al proceso</th> <!-- Nueva columna --> --}}

                                                                            <th style="width: 2%; text-align:center">Acción
                                                                            </th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php
                                                                        $num = 1;
                                                                        ?>
                                                                        @foreach ($ordenesModal as $orden)
                                                                            <tr>
                                                                                <td style="text-align: center">
                                                                                    {{ $num++ }}</td>
                                                                                {{-- <td><strong>{{ str_pad($orden->id_proyecto, 4, '0', STR_PAD_LEFT) }}</strong></td> --}}
                                                                                {{-- @if ($orden->licitacion)
                                                                                    <td><strong>{{ $orden->licitacion->numero }}</strong>
                                                                                    </td>
                                                                                @else
                                                                                    <td>No hay licitación2</td>
                                                                                @endif --}}
                                                                                <td>{{ $orden->pac->especie->detalle }}
                                                                                </td>
                                                                                <td>{{ $orden->licitacion->modalidad }}
                                                                                </td>
                                                                                <td>{{ $orden->numero }}</td>

                                                                                <td>$
                                                                                    {{ number_format((float) str_replace('.', '', str_replace(',', '', $orden->monto)), 0, '.', '.') }}
                                                                                </td>

                                                                                <td>
                                                                                    @if ($orden->estadocompra)
                                                                                        <button
                                                                                            class="btn btn-{{ $orden->estadocompra->detalle == 'Aceptada'
                                                                                                ? 'success'
                                                                                                : ($orden->estadocompra->detalle == 'Cancelada' || $orden->estadocompra->detalle == 'Eliminada'
                                                                                                    ? 'danger'
                                                                                                    : 'warning') }} btn-sm btn-rounded">
                                                                                            {{ $orden->estadocompra->detalle }}
                                                                                        </button>
                                                                                    @else
                                                                                        <button
                                                                                            class="btn btn-danger btn-sm btn-rounded">
                                                                                            Sin estado de compra
                                                                                        </button>
                                                                                    @endif
                                                                                </td>

                                                                                {{-- <td><pre style="font-size: 14px;">{{ $modalidad->observacion}}</pre></td> --}}
                                                                                <td>{{ $orden->created_at }}</td>
                                                                                <td>{{ $orden->updated_at }}</td>
                                                                                {{-- <td>{!! $orden->auditoria !!}</td>  <!-- Mostrar el mensaje de auditoría --> --}}
                                                                                <td style="text-align: center">
                                                                                    <div class="btn-group float-right"
                                                                                        role="group"
                                                                                        aria-label="Basic example">

                                                                                        @can('MODIFICAR ORDEN DE COMPRA')
                                                                                            <a href="{{ url('ordenes/' . $orden->id . '/edit') }}"
                                                                                                type="button"
                                                                                                class="btn btn-success btn-sm"
                                                                                                title="Modificar registro"><i
                                                                                                    class="bi bi-pencil"></i>
                                                                                            </a>
                                                                                        @endcan

                                                                                        @can('ELIMINAR LICITACION')
                                                                                            <form
                                                                                                action="{{ route('ordenes.destroy', $orden) }}"
                                                                                                method="post"
                                                                                                class="d-inline formulario-eliminar">
                                                                                                @csrf
                                                                                                {{ method_field('DELETE') }}
                                                                                                <button
                                                                                                    class="btn btn-danger btn-sm"
                                                                                                    title="Eliminar registro"
                                                                                                    type="submit">
                                                                                                    <i
                                                                                                        class="bi bi-trash-fill"></i>
                                                                                                </button>
                                                                                            </form>
                                                                                        @endcan
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>

                                                                <script @cspNonce>
                                                                    $(function() {
                                                                        $("#example3-{{ $pac->id }}-{{ $modalidad->id }}").DataTable({
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
                                                                            "ordering": false,
                                                                            buttons: [{
                                                                                extend: 'collection',
                                                                                text: 'Reportes',
                                                                                orientation: 'landscape',
                                                                                buttons: [{
                                                                                    //      text: 'Copiar',
                                                                                    //      extend: 'copy',
                                                                                    //  }, //      extend: 'pdf'
                                                                                    //  }, {
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
                                                                            }, ],
                                                                        });
                                                                    });
                                                                </script>

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-dismiss="modal">Cerrar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endforeach


                    <script @cspNonce>
                        function formatNumber(input) {
                            let value = input.value.replace(/\./g, ''); // Elimina los puntos existentes
                            if (!isNaN(value)) {
                                // Formatea el número con puntos como separadores de miles
                                input.value = Number(value).toLocaleString('es'); // Alemán usa punto como separador
                            } else {
                                input.value = '';
                            }
                        }

                        function removeCommas() {
                            let input = document.getElementById('presupuesto');
                            input.value = input.value.replace(/\./g, ''); // Elimina los puntos en lugar de las comas
                        }
                        //Example 1                  

                        $(function() {
                            // Inicializamos DataTable y lo guardamos en una variable
                            var table = $("#example1").DataTable({
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
                                    "search": "Buscador General por rubro especie:",
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
                                "ordering": false,
                                "buttons": [{
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
                                }],

                                // --- LÓGICA DE SUMA DINÁMICA ---
                                "drawCallback": function(settings) {
                                    var api = this.api();

                                    // 1. Obtener datos de la columna "Comprometido $$" (índice 10)
                                    // Usamos {filter: 'applied'} para sumar solo lo que está visible tras filtrar
                                    var total = api.column(10, {
                                        filter: 'applied'
                                    }).data().reduce(function(a, b) {
                                        // Limpiamos los caracteres no numéricos ($ y puntos)
                                        var x = parseFloat(a.toString().replace(/\./g, '').replace('$', '')
                                            .trim()) || 0;
                                        var y = parseFloat(b.toString().replace(/\./g, '').replace('$', '')
                                            .trim()) || 0;
                                        return x + y;
                                    }, 0);

                                    // 2. Formatear el resultado como moneda chilena/puntos
                                    var totalFormateado = '$ ' + total.toLocaleString('es-CL');

                                    // 3. Actualizar el contenido de los elementos en los modales
                                    // Usamos un selector de atributo para encontrar todos los IDs que empiecen con ese nombre
                                    $('[id^="totalComprometidoFiltrado"]').text(totalFormateado);

                                    console.log("Suma calculada: " + totalFormateado); // Para depuración en consola
                                }
                            });

                            // Mover los botones al contenedor de la tabla
                            table.buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
                        });
                        //Example 2        
                        $(function() {
                            $("#example2").DataTable({
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
                                "ordering": false,

                            }).buttons().container().appendTo('#example2_wrapper .col-md-6:eq(0)');
                        });
                        //Example 3        
                        $(function() {
                            $("#example3").DataTable({
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
                                "ordering": false,

                            }).buttons().container().appendTo('#example3_wrapper .col-md-6:eq(0)');
                        });
                    </script>

                    <script @cspNonce>
                        $(document).ready(function() {
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
                                        $(this).unbind('submit').submit();
                                    }
                                });
                            });
                        });
                    </script>

                </div>
            </div>
        </div>
    </div>
@endsection
