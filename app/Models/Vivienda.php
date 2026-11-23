<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vivienda extends Model
{
    public const ROL_PROPIETARIO = 'propietario';

    public const ROL_MIEMBRO = 'miembro';

    protected $table = 'viviendas';

    protected $fillable = [
        'alias', 'tipo', 'habitantes', 'alcaldia', 'codigo_postal', 'tarifa_m3',
    ];

    protected function casts(): array
    {
        return ['habitantes' => 'integer', 'tarifa_m3' => 'decimal:2'];
    }

    public static function alcaldiaPorCodigoPostal(string $codigoPostal): ?string
    {
        if (preg_match('/\A[0-9]{5}\z/', $codigoPostal) !== 1) {
            return null;
        }

        $zona = config('codigos_postales.cp'.substr($codigoPostal, 0, 2));

        return $zona !== null && in_array(substr($codigoPostal, 2), $zona['sufijos'], true)
            ? $zona['alcaldia']
            : null;
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'vivienda_usuario', 'vivienda_id', 'user_id')
            ->withPivot('rol')
            ->withTimestamps();
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroHabito::class, 'vivienda_id');
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(LecturaConsumo::class, 'vivienda_id');
    }

    public function metas(): HasMany
    {
        return $this->hasMany(Meta::class, 'vivienda_id');
    }

    public function invitaciones(): HasMany
    {
        return $this->hasMany(Invitacion::class, 'vivienda_id');
    }

    /** Rol del usuario dentro de esta vivienda, o null si no es miembro. */
    public function rolDe(User $usuario): ?string
    {
        $miembro = $this->usuarios()->whereKey($usuario->id)->first();

        return $miembro?->pivot->rol;
    }

    public function esMiembro(User $usuario): bool
    {
        return $this->usuarios()->whereKey($usuario->id)->exists();
    }

    public function esPropietario(User $usuario): bool
    {
        return $this->usuarios()
            ->whereKey($usuario->id)
            ->wherePivot('rol', self::ROL_PROPIETARIO)
            ->exists();
    }
}
