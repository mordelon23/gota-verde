@extends('layouts.app')
@section('titulo','Usuarios')
@section('contenido')
<header class="gv-header gv-admin-heading"><div>@include('partials.icon', ['icon' => 'people'])<h1>Usuarios</h1><p class="gv-sub">Gestiona el rol global de cada cuenta (RF-04).</p></div><form method="GET" class="gv-row"><input class="gv-select" name="q" value="{{ $busqueda }}" placeholder="Buscar por nombre o correo" style="width:260px"><button class="gv-btn alt">Buscar</button></form></header>
<section class="gv-card gv-data-panel"><div class="gv-scroll"><table class="gv-table"><thead><tr><th>Nombre</th><th>Correo</th><th>Viviendas</th><th>Hábitos</th><th>Puntos</th><th>Rol</th></tr></thead><tbody>
@forelse($usuarios as $usuario)<tr><td>{{ $usuario->name }}</td><td>{{ $usuario->email }}</td><td>{{ $usuario->viviendas_count }}</td><td>{{ $usuario->registros_count }}</td><td>{{ $usuario->puntos }}</td><td><form method="POST" action="{{ route('admin.usuarios.rol',$usuario) }}" class="gv-row">@csrf @method('PATCH')<select name="rol" class="gv-select" style="width:auto;padding:6px 8px" onchange="this.form.submit()" @disabled($usuario->is(auth()->user()))><option value="usuario" @selected($usuario->rol==='usuario')>Usuario estándar</option><option value="admin" @selected($usuario->rol==='admin')>Administrador</option></select></form></td></tr>
@empty<tr><td colspan="6">Sin resultados.</td></tr>@endforelse</tbody></table></div>
<div class="gv-pagination">@if($usuarios->previousPageUrl())<a class="gv-btn sm alt" href="{{ $usuarios->previousPageUrl() }}">← Anterior</a>@endif<span class="gv-label" style="align-self:center">Página {{ $usuarios->currentPage() }} de {{ $usuarios->lastPage() }}</span>@if($usuarios->nextPageUrl())<a class="gv-btn sm alt" href="{{ $usuarios->nextPageUrl() }}">Siguiente →</a>@endif</div>
<p class="gv-note">Por seguridad no puedes cambiar tu propio rol.</p></section>
@endsection
