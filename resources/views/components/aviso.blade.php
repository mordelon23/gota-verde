@props(['tipo', 'icono', 'local' => false])

<div class="gv-toast" data-aviso-temporal data-tipo="{{ $tipo }}" @if($local) data-validacion-local hidden @endif>
    <span class="gv-toast-icon">@include('partials.icon', ['icon' => $icono])</span>
    <div class="gv-toast-copy" role="{{ $tipo === 'error' ? 'alert' : 'status' }}" aria-atomic="true">
        {{ $slot }}
    </div>
    <button class="gv-toast-close" type="button" data-cerrar-aviso aria-label="Cerrar notificación" hidden>
        @include('partials.icon', ['icon' => 'close'])
    </button>
</div>
