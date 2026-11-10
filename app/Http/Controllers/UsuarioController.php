<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Departamento;
use App\Models\Funcionario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    private function verificarDepartamento(User $usuario): void
    {
        $authUser = auth()->user();
        if ($authUser->departamento_id !== 7 && $usuario->departamento_id !== $authUser->departamento_id) {
            abort(403, 'No tienes permiso para gestionar usuarios de otro departamento.');
        }
    }

    public function index()
    {
        $authUser = auth()->user();
        $usuarios = $authUser->departamento_id === 7
            ? User::with('departamento')->get()
            : User::with('departamento')->where('departamento_id', $authUser->departamento_id)->get();

        // Cargar funcionarios existentes indexados por RUT normalizado
        $funcionarios = Funcionario::all()->keyBy(function ($f) {
            return User::normalizarRut($f->Rut);
        });

        // Sincronizar inmediatamente la dotación con el departamento asignado en PAC
        foreach ($usuarios as $u) {
            $rNorm = User::normalizarRut($u->Rut);
            $fRecord = $funcionarios->get($rNorm);
            if ($fRecord && $u->departamento && Funcionario::esDotacionInvalida($fRecord->Dotacion)) {
                $fRecord->Dotacion = $u->departamento->detalle;
                $fRecord->save();
            }
        }

        // Si hay token institucional en sesión o cookie, consultar API para usuarios sin datos de funcionario
        $token = session('autentificatic_token') ?: request()->cookie('token_de_acceso');
        if ($token) {
            $consultasRealizadas = 0;
            foreach ($usuarios as $usuario) {
                if ($consultasRealizadas >= 6) {
                    break; // Limitar consultas sincronas por peticion para mantener velocidad de carga
                }

                $rutNorm = User::normalizarRut($usuario->Rut);
                $func = $funcionarios->get($rutNorm);

                if (!$func || $func->Grado === 'S/G' || empty($func->Apellidos)) {
                    $apiData = Funcionario::consultarApi($rutNorm, $token);
                    $consultasRealizadas++;
                    if ($apiData) {
                        $guardado = Funcionario::guardarDesdeApi($rutNorm, $apiData);
                        if ($guardado) {
                            $funcionarios->put($rutNorm, $guardado);
                        }
                    }
                }
            }
        }

        return view('admin.usuarios.index', compact('usuarios', 'funcionarios'));
    }

    public function create(Request $request)
    {
        $departamentos = Departamento::all();
        $rut  = $request->input('Rut') ?: $request->input('rut');
        $name = $request->input('name') ?: $request->input('Nombre') ?: $request->input('nombres');

        // Si viene Rut pero aún no viene el nombre, buscar en la BD o en la API institucional
        if ($rut && empty($name)) {
            $rutNorm = User::normalizarRut($rut);
            $funcionario = Funcionario::where('Rut', $rutNorm)->first();
            $token = $request->session()->get('autentificatic_token') ?: $request->cookie('token_de_acceso');

            if ((!$funcionario || empty($funcionario->Apellidos)) && $token) {
                $apiData = Funcionario::consultarApi($rutNorm, $token);
                if ($apiData) {
                    $funcionario = Funcionario::guardarDesdeApi($rutNorm, $apiData);
                }
            }

            if ($funcionario && $funcionario->nombre_completo && $funcionario->nombre_completo !== 'Sin Nombre') {
                $name = $funcionario->nombre_completo;
            } elseif ($funcionario && $funcionario->Nombres) {
                $name = $funcionario->Nombres;
            }
        }

        return view('admin.usuarios.create', compact('departamentos', 'name', 'rut'));
    }

    public function store(Request $request)
    {
        $rutNorm = User::normalizarRut($request->input('Rut'));
        $request->merge(['Rut' => $rutNorm]);

        // Si no se proporcionó email en el formulario, obtenerlo de Funcionario o generar el institucional
        $funcionario = Funcionario::where('Rut', $rutNorm)->first();
        $token = $request->session()->get('autentificatic_token') ?: $request->cookie('token_de_acceso');

        if ((!$funcionario || empty($funcionario->Apellidos)) && $token) {
            $apiData = Funcionario::consultarApi($rutNorm, $token);
            if ($apiData) {
                $funcionario = Funcionario::guardarDesdeApi($rutNorm, $apiData);
            }
        }

        if (!$request->filled('email')) {
            $email = ($funcionario && !empty($funcionario->Email) && filter_var($funcionario->Email, FILTER_VALIDATE_EMAIL))
                ? $funcionario->Email
                : ($rutNorm . '@carabineros.cl');
            $request->merge(['email' => $email]);
        }

        $request->validate([
            'Rut'             => ['required', 'max:12', Rule::unique('users', 'Rut')->whereNull('deleted_at')],
            'name'            => 'required|max:100',
            'email'           => ['nullable', 'email', 'max:200', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'departamento_id' => 'required|exists:departamentos,id',
        ]);

        $usuario = new User();
        $usuario->Rut             = $rutNorm;
        $usuario->name            = $request->input('name');
        $usuario->email           = $request->input('email');
        $usuario->departamento_id = $request->input('departamento_id');
        $usuario->password        = Hash::make(\Illuminate\Support\Str::random(32));
        $usuario->save();

        return redirect()->route('admin.usuarios.index')
            ->with('mensaje', 'Se registró al usuario de la manera correcta')
            ->with('icono', 'success');
    }

    public function show($id){
        $usuario = User::findOrFail($id);
        $this->verificarDepartamento($usuario);
        return view('admin.usuarios.show', compact('usuario'));
    }

    public function edit($id){
        $usuario = User::findOrFail($id);
        $this->verificarDepartamento($usuario);
        $departamentos = Departamento::all();
        return view('admin.usuarios.edit', compact('usuario', 'departamentos'));
    }

    public function update(Request $request, $id)
    {
        $usuario = User::findOrFail($id);
        $this->verificarDepartamento($usuario);

        $rutNorm = User::normalizarRut($request->input('Rut'));
        $request->merge(['Rut' => $rutNorm]);

        // Si no viene email en el formulario, conservar el existente o buscar en Funcionario
        $email = $request->input('email');
        if (!$email) {
            $email = $usuario->email;
            if (!$email) {
                $funcionario = Funcionario::where('Rut', $rutNorm)->first();
                $email = ($funcionario && !empty($funcionario->Email) && filter_var($funcionario->Email, FILTER_VALIDATE_EMAIL))
                    ? $funcionario->Email
                    : ($rutNorm . '@carabineros.cl');
            }
            $request->merge(['email' => $email]);
        }

        $request->validate([
            'Rut'             => ['required', 'max:12', Rule::unique('users', 'Rut')->ignore($usuario->id)->whereNull('deleted_at')],
            'name'            => 'required|max:100',
            'email'           => ['nullable', 'email', 'max:200', Rule::unique('users', 'email')->ignore($usuario->id)->whereNull('deleted_at')],
            'departamento_id' => 'required|exists:departamentos,id',
            'password'        => ['nullable', 'confirmed', 'min:10', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'],
        ]);

        $usuario->Rut             = $rutNorm;
        $usuario->name            = $request->name;
        $usuario->email           = $email;
        $usuario->departamento_id = $request->departamento_id;

        if ($request->filled('password')) {
            $usuario->password = Hash::make($request->password);
        }

        $usuario->save();

        return redirect()->route('admin.usuarios.index')
            ->with('mensaje', 'Se actualizó el usuario de la manera correcta')
            ->with('icono', 'success');
    }

    public function toggleHabilitado(Request $request, $id)
    {
        $usuario = User::findOrFail($id);
        $this->verificarDepartamento($usuario);

        $request->validate([
            'habilitado' => 'required|boolean',
        ]);

        $usuario->habilitado = $request->boolean('habilitado');
        $usuario->save();

        Bitacora::create([
            'user_id'     => auth()->id(),
            'modulo'      => 'Usuarios',
            'accion'      => $usuario->habilitado ? 'HABILITAR' : 'INHABILITAR',
            'descripcion' => ($usuario->habilitado ? 'Habilitacion' : 'Inhabilitacion') . ' de usuario: ' . $usuario->name . ' (RUT: ' . $usuario->Rut . ')',
            'ip'          => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);

        return redirect()->route('admin.usuarios.index')
            ->with('mensaje', 'Se actualizo el estado del usuario correctamente')
            ->with('icono', 'success');
    }

    public function confirmDelete($id){
        $usuario = User::findOrFail($id);
        $this->verificarDepartamento($usuario);
        return view('admin.usuarios.delete', compact('usuario'));
    }

    public function destroy($id){
        $usuario = User::findOrFail($id);
        $this->verificarDepartamento($usuario);

        $rutNorm = User::normalizarRut($usuario->Rut);
        $token = session('autentificatic_token') ?: request()->cookie('token_de_acceso');

        // Desvincular de AutentificaTic API de Carabineros
        \App\Http\Controllers\Autenti\AutentiController::eliminarUsuarioEnApi($rutNorm, $token);

        // Desvincular de funcionarios locales si existe
        Funcionario::where('Rut', $rutNorm)->delete();

        Bitacora::create([
            'user_id'     => auth()->id(),
            'modulo'      => 'Usuarios',
            'accion'      => 'ELIMINAR',
            'descripcion' => 'Eliminacion de usuario: ' . $usuario->name . ' (RUT: ' . $usuario->Rut . ')',
            'ip'          => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);

        $usuario->delete();
        return redirect()->route('admin.usuarios.index')
            ->with('mensaje', 'Se eliminó el usuario de la manera correcta y se desvinculó de AutentificaTic')
            ->with('icono', 'success');
    }
}
