@extends('layouts.app')

@section('titulo', 'Invitaciones')

@section('contenido')
<div class="mx-auto max-w-3xl">
    <header class="gv-header">
        <div>
            <h1>Invitaciones recibidas</h1>
            <p class="gv-sub">Mostramos las invitaciones enviadas a {{ auth()->user()->email }}.</p>
        </div>
    </header>

    <div class="grid gap-4">
        @forelse($pendientes as $pendiente)
            <section class="gv-card gv-row" style="justify-content:space-between;gap:16px">
                <div>
                    <h2>{{ $pendiente->vivienda->alias }}</h2>
                    <p>{{ $pendiente->anfitrion->name }} te invitó a unirte a esta vivienda.</p>
                    <p class="gv-note">Vence el {{ $pendiente->expira_en->format('d/m/Y') }} a las {{ $pendiente->expira_en->format('H:i') }}.</p>
                </div>
                <form class="gv-right" method="POST" action="{{ route('invitaciones.recibidas.aceptar', $pendiente->id) }}">
                    @csrf
                    <button class="gv-btn" type="submit">Aceptar invitación</button>
                </form>
            </section>
        @empty
            <p class="gv-card">Todavía no tienes invitaciones pendientes para este correo.</p>
        @endforelse

        <section class="gv-card">
            <h2>¿Te compartieron un enlace?</h2>
            <form method="POST" action="{{ route('invitaciones.enlace') }}">
                @csrf
                <div class="gv-field">
                    <label for="enlace">Pega el enlace de invitación</label>
                    <input id="enlace" name="enlace" type="text" inputmode="url" maxlength="2048" required autocomplete="off" value="{{ old('enlace') }}">
                </div>
                <div class="gv-actions"><button class="gv-btn alt" type="submit">Abrir invitación</button></div>
            </form>
        </section>
    </div>
</div>
@endsection
