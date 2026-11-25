@extends('layouts.error')
@section('titulo', 'Demasiadas solicitudes')
@section('contenido')
<div class="gv-auth-accent">@include('partials.icon', ['icon' => 'drop'])</div>
<h1>Demasiadas solicitudes</h1>
<p class="gv-sub">Espera un momento antes de volver a intentarlo.</p>
<div class="gv-actions gv-error-actions"><a class="gv-btn" href="{{ route('inicio') }}">Volver al inicio</a></div>
@endsection
