@php
    $archivo = collect(['png', 'svg'])
        ->map(fn ($extension) => 'img/gotaverde/'.$recurso.'.'.$extension)
        ->first(fn ($ruta) => is_file(public_path($ruta)));
@endphp
@if($archivo)
    <img class="{{ $claseIlustracion }}" src="{{ asset($archivo) }}" alt="" aria-hidden="true">
@endif
