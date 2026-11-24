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
<div class="gv-grid">
<article class="gv-card"><p class="gv-label">Litros consumidos (semana)</p><p class="gv-metric" style="color:#2B8BC6">{{ number_format($litrosSemana,2) }} L</p><p class="gv-label">Consumo estimado de la vivienda</p></article>
<article class="gv-card"><p class="gv-label">Agua ahorrada (semana)</p><p class="gv-metric" style="color:#5FA35A">{{ number_format($ahorradosSemana,2) }} L</p><p class="gv-label">Acumulado: {{ number_format($ahorroAcumulado,2) }} L</p></article>
<article class="gv-card"><p class="gv-label">Ahorro económico (semana)</p><p class="gv-metric">{{ (float)$vivienda->tarifa_m3 > 0 ? '$'.number_format($pesosSemana,2) : '—' }}</p><p class="gv-label">{{ (float)$vivienda->tarifa_m3 > 0 ? 'Acumulado $'.number_format($pesosAcumulado,2).' · $'.number_format((float)$vivienda->tarifa_m3,2).'/m³' : 'Sin tarifa configurada' }}</p></article>
<article class="gv-card"><p class="gv-label">Tu racha actual</p><p class="gv-metric" style="color:#C9A227">🔥 {{ $racha?->actual ?? 0 }} {{ ($racha?->actual ?? 0) === 1 ? 'día' : 'días' }}</p><p class="gv-label">Mejor racha: {{ $racha?->mejor ?? 0 }} días</p></article>
</div>
<div class="gv-columns"><section class="gv-card"><div class="gv-header"><h2>Consumo diario por actividad (litros)</h2><span class="gv-tag">Semana actual</span></div><div class="gv-chart" aria-label="Consumo estimado por día">@foreach($dias as $dia)@php($segmentos = $series->where('fecha', $dia->toDateString()))<div class="gv-barcol"><small>{{ number_format((float)$segmentos->sum('total'),0) }} L</small><div class="gv-bartrack">@foreach($segmentos as $segmento)<div title="{{ $segmento['actividad'] }}: {{ $segmento['total'] }} L" style="height:{{ max(0,(float)$segmento['total']/$maximo*100) }}%;background:{{ $paleta[$segmento['actividad_id']] }}"></div>@endforeach</div><small>{{ $dia->locale('es')->isoFormat('dd D') }}</small></div>@endforeach</div><div class="gv-legend">@foreach($catalogo as $id=>$nombre)<span><i style="background:{{ $paleta[$id] }}"></i>{{ $nombre }}</span>@endforeach @if($catalogo->isEmpty()) Sin registros esta semana @endif</div></section>
<section class="gv-card"><div class="gv-header" style="margin-bottom:6px"><h2 style="margin:0">Metas activas</h2><a href="{{ route('metas.index') }}" class="gv-link">Ver todas</a></div>
@forelse($metasActivas as $meta)<div style="margin-bottom:12px"><div class="gv-row" style="justify-content:space-between"><strong style="font-size:13px">{{ $meta->titulo }}</strong><small class="gv-label">{{ number_format((float)$meta->progreso,0) }}%</small></div><div class="gv-progress"><span style="width:{{ (float)$meta->progreso }}%"></span></div><small class="gv-label">Vence el {{ $meta->fecha_fin->format('d/m/Y') }}</small></div>@empty<div class="gv-pending">No hay metas activas. <a href="{{ route('metas.index') }}">Crea una meta</a>.</div>@endforelse
<h2 style="margin-top:14px">Último logro</h2>
@if($ultimoLogro)<div class="gv-row" style="margin-bottom:14px"><span class="gv-medal" style="padding:6px 10px;font-size:22px">{{ $ultimoLogro->icono }}</span><div><strong>{{ $ultimoLogro->nombre }}</strong><p class="gv-label">{{ $ultimoLogro->descripcion }}</p></div></div>@else<div class="gv-pending">Registra tu primer hábito para desbloquear un logro.</div>@endif
<div class="gv-actions"><a class="gv-btn" href="{{ route('habitos.create') }}">+ Registrar hábito</a></div></section></div>
<section class="gv-card"><h2>Últimos hábitos registrados en {{ $vivienda->alias }}</h2><div class="gv-scroll"><table class="gv-table"><thead><tr><th>Fecha</th><th>Miembro</th><th>Actividad</th><th>Cantidad</th><th>Consumo est.</th><th>Ahorro est.</th></tr></thead><tbody>@forelse($registros as $registro)<tr><td>{{ $registro->fecha->format('d/m/Y') }}</td><td>{{ $registro->usuario->is(auth()->user()) ? 'Tú' : $registro->usuario->name }}</td><td>{{ $registro->actividad->nombre }}</td><td>{{ (float)$registro->cantidad }} {{ $registro->actividad->unidad }}{{ (float)$registro->cantidad != 1 ? 's' : '' }}</td><td>{{ $registro->litros_consumidos }} L</td><td>{{ $registro->litros_ahorrados }} L</td></tr>@empty<tr><td colspan="6">Todavía no hay actividades registradas.</td></tr>@endforelse</tbody></table></div><p class="gv-note">Los valores son estimaciones basadas en referencias de actividad, no mediciones reales.</p></section>
@endif
@endsection
