<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>@yield('titulo','GotaVerde') | GotaVerde</title>@vite(['resources/css/app.css','resources/js/app.js'])<link rel="stylesheet" href="{{ asset('css/gotaverde.css') }}"></head><body>
<div class="gv-shell"><aside class="gv-nav">@include('partials.brand')<nav aria-label="Navegación principal">
<a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Panel principal</a>
<a class="{{ request()->routeIs('habitos.*') ? 'active' : '' }}" href="{{ route('habitos.create') }}">Registrar hábito</a>
<a class="{{ request()->routeIs('lecturas.*') ? 'active' : '' }}" href="{{ route('lecturas.index') }}">Lecturas</a>
<a class="{{ request()->routeIs('metas.*') ? 'active' : '' }}" href="{{ route('metas.index') }}">Metas</a>
<a class="{{ request()->routeIs('logros.*') ? 'active' : '' }}" href="{{ route('logros.index') }}">Logros y rachas</a>
<a class="{{ request()->routeIs('viviendas.*') ? 'active' : '' }}" href="{{ route('viviendas.index') }}">Mis viviendas</a>
<a class="{{ request()->routeIs('invitaciones.recibidas') ? 'active' : '' }}" href="{{ route('invitaciones.recibidas') }}">Invitaciones</a>
@if(auth()->user()->esAdmin())
<span class="gv-nav-sep">Administración</span>
<a class="{{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}" href="{{ route('admin.usuarios.index') }}">Usuarios</a>
<a class="{{ request()->routeIs('admin.actividades.*') ? 'active' : '' }}" href="{{ route('admin.actividades.index') }}">Actividades</a>
<a class="{{ request()->routeIs('admin.logros.*') ? 'active' : '' }}" href="{{ route('admin.logros.index') }}">Catálogo de logros</a>
@endif
</nav><footer>
<a class="gv-profile-link" href="{{ route('perfil.edit') }}" @if(request()->routeIs('perfil.*')) aria-current="page" @endif>
    @include('partials.avatar', ['usuario' => auth()->user()])
    <span><strong>{{ auth()->user()->name }}</strong><small>Mi perfil · Nivel {{ auth()->user()->nivel() }}</small></span>
</a>
<form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Cerrar sesión</button></form>
</footer></aside><main class="gv-main">@include('partials.alerts')@yield('contenido')</main></div></body></html>
