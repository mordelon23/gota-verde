@extends('layouts.app')
@section('titulo','Panel principal')
@section('contenido')
@include('dashboard.invitaciones')
@if(!$vivienda)
<header class="gv-header">
    <h1>Hola, {{ auth()->user()->name }}</h1>
    @include('partials.avatar', ['usuario' => auth()->user()])
</header>
@include('dashboard.primeros-pasos', [
    'paso' => 1,
    'tituloPaso' => 'Registra tu vivienda',
    'descripcionPaso' => 'Empieza por el lugar donde quieres llevar el control del agua. Ten a la mano su código postal. Si alguien te invitó a su vivienda, abre el enlace de la invitación.',
    'accionPaso' => 'Registrar vivienda',
    'enlacePaso' => route('viviendas.create'),
])
@else
<div class="gv-home-grid">
<div class="gv-home-lead">
@include('partials.artwork', ['recurso' => 'rama', 'claseIlustracion' => 'gv-botanical-art gv-art-home'])
@include('dashboard._cabecera')
@if($registros->isEmpty())
    @include('dashboard.primeros-pasos', [
        'paso' => 2,
        'tituloPaso' => 'Anota tu primer hábito',
        'descripcionPaso' => 'Registra algo que ya hiciste en casa. Por ejemplo, elige Ducha y escribe cuántos minutos duró. GotaVerde estima los litros que usaste.',
        'accionPaso' => 'Registrar un hábito',
        'enlacePaso' => route('habitos.create'),
    ])
@elseif($mostrarPrimeraMeta)
    @include('dashboard.primeros-pasos', [
        'paso' => 3,
        'tituloPaso' => 'Crea una meta con ese hábito',
        'descripcionPaso' => $habitoParaMeta
            ? 'Usa «'.$habitoParaMeta->actividad->nombre.'» como punto de partida. Te proponemos un límite por registro y cuántos días intentar cumplirlo.'
            : 'Elige algo que quieras cambiar. Puedes fijar un límite para una actividad o un objetivo en litros ahorrados.',
        'accionPaso' => 'Crear mi primera meta',
        'enlacePaso' => route('metas.index', $habitoParaMeta ? ['habito' => $habitoParaMeta->id] : []).'#nueva-meta',
    ])
@endif
</div>
<aside class="gv-home-summary" aria-label="Resumen de la vivienda">
@php($metaDeAhorro = $metasActivas->where('estado', 'activa')->firstWhere('tipo', 'cuantitativa'))
@php($actividadesDelHistorial = $registros->pluck('actividad', 'actividad_id'))
<article class="gv-card"><p class="gv-label">Litros consumidos (semana)</p><p class="gv-metric">{{ number_format($litrosSemana,2) }} L</p><p class="gv-label">Consumo estimado de la vivienda</p></article>
<article class="gv-card gv-week-saving">
    <h2>Tu progreso de la semana</h2>
    <div class="gv-week-saving-body">
        @if($metaDeAhorro)
            <div class="gv-goal-ring" role="img" style="--avance: {{ (float)$metaDeAhorro->progreso }}%" aria-label="Avance de la meta: {{ number_format((float)$metaDeAhorro->progreso,0) }}%">
                <span aria-hidden="true">{{ number_format((float)$metaDeAhorro->progreso,0) }}%</span>
            </div>
        @endif
        <div>
            <p class="gv-metric">{{ number_format($ahorradosSemana,2) }} L</p>
            <p class="gv-label">Ahorrados esta semana</p>
            <p class="gv-label">Acumulado: {{ number_format($ahorroAcumulado,2) }} L</p>
            @if($metaDeAhorro)
                <p class="gv-ring-caption">Meta activa: {{ $metaDeAhorro->titulo }} · {{ number_format((float)$metaDeAhorro->objetivo_litros,0) }} L</p>
            @endif
        </div>
    </div>
</article>
<article class="gv-card"><p class="gv-label">Ahorro económico (semana)</p><p class="gv-metric">{{ (float)$vivienda->tarifa_m3 > 0 ? '$'.number_format($pesosSemana,2) : '—' }}</p><p class="gv-label">{{ (float)$vivienda->tarifa_m3 > 0 ? 'Acumulado $'.number_format($pesosAcumulado,2).' · $'.number_format((float)$vivienda->tarifa_m3,2).'/m³' : 'Sin tarifa configurada' }}</p></article>
<article class="gv-card gv-week-streak">
    <h2>@include('partials.icon', ['icon' => 'drop']) Tu racha actual</h2>
    <p class="gv-metric">{{ $racha?->actual ?? 0 }} {{ ($racha?->actual ?? 0) === 1 ? 'día' : 'días' }}</p>
    <p class="gv-label">Mejor racha: {{ $racha?->mejor ?? 0 }} días</p>
</article>
<section class="gv-card gv-home-chart"><div class="gv-header"><h2>Consumo diario por actividad (litros)</h2><span class="gv-tag">Semana actual</span></div><div class="gv-chart" aria-label="Consumo estimado por día">@foreach($dias as $dia)@php($segmentos = $series->where('fecha', $dia->toDateString()))<div class="gv-barcol"><small>{{ number_format((float)$segmentos->sum('total'),0) }} L</small><div class="gv-bartrack">@foreach($segmentos as $segmento)<div title="{{ $segmento['actividad'] }}: {{ $segmento['total'] }} L" style="height:{{ max(0,(float)$segmento['total']/$maximo*100) }}%;background:{{ $paleta[$segmento['actividad_id']] }}"></div>@endforeach</div><small>{{ $dia->locale('es')->isoFormat('dd D') }}</small></div>@endforeach</div><div class="gv-legend">@foreach($catalogo as $id=>$nombre)<span><i style="background:{{ $paleta[$id] }}"></i>{{ $nombre }}</span>@endforeach @if($catalogo->isEmpty()) Sin registros esta semana @endif</div></section>
<section class="gv-card gv-home-goals">
    <div class="gv-header">
        <h2>@include('partials.icon', ['icon' => 'flag']) Metas activas</h2>
        <a href="{{ route('metas.index') }}" class="gv-link">Ver todas</a>
    </div>
    @forelse($metasActivas as $meta)
        <div class="gv-home-goal">
            <div class="gv-meta-title">
                <span class="gv-feed-symbol">
                    @if($actividadesDelHistorial->has($meta->actividad_id))
                        @include('partials.activity-icon', ['activityKey' => $actividadesDelHistorial->get($meta->actividad_id)->clave])
                    @else
                        @include('partials.icon', ['icon' => 'target'])
                    @endif
                </span>
                <strong>{{ $meta->titulo }}</strong>
            </div>
            <div class="gv-goal-progress">
                <div class="gv-progress"><span style="width:{{ (float)$meta->progreso }}%"></span></div>
                <strong>{{ number_format((float)$meta->progreso,0) }}%</strong>
            </div>
            <small class="gv-label">Vence el {{ $meta->fecha_fin->format('d/m/Y') }}</small>
        </div>
    @empty
        <div class="gv-home-goal gv-label">No hay metas activas. <a href="{{ route('metas.index') }}">Crea una meta</a>.</div>
    @endforelse

    <div class="gv-home-achievement">
        <h2>Último logro</h2>
        @if($ultimoLogro)
            <div class="gv-meta-title">
                @include('partials.badge-icon', ['badgeKey' => $ultimoLogro->clave])
                <div><strong>{{ $ultimoLogro->nombre }}</strong><p class="gv-label">{{ $ultimoLogro->descripcion }}</p></div>
            </div>
        @else
            <p class="gv-label">Registra tu primer hábito para desbloquear un logro.</p>
        @endif
    </div>
</section>
</aside>
<section class="gv-home-feed" aria-labelledby="actividad-hogar">
    <div class="gv-header">
        <h2 id="actividad-hogar">Actividad del hogar</h2>
        <a class="gv-btn" href="{{ route('habitos.create') }}">@include('partials.icon', ['icon' => 'plus']) Registrar hábito</a>
    </div>
    <div class="gv-feed-list">
        @forelse($registros as $registro)
            <article class="gv-card gv-feed-item">
                @include('partials.avatar', ['usuario' => $registro->usuario, 'mostrarFoto' => $registro->usuario->is(auth()->user())])
                <div class="gv-feed-entry">
                    <h3>{{ $registro->usuario->is(auth()->user()) ? 'Tú' : $registro->usuario->name }}</h3>
                    <p>{{ $registro->actividad->nombre }} · {{ (float)$registro->cantidad }} {{ $registro->actividad->unidad }}{{ (float)$registro->cantidad != 1 ? 's' : '' }}</p>
                    <time class="gv-label">{{ $registro->fecha->format('d/m/Y') }}</time>
                </div>
                <div class="gv-feed-totals">
                    <div><strong>{{ $registro->litros_consumidos }} L</strong><span class="gv-label">Consumidos</span></div>
                    <div><strong>{{ $registro->litros_ahorrados }} L</strong><span class="gv-label">Ahorrados</span></div>
                </div>
                <span class="gv-feed-symbol">@include('partials.activity-icon', ['activityKey' => $registro->actividad->clave])</span>
            </article>
        @empty
            <p class="gv-card gv-empty">Todavía no hay actividades registradas.</p>
        @endforelse
    </div>
    <p class="gv-note">Los valores son estimaciones basadas en referencias de actividad, no mediciones reales.</p>
</section>
</div>
@endif
@endsection
