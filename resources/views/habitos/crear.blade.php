@extends('layouts.app')

@section('titulo', 'Registrar hábito')

@section('contenido')
<div class="gv-botanical-heading gv-habit-heading">
@include('partials.artwork', ['recurso' => 'rama', 'claseIlustracion' => 'gv-botanical-art gv-art-habit'])
@include('partials.screen-heading', ['simbolo' => 'plus', 'encabezado' => 'Registrar un hábito', 'detalle' => 'Anota lo que hiciste en casa y consulta su consumo y ahorro estimados.'])
</div>
<div class="gv-registration gv-habit-layout">
    <section class="gv-card gv-form-panel">
        <div class="gv-header gv-habit-house">
            <div class="gv-home-inline">@include('partials.home-photo', ['hogar' => $vivienda]) <strong>{{ $vivienda->alias }}</strong></div>
            @include('partials.selector-vivienda', ['destino' => 'habitos'])
        </div>
        @if ($actividades->isEmpty())
            <div class="rounded-xl bg-amber-50 p-5 text-sm leading-7 text-amber-900">
                No hay actividades disponibles. Ejecuta <code class="rounded bg-amber-100 px-2 py-1">php artisan db:seed --class=ActividadInicialSeeder</code> en la terminal del proyecto.
            </div>
            <div class="gv-return gv-actions-between"><a class="gv-btn alt" href="{{ route('dashboard') }}">Volver al inicio</a></div>
        @else
            <form method="POST" action="{{ route('habitos.store') }}" class="space-y-5" @unless(session('duplicado')) data-limpiar-formulario @endunless autocomplete="off">
                @csrf
                @if (session('duplicado'))
                    @php($dup = session('duplicado'))
                    {{-- CU-09 5a: ya existe un registro de la misma actividad ese día --}}
                    <div class="gv-confirm" role="alert">
                        <p class="mb-3 text-sm">Ya registraste <strong>{{ $dup['actividad'] }}</strong> ese día ({{ $dup['cantidad'] }} {{ $dup['unidad'] }}{{ $dup['cantidad'] != 1 ? 's' : '' }}). ¿Qué quieres hacer?</p>
                        <div class="gv-actions">
                            <button type="submit" name="duplicado" value="sumar" class="gv-btn sm">Sumarlo al registro existente</button>
                            <button type="submit" name="duplicado" value="separado" class="gv-btn sm alt">Guardar como registro aparte</button>
                        </div>
                    </div>
                @endif
                <div>
                    <label for="actividad_id" class="mb-2 block text-sm font-semibold">Actividad</label>
                    <div class="gv-activity-options" role="group" aria-label="Actividad" hidden>
                        @foreach($actividades as $actividad)
                            <button type="button" class="gv-activity-option" data-actividad="{{ $actividad->id }}" aria-pressed="false">
                                @include('partials.activity-icon', ['activityKey' => $actividad->clave])
                                <span>{{ $actividad->nombre }}</span>
                            </button>
                        @endforeach
                    </div>
                    <select id="actividad_id" name="actividad_id" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 focus:border-[#2E7D6B] focus:outline-none">
                        <option value="">Selecciona una actividad</option>
                        @foreach ($actividades as $actividad)
                            <option value="{{ $actividad->id }}" data-unidad="{{ $actividad->unidad }}" data-tasa="{{ $actividad->litros_por_unidad }}" data-referencia="{{ $actividad->referencia_eficiente }}" @selected(old('actividad_id') == $actividad->id)>
                                {{ $actividad->nombre }} ({{ number_format((float) $actividad->litros_por_unidad, 2) }} L/{{ $actividad->unidad }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="gv-forms gv-habit-fields">
                <div>
                    <label for="cantidad" class="mb-2 block text-sm font-semibold">Cantidad o duración</label>
                    <input id="cantidad" name="cantidad" type="number" min="0.01" max="1000" step="0.01" required placeholder="Ejemplo: 10 minutos" value="{{ old('cantidad') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-[#2E7D6B] focus:outline-none">
                    <p class="mt-2 text-xs text-slate-500" id="unidad-ayuda">La unidad depende de la actividad (por ejemplo, minutos).</p>
                </div>
                <div>
                    <label for="fecha" class="mb-2 block text-sm font-semibold">Fecha de la actividad</label>
                    <input id="fecha" name="fecha" type="date" max="{{ now()->toDateString() }}" required value="{{ old('fecha', now()->toDateString()) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-[#2E7D6B] focus:outline-none">
                </div>
                </div>
                <div id="estimacion-visual" aria-live="polite" class="gv-estimation">
                    <strong>Vista previa:</strong> <span id="litros-previos">Indica actividad y cantidad para calcular.</span>
                </div>
                <div class="gv-actions gv-actions-between">
                    <a class="gv-btn alt" href="{{ route('dashboard') }}">Volver al inicio</a>
                    <button type="submit" class="gv-btn gv-right">Guardar hábito</button>
                </div>
            </form>
            <script>
                (() => {
                    const actividad = document.getElementById('actividad_id');
                    const cantidad = document.getElementById('cantidad');
                    const salida = document.getElementById('litros-previos');
                    const ayuda = document.getElementById('unidad-ayuda');
                    const opcionesVisuales = actividad.form.querySelector('.gv-activity-options');
                    const botones = opcionesVisuales.querySelectorAll('[data-actividad]');
                    const sincronizarOpciones = () => {
                        botones.forEach(boton => boton.setAttribute('aria-pressed', String(boton.dataset.actividad === actividad.value)));
                    };
                    botones.forEach(boton => boton.addEventListener('click', () => {
                        actividad.value = boton.dataset.actividad;
                        actividad.dispatchEvent(new Event('change', { bubbles: true }));
                    }));
                    actividad.addEventListener('change', sincronizarOpciones);
                    actividad.form.addEventListener('reset', () => requestAnimationFrame(sincronizarOpciones));
                    actividad.addEventListener('invalid', () => botones[0]?.focus());
                    actividad.classList.add('gv-activity-native');
                    actividad.tabIndex = -1;
                    opcionesVisuales.hidden = false;
                    sincronizarOpciones();
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
    </section>
    <aside class="gv-habit-aside">
        <section class="gv-card gv-form-guide">@include('partials.icon', ['icon' => 'drop'])
                    <h2>¿Cómo calculamos los litros?</h2><p>Multiplicamos la cantidad por los litros de referencia de la actividad (por ejemplo, ducha: 10 L/min, así que 10 minutos son 100 litros). Si usas menos que la referencia eficiente, la diferencia cuenta como ahorro. El consumo real depende de tus llaves y de la presión del agua.</p>
        </section>
        @include('partials.recompensas')
    </aside>
</div>
@endsection
