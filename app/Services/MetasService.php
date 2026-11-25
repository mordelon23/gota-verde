<?php

namespace App\Services;

use App\Models\Meta;
use App\Models\RegistroHabito;
use App\Models\Vivienda;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Servicio de metas (RF-08, RF-18, RF-21 y CU-19).
 * Las metas son de la vivienda: se suma lo que registran todos sus miembros.
 */
class MetasService
{
    /**
     * Valor alcanzado y objetivo de una meta.
     *
     * @return array{actual: float, objetivo: float, unidad: string}
     */
    public function avance(Meta $meta): array
    {
        $registros = RegistroHabito::query()
            ->where('vivienda_id', $meta->vivienda_id)
            ->where('fecha', '>=', $meta->fecha_inicio->toDateString())
            ->where('fecha', '<', $meta->fecha_fin->copy()->addDay()->toDateString())
            ->when($meta->actividad_id, fn ($consulta) => $consulta->where('actividad_id', $meta->actividad_id));

        if ($meta->tipo === Meta::CUANTITATIVA) {
            return [
                'actual' => round((float) $registros->sum('litros_ahorrados'), 2),
                'objetivo' => (float) $meta->objetivo_litros,
                'unidad' => 'L ahorrados',
            ];
        }

        // Cualitativa: días en que TODOS los registros de la actividad respetaron el límite.
        $diasCumplidos = $registros
            ->selectRaw('fecha, MAX(cantidad) AS maxima')
            ->groupBy('fecha')
            ->get()
            ->filter(fn ($dia) => (float) $dia->maxima <= (float) $meta->limite_cantidad)
            ->count();

        return [
            'actual' => (float) $diasCumplidos,
            'objetivo' => (float) $meta->dias_objetivo,
            'unidad' => 'días',
        ];
    }

    public function progreso(Meta $meta): float
    {
        $avance = $this->avance($meta);

        if ($avance['objetivo'] <= 0) {
            return 0;
        }

        return round(min(100, $avance['actual'] / $avance['objetivo'] * 100), 2);
    }

    /**
     * Actualiza progreso y estado. Devuelve true si la meta se acaba de cumplir.
     */
    public function evaluar(Meta $meta, ?Carbon $hoy = null): bool
    {
        if ($meta->estado !== 'activa') {
            return false;
        }

        $hoy ??= today();
        $meta->progreso = $this->progreso($meta);
        $recienCumplida = false;

        if ($meta->progreso >= 100) {
            $meta->estado = 'cumplida';
            $meta->cumplida_en = now();
            $recienCumplida = true;
        } elseif ($meta->fecha_fin->lt($hoy->copy()->startOfDay())) {
            $meta->estado = 'vencida';
        }

        $meta->save();

        return $recienCumplida;
    }

    /**
     * Evalúa las metas activas de una vivienda.
     *
     * @return Collection<int, Meta> metas que se acaban de cumplir
     */
    public function evaluarVivienda(Vivienda $vivienda, ?Carbon $hoy = null): Collection
    {
        return $vivienda->metas()
            ->where('estado', 'activa')
            ->get()
            ->filter(fn (Meta $meta) => $this->evaluar($meta, $hoy))
            ->values();
    }

    /**
     * Evaluación diaria de todas las metas activas (planificador).
     *
     * @return Collection<int, Meta> metas que se acaban de cumplir
     */
    public function evaluarTodas(?Carbon $hoy = null): Collection
    {
        return Meta::query()
            ->where('estado', 'activa')
            ->get()
            ->filter(fn (Meta $meta) => $this->evaluar($meta, $hoy))
            ->values();
    }
}
