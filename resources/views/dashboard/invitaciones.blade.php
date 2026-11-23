@if($hayInvitaciones)
    <aside class="gv-card gv-next-step">
        <div>
            <h2>Tienes una invitación pendiente</h2>
            <p>Revísala para unirte a otra vivienda.</p>
        </div>
        <a class="gv-btn" href="{{ route('invitaciones.recibidas') }}">Ver invitaciones</a>
    </aside>
@endif
