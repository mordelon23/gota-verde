<?php

use App\Models\Actividad;
use App\Models\Meta;
use App\Models\Racha;
use App\Models\RegistroHabito;
use App\Models\User;
use Database\Seeders\GamificacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(GamificacionSeeder::class));

it('crea una meta cuantitativa y se cumple con el ahorro de todos los miembros (RF-08, RF-21)', function (): void {
    $propietario = User::factory()->create();
    $miembro = User::factory()->create();
    $vivienda = viviendaDe($propietario);
    $vivienda->usuarios()->attach($miembro->id, ['rol' => 'miembro']);
    $ducha = ducha();

    $this->actingAs($propietario)->post(route('metas.store'), [
        'tipo' => 'cuantitativa', 'titulo' => 'Ahorrar 50 litros', 'objetivo_litros' => 50,
        'fecha_inicio' => today()->toDateString(), 'fecha_fin' => today()->addDays(6)->toDateString(),
    ])->assertRedirect(route('metas.index'));

    // Cada ducha de 3 minutos ahorra 20 L (referencia 5 min × 10 L/min).
    foreach ([$propietario, $miembro] as $usuario) {
        $this->actingAs($usuario)->post(route('habitos.store'), ['actividad_id' => $ducha->id, 'cantidad' => 3, 'fecha' => today()->toDateString()]);
    }
    expect(Meta::sole()->estado)->toBe('activa')->and((float) Meta::sole()->progreso)->toBe(80.0);

    // Segunda ducha del día del miembro (registro aparte): ahorra 10 L más y completa la meta.
    $this->actingAs($miembro)->post(route('habitos.store'), ['actividad_id' => $ducha->id, 'cantidad' => 4, 'fecha' => today()->toDateString(), 'duplicado' => 'separado'])
        ->assertSessionHas('gamificacion', fn (array $g) => $g['metas'] === ['Ahorrar 50 litros']);

    expect(Meta::sole()->estado)->toBe('cumplida')
        ->and($propietario->logros()->pluck('clave'))->toContain('meta_1')
        ->and($miembro->logros()->pluck('clave'))->toContain('meta_1');
});

it('mide una meta cualitativa por días que respetan el límite', function (): void {
    $usuario = User::factory()->create();
    $vivienda = viviendaDe($usuario);
    $ducha = ducha();

    $this->actingAs($usuario)->post(route('metas.store'), [
        'tipo' => 'cualitativa', 'titulo' => 'Duchas de 5 minutos', 'actividad_id' => $ducha->id,
        'limite_cantidad' => 5, 'dias_objetivo' => 4,
        'fecha_inicio' => today()->subDays(3)->toDateString(), 'fecha_fin' => today()->addDays(3)->toDateString(),
    ])->assertSessionHasNoErrors();

    foreach ([[3, 4], [2, 9], [1, 5]] as [$diasAtras, $minutos]) {
        $this->actingAs($usuario)->post(route('habitos.store'), ['actividad_id' => $ducha->id, 'cantidad' => $minutos, 'fecha' => today()->subDays($diasAtras)->toDateString()]);
    }

    // Cumplen 2 de 4 días (el de 9 minutos no cuenta).
    expect((float) Meta::sole()->progreso)->toBe(50.0);
});

it('completa una meta de un día al ahorrar 16 litros en un hábito guardado con hora', function (): void {
    $usuario = User::factory()->create();
    $vivienda = viviendaDe($usuario);
    $trastes = Actividad::create([
        'clave' => 'lavado_trastes', 'nombre' => 'Lavar trastes', 'unidad' => 'minuto',
        'litros_por_unidad' => 8, 'referencia_eficiente' => 5, 'activa' => true,
    ]);
    $fecha = today()->toDateString();

    $this->actingAs($usuario)->post(route('metas.store'), [
        'tipo' => 'cuantitativa', 'titulo' => 'ahorrar 1 litro', 'actividad_id' => $trastes->id,
        'objetivo_litros' => 1, 'fecha_inicio' => $fecha, 'duracion_dias' => 1,
    ])->assertRedirect(route('metas.index'));

    $this->post(route('habitos.store'), [
        'actividad_id' => $trastes->id, 'cantidad' => 3, 'fecha' => $fecha,
    ])->assertSessionHas('gamificacion', fn (array $aviso): bool => $aviso['metas'] === ['ahorrar 1 litro']);

    $habito = $vivienda->registros()->sole();
    expect((float) $habito->litros_ahorrados)->toBe(16.0);
    if ($habito->getConnection()->getDriverName() === 'sqlite') {
        expect($habito->getRawOriginal('fecha'))->toBe($fecha.' 00:00:00');
    }

    $meta = $vivienda->metas()->sole();
    expect((float) $meta->progreso)->toBe(100.0)
        ->and($meta->estado)->toBe('cumplida')
        ->and($meta->cumplida_en)->not->toBeNull();
});

it('completa una meta cualitativa con un hábito válido en cada extremo del periodo', function (): void {
    $usuario = User::factory()->create();
    $vivienda = viviendaDe($usuario);
    $ducha = ducha();
    $inicio = today()->subDay()->toDateString();
    $fin = today()->toDateString();

    $this->actingAs($usuario)->post(route('metas.store'), [
        'tipo' => 'cualitativa', 'titulo' => 'Duchas de máximo cinco minutos', 'actividad_id' => $ducha->id,
        'limite_cantidad' => 5, 'fecha_inicio' => $inicio, 'duracion_dias' => 2,
    ])->assertRedirect(route('metas.index'));

    $this->post(route('habitos.store'), ['actividad_id' => $ducha->id, 'cantidad' => 4, 'fecha' => $inicio])
        ->assertSessionHasNoErrors();
    expect((float) $vivienda->metas()->sole()->progreso)->toBe(50.0);

    $this->post(route('habitos.store'), ['actividad_id' => $ducha->id, 'cantidad' => 5, 'fecha' => $fin])
        ->assertSessionHasNoErrors();
    $meta = $vivienda->metas()->sole();
    expect((float) $meta->progreso)->toBe(100.0)->and($meta->estado)->toBe('cumplida');
});

it('suma los extremos de una meta de varios días y excluye hábitos anteriores y posteriores', function (): void {
    $usuario = User::factory()->create();
    $vivienda = viviendaDe($usuario);
    $ducha = ducha();
    $inicio = today()->subDays(2);
    $fin = today();

    $this->actingAs($usuario)->post(route('metas.store'), [
        'tipo' => 'cuantitativa', 'titulo' => 'Ahorro durante tres días', 'actividad_id' => $ducha->id,
        'objetivo_litros' => 100, 'fecha_inicio' => $inicio->toDateString(), 'duracion_dias' => 3,
    ])->assertRedirect(route('metas.index'));

    foreach ([
        [$inicio->copy()->subDay()->endOfDay(), 70],
        [$inicio, 20],
        [$fin->copy()->endOfDay(), 30],
        [$fin->copy()->addDay(), 80],
    ] as [$fecha, $ahorro]) {
        RegistroHabito::create([
            'user_id' => $usuario->id, 'vivienda_id' => $vivienda->id, 'actividad_id' => $ducha->id,
            'fecha' => $fecha, 'cantidad' => 1, 'litros_consumidos' => 10, 'litros_ahorrados' => $ahorro,
        ]);
    }

    $this->get(route('metas.index'))->assertOk();
    $meta = $vivienda->metas()->sole();
    expect((float) $meta->progreso)->toBe(50.0)->and($meta->estado)->toBe('activa');
});

it('no permite dos metas activas iguales ni fechas inválidas (CU-10)', function (): void {
    $usuario = User::factory()->create();
    viviendaDe($usuario);
    $meta = ['tipo' => 'cuantitativa', 'titulo' => 'Meta', 'objetivo_litros' => 100,
        'fecha_inicio' => today()->toDateString(), 'fecha_fin' => today()->addDays(10)->toDateString()];

    $this->actingAs($usuario)->post(route('metas.store'), $meta)->assertSessionHasNoErrors();
    $this->actingAs($usuario)->post(route('metas.store'), $meta)->assertSessionHasErrors('tipo');
    $this->actingAs($usuario)->post(route('metas.store'), [...$meta, 'fecha_fin' => today()->subDay()->toDateString()])
        ->assertSessionHasErrors('fecha_fin');
});

it('calcula la fecha de fin con la duración capturada y cuenta el día de inicio', function (): void {
    $usuario = User::factory()->create();
    $vivienda = viviendaDe($usuario);
    $inicio = today()->endOfMonth();
    $datos = [
        'tipo' => 'cuantitativa', 'titulo' => 'Ahorrar agua', 'objetivo_litros' => 50,
        'fecha_inicio' => $inicio->toDateString(), 'duracion_dias' => 2,
        'fecha_fin' => $inicio->copy()->addDays(20)->toDateString(),
    ];

    $this->actingAs($usuario)->post(route('metas.store'), [...$datos, 'duracion_dias' => 0])
        ->assertSessionHasErrors('duracion_dias');
    $this->post(route('metas.store'), [...$datos, 'fecha_inicio' => today()->subDays(3)->toDateString()])
        ->assertSessionHasErrors('fecha_inicio');
    $this->post(route('metas.store'), $datos)->assertRedirect(route('metas.index'));

    expect($vivienda->metas()->sole()->fecha_fin->toDateString())
        ->toBe($inicio->copy()->addDay()->toDateString());
});

it('el cierre diario vence metas, reinicia rachas y expira invitaciones (CU-19)', function (): void {
    $usuario = User::factory()->create();
    $vivienda = viviendaDe($usuario);
    $meta = $vivienda->metas()->create([
        'creada_por' => $usuario->id, 'tipo' => 'cuantitativa', 'titulo' => 'Vieja', 'objetivo_litros' => 1000,
        'fecha_inicio' => today()->subDays(10), 'fecha_fin' => today()->subDay(),
    ]);
    $racha = Racha::create(['user_id' => $usuario->id, 'vivienda_id' => $vivienda->id, 'actual' => 4, 'mejor' => 4, 'ultima_fecha' => today()->subDays(2)]);
    $invitacion = $vivienda->invitaciones()->create([
        'invitado_por' => $usuario->id, 'email' => 'x@example.test', 'token' => hash('sha256', 'abc'),
        'expira_en' => now()->subHour(),
    ]);

    $this->artisan('gotaverde:cierre-diario')->assertSuccessful();

    expect($meta->fresh()->estado)->toBe('vencida')
        ->and($racha->fresh()->actual)->toBe(0)
        ->and($racha->fresh()->mejor)->toBe(4)
        ->and($invitacion->fresh()->estado)->toBe('expirada');
});
