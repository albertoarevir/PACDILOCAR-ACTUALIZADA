@extends('layouts.admin')

@section('content')
    {{-- ================================================================
     ESTILOS PROPIOS DE LA VISTA
     ================================================================ --}}
    <style @cspNonce>
        :root {
            --color-header-bg: #1a2744;
            --color-header-txt: #e8edf8;
            --color-accent: #2563eb;
            --color-accent-lt: #dbeafe;
            --color-row-alt: #f4f7fd;
            --color-border: #d1d9ee;
            --color-badge-ok: #16a34a;
            --color-badge-warn: #d97706;
            --color-badge-err: #dc2626;
            --color-badge-neu: #64748b;
        }

        /* ---- Barra de filtros ---- */
        .filtros-card {
            background: #fff;
            border: 1px solid var(--color-border);
            border-radius: 10px;
            padding: 1.1rem 1.4rem;
            margin-bottom: 1.4rem;
            box-shadow: 0 2px 8px rgba(37, 99, 235, .06);
        }

        .filtros-card label {
            font-size: .78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #475569;
            margin-bottom: .25rem;
            display: block;
        }

        .filtros-card .form-control,
        .filtros-card .form-select {
            font-size: .875rem;
            border-color: var(--color-border);
            border-radius: 6px;
        }

        .filtros-card .form-control:focus,
        .filtros-card .form-select:focus {
            border-color: var(--color-accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
        }

        .btn-filtrar {
            background: var(--color-accent);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: .42rem 1.1rem;
            font-size: .875rem;
            font-weight: 600;
            transition: background .2s;
        }

        .btn-filtrar:hover {
            background: #1d4ed8;
            color: #fff;
        }

        .btn-limpiar {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid var(--color-border);
            border-radius: 6px;
            padding: .42rem 1rem;
            font-size: .875rem;
            font-weight: 500;
            transition: background .2s;
        }

        .btn-limpiar:hover {
            background: #b2eb8d;
        }

        /* ---- Tabla ---- */
        .tabla-reporte-wrapper {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--color-border);
            box-shadow: 0 2px 12px rgba(37, 99, 235, .07);
        }

        .tabla-reporte {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem;
            background: #fff;
        }

        /* Cabecera con doble nivel */
        .tabla-reporte thead tr.grupo-header th {
            background: var(--color-header-bg);
            color: var(--color-header-txt);
            text-align: center;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: .55rem .6rem;
            border-right: 1px solid rgba(255, 255, 255, .1);
        }

        .tabla-reporte thead tr.col-header th {
            color: #e8f0e8;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .03em;
            text-transform: uppercase;
            padding: .45rem .5rem;
            border-right: 1px solid rgba(255, 255, 255, .12);
            white-space: nowrap;
        }

        /* Filas */
        .tabla-reporte tbody tr {
            border-bottom: 1px solid var(--color-border);
            transition: background .12s;
        }

        .tabla-reporte tbody tr:nth-child(even) {
            background: var(--color-row-alt);
        }

        .tabla-reporte tbody tr:hover {
            background: #fef9c3;
        }

        .tabla-reporte tbody td {
            padding: .48rem .55rem;
            vertical-align: middle;
            color: #1e293b;
            border-right: 1px solid #e8edf6;
        }

        .tabla-reporte tbody td:last-child {
            border-right: none;
        }

        /* Celdas especiales */
        .td-id {
            font-weight: 700;
            color: var(--color-accent);
            text-align: center;
        }

        .td-anio {
            text-align: center;
            font-weight: 600;
        }

        .td-num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .td-center {
            text-align: center;
        }

        .td-null {
            color: #94a3b8;
            font-style: italic;
            text-align: center;
            font-size: .75rem;
        }

        /* Badges de estado */
        .badge-estado {
            display: inline-block;
            padding: .22rem .6rem;
            border-radius: 20px;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .02em;
            white-space: nowrap;
        }

        .badge-verde {
            background: #dcfce7;
            color: var(--color-badge-ok);
        }

        .badge-amarillo {
            background: #fef9c3;
            color: var(--color-badge-warn);
        }

        .badge-rojo {
            background: #fee2e2;
            color: var(--color-badge-err);
        }

        .badge-gris {
            background: #f1f5f9;
            color: var(--color-badge-neu);
        }

        /* Sin resultados */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: .6rem;
            display: block;
        }

        /* Paginación */
        .pagination {
            justify-content: center;
            margin-top: 1rem;
        }

        .page-link {
            color: var(--color-accent);
            border-color: var(--color-border);
            font-size: .82rem;
        }

        .page-item.active .page-link {
            background: var(--color-accent);
            border-color: var(--color-accent);
        }

        /* Resumen rápido */
        .resumen-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            background: var(--color-accent-lt);
            color: var(--color-accent);
            font-size: .78rem;
            font-weight: 700;
            padding: .28rem .8rem;
            border-radius: 20px;
            margin-bottom: .8rem;
        }

        /* Responsive scroll */
        .scroll-x {
            overflow-x: auto;
        }

        /* Evita que el calculo JS de AdminLTE deje al content-wrapper mas bajo que la tabla o flotando a mitad de pantalla */
        .wrapper {
            min-height: 100vh !important;
            display: flex !important;
            flex-direction: column !important;
        }

        .content-wrapper {
            flex: 1 0 auto !important;
            height: auto !important;
            min-height: calc(100vh - 7.5rem) !important;
            padding-bottom: 1.5rem !important;
        }

        .main-footer {
            flex-shrink: 0;
        }
    </style>

    {{-- ================================================================
     TÍTULO
     ================================================================ --}}
    <div class="row" style="margin-left:40px; margin-bottom:.5rem;">
        <div class="col-12">
            <h1 style="font-size:24px; font-weight:700; color:#1a2744; margin:0;">
                <i class="bi bi-table me-2" style="color:var(--color-accent);"></i>
                Reporte de Ejecución Plan Anual de Compras
            </h1>
            <p style="font-size:13px; color:#64748b; margin:.2rem 0 0;">
                Visualización detallada por Proyecto, Licitación y Orden de Compra — Año {{ $selectedYear }}
            </p>
        </div>
    </div>
    <hr style="margin-left:40px; margin-right:20px;">

    <div style="margin-left:40px; margin-right:20px;">

        {{-- ============================================================
         FILTROS
         ============================================================ --}}
        <div class="filtros-card">
            <form method="GET" action="{{ route('reporte.index') }}" class="row g-2 align-items-end">
                @csrf

                {{-- Año --}}
                <div class="col-md-1 col-sm-6">
                    <label for="year"><i class="bi bi-calendar3 me-1"></i>Año</label>
                    <select name="year" id="year" class="form-control form-control-sm">
                        @foreach ($availableYears as $year)
                            <option value="{{ $year }}"
                                {{ (string) $year === (string) $selectedYear ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- ID Proyecto --}}
                <div class="col-md-1 col-sm-6">
                    <label for="id_proyecto"><i class="bi bi-hash me-1"></i>ID Proyecto</label>
                    <input type="number" name="id_proyecto" id="id_proyecto" class="form-control form-control-sm"
                        placeholder="Ej: 42" value="{{ $selectedIdProyecto ?? '' }}" min="1">
                </div>

                {{-- Departamento --}}
                <div class="col-md-2 col-sm-6">
                    <label for="departamento_id"><i class="bi bi-building me-1"></i>Departamento</label>
                    <select name="departamento_id" id="departamento_id" class="form-control form-control-sm">
                        <option value="">— Todos —</option>
                        @foreach ($departamentos as $dep)
                            <option value="{{ $dep->id }}"
                                {{ (string) $dep->id === (string) $selectedDepartamentoId ? 'selected' : '' }}>
                                {{ $dep->detalle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Ítem Presupuestario --}}
                <div class="col-md-1 col-sm-6">
                    <label for="item_presupuestario"><i class="bi bi-tag me-1"></i>Ítem Pres.</label>
                    <input type="text" name="item_presupuestario" id="item_presupuestario"
                        class="form-control form-control-sm"
                        placeholder="Ej: 22.04"
                        value="{{ $selectedItemPresupuestario ?? '' }}">
                </div>

                {{-- Estado Licitación --}}
                <div class="col-md-2 col-sm-6">
                    <label for="estado_licitacion_id"><i class="bi bi-file-earmark-text me-1"></i>Estado Licitación</label>
                    <select name="estado_licitacion_id" id="estado_licitacion_id" class="form-control form-control-sm">
                        <option value="">— Todos —</option>
                        @foreach ($estadosLicitacion as $est)
                            <option value="{{ $est->id }}"
                                {{ (string) $est->id === (string) $selectedEstadoLicitacionId ? 'selected' : '' }}>
                                {{ $est->detalle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Estado Orden de Compra --}}
                <div class="col-md-2 col-sm-6">
                    <label for="estado_compra_id"><i class="bi bi-cart-check me-1"></i>Estado Orden Compra</label>
                    <select name="estado_compra_id" id="estado_compra_id" class="form-control form-control-sm">
                        <option value="">— Todos —</option>
                        @foreach ($estadosCompra as $est)
                            <option value="{{ $est->id }}"
                                {{ (string) $est->id === (string) $selectedEstadoCompraId ? 'selected' : '' }}>
                                {{ $est->detalle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Búsqueda libre --}}
                <div class="col">
                    <label for="buscar"><i class="bi bi-search me-1"></i>Búsqueda libre</label>
                    <input type="text" name="buscar" id="buscar" class="form-control form-control-sm"
                        placeholder="Especies, Orden de Compras"
                        value="{{ request('buscar') }}">
                </div>

                {{-- Botones de Acción --}}
                <div class="col-auto d-flex gap-2 align-items-end flex-wrap">
                    <button type="submit" class="btn-filtrar">
                        <i class="bi bi-funnel-fill me-1"></i>Filtrar
                    </button>

                    <button type="submit" name="export" value="1" class="btn-excel"
                        style="background-color: #16a34a; color: white; border: none; padding: 6px 12px; border-radius: 6px;">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </button>

                    <a href="{{ route('reporte.index') }}" class="btn-limpiar">
                        <i class="bi bi-x-circle me-1"></i>Limpiar
                    </a>
                </div>

            </form>
        </div>

        {{-- Resumen de resultados --}}
        <div class="resumen-pill">
            <i class="bi bi-list-check"></i>
            {{ $reporte->total() }} {{ $reporte->total() === 1 ? 'registro' : 'registros' }} encontrados
        </div>

        {{-- ============================================================
         TABLA
         ============================================================ --}}
        <div class="tabla-reporte-wrapper scroll-x">
            <table class="tabla-reporte">
                <thead>
                    {{-- Fila 1: grupos de columnas con colores personalizados --}}
                    <tr class="grupo-header">
                        {{-- DATOS DEL PROYECTO: Verde Oscuro --}}
                        <th colspan="8" style="background-color: #064e3b; border-right:2px solid rgba(255,255,255,.25);">
                            <i class="bi bi-clipboard-data me-1"></i> Datos del Proyecto (PAC)
                        </th>

                        {{-- LICITACIÓN: Naranjo Oscuro --}}
                        <th colspan="4" style="background-color: #5e7216; border-right:2px solid rgb(10, 10, 10);">
                            <i class="bi bi-file-earmark-text me-1"></i> Licitación
                        </th>

                        {{-- ORDEN DE COMPRA: Amarillo Tenue (Texto oscuro para legibilidad) --}}
                        <th colspan="4" style="background-color: #8acafe; color: #854d0e;">
                            <i class="bi bi-cart-check me-1"></i> Orden de Compra
                        </th>
                    </tr>
                    {{-- Fila 2: columnas individuales con color del grupo --}}
                    <tr class="col-header">
                        {{-- GRUPO PAC: verde oscuro #064e3b --}}
                        <th style="background-color:#0a6b52;">ID Proyecto</th>
                        <th style="background-color:#0a6b52;">Año</th>
                        <th style="background-color:#0a6b52;">Departamento</th>
                        <th style="background-color:#0a6b52;">Ítem Presupuestario</th>
                        <th style="background-color:#0a6b52;">Especie</th>
                        <th style="background-color:#0a6b52; text-align:right;">Cantidad</th>
                        <th style="background-color:#0a6b52; text-align:right;">Presupuesto Inicial SAP</th>
                        <th style="background-color:#0a6b52; text-align:right; border-right:2px solid rgba(255,255,255,.3);">Saldo Disponible</th>

                        {{-- GRUPO LICITACIÓN: verde oliva #5e7216 --}}
                        <th style="background-color:#7a941e;">N° Licitación</th>
                        <th style="background-color:#7a941e;">Modalidad de Compra</th>
                        <th style="background-color:#7a941e;">Estado Licitación</th>
                        <th style="background-color:#7a941e; border-right:2px solid rgba(0,0,0,.3);">Últ. Actualización</th>

                        {{-- GRUPO ORDEN DE COMPRA: azul claro #8acafe --}}
                        <th style="background-color:#6ab8f7; color:#1e3a5f;">N° Orden Compra</th>
                        <th style="background-color:#6ab8f7; color:#1e3a5f; text-align:right;">Monto Compra</th>
                        <th style="background-color:#6ab8f7; color:#1e3a5f;">Estado Compra</th>
                        <th style="background-color:#6ab8f7; color:#1e3a5f;">Últ. Actualización</th>
                    </tr>
                </thead>

                <tbody>
                    @php
                        // Pre-calcular rowspan por id_proyecto Y por licitación (clave: proyecto+licitacion)
                        $rowspanMap    = [];   // cuántas filas tiene cada id_proyecto
                        $rowspanLic    = [];   // cuántas filas tiene cada combinación proyecto+licitacion
                        $renderedPac   = [];   // control: ya se renderizó el bloque PAC de este proyecto
                        $renderedLic   = [];   // control: ya se renderizó el bloque Licitación de esta clave

                        foreach ($reporte as $f) {
                            // Rowspan PAC
                            $rowspanMap[$f->id_proyecto] = ($rowspanMap[$f->id_proyecto] ?? 0) + 1;

                            // Rowspan Licitación — clave única por proyecto+numero_licitacion
                            $keyLic = $f->id_proyecto . '__' . ($f->numero_licitacion ?? 'NULL');
                            $rowspanLic[$keyLic] = ($rowspanLic[$keyLic] ?? 0) + 1;
                        }
                    @endphp

                    @forelse($reporte as $fila)
                        @php
                            // ---- Badges licitación ----
                            $estLic   = strtolower($fila->estado_licitacion ?? '');
                            $badgeLic = 'badge-gris';
                            if (str_contains($estLic, 'adjudic') || str_contains($estLic, 'publicad'))
                                $badgeLic = 'badge-verde';
                            elseif (str_contains($estLic, 'desert') || str_contains($estLic, 'suspendid'))
                                $badgeLic = 'badge-rojo';
                            elseif (str_contains($estLic, 'proceso') || str_contains($estLic, 'evaluaci'))
                                $badgeLic = 'badge-amarillo';

                            // ---- Badges compra ----
                            $estComp   = strtolower($fila->estado_compra ?? '');
                            $badgeComp = 'badge-gris';
                            if (str_contains($estComp, 'recibid') || str_contains($estComp, 'aceptad'))
                                $badgeComp = 'badge-verde';
                            elseif (str_contains($estComp, 'rechaz') || str_contains($estComp, 'cancel'))
                                $badgeComp = 'badge-rojo';
                            elseif (str_contains($estComp, 'pend') || str_contains($estComp, 'proceso'))
                                $badgeComp = 'badge-amarillo';

                            // ---- Control rowspan PAC ----
                            $esPrimeraFilaPac = !isset($renderedPac[$fila->id_proyecto]);
                            $spanPac          = $rowspanMap[$fila->id_proyecto] ?? 1;
                            if ($esPrimeraFilaPac) {
                                $renderedPac[$fila->id_proyecto] = true;
                            }

                            // ---- Control rowspan Licitación ----
                            $keyLic              = $fila->id_proyecto . '__' . ($fila->numero_licitacion ?? 'NULL');
                            $esPrimeraFilaLic    = !isset($renderedLic[$keyLic]);
                            $spanLic             = $rowspanLic[$keyLic] ?? 1;
                            if ($esPrimeraFilaLic) {
                                $renderedLic[$keyLic] = true;
                            }

                            // ---- Saldo ----
                            $saldo = (float)$fila->presupuesto_inicial_sap - (float)$fila->comprometido;
                        @endphp

                        <tr>
                            {{-- ======== GRUPO PAC: solo primera fila del proyecto ======== --}}
                            @if ($esPrimeraFilaPac)
                                <td class="td-id"
                                    rowspan="{{ $spanPac }}"
                                    style="vertical-align:middle; border-bottom:2px solid #c7d2e8;">
                                    {{ $fila->id_proyecto }}
                                </td>
                                <td class="td-anio"
                                    rowspan="{{ $spanPac }}"
                                    style="vertical-align:middle; border-bottom:2px solid #c7d2e8;">
                                    {{ $fila->anio }}
                                </td>
                                <td rowspan="{{ $spanPac }}"
                                    style="vertical-align:middle; border-bottom:2px solid #c7d2e8;">
                                    {{ $fila->departamento }}
                                </td>
                                <td rowspan="{{ $spanPac }}"
                                    style="vertical-align:middle; border-bottom:2px solid #c7d2e8;">
                                    {{ $fila->item_presupuestario ?? '—' }}
                                </td>
                                <td rowspan="{{ $spanPac }}"
                                    style="vertical-align:middle; border-bottom:2px solid #c7d2e8;">
                                    {{ $fila->especie ?? '—' }}
                                </td>
                                <td class="td-num"
                                    rowspan="{{ $spanPac }}"
                                    style="vertical-align:middle; border-bottom:2px solid #c7d2e8;">
                                    {{ number_format($fila->cantidad, 0, ',', '.') }}
                                </td>
                                <td class="td-num"
                                    rowspan="{{ $spanPac }}"
                                    style="vertical-align:middle; border-bottom:2px solid #c7d2e8;">
                                    $ {{ number_format($fila->presupuesto_inicial_sap, 0, ',', '.') }}
                                </td>
                                <td class="td-num"
                                    rowspan="{{ $spanPac }}"
                                    style="vertical-align:middle; border-right:2px solid #d1d9ee; border-bottom:2px solid #c7d2e8; font-weight:700; {{ $saldo < 0 ? 'color:#dc2626;' : 'color:#16a34a;' }}">
                                    $ {{ number_format($saldo, 0, ',', '.') }}
                                </td>
                            @endif
                            {{-- ============================================================ --}}

                            {{-- ======== GRUPO LICITACIÓN: solo primera fila de esa licitación ======== --}}
                            @if ($fila->numero_licitacion)
                                @if ($esPrimeraFilaLic)
                                    <td class="td-center"
                                        rowspan="{{ $spanLic }}"
                                        style="vertical-align:middle;">
                                        {{ $fila->numero_licitacion }}
                                    </td>
                                    <td rowspan="{{ $spanLic }}"
                                        style="vertical-align:middle;">
                                        {{ $fila->modalidad_compra }}
                                    </td>
                                    <td class="td-center"
                                        rowspan="{{ $spanLic }}"
                                        style="vertical-align:middle; border-right:2px solid #d1d9ee;">
                                        <span class="badge-estado {{ $badgeLic }}">
                                            {{ $fila->estado_licitacion ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="td-center"
                                        rowspan="{{ $spanLic }}"
                                        style="vertical-align:middle;">
                                        {{ $fila->fecha_actualizacion_licitacion
                                            ? \Carbon\Carbon::parse($fila->fecha_actualizacion_licitacion)->format('d/m/Y')
                                            : '—' }}
                                    </td>
                                @endif
                            @else
                                <td class="td-null" colspan="4" style="border-right:2px solid #d1d9ee;">
                                    Sin licitación asociada
                                </td>
                            @endif
                            {{-- ====================================================================== --}}

                            {{-- ======== ORDEN DE COMPRA: siempre una fila por orden ======== --}}
                            @if ($fila->numero_orden)
                                <td class="td-center">{{ $fila->numero_orden }}</td>
                                <td class="td-num">$ {{ number_format($fila->monto_compra, 0, ',', '.') }}</td>
                                <td class="td-center">
                                    <span class="badge-estado {{ $badgeLic }}">
                                        {{ $fila->estado_compra ?? '—' }}
                                    </span>
                                </td>
                                <td class="td-center">
                                    {{ $fila->fecha_actualizacion_compra
                                        ? \Carbon\Carbon::parse($fila->fecha_actualizacion_compra)->format('d/m/Y')
                                        : '—' }}
                                </td>
                            @else
                                <td class="td-null" colspan="4">Sin orden de compra</td>
                            @endif
                            {{-- ============================================================ --}}
                        </tr>
                    @empty
                        <tr>
                            <td colspan="16">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    No se encontraron registros con los filtros aplicados.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        <div class="mt-3">
            {{ $reporte->links() }}
        </div>

    </div>
@endsection
