const modalMeta = document.getElementById('nueva-meta');
const accesoMeta = document.querySelector('[data-abrir-meta]');
let devolverFoco = accesoMeta;

function abrirMeta(opener = accesoMeta) {
    if (modalMeta.open) return;
    devolverFoco = opener;
    modalMeta.showModal();
    (modalMeta.querySelector('[data-meta-error]') ?? modalMeta.querySelector('#titulo')).focus();
}

accesoMeta.addEventListener('click', (evento) => {
    evento.preventDefault();
    abrirMeta(evento.currentTarget);
});

modalMeta.querySelectorAll('[data-cerrar-meta]').forEach((boton) => {
    boton.addEventListener('click', () => modalMeta.close());
});

modalMeta.addEventListener('close', () => {
    if (location.hash === '#nueva-meta') {
        history.replaceState(history.state, '', location.pathname + location.search);
    }
    devolverFoco?.focus({ preventScroll: true });
});

window.addEventListener('hashchange', () => {
    if (location.hash === '#nueva-meta') abrirMeta();
});

if (modalMeta.hasAttribute('data-reabrir-meta') || location.hash === '#nueva-meta') abrirMeta();

const filtros = document.querySelectorAll('[data-filtro-meta]');
const tarjetas = document.querySelectorAll('[data-estado-meta]');
const sinResultados = document.querySelector('[data-metas-sin-resultados]');

filtros.forEach((filtro) => {
    filtro.addEventListener('click', () => {
        const estado = filtro.dataset.filtroMeta;
        filtros.forEach((boton) => boton.setAttribute('aria-pressed', String(boton === filtro)));
        let hayCoincidencias = false;

        tarjetas.forEach((tarjeta) => {
            const coincide = estado === 'todas' || tarjeta.dataset.estadoMeta === estado;
            tarjeta.hidden = ! coincide;
            hayCoincidencias ||= coincide;
        });
        sinResultados.hidden = hayCoincidencias;
    });
});
