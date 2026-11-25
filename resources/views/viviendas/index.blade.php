@extends('layouts.app')
@section('titulo','Mis viviendas')
@section('contenido')
<header class="gv-header gv-botanical-heading">
@include('partials.artwork', ['recurso' => 'rama', 'claseIlustracion' => 'gv-botanical-art gv-art-houses'])
<div><h1>Mis viviendas</h1><p class="gv-sub">Administra tus propiedades, su tarifa y quién participa en cada una.</p></div><a class="gv-btn" href="{{ route('viviendas.create') }}">@include('partials.icon', ['icon' => 'plus']) Agregar vivienda</a></header>
<div class="gv-house-directory">
<section class="gv-house-list" aria-label="Viviendas disponibles">
@forelse($viviendas as $item)
    <article class="gv-house-item {{ $vivienda?->id === $item->id ? 'active' : '' }}">
        @include('partials.home-photo', ['hogar' => $item])
        <div>
            <h2>{{ $item->alias }}</h2>
            <p>{{ $item->tipo }} · {{ $item->habitantes }} habitantes</p>
            <p class="gv-label">{{ $item->alcaldia }} · C.P. {{ $item->codigo_postal }} · ${{ number_format((float)$item->tarifa_m3,2) }}/m³</p>
            <p class="gv-label">{{ ucfirst($roles[$item->id] ?? 'miembro') }}</p>
        </div>
        <form method="POST" action="{{ route('viviendas.seleccionar',$item) }}">
            @csrf
            <input type="hidden" name="destino" value="viviendas">
            <button class="gv-btn {{ $vivienda?->id === $item->id ? '' : 'alt' }}" type="submit" aria-pressed="{{ $vivienda?->id === $item->id ? 'true' : 'false' }}">
                {{ $vivienda?->id === $item->id ? 'Seleccionada' : 'Seleccionar' }}
            </button>
        </form>
    </article>
@empty
    <div class="gv-card gv-house-empty">@include('partials.home-photo', ['hogar' => null]) <div><h2>Todavía no tienes viviendas.</h2><a href="{{ route('viviendas.create') }}">Registrar vivienda</a></div></div>
@endforelse
</section>
@if($vivienda)
<section class="gv-card gv-member-panel"><div class="gv-header"><div class="gv-home-inline">@include('partials.home-photo', ['hogar' => $vivienda])<div><h2>Miembros de {{ $vivienda->alias }}</h2><p class="gv-label">{{ $miembros->count() }} {{ $miembros->count() === 1 ? 'integrante' : 'integrantes' }}</p></div></div>@if($esPropietario)<a class="gv-btn sm alt" href="{{ route('viviendas.edit',$vivienda) }}">Editar vivienda</a>@endif</div>
<ul class="gv-member-list">@foreach($miembros as $miembro)<li class="gv-member-row">
@include('partials.avatar', ['usuario' => $miembro, 'mostrarFoto' => $miembro->is(auth()->user())])
<div><strong>{{ $miembro->name }}{{ $miembro->is(auth()->user()) ? ' (tú)' : '' }}</strong><p class="gv-label">{{ $miembro->email }}</p></div>
<span class="gv-tag">{{ ucfirst($miembro->pivot->rol) }}</span><div class="gv-actions">@if($esPropietario && ! $miembro->is(auth()->user()) && $miembro->pivot->rol !== 'propietario')<form class="gv-inline" method="POST" action="{{ route('viviendas.miembros.eliminar',[$vivienda,$miembro]) }}" data-confirmar="¿Quitar el acceso a {{ $miembro->name }}?" onsubmit="return confirm(this.dataset.confirmar)">@csrf @method('DELETE')<button class="gv-link danger">Eliminar</button></form>@endif</div></li>@endforeach</ul>
@if($esPropietario)
<h2 style="margin-top:18px">Invitar a un miembro</h2>
<p class="gv-label" style="margin-bottom:10px">Ingresa el correo registrado de la persona. Podrá aceptar desde <strong>Invitaciones</strong> al iniciar sesión.</p>
<form method="POST" action="{{ route('viviendas.invitar',$vivienda) }}" class="gv-row">@csrf<input name="email" type="email" required aria-label="Correo de la persona invitada" placeholder="correo@ejemplo.com" class="gv-select" style="flex:1;min-width:180px" value="{{ old('email') }}"><button class="gv-btn" type="submit">Enviar invitación</button></form>
@if(session('enlace_invitacion'))<div class="gv-confirm" style="margin-top:12px"><label for="enlace-invitacion" class="gv-label">Enlace para compartir · Solo aparece una vez</label><div class="gv-copy"><input id="enlace-invitacion" readonly value="{{ session('enlace_invitacion') }}" onclick="this.select()"><button type="button" class="gv-btn sm" data-copiar-invitacion>Copiar</button></div><p class="gv-label" data-copia-resultado role="status"></p></div>@endif
@if($invitaciones->isNotEmpty())<h2 style="margin-top:18px">Invitaciones pendientes</h2><table class="gv-table"><tbody>@foreach($invitaciones as $invitacion)<tr><td>{{ $invitacion->email }}</td><td class="gv-label">Vence {{ $invitacion->expira_en->format('d/m H:i') }}</td><td><form class="gv-inline" method="POST" action="{{ route('viviendas.invitaciones.cancelar',[$vivienda,$invitacion]) }}">@csrf @method('DELETE')<button class="gv-link danger">Cancelar</button></form></td></tr>@endforeach</tbody></table>@endif
@endif
<form method="POST" action="{{ route('viviendas.salir', $vivienda, absolute: false) }}" class="gv-actions" style="margin-top:18px" data-confirmar="¿Salir de {{ $vivienda->alias }}? Dejarás de ver sus registros." onsubmit="return confirm(this.dataset.confirmar)">@csrf<button class="gv-link danger">Abandonar vivienda</button></form>
</section>
@endif
</div>
@endsection
