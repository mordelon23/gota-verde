<span class="gv-avatar {{ $claseAvatar ?? '' }}" aria-hidden="true">
    @if($usuario->foto_perfil)
        <img src="{{ route('perfil.foto') }}" alt="">
    @else
        {{ $usuario->iniciales() }}
    @endif
</span>
