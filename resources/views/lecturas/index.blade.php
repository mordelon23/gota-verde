@extends('layouts.app')
@section('titulo','Lecturas')
@section('contenido')
<header class="gv-header gv-botanical-heading">
@include('partials.artwork', ['recurso' => 'rama', 'claseIlustracion' => 'gv-botanical-art gv-art-readings'])
<div><h1>{{ $editando ? 'Editar lectura' : 'Registrar recibo o lectura' }}</h1><p class="gv-sub">Captura el consumo de tu boleta bimestral o la lectura del medidor.</p></div>@include('partials.selector-vivienda',['destino'=>'lecturas'])</header>
@if($viviendas->isEmpty())
<section class="gv-card gv-empty"><h2>Primero registra una vivienda</h2><div class="gv-actions"><a class="gv-btn" style="margin-top:15px" href="{{ route('viviendas.create') }}">Registrar vivienda</a></div></section>
@else
@php($l = $editando)
<div class="gv-columns gv-reading-layout"><section class="gv-card gv-form-panel"><h2>@include('partials.icon', ['icon' => 'list']) {{ $l ? 'Editar lectura' : 'Nueva lectura' }}</h2>
<form method="POST" action="{{ $l ? route('lecturas.update',$l) : route('lecturas.store') }}">@csrf @if($l) @method('PUT') @endif
<div class="gv-field"><label for="vivienda_id">¿A qué vivienda corresponde?</label><select id="vivienda_id" name="vivienda_id" required><option value="">Seleccionar vivienda</option>@foreach($viviendas as $item)<option value="{{ $item->id }}" @selected((int)old('vivienda_id',$l?->vivienda_id ?? $vivienda?->id)===$item->id)>{{ $item->alias }}</option>@endforeach</select></div>
<div class="gv-field"><label>Tipo de registro</label><div class="gv-row">@foreach(['recibo'=>'Recibo bimestral','medidor'=>'Lectura de medidor'] as $valor=>$texto)<label class="gv-choice"><input type="radio" name="tipo" value="{{ $valor }}" required @checked(old('tipo',$l?->tipo ?? 'recibo')===$valor)> {{ $texto }}</label>@endforeach</div></div>
<div class="gv-forms"><div class="gv-field"><label for="periodo_inicio">Inicio del periodo</label><input id="periodo_inicio" name="periodo_inicio" type="date" required value="{{ old('periodo_inicio',$l?->periodo_inicio?->toDateString()) }}"></div><div class="gv-field"><label for="periodo_fin">Fin del periodo</label><input id="periodo_fin" name="periodo_fin" type="date" required max="{{ now()->toDateString() }}" value="{{ old('periodo_fin',$l?->periodo_fin?->toDateString()) }}"></div><div class="gv-field"><label for="consumo_m3">Consumo (m³)</label><input id="consumo_m3" name="consumo_m3" type="number" min="0" step="0.001" required value="{{ old('consumo_m3',$l ? (float)$l->consumo_m3 : null) }}"></div><div class="gv-field"><label for="importe">Importe pagado (opcional)</label><input id="importe" name="importe" type="number" min="0" step="0.01" value="{{ old('importe',$l?->importe) }}"></div></div>
@if(session('traslape_id'))<p class="gv-note" style="margin:0 0 12px"><a href="{{ route('lecturas.edit',session('traslape_id')) }}"><strong>Editar la lectura existente →</strong></a></p>@endif
<div class="gv-actions"><button class="gv-btn" type="submit">{{ $l ? 'Guardar cambios' : 'Guardar lectura' }}</button>@if($l)<a class="gv-btn alt" href="{{ route('lecturas.index') }}">Cancelar</a>@endif</div></form>
<p class="gv-note">1 m³ = 1,000 litros. La primera lectura de una vivienda se guarda como consumo base.</p></section>
<section class="gv-card gv-reading-comparison"><h2>@include('partials.icon', ['icon' => 'chart']) Comparación con el periodo anterior</h2>
@if(! $ultima)<div class="gv-pending">Sin lecturas en {{ $vivienda->alias }}.</div>
@elseif($ultima['esBase'])<div class="gv-pending">La última lectura es el consumo base. Registra el siguiente periodo para comparar.</div>
@else
<div class="gv-compare"><div><span class="gv-label">Periodo anterior</span><strong>{{ number_format($ultima['anterior']->litros(),0) }} L</strong></div><div><span class="gv-label">Periodo actual</span><strong>{{ number_format($ultima['lectura']->litros(),0) }} L</strong></div><div><span class="gv-label">{{ $ultima['ahorroLitros'] >= 0 ? 'Ahorro' : 'Aumento' }}</span><strong class="{{ $ultima['ahorroLitros'] >= 0 ? 'gv-pos' : 'gv-neg' }}">{{ number_format(abs($ultima['ahorroLitros']),0) }} L</strong>@if((float)$vivienda->tarifa_m3 > 0)<small class="gv-label">≈ ${{ number_format(abs($ultima['ahorroPesos']),2) }}</small>@endif</div></div>
<p class="gv-note" style="margin-top:0">Se compara el promedio diario de cada periodo, porque no siempre duran lo mismo.</p>
@endif
</section></div>
<section class="gv-card gv-reading-history"><h2>@include('partials.icon', ['icon' => 'calendar']) Historial de {{ $vivienda->alias }}</h2><div class="gv-scroll"><table class="gv-table"><thead><tr><th>Periodo</th><th>Tipo</th><th>m³</th><th>L/día</th><th>vs. anterior</th><th>Capturó</th><th></th></tr></thead><tbody>
@forelse($historial as $fila)@php($lec = $fila['lectura'])<tr><td>{{ $lec->periodo_inicio->format('d/m/y') }} – {{ $lec->periodo_fin->format('d/m/y') }}</td><td>{{ $lec->tipo === 'recibo' ? 'Recibo' : 'Medidor' }}</td><td>{{ (float)$lec->consumo_m3 }}</td><td>{{ $fila['litrosPorDia'] }}</td><td>@if($fila['esBase'])<span class="gv-tag">Base</span>@else<span class="{{ $fila['ahorroLitros'] >= 0 ? 'gv-pos' : 'gv-neg' }}">{{ $fila['ahorroLitros'] >= 0 ? '−' : '+' }}{{ number_format(abs($fila['ahorroLitros']),0) }} L</span>@endif</td><td><span class="gv-person-inline">@include('partials.avatar', ['usuario' => $lec->usuario, 'mostrarFoto' => $lec->usuario->is(auth()->user())]) {{ $lec->usuario->is(auth()->user()) ? 'Tú' : $lec->usuario->name }}</span></td><td><div class="gv-row"><a class="gv-link" href="{{ route('lecturas.edit',$lec) }}">Editar</a>@if($lec->user_id === auth()->id() || auth()->user()->can('update',$vivienda))<form class="gv-inline" method="POST" action="{{ route('lecturas.destroy',$lec) }}" onsubmit="return confirm('¿Eliminar esta lectura?')">@csrf @method('DELETE')<button class="gv-link danger">Eliminar</button></form>@endif</div></td></tr>
@empty<tr><td colspan="7">Sin lecturas registradas.</td></tr>@endforelse</tbody></table></div></section>
@endif
@endsection
