<?php

namespace App\Http\Controllers\Autenti;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\User;
use App\Models\Funcionario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AutentiController extends Controller
{
    public function index()
    {
        return view('Autenti.registerUser');
    }

    /**
     * Habilita un funcionario en AutentificaTic para PacDilocar.
     * La llamada se hace desde el servidor porque la cookie token_de_acceso es HttpOnly (Mitigación 1.2).
     */
    public function store(Request $request)
    {
        $request->validate([
            'rut_funcionario' => 'required|string|max:12',
        ]);

        $rut = User::normalizarRut($request->input('rut_funcionario'));
        $dotacionIngresada = trim((string) ($request->input('dotacion_funcionario') ?: $request->input('dotacion')));
        if (Funcionario::esDotacionInvalida($dotacionIngresada)) {
            $dotacionIngresada = null;
        }

        // Primero la sesion del servidor; la cookie queda como respaldo para sesiones iniciadas antes de este cambio
        $token = $request->session()->get('autentificatic_token') ?: $request->cookie('token_de_acceso');

        if (strlen($rut) < 8) {
            return response()->json(['message' => 'El RUN ingresado no es valido.'], 422);
        }

        if (!$token) {
            return response()->json(['message' => 'Su sesion institucional ha caducado. Cierre el sistema y vuelva a ingresar.'], 401);
        }

        // 1. Validar si el usuario ya existe en la base de datos local
        $rutSql = "UPPER(REGEXP_REPLACE(\"Rut\", '[^0-9Kk]', '', 'g')) = ?";
        $usuarioLocal = User::whereRaw($rutSql, [$rut])->first();

        // Consultar SIEMPRE los datos frescos desde la API institucional (incluyendo dotación, código, grado y nombres)
        $funcionarioApiData = $token ? $this->consultarDatosFuncionario($rut, $token) : null;
        $dotacionDesdeApi   = $funcionarioApiData ? Funcionario::extraerDotacionDeArray($funcionarioApiData) : null;

        // Determinar dotación final:
        // 1) Si se ingresó manualmente de forma explícita
        // 2) Si la API trajo la dotación/repartición oficial
        // 3) Si el usuario tiene departamento asignado en PAC
        // 4) Si ya existía una dotación válida en BD local
        // 5) Sesión institucional
        $dotacionFinal = (!empty($dotacionIngresada) && !Funcionario::esDotacionInvalida($dotacionIngresada))
            ? $dotacionIngresada
            : ((!empty($dotacionDesdeApi) && !Funcionario::esDotacionInvalida($dotacionDesdeApi))
                ? $dotacionDesdeApi
                : ($usuarioLocal?->departamento?->detalle
                    ?: (Funcionario::where('Rut', $rut)->value('Dotacion'))));

        if (Funcionario::esDotacionInvalida($dotacionFinal)) {
            $dotacionFinal = $usuarioLocal?->departamento?->detalle
                ?: 'Pendiente (se actualiza al iniciar sesión)';
        }

        // Si la API devolvió datos, guardar/actualizar el Funcionario local con los datos oficiales de la API
        if ($funcionarioApiData) {
            $funcLocal = $this->guardarFuncionarioLocal($rut, $funcionarioApiData, $dotacionFinal);
        } else {
            $funcLocal = Funcionario::where('Rut', $rut)->first();
            if (!$funcLocal && $usuarioLocal) {
                $funcLocal = new Funcionario();
                $funcLocal->Rut = $rut;
                $funcLocal->Codigo = 'S/C';
                $funcLocal->Grado = $usuarioLocal->Grado ?? 'S/G';
                $funcLocal->Nombres = $usuarioLocal->name;
                $funcLocal->Apellidos = '';
                $funcLocal->Email = $usuarioLocal->email ?: ($rut . '@carabineros.cl');
            }
            if ($funcLocal && $dotacionFinal) {
                $funcLocal->Dotacion = $dotacionFinal;
                $funcLocal->save();
            }
        }

        if ($usuarioLocal) {
            if ($dotacionFinal && $usuarioLocal->Dotacion !== $dotacionFinal) {
                $usuarioLocal->Dotacion = $dotacionFinal;
                $usuarioLocal->save();
            }

            $nomLocal = ($funcLocal && $funcLocal->nombre_completo && $funcLocal->nombre_completo !== 'Sin Nombre')
                ? $funcLocal->nombre_completo
                : $usuarioLocal->name;
            $createUrlLocal = route('admin.usuarios.create') . '?Rut=' . urlencode($rut) . '&name=' . urlencode($nomLocal);
            $estaHabilitado = (bool) $usuarioLocal->habilitado;
            $estadoTexto = $estaHabilitado ? 'activo' : 'no habilitado';

            return response()->json([
                'status'            => 'already_exists',
                'already_exists'    => true,
                'message'           => "El RUN {$rut} ya existe y está ingresado en AutentificaTic.",
                'detalle'           => "El funcionario ya se encuentra registrado ({$estadoTexto}) en el sistema PAC ({$nomLocal}).",
                'rut'               => $rut,
                'dotacion'          => $funcLocal?->Dotacion ?? $dotacionFinal,
                'user'              => [
                    'primer_nombre' => $nomLocal,
                    'rut'           => $usuarioLocal->Rut,
                    'dotacion'      => $funcLocal?->Dotacion ?? $dotacionFinal,
                    'grado'         => $funcLocal?->Grado ?? $usuarioLocal->Grado,
                ],
                'funcionario'       => [
                    'Nombres'   => $funcLocal?->Nombres ?: $nomLocal,
                    'Apellidos' => $funcLocal?->Apellidos ?: '',
                    'Rut'       => $rut,
                    'grado'     => $funcLocal?->Grado,
                    'Grado'     => $funcLocal?->Grado,
                    'codigo'    => $funcLocal?->Codigo,
                    'Codigo'    => $funcLocal?->Codigo,
                    'dotacion'  => $funcLocal?->Dotacion ?? $dotacionFinal,
                    'Dotacion'  => $funcLocal?->Dotacion ?? $dotacionFinal,
                ],
                'apellidos_nombres' => $nomLocal,
                'nombre_completo'   => $nomLocal,
                'create_url'        => $createUrlLocal,
                'already_in_pac'    => true,
                'pac_habilitado'    => $estaHabilitado,
            ], 200);
        }

        // 2. Verificar si ya fue registrado previamente en la bitacora de AutentificaTic
        $yaRegistradoEnBitacora = Bitacora::where('modulo', 'AutentificaTic')
            ->where('accion', 'habilitar_autentificatic')
            ->where('descripcion', 'like', "%{$rut}%")
            ->exists();

        $platformUrl    = config('services.autentificatic.platform_url', 'http://pacdilocar.carabineros.cl/');
        $platformDomain = config('services.autentificatic.platform_domain', 'pacdilocar.carabineros.cl');

        try {
            $response = Http::timeout(10)
                ->withoutVerifying()
                ->withToken($token)
                ->withHeaders([
                    'Accept'  => 'application/json',
                    'Origin'  => rtrim($platformUrl, '/'),
                    'Referer' => rtrim($platformUrl, '/') . '/',
                ])
                ->asForm()
                ->post(config('services.autentificatic.register_url'), [
                    'rut'     => $rut,
                    'website' => rtrim($platformUrl, '/') . '/',
                    'domain'  => $platformDomain,
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Error de conexion con Autentificatic al habilitar usuario: ' . $e->getMessage(), ['rut' => $rut]);
            return response()->json(['message' => 'Error de conexion con AutentificaTic. Intente nuevamente.'], 502);
        }

        $this->registrarBitacora($request, $rut, $response, $platformDomain);

        if ($response->status() === 401) {
            return response()->json(['message' => 'Su sesion institucional ha caducado. Cierre el sistema y vuelva a ingresar.'], 401);
        }

        // 3. Evaluar si la API informa que el usuario ya se encuentra registrado
        if ($response->failed()) {
            $errores = $response->json('errors');
            $mensajeError = $errores
                ? collect($errores)->flatten()->implode(' ')
                : ($response->json('message') ?? '');

            $textoErrorLower = strtolower($mensajeError . ' ' . $response->body());

            if (str_contains($textoErrorLower, 'ya ') && (
                str_contains($textoErrorLower, 'registrado') ||
                str_contains($textoErrorLower, 'existe') ||
                str_contains($textoErrorLower, 'asociado') ||
                str_contains($textoErrorLower, 'ingresado')
            )) {
                $res = $this->resolverFuncionario($rut, $token, $response, $dotacionIngresada);

                return response()->json([
                    'status'            => 'already_exists',
                    'already_exists'    => true,
                    'message'           => "El RUN {$rut} ya existe y está ingresado en AutentificaTic.",
                    'detalle'           => 'El funcionario ya se encuentra habilitado para esta plataforma en la API institucional.',
                    'rut'               => $rut,
                    'dotacion'          => $res['funcionario']?->Dotacion ?? $dotacionIngresada,
                    'user'              => $res['user'],
                    'funcionario'       => $res['funcionario'],
                    'apellidos_nombres' => $res['apellidos_nombres'],
                    'nombre_completo'   => $res['nombre_completo'],
                    'create_url'        => $res['create_url'],
                    'already_in_pac'    => false,
                ], 200);
            }

            Log::error('Autentificatic rechazo la habilitacion de usuario', [
                'rut'    => $rut,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return response()->json(['message' => $mensajeError ?: 'AutentificaTic rechazo la solicitud.'], 422);
        }

        $res = $this->resolverFuncionario($rut, $token, $response, $dotacionIngresada);

        $responseBodyLower = strtolower($response->body());

        // 4. Si la API devolvio HTTP 200 (recurso ya creado anteriormente segun doc pág 5) o ya estaba en bitacora
        $yaExisteEnApi = $yaRegistradoEnBitacora || (
            $response->status() === 200 && (
                str_contains($responseBodyLower, 'ya ') ||
                str_contains($responseBodyLower, 'existe') ||
                str_contains($responseBodyLower, 'anterior')
            )
        );

        if ($yaExisteEnApi) {
            return response()->json([
                'status'            => 'already_exists',
                'already_exists'    => true,
                'message'           => "El RUN {$rut} ya existe y está ingresado en AutentificaTic.",
                'detalle'           => 'El funcionario ya se encuentra vinculado en la API institucional para pacdilocar.carabineros.cl.',
                'rut'               => $rut,
                'dotacion'          => $res['funcionario']?->Dotacion ?? $dotacionIngresada,
                'user'              => $res['user'],
                'funcionario'       => $res['funcionario'],
                'apellidos_nombres' => $res['apellidos_nombres'],
                'nombre_completo'   => $res['nombre_completo'],
                'create_url'        => $res['create_url'],
                'already_in_pac'    => false,
            ], 200);
        }

        return response()->json([
            'status'            => 'success',
            'already_exists'    => false,
            'message'           => $response->json('message') ?? 'Usuario vinculado y habilitado correctamente en AutentificaTic para pacdilocar.carabineros.cl.',
            'rut'               => $rut,
            'dotacion'          => $res['funcionario']?->Dotacion ?? $dotacionIngresada,
            'user'              => $res['user'],
            'funcionario'       => $res['funcionario'],
            'apellidos_nombres' => $res['apellidos_nombres'],
            'nombre_completo'   => $res['nombre_completo'],
            'create_url'        => $res['create_url'],
            'already_in_pac'    => false,
        ]);
    }

    /**
     * Muestra el listado de funcionarios ingresados en AutentificaTic.
     */
    public function listado(Request $request)
    {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        $token = $request->session()->get('autentificatic_token') ?: $request->cookie('token_de_acceso');

        // Extraer rubro dotacion oficial desde la sesion (mismo origen que layouts/admin.blade.php linea 115) o de la API institucional
        $apiDotacion = $request->session()->get('dotacion') ?: ($request->session()->get('descripcion_reparticion') ?: (optional(auth()->user()?->departamento)->detalle ?: 'DIRECCION DE LOGISTICA'));
        if (empty($apiDotacion) && $token) {
            try {
                $userApi = Http::timeout(4)
                    ->withoutVerifying()
                    ->withToken($token)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->get('http://autentificaticapi.carabineros.cl/api/auth/user');

                if ($userApi->ok()) {
                    $apiDotacion = $userApi->json('success.user.descripcion_reparticion')
                        ?? $userApi->json('success.user.dotacion')
                        ?? $userApi->json('user.descripcion_reparticion')
                        ?? $userApi->json('user.dotacion');
                    if ($apiDotacion) {
                        $request->session()->put('descripcion_reparticion', $apiDotacion);
                        $request->session()->put('dotacion', $apiDotacion);
                    }
                }
            } catch (\Exception $e) {
                // mantener fallback
            }
        }

        if (empty($apiDotacion)) {
            $apiDotacion = 'DIRECCION DE LOGISTICA';
        }

        // Si la tabla de funcionarios está vacía, sincronizar inicialmente con los usuarios existentes en PAC
        if (Funcionario::count() === 0) {
            $users = User::all();
            foreach ($users as $u) {
                $rutNorm = User::normalizarRut($u->Rut);
                Funcionario::firstOrCreate(
                    ['Rut' => $rutNorm],
                    [
                        'Codigo'    => 'S/C',
                        'Grado'     => 'S/G',
                        'Nombres'   => $u->name,
                        'Apellidos' => '',
                        'Dotacion'  => $apiDotacion,
                        'Email'     => $u->email ?: ($rutNorm . '@carabineros.cl'),
                    ]
                );
            }
        }

        // Reemplazar cualquier dotacion con placeholder ('DOTACION', 'PAC-DILOCAR', 'Sin Asignar', etc.) por la dotacion oficial extraida de la API o PAC
        try {
            Funcionario::all()->each(function ($f) {
                if (Funcionario::esDotacionInvalida($f->Dotacion)) {
                    $rutNorm = User::normalizarRut($f->Rut);
                    $uLocal = User::whereRaw("UPPER(REGEXP_REPLACE(\"Rut\", '[^0-9Kk]', '', 'g')) = ?", [$rutNorm])->first();
                    if ($uLocal && $uLocal->departamento && !Funcionario::esDotacionInvalida($uLocal->departamento->detalle)) {
                        $f->Dotacion = $uLocal->departamento->detalle;
                        $f->save();
                    }
                }
            });

            if (auth()->check() && auth()->user()->Rut) {
                Funcionario::where('Rut', User::normalizarRut(auth()->user()->Rut))
                    ->update(['Dotacion' => $apiDotacion]);
            }
        } catch (\Exception $e) {
            Log::warning('No se pudo actualizar dotacion masiva: ' . $e->getMessage());
        }

        $funcionarios = Funcionario::orderBy('Apellidos')->orderBy('Nombres')->get();

        // Extraer datos actualizados desde la API institucional para funcionarios existentes
        if ($token) {
            $consultasRealizadas = 0;
            foreach ($funcionarios as $func) {
                if ($consultasRealizadas >= 15) {
                    break;
                }
                $rutNorm = User::normalizarRut($func->Rut);
                if (empty($func->Codigo) || $func->Codigo === 'S/C' || empty($func->Apellidos) || Funcionario::esDotacionInvalida($func->Dotacion)) {
                    $apiData = $this->consultarDatosFuncionario($rutNorm, $token);
                    $consultasRealizadas++;
                    if ($apiData) {
                        $actualizado = $this->guardarFuncionarioLocal($rutNorm, $apiData);
                        if ($actualizado) {
                            $func->Dotacion  = $actualizado->Dotacion;
                            $func->Grado     = $actualizado->Grado;
                            $func->Codigo    = $actualizado->Codigo;
                            $func->Nombres   = $actualizado->Nombres;
                            $func->Apellidos = $actualizado->Apellidos;
                        }
                    }
                }
            }
        }

        // Obtener usuarios indexados por RUT normalizado con su estado de habilitación
        $usuariosPac = User::all()->keyBy(function ($u) {
            return User::normalizarRut($u->Rut);
        });

        // Asegurar que cada funcionario en la coleccion tenga su dotacion correcta
        foreach ($funcionarios as $func) {
            if (Funcionario::esDotacionInvalida($func->Dotacion)) {
                $rutNorm = User::normalizarRut($func->Rut);
                $uPac = $usuariosPac->get($rutNorm);
                $func->Dotacion = ($uPac && $uPac->departamento && !Funcionario::esDotacionInvalida($uPac->departamento->detalle))
                    ? $uPac->departamento->detalle
                    : 'Pendiente (se actualiza al iniciar sesión)';
            }
        }

        $usuariosRuts = $usuariosPac->keys()->toArray();

        return view('Autenti.listado', compact('funcionarios', 'usuariosPac', 'usuariosRuts'));
    }

    /**
     * Sincroniza/actualiza los datos de un funcionario específico desde la API institucional.
     */
    public function syncFuncionario(Request $request, string $rut)
    {
        $rutNorm = User::normalizarRut($rut);
        $token   = $request->session()->get('autentificatic_token') ?: $request->cookie('token_de_acceso');

        $res = $this->resolverFuncionario($rutNorm, $token);
        $func = $res['funcionario'];

        // Si el funcionario no tiene dotación válida en la BD local, intentar extraer su departamento de PAC
        $rutSql = "UPPER(REGEXP_REPLACE(\"Rut\", '[^0-9Kk]', '', 'g')) = ?";
        $uLocal = User::whereRaw($rutSql, [$rutNorm])->first();

        if ($func && Funcionario::esDotacionInvalida($func->Dotacion)) {
            if ($uLocal && $uLocal->departamento && !Funcionario::esDotacionInvalida($uLocal->departamento->detalle)) {
                $func->Dotacion = $uLocal->departamento->detalle;
                $func->save();
            }
        }

        $dotacionFinal = ($func && !empty($func->Dotacion) && !Funcionario::esDotacionInvalida($func->Dotacion))
            ? $func->Dotacion
            : ($uLocal?->departamento?->detalle ?? null);

        return response()->json([
            'message'           => 'Datos del funcionario consultados correctamente.',
            'funcionario'       => $func,
            'dotacion'          => $dotacionFinal,
            'grado'             => $func?->Grado,
            'codigo'            => $func?->Codigo,
            'apellidos_nombres' => $res['apellidos_nombres'],
            'nombre_completo'   => $res['nombre_completo'],
            'rut'               => $rutNorm,
            'already_in_pac'    => $uLocal !== null,
            'pac_habilitado'    => $uLocal ? (bool) $uLocal->habilitado : false,
        ]);
    }

    /**
     * Resuelve los datos del funcionario desde la API, el response de registro o la BD local,
     * formateando el nombre en estilo institucional (Apellidos y Nombres).
     */
    private function resolverFuncionario(string $rut, ?string $token, ?\Illuminate\Http\Client\Response $response = null, ?string $dotacionManual = null): array
    {
        $funcionarioData = $token ? $this->consultarDatosFuncionario($rut, $token) : null;

        if (!$funcionarioData && $response && $response->successful()) {
            $respJson = $response->json();
            $funcionarioData = $respJson['user']
                ?? $respJson['success']['user']
                ?? $respJson['data']['user']
                ?? $respJson['data']
                ?? $respJson['funcionario']
                ?? null;
        }

        $funcionario = null;
        if ($funcionarioData) {
            $funcionario = $this->guardarFuncionarioLocal($rut, $funcionarioData, $dotacionManual);
        }

        if (!$funcionario) {
            $funcionario = Funcionario::where('Rut', $rut)->first();
            if ($funcionario && $dotacionManual) {
                $funcionario->Dotacion = $dotacionManual;
                $funcionario->save();
            }
            if ($funcionario && empty($funcionarioData)) {
                $funcionarioData = [
                    'primer_nombre'    => $funcionario->Nombres,
                    'apellido_paterno' => $funcionario->Apellidos,
                    'grado'            => $funcionario->Grado,
                    'dotacion'         => $funcionario->Dotacion,
                    'email'            => $funcionario->Email,
                ];
            }
        }

        $apellidosNombres = '';
        if ($funcionario && $funcionario->nombre_completo && $funcionario->nombre_completo !== 'Sin Nombre') {
            $apellidosNombres = $funcionario->nombre_completo;
        } elseif ($funcionarioData) {
            $ape = trim(($funcionarioData['apellido_paterno'] ?? '') . ' ' . ($funcionarioData['apellido_materno'] ?? ''));
            if (!$ape) {
                $ape = trim($funcionarioData['apellidos'] ?? ($funcionarioData['Apellidos'] ?? ''));
            }
            $nom = trim(($funcionarioData['primer_nombre'] ?? '') . ' ' . ($funcionarioData['segundo_nombre'] ?? ''));
            if (!$nom) {
                $nom = trim($funcionarioData['nombres'] ?? ($funcionarioData['Nombres'] ?? ''));
            }
            if ($ape && $nom) {
                $apellidosNombres = "{$ape}, {$nom}";
            } else {
                $apellidosNombres = $ape ?: $nom;
            }
        }

        $createUrl = route('admin.usuarios.create') . '?Rut=' . urlencode($rut) .
            ($apellidosNombres ? '&name=' . urlencode($apellidosNombres) : '');

        return [
            'user'              => $funcionarioData,
            'funcionario'       => $funcionario,
            'apellidos_nombres' => $apellidosNombres,
            'nombre_completo'   => $apellidosNombres,
            'create_url'        => $createUrl,
        ];
    }

    /**
     * Registra o actualiza un funcionario en la base de datos local con los datos de AutentificaTic.
     */
    private function guardarFuncionarioLocal(string $rut, array $d, ?string $dotacionManual = null): ?Funcionario
    {
        try {
            $nom = trim(($d['primer_nombre'] ?? '') . ' ' . ($d['segundo_nombre'] ?? ''));
            if (!$nom) {
                $nom = trim($d['nombres'] ?? ($d['Nombres'] ?? ($d['nombre'] ?? '')));
            }
            $ape = trim(($d['apellido_paterno'] ?? '') . ' ' . ($d['apellido_materno'] ?? ''));
            if (!$ape) {
                $ape = trim($d['apellidos'] ?? ($d['Apellidos'] ?? ''));
            }

            // Extraer la dotación de la API mediante análisis profundo de claves
            $dotacionApi = Funcionario::extraerDotacionDeArray($d);
            $dotacionVal = (!empty($dotacionApi) && !Funcionario::esDotacionInvalida($dotacionApi)) ? trim((string)$dotacionApi) : null;
            $existente = Funcionario::where('Rut', $rut)->first();

            // Prioridad:
            // 1. Dotación ingresada manualmente
            // 2. Dotación real traída desde la API institucional
            // 3. Dotación existente válida previamente guardada en BD
            // 4. Departamento del usuario en PAC
            // 5. Sesión institucional
            $dotacionFinal = (!empty($dotacionManual) && !Funcionario::esDotacionInvalida($dotacionManual))
                ? $dotacionManual
                : ($dotacionVal ?: ($existente && !empty($existente->Dotacion) && !Funcionario::esDotacionInvalida($existente->Dotacion) ? $existente->Dotacion : null));

            if (!$dotacionFinal || Funcionario::esDotacionInvalida($dotacionFinal)) {
                try {
                    $rutSql = "UPPER(REGEXP_REPLACE(\"Rut\", '[^0-9Kk]', '', 'g')) = ?";
                    $uLocal = User::whereRaw($rutSql, [$rut])->first();
                    $dotacionFinal = ($uLocal && $uLocal->departamento && !Funcionario::esDotacionInvalida($uLocal->departamento->detalle))
                        ? $uLocal->departamento->detalle
                        : 'Pendiente (se actualiza al iniciar sesión)';
                } catch (\Exception $exU) {
                    $dotacionFinal = 'Pendiente (se actualiza al iniciar sesión)';
                }
            }

            return Funcionario::updateOrCreate(
                ['Rut' => $rut],
                [
                    'Codigo'    => $d['codigo_funcionario'] ?? ($d['codigo'] ?? ($existente->Codigo ?? 'S/C')),
                    'Grado'     => $d['grado'] ?? ($d['Grado'] ?? ($existente->Grado ?? 'S/G')),
                    'Nombres'   => $nom ?: ($d['primer_nombre'] ?? ($d['Nombres'] ?? ($existente->Nombres ?? 'S/N'))),
                    'Apellidos' => $ape ?: ($d['apellido_paterno'] ?? ($d['Apellidos'] ?? ($existente->Apellidos ?? 'S/A'))),
                    'Dotacion'  => $dotacionFinal,
                    'Email'     => $d['correo_institucional'] ?? $d['email'] ?? ($rut . '@carabineros.cl'),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error al guardar funcionario localmente: ' . $e->getMessage(), ['rut' => $rut]);
            return null;
        }
    }

    /**
     * Consulta los datos institucionales del funcionario mediante la API de Autentificatic.
     */
    private function consultarDatosFuncionario(string $rut, string $token): ?array
    {
        $platformUrl = config('services.autentificatic.platform_url', 'http://pacdilocar.carabineros.cl/');
        $rutNorm = User::normalizarRut($rut);

        // Probar variantes de formato (sin guión, con guión, minúscula)
        $variantes = array_unique(array_filter([
            $rutNorm,
            strlen($rutNorm) >= 8 ? substr($rutNorm, 0, -1) . '-' . substr($rutNorm, -1) : null,
            strtolower($rutNorm),
            strlen($rutNorm) >= 8 ? strtolower(substr($rutNorm, 0, -1) . '-' . substr($rutNorm, -1)) : null,
            $rut,
        ]));

        foreach ($variantes as $rutQuery) {
            try {
                $userResponse = Http::timeout(6)
                    ->withoutVerifying()
                    ->withToken($token)
                    ->withHeaders([
                        'Accept'  => 'application/json',
                        'Origin'  => rtrim($platformUrl, '/'),
                        'Referer' => rtrim($platformUrl, '/') . '/',
                    ])
                    ->get(config('services.autentificatic.user_data_url') . '/' . $rutQuery);

                if ($userResponse->successful()) {
                    $json = $userResponse->json();

                    $userData = $json['user']
                        ?? $json['success']['user']
                        ?? $json['data']['user']
                        ?? $json['data']
                        ?? $json['funcionario']
                        ?? (isset($json['primer_nombre']) || isset($json['apellido_paterno']) || isset($json['Nombres']) || isset($json['descripcion_reparticion']) || isset($json['dotacion']) ? $json : null);

                    if ($userData && is_array($userData)) {
                        // Si la dotación está en el objeto raíz de la respuesta, fusionarla
                        $dotacionRoot = Funcionario::extraerDotacionDeArray($json);
                        if ($dotacionRoot && empty(Funcionario::extraerDotacionDeArray($userData))) {
                            $userData['descripcion_reparticion'] = $dotacionRoot;
                        }

                        Log::info("Consulta API funcionario exitosa [{$rutQuery}]", [
                            'dotacion'                => Funcionario::extraerDotacionDeArray($userData),
                            'grado'                   => $userData['grado'] ?? ($userData['Grado'] ?? null),
                            'codigo'                  => $userData['codigo'] ?? ($userData['codigo_funcionario'] ?? null),
                        ]);
                        return $userData;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Error al consultar datos del funcionario [{$rutQuery}]: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Deja en la bitacora el resultado de cada intento, con el codigo HTTP y la respuesta de AutentificaTic.
     */
    private function registrarBitacora(Request $request, string $rut, \Illuminate\Http\Client\Response $response, string $domain = 'pacdilocar.carabineros.cl'): void
    {
        Bitacora::create([
            'user_id'     => auth()->id(),
            'modulo'      => 'AutentificaTic',
            'accion'      => $response->successful() ? 'habilitar_autentificatic' : 'habilitar_autentificatic_error',
            'descripcion' => 'Habilitacion de funcionario en AutentificaTic: ' . $rut
                . ' (DNS: ' . $domain . ')'
                . ' | HTTP ' . $response->status()
                . ' | ' . \Illuminate\Support\Str::limit($response->body(), 500),
            'ip'          => $request->ip(),
            'user_agent'  => $request->userAgent(),
        ]);
    }

    /**
     * Elimina/desvincula un usuario en AutentificaTic API para pacdilocar.carabineros.cl.
     * Documentación oficial institucional:
     * DELETE http://autentificaticapi.carabineros.cl/api/institutional-app-user-from-external-app
     * Headers: Authorization: Bearer <token>, Accept: application/json, Content-Type: application/x-www-form-urlencoded
     * Body: rut: <rut del usuario>
     */
    public static function eliminarUsuarioEnApi(string $rut, ?string $token = null): array
    {
        $token = $token ?: (session('autentificatic_token') ?: request()->cookie('token_de_acceso'));
        $rutNorm = User::normalizarRut($rut);
        $deleteUrl = config('services.autentificatic.register_url', 'http://autentificaticapi.carabineros.cl/api/institutional-app-user-from-external-app');
        $platformUrl = config('services.autentificatic.platform_url', 'http://pacdilocar.carabineros.cl/');
        $platformDomain = config('services.autentificatic.platform_domain', 'pacdilocar.carabineros.cl');

        if (!$token) {
            return [
                'success' => false,
                'status'  => 401,
                'message' => 'No hay sesión activa institucional para autenticar en AutentificaTic.',
            ];
        }

        // Variantes de formato de RUN institucional
        $variantesRut = array_unique(array_filter([
            $rutNorm,
            strlen($rutNorm) >= 8 ? substr($rutNorm, 0, -1) . '-' . substr($rutNorm, -1) : null,
            strtolower($rutNorm),
        ]));

        $lastResponse = null;
        $fueExitoso = false;

        foreach ($variantesRut as $rutQuery) {
            try {
                $response = Http::timeout(10)
                    ->withoutVerifying()
                    ->withToken($token)
                    ->withHeaders([
                        'Accept'       => 'application/json',
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Origin'       => rtrim($platformUrl, '/'),
                        'Referer'      => rtrim($platformUrl, '/') . '/',
                    ])
                    ->asForm()
                    ->delete($deleteUrl, [
                        'rut'      => $rutQuery,
                        'platform' => $platformDomain,
                    ]);

                $lastResponse = $response;

                if ($response->successful()) {
                    $fueExitoso = true;
                    break;
                }
            } catch (\Exception $e) {
                Log::warning("Error al llamar DELETE a AutentificaTic para {$rutQuery}: " . $e->getMessage());
            }
        }

        $statusCode = $lastResponse ? $lastResponse->status() : 500;
        $bodyText   = $lastResponse ? $lastResponse->body() : 'Sin respuesta de API';
        $jsonResp   = $lastResponse ? $lastResponse->json() : null;

        // Registrar en bitácora institucional
        if (auth()->check()) {
            try {
                Bitacora::create([
                    'user_id'     => auth()->id(),
                    'modulo'      => 'AutentificaTic',
                    'accion'      => $fueExitoso ? 'eliminar_autentificatic' : 'eliminar_autentificatic_error',
                    'descripcion' => "Eliminacion de usuario en AutentificaTic: {$rutNorm} (DNS: {$platformDomain}) | HTTP {$statusCode} | " . \Illuminate\Support\Str::limit($bodyText, 500),
                    'ip'          => request()->ip(),
                    'user_agent'  => request()->userAgent(),
                ]);
            } catch (\Exception $exBit) {}
        }

        return [
            'success'  => $fueExitoso,
            'status'   => $statusCode,
            'message'  => $jsonResp['message'] ?? ($fueExitoso ? 'Funcionario eliminado y desvinculado exitosamente de AutentificaTic.' : 'AutentificaTic rechazó la solicitud de eliminación.'),
            'response' => $jsonResp ?? $bodyText,
        ];
    }

    /**
     * Endpoint para eliminar/desvincular un funcionario de AutentificaTic y de la base local.
     */
    public function eliminarUsuario(Request $request, ?string $rut = null)
    {
        $rut = $rut ?: $request->input('rut') ?: $request->input('rut_funcionario');
        $rutNorm = User::normalizarRut($rut);

        if (strlen($rutNorm) < 8) {
            return response()->json(['message' => 'El RUN ingresado no es válido.'], 422);
        }

        $token = $request->session()->get('autentificatic_token') ?: $request->cookie('token_de_acceso');
        if (!$token) {
            return response()->json(['message' => 'Su sesión institucional ha caducado. Cierre el sistema y vuelva a ingresar.'], 401);
        }

        // 1. Invocar la eliminación en AutentificaTic API
        $resApi = self::eliminarUsuarioEnApi($rutNorm, $token);

        // 2. Si la API lo eliminó o respondió 404 (ya no estaba registrado en la API)
        if ($resApi['success'] || $resApi['status'] === 404) {
            // Eliminar de funcionarios locales
            Funcionario::where('Rut', $rutNorm)->delete();

            return response()->json([
                'status'  => 'success',
                'message' => "El RUN {$rutNorm} ha sido eliminado y desvinculado de AutentificaTic para pacdilocar.carabineros.cl.",
                'rut'     => $rutNorm,
            ], 200);
        }

        return response()->json([
            'status'  => 'error',
            'message' => $resApi['message'] ?: 'No se pudo eliminar el funcionario en AutentificaTic.',
            'detalle' => $resApi['response'] ?? null,
        ], $resApi['status'] >= 400 && $resApi['status'] < 600 ? $resApi['status'] : 422);
    }

    /**
     * Permite al administrador actualizar la dotación oficial de un funcionario.
     */
    public function updateDotacion(Request $request, string $rut)
    {
        $request->validate([
            'dotacion' => 'required|string|max:255',
        ]);

        $rutNorm  = User::normalizarRut($rut);
        $dotacion = trim($request->input('dotacion'));

        $func = Funcionario::where('Rut', $rutNorm)->first();
        if ($func) {
            $func->Dotacion = $dotacion;
            $func->save();
        }

        $rutSql = "UPPER(REGEXP_REPLACE(\"Rut\", '[^0-9Kk]', '', 'g')) = ?";
        $uLocal = User::whereRaw($rutSql, [$rutNorm])->first();
        if ($uLocal) {
            $uLocal->Dotacion = $dotacion;
            $uLocal->save();
        }

        Bitacora::create([
            'user_id'     => auth()->id(),
            'modulo'      => 'AutentificaTic',
            'accion'      => 'actualizar_dotacion',
            'descripcion' => "Se actualizó la dotación del funcionario RUN {$rutNorm} a '{$dotacion}'.",
            'ip'          => $request->ip(),
            'user_agent'  => $request->userAgent(),
        ]);

        return response()->json([
            'success'  => true,
            'message'  => "Dotación actualizada correctamente a '{$dotacion}'.",
            'dotacion' => $dotacion,
        ]);
    }
}
