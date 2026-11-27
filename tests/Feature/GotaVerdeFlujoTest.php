<?php

use App\Models\Actividad;
use App\Models\User;
use App\Models\Vivienda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('GotaVerde protege el dashboard para usuarios autenticados', function (): void {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('registra una cuenta sin iniciar sesión y permite ingresar manualmente', function (): void {
    $this->post(route('register.store'), [
        'name' => 'Persona Demo',
        'email' => 'demo@example.test',
        'password' => 'ClaveSegura123!',
        'password_confirmation' => 'ClaveSegura123!',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('_old_input', ['email' => 'demo@example.test']);

    $this->assertGuest();
    $usuario = User::where('email', 'demo@example.test')->sole();
    expect(Hash::check('ClaveSegura123!', $usuario->password))->toBeTrue();

    $login = $this->get(route('login'))
        ->assertOk()
        ->assertSee('Cuenta registrada correctamente')
        ->assertSee('Ya puedes iniciar sesión con tu correo electrónico y contraseña.')
        ->assertSee('value="demo@example.test"', false)
        ->assertDontSee('ClaveSegura123!');
    expect($login->getContent())->toMatch('/<input\b(?=[^>]*\bid="password")(?![^>]*\bvalue=)[^>]*>/');

    $this->post(route('login.store'), [
        'email' => 'demo@example.test',
        'password' => 'ClaveSegura123!',
    ])->assertRedirect(route('viviendas.create'));
    $this->assertAuthenticatedAs($usuario);

    $this->get(route('viviendas.create'))
        ->assertOk()
        ->assertSee('Te damos la bienvenida a GotaVerde')
        ->assertSee('Omitir guía');

    $this->get(route('viviendas.create'))
        ->assertOk()
        ->assertDontSee('Te damos la bienvenida a GotaVerde');
});

it('devuelve a la persona a su invitación después de que inicia sesión manualmente', function (): void {
    $enlace = route('invitaciones.mostrar', 'token-de-prueba');

    $this->withSession(['url.intended' => $enlace])->post(route('register.store'), [
        'name' => 'Persona Invitada',
        'email' => 'invitada@example.test',
        'password' => 'ClaveSegura123!',
        'password_confirmation' => 'ClaveSegura123!',
    ])->assertRedirect(route('login'))->assertSessionMissing('guia_primer_uso');
    $this->assertGuest();

    $this->post(route('login.store'), [
        'email' => 'invitada@example.test',
        'password' => 'ClaveSegura123!',
    ])->assertRedirect($enlace);
});

it('regresa al registro con validación en español y sin conservar las contraseñas', function (): void {
    $this->from(route('register'))->post(route('register.store'), [
        'name' => 'Persona Demo',
        'email' => 'demo@example.test',
        'password' => 'ClaveSegura123!',
        'password_confirmation' => 'OtraClave123!',
    ])->assertRedirect(route('register'))
        ->assertSessionHasErrors(['password' => 'La confirmación de contraseña no coincide.'])
        ->assertSessionHas('_old_input', ['name' => 'Persona Demo', 'email' => 'demo@example.test']);

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'demo@example.test']);
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('value="Persona Demo"', false)
        ->assertSee('value="demo@example.test"', false);
});

it('GotaVerde crea una vivienda y asigna al creador como propietario', function (): void {
    $usuario = User::factory()->create();

    $this->actingAs($usuario)->post(route('viviendas.store'), [
        'alias' => 'Casa de prueba',
        'tipo' => 'Casa',
        'habitantes' => 3,
        'alcaldia' => 'Iztapalapa',
        'codigo_postal' => '09000',
        'tarifa_m3' => '20.00',
    ])->assertRedirect(route('dashboard'));

    $vivienda = Vivienda::where('alias', 'Casa de prueba')->firstOrFail();
    $this->assertDatabaseHas('vivienda_usuario', [
        'user_id' => $usuario->id,
        'vivienda_id' => $vivienda->id,
        'rol' => 'propietario',
    ]);
});

it('GotaVerde impide cambiar a una vivienda ajena', function (): void {
    $usuario = User::factory()->create();
    $ajena = Vivienda::create([
        'alias' => 'Hogar privado', 'tipo' => 'Casa', 'habitantes' => 1,
        'alcaldia' => 'Coyoacán', 'codigo_postal' => '04000', 'tarifa_m3' => 0,
    ]);

    $this->actingAs($usuario)
        ->post(route('viviendas.seleccionar', $ajena))
        ->assertNotFound();
});

it('GotaVerde calcula cien litros para una ducha de diez minutos', function (): void {
    $usuario = User::factory()->create();
    $vivienda = Vivienda::create([
        'alias' => 'Casa ejemplo', 'tipo' => 'Departamento', 'habitantes' => 2,
        'alcaldia' => 'Tlalpan', 'codigo_postal' => '14000', 'tarifa_m3' => 20,
    ]);
    $vivienda->usuarios()->attach($usuario->id, ['rol' => 'propietario']);

    $actividad = Actividad::create([
        'clave' => 'ducha', 'nombre' => 'Ducha', 'unidad' => 'minuto',
        'litros_por_unidad' => 10, 'referencia_eficiente' => 5, 'activa' => true,
    ]);

    $this->actingAs($usuario)->post(route('habitos.store'), [
        'actividad_id' => $actividad->id,
        'cantidad' => 10,
        'fecha' => now()->toDateString(),
    ])->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('registros_habito', [
        'user_id' => $usuario->id,
        'vivienda_id' => $vivienda->id,
        'actividad_id' => $actividad->id,
        'litros_consumidos' => 100,
        'litros_ahorrados' => 0,
    ]);
});

it('GotaVerde no permite registrar actividades futuras', function (): void {
    $usuario = User::factory()->create();
    $vivienda = Vivienda::create([
        'alias' => 'Vivienda', 'tipo' => 'Casa', 'habitantes' => 1,
        'alcaldia' => 'Benito Juárez', 'codigo_postal' => '03100', 'tarifa_m3' => 0,
    ]);
    $vivienda->usuarios()->attach($usuario->id, ['rol' => 'propietario']);
    $actividad = Actividad::create([
        'clave' => 'ducha', 'nombre' => 'Ducha', 'unidad' => 'minuto',
        'litros_por_unidad' => 10, 'referencia_eficiente' => 5, 'activa' => true,
    ]);

    $this->actingAs($usuario)->post(route('habitos.store'), [
        'actividad_id' => $actividad->id,
        'cantidad' => 4,
        'fecha' => now()->addDay()->toDateString(),
    ])->assertSessionHasErrors('fecha');
});
