const avisos = document.querySelector('.gv-notifications');
const plantillaAvisoLocal = avisos.querySelector('[data-validacion-local]');
plantillaAvisoLocal.remove();
const avisosActivos = new Map();
const movimientoReducido = window.matchMedia('(prefers-reduced-motion: reduce)');
const admiteCursor = window.matchMedia('(any-hover: hover)');
const navegacionMovil = document.querySelector('.gv-mobile-nav');
const dialogoMetas = document.querySelector('.gv-meta-dialog');
let avisoLocal;
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

function prepararAviso(aviso) {
    const boton = aviso.querySelector('[data-cerrar-aviso]');
    const contenido = aviso.querySelector('.gv-toast-copy');
    const eventos = new AbortController();
    const opciones = { signal: eventos.signal };
    let restante = 5000;
    let temporizador;
    let vence;
    let fase = 'espera';
    let cursorDentro = false;
    let focoDentro = false;

    const alTerminar = (clase, callback) => {
        aviso.classList.add(clase);
        const animacion = aviso.getAnimations?.().find((efecto) => efecto.animationName);
        if (animacion) animacion.finished.then(callback, () => {});
        else callback();
    };

    const pausar = () => {
        if (temporizador === undefined) return;

        clearTimeout(temporizador);
        temporizador = undefined;
        restante = Math.max(0, vence - performance.now());
    };

    const ocultar = () => {
        if (fase === 'eliminado') return;

        pausar();
        fase = 'eliminado';
        const posiciones = new Map([...avisosActivos.keys()]
            .filter((otro) => otro !== aviso && !otro.hidden && !otro.classList.contains('is-entering'))
            .map((otro) => [otro, otro.getBoundingClientRect().top]));
        eventos.abort();
        avisosActivos.delete(aviso);
        aviso.remove();
        distribuirAvisos();

        if (movimientoReducido.matches) return;
        posiciones.forEach((anterior, otro) => {
            if (otro.hidden) return;

            const distancia = anterior - otro.getBoundingClientRect().top;
            if (distancia) otro.animate?.([{ translate: `0 ${distancia}px` }, { translate: '0 0' }], {
                duration: 200,
                easing: 'ease-out',
            });
        });
    };

    const cerrar = () => {
        if (fase === 'salida' || fase === 'eliminado' || aviso.hidden) return;

        pausar();
        fase = 'salida';
        aviso.classList.remove('is-entering');
        boton.disabled = true;
        alTerminar('is-leaving', ocultar);
    };

    const reanudar = () => {
        if (aviso.hidden || fase !== 'visible' || cursorDentro || focoDentro || temporizador !== undefined) return;

        vence = performance.now() + restante;
        temporizador = setTimeout(cerrar, restante);
    };

    aviso.addEventListener('pointerenter', (evento) => {
        if (evento.pointerType === 'touch') return;

        cursorDentro = true;
        pausar();
    }, opciones);
    aviso.addEventListener('pointerleave', (evento) => {
        if (evento.pointerType === 'touch') return;

        cursorDentro = false;
        reanudar();
    }, opciones);
    aviso.addEventListener('focusin', () => {
        focoDentro = true;
        pausar();
    }, opciones);
    aviso.addEventListener('focusout', (evento) => {
        focoDentro = aviso.contains(evento.relatedTarget);
        reanudar();
    }, opciones);
    boton.hidden = false;
    boton.addEventListener('click', cerrar, opciones);

    const control = {
        get enSalida() { return fase === 'salida'; },
        mostrar() {
            contenido.tabIndex = contenido.scrollHeight > contenido.clientHeight ? 0 : -1;
            cursorDentro = admiteCursor.matches && aviso.matches(':hover');
            focoDentro = aviso.contains(document.activeElement);

            if (fase !== 'espera') {
                reanudar();
                return;
            }

            fase = 'entrada';
            alTerminar('is-entering', () => {
                if (fase !== 'entrada') return;

                aviso.classList.remove('is-entering');
                fase = 'visible';
                reanudar();
            });
        },
        esperar() {
            pausar();
            aviso.hidden = true;
            if (fase !== 'entrada') return;

            aviso.classList.remove('is-entering');
            fase = 'espera';
        },
        ocultar,
    };
    avisosActivos.set(aviso, control);
    return control;
}

function distribuirAvisos() {
    const alturaVentana = document.documentElement.clientHeight;
    const barra = navegacionMovil?.getBoundingClientRect();
    if (barra?.height) {
        avisos.style.setProperty('--toast-bottom', `${Math.ceil(alturaVentana - barra.top + 10)}px`);
    } else {
        avisos.style.removeProperty('--toast-bottom');
    }

    const estilos = getComputedStyle(avisos);
    const limite = Math.min(alturaVentana * .42, alturaVentana - parseFloat(estilos.bottom) - 12);
    const separacion = parseFloat(estilos.rowGap);
    let altura = 0;
    let cola = Boolean(dialogoMetas?.open);

    avisosActivos.forEach((control, aviso) => {
        if (control.enSalida) {
            altura += aviso.offsetHeight + separacion;
            return;
        }
        if (cola) {
            control.esperar();
            return;
        }

        aviso.hidden = false;
        const espacio = aviso.offsetHeight + separacion;
        if (altura && altura + espacio - separacion > limite && !aviso.contains(document.activeElement)) {
            cola = true;
            control.esperar();
            return;
        }

        control.mostrar();
        altura += espacio;
    });
}

avisos.querySelectorAll('[data-aviso-temporal]').forEach(prepararAviso);
window.addEventListener('resize', distribuirAvisos);
document.fonts?.ready.then(distribuirAvisos);
if (navegacionMovil && typeof ResizeObserver === 'function') {
    new ResizeObserver(distribuirAvisos).observe(navegacionMovil);
}
if (dialogoMetas) {
    new MutationObserver(distribuirAvisos).observe(dialogoMetas, { attributeFilter: ['open'] });
}
distribuirAvisos();

const ocultarAvisoLocal = () => {
    avisoLocal?.ocultar();
    avisoLocal = undefined;
};

document.addEventListener('invalid', (evento) => {
    if (mostrandoError) return;

    mostrandoError = true;
    const etiqueta = evento.target.labels?.[0]?.textContent.trim();
    ocultarAvisoLocal();
    const aviso = plantillaAvisoLocal.cloneNode(true);
    aviso.querySelector('[data-aviso-mensaje]').textContent = [etiqueta, evento.target.validationMessage].filter(Boolean).join(' · ');
    avisos.append(aviso);
    avisoLocal = prepararAviso(aviso);
    distribuirAvisos();
    setTimeout(() => { mostrandoError = false; }, 0);
}, true);

document.addEventListener('input', () => {
    ocultarAvisoLocal();
});

window.addEventListener('pageshow', (evento) => {
    const navegacion = performance.getEntriesByType('navigation')[0];
    if (!evento.persisted && navegacion?.type !== 'back_forward') return;

    ocultarAvisoLocal();
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

const modalEliminarVivienda = document.getElementById('eliminar-vivienda');

if (modalEliminarVivienda) {
    const abrir = document.querySelector('[data-abrir-eliminar-vivienda]');
    const cancelar = modalEliminarVivienda.querySelector('[data-cancelar-eliminar-vivienda]');
    const formulario = modalEliminarVivienda.querySelector('form');
    const confirmar = formulario.querySelector('button[type="submit"]');

    abrir.addEventListener('click', () => modalEliminarVivienda.showModal());
    cancelar.addEventListener('click', () => modalEliminarVivienda.close());
    modalEliminarVivienda.addEventListener('close', () => {
        if (!confirmar.disabled) formulario.reset();
        abrir.focus();
    });

    formulario.addEventListener('submit', (evento) => {
        if (!modalEliminarVivienda.open || confirmar.disabled || evento.submitter !== confirmar) {
            evento.preventDefault();
            return;
        }

        confirmar.disabled = true;
    });

    window.addEventListener('pageshow', (evento) => {
        if (evento.persisted) confirmar.disabled = false;
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
