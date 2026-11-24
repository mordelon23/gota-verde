@extends('layouts.auth')
@section('titulo','Nueva contraseña')
@section('contenido')
<h1>Nueva contraseña</h1><p class="gv-sub">Elige una contraseña de al menos 8 caracteres.</p>
<form method="POST" action="{{ route('password.update') }}">@csrf<input type="hidden" name="token" value="{{ $token }}">
<div class="gv-field"><label for="email">Correo electrónico</label><input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email', $email) }}"></div>
<div class="gv-field"><label for="password">Nueva contraseña</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required></div>
<div class="gv-field"><label for="password_confirmation">Confirmar contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required></div>
<div class="gv-actions"><button class="gv-btn" type="submit">Guardar contraseña</button></div></form>
@endsection
