<?php

namespace App\Http\Controllers;

use App\Models\Logro;
use App\Models\Racha;
use App\Models\Recompensa;
use App\Models\User;
use App\Services\GamificacionService;
use App\Services\ViviendasDelUsuario;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU-11: logros, rachas y recompensas.
 */
class LogroController extends Controller
{
    public function index(Request $request, ViviendasDelUsuario $servicio, GamificacionService $gamificacion): View
    {
        $usuario = $request->user();
        $vivienda = $servicio->activa($request);

        // Mantiene la racha al día aunque el planificador no haya corrido.
        $racha = $vivienda ? $gamificacion->actualizarRacha($usuario, $vivienda) : null;
        $obtenidos = $usuario->logros()->get()->keyBy('id');
        $valores = $gamificacion->valoresDeUsuario($usuario);

        $logros = Logro::orderBy('criterio')->orderBy('umbral')->get()->keyBy('clave')->map(fn (Logro $logro) => [
            'logro' => $logro,
            'obtenido' => $obtenidos->get($logro->id),
            'avance' => min(100, round(($valores[$logro->criterio] ?? 0) / max(1, $logro->umbral) * 100)),
        ]);
        $primerosHitos = $logros->only(['primer_registro', 'racha_3'])->sortBy('logro.umbral');

        $recompensasObtenidas = $usuario->recompensas()->pluck('recompensas.id');
        $proximaRecompensa = Recompensa::whereNotIn('id', $recompensasObtenidas)->orderBy('puntos_requeridos')->first();

        return view('logros.index', [
            'usuario' => $usuario,
            'vivienda' => $vivienda,
            'racha' => $racha,
            'mejorRachaGlobal' => (int) Racha::where('user_id', $usuario->id)->max('mejor'),
            'primerosHitos' => $primerosHitos,
            'logros' => $logros->except($primerosHitos->keys()->all()),
            'recompensas' => Recompensa::orderBy('puntos_requeridos')->get(),
            'recompensasObtenidas' => $recompensasObtenidas,
            'proximaRecompensa' => $proximaRecompensa,
            'puntosPorNivel' => User::PUNTOS_POR_NIVEL,
        ]);
    }
}
