@extends('layouts.error')
@section('titulo', 'Página no encontrada')
@section('contenido')
<div class="gv-auth-accent">@include('partials.icon', ['icon' => 'drop'])</div>
<h1>Página no encontrada</h1>
<p class="gv-sub">No encontramos la página que solicitaste.</p>
<div class="gv-actions gv-error-actions"><a class="gv-btn" href="{{ route('inicio') }}">Volver al inicio</a></div>
@endsection
