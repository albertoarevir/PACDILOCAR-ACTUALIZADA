<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use Illuminate\Http\Request;
use App\Models\Grado;
use App\Models\Departamento;
use App\Http\Controllers\Controller; // <-- Agrega esta línea

class FuncionarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $grados = Grado::all()->sortByDesc('id');
        $funcionarios = Funcionario::all()->sortByDesc('id');
        return view('funcionarios.index', compact('grados', 'funcionarios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $departamentos = Departamento::all();
        $grados = Grado::all()->sortByDesc('id');
        return view('funcionarios/create', compact('grados', 'departamentos'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'Rut' => 'required|max:12|unique:funcionarios,Rut|max:12',
            'Codigo' => 'required',
            'Grado' => 'required',
            'Nombres' => 'required',
            'Apellidos' => 'required',
            'Dotacion' => 'required',
            'Email' => 'required|email|unique:funcionarios,email|max:100',
        ]);


        $funcionario = new Funcionario();
        $funcionario->Rut = $request->input('Rut');
        $funcionario->Codigo = $request->input('Codigo');
        $funcionario->Grado = $request->input('Grado');
        $funcionario->Nombres = $request->input('Nombres');
        $funcionario->Apellidos = $request->input('Apellidos');
        $funcionario->Dotacion = $request->input('Dotacion');
        $funcionario->Email = $request->input('Email');
        $funcionario->save();
        return redirect()->route('funcionarios.index')
            ->with('mensaje', 'Se registro al usuario de la manera correcta')
            ->with('icono', 'success');
    }

    /**
     * Display the specified resource.
     */
    public function show(Funcionario $funcionario)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
        $funcionarios = Funcionario::findOrFail($id);
        $grados = Grado::all();
        $departamentos = Departamento::all();
        return view('/funcionarios.edit', compact('funcionarios', 'grados', 'departamentos'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $funcionario = Funcionario::find($id);
    
        $request->validate([
            'Rut' => 'required|max:9|unique:Funcionarios,Rut,'.$funcionario->id,
            'Codigo'=> 'required',  
            'Grado' => 'required',
            'Nombres' => 'required|max:100',
            'Apellidos' => 'required',
            'Dotacion' => 'required',                        
            'Email' => 'required|Email|unique:funcionarios,Email,'.$funcionario->id.'|max:100',
    
        ]);        
    
        $funcionario->Rut = $request->Rut;  
        $funcionario->Codigo = $request->Codigo;
        $funcionario->Grado = $request->Grado;
        $funcionario->Nombres = $request->Nombres;
        $funcionario->Apellidos = $request->Apellidos;       
        $funcionario->Dotacion = $request->Dotacion;
        $funcionario->Email = $request->Email;
        $funcionario->save(); // Guardar en la base de datos
    
        return redirect()->route('funcionarios.index')
            ->with('mensaje','Se actualizó el usuario de la manera correcta')
            ->with('icono','success');
    }
    /**
     * Remove the specified resource from storage.
     */


    public function destroy($id)
    {
        $funcionario = Funcionario::find($id);
        if ($funcionario) {
            $rutNorm = \App\Models\User::normalizarRut($funcionario->Rut);
            $token = session('autentificatic_token') ?: request()->cookie('token_de_acceso');
            \App\Http\Controllers\Autenti\AutentiController::eliminarUsuarioEnApi($rutNorm, $token);
            $funcionario->delete();
        }

        return redirect()->route('funcionarios.index')
            ->with('mensaje', 'Se eliminó el funcionario de la manera correcta y se desvinculó de AutentificaTic')
            ->with('icono', 'success');
    }
}
