<div class="gv-notifications" @if($errors->any()) data-errores-del-servidor @endif>
<div class="gv-alert error" data-aviso-temporal data-validacion-local hidden>
    <p role="alert"></p>
    <button type="button" data-cerrar-aviso aria-label="Cerrar notificación" hidden>×</button>
</div>
@foreach(['exito', 'advertencia', 'informacion'] as $tipoAviso)
@if(session($tipoAviso))
    <div class="gv-alert" data-aviso-temporal>
        <p role="status">{{ session($tipoAviso) }}</p>
        <button type="button" data-cerrar-aviso aria-label="Cerrar notificación" hidden>×</button>
    </div>
@endif
@endforeach
@if(session('gamificacion'))@php($g = session('gamificacion'))
<div class="gv-alert gv-award" data-aviso-temporal>
<div role="status">
<strong>🔥 Racha: {{ $g['racha'] }} {{ $g['racha'] === 1 ? 'día' : 'días' }}</strong>
@foreach($g['metas'] as $meta)<p>🎯 ¡Meta cumplida: {{ $meta }}!</p>@endforeach
@foreach($g['logros'] as $logro)<p>Logro desbloqueado: <strong>{{ $logro }}</strong></p>@endforeach
@foreach($g['recompensas'] as $recompensa)<p>Recompensa obtenida: <strong>{{ $recompensa }}</strong></p>@endforeach
</div>
<button type="button" data-cerrar-aviso aria-label="Cerrar notificación" hidden>×</button>
</div>@endif
@if($errors->any())
    <div class="gv-alert error" data-aviso-temporal>
        <div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        <button type="button" data-cerrar-aviso aria-label="Cerrar notificación" hidden>×</button>
    </div>
@endif
</div>
<script src="{{ asset('js/gotaverde.js') }}?v={{ filemtime(public_path('js/gotaverde.js')) }}" defer></script>
