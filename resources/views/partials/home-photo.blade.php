<span class="gv-home-photo {{ $claseFoto ?? '' }}" aria-hidden="true">
    @include('partials.icon', ['icon' => 'households'])
    @if($hogar?->foto)
        <img src="{{ route('viviendas.foto', $hogar) }}" alt="" data-foto-vivienda>
    @endif
</span>
