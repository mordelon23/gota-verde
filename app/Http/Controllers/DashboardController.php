<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\InvitacionesService;
use App\Services\ViviendasDelUsuario;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * RF-19 (CU-12): dashboard individual por vivienda y global sumado.
 */
class DashboardController extends Controller
{
    public function index(Request $request, ViviendasDelUsuario $servicio, DashboardService $dashboard, InvitacionesService $invitaciones): View
    {
        $viviendas = $servicio->todas($request);
        $vivienda = $servicio->activa($request, $viviendas);
        $modo = $request->query('modo') === 'global' ? 'global' : 'individual';

        $datos = match (true) {
            ! $vivienda => [],
            $modo === 'global' => $dashboard->global($viviendas),
            default => $dashboard->individual($vivienda, $request->user()),
        };

        if ($vivienda && $modo === 'individual') {
            $datos['mostrarPrimeraMeta'] = $datos['registros']->isNotEmpty() && ! $vivienda->metas()->exists();
            $datos['habitoParaMeta'] = $datos['mostrarPrimeraMeta']
                ? $datos['registros']->first(fn ($registro) => $registro->actividad->activa)
                : null;
        }

        return view($modo === 'global' && $vivienda ? 'dashboard.global' : 'dashboard.index', [
            'viviendas' => $viviendas,
            'vivienda' => $vivienda,
            'modo' => $modo,
            'hayInvitaciones' => $invitaciones->pendientesPara($request->user())->exists(),
            ...$datos,
        ]);
    }
}
