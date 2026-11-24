@extends('layouts.app')
@section('titulo','Catálogo de logros')
@section('contenido')
<header class="gv-header"><div><h1>Catálogo de logros</h1><p class="gv-sub">Define qué se premia, a partir de qué umbral y cuántos puntos otorga (CU-16).</p></div></header>
@php($l = $editando)
<div class="gv-columns"><section class="gv-card"><div class="gv-scroll"><table class="gv-table"><thead><tr><th></th><th>Logro</th><th>Criterio</th><th>Umbral</th><th>Puntos</th><th>Obtenido por</th><th></th></tr></thead><tbody>
@foreach($logros as $logro)<tr><td style="font-size:20px">{{ $logro->icono }}</td><td><strong>{{ $logro->nombre }}</strong><br><small class="gv-label">{{ $logro->descripcion }}</small></td><td>{{ \App\Models\Logro::CRITERIOS[$logro->criterio] ?? $logro->criterio }}</td><td>{{ $logro->umbral }}</td><td>{{ $logro->puntos }}</td><td>{{ $logro->usuarios_count }}</td><td><a class="gv-link" href="{{ route('admin.logros.edit',$logro) }}">Editar</a></td></tr>@endforeach
</tbody></table></div></section>
<section class="gv-card"><h2>{{ $l ? 'Editar '.$l->nombre : 'Nuevo logro' }}</h2>
<form method="POST" action="{{ $l ? route('admin.logros.update',$l) : route('admin.logros.store') }}">@csrf @if($l) @method('PUT') @endif
<div class="gv-forms"><div class="gv-field"><label for="nombre">Nombre</label><input id="nombre" name="nombre" required maxlength="100" value="{{ old('nombre',$l?->nombre) }}"></div><div class="gv-field"><label for="icono">Ícono (emoji)</label><input id="icono" name="icono" required maxlength="10" value="{{ old('icono',$l?->icono ?? '🏅') }}"></div></div>
<div class="gv-field"><label for="clave">Clave (sin espacios)</label><input id="clave" name="clave" required maxlength="60" pattern="[A-Za-z0-9_\-]+" value="{{ old('clave',$l?->clave) }}"></div>
<div class="gv-field"><label for="descripcion">Descripción</label><input id="descripcion" name="descripcion" required maxlength="255" value="{{ old('descripcion',$l?->descripcion) }}"></div>
<div class="gv-field"><label for="criterio">Criterio</label><select id="criterio" name="criterio" required>@foreach(\App\Models\Logro::CRITERIOS as $valor=>$texto)<option value="{{ $valor }}" @selected(old('criterio',$l?->criterio)===$valor)>{{ $texto }}</option>@endforeach</select></div>
<div class="gv-forms"><div class="gv-field"><label for="umbral">Umbral</label><input id="umbral" name="umbral" type="number" min="1" required value="{{ old('umbral',$l?->umbral) }}"></div><div class="gv-field"><label for="puntos">Puntos</label><input id="puntos" name="puntos" type="number" min="0" required value="{{ old('puntos',$l?->puntos ?? 10) }}"></div></div>
<div class="gv-actions"><button class="gv-btn" type="submit">{{ $l ? 'Guardar cambios' : 'Agregar logro' }}</button>@if($l)<a class="gv-btn alt" href="{{ route('admin.logros.index') }}">Cancelar</a>@endif</div></form></section></div>
@endsection
