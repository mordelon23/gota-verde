@extends('layouts.app')

@section('titulo', 'Registrar hábito')

@section('contenido')
<div class="gv-registration">
    <section class="gv-card">
        <div class="gv-header" style="margin-bottom:8px">
            <p class="text-xs font-bold uppercase tracking-widest text-[#2E7D6B]">Vivienda: {{ $vivienda->alias }}</p>
            @include('partials.selector-vivienda', ['destino' => 'habitos'])
        </div>
        <h1 class="mb-2 text-2xl font-bold text-[#182d2b]">Registrar un hábito</h1>
        <p class="mb-8 text-sm text-slate-600" style="margin-bottom:24px">Anota una actividad que ya hiciste en esta vivienda. Por ejemplo, si te bañaste durante 7 minutos, elige Ducha e indica 7. GotaVerde calcula los litros.</p>
        @if ($actividades->isEmpty())
            <div class="rounded-xl bg-amber-50 p-5 text-sm leading-7 text-amber-900">
                No hay actividades disponibles. Ejecuta <code class="rounded bg-amber-100 px-2 py-1">php artisan db:seed --class=ActividadInicialSeeder</code> en la terminal del proyecto.
            </div>
        @else
            <form method="POST" action="{{ route('habitos.store') }}" class="space-y-5" @unless(session('duplicado')) data-limpiar-formulario @endunless autocomplete="off">
                @csrf
                @if (session('duplicado'))
                    @php($dup = session('duplicado'))
                    {{-- CU-09 5a: ya existe un registro de la misma actividad ese día --}}
                    <div class="gv-confirm" role="alert">
                        <p class="mb-3 text-sm">Ya registraste <strong>{{ $dup['actividad'] }}</strong> ese día ({{ $dup['cantidad'] }} {{ $dup['unidad'] }}{{ $dup['cantidad'] != 1 ? 's' : '' }}). ¿Qué quieres hacer?</p>
                        <div class="gv-row">
                            <button type="submit" name="duplicado" value="sumar" class="gv-btn sm">Sumarlo al registro existente</button>
                            <button type="submit" name="duplicado" value="separado" class="gv-btn sm alt">Guardar como registro aparte</button>
                        </div>
                    </div>
                @endif
                <div>
                    <label for="actividad_id" class="mb-2 block text-sm font-semibold">Actividad</label>
                    <select id="actividad_id" name="actividad_id" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 focus:border-[#2E7D6B] focus:outline-none">
                        <option value="">Selecciona una actividad</option>
                        @foreach ($actividades as $actividad)
                            <option value="{{ $actividad->id }}" data-unidad="{{ $actividad->unidad }}" data-tasa="{{ $actividad->litros_por_unidad }}" data-referencia="{{ $actividad->referencia_eficiente }}" @selected(old('actividad_id') == $actividad->id)>
                                {{ $actividad->nombre }} ({{ number_format((float) $actividad->litros_por_unidad, 2) }} L/{{ $actividad->unidad }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="cantidad" class="mb-2 block text-sm font-semibold">Cantidad o duración</label>
                    <input id="cantidad" name="cantidad" type="number" min="0.01" max="1000" step="0.01" required placeholder="Ejemplo: 10 minutos" value="{{ old('cantidad') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-[#2E7D6B] focus:outline-none">
                    <p class="mt-2 text-xs text-slate-500" id="unidad-ayuda">La unidad depende de la actividad (por ejemplo, minutos).</p>
                </div>
                <div id="estimacion-visual" aria-live="polite" class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-[#1F5F5B]">
                    <strong>Vista previa:</strong> <span id="litros-previos">Indica actividad y cantidad para calcular.</span>
                </div>
                <div>
                    <label for="fecha" class="mb-2 block text-sm font-semibold">Fecha de la actividad</label>
                    <input id="fecha" name="fecha" type="date" max="{{ now()->toDateString() }}" required value="{{ old('fecha', now()->toDateString()) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-[#2E7D6B] focus:outline-none">
                </div>
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-slate-700">
                    <strong>¿Cómo calculamos los litros?</strong> Multiplicamos la cantidad por los litros de referencia de la actividad (por ejemplo, ducha: 10 L/min, así que 10 minutos son 100 litros). Si usas menos que la referencia eficiente, la diferencia cuenta como ahorro. El consumo real depende de tus llaves y de la presión del agua.
                </div>
                <button type="submit" class="gv-btn w-full">Guardar hábito</button>
            </form>
            <script>
                (() => {
                    const actividad = document.getElementById('actividad_id');
                    const cantidad = document.getElementById('cantidad');
                    const salida = document.getElementById('litros-previos');
                    const ayuda = document.getElementById('unidad-ayuda');
                    const mostrar = () => {
                        const opcion = actividad.selectedOptions[0];
                        if (opcion?.dataset.unidad) ayuda.textContent = `Cantidad en ${opcion.dataset.unidad}s. Referencia eficiente: ${Number(opcion.dataset.referencia)} ${opcion.dataset.unidad}s.`;
                        const tasa = Number(opcion?.dataset.tasa);
                        const valor = Number(cantidad.value);
                        if (!opcion?.value || cantidad.value === '' || !Number.isFinite(valor) || valor <= 0) {
                            salida.textContent = 'Indica actividad y cantidad para calcular.';
                            return;
                        }
                        const litros = valor * tasa;
                        const ahorro = Math.max(0, Number(opcion.dataset.referencia) - valor) * tasa;
                        salida.textContent = `${litros.toFixed(2)} litros estimados · Ahorro frente a la referencia eficiente: ${ahorro.toFixed(2)} L.`;
                    };
                    actividad.addEventListener('change', mostrar);
                    cantidad.addEventListener('input', mostrar);
                    actividad.form.addEventListener('reset', () => requestAnimationFrame(mostrar));
                    mostrar();
                })();
            </script>
        @endif
        <div class="gv-return"><a class="gv-btn alt" href="{{ route('dashboard') }}">Volver al inicio</a></div>
    </section>
    @include('partials.recompensas')
</div>
@endsection
