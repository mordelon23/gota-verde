<header class="gv-header gv-screen-heading {{ $claseEncabezado ?? '' }}">
    <div class="gv-screen-title">
        @include('partials.icon', ['icon' => $simbolo])
        <div><h1>{{ $encabezado }}</h1><p class="gv-sub">{{ $detalle }}</p></div>
    </div>
</header>
