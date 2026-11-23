<dialog id="guia-primer-uso" class="gv-guide" data-guia-primer-uso @if($mostrarAlEntrar) data-guia-automatica @endif aria-labelledby="guia-titulo" aria-describedby="guia-descripcion">
    <p class="gv-label" data-guia-progreso aria-live="polite">Paso 1 de 3</p>
    <h2 id="guia-titulo">Te damos la bienvenida a GotaVerde</h2>
    <p id="guia-descripcion">Crea tu vivienda y registra un hábito para ver cuánta agua usas y cuánto puedes ahorrar.</p>

    <section data-guia-paso aria-labelledby="guia-vivienda">
        <h3 id="guia-vivienda" tabindex="-1">1. Registra tu vivienda</h3>
        <p>Ingresa el código postal para completar la alcaldía. Luego escribe un alias como «Mi casa», elige el tipo de vivienda e indica cuántas personas viven ahí. La tarifa es opcional y puedes añadirla después.</p>
    </section>
    <section data-guia-paso aria-labelledby="guia-habito" hidden>
        <h3 id="guia-habito" tabindex="-1">2. Anota un hábito</h3>
        <p>Después de guardar la vivienda, abre <strong>Registrar hábito</strong>. Elige una actividad como Ducha y escribe cuántos minutos duró. Verás los litros estimados antes de guardar.</p>
    </section>
    <section data-guia-paso aria-labelledby="guia-progreso" hidden>
        <h3 id="guia-progreso" tabindex="-1">3. Consulta tu progreso</h3>
        <p>En el <strong>Panel principal</strong> puedes ver tu consumo y ahorro estimados. En <strong>Lecturas</strong> puedes capturar un recibo para comparar periodos, y en <strong>Metas</strong> puedes fijar un objetivo para tu hogar.</p>
    </section>

    <div class="gv-actions">
        <button class="gv-btn alt" type="button" data-guia-anterior hidden>Atrás</button>
        <button class="gv-btn" type="button" data-guia-siguiente autofocus>Siguiente</button>
        <form method="dialog"><button class="gv-link" type="submit">Omitir guía</button></form>
    </div>
</dialog>
