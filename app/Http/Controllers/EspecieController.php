<?php

namespace App\Http\Controllers;

use App\Models\Especie;
use App\Models\Departamento;
use Illuminate\Http\Request;

class EspecieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;

        $especies = $isAdmin
            ? Especie::with('departamento')->get()
            : Especie::with('departamento')->where('departamento_id', $user->departamento_id)->get();

        $departamentos = $isAdmin
            ? Departamento::all()
            : Departamento::where('id', $user->departamento_id)->get();

        return view('especies.index', compact('especies', 'departamentos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;

        $departamentos = $isAdmin
            ? Departamento::all()
            : Departamento::where('id', $user->departamento_id)->get();

        $especies = $isAdmin
            ? Especie::all()
            : Especie::where('departamento_id', $user->departamento_id)->get();

        // Pass both variables to the view
        return view('especies.create', compact('departamentos', 'especies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'departamento_id' => 'required|exists:departamentos,id',
            'detalle' => 'required',
        ]);

        $user = auth()->user();
        $isAdmin = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;

        if (!$isAdmin && (int) $request->departamento_id !== (int) $user->departamento_id) {
            abort(403, 'No tienes permiso para registrar especies o servicios en otro departamento.');
        }

        $especie = new Especie();
        $especie->departamento_id = $request->departamento_id;
        $especie->detalle = $request->detalle;

        $especie->save();
        return redirect()->route('especies.index')->with('mensaje', 'Se registró la Especie/Servicio de la manera correcta');
    }

    /**
     * Display the specified resource.
     */
    public function show(Especie $especie)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $especies = Especie::findOrFail($id);
        $user = auth()->user();
        $isAdmin = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;

        if (!$isAdmin && (int) $especies->departamento_id !== (int) $user->departamento_id) {
            abort(403, 'No tienes permiso para editar este registro.');
        }

        return view('especies.edit', ['especies' => $especies]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Especie $especie, $id)
    {
        $request->validate([
            'detalle' => 'required',
        ]);

        $especies = Especie::findOrFail($id);
        $user = auth()->user();
        $isAdmin = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;

        if (!$isAdmin && (int) $especies->departamento_id !== (int) $user->departamento_id) {
            abort(403, 'No tienes permiso para modificar este registro.');
        }

        $especies->detalle = $request->detalle;
        $especies->save();

        return redirect()->route('especies.index')->with('mensaje', 'Se actualizó el registro de la manera correcta');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Especie $especie, $id)
    {
        $especieRegistro = Especie::findOrFail($id);
        $user = auth()->user();
        $isAdmin = $user->hasRole('ADMINISTRADOR') || (int) $user->departamento_id === 7;

        if (!$isAdmin && (int) $especieRegistro->departamento_id !== (int) $user->departamento_id) {
            abort(403, 'No tienes permiso para eliminar este registro.');
        }

        $especieRegistro->delete();
        return redirect()->route('especies.index')->with('mensaje', 'Se eliminó el registro del control de Especies/Servicios');
    }
}