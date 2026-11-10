<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'Rut',
        'name',
        'email',
        'departamento_id',
        'password',
        'habilitado',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'habilitado'        => 'boolean',
    ];

    /**
     * Deja el RUT sin puntos, guion ni espacios y con la K en mayuscula (ej: 15.955.820-k -> 15955820K).
     */
    public static function normalizarRut(?string $rut): string
    {
        return strtoupper(preg_replace('/[^0-9kK]/', '', (string) $rut));
    }

    public function setRutAttribute($value): void
    {
        $this->attributes['Rut'] = self::normalizarRut($value);
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    public function funcionario()
    {
        return $this->hasOne(Funcionario::class, 'Rut', 'Rut');
    }

    public function getGradoAttribute(): string
    {
        return $this->funcionario?->Grado ?? $this->attributes['Grado'] ?? 'S/G';
    }

    public function getCodigoAttribute(): string
    {
        return $this->funcionario?->Codigo ?? $this->attributes['Codigo'] ?? 'S/C';
    }

    public function getDotacionAttribute(): string
    {
        $dot = $this->funcionario?->Dotacion;
        if (Funcionario::esDotacionInvalida($dot)) {
            $depDetalle = $this->departamento?->detalle;
            if (!empty($depDetalle) && !Funcionario::esDotacionInvalida($depDetalle)) {
                return $depDetalle;
            }
            if (auth()->check() && User::normalizarRut(auth()->user()->Rut) === User::normalizarRut($this->Rut)) {
                return session('descripcion_reparticion') ?: (session('dotacion') ?: 'DIRECCION DE LOGISTICA');
            }
            return 'Pendiente (se actualiza al iniciar sesión)';
        }
        return $dot;
    }
}
