<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;
use App\Models\User;
use App\Models\Bitacora;

class ApiLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('inicio');
    }

    public function loginWithApi(Request $request)
    {
        $request->validate([
            'rut_funcionario' => 'required|string',
            'accessToken'     => 'required|string',
        ]);

        $rut   = User::normalizarRut($request->input('rut_funcionario'));
        $token = $request->input('accessToken');
        $ip    = $request->ip();

        try {
            // 
            $response = Http::timeout(10)->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ])->get(config('services.autentificatic.url'));

            if ($response->failed()) {
                Log::error('Login fallido: token invalido', ['rut' => $rut, 'ip' => $ip, 'status' => $response->status()]);
                return back()->withErrors(['msg' => 'La sesion con la API Institucional no es valida o su token ha expirado.']);
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Error de conexion con Autentificatic API: ' . $e->getMessage(), ['ip' => $ip]);
            return back()->withErrors(['msg' => 'Error de conexion con el servidor de validacion. Intente nuevamente.']);
        } catch (\Exception $e) {
            Log::error('Error inesperado en login API: ' . $e->getMessage(), ['ip' => $ip]);
            return back()->withErrors(['msg' => 'Error inesperado al validar token.']);
        }

        try {
            // Comparar el RUT normalizado en ambos lados: en la BD pueden existir RUT guardados con puntos o guion
            $rutSql = "UPPER(REGEXP_REPLACE(\"Rut\", '[^0-9Kk]', '', 'g')) = ?";

            // Primero buscar usuario activo (no eliminado)
            $user = User::whereRaw($rutSql, [$rut])->first();

            // Si no hay usuario activo, buscar soft-deleted y restaurarlo
            if (!$user) {
                $userTrashed = User::onlyTrashed()->whereRaw($rutSql, [$rut])->first();

                if ($userTrashed) {
                    $userTrashed->restore();
                    $user = $userTrashed;
                }
            }

            if (!$user) {
                Bitacora::create([
                    'user_id'     => null,
                    'modulo'      => 'Acceso al Sistema',
                    'accion'      => 'login_sin_cuenta',
                    'descripcion' => 'RUT validado en API institucional pero sin cuenta local: ' . $rut,
                    'ip'          => $ip,
                    'user_agent'  => request()->userAgent(),
                ]);
                return back()->withErrors(['msg' => 'Usted se autentico correctamente en la API, pero no tiene cuenta habilitada en PacDilocar. Contacte al administrador.']);
            }

            if (!$user->habilitado) {
                Bitacora::create([
                    'user_id'     => $user->id,
                    'modulo'      => 'Acceso al Sistema',
                    'accion'      => 'login_inhabilitado',
                    'descripcion' => 'Intento de acceso de usuario inhabilitado por el administrador: ' . $rut,
                    'ip'          => $ip,
                    'user_agent'  => request()->userAgent(),
                ]);
                return back()->withErrors(['msg' => 'Su cuenta no esta habilitada en el sistema por el Administrador. Contacte al administrador.']);
            }

            // Limpiar caché de permisos de Spatie para obtener datos frescos
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $user->load('roles', 'permissions');

            if ($user->roles->isEmpty()) {
                return back()->withErrors(['msg' => 'Su cuenta no tiene roles asignados. Contacte al administrador del sistema para que le asigne un perfil de acceso.']);
            }

            Auth::login($user);
            $request->session()->regenerate();

            // Token institucional en la sesion del servidor, para llamadas posteriores a AutentificaTic (ej: habilitar usuarios)
            $request->session()->put('autentificatic_token', $token);

            // Obtener nombre completo, dotacion y foto desde la API
            try {
                $userApi = Http::timeout(5)
                    ->withHeaders(['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'])
                    ->get('http://autentificaticapi.carabineros.cl/api/auth/user');

                if ($userApi->ok()) {
                    $u = $userApi->json('success.user');

                    // Log para diagnosticar el campo photo
                    $rawPhoto = $userApi->json('success.photo');
                    Log::info('API foto_perfil', [
                        'rut'         => $rut,
                        'photo_null'  => is_null($rawPhoto),
                        'photo_type'  => gettype($rawPhoto),
                        'photo_start' => $rawPhoto ? substr($rawPhoto, 0, 30) : 'null',
                    ]);

                    if (!empty($u['apellido_paterno'])) {
                        $segundoNombre  = !empty($u['segundo_nombre']) ? ' ' . $u['segundo_nombre'] : '';
                        $nombreCompleto = trim(
                            $u['apellido_paterno'] . ' ' .
                            ($u['apellido_materno'] ?? '') . ', ' .
                            ($u['primer_nombre'] ?? '') . $segundoNombre
                        );

                        // Normalizar foto: si ya trae prefijo data: úsala directamente,
                        // si es base64 puro agregar prefijo, si es null dejar null
                        $fotoPerfil = null;
                        if (!empty($rawPhoto)) {
                            $fotoPerfil = str_starts_with($rawPhoto, 'data:')
                                ? $rawPhoto
                                : 'data:image/jpeg;base64,' . $rawPhoto;
                        }

                        $dotacionApi = $u['descripcion_reparticion']
                            ?? ($u['dotacion']
                            ?? 'DIRECCION DE LOGISTICA');

                        session([
                            'nombre_completo'         => $nombreCompleto,
                            'dotacion'                => $dotacionApi,
                            'descripcion_reparticion' => $dotacionApi,
                            'grado'                   => $u['grado'] ?? null,
                            'foto_perfil'             => $fotoPerfil,
                        ]);

                        // Persistir la dotacion oficial extraida de la API institucional en la tabla funcionarios
                        try {
                            \App\Models\Funcionario::where('Rut', $rut)
                                ->update(['Dotacion' => $dotacionApi]);
                        } catch (\Exception $exFunc) {
                            // no interrumpir login
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error al obtener datos de API usuario: ' . $e->getMessage(), ['rut' => $rut]);
            }

            Bitacora::create([
                'user_id'     => $user->id,
                'modulo'      => 'Acceso al Sistema',
                'accion'      => 'login',
                'descripcion' => 'Inicio de sesion exitoso via API institucional',
                'ip'          => $ip,
                'user_agent'  => $request->userAgent(),
            ]);

            // Emitir cookie de token_de_acceso HttpOnly desde el servidor (Mitigación 1.2 - Anti XSS)
            $tokenCookie = cookie(
                'token_de_acceso',
                $token,
                60,                 // Expiración: 60 minutos
                '/',                // Path
                null,               // Domain
                $request->secure(), // Secure (se activa automáticamente bajo HTTPS)
                true,               // HttpOnly -> Impide acceso/lectura desde JavaScript (document.cookie)
                false,              // Raw
                'Strict'            // SameSite Strict
            );

            // Todo usuario autenticado con rol asignado aterriza en el panel principal
            return redirect()->route('admin.index')->withCookie($tokenCookie);

        } catch (\Exception $e) {
            Log::error('Error en proceso de login: ' . $e->getMessage(), ['rut' => $rut, 'ip' => $ip]);
            return back()->withErrors(['msg' => 'Error interno al procesar el login. Contacte al administrador.']);
        }
    }
}
