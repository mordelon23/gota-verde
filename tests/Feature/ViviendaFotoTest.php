<?php

use App\Models\User;
use App\Models\Vivienda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function imagenDeVivienda(): UploadedFile
{
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aA3sAAAAASUVORK5CYII=');

    return UploadedFile::fake()->createWithContent('casa.png', $png);
}

function datosDeVivienda(): array
{
    return ['alias' => 'Casa de prueba', 'tipo' => 'Casa', 'habitantes' => 2, 'codigo_postal' => '04000'];
}

it('persiste la foto al crear y permite reemplazarla, quitarla y volver a subirla', function (): void {
    $disco = Storage::fake('local');
    $dueño = User::factory()->create();
    $this->actingAs($dueño)
        ->post(route('viviendas.store'), [...datosDeVivienda(), 'foto' => imagenDeVivienda()])
        ->assertRedirect(route('dashboard'));

    $vivienda = Vivienda::sole();
    $anterior = $vivienda->foto;
    $disco->assertExists($anterior);
    $this->get(route('dashboard'))->assertSee(route('viviendas.foto', $vivienda));
    $this->get(route('viviendas.index'))->assertSee(route('viviendas.foto', $vivienda));
    $this->get(route('viviendas.edit', $vivienda))->assertSee(route('viviendas.foto', $vivienda));
    $this->get(route('viviendas.foto', $vivienda))->assertOk()->assertHeader('Content-Type', 'image/png');

    $this->put(route('viviendas.update', $vivienda), [...datosDeVivienda(), 'foto' => imagenDeVivienda()])
        ->assertRedirect(route('viviendas.index'));
    $nueva = $vivienda->fresh()->foto;
    expect($nueva)->not->toBe($anterior);
    $disco->assertMissing($anterior)->assertExists($nueva);

    $this->put(route('viviendas.update', $vivienda), [...datosDeVivienda(), 'quitar_foto' => 1])
        ->assertRedirect(route('viviendas.index'));
    expect($vivienda->fresh()->foto)->toBeNull();
    $disco->assertMissing($nueva);
    $this->get(route('viviendas.foto', $vivienda))->assertNotFound();

    $this->put(route('viviendas.update', $vivienda), [...datosDeVivienda(), 'foto' => imagenDeVivienda()])
        ->assertRedirect(route('viviendas.index'));
    $ultima = $vivienda->fresh()->foto;
    $this->delete(route('viviendas.destroy', $vivienda), ['confirmacion' => $vivienda->alias])
        ->assertRedirect(route('viviendas.index'));
    $disco->assertMissing($ultima);
});

it('mantiene el acceso de los miembros y conserva el archivo ante cambios inválidos o sin permiso', function (): void {
    $disco = Storage::fake('local');
    $dueño = User::factory()->create();
    $miembro = User::factory()->create();
    $vivienda = viviendaDe($dueño);
    $vivienda->usuarios()->attach($miembro->id, ['rol' => Vivienda::ROL_MIEMBRO]);
    $this->actingAs($dueño)->put(route('viviendas.update', $vivienda), [...datosDeVivienda(), 'foto' => imagenDeVivienda()])
        ->assertRedirect(route('viviendas.index'));
    $foto = $vivienda->fresh()->foto;

    $contenidoInvalido = UploadedFile::fake()->createWithContent('fraude.jpg', '<?php echo 1;');
    foreach ([
        new UploadedFile($contenidoInvalido->getPathname(), 'fraude.jpg', null, null, true),
        imagenDeVivienda()->size(5121),
    ] as $archivo) {
        $this->put(route('viviendas.update', $vivienda), [...datosDeVivienda(), 'foto' => $archivo])
            ->assertSessionHasErrors('foto');
    }

    $this->actingAs($miembro)->get(route('viviendas.foto', $vivienda))->assertOk();
    $this->put(route('viviendas.update', $vivienda), [...datosDeVivienda(), 'quitar_foto' => 1])->assertForbidden();
    $this->actingAs(User::factory()->create())->get(route('viviendas.foto', $vivienda))->assertForbidden();
    $disco->assertExists($foto);
    expect($vivienda->fresh()->foto)->toBe($foto);
});

it('muestra la foto actual al destinatario de una invitación pendiente y vigente', function (): void {
    $disco = Storage::fake('local');
    $dueño = User::factory()->create();
    $destinatario = User::factory()->create(['email' => 'Invitada@example.test']);
    $vivienda = viviendaDe($dueño);
    $otraVivienda = viviendaDe($dueño);
    $url = route('viviendas.foto', $vivienda);
    $invitacion = $vivienda->invitaciones()->create([
        'invitado_por' => $dueño->id,
        'email' => 'invitada@example.test',
        'token' => hash('sha256', 'invitacion-foto'),
        'estado' => 'pendiente',
        'expira_en' => now()->addDay(),
    ]);

    $this->actingAs($destinatario)->get(route('invitaciones.recibidas'))
        ->assertOk()->assertSee('#households', false)->assertDontSee($url);
    $this->get($url)->assertNotFound();

    $this->actingAs($dueño)->put(route('viviendas.update', $vivienda), [...datosDeVivienda(), 'foto' => imagenDeVivienda()])
        ->assertRedirect(route('viviendas.index'));
    $anterior = $vivienda->fresh()->foto;

    $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    $this->actingAs($destinatario)->get(route('viviendas.foto', $otraVivienda))->assertForbidden();
    $this->get(route('invitaciones.recibidas'))->assertOk()->assertSee($url);
    $respuesta = $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
    expect($respuesta->headers->get('Cache-Control'))->toContain('private', 'no-store');
    $this->get(route('viviendas.edit', $vivienda))->assertForbidden();
    $this->put(route('viviendas.update', $vivienda), [...datosDeVivienda(), 'quitar_foto' => 1])->assertForbidden();
    expect($vivienda->esMiembro($destinatario))->toBeFalse();

    $reemplazo = imagenDeVivienda();
    $contenidoNuevo = file_get_contents($reemplazo->getPathname())."\n";
    $this->actingAs($dueño)->put(route('viviendas.update', $vivienda), [
        ...datosDeVivienda(), 'foto' => UploadedFile::fake()->createWithContent('reemplazo.png', $contenidoNuevo),
    ])->assertRedirect(route('viviendas.index'));
    $disco->assertMissing($anterior);
    $this->actingAs($destinatario)->get($url)->assertOk()->assertStreamedContent($contenidoNuevo);

    foreach (['vencida', 'cancelada', 'rechazada', 'aceptada'] as $estado) {
        $invitacion->update(['estado' => $estado]);
        $this->get($url)->assertForbidden();
    }

    $invitacion->update(['estado' => 'pendiente', 'expira_en' => now()->subSecond()]);
    $this->get($url)->assertForbidden();

    $invitacion->update(['expira_en' => now()->addDay()]);
    $this->post(route('invitaciones.recibidas.aceptar', $invitacion))->assertRedirect(route('dashboard'));
    $this->get($url)->assertOk();
    $this->get(route('invitaciones.recibidas'))->assertOk()->assertDontSee($url);
    expect($vivienda->esMiembro($destinatario))->toBeTrue();
});
