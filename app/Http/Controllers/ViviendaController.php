<?php

namespace App\Http\Controllers;

use App\Models\Vivienda;
use App\Services\InvitacionesService;
use App\Services\ViviendasDelUsuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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

        $foto = $this->guardarFoto($request);

        try {
            $vivienda = DB::transaction(function () use ($datos, $request, $foto): Vivienda {
                $nueva = Vivienda::create($datos);
                if ($foto !== null) {
                    $nueva->foto = $foto;
                    $nueva->save();
                }
                $nueva->usuarios()->attach($request->user()->id, ['rol' => Vivienda::ROL_PROPIETARIO]);

                return $nueva;
            });
        } catch (Throwable $error) {
            $this->borrarFoto($foto);

            throw $error;
        }

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

        $datos = $this->validar($request, $vivienda);
        $foto = $this->guardarFoto($request);

        if ($foto === null && ! $request->boolean('quitar_foto')) {
            $vivienda->update($datos);
        } else {
            try {
                $anterior = DB::transaction(function () use ($vivienda, $datos, $foto): ?string {
                    $actual = Vivienda::query()->lockForUpdate()->findOrFail($vivienda->id);
                    $anterior = $actual->foto;
                    $actual->fill($datos);
                    $actual->foto = $foto;
                    $actual->save();

                    return $anterior;
                });
            } catch (Throwable $error) {
                $this->borrarFoto($foto);

                throw $error;
            }

            $this->borrarFoto($anterior);
        }

        return redirect()->route('viviendas.index')->with('exito', 'Perfil de la vivienda actualizado. Los cálculos usan la nueva tarifa.');
    }

    public function foto(Request $request, Vivienda $vivienda, InvitacionesService $invitaciones): StreamedResponse
    {
        abort_unless(
            Gate::allows('view', $vivienda)
                || $invitaciones->pendientesPara($request->user())->where('vivienda_id', $vivienda->id)->exists(),
            403
        );

        $ruta = $vivienda->foto;
        $disco = Storage::disk('local');
        abort_unless($ruta && $disco->exists($ruta), 404);

        return $disco->response($ruta, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request, Vivienda $vivienda): RedirectResponse
    {
        Gate::authorize('delete', $vivienda);

        $request->validate(['confirmacion' => ['required', 'in:'.$vivienda->alias]], [
            'confirmacion.in' => 'Escribe el alias exacto de la vivienda para confirmar.',
        ]);

        $foto = DB::transaction(function () use ($vivienda): ?string {
            $actual = Vivienda::query()->lockForUpdate()->findOrFail($vivienda->id);
            // Los registros, lecturas, metas, rachas e invitaciones se eliminan en cascada.
            $actual->delete();

            return $actual->foto;
        });
        $this->borrarFoto($foto);

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
        $archivo = $request->file('foto');
        if ($archivo instanceof UploadedFile && ! $archivo->isValid()) {
            $codigo = $archivo->getError();
            if (! in_array($codigo, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                Log::warning('PHP no recibió correctamente una fotografía de vivienda.', ['codigo_php' => $codigo]);
            }

            $mensaje = match ($codigo) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La fotografía no debe superar los 5 MB.',
                UPLOAD_ERR_PARTIAL => 'La fotografía no se cargó completamente. Inténtalo nuevamente.',
                default => 'No se pudo cargar la fotografía. Inténtalo nuevamente.',
            };

            throw ValidationException::withMessages(['foto' => $mensaje]);
        }

        $datos = $request->validate([
            'alias' => ['required', 'string', 'max:100'],
            'tipo' => ['required', 'in:Casa,Departamento,Otro'],
            'habitantes' => ['required', 'integer', 'min:1', 'max:30'],
            'alcaldia' => ['nullable', 'string'],
            'codigo_postal' => ['required', 'string', 'regex:/^[0-9]{5}$/'],
            'tarifa_m3' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'foto' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'quitar_foto' => ['sometimes', 'boolean'],
        ], [
            'codigo_postal.regex' => 'El código postal debe tener 5 dígitos.',
            'tarifa_m3.numeric' => 'La tarifa debe ser un número.',
            'foto.file' => 'Selecciona una imagen en formato JPG, PNG o WebP.',
            'foto.image' => 'Selecciona una imagen en formato JPG, PNG o WebP.',
            'foto.mimes' => 'Selecciona una imagen en formato JPG, PNG o WebP.',
            'foto.max' => 'La fotografía no debe superar los 5 MB.',
        ]);
        unset($datos['foto'], $datos['quitar_foto']);

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

    private function guardarFoto(Request $request): ?string
    {
        if (! $request->hasFile('foto')) {
            return null;
        }

        try {
            $ruta = $request->file('foto')->store('fotos-viviendas', 'local');
        } catch (Throwable $error) {
            report($error);

            throw ValidationException::withMessages(['foto' => 'No se pudo guardar la fotografía de la vivienda.']);
        }

        if (! $ruta) {
            Log::error('El disco rechazó el guardado de una fotografía de vivienda.', ['disco' => 'local']);

            throw ValidationException::withMessages(['foto' => 'No se pudo guardar la fotografía de la vivienda.']);
        }

        return $ruta;
    }

    private function borrarFoto(?string $ruta): void
    {
        if ($ruta !== null) {
            try {
                if (! Storage::disk('local')->delete($ruta)) {
                    report(new \RuntimeException('No se pudo retirar el archivo de una foto de vivienda.'));
                }
            } catch (Throwable $error) {
                report($error);
            }
        }
    }
}
