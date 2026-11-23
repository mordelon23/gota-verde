@extends('layouts.app')
@section('titulo','Editar vivienda')
@section('contenido')
<div style="max-width:760px">
<a href="{{ route('viviendas.index') }}" style="display:inline-flex;margin-bottom:18px;font-weight:600">Volver a mis viviendas</a>
<section class="gv-card" style="margin-bottom:16px"><h1 style="margin-bottom:6px">Editar {{ $vivienda->alias }}</h1><p class="gv-sub" style="margin-bottom:20px">Solo el propietario puede cambiar el perfil. La nueva tarifa se aplica a todos los cálculos en pesos.</p>
<form method="POST" action="{{ route('viviendas.update',$vivienda) }}">@csrf @method('PUT')
<div class="gv-forms">
@include('partials.codigo-postal', ['codigoActual' => $vivienda->codigo_postal, 'alcaldiaActual' => $vivienda->alcaldia])
<div class="gv-field wide"><label for="alias">Nombre o alias</label><input id="alias" name="alias" required maxlength="100" value="{{ old('alias',$vivienda->alias) }}"></div>
<div class="gv-field"><label for="tipo">Tipo de vivienda</label><select id="tipo" name="tipo" required>@foreach(['Casa','Departamento','Otro'] as $tipo)<option @selected(old('tipo',$vivienda->tipo)===$tipo)>{{ $tipo }}</option>@endforeach</select></div>
<div class="gv-field"><label for="habitantes">Número de habitantes</label><input id="habitantes" name="habitantes" type="number" min="1" max="30" required value="{{ old('habitantes',$vivienda->habitantes) }}"></div>
<div class="gv-field wide"><label for="tarifa_m3">Tarifa por m³ en pesos</label><input id="tarifa_m3" name="tarifa_m3" type="number" min="0" max="10000" step="0.01" value="{{ old('tarifa_m3',(float)$vivienda->tarifa_m3) }}"><small class="gv-note" style="margin:0">Valor de referencia sugerido: ${{ number_format($tarifaSugerida,2) }}/m³.</small></div>
</div>
<button class="gv-btn" type="submit">Guardar cambios</button></form></section>
<section class="gv-card" style="border-color:#efb6ad"><h2>Eliminar vivienda</h2><p class="gv-label" style="margin-bottom:12px">Se borrarán sus hábitos, lecturas, metas y rachas. Esta acción no se puede deshacer.</p>
<form method="POST" action="{{ route('viviendas.destroy',$vivienda) }}">@csrf @method('DELETE')<div class="gv-field"><label for="confirmacion">Escribe <strong>{{ $vivienda->alias }}</strong> para confirmar</label><input id="confirmacion" name="confirmacion" autocomplete="off" required></div><button class="gv-btn danger" type="submit">Eliminar definitivamente</button></form></section>
</div>
@endsection
