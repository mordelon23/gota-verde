<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

// 'rol' y 'puntos' NO son asignables masivamente a propósito:
// así nadie puede volverse admin enviando un campo extra en un formulario.
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROL_USUARIO = 'usuario';

    public const ROL_ADMIN = 'admin';

    /** Puntos necesarios para subir un nivel. */
    public const PUNTOS_POR_NIVEL = 100;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'puntos' => 'integer',
        ];
    }

    public function esAdmin(): bool
    {
        return $this->rol === self::ROL_ADMIN;
    }

    public function nivel(): int
    {
        return intdiv((int) $this->puntos, self::PUNTOS_POR_NIVEL) + 1;
    }

    public function iniciales(): string
    {
        return Str::of($this->name)->squish()->explode(' ')->take(2)
            ->map(fn (string $nombre): string => Str::upper(Str::substr($nombre, 0, 1)))
            ->implode('');
    }

    public function viviendas(): BelongsToMany
    {
        return $this->belongsToMany(Vivienda::class, 'vivienda_usuario', 'user_id', 'vivienda_id')
            ->withPivot('rol')
            ->withTimestamps();
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroHabito::class, 'user_id');
    }

    public function rachas(): HasMany
    {
        return $this->hasMany(Racha::class, 'user_id');
    }

    public function logros(): BelongsToMany
    {
        return $this->belongsToMany(Logro::class, 'logro_usuario', 'user_id', 'logro_id')
            ->withPivot('obtenido_en')
            ->withTimestamps();
    }

    public function recompensas(): BelongsToMany
    {
        return $this->belongsToMany(Recompensa::class, 'recompensa_usuario', 'user_id', 'recompensa_id')
            ->withPivot('otorgada_en')
            ->withTimestamps();
    }
}
