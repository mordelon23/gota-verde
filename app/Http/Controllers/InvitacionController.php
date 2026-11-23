<?php

namespace App\Http\Controllers;

use App\Models\Invitacion;
use App\Services\InvitacionesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * CU-14: aceptar invitación.
 */
class InvitacionController extends Controller
{
    public function recibidas(Request $request, InvitacionesService $invitaciones): View
    {
        $pendientes = $invitaciones->pendientesPara($request->user())->with(['vivienda', 'anfitrion'])->latest()->get();

        return view('invitaciones.recibidas', compact('pendientes'));
    }

    public function mostrar(Request $request, string $token, InvitacionesService $invitaciones): View
    {
        $invitacion = $invitaciones->buscarPorToken($token);
        abort_unless($invitacion, 404);

        if (! $request->user()) {
            // Al iniciar sesión o registrarse, el usuario regresa a esta página.
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return view('invitaciones.mostrar', compact('invitacion', 'token'));
    }

    public function aceptarRecibida(Request $request, int $invitacion, InvitacionesService $invitaciones): RedirectResponse
    {
        $pendiente = $invitaciones->pendientesPara($request->user())->findOrFail($invitacion);

        return $this->confirmar($request, $pendiente, $invitaciones);
    }

    public function abrirEnlace(Request $request, InvitacionesService $invitaciones): RedirectResponse
    {
        $datos = $request->validate(['enlace' => ['required', 'string', 'max:2048']]);
        $ruta = parse_url($datos['enlace'], PHP_URL_PATH);

        if (! is_string($ruta) || preg_match('~/invitaciones/([A-Za-z0-9]{64})/?\z~', $ruta, $partes) !== 1
            || ! $invitaciones->buscarPorToken($partes[1])) {
            throw ValidationException::withMessages(['enlace' => 'El enlace no corresponde a una invitación de esta instalación de GotaVerde.']);
        }

        return redirect()->route('invitaciones.mostrar', $partes[1]);
    }

    public function aceptar(Request $request, string $token, InvitacionesService $invitaciones): RedirectResponse
    {
        $invitacion = $invitaciones->buscarPorToken($token);
        abort_unless($invitacion, 404);

        return $this->confirmar($request, $invitacion, $invitaciones);
    }

    private function confirmar(Request $request, Invitacion $invitacion, InvitacionesService $invitaciones): RedirectResponse
    {
        $invitaciones->aceptar($invitacion, $request->user());
        $request->session()->put('vivienda_activa_id', $invitacion->vivienda_id);

        return redirect()->route('dashboard')->with('exito', 'Ahora eres miembro de '.$invitacion->vivienda->alias.'.');
    }
}
