<div class="gv-notifications" @if($errors->any()) data-errores-del-servidor @endif>
    <x-aviso tipo="error" icono="close" :local="true">
        <p class="gv-toast-title" data-aviso-mensaje></p>
    </x-aviso>
    @foreach(['exito' => 'check', 'advertencia' => 'warning', 'informacion' => 'info'] as $tipoAviso => $icono)
        @if(session($tipoAviso))
            <x-aviso :tipo="$tipoAviso" :icono="$tipoAviso === 'exito' && session('enlace_invitacion') ? 'mail' : $icono">
                @if($tipoAviso === 'exito' && session('exito_titulo'))
                    <p class="gv-toast-title">{{ session('exito_titulo') }}</p>
                    <p>{{ session($tipoAviso) }}</p>
                @else
                    <p class="gv-toast-title">{{ session($tipoAviso) }}</p>
                @endif
            </x-aviso>
        @endif
    @endforeach
    @if(session('gamificacion'))
        @php($g = session('gamificacion'))
        <x-aviso tipo="logro" :icono="$g['metas'] ? 'target' : 'trophy'">
            <p class="gv-toast-title">Racha: {{ $g['racha'] }} {{ $g['racha'] === 1 ? 'día' : 'días' }}</p>
            @foreach($g['metas'] as $meta)<p>¡Meta cumplida: {{ $meta }}!</p>@endforeach
            @foreach($g['logros'] as $logro)<p>Logro desbloqueado: <strong>{{ $logro }}</strong></p>@endforeach
            @foreach($g['recompensas'] as $recompensa)<p>Recompensa obtenida: <strong>{{ $recompensa }}</strong></p>@endforeach
        </x-aviso>
    @endif
    @if($errors->any())
        <x-aviso tipo="error" icono="close">
            @foreach($errors->all() as $error)<p @class(['gv-toast-title' => $loop->first])>{{ $error }}</p>@endforeach
        </x-aviso>
    @endif
</div>
<script src="{{ asset('js/gotaverde.js') }}?v={{ filemtime(public_path('js/gotaverde.js')) }}" defer></script>
