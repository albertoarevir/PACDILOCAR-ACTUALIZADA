<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pac;
use App\Models\Orden;
use App\Models\Modalidad;
use App\Models\Departamento;
use Illuminate\Support\Facades\DB;

class ReporteDashboardController extends Controller
{
    private const VALOR_UTM  = 68000;
    private const UMBRAL_UTM = 100;

    public function index(Request $request)
    {
        $currentYear    = now()->year;
        $availableYears = range($currentYear, $currentYear - 4);
        $selectedYear   = (int) $request->input('year', $currentYear);
        if ($selectedYear < $currentYear - 4 || $selectedYear > $currentYear) {
            $selectedYear = $currentYear;
        }

        // Admin: tiene rol ADMINISTRADOR o pertenece al depto 7 (Dirección de Logística)
        $user    = auth()->user();
        $isAdmin = $user->hasRole('ADMINISTRADOR') || (int)$user->departamento_id === 7;

        $departamentos = $isAdmin
            ? Departamento::orderBy('detalle')->get()
            : collect();

        if ($isAdmin) {
            $selectedDepartamentoId = $request->input('departamento_id');
        } else {
            // No-admin: forzar el depto del usuario.
            // Si no tiene depto asignado, -1 = no matchea nada (dashboard vací­o, no todo abierto).
            $selectedDepartamentoId = $user->departamento_id ?? -1;
        }

        $valorUTM            = self::VALOR_UTM;
        $umbralFragmentacion = self::VALOR_UTM * self::UMBRAL_UTM;

        $presupuestoPorDepto = DB::table('departamentos')
            ->select(
                'departamentos.id',
                'departamentos.detalle as departamento',
                DB::raw('COALESCE(SUM(presupuestos.monto), 0) as total_presupuesto'),
                DB::raw("COALESCE((
                    SELECT SUM(o.monto)
                    FROM ordens o
                    INNER JOIN pacs p ON o.id_proyecto = p.id
                    LEFT JOIN estado_compras ec ON o.estado_id = ec.id
                    WHERE p.departamento_id = departamentos.id
                      AND p.year = " . (int)$selectedYear . "
                      AND COALESCE(LOWER(TRIM(ec.detalle)), '') != 'orden de compra cancelada'
                ), 0) as total_comprometido")
            )
            ->leftJoin('presupuestos', function ($join) use ($selectedYear) {
                $join->on('presupuestos.departamento_id', '=', 'departamentos.id')
                     ->where('presupuestos.year', '=', $selectedYear);
            })
            ->where('departamentos.detalle', '!=', 'Direccion de Logistica')
            ->when($selectedDepartamentoId, fn($q) => $q->where('departamentos.id', $selectedDepartamentoId))
            ->groupBy('departamentos.id', 'departamentos.detalle')
            ->orderBy('departamentos.detalle')
            ->get();

        $chartBarLabels       = $presupuestoPorDepto->pluck('departamento')->toArray();
        $chartBarPresupuesto  = $presupuestoPorDepto->map(fn($r) => (float)$r->total_presupuesto)->toArray();
        $chartBarComprometido = $presupuestoPorDepto->map(fn($r) => (float)$r->total_comprometido)->toArray();

        $tablaDepartamentos = $presupuestoPorDepto->map(function ($row) {
            $presupuesto  = (float) $row->total_presupuesto;
            $comprometido = (float) $row->total_comprometido;
            $porcentaje   = $presupuesto > 0 ? round(($comprometido / $presupuesto) * 100, 2) : 0;
            $saldo        = $presupuesto - $comprometido;
            return [
                'departamento' => $row->departamento,
                'presupuesto'  => $presupuesto,
                'comprometido' => $comprometido,
                'saldo'        => $saldo,
                'porcentaje'   => $porcentaje,
            ];
        });

        $totalPresupuesto  = $tablaDepartamentos->sum('presupuesto');
        $totalComprometido = $tablaDepartamentos->sum('comprometido');
        $totalSaldo        = $totalPresupuesto - $totalComprometido;
        $totalPorcentaje   = $totalPresupuesto > 0
            ? round(($totalComprometido / $totalPresupuesto) * 100, 2) : 0;

        $estadosLicitacionesQuery = DB::table('modalidads')
            ->select('estado_licitacions.detalle as estado', DB::raw('COUNT(modalidads.id) as cantidad'))
            ->join('estado_licitacions', 'modalidads.estado_id', '=', 'estado_licitacions.id')
            ->join('pacs', 'modalidads.id_proyecto', '=', 'pacs.id')
            ->where('pacs.year', $selectedYear)
            ->where('pacs.departamento_id', '!=', function ($q) {
                $q->select('id')->from('departamentos')
                  ->where('detalle', 'Direccion de Logistica')->limit(1);
            });

        if ($selectedDepartamentoId) {
            $estadosLicitacionesQuery->where('pacs.departamento_id', $selectedDepartamentoId);
        }

        $estadosLicitaciones = $estadosLicitacionesQuery->groupBy('estado_licitacions.detalle')->get();
        $chartDonaLicLabels  = $estadosLicitaciones->pluck('estado')->toArray();
        $chartDonaLicData    = $estadosLicitaciones->pluck('cantidad')->toArray();

        $estadosOrdenesQuery = DB::table('ordens')
            ->select('estado_compras.detalle as estado', DB::raw('COUNT(ordens.id) as cantidad'))
            ->join('estado_compras', 'ordens.estado_id', '=', 'estado_compras.id')
            ->join('pacs', 'ordens.id_proyecto', '=', 'pacs.id')
            ->whereYear('ordens.fecha_seguimiento', $selectedYear)
            ->where('pacs.departamento_id', '!=', function ($q) {
                $q->select('id')->from('departamentos')
                  ->where('detalle', 'Direccion de Logistica')->limit(1);
            });

        if ($selectedDepartamentoId) {
            $estadosOrdenesQuery->where('pacs.departamento_id', $selectedDepartamentoId);
        }

        $estadosOrdenes    = $estadosOrdenesQuery->groupBy('estado_compras.detalle')->get();
        $chartDonaOcLabels = $estadosOrdenes->pluck('estado')->toArray();
        $chartDonaOcData   = $estadosOrdenes->pluck('cantidad')->toArray();

        $totalProyectos = Pac::where('year', $selectedYear)
            ->when($selectedDepartamentoId, fn($q) => $q->where('departamento_id', $selectedDepartamentoId))
            ->count();

        $totalLicitaciones = Modalidad::whereHas('pac', fn($q) => $q->where('year', $selectedYear))
            ->when($selectedDepartamentoId, fn($q) => $q->whereHas('pac', fn($q2) =>
                $q2->where('departamento_id', $selectedDepartamentoId)))
            ->count();

        $totalOrdenes = Orden::whereYear('fecha_seguimiento', $selectedYear)
            ->when($selectedDepartamentoId, fn($q) => $q->whereHas('pac', fn($q2) =>
                $q2->where('departamento_id', $selectedDepartamentoId)))
            ->count();

        //  ALERTAS 

        $proyectosSinLicitacion = DB::table('pacs')
            ->leftJoin('modalidads', 'modalidads.id_proyecto', '=', 'pacs.id')
            ->where('pacs.year', $selectedYear)
            ->whereNull('modalidads.id')
            ->when($selectedDepartamentoId, fn($q) => $q->where('pacs.departamento_id', $selectedDepartamentoId))
            ->count();

        $proyectosSinOC = DB::table('pacs')
            ->leftJoin('ordens', 'ordens.id_proyecto', '=', 'pacs.id')
            ->where('pacs.year', $selectedYear)
            ->whereNull('ordens.id')
            ->when($selectedDepartamentoId, fn($q) => $q->where('pacs.departamento_id', $selectedDepartamentoId))
            ->count();

        $proyectosSaldoNegativo = DB::table('pacs')
            ->where('pacs.year', $selectedYear)
            ->whereRaw('
                COALESCE((
                    SELECT SUM(pr.monto) FROM presupuestos pr
                    WHERE pr.departamento_id = pacs.departamento_id
                      AND pr.year = pacs.year
                      AND pr.item = pacs.codigo
                ), 0)
                <
                COALESCE((
                    SELECT SUM(o.monto)
                    FROM ordens o
                    LEFT JOIN estado_compras ec ON o.estado_id = ec.id
                    WHERE o.id_proyecto = pacs.id
                      AND COALESCE(LOWER(TRIM(ec.detalle)), \'\') != \'orden de compra cancelada\'
                ), 0)
            ')
            ->when($selectedDepartamentoId, fn($q) => $q->where('departamento_id', $selectedDepartamentoId))
            ->count();

        $licitacionesProblematicas = DB::table('modalidads')
            ->join('estado_licitacions', 'modalidads.estado_id', '=', 'estado_licitacions.id')
            ->join('pacs', 'modalidads.id_proyecto', '=', 'pacs.id')
            ->where('pacs.year', $selectedYear)
            ->whereRaw("LOWER(estado_licitacions.detalle) IN (?, ?, ?, ?)", [
                'desierta', 'suspendida', 'revocada', 'proyecto no ejecutado',
            ])
            ->when($selectedDepartamentoId, fn($q) => $q->where('pacs.departamento_id', $selectedDepartamentoId))
            ->count();

        // MOTOR DE DETECCIÓN DE FRAGMENTACIÓN

        // Reglas configurables del motor (parametrizables)
        $reglasFragmentacion = [
            'alto'  => ['cantidad_oc' => 5,  'dispersion_dias' => 45, 'monto_min' => $umbralFragmentacion],
            'medio' => ['cantidad_oc' => 2,  'dispersion_dias' => 90, 'monto_min' => $umbralFragmentacion * 0.5],
        ];

        // Query base: pre-agrega OCs en subquery para evitar duplicación por modalidades
        // (un proyecto puede tener múltiples modalidades → JOIN directo multiplicaría las OCs)
        $rawFrag = DB::table('pacs')
            ->select([
                'pacs.id as id_proyecto',
                'departamentos.detalle as departamento',
                DB::raw("COALESCE(especies.detalle, 'Sin especie') as especie"),
                'pacs.codigo as item_presupuestario',
                DB::raw("COALESCE(string_agg(DISTINCT modalidads.modalidad, ', '), '—') as modalidad"),
                'oc_sub.cantidad_oc',
                'oc_sub.monto_total',
                'oc_sub.promedio_oc',
                'oc_sub.dispersion_dias',
                'oc_sub.primera_oc',
                'oc_sub.ultima_oc',
                'oc_sub.mes_inicio',
            ])
            ->join('departamentos', 'pacs.departamento_id', '=', 'departamentos.id')
            ->leftJoin('especies', 'pacs.especie_id', '=', 'especies.id')
            ->leftJoin('modalidads', 'modalidads.id_proyecto', '=', 'pacs.id')
            ->join(DB::raw("(
                SELECT
                    id_proyecto,
                    COUNT(id)::int                                                                       AS cantidad_oc,
                    COALESCE(SUM(monto), 0)                                                             AS monto_total,
                    COALESCE(AVG(monto), 0)                                                             AS promedio_oc,
                    GREATEST(COALESCE((MAX(fecha_seguimiento)::date - MIN(fecha_seguimiento)::date)::numeric, 0), 0) AS dispersion_dias,
                    MIN(fecha_seguimiento)                                                               AS primera_oc,
                    MAX(fecha_seguimiento)                                                               AS ultima_oc,
                    COALESCE(EXTRACT(MONTH FROM MIN(fecha_seguimiento))::int, 0)                        AS mes_inicio
                FROM ordens
                WHERE fecha_seguimiento IS NOT NULL
                GROUP BY id_proyecto
                HAVING COUNT(id) > 1
            ) oc_sub"), 'oc_sub.id_proyecto', '=', 'pacs.id')
            ->where('pacs.year', $selectedYear)
            ->when($selectedDepartamentoId, fn($q) => $q->where('pacs.departamento_id', $selectedDepartamentoId))
            ->groupBy(
                'pacs.id', 'departamentos.detalle', 'especies.detalle', 'pacs.codigo',
                'oc_sub.cantidad_oc', 'oc_sub.monto_total', 'oc_sub.promedio_oc',
                'oc_sub.dispersion_dias', 'oc_sub.primera_oc', 'oc_sub.ultima_oc', 'oc_sub.mes_inicio'
            )
            ->get();

        // Motor de scoring multidimensional 0-100
        $casosFrag = $rawFrag->map(function ($item) use ($umbralFragmentacion, $reglasFragmentacion) {
            $item->cantidad_oc     = (int)$item->cantidad_oc;
            $item->monto_total     = (float)$item->monto_total;
            $item->promedio_oc     = (float)$item->promedio_oc;
            $item->dispersion_dias = (float)$item->dispersion_dias;

            $score = 0;

            // Dimensión 1 Frecuencia de OC (peso 35 pts)
            $score += match(true) {
                $item->cantidad_oc >= 10 => 35,
                $item->cantidad_oc >= 7  => 28,
                $item->cantidad_oc >= 5  => 22,
                $item->cantidad_oc >= 3  => 12,
                default                  => 5,
            };

            // Dimensión 2 Monto relativo al umbral 100 UTM (peso 35 pts)
            $ratio = $umbralFragmentacion > 0 ? $item->monto_total / $umbralFragmentacion : 0;
            $score += match(true) {
                $ratio >= 3.0 => 35,
                $ratio >= 2.0 => 28,
                $ratio >= 1.0 => 20,
                $ratio >= 0.5 => 10,
                default       => 3,
            };

            // Dimensión 3 Temporalidad (peso 20 pts, menor dispersión = mayor riesgo)
            $score += match(true) {
                $item->dispersion_dias <= 7  => 20,
                $item->dispersion_dias <= 15 => 17,
                $item->dispersion_dias <= 30 => 12,
                $item->dispersion_dias <= 45 => 7,
                $item->dispersion_dias <= 90 => 3,
                default                      => 0,
            };

            // Dimensión Modalidad de menor cuantí­a (peso 10 pts)
            $item->modalidad_baja = (bool)preg_match('/trato directo|compra.?agil|compra directa/i', $item->modalidad);
            $score += $item->modalidad_baja ? 10 : 0;

            $item->score_riesgo = min(100, $score);

            // Clasificación por reglas parametrizables
            $r = $reglasFragmentacion;
            $esAlto = $item->cantidad_oc    >= $r['alto']['cantidad_oc']
                   && $item->dispersion_dias <= $r['alto']['dispersion_dias']
                   && $item->monto_total     >= $r['alto']['monto_min'];

            $esMedio = !$esAlto && (
                $item->cantidad_oc >= $r['medio']['cantidad_oc']
                && ($item->dispersion_dias <= $r['medio']['dispersion_dias']
                    || $item->monto_total  >= $r['medio']['monto_min'])
            );

            $item->nivel_riesgo = $esAlto ? 'ALTO' : ($esMedio ? 'MEDIO' : 'BAJO');
            return $item;
        })->sortByDesc('score_riesgo')->values();

        // KPIs del módulo
        $kpiTotalOC          = $casosFrag->sum('cantidad_oc');
        $kpiCasosSospechosos = $casosFrag->whereIn('nivel_riesgo', ['ALTO', 'MEDIO'])->count();
        $kpiRiesgoAlto       = $casosFrag->where('nivel_riesgo', 'ALTO')->count();
        $kpiMontoFragmentado = $casosFrag->whereIn('nivel_riesgo', ['ALTO', 'MEDIO'])->sum('monto_total');

        // Chart: Top 10 especies por score
        $chartEspeciesRiesgo = $casosFrag
            ->groupBy('especie')
            ->map(fn($g, $k) => (object)[
                'especie' => $k,
                'score'   => $g->max('score_riesgo'),
                'cantidad'=> $g->sum('cantidad_oc'),
                'monto'   => $g->sum('monto_total'),
                'nivel'   => $g->sortByDesc('score_riesgo')->first()->nivel_riesgo,
            ])
            ->sortByDesc('score')
            ->take(10)
            ->values();

        $chartEspecieLabels  = $chartEspeciesRiesgo->pluck('especie')->toArray();
        $chartEspecieScores  = $chartEspeciesRiesgo->pluck('score')->toArray();
        $chartEspecieMontos  = $chartEspeciesRiesgo->pluck('monto')->toArray();
        $chartEspecieNiveles = $chartEspeciesRiesgo->pluck('nivel')->toArray();

        // Chart: Riesgo por departamento
        $chartRiesgoDept = $casosFrag
            ->groupBy('departamento')
            ->map(fn($g, $k) => (object)[
                'departamento' => $k,
                'alto'  => $g->where('nivel_riesgo', 'ALTO')->count(),
                'medio' => $g->where('nivel_riesgo', 'MEDIO')->count(),
                'bajo'  => $g->where('nivel_riesgo', 'BAJO')->count(),
            ])
            ->sortByDesc('alto')
            ->take(12)
            ->values();

        $chartDeptLabels = $chartRiesgoDept->pluck('departamento')->toArray();
        $chartDeptAlto   = $chartRiesgoDept->pluck('alto')->toArray();
        $chartDeptMedio  = $chartRiesgoDept->pluck('medio')->toArray();
        $chartDeptBajo   = $chartRiesgoDept->pluck('bajo')->toArray();

        // Chart: Evolución temporal mensual
        $evolucionMeses = $casosFrag
            ->filter(fn($r) => $r->mes_inicio > 0)
            ->groupBy('mes_inicio')
            ->map(fn($g, $m) => ['mes' => (int)$m, 'casos' => $g->count(), 'monto' => $g->sum('monto_total')])
            ->keyBy('mes');

        $chartEvolucionLabels = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        $chartEvolucionCasos  = [];
        $chartEvolucionMontos = [];
        for ($m = 1; $m <= 12; $m++) {
            $d = $evolucionMeses->get($m);
            $chartEvolucionCasos[]  = $d ? (int)$d['casos']   : 0;
            $chartEvolucionMontos[] = $d ? (float)$d['monto']  : 0;
        }

        //Chart: Modalidades en casos sospechosos
        $chartModalidadFrag = $casosFrag
            ->whereIn('nivel_riesgo', ['ALTO', 'MEDIO'])
            ->groupBy('modalidad')
            ->map(fn($g, $k) => (object)['modalidad' => $k, 'casos' => $g->count()])
            ->sortByDesc('casos')
            ->values();

        $chartFragModLabels = $chartModalidadFrag->pluck('modalidad')->toArray();
        $chartFragModData   = $chartModalidadFrag->pluck('casos')->toArray();

        // Heatmap: Especie — Departamento
        $heatmapEspecies = $casosFrag->pluck('especie')->unique()->take(8)->values()->toArray();
        $heatmapDeptos   = $casosFrag->pluck('departamento')->unique()->take(8)->values()->toArray();
        $heatmapMatrix   = [];
        foreach ($heatmapEspecies as $esp) {
            $row = [];
            foreach ($heatmapDeptos as $dep) {
                $match = $casosFrag->where('especie', $esp)->where('departamento', $dep);
                $row[] = [
                    'oc'    => $match->sum('cantidad_oc'),
                    'monto' => $match->sum('monto_total'),
                    'score' => $match->isNotEmpty() ? (int)$match->max('score_riesgo') : 0,
                ];
            }
            $heatmapMatrix[] = $row;
        }

        // Compatibilidad con variables anteriores referenciadas en vista
        $sospechaNFragmentacion  = $casosFrag->take(20);
        $fragmentacionPorEspecie = $casosFrag
            ->groupBy('especie')
            ->map(fn($g, $k) => (object)[
                'especie'     => $k,
                'num_ordenes' => $g->sum('cantidad_oc'),
                'monto_total' => $g->sum('monto_total'),
            ])
            ->filter(fn($r) => $r->num_ordenes > 1)
            ->sortByDesc('num_ordenes')
            ->take(15)->values();

        $chartFragEspecieLabels = $fragmentacionPorEspecie->pluck('especie')->toArray();
        $chartFragEspecieOCs    = $fragmentacionPorEspecie->map(fn($r) => (int)$r->num_ordenes)->toArray();
        $chartFragEspecieMontos = $fragmentacionPorEspecie->map(fn($r) => (float)$r->monto_total)->toArray();

        // MODALIDAD

        $distribucionModalidad = DB::table('modalidads')
            ->select([
                'modalidads.modalidad',
                DB::raw('COUNT(DISTINCT modalidads.id) as num_licitaciones'),
                DB::raw('COUNT(DISTINCT ordens.id) as num_ordenes'),
                DB::raw('COALESCE(SUM(ordens.monto), 0) as monto_total'),
            ])
            ->join('pacs', 'modalidads.id_proyecto', '=', 'pacs.id')
            ->leftJoin('ordens', function ($join) {
                $join->on('ordens.id_licitacion', '=', 'modalidads.id')
                     ->on('ordens.id_proyecto', '=', 'pacs.id');
            })
            ->where('pacs.year', $selectedYear)
            ->when($selectedDepartamentoId, fn($q) => $q->where('pacs.departamento_id', $selectedDepartamentoId))
            ->groupBy('modalidads.modalidad')
            ->orderByDesc(DB::raw('COALESCE(SUM(ordens.monto), 0)'))
            ->get();

        $totalMontoModalidad  = max($distribucionModalidad->sum('monto_total'), 1);
        $chartModalidadLabels = $distribucionModalidad->pluck('modalidad')->toArray();
        $chartModalidadData   = $distribucionModalidad->map(fn($r) => (float)$r->monto_total)->toArray();

        // CONCENTRACIÓN TEMPORAL

        $concentracionRaw = DB::table('ordens')
            ->select([
                DB::raw('EXTRACT(MONTH FROM ordens.fecha_seguimiento)::int AS mes'),
                DB::raw('COUNT(ordens.id) AS num_ordenes'),
                DB::raw('COALESCE(SUM(ordens.monto), 0) AS monto_total'),
            ])
            ->join('pacs', 'ordens.id_proyecto', '=', 'pacs.id')
            ->where('pacs.year', $selectedYear)
            ->whereNotNull('ordens.fecha_seguimiento')
            ->when($selectedDepartamentoId, fn($q) => $q->where('pacs.departamento_id', $selectedDepartamentoId))
            ->groupBy(DB::raw('EXTRACT(MONTH FROM ordens.fecha_seguimiento)::int'))
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        $chartTemporalLabels = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        $chartTemporalOCs    = [];
        $chartTemporalMontos = [];

        for ($m = 1; $m <= 12; $m++) {
            $dato = $concentracionRaw->get($m);
            $chartTemporalOCs[]    = $dato ? (int)$dato->num_ordenes   : 0;
            $chartTemporalMontos[] = $dato ? (float)$dato->monto_total : 0;
        }

        // TOP PROYECTOS

        $todosProyectos = DB::table('pacs')
            ->select([
                'pacs.id as id_proyecto',
                'departamentos.detalle as departamento',
                DB::raw("COALESCE(especies.detalle, '-') as especie"),
                'pacs.codigo as item_presupuestario',
                DB::raw('COALESCE((
                    SELECT SUM(pr.monto) FROM presupuestos pr
                    WHERE pr.departamento_id = pacs.departamento_id
                      AND pr.year = pacs.year
                      AND pr.item = pacs.codigo
                ), 0) as presupuesto'),
                DB::raw("COALESCE((
                    SELECT SUM(o.monto)
                    FROM ordens o
                    LEFT JOIN estado_compras ec ON o.estado_id = ec.id
                    WHERE o.id_proyecto = pacs.id
                      AND COALESCE(LOWER(TRIM(ec.detalle)), '') != 'orden de compra cancelada'
                ), 0) as comprometido"),
            ])
            ->join('departamentos', 'pacs.departamento_id', '=', 'departamentos.id')
            ->leftJoin('especies', 'pacs.especie_id', '=', 'especies.id')
            ->where('pacs.year', $selectedYear)
            ->when($selectedDepartamentoId, fn($q) => $q->where('pacs.departamento_id', $selectedDepartamentoId))
            ->get();

        $topBajaEjecucion = $todosProyectos
            ->filter(fn($r) => $r->presupuesto > 0)
            ->map(function ($r) {
                $r->porcentaje = round(($r->comprometido / $r->presupuesto) * 100, 1);
                return $r;
            })
            ->sortBy('porcentaje')
            ->take(10)
            ->values();

        $topSaldoNegativo = $todosProyectos
            ->filter(fn($r) => $r->comprometido > $r->presupuesto)
            ->map(function ($r) {
                $r->exceso     = $r->comprometido - $r->presupuesto;
                $r->porcentaje = $r->presupuesto > 0
                    ? round(($r->comprometido / $r->presupuesto) * 100, 1) : 0;
                return $r;
            })
            ->sortByDesc('exceso')
            ->take(10)
            ->values();

        // DETALLE ALERTAS (para modales "Ver Más")
        $detalleSinLicitacion = DB::table('pacs')
            ->select(
                'pacs.id as id_proyecto',
                'pacs.year as anio',
                'departamentos.detalle as departamento',
                DB::raw("COALESCE(especies.detalle, 'â€”') as especie"),
                'pacs.codigo as item'
            )
            ->leftJoin('modalidads', 'modalidads.id_proyecto', '=', 'pacs.id')
            ->join('departamentos', 'pacs.departamento_id', '=', 'departamentos.id')
            ->leftJoin('especies', 'pacs.especie_id', '=', 'especies.id')
            ->where('pacs.year', $selectedYear)
            ->whereNull('modalidads.id')
            ->when($selectedDepartamentoId, fn($q) => $q->where('pacs.departamento_id', $selectedDepartamentoId))
            ->orderBy('departamentos.detalle')
            ->get();

        $detalleSinOC = DB::table('pacs')
            ->select(
                'pacs.id as id_proyecto',
                'pacs.year as anio',
                'departamentos.detalle as departamento',
                DB::raw("COALESCE(especies.detalle, 'â€”') as especie"),
                'pacs.codigo as item'
            )
            ->leftJoin('ordens', 'ordens.id_proyecto', '=', 'pacs.id')
            ->join('departamentos', 'pacs.departamento_id', '=', 'departamentos.id')
            ->leftJoin('especies', 'pacs.especie_id', '=', 'especies.id')
            ->where('pacs.year', $selectedYear)
            ->whereNull('ordens.id')
            ->when($selectedDepartamentoId, fn($q) => $q->where('pacs.departamento_id', $selectedDepartamentoId))
            ->orderBy('departamentos.detalle')
            ->get();

        $detalleSaldoNegativo = $todosProyectos
            ->filter(fn($r) => (float)$r->comprometido > (float)$r->presupuesto)
            ->map(function ($r) {
                $r->exceso     = (float)$r->comprometido - (float)$r->presupuesto;
                $r->porcentaje = (float)$r->presupuesto > 0
                    ? round(((float)$r->comprometido / (float)$r->presupuesto) * 100, 1) : 0;
                return $r;
            })
            ->sortByDesc('exceso')
            ->values();

        $detalleLicitacionesProblematicas = DB::table('modalidads')
            ->select(
                'pacs.id as id_proyecto',
                'departamentos.detalle as departamento',
                DB::raw("COALESCE(especies.detalle, 'â€”') as especie"),
                'modalidads.modalidad',
                'estado_licitacions.detalle as estado'
            )
            ->join('estado_licitacions', 'modalidads.estado_id', '=', 'estado_licitacions.id')
            ->join('pacs', 'modalidads.id_proyecto', '=', 'pacs.id')
            ->join('departamentos', 'pacs.departamento_id', '=', 'departamentos.id')
            ->leftJoin('especies', 'pacs.especie_id', '=', 'especies.id')
            ->where('pacs.year', $selectedYear)
            ->whereRaw("LOWER(estado_licitacions.detalle) IN (?, ?, ?, ?)", [
                'desierta', 'suspendida', 'revocada', 'proyecto no ejecutado',
            ])
            ->when($selectedDepartamentoId, fn($q) => $q->where('pacs.departamento_id', $selectedDepartamentoId))
            ->orderBy('departamentos.detalle')
            ->get();

        // Panel de estado de usuarios solo visible para admin en la vista
        $resumenUsuarios = $isAdmin
            ? DB::table('users')
                ->leftJoin('departamentos', 'users.departamento_id', '=', 'departamentos.id')
                ->select('users.name', 'users.email', 'users.departamento_id', 'departamentos.detalle as departamento_nombre')
                ->orderBy('users.name')
                ->get()
            : collect();

        return view('reporte.dashboard', compact(
            'isAdmin', 'resumenUsuarios',
            'availableYears', 'selectedYear', 'departamentos', 'selectedDepartamentoId',
            // SecciÃ³n 1: KPIs generales
            'totalProyectos', 'totalLicitaciones', 'totalOrdenes',
            'totalPresupuesto', 'totalComprometido', 'totalSaldo', 'totalPorcentaje',
            // SecciÃ³n 2: Alertas
            'proyectosSinLicitacion', 'proyectosSinOC', 'proyectosSaldoNegativo', 'licitacionesProblematicas',
            // SecciÃ³n 3: Motor de fragmentaciÃ³n
            'casosFrag', 'reglasFragmentacion', 'umbralFragmentacion', 'valorUTM',
            'kpiTotalOC', 'kpiCasosSospechosos', 'kpiRiesgoAlto', 'kpiMontoFragmentado',
            'chartEspecieLabels', 'chartEspecieScores', 'chartEspecieMontos', 'chartEspecieNiveles',
            'chartDeptLabels', 'chartDeptAlto', 'chartDeptMedio', 'chartDeptBajo',
            'chartEvolucionLabels', 'chartEvolucionCasos', 'chartEvolucionMontos',
            'chartFragModLabels', 'chartFragModData',
            'heatmapEspecies', 'heatmapDeptos', 'heatmapMatrix',
            'sospechaNFragmentacion', 'fragmentacionPorEspecie',
            'chartFragEspecieLabels', 'chartFragEspecieOCs', 'chartFragEspecieMontos',
            // SecciÃ³n 4: Modalidad
            'distribucionModalidad', 'totalMontoModalidad', 'chartModalidadLabels', 'chartModalidadData',
            // SecciÃ³n 5-6: Temporal y departamentos
            'chartTemporalLabels', 'chartTemporalOCs', 'chartTemporalMontos',
            'chartBarLabels', 'chartBarPresupuesto', 'chartBarComprometido', 'tablaDepartamentos',
            // SecciÃ³n 7: Top proyectos
            'topBajaEjecucion', 'topSaldoNegativo',
            // Modales de alerta
            'detalleSinLicitacion', 'detalleSinOC', 'detalleSaldoNegativo', 'detalleLicitacionesProblematicas',
            // SecciÃ³n 8: Estado licitaciones
            'chartDonaLicLabels', 'chartDonaLicData', 'chartDonaOcLabels', 'chartDonaOcData'
        ));
    }
}




