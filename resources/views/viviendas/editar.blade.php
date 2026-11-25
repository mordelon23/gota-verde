@extends('layouts.app')
@section('titulo','Editar vivienda')
@section('contenido')
<div class="gv-edit-house">
<section class="gv-card gv-form-panel"><h1 style="margin-bottom:6px">Editar {{ $vivienda->alias }}</h1><p class="gv-sub" style="margin-bottom:20px">Solo el propietario puede cambiar el perfil. La nueva tarifa se aplica a todos los cálculos en pesos.</p>
<form method="POST" action="{{ route('viviendas.update',$vivienda) }}" class="gv-house-editor" enctype="multipart/form-data">@csrf @method('PUT')
@include('partials.home-photo-field', ['hogar' => $vivienda])
<div class="gv-house-fields">
@include('partials.codigo-postal', ['codigoActual' => $vivienda->codigo_postal, 'alcaldiaActual' => $vivienda->alcaldia])
<div class="gv-field wide"><label for="alias">Nombre o alias</label><input id="alias" name="alias" required maxlength="100" value="{{ old('alias',$vivienda->alias) }}"></div>
<div class="gv-field"><label for="tipo">Tipo de vivienda</label><select id="tipo" name="tipo" required>@foreach(['Casa','Departamento','Otro'] as $tipo)<option @selected(old('tipo',$vivienda->tipo)===$tipo)>{{ $tipo }}</option>@endforeach</select></div>
<div class="gv-field"><label for="habitantes">Número de habitantes</label><input id="habitantes" name="habitantes" type="number" min="1" max="30" required value="{{ old('habitantes',$vivienda->habitantes) }}"></div>
<div class="gv-field wide"><label for="tarifa_m3">Tarifa por m³ en pesos</label><input id="tarifa_m3" name="tarifa_m3" type="number" min="0" max="10000" step="0.01" value="{{ old('tarifa_m3',(float)$vivienda->tarifa_m3) }}"><small class="gv-note" style="margin:0">Valor de referencia sugerido: ${{ number_format($tarifaSugerida,2) }}/m³.</small></div>
<div class="gv-actions gv-actions-between wide"><a class="gv-btn alt" href="{{ route('viviendas.index') }}">Volver a mis viviendas</a><button class="gv-btn" type="submit">Guardar cambios</button></div>
</div></form></section>
<section class="gv-card gv-destructive"><h2>Eliminar vivienda</h2><p class="gv-label" style="margin-bottom:12px">Se borrarán sus hábitos, lecturas, metas y rachas. Esta acción no se puede deshacer.</p>
<form method="POST" action="{{ route('viviendas.destroy',$vivienda) }}">@csrf @method('DELETE')<div class="gv-field"><label for="confirmacion">Escribe <strong>{{ $vivienda->alias }}</strong> para confirmar</label><input id="confirmacion" name="confirmacion" autocomplete="off" required></div><div class="gv-actions"><button class="gv-btn danger" type="submit">Eliminar definitivamente</button></div></form></section>
</div>
@endsection
