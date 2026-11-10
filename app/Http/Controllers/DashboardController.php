<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Pac;
use App\Models\Modalidad;
use App\Models\Departamento;
use App\Models\Presupuesto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $departamentoIdUsuario = $user->departamento_id;

        // Obtener el departamento de Logística de manera robusta
        $direccionLogistica = Departamento::where('detalle', 'Direccion de Logistica')->first();
        $direccionLogisticaId = $direccionLogistica ? $direccionLogistica->id : null;
        $direccionLogisticaNombre = $direccionLogistica ? $direccionLogistica->detalle : 'Dirección de Logística'; // Fallback por si no se encuentra

        // --- LÓGICA DE FILTRO POR AÑO (Nuevo) ---
        $currentYear = now()->year;
        $availableYears = range($currentYear, $currentYear - 1); // Por ejemplo, el año actual y los 4 anteriores.

        // Obtener el año seleccionado del request o usar el actual por defecto
        $selectedYear = $request->input('year', $currentYear);

        // Usar el año seleccionado en lugar de $currentYear en todo el controlador
        $currentYear = $selectedYear;
        // --- FIN LÓGICA DE FILTRO POR AÑO ---


        // --- Lógica de filtrado centralizada ---
        $selectedDepartamentoId = $request->input('departamento_id');

        // Determinar qué departamentos estarán disponibles para el select
        $availableDepartmentsForSelect = collect();
        if ($departamentoIdUsuario === $direccionLogisticaId) {
            $availableDepartmentsForSelect = Departamento::where('detalle', '!=', $direccionLogisticaNombre)->get();
            $availableDepartmentsForSelect->prepend((object)['id' => '', 'detalle' => 'Todos los Departamentos']);
        } else {
            $availableDepartmentsForSelect = Departamento::where('id', $departamentoIdUsuario)->get();
            // Si el usuario normal no ha seleccionado un filtro, se auto-filtra por su departamento.
            if (is_null($selectedDepartamentoId) || $selectedDepartamentoId === '') {
                $selectedDepartamentoId = $departamentoIdUsuario;
            }
        }

        // Si se seleccionó "Todos los Departamentos" (valor vacío), establecer a null para el filtrado general
        if ($selectedDepartamentoId === '') {
            $selectedDepartamentoId = null;
        }

        // --- Función para aplicar filtros de departamento a las consultas ---
        $applyDepartamentoFilter = function ($query) use ($selectedDepartamentoId, $departamentoIdUsuario, $direccionLogisticaId, $direccionLogisticaNombre) {
            if ($selectedDepartamentoId) {
                // Si se seleccionó un departamento específico, filtrar por ese ID
                $query->whereHas('pac', function ($q) use ($selectedDepartamentoId) {
                    $q->where('departamento_id', $selectedDepartamentoId);
                });
            } elseif ($departamentoIdUsuario !== $direccionLogisticaId) {
                // Si no hay selección y el usuario NO es de Logística, filtrar por su departamento
                $query->whereHas('pac', function ($q) use ($departamentoIdUsuario) {
                    $q->where('departamento_id', $departamentoIdUsuario);
                });
            } else {
                // Si el usuario ES de Logística y no hay selección específica, excluir Logística
                $query->whereHas('pac.departamento', function ($q) use ($direccionLogisticaNombre) {
                    $q->where('detalle', '!=', $direccionLogisticaNombre);
                });
            }
        };

        // --- Preparar las consultas base y aplicar filtros ---
        $presupuestoBaseQuery = Presupuesto::where('year', $currentYear);
        $ordenBaseQuery = Orden::whereHas('pac', function ($q) use ($currentYear) {
            $q->where('year', $currentYear);
        })->whereDoesntHave('estadocompra', function ($q) {
            $q->whereRaw("LOWER(TRIM(detalle)) = 'orden de compra cancelada'");
        });
        $modalidadBaseQuery = Modalidad::whereHas('pac', function ($q) use ($currentYear) {
            $q->where('year', $currentYear);
        });

        // Aplicar el filtro de departamento a la consulta base de PAC
        $filteredPresupuestoQuery = clone $presupuestoBaseQuery;
        if ($selectedDepartamentoId) {
            $filteredPresupuestoQuery->where('departamento_id', $selectedDepartamentoId);
        } elseif ($departamentoIdUsuario !== $direccionLogisticaId) {
            $filteredPresupuestoQuery->where('departamento_id', $departamentoIdUsuario);
        } else {
            $filteredPresupuestoQuery->whereHas('departamento', function ($query) use ($direccionLogisticaNombre) {
                $query->where('detalle', '!=', $direccionLogisticaNombre);
            });
        }

        // Aplicar el filtro de departamento a las consultas base de Orden y Modalidad
        $filteredOrdenQuery = clone $ordenBaseQuery;
        $applyDepartamentoFilter($filteredOrdenQuery);

        $filteredModalidadQuery = clone $modalidadBaseQuery;
        $applyDepartamentoFilter($filteredModalidadQuery);

        // --- Métricas Generales ---
        $total_presupuesto_general = $filteredPresupuestoQuery->sum('monto');
        $total_comprometido_general = $filteredOrdenQuery->sum('monto');
        $total_porcentaje_ejecucion_general = $total_presupuesto_general > 0 ? round(($total_comprometido_general / $total_presupuesto_general) * 100, 2) : 0;

        // --- Datos por Departamento para la tabla inferior (N+1 optimizado) ---
        $departamentosParaTablaQuery = Departamento::query();
        if ($selectedDepartamentoId) {
            $departamentosParaTablaQuery->where('id', $selectedDepartamentoId);
        } elseif ($departamentoIdUsuario !== $direccionLogisticaId) {
            $departamentosParaTablaQuery->where('id', $departamentoIdUsuario);
        } else {
            $departamentosParaTablaQuery->where('detalle', '!=', $direccionLogisticaNombre);
        }
        $departamentosParaTabla = $departamentosParaTablaQuery->get();


        // DATOS PARA LA TABLA: También usamos 'monto' aquí
        $presupuestoData = Presupuesto::select('departamento_id', DB::raw('SUM(monto) as total_presupuesto'))
            ->where('year', $currentYear)
            ->groupBy('departamento_id');
        $pacData = Pac::select('departamento_id', DB::raw('SUM(presupuesto) as total_presupuesto'))
            ->where('year', $currentYear);

        // CORRECCIÓN: Usar getQualifiedForeignKeyName() para el join robusto
        $ordenData = Orden::select('pacs.departamento_id', DB::raw('SUM(ordens.monto) as total_comprometido'))
            ->join('pacs', (new Orden)->pac()->getQualifiedForeignKeyName(), '=', 'pacs.id')
            ->leftJoin('estado_compras', 'ordens.estado_id', '=', 'estado_compras.id')
            ->where('pacs.year', $currentYear)
            ->whereRaw("COALESCE(LOWER(TRIM(estado_compras.detalle)), '') != 'orden de compra cancelada'");

        if ($selectedDepartamentoId) {
            $pacData->where('departamento_id', $selectedDepartamentoId);
            // El filtro para ordenData ya está dentro del join
            $ordenData->whereHas('pac', function ($q) use ($selectedDepartamentoId) {
                $q->where('departamento_id', $selectedDepartamentoId);
            });
        } elseif ($departamentoIdUsuario !== $direccionLogisticaId) {
            $pacData->where('departamento_id', $departamentoIdUsuario);
            // El filtro para ordenData ya está dentro del join
            $ordenData->whereHas('pac', function ($q) use ($departamentoIdUsuario) {
                $q->where('departamento_id', $departamentoIdUsuario);
            });
        } else {
            $pacData->whereHas('departamento', function ($q) use ($direccionLogisticaNombre) {
                $q->where('detalle', '!=', $direccionLogisticaNombre);
            });
            // El filtro para ordenData ya está dentro del join
            $ordenData->whereHas('pac.departamento', function ($q) use ($direccionLogisticaNombre) {
                $q->where('detalle', '!=', $direccionLogisticaNombre);
            });
        }

        $pacData = $pacData->groupBy('departamento_id')->get()->keyBy('departamento_id');
        $ordenData = $ordenData->groupBy('departamento_id')->get()->keyBy('departamento_id');

        // Construir $datos_departamentos excluyendo siempre la Dirección de Logística
        $datos_departamentos = [];

        // Determinar qué departamentos iterar según el filtro activo
        if ($selectedDepartamentoId) {
            $departamentosLoop = Departamento::where('id', $selectedDepartamentoId)
                ->where('detalle', '!=', $direccionLogisticaNombre)
                ->get();
        } else {
            $departamentosLoop = Departamento::where('detalle', '!=', $direccionLogisticaNombre)
                ->orderBy('detalle')
                ->get();
        }

        foreach ($departamentosLoop as $depto) {
            // Presupuesto del departamento para el año seleccionado
            $presupuestoMonto = Presupuesto::where('departamento_id', $depto->id)
                ->where('year', (int) $selectedYear)
                ->sum('monto');

            // Comprometido: idéntico al cálculo de ReporteDashboardController ($dato['comprometido']), excluyendo Orden de Compra Cancelada
            $comprometido = DB::table('ordens')
                ->join('pacs', 'ordens.id_proyecto', '=', 'pacs.id')
                ->leftJoin('estado_compras', 'ordens.estado_id', '=', 'estado_compras.id')
                ->where('pacs.departamento_id', $depto->id)
                ->where('pacs.year', (int) $selectedYear)
                ->whereRaw("COALESCE(LOWER(TRIM(estado_compras.detalle)), '') != 'orden de compra cancelada'")
                ->sum('ordens.monto');

            $porcentaje = $presupuestoMonto > 0 ? ($comprometido / $presupuestoMonto) * 100 : 0;

            $datos_departamentos[] = [
                'departamento' => $depto,
                'presupuesto'  => (float) $presupuestoMonto,
                'comprometido' => (float) $comprometido,
                'porcentaje'   => round($porcentaje, 2),
            ];
        }

        // Unificar métricas generales con el total de $datos_departamentos (igual que en reporte/dashboard.blade.php)
        $total_presupuesto_general = collect($datos_departamentos)->sum('presupuesto');
        $total_comprometido_general = collect($datos_departamentos)->sum('comprometido');
        $total_porcentaje_ejecucion_general = $total_presupuesto_general > 0
            ? round(($total_comprometido_general / $total_presupuesto_general) * 100, 2)
            : 0;

        // --- Gráfico de Dona: Estados de Compras ---
        $estadosCompras = Orden::query()
            ->whereYear('fecha_seguimiento', $currentYear)
            ->select('estado_compras.detalle as estado', DB::raw('COUNT(ordens.id) as cantidad'))
            ->join('estado_compras', 'ordens.estado_id', '=', 'estado_compras.id');

        // Aplicar el mismo filtro centralizado a la consulta de estados de compras
        $applyDepartamentoFilter($estadosCompras);

        $estadosCompras = $estadosCompras
            ->groupBy('estado_compras.detalle')
            ->orderBy('estado_compras.detalle')
            ->get();

        $chartLabels = $estadosCompras->pluck('estado')->toArray();
        $chartData = $estadosCompras->pluck('cantidad')->toArray();

        // --- Gráfico de Barras Horizontales: Estados de Licitaciones ---
        $estadosLicitaciones = Modalidad::query()
            ->select('estado_licitacions.detalle as estado_licitacion', DB::raw('COUNT(modalidads.id) as cantidad_licitaciones'))
            ->join('estado_licitacions', 'modalidads.estado_id', '=', 'estado_licitacions.id')
            ->whereHas('pac', function ($query) use ($currentYear) {
                $query->where('year', $currentYear);
            });

        // Aplicar el mismo filtro centralizado a la consulta de estados de licitaciones
        $applyDepartamentoFilter($estadosLicitaciones);

        $estadosLicitaciones = $estadosLicitaciones
            ->groupBy('estado_licitacions.detalle')
            ->orderBy('estado_licitacions.detalle')
            ->get();

        $chartLicitacionLabels = $estadosLicitaciones->pluck('estado_licitacion')->toArray();
        $chartLicitacionData = $estadosLicitaciones->pluck('cantidad_licitaciones')->toArray();

        // --- Total de Proyectos Registrados y Última Fecha/Departamento de Actualización ---
        $totalProyectosQuery = Pac::where('year', $currentYear);
        $ultimaActualizacionPacQuery = Pac::where('year', $currentYear)->with('departamento')->latest('updated_at');

        if ($selectedDepartamentoId) {
            $totalProyectosQuery->where('departamento_id', $selectedDepartamentoId);
            $ultimaActualizacionPacQuery->where('departamento_id', $selectedDepartamentoId);
        } elseif ($departamentoIdUsuario !== $direccionLogisticaId) {
            $totalProyectosQuery->where('departamento_id', $departamentoIdUsuario);
            $ultimaActualizacionPacQuery->where('departamento_id', $departamentoIdUsuario);
        } else {
            $totalProyectosQuery->whereHas('departamento', function ($q) use ($direccionLogisticaNombre) {
                $q->where('detalle', '!=', $direccionLogisticaNombre);
            });
            $ultimaActualizacionPacQuery->whereHas('departamento', function ($q) use ($direccionLogisticaNombre) {
                $q->where('detalle', '!=', $direccionLogisticaNombre);
            });
        }
        $total_proyectos_registrados = $totalProyectosQuery->count();
        $ultima_actualizacion_pac = $ultimaActualizacionPacQuery->first();

        // --- Total de Licitaciones Registradas y Última Actualización ---
        $totalLicitacionesQuery = Modalidad::whereHas('pac', function ($query) use ($currentYear) {
            $query->where('year', $currentYear);
        });
        $ultimaActualizacionLicitacionQuery = Modalidad::whereHas('pac', function ($query) use ($currentYear) {
            $query->where('year', $currentYear);
        })->with('pac.departamento')->latest('updated_at');

        $applyDepartamentoFilter($totalLicitacionesQuery);
        $applyDepartamentoFilter($ultimaActualizacionLicitacionQuery);

        $total_licitaciones_registradas = $totalLicitacionesQuery->count();
        $ultima_actualizacion_licitacion = $ultimaActualizacionLicitacionQuery->first();

        // --- Total de Órdenes de Compras Registradas ---
        $totalOrdenesRegistradasQuery = Orden::whereYear('fecha_seguimiento', $currentYear);
        $applyDepartamentoFilter($totalOrdenesRegistradasQuery);
        $total_ordenes_registradas = $totalOrdenesRegistradasQuery->count();

        // --- Última Fecha/Departamento de Actualización de Órdenes de Compra ---
        $ultimaActualizacionOrdenQuery = Orden::whereYear('fecha_seguimiento', $currentYear)
            ->with('pac.departamento')
            ->latest('updated_at');

        $applyDepartamentoFilter($ultimaActualizacionOrdenQuery);

        $ultima_actualizacion_orden = $ultimaActualizacionOrdenQuery->first();

        // --- Total de Licitaciones Sin Órdenes de Compra (Optimizado) ---
        $licitacionesSinOrdenesQuery = Modalidad::whereHas('pac', function ($query) use ($currentYear) {
            $query->where('year', $currentYear);
        });

        // Aplicar el filtro de departamento
        $applyDepartamentoFilter($licitacionesSinOrdenesQuery);

        // Subconsulta para obtener las licitaciones que SÍ tienen órdenes de compra
        $licitacionIdsWithOrdersSubquery = Orden::select('id_licitacion')
            ->whereYear('fecha_seguimiento', $currentYear);

        // El filtro de departamento para la subconsulta
        if ($selectedDepartamentoId) {
            $licitacionIdsWithOrdersSubquery->whereHas('pac', function ($q) use ($selectedDepartamentoId) {
                $q->where('departamento_id', $selectedDepartamentoId);
            });
        } elseif ($departamentoIdUsuario !== $direccionLogisticaId) {
            $licitacionIdsWithOrdersSubquery->whereHas('pac', function ($q) use ($departamentoIdUsuario) {
                $q->where('departamento_id', $departamentoIdUsuario);
            });
        } else {
            $licitacionIdsWithOrdersSubquery->whereHas('pac.departamento', function ($q) use ($direccionLogisticaNombre) {
                $q->where('detalle', '!=', $direccionLogisticaNombre);
            });
        }

        $total_licitaciones_sin_ordenes = $licitacionesSinOrdenesQuery
            ->whereNotIn('id', $licitacionIdsWithOrdersSubquery)
            ->count();


        // --- Gráfico de Proyectos por Departamento (ahora incluye los departamentos con 0 proyectos) ---
        $proyectosPorDepartamentoQuery = Departamento::query()
            ->select(
                'departamentos.detalle as departamento_nombre',
                DB::raw('COUNT(pacs.id) as cantidad_proyectos')
            )
            ->leftJoin('pacs', function ($join) use ($currentYear) {
                $join->on('departamentos.id', '=', 'pacs.departamento_id')
                    ->where('pacs.year', $currentYear);
            });

        if ($selectedDepartamentoId) {
            $proyectosPorDepartamentoQuery->where('departamentos.id', $selectedDepartamentoId);
        } elseif ($departamentoIdUsuario !== $direccionLogisticaId) {
            $proyectosPorDepartamentoQuery->where('departamentos.id', $departamentoIdUsuario);
        } else {
            $proyectosPorDepartamentoQuery->where('departamentos.detalle', '!=', $direccionLogisticaNombre);
        }

        $proyectosPorDepartamento = $proyectosPorDepartamentoQuery
            ->groupBy('departamentos.detalle')
            ->orderBy('departamentos.detalle')
            ->get();

        $chartProyectosLabels = $proyectosPorDepartamento->pluck('departamento_nombre')->toArray();
        $chartProyectosData = $proyectosPorDepartamento->pluck('cantidad_proyectos')->toArray();

        return view('dashboard.index', compact(
            'total_presupuesto_general',
            'total_comprometido_general',
            'total_porcentaje_ejecucion_general',
            'datos_departamentos',
            'chartLabels',
            'chartData',
            'chartLicitacionLabels',
            'chartLicitacionData',
            'total_proyectos_registrados',
            'total_licitaciones_registradas',
            'total_ordenes_registradas',
            'total_licitaciones_sin_ordenes',
            'chartProyectosLabels',
            'chartProyectosData',
            'ultima_actualizacion_pac',
            'ultima_actualizacion_licitacion',
            'ultima_actualizacion_orden',
            'availableDepartmentsForSelect',
            'selectedDepartamentoId',
            'availableYears', // <-- ¡Nueva variable agregada!
            'selectedYear'    // <-- ¡Nueva variable agregada!
        ));
    }
}
