@extends('layouts.error')
@section('titulo', 'GotaVerde está en mantenimiento')
@section('contenido')
<div class="gv-auth-accent">@include('partials.icon', ['icon' => 'drop'])</div>
<h1>GotaVerde está en mantenimiento</h1>
<p class="gv-sub">Vuelve a intentarlo más tarde.</p>
<div class="gv-actions gv-error-actions"><a class="gv-btn" href="{{ route('inicio') }}">Volver al inicio</a></div>
@endsection
