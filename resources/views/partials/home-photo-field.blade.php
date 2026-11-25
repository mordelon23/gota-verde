<div class="gv-photo-upload" data-foto-carga>
    <div class="gv-photo-preview">
        @include('partials.home-photo', ['hogar' => $hogar, 'claseFoto' => 'gv-photo-large'])
        <img class="gv-photo-draft" data-foto-previa hidden alt="Vista previa de la fotografía seleccionada">
    </div>
    <label class="gv-photo-input" for="foto-vivienda">
        @include('partials.icon', ['icon' => 'camera'])
        <span><strong>Fotografía de la vivienda</strong><small>Opcional · JPG, PNG o WebP · Hasta 5 MB</small></span>
    </label>
    <div class="gv-photo-picker">
        <input id="foto-vivienda" type="file" name="foto" accept="image/jpeg,image/png,image/webp" aria-describedby="foto-vivienda-nombre foto-vivienda-estado" @if($errors->has('foto')) aria-invalid="true" @endif>
        <label class="gv-photo-select" for="foto-vivienda">Seleccionar foto</label>
        <span class="gv-label gv-photo-filename" id="foto-vivienda-nombre" data-foto-nombre aria-live="polite">Ningún archivo seleccionado</span>
    </div>
    <p id="foto-vivienda-estado" class="gv-label" aria-live="polite" data-foto-estado>@error('foto'){{ $message }}@enderror</p>
    @if($hogar?->foto)
        <label class="gv-choice"><input type="checkbox" name="quitar_foto" value="1" @checked(old('quitar_foto'))> Quitar la foto al guardar</label>
    @endif
</div>
