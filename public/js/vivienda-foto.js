document.querySelectorAll('[data-foto-vivienda]').forEach((imagen) => {
    const mostrarIlustracion = () => { imagen.hidden = true; };
    imagen.addEventListener('error', mostrarIlustracion);
    if (imagen.complete && imagen.naturalWidth === 0) mostrarIlustracion();
});

document.querySelectorAll('[data-foto-carga]').forEach((panel) => {
    const campo = panel.querySelector('input[type="file"]');
    const quitar = panel.querySelector('input[name="quitar_foto"]');
    const fotoActual = panel.querySelector('[data-foto-vivienda]');
    const vistaPrevia = panel.querySelector('[data-foto-previa]');
    const estado = panel.querySelector('[data-foto-estado]');
    const nombre = panel.querySelector('[data-foto-nombre]');
    const sinArchivo = nombre.textContent;
    let url;

    const actualizar = () => {
        if (url) URL.revokeObjectURL(url);
        url = undefined;
        vistaPrevia.hidden = true;
        vistaPrevia.removeAttribute('src');
        estado.textContent = '';
        const archivo = campo.files[0];
        nombre.textContent = archivo?.name ?? sinArchivo;
        nombre.title = archivo?.name ?? '';

        if (archivo) {
            url = URL.createObjectURL(archivo);
            vistaPrevia.src = url;
            vistaPrevia.hidden = false;
        }

        if (fotoActual) fotoActual.classList.toggle('gv-photo-pending-removal', !!quitar?.checked);
    };

    vistaPrevia.addEventListener('error', () => {
        vistaPrevia.hidden = true;
        estado.textContent = 'No se pudo mostrar la vista previa de esta imagen.';
    });
    campo.addEventListener('change', () => {
        if (campo.files.length && quitar) quitar.checked = false;
        actualizar();
    });
    quitar?.addEventListener('change', () => {
        if (quitar.checked) campo.value = '';
        actualizar();
    });
    campo.form.addEventListener('reset', () => requestAnimationFrame(actualizar));
});
