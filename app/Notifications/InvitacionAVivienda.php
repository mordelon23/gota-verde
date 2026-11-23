<?php

namespace App\Notifications;

use App\Models\Invitacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * DS-05: plantilla de correo de invitación.
 */
class InvitacionAVivienda extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invitacion $invitacion, public string $enlace) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $vivienda = $this->invitacion->vivienda;
        $anfitrion = $this->invitacion->anfitrion;

        return (new MailMessage)
            ->subject('Te invitaron a una vivienda en GotaVerde')
            ->greeting('¡Hola!')
            ->line("{$anfitrion->name} te invitó a unirte a la vivienda \"{$vivienda->alias}\" en GotaVerde.")
            ->line('Juntos podrán registrar hábitos y cumplir metas de ahorro de agua.')
            ->action('Aceptar invitación', $this->enlace)
            ->line('El enlace vence en '.Invitacion::VIGENCIA_HORAS.' horas. Si no esperabas este correo, puedes ignorarlo.')
            ->salutation('Equipo GotaVerde');
    }
}
