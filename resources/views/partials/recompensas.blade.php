@if($recompensas->isNotEmpty())
    <aside class="gv-rewards" aria-label="Recompensas de GotaVerde">
        <h2>Recompensas</h2>
        @foreach($recompensas as $recompensa)
            <details class="gv-card gv-promotion">
                <summary>
                    @include('partials.badge-icon', ['badgeKey' => $recompensa->clave])
                    <span>
                        @if(isset($empresas[$recompensa->clave]))
                            <small>{{ $empresas[$recompensa->clave] }}</small>
                        @endif
                        <strong>{{ $recompensa->nombre }}</strong>
                        <small>{{ $recompensa->puntos_requeridos }} puntos</small>
                    </span>
                </summary>
                <p>{{ $recompensa->descripcion }}</p>
                <a class="gv-link" href="{{ route('logros.index') }}">Ver mis recompensas</a>
            </details>
        @endforeach
    </aside>
@endif
