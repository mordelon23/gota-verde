<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('permite editar el nombre visible y consultar la guía de inicio', function (): void {
    $usuario = User::factory()->create(['name' => 'Ana López']);

    $this->actingAs($usuario)->get(route('perfil.edit'))
        ->assertOk()
        ->assertSee('AL')
        ->assertSee('Ver guía de inicio')
        ->assertDontSee('data-guia-automatica', false);

    $this->patch(route('perfil.update'), ['name' => 'Ana Torres', 'email' => 'otro@example.test', 'rol' => 'admin'])
        ->assertRedirect(route('perfil.edit'));

    expect($usuario->fresh())->name->toBe('Ana Torres')->email->toBe($usuario->email)->rol->toBe('usuario');
});

it('guarda, reemplaza y quita la foto privada de la cuenta activa', function (): void {
    Storage::fake('local');
    $usuario = User::factory()->create();
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aA3sAAAAASUVORK5CYII=');

    $this->actingAs($usuario)->post(route('perfil.update'), [
        '_method' => 'PATCH', 'name' => $usuario->name, 'foto' => UploadedFile::fake()->createWithContent('perfil.png', $png),
    ])->assertRedirect(route('perfil.edit'));

    $anterior = $usuario->fresh()->foto_perfil;
    Storage::disk('local')->assertExists($anterior);
    $this->actingAs($usuario->fresh())->get(route('perfil.foto'))->assertOk()->assertHeader('Content-Type', 'image/png');
    $foto = 'src="'.route('perfil.foto').'"';
    expect(substr_count($this->get(route('dashboard'))->assertOk()->getContent(), $foto))->toBe(2);
    viviendaDe($usuario);
    foreach (['individual', 'global'] as $modo) {
        expect(substr_count($this->get(route('dashboard', ['modo' => $modo]))->assertOk()->getContent(), $foto))->toBe(2);
    }
    $this->actingAs(User::factory()->create())->get(route('perfil.foto'))->assertNotFound();

    $this->actingAs($usuario)->patch(route('perfil.update'), [
        'name' => $usuario->name, 'foto' => UploadedFile::fake()->createWithContent('nueva.png', $png),
    ])->assertRedirect(route('perfil.edit'));

    $nueva = $usuario->fresh()->foto_perfil;
    Storage::disk('local')->assertExists($nueva)->assertMissing($anterior);

    $this->delete(route('perfil.foto.destroy'))->assertRedirect(route('perfil.edit'));
    Storage::disk('local')->assertMissing($nueva);
    expect($usuario->fresh()->foto_perfil)->toBeNull();
    $this->actingAs($usuario->fresh())->get(route('dashboard'))->assertDontSee($foto, false);
});

it('muestra un error útil cuando PHP rechaza la foto antes de validarla', function (): void {
    $usuario = User::factory()->create();
    $archivo = new UploadedFile(__FILE__, 'foto.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);

    $this->actingAs($usuario)->from(route('perfil.edit'))->followingRedirects()->post(route('perfil.update'), [
        '_method' => 'PATCH', 'name' => 'Cambio sin guardar', 'foto' => $archivo,
    ])->assertOk()->assertSee('La foto supera el límite de carga de 2 MB.');

    expect($usuario->fresh())->name->toBe($usuario->name)->foto_perfil->toBeNull();
});

it('guarda una foto enviada como datos sin usar un archivo temporal de PHP', function (): void {
    Storage::fake('local');
    $usuario = User::factory()->create();
    $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aA3sAAAAASUVORK5CYII=';

    $this->actingAs($usuario)->post(route('perfil.update'), [
        '_method' => 'PATCH', 'name' => $usuario->name, 'foto_codificada' => base64_encode('<svg/>'),
    ])->assertSessionHasErrors('foto')->assertSessionMissing('_old_input.foto_codificada');

    $this->post(route('perfil.update'), [
        '_method' => 'PATCH', 'name' => $usuario->name, 'foto_codificada' => $png,
    ])->assertRedirect(route('perfil.edit'))->assertSessionHasNoErrors();

    $usuario = $usuario->fresh();
    expect(Storage::disk('local')->get($usuario->foto_perfil))->toBe(base64_decode($png));
    $this->actingAs($usuario)->get(route('perfil.foto'))->assertOk()->assertHeader('Content-Type', 'image/png');
});

it('rechaza archivos que no sean fotos y exige autenticación', function (): void {
    $this->get(route('perfil.edit'))->assertRedirect(route('login'));
    $this->get(route('perfil.foto'))->assertRedirect(route('login'));

    $usuario = User::factory()->create();
    $this->actingAs($usuario)->patch(route('perfil.update'), [
        'name' => $usuario->name, 'foto' => UploadedFile::fake()->createWithContent('perfil.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
    ])->assertSessionHasErrors('foto');

    expect($usuario->fresh()->foto_perfil)->toBeNull();
});
