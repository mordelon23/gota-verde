@extends('layouts.error')
@section('titulo', 'Sesión expirada')
@section('contenido')
<div class="gv-auth-accent">@include('partials.icon', ['icon' => 'drop'])</div>
<h1>Sesión expirada</h1>
<p class="gv-sub">Vuelve a abrir la página e intenta de nuevo.</p>
<div class="gv-actions gv-error-actions"><a class="gv-btn" href="{{ route('inicio') }}">Volver al inicio</a></div>
@endsection
