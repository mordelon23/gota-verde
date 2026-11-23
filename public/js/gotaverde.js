const avisos = document.querySelector('.gv-notifications');
const avisoLocal = avisos.querySelector('[data-validacion-local]');
const mensajeLocal = avisoLocal.querySelector('[role="alert"]');
let mostrandoError = false;

const formularioPerfil = document.querySelector('[data-formulario-perfil]');

if (formularioPerfil && typeof createImageBitmap === 'function' && 'FormDataEvent' in window) {
    const foto = formularioPerfil.elements.foto;
    const estado = document.getElementById('perfil-foto-estado');
    const guardar = formularioPerfil.querySelector('button[type="submit"]');
    const maxBytes = Number(foto.dataset.limiteBytes);
    const maxPixeles = Number(foto.dataset.limitePixeles);
    let preparada = false;
    let preparando = false;
    let fotoCodificada = null;

    foto.addEventListener('change', () => {
        preparada = false;
        fotoCodificada = null;
        foto.removeAttribute('aria-invalid');
        estado.textContent = '';
    });

    formularioPerfil.addEventListener('formdata', (evento) => {
        if (fotoCodificada) {
            evento.formData.delete(foto.name);
            evento.formData.set('foto_codificada', fotoCodificada);
        }
    });

    formularioPerfil.addEventListener('submit', async (evento) => {
        const archivo = foto.files[0];
        if (!archivo || preparada) return;

        evento.preventDefault();
        if (preparando) return;

        preparando = true;
        guardar.disabled = true;
        foto.disabled = true;
        estado.textContent = 'Preparando la foto…';
        let imagen;

        try {
            const compatible = archivo.type
                ? /^image\/(jpeg|png|webp)$/.test(archivo.type)
                : /\.(jpe?g|png|webp)$/i.test(archivo.name);

            if (!compatible) {
                throw new Error('formato');
            }

            imagen = await createImageBitmap(archivo);
            let fotoParaEnviar = archivo;

            if (archivo.size > maxBytes || Math.max(imagen.width, imagen.height) > maxPixeles) {
                const escala = Math.min(1, 1200 / Math.max(imagen.width, imagen.height));
                const lienzo = document.createElement('canvas');
                lienzo.width = Math.max(1, Math.round(imagen.width * escala));
                lienzo.height = Math.max(1, Math.round(imagen.height * escala));
                const contexto = lienzo.getContext('2d', { alpha: false });
                contexto.fillStyle = '#fff';
                contexto.fillRect(0, 0, lienzo.width, lienzo.height);
                contexto.drawImage(imagen, 0, 0, lienzo.width, lienzo.height);
                const reducida = await new Promise((resolver) => lienzo.toBlob(resolver, 'image/jpeg', 0.82));

                if (!reducida || reducida.size > maxBytes) {
                    throw new Error('tamaño');
                }

                fotoParaEnviar = reducida;
            }

            fotoCodificada = await new Promise((resolver, rechazar) => {
                const lector = new FileReader();
                lector.onload = () => resolver(lector.result.split(',', 2)[1]);
                lector.onerror = rechazar;
                lector.readAsDataURL(fotoParaEnviar);
            });

            preparada = true;
            estado.textContent = 'Guardando la foto…';
        } catch {
            foto.setAttribute('aria-invalid', 'true');
            estado.textContent = 'No pudimos preparar la foto. Elige otra imagen JPG, PNG o WebP.';
        } finally {
            imagen?.close();
            preparando = false;
            foto.disabled = false;
            guardar.disabled = false;
        }

        if (preparada) formularioPerfil.requestSubmit(guardar);
    });
}

document.querySelector('[data-copiar-invitacion]')?.addEventListener('click', async () => {
    const enlace = document.getElementById('enlace-invitacion');
    const resultado = document.querySelector('[data-copia-resultado]');

    try {
        await navigator.clipboard.writeText(enlace.value);
        resultado.textContent = 'Enlace copiado.';
    } catch {
        enlace.focus();
        enlace.select();
        resultado.textContent = 'Enlace seleccionado. Usa la opción de copiar de tu dispositivo.';
    }
});

avisos.querySelectorAll('[data-cerrar-aviso]').forEach((boton) => {
    boton.hidden = false;
    boton.addEventListener('click', () => {
        boton.closest('.gv-alert').hidden = true;
    });
});

document.addEventListener('invalid', (evento) => {
    if (mostrandoError) return;

    mostrandoError = true;
    avisoLocal.hidden = false;
    const etiqueta = evento.target.labels?.[0]?.textContent.trim();
    mensajeLocal.textContent = [etiqueta, evento.target.validationMessage].filter(Boolean).join(' · ');
    setTimeout(() => { mostrandoError = false; }, 0);
}, true);

document.addEventListener('input', () => {
    avisoLocal.hidden = true;
});

window.addEventListener('pageshow', (evento) => {
    const navegacion = performance.getEntriesByType('navigation')[0];
    if (!evento.persisted && navegacion?.type !== 'back_forward') return;

    avisoLocal.hidden = true;
    if (avisos.hasAttribute('data-errores-del-servidor')) return;

    setTimeout(() => {
        document.querySelectorAll('form[data-limpiar-formulario]').forEach((formulario) => formulario.reset());
    }, 0);
});

const guia = document.querySelector('[data-guia-primer-uso]');

if (guia && typeof guia.showModal === 'function') {
    const abrir = document.querySelector('[data-abrir-guia]');
    const pasos = Array.from(guia.querySelectorAll('[data-guia-paso]'));
    const anterior = guia.querySelector('[data-guia-anterior]');
    const siguiente = guia.querySelector('[data-guia-siguiente]');
    const progreso = guia.querySelector('[data-guia-progreso]');
    let paso = 0;

    const mostrarPaso = () => {
        pasos.forEach((panel, indice) => { panel.hidden = indice !== paso; });
        progreso.textContent = `Paso ${paso + 1} de ${pasos.length}`;
        anterior.hidden = paso === 0;
        siguiente.textContent = paso === pasos.length - 1
            ? (abrir ? 'Entendido' : 'Registrar mi vivienda')
            : 'Siguiente';
        pasos[paso].querySelector('h3').focus();
    };

    anterior.addEventListener('click', () => {
        paso -= 1;
        mostrarPaso();
    });

    siguiente.addEventListener('click', () => {
        if (paso === pasos.length - 1) {
            guia.close();
            return;
        }

        paso += 1;
        mostrarPaso();
    });

    guia.addEventListener('close', () => (abrir ?? document.getElementById('codigo_postal'))?.focus());
    window.addEventListener('pagehide', () => {
        if (guia.open) guia.close();
    });
    if (guia.hasAttribute('data-guia-automatica')) guia.showModal();

    abrir?.addEventListener('click', () => {
        paso = 0;
        guia.showModal();
        mostrarPaso();
    });
}

const catalogoPostal = document.getElementById('catalogo-codigos-postales');

if (catalogoPostal) {
    const catalogo = JSON.parse(catalogoPostal.textContent);
    const codigo = document.getElementById('codigo_postal');
    const alcaldia = document.getElementById('alcaldia');
    const estado = document.getElementById('estado-codigo-postal');

    const actualizarAlcaldia = () => {
        const completo = /^[0-9]{5}$/.test(codigo.value);
        const zona = catalogo[`cp${codigo.value.slice(0, 2)}`];
        const coincide = completo && zona?.sufijos.includes(codigo.value.slice(2));
        const conservado = completo && codigo.value === codigo.dataset.codigoGuardado;
        const invalido = completo && !coincide && !conservado;

        alcaldia.value = coincide ? zona.alcaldia : (conservado ? codigo.dataset.alcaldiaGuardada : '');
        codigo.setAttribute('aria-invalid', String(invalido));

        if (invalido) {
            estado.textContent = 'Este código postal no está en el catálogo de la Ciudad de México.';
        } else if (coincide) {
            estado.textContent = `Código postal válido · ${zona.alcaldia}`;
        } else if (conservado) {
            estado.textContent = 'Código postal guardado anteriormente.';
        } else {
            estado.textContent = 'Ingresa los cinco dígitos de tu código postal.';
        }

        codigo.setCustomValidity(invalido ? estado.textContent : '');
    };

    codigo.addEventListener('input', actualizarAlcaldia);
    codigo.addEventListener('change', actualizarAlcaldia);
    codigo.form.addEventListener('reset', () => requestAnimationFrame(actualizarAlcaldia));
    actualizarAlcaldia();
}
