<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('titulo','GotaVerde') | GotaVerde</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/gotaverde.css') }}?v={{ filemtime(public_path('css/gotaverde.css')) }}">
    <script src="{{ asset('js/vivienda-foto.js') }}?v={{ filemtime(public_path('js/vivienda-foto.js')) }}" defer></script>
</head>
<body class="gv-interior">
<div class="gv-shell">
    <aside class="gv-nav">
        @include('partials.brand')
        <nav class="gv-rail-links" aria-label="Navegación principal">
            @include('partials.nav-links')
        </nav>
        <div class="gv-rail-logout">
            @include('partials.logout-button')
        </div>
    </aside>

    <div class="gv-workspace">
        <header class="gv-topbar">
            <a class="gv-brand" href="{{ route('inicio') }}">GotaVerde</a>
            <div class="gv-top-actions">
                <details class="gv-more">
                    <summary aria-label="Más opciones">@include('partials.icon', ['icon' => 'more'])</summary>
                    <nav aria-label="Más opciones">
                        @include('partials.nav-links')
                    </nav>
                </details>

                <a class="gv-profile-link" href="{{ route('perfil.edit') }}" @if(request()->routeIs('perfil.*')) aria-current="page" @endif>
                    @include('partials.avatar', ['usuario' => auth()->user()])
                    <span><strong>{{ auth()->user()->name }}</strong><small>Mi perfil · Nivel {{ auth()->user()->nivel() }}</small></span>
                </a>
                <div class="gv-mobile-logout">
                    @include('partials.logout-button')
                </div>
            </div>
        </header>
        <main class="gv-main">
            @include('partials.alerts')
            @yield('contenido')
        </main>
    </div>

    <nav class="gv-mobile-nav" aria-label="Navegación principal móvil">
        <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" aria-label="Inicio">@include('partials.icon', ['icon' => 'home'])</a>
        <a class="{{ request()->routeIs('lecturas.*') ? 'active' : '' }}" href="{{ route('lecturas.index') }}" aria-label="Lecturas">@include('partials.icon', ['icon' => 'chart'])</a>
        <a class="gv-nav-add {{ request()->routeIs('habitos.*') ? 'active' : '' }}" href="{{ route('habitos.create') }}" aria-label="Registrar hábito">@include('partials.icon', ['icon' => 'plus'])</a>
        <a class="{{ request()->routeIs('metas.*') ? 'active' : '' }}" href="{{ route('metas.index') }}" aria-label="Metas">@include('partials.icon', ['icon' => 'target'])</a>
        <a class="{{ request()->routeIs('viviendas.*') ? 'active' : '' }}" href="{{ route('viviendas.index') }}" aria-label="Mis viviendas">@include('partials.icon', ['icon' => 'households'])</a>
    </nav>
</div>
</body>
</html>
