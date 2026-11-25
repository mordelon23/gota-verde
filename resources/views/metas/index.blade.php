@extends('layouts.app')
@section('titulo', 'Metas')
@section('contenido')
@php($hayErroresDeMeta = $errors->hasAny(['tipo', 'titulo', 'actividad_id', 'objetivo_litros', 'limite_cantidad', 'dias_objetivo', 'duracion_dias', 'fecha_inicio', 'fecha_fin']))
<div class="gv-goals-page">
    <header class="gv-header gv-botanical-heading gv-goals-heading">
        @include('partials.artwork', ['recurso' => 'rama', 'claseIlustracion' => 'gv-botanical-art gv-art-goals-panel'])
        <div><h1>Mis metas</h1><p class="gv-sub">Pequeños cambios, grandes ahorros.</p></div>
        <div class="gv-actions gv-goal-header-actions">
            <div class="gv-goal-house-picker">
                @include('partials.icon', ['icon' => 'households'])
                @include('partials.selector-vivienda', ['destino' => 'metas'])
            </div>
            <a class="gv-btn" href="#nueva-meta" aria-haspopup="dialog" data-abrir-meta>@include('partials.icon', ['icon' => 'plus']) Registrar meta</a>
        </div>
    </header>

    <div class="gv-goal-summary">
        <article class="gv-card gv-goal-stat">
            <span class="gv-feed-symbol">@include('partials.icon', ['icon' => 'target'])</span>
            <div><p class="gv-label">Metas activas</p><strong>{{ $metas->where('meta.estado', 'activa')->count() }}</strong></div>
        </article>
        <article class="gv-card gv-goal-stat">
            <span class="gv-feed-symbol">@include('partials.icon', ['icon' => 'award'])</span>
            <div><p class="gv-label">Metas completadas</p><strong>{{ $metas->where('meta.estado', 'cumplida')->count() }}</strong></div>
        </article>
    </div>

    <section class="gv-goal-objectives" aria-labelledby="titulo-objetivos">
        <div class="gv-header">
            <h2 id="titulo-objetivos">Tus objetivos</h2>
            @if($metas->isNotEmpty())
                <div class="gv-segments gv-goal-filters" role="group" aria-label="Filtrar objetivos">
                    <button type="button" data-filtro-meta="todas" aria-pressed="true">Todas</button>
                    <button type="button" data-filtro-meta="activa" aria-pressed="false">Activas</button>
                    <button type="button" data-filtro-meta="cumplida" aria-pressed="false">Completadas</button>
                </div>
            @endif
        </div>

        <div class="gv-goal-directory">
            @forelse($metas as $fila)
                @php($meta = $fila['meta'])
                @php($avance = $fila['avance'])
                <article class="gv-card gv-meta {{ $meta->estado }}" data-estado-meta="{{ $meta->estado }}">
                    <div class="gv-meta-head">
                        <div class="gv-meta-title">
                            <span class="gv-feed-symbol">
                                @if($meta->actividad)
                                    @include('partials.activity-icon', ['activityKey' => $meta->actividad->clave])
                                @else
                                    @include('partials.icon', ['icon' => 'drop'])
                                @endif
                            </span>
                            <div>
                                <h3>{{ $meta->titulo }}</h3>
                                <p class="gv-label">{{ $meta->tipo === 'cuantitativa' ? 'Ahorrar litros' : 'Poner un límite a un hábito' }}@if($meta->actividad) · {{ $meta->actividad->nombre }}@endif</p>
                            </div>
                        </div>
                        <span class="gv-tag {{ ['activa'=>'','cumplida'=>'ok','vencida'=>'warn'][$meta->estado] }}">{{ ucfirst($meta->estado) }}</span>
                    </div>

                    <div class="gv-goal-progress">
                        <div class="gv-progress" aria-hidden="true"><span style="width:{{ (float)$meta->progreso }}%"></span></div>
                        <strong>{{ number_format((float)$meta->progreso,0) }}%</strong>
                    </div>

                    <div class="gv-goal-card-footer">
                        <div>
                            <p><strong>{{ number_format($avance['actual'], $meta->tipo === 'cuantitativa' ? 1 : 0) }} de {{ number_format($avance['objetivo'], 0) }} {{ $avance['unidad'] }}</strong>@if($meta->tipo === 'cualitativa') <span class="gv-label">· Máx. {{ (float)$meta->limite_cantidad }} {{ $meta->actividad?->unidad }}s por registro</span>@endif</p>
                            <p class="gv-label">{{ $meta->fecha_inicio->format('d/m/Y') }} al {{ $meta->fecha_fin->format('d/m/Y') }}</p>
                            @if($meta->estado === 'activa')
                                @php($diasRestantes = max(0, (int) today()->diffInDays($meta->fecha_fin)))
                                <p class="gv-label">{{ $diasRestantes === 0 ? 'Termina hoy' : ($diasRestantes === 1 ? 'Queda 1 día' : 'Quedan '.$diasRestantes.' días') }}</p>
                            @endif
                        </div>
                        @if($meta->creada_por === auth()->id() || auth()->user()->can('update', $vivienda))
                            <form method="POST" action="{{ route('metas.destroy', $meta) }}" onsubmit="return confirm('¿Eliminar esta meta?')">
                                @csrf
                                @method('DELETE')
                                <button class="gv-link danger" type="submit">Eliminar</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="gv-card gv-goal-empty">
                    @include('partials.artwork', ['recurso' => 'gota-meta', 'claseIlustracion' => 'gv-goal-empty-art'])
                    <div><h3>Todavía no hay metas en esta vivienda</h3><p class="gv-label">Registra tu primer objetivo para empezar a seguir el ahorro de agua de tu hogar.</p></div>
                </div>
            @endforelse
        </div>
        <p class="gv-card" data-metas-sin-resultados hidden>No hay metas en este estado.</p>
    </section>
</div>

<dialog class="gv-meta-dialog" id="nueva-meta" aria-labelledby="titulo-nueva-meta" aria-describedby="descripcion-nueva-meta" @if($hayErroresDeMeta) data-reabrir-meta @endif>
    <header class="gv-meta-editor-heading">
        <div>
            <h2 id="titulo-nueva-meta">Registrar meta</h2>
            <p class="gv-sub" id="descripcion-nueva-meta">Define tu próximo objetivo de ahorro.</p>
        </div>
        <button class="gv-link gv-meta-dialog-close" type="button" aria-label="Cerrar ventana" data-cerrar-meta>×</button>
    </header>

    @if($hayErroresDeMeta)
        <div class="gv-alert error" role="alert" tabindex="-1" data-meta-error>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="gv-meta-editor">
        <aside class="gv-meta-guide">
            @include('partials.artwork', ['recurso' => 'gota-meta', 'claseIlustracion' => 'gv-meta-guide-art'])
            <h3>Cada meta cuenta.</h3>
            <p>Construye hábitos más sostenibles junto con tu hogar.</p>
        </aside>
<div class="gv-meta-form">
@if($sugerencia)
    <p class="gv-label">Este ejemplo usa un hábito de tu vivienda. Puedes ajustar el límite, la duración y la fecha de inicio.</p>
@endif
<form method="POST" action="{{ route('metas.store') }}" id="form-meta">@csrf
@php($tipoMeta = old('tipo', $sugerencia['tipo'] ?? 'cuantitativa'))
<div class="gv-field"><label for="tipo">@include('partials.icon', ['icon' => 'target']) ¿Qué quieres lograr?</label><select id="tipo" name="tipo" required><option value="cuantitativa" @selected($tipoMeta === 'cuantitativa')>Ahorrar cierta cantidad de litros</option><option value="cualitativa" @selected($tipoMeta === 'cualitativa')>Poner un límite a un hábito</option></select></div>
<div class="gv-field"><label for="titulo">@include('partials.icon', ['icon' => 'flag']) Nombre de la meta</label><input id="titulo" name="titulo" required maxlength="120" placeholder="Ej. Ahorrar 300 L este mes" value="{{ old('titulo', $sugerencia['titulo'] ?? '') }}"></div>
<div class="gv-forms gv-meta-form-row">
<div class="gv-field"><label for="actividad_id"><svg class="gv-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use id="icono-meta-actividad" href="{{ asset('gotaverde-icons.svg') }}#list"></use></svg> Actividad <span class="gv-label" data-solo="cuantitativa">(opcional)</span></label><select id="actividad_id" name="actividad_id"><option value="">Todas las actividades</option>@foreach($actividades as $actividad)<option value="{{ $actividad->id }}" data-icono="@include('partials.activity-symbol', ['activityKey' => $actividad->clave])" data-unidad="{{ $actividad->unidad }}" @selected((int)old('actividad_id', $sugerencia['actividad_id'] ?? null) === $actividad->id)>{{ $actividad->nombre }}</option>@endforeach</select></div>

    <div class="gv-field" data-solo="cuantitativa"><label for="objetivo_litros">@include('partials.icon', ['icon' => 'drop']) Litros a ahorrar</label><input id="objetivo_litros" name="objetivo_litros" type="number" min="1" step="1" value="{{ old('objetivo_litros') }}" placeholder="300"></div>
    <div class="gv-field" data-solo="cualitativa"><label for="limite_cantidad">@include('partials.icon', ['icon' => 'drop']) Máximo cada vez <span id="unidad-limite"></span></label><input id="limite_cantidad" name="limite_cantidad" type="number" min="0.1" step="0.01" value="{{ old('limite_cantidad', $sugerencia['limite_cantidad'] ?? '') }}" placeholder="5"></div>
</div>
<div class="gv-forms gv-meta-form-row">
    <div class="gv-field">
        <label for="duracion_dias">@include('partials.icon', ['icon' => 'calendar']) <span id="etiqueta-duracion">Duración en días</span></label>
        <input id="duracion_dias" name="duracion_dias" type="number" min="1" max="366" step="1" required
            value="{{ old('duracion_dias', $sugerencia['duracion_dias'] ?? '') }}" aria-describedby="ayuda-duracion">
    </div>
    <div class="gv-field">
        <label for="fecha_inicio">@include('partials.icon', ['icon' => 'calendar']) Fecha de inicio</label>
        <input id="fecha_inicio" name="fecha_inicio" type="date" required value="{{ old('fecha_inicio', today()->toDateString()) }}">
    </div>
    <div class="gv-field wide">
        <label for="fecha_fin">@include('partials.icon', ['icon' => 'calendar']) Fecha de fin</label>
        <output id="fecha_fin" for="fecha_inicio duracion_dias" class="gv-label">Se calcula con el inicio y la duración.</output>
        <small class="gv-label" id="ayuda-duracion">El inicio cuenta como el primer día.</small>
    </div>
</div>
<div class="gv-meta-dialog-actions">
    <button class="gv-btn" type="submit">@include('partials.icon', ['icon' => 'leaf']) Guardar meta</button>
    <button class="gv-btn alt" type="button" data-cerrar-meta>Cancelar</button>
</div>
</form>
<p class="gv-note" data-solo="cuantitativa">El avance suma los litros que GotaVerde estima que ahorraron los miembros con sus hábitos. @unless($tieneConsumoBase)Si quieres comparar consumos de recibos, puedes hacerlo en <a href="{{ route('lecturas.index') }}">Lecturas</a>.@endunless</p>
<p class="gv-note" data-solo="cualitativa">Un día cuenta si alguien registra la actividad y todos los registros del día respetan el límite.</p>

        </div>
    </div>
</dialog>
<script src="{{ asset('js/metas-interfaz.js') }}?v={{ filemtime(public_path('js/metas-interfaz.js')) }}" defer></script>
<script>
    (() => {
        const tipo = document.getElementById('tipo');
        const actividad = document.getElementById('actividad_id');
        const unidad = document.getElementById('unidad-limite');
        const etiquetaDuracion = document.getElementById('etiqueta-duracion');
        const duracion = document.getElementById('duracion_dias');
        const inicio = document.getElementById('fecha_inicio');
        const fin = document.getElementById('fecha_fin');
        const formatoFecha = new Intl.DateTimeFormat('es-MX', { dateStyle: 'long', timeZone: 'UTC' });

        const calcularFechaFin = () => {
            const fecha = inicio.valueAsDate;
            const dias = duracion.valueAsNumber;

            if (!fecha || !Number.isInteger(dias) || dias < 1 || dias > 366) {
                fin.textContent = 'Se calcula con el inicio y la duración.';
                return;
            }

            fecha.setUTCDate(fecha.getUTCDate() + dias - 1);
            fin.textContent = formatoFecha.format(fecha);
        };

        const mostrar = () => {
            document.querySelectorAll('[data-solo]').forEach((elemento) => {
                const visible = elemento.dataset.solo === tipo.value;
                elemento.style.display = visible ? '' : 'none';
                elemento.querySelectorAll('input').forEach((campo) => {
                    campo.required = visible;
                    campo.disabled = !visible;
                });
            });
            const medida = actividad.selectedOptions[0]?.dataset.unidad;
            unidad.textContent = medida ? `(${medida})` : '';
            actividad.required = tipo.value === 'cualitativa';
            actividad.options[0].textContent = actividad.required ? 'Elige una actividad' : 'Todas las actividades';
            etiquetaDuracion.textContent = actividad.required
                ? 'Días para cumplirlo'
                : 'Duración en días';
        };
        tipo.addEventListener('change', mostrar);
        actividad.addEventListener('change', mostrar);
        duracion.addEventListener('input', calcularFechaFin);
        inicio.addEventListener('input', calcularFechaFin);
        mostrar();
        calcularFechaFin();

        const iconoActividad = document.getElementById('icono-meta-actividad');
        const sprite = iconoActividad.getAttribute('href').split('#')[0];
        const mostrarIconoActividad = () => {
            iconoActividad.setAttribute('href', sprite + '#' + (actividad.selectedOptions[0]?.dataset.icono || 'list'));
        };
        actividad.addEventListener('change', mostrarIconoActividad);
        mostrarIconoActividad();
    })();
</script>
@endsection
