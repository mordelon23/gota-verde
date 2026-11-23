<?php

namespace App\Services;

use App\Models\Invitacion;
use App\Models\User;
use App\Models\Vivienda;
use App\Notifications\InvitacionAVivienda;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Servicio de invitaciones (RF-20, CU-13 y CU-14).
 */
class InvitacionesService
{
    /**
     * @return array{invitacion: Invitacion, enlace: string, correoEnviado: bool}
     */
    public function invitar(Vivienda $vivienda, User $anfitrion, string $email): array
    {
        $email = Str::lower(trim($email));

        if ($email === Str::lower($anfitrion->email)) {
            throw ValidationException::withMessages(['email' => 'Ya eres propietario de esta vivienda. Ingresa el correo de otra persona.']);
        }

        $tokenPlano = Str::random(64);
        $enlace = route('invitaciones.mostrar', $tokenPlano);
        $apiKey = config('services.resend.key');

        $invitacion = DB::transaction(function () use ($vivienda, $anfitrion, $email, $tokenPlano, $enlace, $apiKey): Invitacion {
            Vivienda::query()->whereKey($vivienda->id)->lockForUpdate()->firstOrFail();

            // CU-13 4a: el correo ya pertenece a un miembro.
            if ($vivienda->usuarios()->whereRaw('LOWER(users.email) = ?', [$email])->exists()) {
                throw ValidationException::withMessages(['email' => 'Esa persona ya es miembro de la vivienda.']);
            }

            // Una invitación nueva reemplaza a la pendiente anterior para el mismo correo.
            $vivienda->invitaciones()
                ->where('email', $email)
                ->where('estado', 'pendiente')
                ->update(['estado' => 'cancelada']);

            $invitacion = $vivienda->invitaciones()->create([
                'invitado_por' => $anfitrion->id,
                'email' => $email,
                'token' => hash('sha256', $tokenPlano),
                'estado' => 'pendiente',
                'expira_en' => now()->addHours(Invitacion::VIGENCIA_HORAS),
            ]);

            if (filled($apiKey)) {
                $this->enviarCorreo($invitacion, $enlace, $apiKey);
            }

            return $invitacion;
        });

        return ['invitacion' => $invitacion, 'enlace' => $enlace, 'correoEnviado' => filled($apiKey)];
    }

    private function enviarCorreo(Invitacion $invitacion, string $enlace, string $apiKey): void
    {
        $destinatario = (new AnonymousNotifiable)->route('mail', $invitacion->email);
        $correo = (new InvitacionAVivienda($invitacion, $enlace))->toMail($destinatario);

        try {
            $respuesta = Http::withToken($apiKey)
                ->acceptJson()
                ->connectTimeout(3)
                ->timeout(8)
                ->post('https://api.resend.com/emails', [
                    'from' => config('mail.from.address'),
                    'to' => [$invitacion->email],
                    'subject' => $correo->subject,
                    'html' => $correo->render()->toHtml(),
                ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages(['email' => 'No pudimos conectar con el servicio de correo. Intenta de nuevo.']);
        }

        if (! $respuesta->successful() || ! filled($respuesta->json('id'))) {
            throw ValidationException::withMessages(['email' => 'El servicio de correo no aceptó la invitación. Intenta de nuevo.']);
        }
    }

    public function buscarPorToken(string $tokenPlano): ?Invitacion
    {
        return Invitacion::with(['vivienda', 'anfitrion'])
            ->where('token', hash('sha256', $tokenPlano))
            ->first();
    }

    /** @return Builder<Invitacion> */
    public function pendientesPara(User $usuario): Builder
    {
        return Invitacion::query()
            ->where('email', Str::lower($usuario->email))
            ->where('estado', 'pendiente')
            ->where('expira_en', '>', now());
    }

    /** CU-14: el invitado acepta y queda vinculado con rol de miembro. */
    public function aceptar(Invitacion $invitacion, User $usuario): void
    {
        if (Str::lower($usuario->email) !== Str::lower($invitacion->email)) {
            throw ValidationException::withMessages([
                'invitacion' => 'Esta invitación fue enviada a otro correo. Inicia sesión con la cuenta de '.$invitacion->email.'.',
            ]);
        }

        DB::transaction(function () use ($invitacion, $usuario): void {
            $vivienda = Vivienda::query()->whereKey($invitacion->vivienda_id)->lockForUpdate()->firstOrFail();
            $pendiente = Invitacion::query()->whereKey($invitacion->id)->lockForUpdate()->firstOrFail();

            if (! $pendiente->estaVigente()) {
                throw ValidationException::withMessages(['invitacion' => 'La invitación ya no es válida o expiró.']);
            }

            if (! $vivienda->esMiembro($usuario)) {
                $vivienda->usuarios()->attach($usuario->id, ['rol' => Vivienda::ROL_MIEMBRO]);
            }

            $pendiente->update(['estado' => 'aceptada', 'aceptada_en' => now()]);
        });
    }

    /** Planificador: marca como expiradas las invitaciones vencidas. */
    public function expirarVencidas(): int
    {
        return Invitacion::query()
            ->where('estado', 'pendiente')
            ->where('expira_en', '<', now())
            ->update(['estado' => 'expirada']);
    }
}
