<?php

namespace Database\Seeders;

use App\Models\Actividad;
use App\Models\RegistroHabito;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\GamificacionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoGamificacionSeeder extends Seeder
{
    public function run(GamificacionService $gamificacion): void
    {
        if (! app()->environment('local')) {
            throw new \RuntimeException('La cuenta de demostración requiere el entorno local.');
        }

        $this->call([ActividadInicialSeeder::class, GamificacionSeeder::class]);

        DB::transaction(function () use ($gamificacion): void {
            $usuario = User::firstOrCreate(
                ['email' => 'demo-rachas@example.test'],
                ['name' => 'Demo de rachas', 'password' => 'GotaVerdeDemo50!'],
            );

            $vivienda = $usuario->viviendas()->where('alias', 'Casa de demostración')->first();
            if (! $vivienda) {
                $vivienda = Vivienda::create([
                    'alias' => 'Casa de demostración',
                    'tipo' => 'Casa',
                    'habitantes' => 1,
                    'alcaldia' => 'Gustavo A. Madero',
                    'codigo_postal' => '07738',
                    'tarifa_m3' => 20,
                ]);
                $vivienda->usuarios()->attach($usuario->id, ['rol' => Vivienda::ROL_PROPIETARIO]);
            }

            $ducha = Actividad::where('clave', 'ducha')->firstOrFail();
            $hoy = today();

            for ($dias = 49; $dias >= 0; $dias--) {
                $registro = RegistroHabito::firstOrCreate([
                    'user_id' => $usuario->id,
                    'vivienda_id' => $vivienda->id,
                    'actividad_id' => $ducha->id,
                    'fecha' => $hoy->copy()->subDays($dias),
                ], [
                    'cantidad' => 1,
                    'litros_consumidos' => $ducha->litros_por_unidad,
                    'litros_ahorrados' => max(0, ($ducha->referencia_eficiente - 1) * $ducha->litros_por_unidad),
                ]);

                if ($registro->wasRecentlyCreated) {
                    $gamificacion->procesarRegistro($registro);
                }
            }
        });
    }
}
