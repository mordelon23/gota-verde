@extends('layouts.app')
@section('titulo','Metas')
@section('contenido')
<header class="gv-header"><div><h1>Metas de {{ $vivienda->alias }}</h1><p class="gv-sub">Las metas son de toda la vivienda: cuenta lo que registra cada miembro.</p></div>@include('partials.selector-vivienda',['destino'=>'metas'])</header>
<div class="gv-columns"><section class="gv-card"><h2>Metas</h2>
@forelse($metas as $fila)@php($meta = $fila['meta'])@php($avance = $fila['avance'])
<div class="gv-meta {{ $meta->estado }}"><div class="gv-meta-head"><div><strong>{{ $meta->titulo }}</strong><p class="gv-label">{{ $meta->tipo === 'cuantitativa' ? 'Cuantitativa' : 'Cualitativa' }}@if($meta->actividad) · {{ $meta->actividad->nombre }}@endif · {{ $meta->fecha_inicio->format('d/m') }} al {{ $meta->fecha_fin->format('d/m/Y') }}</p></div>
<span class="gv-tag {{ ['activa'=>'','cumplida'=>'ok','vencida'=>'warn'][$meta->estado] }}">{{ ucfirst($meta->estado) }}</span></div>
<div class="gv-progress {{ $meta->estado === 'cumplida' ? 'gold' : '' }}"><span style="width:{{ (float)$meta->progreso }}%"></span></div>
<div class="gv-row" style="justify-content:space-between"><small class="gv-label">{{ number_format($avance['actual'], $meta->tipo==='cuantitativa' ? 1 : 0) }} de {{ number_format($avance['objetivo'], $meta->tipo==='cuantitativa' ? 0 : 0) }} {{ $avance['unidad'] }}@if($meta->tipo==='cualitativa') con máximo {{ (float)$meta->limite_cantidad }} {{ $meta->actividad?->unidad }}s por registro @endif · {{ number_format((float)$meta->progreso,0) }}%</small>
@if($meta->creada_por === auth()->id() || auth()->user()->can('update',$vivienda))<form method="POST" action="{{ route('metas.destroy',$meta) }}" onsubmit="return confirm('¿Eliminar esta meta?')">@csrf @method('DELETE')<button class="gv-link danger">Eliminar</button></form>@endif</div></div>
@empty<div class="gv-pending">Todavía no hay metas en esta vivienda. Crea la primera con el formulario.</div>@endforelse
</section>
<section class="gv-card" id="nueva-meta"><h2>Nueva meta</h2>
@if($sugerencia)
    <p class="gv-note" style="margin:0 0 16px">Este ejemplo usa un hábito de tu vivienda. Puedes ajustar el límite, la duración y la fecha de inicio.</p>
@else
    <p class="gv-note" style="margin:0 0 16px">Decide qué quieres lograr. Por ejemplo, tomar duchas de 5 minutos o menos durante 4 días, o ahorrar 300 litros este mes.</p>
@endif
<form method="POST" action="{{ route('metas.store') }}" id="form-meta">@csrf
@php($tipoMeta = old('tipo', $sugerencia['tipo'] ?? 'cuantitativa'))
<div class="gv-field"><label for="tipo">¿Qué quieres lograr?</label><select id="tipo" name="tipo" required><option value="cuantitativa" @selected($tipoMeta === 'cuantitativa')>Ahorrar cierta cantidad de litros</option><option value="cualitativa" @selected($tipoMeta === 'cualitativa')>Poner un límite a un hábito</option></select></div>
<div class="gv-field"><label for="titulo">Nombre de la meta</label><input id="titulo" name="titulo" required maxlength="120" placeholder="Ej. Ahorrar 300 L este mes" value="{{ old('titulo', $sugerencia['titulo'] ?? '') }}"></div>
<div class="gv-field"><label for="actividad_id">Actividad <span class="gv-label" data-solo="cuantitativa">(opcional)</span></label><select id="actividad_id" name="actividad_id"><option value="">Todas las actividades</option>@foreach($actividades as $actividad)<option value="{{ $actividad->id }}" data-unidad="{{ $actividad->unidad }}" @selected((int)old('actividad_id', $sugerencia['actividad_id'] ?? null) === $actividad->id)>{{ $actividad->nombre }}</option>@endforeach</select></div>
<div class="gv-forms gv-meta-target">
    <div class="gv-field" data-solo="cuantitativa"><label for="objetivo_litros">Litros a ahorrar</label><input id="objetivo_litros" name="objetivo_litros" type="number" min="1" step="1" value="{{ old('objetivo_litros') }}" placeholder="300"></div>
    <div class="gv-field" data-solo="cualitativa"><label for="limite_cantidad">Máximo cada vez <span id="unidad-limite"></span></label><input id="limite_cantidad" name="limite_cantidad" type="number" min="0.1" step="0.01" value="{{ old('limite_cantidad', $sugerencia['limite_cantidad'] ?? '') }}" placeholder="5"></div>
    <div class="gv-field">
        <label for="duracion_dias" id="etiqueta-duracion">Duración en días</label>
        <input id="duracion_dias" name="duracion_dias" type="number" min="1" max="366" step="1" required
            value="{{ old('duracion_dias', $sugerencia['duracion_dias'] ?? '') }}" aria-describedby="ayuda-duracion">
    </div>
</div>
<div class="gv-forms">
    <div class="gv-field">
        <label for="fecha_inicio">Fecha de inicio</label>
        <input id="fecha_inicio" name="fecha_inicio" type="date" required value="{{ old('fecha_inicio', today()->toDateString()) }}">
    </div>
    <div class="gv-field">
        <label for="fecha_fin">Fecha de fin</label>
        <output id="fecha_fin" for="fecha_inicio duracion_dias" class="gv-label">Se calcula con el inicio y la duración.</output>
        <small class="gv-label" id="ayuda-duracion">El inicio cuenta como el primer día.</small>
    </div>
</div>
<div class="gv-actions"><button class="gv-btn" type="submit">Crear meta</button></div></form>
<p class="gv-note" data-solo="cuantitativa">El avance suma los litros que GotaVerde estima que ahorraron los miembros con sus hábitos. @unless($tieneConsumoBase)Si quieres comparar consumos de recibos, puedes hacerlo en <a href="{{ route('lecturas.index') }}">Lecturas</a>.@endunless</p>
<p class="gv-note" data-solo="cualitativa">Un día cuenta si alguien registra la actividad y todos los registros del día respetan el límite.</p>
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
    })();
</script>
</section></div>
@endsection
