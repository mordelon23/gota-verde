@extends('layouts.error')
@section('titulo', 'Acceso restringido')
@section('contenido')
<div class="gv-auth-accent">@include('partials.icon', ['icon' => 'drop'])</div>
<h1>Acceso restringido</h1>
<p class="gv-sub">Tu cuenta no tiene acceso a esta sección.</p>
<div class="gv-actions gv-error-actions"><a class="gv-btn" href="{{ route('inicio') }}">Volver al inicio</a></div>
@endsection
