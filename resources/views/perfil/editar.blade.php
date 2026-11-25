@extends('layouts.app')

@section('titulo', 'Mi perfil')

@section('contenido')
@php($usuario = auth()->user())
<div class="gv-profile">
    @include('partials.screen-heading', ['simbolo' => 'people', 'encabezado' => 'Mi perfil', 'detalle' => 'Gestiona tu nombre visible y tu foto de perfil.'])

    <div class="gv-profile-layout">
    <section class="gv-card gv-form-panel">
        <div class="gv-row gv-profile-heading gv-profile-cover">
            @include('partials.avatar', ['usuario' => $usuario, 'claseAvatar' => 'gv-avatar-lg'])
            <div>
                <h2>{{ $usuario->name }}</h2>
                <p class="gv-label">{{ $usuario->email }}</p>
                <p class="gv-tag">Nivel {{ $usuario->nivel() }} · {{ $usuario->puntos }} puntos</p>
            </div>
        </div>

        <form action="{{ route('perfil.update') }}" method="POST" enctype="multipart/form-data" data-formulario-perfil>
            @csrf
            @method('PATCH')
            <div class="gv-field">
                <label for="name">Nombre visible</label>
                <input id="name" name="name" autocomplete="name" required maxlength="120" value="{{ old('name', $usuario->name) }}">
            </div>
            <div class="gv-field">
                <label for="foto">Foto de perfil</label>
                <input id="foto" class="gv-profile-file" name="foto" type="file" accept="image/jpeg,image/png,image/webp"
                    data-limite-bytes="{{ $limiteFotoBytes }}" data-limite-pixeles="{{ $limiteFotoPixeles }}"
                    aria-describedby="perfil-foto-formatos perfil-foto-estado" @error('foto') aria-invalid="true" @enderror>
                <small id="perfil-foto-formatos" class="gv-label">JPG, PNG o WebP. Si hace falta, ajustaremos el tamaño al guardar.</small>
                <p id="perfil-foto-estado" class="gv-label" role="status">{{ $errors->first('foto') }}</p>
            </div>
            <div class="gv-actions">
                <button class="gv-btn" type="submit">Guardar cambios</button>
                @if($usuario->foto_perfil)
                    <button class="gv-btn alt" type="submit" form="quitar-foto-perfil">Quitar foto</button>
                @endif
            </div>
        </form>

        @if($usuario->foto_perfil)
            <form id="quitar-foto-perfil" method="POST" action="{{ route('perfil.foto.destroy') }}">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </section>

    <section class="gv-card gv-profile-help">
        <h2>@include('partials.icon', ['icon' => 'list']) Ayuda para empezar</h2>
        <p class="gv-note">Consulta la guía o revisa tu nivel, rachas y medallas.</p>
        <p class="gv-note"><a href="{{ route('logros.index') }}">Ver logros y rachas</a></p>
        <div class="gv-actions"><button class="gv-btn alt" type="button" data-abrir-guia aria-controls="guia-primer-uso" aria-haspopup="dialog">Ver guía de inicio</button></div>
    </section>
    </div>
</div>
@include('partials.guia-primer-uso', ['mostrarAlEntrar' => false])
@endsection
