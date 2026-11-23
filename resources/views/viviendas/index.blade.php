@extends('layouts.app')
@section('titulo','Mis viviendas')
@section('contenido')
<header class="gv-header"><div><h1>Mis viviendas</h1><p class="gv-sub">Administra tus propiedades, su tarifa y quién participa en cada una.</p></div><a class="gv-btn" href="{{ route('viviendas.create') }}">+ Registrar vivienda</a></header>
<div class="gv-columns" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,320px),1fr))">
<section aria-label="Viviendas disponibles">@forelse($viviendas as $item)<form method="POST" action="{{ route('viviendas.seleccionar',$item) }}">@csrf<input type="hidden" name="destino" value="viviendas"><button class="gv-house {{ $vivienda?->id === $item->id ? 'active' : '' }}"><div class="gv-header" style="margin:0 0 6px"><strong>{{ $item->alias }}</strong><span class="gv-tag">{{ ucfirst($roles[$item->id] ?? 'miembro') }}{{ $vivienda?->id === $item->id ? ' · activa' : '' }}</span></div><p class="gv-label">{{ $item->tipo }} · {{ $item->habitantes }} habitantes · {{ $item->alcaldia }} · C.P. {{ $item->codigo_postal }} · ${{ number_format((float)$item->tarifa_m3,2) }}/m³</p></button></form>@empty<div class="gv-card">Todavía no tienes viviendas.</div>@endforelse</section>
@if($vivienda)
<section class="gv-card"><div class="gv-header" style="margin-bottom:10px"><h2 style="margin:0">Miembros de {{ $vivienda->alias }}</h2>@if($esPropietario)<a class="gv-btn sm alt" href="{{ route('viviendas.edit',$vivienda) }}">Editar vivienda</a>@endif</div>
<div class="gv-scroll"><table class="gv-table"><thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th></th></tr></thead><tbody>@foreach($miembros as $miembro)<tr><td>{{ $miembro->name }}{{ $miembro->is(auth()->user()) ? ' (tú)' : '' }}</td><td>{{ $miembro->email }}</td><td>{{ ucfirst($miembro->pivot->rol) }}</td><td>@if($esPropietario && ! $miembro->is(auth()->user()) && $miembro->pivot->rol !== 'propietario')<form class="gv-inline" method="POST" action="{{ route('viviendas.miembros.eliminar',[$vivienda,$miembro]) }}" data-confirmar="¿Quitar el acceso a {{ $miembro->name }}?" onsubmit="return confirm(this.dataset.confirmar)">@csrf @method('DELETE')<button class="gv-link danger">Eliminar</button></form>@endif</td></tr>@endforeach</tbody></table></div>
@if($esPropietario)
<h2 style="margin-top:18px">Invitar a un miembro</h2>
<p class="gv-label" style="margin-bottom:10px">Si la persona ya tiene cuenta, puede aceptar desde <strong>Invitaciones</strong> al iniciar sesión con ese correo.</p>
<form method="POST" action="{{ route('viviendas.invitar',$vivienda) }}" class="gv-row">@csrf<input name="email" type="email" required aria-label="Correo de la persona invitada" placeholder="correo@ejemplo.com" class="gv-select" style="flex:1;min-width:180px" value="{{ old('email') }}"><button class="gv-btn" type="submit">Enviar invitación</button></form>
@if(session('enlace_invitacion'))<div class="gv-confirm" style="margin-top:12px"><label for="enlace-invitacion" class="gv-label">Enlace para compartir · Solo aparece una vez</label><div class="gv-copy"><input id="enlace-invitacion" readonly value="{{ session('enlace_invitacion') }}" onclick="this.select()"><button type="button" class="gv-btn sm" data-copiar-invitacion>Copiar</button></div><p class="gv-label" data-copia-resultado role="status"></p></div>@endif
@if($invitaciones->isNotEmpty())<h2 style="margin-top:18px">Invitaciones pendientes</h2><table class="gv-table"><tbody>@foreach($invitaciones as $invitacion)<tr><td>{{ $invitacion->email }}</td><td class="gv-label">Vence {{ $invitacion->expira_en->format('d/m H:i') }}</td><td><form class="gv-inline" method="POST" action="{{ route('viviendas.invitaciones.cancelar',[$vivienda,$invitacion]) }}">@csrf @method('DELETE')<button class="gv-link danger">Cancelar</button></form></td></tr>@endforeach</tbody></table>@endif
@endif
<form method="POST" action="{{ route('viviendas.salir',$vivienda) }}" style="margin-top:18px" data-confirmar="¿Salir de {{ $vivienda->alias }}? Dejarás de ver sus registros." onsubmit="return confirm(this.dataset.confirmar)">@csrf<button class="gv-link danger">Salir de esta vivienda</button></form>
</section>
@endif
</div>
@endsection
