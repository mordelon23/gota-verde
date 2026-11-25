@extends('layouts.app')

@section('titulo', 'Invitaciones')

@section('contenido')
<div class="gv-invitations-page">
    <header class="gv-header gv-botanical-heading">
        @include('partials.artwork', ['recurso' => 'rama', 'claseIlustracion' => 'gv-botanical-art gv-art-invitations'])
        <div>
            <h1>Invitaciones recibidas</h1>
            <p class="gv-sub">Mostramos las invitaciones enviadas a {{ auth()->user()->email }}.</p>
        </div>
    </header>

    <div class="gv-invitation-layout">
        <section class="gv-invitation-list" aria-labelledby="invitaciones-titulo">
            <div class="gv-invitation-list-heading">
                <h2 id="invitaciones-titulo">Tus invitaciones</h2>
                <span class="gv-tag">{{ $pendientes->count() }} {{ $pendientes->count() === 1 ? 'pendiente' : 'pendientes' }}</span>
            </div>

            @forelse($pendientes as $pendiente)
                <article class="gv-card gv-invitation-item">
                    @include('partials.home-photo', ['hogar' => $pendiente->vivienda])
                    <div class="gv-invitation-detail">
                        <div class="gv-invitation-status">
                            <h3>{{ $pendiente->vivienda->alias }}</h3>
                            <span class="gv-tag">Pendiente</span>
                        </div>
                        <div class="gv-invitation-sender">
                            @include('partials.avatar', ['usuario' => $pendiente->anfitrion, 'mostrarFoto' => false])
                            <p>{{ $pendiente->anfitrion->name }} te invitó a unirte a esta vivienda.</p>
                        </div>
                        <p class="gv-label">Vence el {{ $pendiente->expira_en->format('d/m/Y') }} a las {{ $pendiente->expira_en->format('H:i') }}.</p>
                    </div>
                    <form class="gv-right" method="POST" action="{{ route('invitaciones.recibidas.aceptar', $pendiente->id) }}">
                        @csrf
                        <button class="gv-btn" type="submit">Aceptar invitación</button>
                    </form>
                </article>
            @empty
                <div class="gv-card gv-invitation-empty">
                    <div class="gv-invitation-empty-art">
                        @include('partials.artwork', ['recurso' => 'casa-invitaciones', 'claseIlustracion' => 'gv-invitation-house-art'])
                    </div>
                    <h3>Aún no tienes invitaciones</h3>
                    <p>Todavía no tienes invitaciones pendientes para este correo. Cuando alguien te invite a formar parte de su hogar, la invitación aparecerá aquí.</p>
                </div>
            @endforelse
        </section>

        <aside class="gv-invitation-aside">
            <section class="gv-card gv-invitation-link">
                <span class="gv-feed-symbol">@include('partials.icon', ['icon' => 'link'])</span>
                <h2>¿Te compartieron un enlace?</h2>
                <p class="gv-sub">Pega el enlace que recibiste para abrir la invitación a esa vivienda.</p>
                <form method="POST" action="{{ route('invitaciones.enlace') }}">
                    @csrf
                    <div class="gv-field">
                        <label for="enlace">Pega el enlace de invitación</label>
                        <input id="enlace" name="enlace" type="text" inputmode="url" maxlength="2048" required autocomplete="off" value="{{ old('enlace') }}">
                    </div>
                    <button class="gv-btn" type="submit">Abrir invitación @include('partials.icon', ['icon' => 'arrow'])</button>
                </form>
            </section>

            <div class="gv-invitation-info">
                @include('partials.artwork', ['recurso' => 'gota-meta', 'claseIlustracion' => 'gv-invitation-drop-art'])
                <div>
                    <h2>Cada gota suma más cuando la compartimos.</h2>
                    <p>Registra hábitos y alcanza metas de ahorro junto con los integrantes de tu hogar.</p>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
