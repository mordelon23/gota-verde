<?php

namespace App\Providers;

use App\Models\Recompensa;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as BladeView;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('partials.recompensas', function (BladeView $vista): void {
            $vista->with([
                'recompensas' => Recompensa::query()->orderBy('puntos_requeridos')->get(),
                'empresas' => array_filter(config('services.recompensas.empresas', [])),
            ]);
        });

        // RF-03: correo de recuperación de contraseña en español.
        ResetPassword::toMailUsing(function (object $usuario, string $token): MailMessage {
            $enlace = route('password.reset', ['token' => $token, 'email' => $usuario->getEmailForPasswordReset()]);
            $minutos = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('Restablece tu contraseña de GotaVerde')
                ->greeting('Hola, '.$usuario->name)
                ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
                ->action('Restablecer contraseña', $enlace)
                ->line("El enlace vence en {$minutos} minutos.")
                ->line('Si no solicitaste el cambio, ignora este correo; tu contraseña seguirá igual.')
                ->salutation('Equipo GotaVerde');
        });
    }
}
