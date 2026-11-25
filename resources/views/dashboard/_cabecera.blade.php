<header class="gv-header gv-home-heading"><div><h1>Tu hogar, hoy.</h1><p class="gv-sub">@if($modo==='global') Resumen de todas tus viviendas @elseif(isset($inicio)) {{ $vivienda->alias }} · Semana del {{ $inicio->format('d/m') }} al {{ $fin->format('d/m/Y') }} @endif</p></div><div class="gv-actions"><div class="gv-segments"><a class="{{ $modo==='individual'?'active':'' }}" href="{{ route('dashboard',['modo'=>'individual']) }}">Individual</a><a class="{{ $modo==='global'?'active':'' }}" href="{{ route('dashboard',['modo'=>'global']) }}">Global</a></div>@if($modo==='individual')@include('partials.selector-vivienda')@endif

@include('partials.avatar', ['usuario' => auth()->user()])
</div></header>

@if($modo==='individual')
<a class="gv-card gv-home-house" href="{{ route('viviendas.index') }}">
    @include('partials.home-photo', ['hogar' => $vivienda, 'claseFoto' => 'gv-house-picture'])
    <strong>{{ $vivienda->alias }}</strong>
    @include('partials.icon', ['icon' => 'arrow'])
</a>
@endif
