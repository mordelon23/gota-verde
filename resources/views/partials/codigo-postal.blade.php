@php
    $codigoCapturado = old('codigo_postal', $codigoActual);
    $codigoCapturado = is_string($codigoCapturado) ? $codigoCapturado : '';
    $alcaldiaCatalogo = \App\Models\Vivienda::alcaldiaPorCodigoPostal($codigoCapturado);
    $alcaldiaGuardada = $codigoActual === $codigoCapturado ? $alcaldiaActual : null;
@endphp
<div class="gv-field">
    <label for="codigo_postal">Código postal</label>
    <input id="codigo_postal" name="codigo_postal" type="text" inputmode="numeric" autocomplete="postal-code"
        pattern="[0-9]{5}" minlength="5" maxlength="5" required placeholder="Ejemplo: 04000"
        value="{{ $codigoCapturado }}" aria-describedby="estado-codigo-postal"
        aria-invalid="{{ $errors->has('codigo_postal') ? 'true' : 'false' }}"
        data-codigo-guardado="{{ $codigoActual }}" data-alcaldia-guardada="{{ $alcaldiaActual }}">
    <p id="estado-codigo-postal" class="gv-label" role="status">{{ $errors->first('codigo_postal') ?: 'Ingresa los cinco dígitos de tu código postal.' }}</p>
</div>
<div class="gv-field">
    <label for="alcaldia">Alcaldía de la Ciudad de México</label>
    <input id="alcaldia" type="text" readonly value="{{ $alcaldiaCatalogo ?? $alcaldiaGuardada }}" placeholder="Se completa con el código postal">
</div>
<script type="application/json" id="catalogo-codigos-postales">@json(config('codigos_postales'))</script>
