@extends('layouts.auth')
@section('titulo','Recuperar contraseña')
@section('contenido')
<h1>Recuperar contraseña</h1><p class="gv-sub">Escribe el correo de tu cuenta y te enviaremos un enlace para crear una nueva contraseña.</p>
<form method="POST" action="{{ route('password.email') }}">@csrf
<div class="gv-field"><label for="email">Correo electrónico</label><input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"></div>
<div class="gv-actions"><button class="gv-btn" type="submit">Enviar enlace</button></div></form>
<p class="gv-auth-bottom"><a href="{{ route('login') }}">← Volver a iniciar sesión</a></p>
@endsection
