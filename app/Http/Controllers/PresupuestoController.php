<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Presupuesto;
use App\Models\Departamento;
use App\Models\Clasificador;

class PresupuestoController extends Controller
{
    public function index()
    {
        $user    = auth()->user();
        $isAdmin = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;

        // Traemos los presupuestos con su departamento para evitar el problema de N+1
        $query = Presupuesto::with('departamento')->orderBy('id', 'desc');
        if (!$isAdmin) {
            $query->where('departamento_id', $user->departamento_id);
        }
        $Presupuestos = $query->get();

        return view('presupuesto.index', compact('Presupuestos'));
    }

    public function create()
    {
        $user    = auth()->user();
        $isAdmin = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;

        $departamentos = $isAdmin
            ? Departamento::all()
            : Departamento::where('id', $user->departamento_id)->get();
        $clasificadors = Clasificador::all();
        return view('presupuesto.create', compact('departamentos', 'clasificadors'));
    }

    public function store(Request $request)
    {
        // Limpiamos el formato de moneda (quitamos puntos) antes de validar
        if ($request->has('presupuesto')) {
            $request->merge([
                'presupuesto' => str_replace('.', '', $request->presupuesto)
            ]);
        }

        $request->validate([
            'year'           => 'required|integer',
            'clasificador'   => 'required|string|max:10',
            'codigo_id'      => 'required|string|max:50', // Este se guarda en 'item'
            'presupuesto'    => 'required|numeric',       // Este se guarda en 'monto'
            'departamento'   => 'required|exists:departamentos,id',
            'observacion'    => 'nullable|string'
        ]);

        $user    = auth()->user();
        $isAdmin = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;

        if (!$isAdmin && (int) $request->departamento !== (int) $user->departamento_id) {
            abort(403, 'No tienes permiso para registrar presupuesto en otro departamento.');
        }

        $presupuesto = new Presupuesto();
        $presupuesto->year            = $request->year;
        $presupuesto->clasificador    = $request->clasificador;
        $presupuesto->item            = $request->codigo_id; 
        $presupuesto->monto           = $request->presupuesto;
        $presupuesto->departamento_id = $request->departamento;
        $presupuesto->observaciones   = $request->observacion;
        $presupuesto->save();

        return redirect()->route('presupuesto.index')
                         ->with('mensaje', 'Presupuesto creado con éxito.');
    }

    public function edit(string $id)
    {
        $presupuesto = Presupuesto::findOrFail($id);
        $user        = auth()->user();
        $isAdmin     = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;

        if (!$isAdmin && (int) $presupuesto->departamento_id !== (int) $user->departamento_id) {
            abort(403, 'No tienes permiso para editar este registro.');
        }

        $departamentos = $isAdmin
            ? Departamento::all()
            : Departamento::where('id', $user->departamento_id)->get();
        $clasificadors = Clasificador::all();

        return view('presupuesto.edit', compact('presupuesto', 'departamentos', 'clasificadors'));
    }

    public function update(Request $request, string $id)
    {
        // Limpiamos el monto
        $request->merge([
            'presupuesto' => str_replace('.', '', $request->presupuesto)
        ]);

        $request->validate([
            'year'           => 'required|integer',
            'clasificador'   => 'required|string|max:10',
            'codigo_id'      => 'required|string|max:50',
            'presupuesto'    => 'required|numeric',
            'departamento'   => 'required|exists:departamentos,id',
            'observacion'    => 'nullable|string'
        ]);

        $presupuesto = Presupuesto::findOrFail($id);
        $user        = auth()->user();
        $isAdmin     = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;

        if (!$isAdmin && ((int) $presupuesto->departamento_id !== (int) $user->departamento_id || (int) $request->departamento !== (int) $user->departamento_id)) {
            abort(403, 'No tienes permiso para modificar este registro.');
        }

        $presupuesto->year            = $request->year;
        $presupuesto->clasificador    = $request->clasificador;
        $presupuesto->item            = $request->codigo_id;
        $presupuesto->monto           = $request->presupuesto;
        $presupuesto->departamento_id = $request->departamento;
        $presupuesto->observaciones   = $request->observacion;
        $presupuesto->save();

        return redirect()->route('presupuesto.index')
                         ->with('mensaje', 'Presupuesto actualizado con éxito.');
    }

    public function destroy(string $id)
    {
        $presupuesto = Presupuesto::findOrFail($id);
        $user        = auth()->user();
        $isAdmin     = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;

        if (!$isAdmin && (int) $presupuesto->departamento_id !== (int) $user->departamento_id) {
            abort(403, 'No tienes permiso para eliminar este registro.');
        }

        $presupuesto->delete();

        return redirect()->route('presupuesto.index')
                         ->with('mensaje', 'Registro eliminado correctamente.');
    }

    public function checkDuplicate(Request $request)
    {
        $request->validate([
            'departamento_id' => 'required|integer',
            'codigo_id'       => 'required|string',
            'year'            => 'required|integer',
        ]);

        $existe = Presupuesto::where('departamento_id', $request->departamento_id)
            ->where('item', $request->codigo_id)
            ->where('year', $request->year)
            ->first();

        if ($existe) {
            return response()->json([
                'existe' => true,
                'monto'  => number_format($existe->monto, 0, ',', '.'),
            ]);
        }

        return response()->json(['existe' => false]);
    }
}
