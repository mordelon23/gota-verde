<?php

namespace App\Http\Controllers;

use App\Models\Invitacion;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\InvitacionesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * RF-20 y RF-22: invitar y gestionar miembros (CU-13 y CU-15).
 */
class MiembroController extends Controller
{
    public function invitar(Request $request, Vivienda $vivienda, InvitacionesService $invitaciones): RedirectResponse
    {
        Gate::authorize('gestionarMiembros', $vivienda);

        $datos = $request->validate(['email' => ['required', 'email', 'max:255']]);

        $resultado = $invitaciones->invitar($vivienda, $request->user(), $datos['email']);

        $mensaje = $resultado['correoEnviado']
            ? 'Invitación enviada a '.$resultado['invitacion']->email.'. Vence en '.Invitacion::VIGENCIA_HORAS.' horas.'
            : 'Invitación creada. La otra persona puede aceptarla en Invitaciones o usar el enlace que le compartas.';

        // El enlace solo se puede mostrar ahora: en la base se guarda únicamente su hash.
        return back()
            ->with('exito', $mensaje)
            ->with('enlace_invitacion', $resultado['enlace']);
    }

    public function cancelarInvitacion(Vivienda $vivienda, Invitacion $invitacion): RedirectResponse
    {
        Gate::authorize('gestionarMiembros', $vivienda);
        abort_unless($invitacion->vivienda_id === $vivienda->id, 404);

        Invitacion::query()->whereKey($invitacion->id)->where('estado', 'pendiente')->update(['estado' => 'cancelada']);

        return back()->with('exito', 'Invitación cancelada.');
    }

    public function eliminar(Request $request, Vivienda $vivienda, User $usuario): RedirectResponse
    {
        Gate::authorize('gestionarMiembros', $vivienda);

        if ($usuario->is($request->user())) {
            return back()->withErrors(['miembro' => 'No puedes eliminarte a ti mismo. Usa "Salir de la vivienda".']);
        }

        abort_unless($vivienda->esMiembro($usuario), 404);

        if ($vivienda->esPropietario($usuario)) {
            return back()->withErrors(['miembro' => 'No puedes eliminar a otro propietario.']);
        }

        $vivienda->usuarios()->detach($usuario->id);

        return back()->with('exito', $usuario->name.' ya no tiene acceso a '.$vivienda->alias.'.');
    }

    public function salir(Request $request, Vivienda $vivienda): RedirectResponse
    {
        Gate::authorize('view', $vivienda);

        $propietarios = $vivienda->usuarios()->wherePivot('rol', Vivienda::ROL_PROPIETARIO)->count();

        if ($vivienda->esPropietario($request->user()) && $propietarios <= 1) {
            return back()->withErrors(['miembro' => 'Eres el único propietario. Elimina la vivienda en lugar de salir.']);
        }

        $vivienda->usuarios()->detach($request->user()->id);
        if ((int) $request->session()->get('vivienda_activa_id') === $vivienda->id) {
            $request->session()->forget('vivienda_activa_id');
        }

        return redirect()->away(route('viviendas.index', absolute: false), 303)
            ->with('exito', 'Ya no perteneces a la vivienda «'.$vivienda->alias.'».');
    }
}
