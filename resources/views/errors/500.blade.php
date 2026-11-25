@extends('layouts.error')
@section('titulo', 'No pudimos cargar la página')
@section('contenido')
<div class="gv-auth-accent">@include('partials.icon', ['icon' => 'drop'])</div>
<h1>No pudimos cargar la página</h1>
<p class="gv-sub">Intenta volver al inicio o recarga la página más tarde.</p>
<div class="gv-actions gv-error-actions"><a class="gv-btn" href="{{ route('inicio') }}">Volver al inicio</a></div>
@endsection
