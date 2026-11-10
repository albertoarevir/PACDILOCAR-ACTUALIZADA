<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pac;
use App\Models\Departamento;
use App\Models\EstadoLicitacion;
use App\Models\EstadoCompra;
use App\Models\Especie;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReporteExport;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user();
        $isAdmin = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;
        $currentYear    = now()->year;
        $availableYears = range($currentYear, $currentYear - 4);
        $selectedYear   = $request->input('year', $currentYear);

        $departamentos = $isAdmin
            ? Departamento::orderBy('detalle')->get()
            : Departamento::where('id', $user->departamento_id)->get();

        $selectedDepartamentoId = $request->input('departamento_id');

        $estadosLicitacion          = EstadoLicitacion::orderBy('detalle')->get();
        $selectedEstadoLicitacionId = $request->input('estado_licitacion_id');

        $estadosCompra          = EstadoCompra::orderBy('detalle')->get();
        $selectedEstadoCompraId = $request->input('estado_compra_id');

        $selectedIdProyecto         = $request->input('id_proyecto');
        $selectedItemPresupuestario = $request->input('item_presupuestario');

        // ------------------------------------------------------------------
        // 1. Construccion de la query base
        // ------------------------------------------------------------------
        $query = Pac::query()
            ->select([
                'pacs.id                            as id_proyecto',
                'pacs.year                          as anio',
                'departamentos.detalle              as departamento',
                'pacs.codigo                        as item_presupuestario',
               'especies.detalle                   as especie',
                'pacs.cantidad                      as cantidad',
                DB::raw('COALESCE((
                    SELECT SUM(pr.monto)
                    FROM presupuestos pr
                    WHERE pr.departamento_id = pacs.departamento_id
                      AND pr.year            = pacs.year
                      AND pr.item            = pacs.codigo
                ), 0) as presupuesto_inicial_sap'),
                DB::raw("COALESCE((
                    SELECT SUM(pr2.monto)
                    FROM presupuestos pr2
                    WHERE pr2.departamento_id = pacs.departamento_id
                      AND pr2.year            = pacs.year
                      AND pr2.item            = pacs.codigo
                ), 0) - COALESCE((
                    SELECT SUM(o2.monto)
                    FROM ordens o2
                    LEFT JOIN estado_compras ec2 ON o2.estado_id = ec2.id
                    WHERE o2.id_proyecto = pacs.id
                      AND COALESCE(LOWER(TRIM(ec2.detalle)), '') != 'orden de compra cancelada'
                ), 0) as saldo"),
                'ordens.monto                       as monto_compra',
                'ordens.numero                      as numero_orden',
                'ordens.updated_at           as fecha_actualizacion_compra',
                'estado_compras.detalle             as estado_compra',
                'modalidads.numero                  as numero_licitacion',
                'modalidads.modalidad               as modalidad_compra',
                'modalidads.updated_at              as fecha_actualizacion_licitacion',
                'estado_licitacions.detalle         as estado_licitacion',
                DB::raw("(
                    SELECT COALESCE(SUM(o2.monto), 0)
                    FROM ordens o2
                    LEFT JOIN estado_compras ec2 ON o2.estado_id = ec2.id
                    WHERE o2.id_proyecto = pacs.id
                      AND COALESCE(LOWER(TRIM(ec2.detalle)), '') != 'orden de compra cancelada'
                ) as comprometido"),
            ])
            ->join('departamentos', 'pacs.departamento_id', '=', 'departamentos.id')
           ->leftJoin('especies', 'pacs.especie_id', '=', 'especies.id')
            ->leftJoin('modalidads', 'modalidads.id_proyecto', '=', 'pacs.id')
            ->leftJoin('estado_licitacions', 'modalidads.estado_id', '=', 'estado_licitacions.id')
            ->leftJoin('ordens', function ($join) {
                $join->on('ordens.id_proyecto', '=', 'pacs.id')
                     ->on('ordens.id_licitacion', '=', 'modalidads.id');
            })
            ->leftJoin('estado_compras', 'ordens.estado_id', '=', 'estado_compras.id')
            ->where('pacs.year', (string) $selectedYear);

        // ------------------------------------------------------------------
        // 2. Aplicacion de filtros
        // ------------------------------------------------------------------
        if (!$isAdmin) {
            $query->where('pacs.departamento_id', $user->departamento_id);
        }

        if ($selectedIdProyecto) {
            $query->where('pacs.id', $selectedIdProyecto);
        }

        if ($selectedDepartamentoId) {
            $query->where('pacs.departamento_id', $selectedDepartamentoId);
        }

        if ($selectedItemPresupuestario) {
            $query->where('pacs.codigo', 'ilike', "%{$selectedItemPresupuestario}%");
        }

        if ($selectedEstadoLicitacionId) {
            $query->where('modalidads.estado_id', $selectedEstadoLicitacionId);
        }

        if ($selectedEstadoCompraId) {
            $query->where('ordens.estado_id', $selectedEstadoCompraId);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('especies.detalle', 'ilike', "%{$buscar}%")
                  ->orWhere('modalidads.numero', 'ilike', "%{$buscar}%")
                  ->orWhere('modalidads.modalidad', 'ilike', "%{$buscar}%")
                  ->orWhere('ordens.numero', 'ilike', "%{$buscar}%");
            });
        }

        // ------------------------------------------------------------------
        // 3. Exportar a Excel si se presiono el boton Excel
        // ------------------------------------------------------------------
        if ($request->has('export')) {
            return Excel::download(
                new ReporteExport($query),
                'Reporte_PAC_' . now()->format('d-m-Y') . '.xlsx'
            );
        }

        // ------------------------------------------------------------------
        // 4. Paginar para la vista web
        // ------------------------------------------------------------------
        $reporte = $query
            ->orderBy('departamentos.detalle')
            ->orderBy('pacs.id')
            ->paginate(10)
            ->withQueryString();

        return view('reporte.index', compact(
            'reporte',
            'departamentos',
            'availableYears',
            'selectedYear',
            'selectedIdProyecto',
            'selectedDepartamentoId',
            'selectedItemPresupuestario',
            'estadosLicitacion',
            'selectedEstadoLicitacionId',
            'estadosCompra',
            'selectedEstadoCompraId',
        ));
    }
}