<a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">@include('partials.icon', ['icon' => 'home'])<span>Panel principal</span></a>
<a class="{{ request()->routeIs('habitos.*') ? 'active' : '' }}" href="{{ route('habitos.create') }}">@include('partials.icon', ['icon' => 'plus'])<span>Registrar hábito</span></a>
<a class="{{ request()->routeIs('lecturas.*') ? 'active' : '' }}" href="{{ route('lecturas.index') }}">@include('partials.icon', ['icon' => 'chart'])<span>Lecturas</span></a>
<a class="{{ request()->routeIs('metas.*') ? 'active' : '' }}" href="{{ route('metas.index') }}">@include('partials.icon', ['icon' => 'target'])<span>Metas</span></a>
<a class="{{ request()->routeIs('logros.*') ? 'active' : '' }}" href="{{ route('logros.index') }}">@include('partials.icon', ['icon' => 'award'])<span>Logros y rachas</span></a>
<a class="{{ request()->routeIs('viviendas.*') ? 'active' : '' }}" href="{{ route('viviendas.index') }}">@include('partials.icon', ['icon' => 'households'])<span>Mis viviendas</span></a>
<a class="{{ request()->routeIs('invitaciones.recibidas') ? 'active' : '' }}" href="{{ route('invitaciones.recibidas') }}">@include('partials.icon', ['icon' => 'mail'])<span>Invitaciones</span></a>
@if(auth()->user()->esAdmin())
<span class="gv-nav-sep">Administración</span>
<a class="{{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}" href="{{ route('admin.usuarios.index') }}">@include('partials.icon', ['icon' => 'people'])<span>Usuarios</span></a>
<a class="{{ request()->routeIs('admin.actividades.*') ? 'active' : '' }}" href="{{ route('admin.actividades.index') }}">@include('partials.icon', ['icon' => 'list'])<span>Actividades</span></a>
<a class="{{ request()->routeIs('admin.logros.*') ? 'active' : '' }}" href="{{ route('admin.logros.index') }}">@include('partials.icon', ['icon' => 'award'])<span>Catálogo de logros</span></a>
@endif
