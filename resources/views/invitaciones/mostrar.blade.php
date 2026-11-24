@extends('layouts.auth')
@section('titulo','Invitación')
@section('contenido')
<h1>Invitación a una vivienda</h1>
<p class="gv-sub"><strong>{{ $invitacion->anfitrion->name }}</strong> te invitó a unirte a <strong>{{ $invitacion->vivienda->alias }}</strong> en GotaVerde.</p>
@if(! $invitacion->estaVigente())
<div class="gv-alert error" style="margin-top:20px">Esta invitación ya no es válida ({{ $invitacion->estado === 'pendiente' ? 'expiró' : $invitacion->estado }}). Pide una nueva al propietario.</div>
@elseauth
<p class="gv-note">Se envió a {{ $invitacion->email }}. Vence el {{ $invitacion->expira_en->format('d/m/Y H:i') }}.</p>
<form method="POST" action="{{ route('invitaciones.aceptar', $token) }}" style="margin-top:20px">@csrf<div class="gv-actions"><button class="gv-btn" type="submit">Aceptar invitación</button></div></form>
@else
<p class="gv-note">Para aceptarla, inicia sesión o crea una cuenta con el correo <strong>{{ $invitacion->email }}</strong>. Después volverás a esta página.</p>
<div class="gv-actions" style="margin-top:20px"><a class="gv-btn" href="{{ route('login') }}">Iniciar sesión</a><a class="gv-btn alt" href="{{ route('register') }}">Crear cuenta</a></div>
@endif
@endsection
