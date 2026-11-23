<?php

namespace App\Http\Controllers;

use App\Models\Vivienda;
use App\Services\ViviendasDelUsuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ViviendaController extends Controller
{
    /** Tarifa de referencia sugerida cuando el usuario no conoce la suya (CU-05 2a). */
    public const TARIFA_SUGERIDA = 20.00;

    public function index(Request $request, ViviendasDelUsuario $servicio): View
    {
        $viviendas = $servicio->todas($request);
        $vivienda = $servicio->activa($request, $viviendas);
        $miembros = $vivienda ? $vivienda->usuarios()->orderBy('name')->get() : collect();
        $roles = DB::table('vivienda_usuario')->where('user_id', $request->user()->id)->pluck('rol', 'vivienda_id');
        $esPropietario = $vivienda && $request->user()->can('gestionarMiembros', $vivienda);
        $invitaciones = $esPropietario
            ? $vivienda->invitaciones()->where('estado', 'pendiente')->where('expira_en', '>', now())->latest()->get()
            : collect();

        return view('viviendas.index', compact('viviendas', 'vivienda', 'miembros', 'roles', 'esPropietario', 'invitaciones'));
    }

    public function create(): View
    {
        return view('viviendas.crear', ['tarifaSugerida' => self::TARIFA_SUGERIDA]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        $vivienda = DB::transaction(function () use ($datos, $request): Vivienda {
            $nueva = Vivienda::create($datos);
            $nueva->usuarios()->attach($request->user()->id, ['rol' => Vivienda::ROL_PROPIETARIO]);

            return $nueva;
        });

        $request->session()->put('vivienda_activa_id', $vivienda->id);

        return redirect()->route('dashboard')
            ->with('exito', 'Vivienda creada correctamente. Ya puedes registrar tus hábitos.');
    }

    /** CU-06: editar perfil de vivienda (solo propietario). */
    public function edit(Vivienda $vivienda): View
    {
        Gate::authorize('update', $vivienda);

        return view('viviendas.editar', ['vivienda' => $vivienda, 'tarifaSugerida' => self::TARIFA_SUGERIDA]);
    }

    public function update(Request $request, Vivienda $vivienda): RedirectResponse
    {
        Gate::authorize('update', $vivienda);

        $vivienda->update($this->validar($request, $vivienda));

        return redirect()->route('viviendas.index')->with('exito', 'Perfil de la vivienda actualizado. Los cálculos usan la nueva tarifa.');
    }

    public function destroy(Request $request, Vivienda $vivienda): RedirectResponse
    {
        Gate::authorize('delete', $vivienda);

        $request->validate(['confirmacion' => ['required', 'in:'.$vivienda->alias]], [
            'confirmacion.in' => 'Escribe el alias exacto de la vivienda para confirmar.',
        ]);

        DB::transaction(function () use ($vivienda): void {
            // Los registros, lecturas, metas, rachas e invitaciones se eliminan en cascada.
            $vivienda->delete();
        });

        $request->session()->forget('vivienda_activa_id');

        return redirect()->route('viviendas.index')->with('exito', 'La vivienda se eliminó.');
    }

    public function seleccionar(Request $request, Vivienda $vivienda): RedirectResponse
    {
        // No permitir seleccionar viviendas ajenas aunque se conozca el ID.
        abort_unless($vivienda->esMiembro($request->user()), 404);

        $request->session()->put('vivienda_activa_id', $vivienda->id);

        $destinos = ['viviendas' => 'viviendas.index', 'lecturas' => 'lecturas.index', 'metas' => 'metas.index', 'habitos' => 'habitos.create'];

        return redirect()->route($destinos[$request->input('destino')] ?? 'dashboard');
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?Vivienda $vivienda = null): array
    {
        $datos = $request->validate([
            'alias' => ['required', 'string', 'max:100'],
            'tipo' => ['required', 'in:Casa,Departamento,Otro'],
            'habitantes' => ['required', 'integer', 'min:1', 'max:30'],
            'alcaldia' => ['nullable', 'string'],
            'codigo_postal' => ['required', 'string', 'regex:/^[0-9]{5}$/'],
            'tarifa_m3' => ['nullable', 'numeric', 'min:0', 'max:10000'],
        ], [
            'codigo_postal.regex' => 'El código postal debe tener 5 dígitos.',
            'tarifa_m3.numeric' => 'La tarifa debe ser un número.',
        ]);

        $alcaldia = Vivienda::alcaldiaPorCodigoPostal($datos['codigo_postal']);

        if ($alcaldia === null && $vivienda?->codigo_postal === $datos['codigo_postal']) {
            $alcaldia = $vivienda->alcaldia;
        }

        if ($alcaldia === null) {
            throw ValidationException::withMessages([
                'codigo_postal' => 'Este código postal no está en el catálogo de la Ciudad de México.',
            ]);
        }

        if (isset($datos['alcaldia']) && $datos['alcaldia'] !== $alcaldia) {
            throw ValidationException::withMessages([
                'alcaldia' => 'La alcaldía correspondiente a este código postal es '.$alcaldia.'.',
            ]);
        }

        $datos['alcaldia'] = $alcaldia;
        $datos['tarifa_m3'] = $datos['tarifa_m3'] ?? 0;

        return $datos;
    }
}
