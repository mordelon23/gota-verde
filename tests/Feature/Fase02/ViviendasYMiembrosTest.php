<?php

use App\Models\Invitacion;
use App\Models\User;
use Database\Seeders\GamificacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

$datosVivienda = fn (array $cambios = []) => [
    'alias' => 'Casa', 'tipo' => 'Casa', 'habitantes' => 4, 'alcaldia' => 'Tlalpan',
    'codigo_postal' => '14000', 'tarifa_m3' => '25.50', ...$cambios,
];

it('permite al propietario editar el perfil y la tarifa (RF-15, CU-06)', function () use ($datosVivienda): void {
    $propietario = User::factory()->create();
    $vivienda = viviendaDe($propietario);

    $this->actingAs($propietario)
        ->put(route('viviendas.update', $vivienda), $datosVivienda(['alias' => 'Casa nueva']))
        ->assertRedirect(route('viviendas.index'));

    expect($vivienda->fresh())->alias->toBe('Casa nueva')->tarifa_m3->toBe('25.50');
});

it('impide que un miembro edite la vivienda (RF-05)', function () use ($datosVivienda): void {
    $miembro = User::factory()->create();
    $vivienda = viviendaDe($miembro, 'miembro');

    $this->actingAs($miembro)->get(route('viviendas.edit', $vivienda))->assertForbidden();
    $this->actingAs($miembro)->put(route('viviendas.update', $vivienda), $datosVivienda())->assertForbidden();
});

it('limita el registro de viviendas a alcaldías de la CDMX', function () use ($datosVivienda): void {
    $this->actingAs(User::factory()->create())
        ->post(route('viviendas.store'), $datosVivienda(['alcaldia' => 'Naucalpan']))
        ->assertSessionHasErrors('alcaldia');
});

it('deduce la alcaldía a partir de un código postal con cero inicial', function () use ($datosVivienda): void {
    $datos = $datosVivienda(['codigo_postal' => '04000']);
    unset($datos['alcaldia']);

    $this->actingAs(User::factory()->create())
        ->post(route('viviendas.store'), $datos)
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('viviendas', ['codigo_postal' => '04000', 'alcaldia' => 'Coyoacán']);
});

it('rechaza códigos ausentes del catálogo aunque tengan cinco dígitos', function (string $codigo) use ($datosVivienda): void {
    $this->actingAs(User::factory()->create())
        ->post(route('viviendas.store'), $datosVivienda(['codigo_postal' => $codigo]))
        ->assertSessionHasErrors('codigo_postal');
})->with(['fuera de la CDMX' => '53000', 'prefijo válido sin código' => '01001']);

it('permite editar una vivienda anterior sin modificar su código postal', function () use ($datosVivienda): void {
    $propietario = User::factory()->create();
    $vivienda = viviendaDe($propietario);
    $vivienda->update(['codigo_postal' => '14999']);
    $datos = $datosVivienda(['alias' => 'Casa anterior', 'codigo_postal' => '14999']);
    unset($datos['alcaldia']);

    $this->actingAs($propietario)
        ->put(route('viviendas.update', $vivienda), $datos)
        ->assertRedirect(route('viviendas.index'));

    expect($vivienda->fresh())->alias->toBe('Casa anterior')
        ->codigo_postal->toBe('14999')->alcaldia->toBe('Gustavo A. Madero');
});

it('muestra las recompensas y la empresa vinculada junto a los formularios de registro', function (): void {
    $this->seed(GamificacionSeeder::class);
    config(['services.recompensas.empresas.gota_bronce' => 'Empresa de prueba']);
    $usuario = User::factory()->create();
    viviendaDe($usuario);

    $this->actingAs($usuario)->get(route('viviendas.create'))
        ->assertOk()
        ->assertSee('Empresa de prueba')
        ->assertSee('Gota de bronce')
        ->assertSee('Alcaldía de la Ciudad de México')
        ->assertSee('Volver al inicio');

    $this->get(route('habitos.create'))
        ->assertOk()
        ->assertSee('Empresa de prueba')
        ->assertSee('Gota de bronce');
});

it('invita por correo y el invitado acepta como miembro (RF-20, CU-13, CU-14)', function (): void {
    config(['services.resend.key' => 'clave-de-prueba', 'mail.from.address' => 'hola@example.test']);
    Http::fake(['https://api.resend.com/emails' => Http::response(['id' => 'correo-enviado'])]);
    $propietario = User::factory()->create();
    $vivienda = viviendaDe($propietario);
    $invitado = User::factory()->create(['email' => 'Hermana@Example.test']);

    $this->actingAs($propietario)
        ->post(route('viviendas.invitar', $vivienda), ['email' => 'hermana@example.test'])
        ->assertSessionHas('enlace_invitacion');

    $enlace = session('enlace_invitacion');
    $token = basename($enlace);
    $invitacion = Invitacion::first();

    // En la base solo se guarda el hash del token.
    expect($invitacion->token)->toBe(hash('sha256', $token))->not->toBe($token)
        ->and($invitacion->expira_en->diffInHours(now(), true))->toBeGreaterThan(71);
    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer clave-de-prueba')
        && $request['to'] === ['hermana@example.test']
        && str_contains($request['html'], $enlace));

    $this->actingAs($invitado)->get(route('invitaciones.mostrar', $token))->assertOk()->assertSee($vivienda->alias);
    $this->actingAs($invitado)->post(route('invitaciones.aceptar', $token))->assertRedirect(route('dashboard'));

    expect($vivienda->rolDe($invitado))->toBe('miembro')
        ->and($invitacion->fresh()->estado)->toBe('aceptada');
});

it('conserva el enlace anterior cuando Resend rechaza el nuevo correo', function (): void {
    config(['services.resend.key' => null]);
    $propietario = User::factory()->create();
    $vivienda = viviendaDe($propietario);
    $email = ['email' => 'primo@example.test'];
    $this->actingAs($propietario)->post(route('viviendas.invitar', $vivienda), $email)
        ->assertSessionHas('exito', 'Invitación creada. La otra persona puede aceptarla en Invitaciones o usar el enlace que le compartas.');
    $invitacion = $vivienda->invitaciones()->sole();

    config(['services.resend.key' => 'clave-de-prueba']);
    Http::fake(['https://api.resend.com/emails' => Http::response([], 403)]);
    $this->post(route('viviendas.invitar', $vivienda), $email)->assertSessionHasErrors('email');

    expect($vivienda->invitaciones()->sole()->is($invitacion))->toBeTrue()
        ->and($invitacion->fresh()->estado)->toBe('pendiente');
});

it('muestra la invitación a una cuenta registrada y permite aceptarla desde el panel sin correo', function (): void {
    config(['services.resend.key' => null]);
    $propietario = User::factory()->create(['email' => 'dueña@example.test']);
    $vivienda = viviendaDe($propietario, datos: ['alias' => 'Vivienda de invitación']);
    $invitado = User::factory()->create(['email' => 'Invitada@Example.test']);
    $otraCuenta = User::factory()->create();

    $this->actingAs($propietario)->post(route('viviendas.invitar', $vivienda), ['email' => 'invitada@example.test'])
        ->assertSessionHas('enlace_invitacion');
    $enlace = session('enlace_invitacion');
    $invitacion = $vivienda->invitaciones()->sole();
    $aceptar = route('invitaciones.recibidas.aceptar', $invitacion->id);

    $this->actingAs($otraCuenta)->get(route('invitaciones.recibidas'))->assertOk()->assertDontSee($vivienda->alias);
    $this->post($aceptar)->assertNotFound();

    $this->actingAs($invitado)->get(route('dashboard'))->assertSee('Tienes una invitación pendiente');
    $this->get(route('invitaciones.recibidas'))->assertSee($vivienda->alias)->assertSee('Aceptar invitación');
    $this->post(route('invitaciones.enlace'), ['enlace' => 'https://example.test/'])->assertSessionHasErrors('enlace');
    $this->post(route('invitaciones.enlace'), ['enlace' => $enlace])->assertRedirect($enlace);
    $this->post($aceptar)->assertRedirect(route('dashboard'))->assertSessionHas('vivienda_activa_id', $vivienda->id);

    expect($vivienda->rolDe($invitado))->toBe('miembro')->and($invitacion->fresh()->estado)->toBe('aceptada');
    $this->post($aceptar)->assertNotFound();
    $this->get(route('invitaciones.recibidas'))->assertSee('Todavía no tienes invitaciones pendientes');
});

it('explica por qué el propietario no puede invitarse a sí mismo', function (): void {
    $propietario = User::factory()->create(['email' => 'Propietario@Example.test']);
    $vivienda = viviendaDe($propietario);

    $this->actingAs($propietario)->post(route('viviendas.invitar', $vivienda), ['email' => 'propietario@example.test'])
        ->assertSessionHasErrors(['email' => 'Ya eres propietario de esta vivienda. Ingresa el correo de otra persona.']);

    expect($vivienda->invitaciones()->exists())->toBeFalse();
});

it('rechaza invitaciones expiradas o aceptadas por otra cuenta', function (): void {
    config(['services.resend.key' => null]);
    $propietario = User::factory()->create();
    $vivienda = viviendaDe($propietario);
    $this->actingAs($propietario)->post(route('viviendas.invitar', $vivienda), ['email' => 'primo@example.test']);
    $token = basename(session('enlace_invitacion'));

    $otro = User::factory()->create(['email' => 'otro@example.test']);
    $this->actingAs($otro)->post(route('invitaciones.aceptar', $token))->assertSessionHasErrors('invitacion');
    expect($vivienda->esMiembro($otro))->toBeFalse();

    $primo = User::factory()->create(['email' => 'primo@example.test']);
    $this->travel(73)->hours();
    $this->actingAs($primo)->post(route('invitaciones.aceptar', $token))->assertSessionHasErrors('invitacion');
    expect($vivienda->esMiembro($primo))->toBeFalse();
});

it('no invita a quien ya es miembro y solo el propietario puede invitar', function (): void {
    Http::fake();
    $propietario = User::factory()->create();
    $miembro = User::factory()->create(['email' => 'miembro@example.test']);
    $vivienda = viviendaDe($propietario);
    $vivienda->usuarios()->attach($miembro->id, ['rol' => 'miembro']);

    $this->actingAs($propietario)->post(route('viviendas.invitar', $vivienda), ['email' => 'miembro@example.test'])
        ->assertSessionHasErrors('email');
    $this->actingAs($miembro)->post(route('viviendas.invitar', $vivienda), ['email' => 'x@example.test'])
        ->assertForbidden();
});

it('el propietario elimina miembros y un miembro puede salir (RF-22)', function (): void {
    $propietario = User::factory()->create();
    $vivienda = viviendaDe($propietario);
    [$uno, $dos] = User::factory()->count(2)->create();
    $vivienda->usuarios()->attach([$uno->id => ['rol' => 'miembro'], $dos->id => ['rol' => 'miembro']]);

    $this->actingAs($propietario)->delete(route('viviendas.miembros.eliminar', [$vivienda, $uno]))->assertSessionHas('exito');
    $this->actingAs($dos)->post(route('viviendas.salir', $vivienda))->assertRedirect(route('viviendas.index'));

    expect($vivienda->usuarios()->count())->toBe(1);

    // El único propietario no puede salir.
    $this->actingAs($propietario)->post(route('viviendas.salir', $vivienda))->assertSessionHasErrors('miembro');
});

it('elimina la vivienda solo al confirmar el alias', function (): void {
    $propietario = User::factory()->create();
    $vivienda = viviendaDe($propietario, 'propietario', ['alias' => 'Depa']);

    $this->actingAs($propietario)->delete(route('viviendas.destroy', $vivienda), ['confirmacion' => 'otra'])
        ->assertSessionHasErrors('confirmacion');
    $this->actingAs($propietario)->delete(route('viviendas.destroy', $vivienda), ['confirmacion' => 'Depa'])
        ->assertRedirect(route('viviendas.index'));

    $this->assertModelMissing($vivienda);
});
