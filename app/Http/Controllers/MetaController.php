<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Meta;
use App\Services\MetasService;
use App\Services\ViviendasDelUsuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * RF-08 y RF-18 (CU-10): metas por vivienda.
 */
class MetaController extends Controller
{
    public function index(Request $request, ViviendasDelUsuario $servicio, MetasService $metas): View|RedirectResponse
    {
        $viviendas = $servicio->todas($request);
        $vivienda = $servicio->activa($request, $viviendas);

        if (! $vivienda) {
            return redirect()->route('viviendas.create')->with('exito', 'Primero registra una vivienda.');
        }

        // Se actualiza el avance de las metas activas al abrir la pantalla.
        $metas->evaluarVivienda($vivienda);

        $lista = $vivienda->metas()->with('actividad')
            ->orderByRaw("CASE estado WHEN 'activa' THEN 0 WHEN 'cumplida' THEN 1 ELSE 2 END")
            ->orderBy('fecha_fin')
            ->get()
            ->map(fn (Meta $meta) => ['meta' => $meta, 'avance' => $metas->avance($meta)]);

        $actividades = Actividad::where('activa', true)->orderBy('nombre')->get();
        $habito = $request->integer('habito');
        $actividadId = $habito > 0 ? $vivienda->registros()->whereKey($habito)->value('actividad_id') : null;
        $actividadSugerida = $actividades->find($actividadId);
        $sugerencia = $actividadSugerida ? [
            'tipo' => Meta::CUALITATIVA,
            'titulo' => 'Mejorar: '.$actividadSugerida->nombre,
            'actividad_id' => $actividadSugerida->id,
            'limite_cantidad' => min(1000, max(0.1, (float) $actividadSugerida->referencia_eficiente)),
            'duracion_dias' => 4,
        ] : [];

        return view('metas.index', [
            'viviendas' => $viviendas,
            'vivienda' => $vivienda,
            'metas' => $lista,
            'actividades' => $actividades,
            'sugerencia' => $sugerencia,
            'tieneConsumoBase' => $vivienda->lecturas()->exists(),
        ]);
    }

    public function store(Request $request, ViviendasDelUsuario $servicio, MetasService $metas): RedirectResponse
    {
        $vivienda = $servicio->activa($request);
        abort_unless($vivienda, 404);
        Gate::authorize('registrar', $vivienda);

        $calcularFechas = $request->has('duracion_dias');
        $datos = $request->validate([
            'tipo' => ['required', Rule::in([Meta::CUANTITATIVA, Meta::CUALITATIVA])],
            'titulo' => ['required', 'string', 'max:120'],
            'actividad_id' => [
                Rule::requiredIf($request->input('tipo') === Meta::CUALITATIVA),
                'nullable', 'integer', Rule::exists('actividades', 'id')->where('activa', true),
            ],
            'objetivo_litros' => ['exclude_unless:tipo,cuantitativa', 'required', 'numeric', 'min:1', 'max:1000000'],
            'limite_cantidad' => ['exclude_unless:tipo,cualitativa', 'required', 'numeric', 'min:0.1', 'max:1000'],
            'dias_objetivo' => [Rule::excludeIf($calcularFechas), 'exclude_unless:tipo,cualitativa', 'required', 'integer', 'min:1', 'max:366'],
            'duracion_dias' => ['sometimes', 'required', 'integer', 'min:1', 'max:366'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => [Rule::excludeIf($calcularFechas), 'required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio', 'after_or_equal:today'],
        ], [
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio ni a hoy.',
            'actividad_id.required' => 'Las metas cualitativas necesitan una actividad.',
        ]);

        if ($calcularFechas) {
            $duracion = (int) $datos['duracion_dias'];
            $fin = Carbon::parse($datos['fecha_inicio'])->addDays($duracion - 1);

            if ($fin->isBefore(today())) {
                throw ValidationException::withMessages([
                    'fecha_inicio' => 'El periodo termina antes de hoy. Cambia la fecha de inicio o la cantidad de días.',
                ]);
            }

            $datos['fecha_fin'] = $fin->toDateString();
            unset($datos['duracion_dias']);

            if ($datos['tipo'] === Meta::CUALITATIVA) {
                $datos['dias_objetivo'] = $duracion;
            }
        } elseif ($datos['tipo'] === Meta::CUALITATIVA) {
            $diasDelPeriodo = Carbon::parse($datos['fecha_inicio'])->diffInDays(Carbon::parse($datos['fecha_fin'])) + 1;

            if ($datos['dias_objetivo'] > $diasDelPeriodo) {
                throw ValidationException::withMessages(['dias_objetivo' => "El periodo solo tiene {$diasDelPeriodo} días."]);
            }
        }

        // CU-10 4a: ya existe una meta activa igual en la vivienda.
        $repetida = $vivienda->metas()
            ->where('estado', 'activa')
            ->where('tipo', $datos['tipo'])
            ->where('actividad_id', $datos['actividad_id'] ?? null)
            ->exists();

        if ($repetida) {
            throw ValidationException::withMessages([
                'tipo' => 'Ya existe una meta activa de ese tipo para esa actividad. Edítala o cancélala antes de crear otra.',
            ]);
        }

        $meta = $vivienda->metas()->create([...$datos, 'creada_por' => $request->user()->id, 'estado' => 'activa']);
        $metas->evaluar($meta);

        return redirect()->route('metas.index')->with('exito', 'Meta creada para '.$vivienda->alias.'.');
    }

    public function destroy(Request $request, Meta $meta): RedirectResponse
    {
        $vivienda = $meta->vivienda;
        abort_unless($meta->creada_por === $request->user()->id || $request->user()->can('update', $vivienda), 403);

        $meta->delete();

        return redirect()->route('metas.index')->with('exito', 'Meta eliminada.');
    }
}
