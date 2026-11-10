<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class Funcionario extends Model
{
    use HasFactory;
    use HasRoles;
    protected $fillable = [
        'Rut',
        'Codigo',
        'Grado',
        'Nombres',
        'Apellidos',
        'Dotacion',
        'Email',
    ];

    public function getNombreCompletoAttribute(): string
    {
        $nom = trim((string) $this->Nombres);
        $ape = trim((string) $this->Apellidos);
        if ($nom && $ape) {
            return "{$ape}, {$nom}";
        }
        return $nom ?: ($ape ?: 'Sin Nombre');
    }

    /**
     * Valida si un valor de dotacion es un placeholder o valor no valido (ej: 'DOTACION', 'PAC-DILOCAR', 'S/D', 'Sin Asignar').
     */
    public static function esDotacionInvalida(?string $dotacion): bool
    {
        if (empty($dotacion)) {
            return true;
        }
        $limpio = strtoupper(trim((string)$dotacion));
        $sinAcento = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú'], ['A', 'E', 'I', 'O', 'U'], $limpio);
        $invalidos = [
            'DOTACION',
            'DOTACION OFICIAL',
            'PAC-DILOCAR',
            'PAC DILOCAR',
            'PACDILOCAR',
            'PENDIENTE',
            'PENDIENTE (SE ACTUALIZA AL INICIAR SESION)',
            'PENDIENTE (AL INICIAR SESION)',
            'PENDIENTE (SE ACTUALIZA AL INICIAR SESIÓN)',
            'PENDIENTE (AL INICIAR SESIÓN)',
            'SIN ASIGNAR',
            'SIN ASIGNACION',
            'SIN ASIGNACIÓN',
            'S/D',
            'S/N',
            'SIN DOTACION',
            'SIN INFORMACION',
            'N/A',
            'NULL',
            'NONE',
            'UNDEFINED',
            ''
        ];
        return in_array($sinAcento, $invalidos, true);
    }

    /**
     * Extrae de forma recursiva o exhaustiva la dotación/repartición desde la respuesta de la API institucional.
     */
    public static function extraerDotacionDeArray(array $d): ?string
    {
        // 1. Claves directas comunes en la API institucional
        $candidatos = [
            'descripcion_reparticion',
            'Descripcion_Reparticion',
            'descripcionReparticion',
            'reparticion_descripcion',
            'desc_reparticion',
            'des_reparticion',
            'nom_reparticion',
            'nombre_reparticion',
            'reparticion_nombre',
            'dotacion',
            'Dotacion',
            'dotación',
            'Dotación',
            'dotacion_actual',
            'dotacion_nombre',
            'reparticion',
            'Reparticion',
            'repartición',
            'Repartición',
            'unidad',
            'Unidad',
            'nombre_unidad',
            'unidad_descripcion',
            'desc_unidad',
            'glosa_reparticion',
            'glosa_unidad',
            'dependencia',
            'destino',
        ];

        foreach ($candidatos as $k) {
            if (isset($d[$k])) {
                $val = $d[$k];
                if (is_string($val) && !self::esDotacionInvalida($val)) {
                    return trim($val);
                }
                if (is_array($val)) {
                    $sub = self::extraerDotacionDeArray($val);
                    if ($sub && !self::esDotacionInvalida($sub)) {
                        return $sub;
                    }
                }
            }
        }

        // 2. Contenedores anidados comunes
        $subContenedores = ['datos_institucionales', 'datos_laborales', 'institucional', 'laboral', 'datos', 'user', 'funcionario', 'dotacion_info', 'reparticion_info'];
        foreach ($subContenedores as $subKey) {
            if (isset($d[$subKey]) && is_array($d[$subKey])) {
                $sub = self::extraerDotacionDeArray($d[$subKey]);
                if ($sub && !self::esDotacionInvalida($sub)) {
                    return $sub;
                }
            }
        }

        // 3. Claves de texto dentro de un objeto de repartición/unidad
        foreach (['glosa', 'nombre', 'descripcion', 'detalle', 'desc'] as $textKey) {
            if (isset($d[$textKey]) && is_string($d[$textKey]) && !self::esDotacionInvalida($d[$textKey])) {
                return trim($d[$textKey]);
            }
        }

        return null;
    }

    public function getDotacionAttribute($value): string
    {
        if (self::esDotacionInvalida($value)) {
            // Intentar obtener la dotación o departamento asignado en PAC si existe
            try {
                $rutNorm = User::normalizarRut($this->Rut);
                $rutSql = "UPPER(REGEXP_REPLACE(\"Rut\", '[^0-9Kk]', '', 'g')) = ?";
                $usuario = User::whereRaw($rutSql, [$rutNorm])->first();
                if ($usuario && $usuario->departamento && !self::esDotacionInvalida($usuario->departamento->detalle)) {
                    return $usuario->departamento->detalle;
                }
                if ($usuario && !empty($usuario->Dotacion) && !self::esDotacionInvalida($usuario->Dotacion)) {
                    return $usuario->Dotacion;
                }
            } catch (\Exception $e) {}

            // Solo si el funcionario consultado es el mismo usuario logueado en la sesión se usa su dotación de sesión
            if (auth()->check() && User::normalizarRut(auth()->user()->Rut) === User::normalizarRut($this->Rut)) {
                return session('descripcion_reparticion') ?: (session('dotacion') ?: 'DIRECCION DE LOGISTICA');
            }

            return 'Pendiente (se actualiza al iniciar sesión)';
        }
        return $value;
    }

    /**
     * Consulta los datos del funcionario en la API de AutentificaTic.
     */
    public static function consultarApi(string $rut, ?string $token): ?array
    {
        if (!$token) {
            return null;
        }

        $rutNorm = User::normalizarRut($rut);
        $platformUrl = config('services.autentificatic.platform_url', 'http://pacdilocar.carabineros.cl/');

        try {
            $userResponse = \Illuminate\Support\Facades\Http::timeout(8)
                ->withoutVerifying()
                ->withToken($token)
                ->withHeaders([
                    'Accept'  => 'application/json',
                    'Origin'  => rtrim($platformUrl, '/'),
                    'Referer' => rtrim($platformUrl, '/') . '/',
                ])
                ->get(config('services.autentificatic.user_data_url') . '/' . $rutNorm);

            if ($userResponse->successful()) {
                $json = $userResponse->json();
                return $json['user']
                    ?? $json['success']['user']
                    ?? $json['data']['user']
                    ?? $json['data']
                    ?? $json['funcionario']
                    ?? (isset($json['primer_nombre']) || isset($json['apellido_paterno']) || isset($json['Nombres']) ? $json : null);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudieron obtener datos del funcionario desde API: ' . $e->getMessage(), ['rut' => $rutNorm]);
        }

        return null;
    }

    /**
     * Guarda o actualiza los datos del funcionario obtenidos desde la API.
     */
    public static function guardarDesdeApi(string $rut, array $d): ?self
    {
        $rutNorm = User::normalizarRut($rut);

        try {
            $nom = trim(($d['primer_nombre'] ?? '') . ' ' . ($d['segundo_nombre'] ?? ''));
            if (!$nom) {
                $nom = trim($d['nombres'] ?? ($d['Nombres'] ?? ($d['nombre'] ?? '')));
            }
            $ape = trim(($d['apellido_paterno'] ?? '') . ' ' . ($d['apellido_materno'] ?? ''));
            if (!$ape) {
                $ape = trim($d['apellidos'] ?? ($d['Apellidos'] ?? ''));
            }

            $dotacionVal = self::extraerDotacionDeArray($d);
            $existente = self::where('Rut', $rutNorm)->first();
            $dotacionFinal = $dotacionVal ?: ($existente && !empty($existente->Dotacion) && !self::esDotacionInvalida($existente->Dotacion) ? $existente->Dotacion : null);
            if (!$dotacionFinal) {
                try {
                    $uLocal = User::whereRaw("UPPER(REGEXP_REPLACE(\"Rut\", '[^0-9Kk]', '', 'g')) = ?", [$rutNorm])->first();
                    $dotacionFinal = ($uLocal && $uLocal->departamento && !self::esDotacionInvalida($uLocal->departamento->detalle))
                        ? $uLocal->departamento->detalle
                        : 'Pendiente (se actualiza al iniciar sesión)';
                } catch (\Exception $exU) {
                    $dotacionFinal = 'Pendiente (se actualiza al iniciar sesión)';
                }
            }

            return self::updateOrCreate(
                ['Rut' => $rutNorm],
                [
                    'Codigo'    => $d['codigo_funcionario'] ?? ($d['codigo'] ?? 'S/C'),
                    'Grado'     => $d['grado'] ?? 'S/G',
                    'Nombres'   => $nom ?: ($d['primer_nombre'] ?? ($d['Nombres'] ?? 'S/N')),
                    'Apellidos' => $ape ?: ($d['apellido_paterno'] ?? ($d['Apellidos'] ?? 'S/A')),
                    'Dotacion'  => $dotacionFinal,
                    'Email'     => $d['correo_institucional'] ?? $d['email'] ?? ($rutNorm . '@carabineros.cl'),
                ]
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al guardar funcionario: ' . $e->getMessage(), ['rut' => $rutNorm]);
            return null;
        }
    }
}
