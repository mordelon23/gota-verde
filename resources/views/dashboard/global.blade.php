@extends('layouts.app')
@section('titulo','Dashboard global')
@section('contenido')
@include('dashboard.invitaciones')
@include('dashboard._cabecera')
<div class="gv-grid">
<article class="gv-card"><p class="gv-label">Viviendas</p><p class="gv-metric">{{ $porVivienda->count() }}</p><p class="gv-label">Donde eres propietario o miembro</p></article>
<article class="gv-card"><p class="gv-label">Agua ahorrada (total)</p><p class="gv-metric gv-saving-value">{{ number_format($totalAhorrados,2) }} L</p><p class="gv-label">Suma de todas las viviendas</p></article>
<article class="gv-card"><p class="gv-label">Ahorro económico (total)</p><p class="gv-metric">${{ number_format($totalPesos,2) }}</p><p class="gv-label">Con la tarifa de cada vivienda</p></article>
<article class="gv-card"><p class="gv-label">Consumo estimado (total)</p><p class="gv-metric">{{ number_format($totalConsumidos,2) }} L</p><p class="gv-label">Hábitos registrados</p></article>
</div>
<div class="gv-columns">
<section class="gv-card"><div class="gv-header"><h2>Tendencia mensual de ahorro (litros)</h2><span class="gv-tag">Últimos 6 meses</span></div>
<div class="gv-chart" aria-label="Litros ahorrados por mes">@foreach($tendencia as $fila)<div class="gv-barcol"><small>{{ number_format($fila['ahorrados'],0) }} L</small><div class="gv-bartrack"><div title="{{ $fila['mes']->locale('es')->isoFormat('MMMM YYYY') }}: {{ $fila['ahorrados'] }} L ahorrados" style="height:{{ $fila['ahorrados']/$maximoMensual*100 }}%;background:var(--leaf)"></div></div><small>{{ ucfirst($fila['mes']->locale('es')->isoFormat('MMM')) }}</small></div>@endforeach</div></section>
<section class="gv-card"><h2>Aporte de cada vivienda</h2>
@foreach($porVivienda as $fila)<div class="gv-hbar"><span>{{ $fila['vivienda']->alias }}</span><div class="gv-progress"><span style="width:{{ $totalAhorrados > 0 ? $fila['ahorrados']/$totalAhorrados*100 : 0 }}%"></span></div><small>{{ number_format($fila['ahorrados'],0) }} L</small></div>@endforeach
<p class="gv-note">Porcentaje del ahorro total que aporta cada vivienda.</p></section>
</div>
<section class="gv-card"><h2>Detalle por vivienda</h2><div class="gv-scroll"><table class="gv-table"><thead><tr><th>Vivienda</th><th>Consumo est.</th><th>Agua ahorrada</th><th>Tarifa</th><th>Ahorro en pesos</th><th></th></tr></thead><tbody>@foreach($porVivienda as $fila)<tr><td><span class="gv-home-inline">@include('partials.home-photo', ['hogar' => $fila['vivienda']]){{ $fila['vivienda']->alias }}</span></td><td>{{ number_format($fila['consumidos'],2) }} L</td><td>{{ number_format($fila['ahorrados'],2) }} L</td><td>{{ (float)$fila['vivienda']->tarifa_m3 > 0 ? '$'.number_format((float)$fila['vivienda']->tarifa_m3,2).'/m³' : 'Sin tarifa' }}</td><td>${{ number_format($fila['pesos'],2) }}</td><td><form method="POST" action="{{ route('viviendas.seleccionar',$fila['vivienda']) }}">@csrf<button class="gv-link">Ver panel</button></form></td></tr>@endforeach</tbody></table></div><p class="gv-note">Valores estimados a partir de los hábitos registrados.</p></section>
@endsection
