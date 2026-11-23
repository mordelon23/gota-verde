<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PerfilController extends Controller
{
    private const LIMITE_FOTO_KB = 2048;

    private const LIMITE_FOTO_PIXELES = 4096;

    public function edit(): View
    {
        return view('perfil.editar', [
            'limiteFotoBytes' => self::LIMITE_FOTO_KB * 1024,
            'limiteFotoPixeles' => self::LIMITE_FOTO_PIXELES,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $archivo = $request->file('foto');

        if ($archivo instanceof UploadedFile && ! $archivo->isValid()) {
            $mensaje = match ($archivo->getError()) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La foto supera el límite de carga de 2 MB. Elige una imagen más pequeña.',
                UPLOAD_ERR_PARTIAL => 'La foto se recibió incompleta. Selecciónala de nuevo y vuelve a guardar.',
                UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'El servidor no pudo guardar temporalmente la foto. Tu perfil no se actualizó.',
                default => 'No se pudo recibir la foto. Selecciónala de nuevo e intenta guardar.',
            };

            throw ValidationException::withMessages(['foto' => $mensaje]);
        }

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'foto_codificada' => ['nullable', 'string', 'max:'.(4 * (int) ceil(self::LIMITE_FOTO_KB * 1024 / 3))],
            'foto' => [
                'nullable', 'image', 'mimes:jpg,jpeg,png,webp',
                'max:'.self::LIMITE_FOTO_KB,
                'dimensions:max_width='.self::LIMITE_FOTO_PIXELES.',max_height='.self::LIMITE_FOTO_PIXELES,
            ],
        ], [
            'foto_codificada.max' => 'La foto supera el límite de 2 MB.',
            'foto.image' => 'Elige una imagen válida.',
            'foto.mimes' => 'La foto debe ser JPG, PNG o WebP.',
            'foto.max' => 'La foto debe pesar como máximo 2 MB.',
            'foto.dimensions' => 'La foto debe medir como máximo 4096 × 4096 píxeles.',
        ]);

        $cambios = ['name' => $datos['name']];
        $fotoNueva = isset($datos['foto_codificada'])
            ? $this->guardarFotoCodificada($datos['foto_codificada'])
            : $archivo?->store('fotos-perfil', 'local');

        if ($fotoNueva === false) {
            throw ValidationException::withMessages(['foto' => 'No se pudo guardar la foto. Vuelve a intentarlo.']);
        }

        if ($fotoNueva !== null) {
            $cambios['foto_perfil'] = $fotoNueva;
        }

        try {
            $fotoAnterior = $this->guardarCambios($request->user(), $cambios);
        } catch (Throwable $error) {
            if ($fotoNueva !== null) {
                Storage::disk('local')->delete($fotoNueva);
            }

            throw $error;
        }

        if ($fotoAnterior !== null) {
            Storage::disk('local')->delete($fotoAnterior);
        }

        return redirect()->route('perfil.edit')->with('exito', 'Tu perfil se actualizó.');
    }

    private function guardarFotoCodificada(string $codificada): string|false
    {
        $contenido = base64_decode($codificada, true);
        $info = $contenido !== false && strlen($contenido) <= self::LIMITE_FOTO_KB * 1024
            ? @getimagesizefromstring($contenido)
            : false;

        $extension = match ($info[2] ?? null) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => null,
        };

        if ($extension === null || max($info[0], $info[1]) > self::LIMITE_FOTO_PIXELES) {
            throw ValidationException::withMessages(['foto' => 'La foto debe ser JPG, PNG o WebP y medir como máximo 4096 × 4096 píxeles.']);
        }

        $ruta = 'fotos-perfil/'.Str::uuid().'.'.$extension;

        return Storage::disk('local')->put($ruta, $contenido) ? $ruta : false;
    }

    public function foto(Request $request): StreamedResponse
    {
        $ruta = $request->user()->foto_perfil;
        $disco = Storage::disk('local');
        abort_unless($ruta && $disco->exists($ruta), 404);

        return $disco->response($ruta, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function eliminarFoto(Request $request): RedirectResponse
    {
        $fotoAnterior = $this->guardarCambios($request->user(), ['foto_perfil' => null]);

        if ($fotoAnterior !== null) {
            Storage::disk('local')->delete($fotoAnterior);
        }

        return redirect()->route('perfil.edit')->with('exito', 'Se quitó tu foto de perfil.');
    }

    /** @param array{name?: string, foto_perfil?: string|null} $cambios */
    private function guardarCambios(User $usuario, array $cambios): ?string
    {
        return DB::transaction(function () use ($usuario, $cambios): ?string {
            $cuenta = User::query()->whereKey($usuario->id)->lockForUpdate()->firstOrFail();
            $anterior = $cuenta->foto_perfil;
            $cuenta->forceFill($cambios)->save();

            return $cuenta->wasChanged('foto_perfil') ? $anterior : null;
        });
    }
}
