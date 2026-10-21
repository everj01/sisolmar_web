const API = `${VITE_URL_APP}/api`;
const cargos = Array.isArray(window.reportesDjCargos) ? window.reportesDjCargos : [];

const reportes = {
    'sin-foto-dni': {
        panel: document.getElementById('configReporteSinFotoDni'),
        sucursal: document.getElementById('reporteSucursal'), tipo: document.getElementById('reporteTipo'),
        cargo: document.getElementById('reporteCargo'), estado: document.getElementById('reporteEstado'),
    },
    'dni-hijos': {
        panel: document.getElementById('configReporteDniHijos'), sucursal: document.getElementById('hijosSucursal'),
        tipo: document.getElementById('hijosTipo'), cargo: document.getElementById('hijosCargo'), estado: document.getElementById('hijosEstado'),
    },
    'asig-hijos-mayores': {
        panel: document.getElementById('configReporteAsigHijosMayores'), sucursal: document.getElementById('mayoresSucursal'),
        tipo: document.getElementById('mayoresTipo'), cargo: document.getElementById('mayoresCargo'), estado: document.getElementById('mayoresEstado'),
    },
    'sin-dj-escaneada': {
        panel: document.getElementById('configReporteSinDjEscaneada'), sucursal: document.getElementById('sinDjSucursal'),
        tipo: document.getElementById('sinDjTipo'), cargo: document.getElementById('sinDjCargo'), estado: document.getElementById('sinDjEstado'),
    },
};

function actualizarCargos(reporte) {
    const tipoCargo = ({ '01': '01', '03': '01', '02': '02', '05': '02' })[reporte.tipo.value];
    reporte.cargo.replaceChildren();
    if (!tipoCargo) {
        reporte.cargo.disabled = true;
        reporte.cargo.add(new Option('Selecciona primero el tipo', ''));
        return;
    }
    reporte.cargo.disabled = false;
    reporte.cargo.add(new Option('Todos los cargos', ''));
    cargos.filter(cargo => String(cargo.tipo ?? '').trim() === tipoCargo)
        .forEach(cargo => reporte.cargo.add(new Option(cargo.nombre, cargo.codigo)));
}

function limpiarFiltros(reporte) {
    reporte.sucursal.value = '';
    reporte.tipo.value = '';
    reporte.estado.value = '';
    actualizarCargos(reporte);
}

function parametrosFiltros(reporte) {
    const params = new URLSearchParams();
    if (reporte.sucursal.value) params.set('codSucursal', reporte.sucursal.value);
    if (reporte.tipo.value) params.set('tipoPer', reporte.tipo.value);
    if (reporte.cargo.value) params.set('cargo', reporte.cargo.value);
    if (reporte.estado.value) params.set('vigente', reporte.estado.value);
    return params;
}

function nuevoIdEscaneo() {
    if (window.crypto?.randomUUID) return window.crypto.randomUUID();
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, caracter => {
        const aleatorio = Math.random() * 16 | 0;
        return (caracter === 'x' ? aleatorio : (aleatorio & 0x3 | 0x8)).toString(16);
    });
}

const esperarSiguienteLote = () => new Promise(resolve => setTimeout(resolve, 120));

const buscarReporte = document.getElementById('buscarReporteDj');
const opcionesReporte = [...document.querySelectorAll('.reporte-dj-opcion')];
const mensajeSinReportes = document.getElementById('sinReportesDj');
const panelReporteGeneral = document.getElementById('configReporteGeneral');
buscarReporte.addEventListener('input', () => {
    const busqueda = buscarReporte.value.trim().toLocaleLowerCase('es');
    let visibles = 0;
    opcionesReporte.forEach(opcion => {
        const coincide = opcion.textContent.toLocaleLowerCase('es').includes(busqueda);
        opcion.classList.toggle('hidden', !coincide);
        if (coincide) visibles++;
    });
    mensajeSinReportes.classList.toggle('hidden', visibles > 0);
});

opcionesReporte.forEach(opcion => opcion.addEventListener('click', () => {
    opcionesReporte.forEach(item => {
        const seleccionado = item === opcion;
        item.setAttribute('aria-pressed', seleccionado ? 'true' : 'false');
        item.classList.toggle('border-primary-200', seleccionado);
        item.classList.toggle('bg-primary-50', seleccionado);
        item.classList.toggle('ring-1', seleccionado);
        item.classList.toggle('ring-primary-100', seleccionado);
        item.classList.toggle('border-default-200', !seleccionado);
        item.classList.toggle('bg-white', !seleccionado);
    });
    Object.values(reportes).forEach(reporte => reporte.panel.classList.add('hidden'));
    panelReporteGeneral.classList.add('hidden');
    if (opcion.dataset.reporte === 'reporte-general') {
        panelReporteGeneral.classList.remove('hidden');
        panelReporteGeneral.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        return;
    }
    const reporte = reportes[opcion.dataset.reporte];
    reporte.panel.classList.remove('hidden');
    reporte.panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}));

Object.entries(reportes).forEach(([id, reporte]) => {
    reporte.tipo.addEventListener('change', () => actualizarCargos(reporte));
    const limpiar = ({ 'dni-hijos': 'btnLimpiarDniHijos', 'asig-hijos-mayores': 'btnLimpiarMayores', 'sin-foto-dni': 'btnLimpiarReportesDj', 'sin-dj-escaneada': 'btnLimpiarSinDj' })[id];
    const estado = ({ 'dni-hijos': 'estadoDniHijos', 'asig-hijos-mayores': 'estadoMayores', 'sin-foto-dni': 'reporteDjEstadoCarga', 'sin-dj-escaneada': 'estadoSinDj' })[id];
    document.getElementById(limpiar).addEventListener('click', () => {
        limpiarFiltros(reporte);
        document.getElementById(estado).textContent = 'Filtros limpiados.';
    });
    actualizarCargos(reporte);
});

async function descargarReporte(boton, estado, url, parametros, nombrePredeterminado, formato = 'xlsx', noHayDatosMensaje = null) {
    const formatoTexto = formato === 'pdf' ? 'PDF' : 'Excel';
    const textoOriginal = boton.innerHTML;
    boton.disabled = true;
    boton.innerHTML = `<i class="i-ph-spinner animate-spin"></i> Generando ${formatoTexto}…`;
    estado.textContent = 'Preparando reporte…';
    try {
        parametros.set('incremental', '1');
        if (formato === 'pdf') parametros.set('formato', 'pdf');
        parametros.set('scan_id', nuevoIdEscaneo());
        let resultado;
        do {
            const response = await fetch(`${API}${url}?${parametros}`, { headers: { Accept: 'application/json, application/pdf' } });
            if (!response.ok) throw new Error(`Error HTTP ${response.status}`);
            if (response.headers.get('content-type')?.includes('application/pdf')) {
                const blob = await response.blob();
                const disposition = response.headers.get('content-disposition') || '';
                const nombre = disposition.match(/filename="?([^";]+)"?/i)?.[1] || nombrePredeterminado.replace(/\.xlsx$/i, '.pdf');
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = nombre;
                document.body.appendChild(link);
                link.click();
                link.remove();
                setTimeout(() => URL.revokeObjectURL(link.href), 1000);
                estado.textContent = 'PDF descargado con los filtros seleccionados.';
                return;
            }
            resultado = await response.json();
            if (resultado.completo === false) {
                estado.textContent = `Revisando documentos: ${Number(resultado.procesados || 0).toLocaleString('es-PE')} de ${Number(resultado.total || 0).toLocaleString('es-PE')} personas…`;
                await esperarSiguienteLote();
            }
        } while (resultado.completo === false);
        if (!resultado?.url) {
            estado.textContent = noHayDatosMensaje || resultado?.message || 'El servidor no devolvió el archivo.';
            return;
        }
        const link = document.createElement('a');
        link.href = resultado.url;
        link.download = resultado.nombre || nombrePredeterminado;
        document.body.appendChild(link);
        link.click();
        link.remove();
        estado.textContent = `${formatoTexto} descargado con los filtros seleccionados.`;
    } catch (error) {
        console.error('No se pudo generar el reporte:', error);
        estado.textContent = `No se pudo generar el ${formatoTexto}. Intenta nuevamente.`;
    } finally {
        boton.disabled = false;
        boton.innerHTML = textoOriginal;
    }
}

document.getElementById('btnExportarSinFotoDni').addEventListener('click', event => {
    const params = parametrosFiltros(reportes['sin-foto-dni']);
    params.set('solo_faltantes', '1');
    descargarReporte(event.currentTarget, document.getElementById('reporteDjEstadoCarga'), '/dj/reporte/personal-documentos', params, 'Reporte_Sin_Foto_DNI.xlsx');
});

document.getElementById('btnExportarDniHijos').addEventListener('click', event => {
    const params = parametrosFiltros(reportes['dni-hijos']);
    descargarReporte(event.currentTarget, document.getElementById('estadoDniHijos'), '/dj/reporte/dni-hijos', params, 'Reporte_DNI_Hijos_Pendientes.xlsx');
});

document.getElementById('btnExportarPdfSinFotoDni').addEventListener('click', event => {
    const params = parametrosFiltros(reportes['sin-foto-dni']);
    params.set('solo_faltantes', '1');
    descargarReporte(event.currentTarget, document.getElementById('reporteDjEstadoCarga'), '/dj/reporte/personal-documentos', params, 'Reporte_Pendientes_Foto_DNI.pdf', 'pdf');
});

document.getElementById('btnExportarPdfDniHijos').addEventListener('click', event => {
    const params = parametrosFiltros(reportes['dni-hijos']);
    descargarReporte(event.currentTarget, document.getElementById('estadoDniHijos'), '/dj/reporte/dni-hijos', params, 'Reporte_Pendientes_DNI_Hijos.pdf', 'pdf');
});

const endpointHijosMayores = '/dj/reporte/asignacion-familiar-hijos-mayores';
document.getElementById('btnExportarMayores').addEventListener('click', event => {
    const params = parametrosFiltros(reportes['asig-hijos-mayores']);
    descargarReporte(event.currentTarget, document.getElementById('estadoMayores'), endpointHijosMayores, params, 'Reporte_Asignacion_Familiar_Hijos_Mayores.xlsx');
});
document.getElementById('btnExportarPdfMayores').addEventListener('click', event => {
    const params = parametrosFiltros(reportes['asig-hijos-mayores']);
    descargarReporte(event.currentTarget, document.getElementById('estadoMayores'), endpointHijosMayores, params, 'Reporte_Asignacion_Familiar_Hijos_Mayores.pdf', 'pdf');
});

const endpointSinDj = '/dj/reporte/personal-sin-dj-escaneada';
document.getElementById('btnExportarSinDj').addEventListener('click', event => {
    const params = parametrosFiltros(reportes['sin-dj-escaneada']);
    descargarReporte(event.currentTarget, document.getElementById('estadoSinDj'), endpointSinDj, params, 'Reporte_Personal_Sin_DJ_Escaneada.xlsx');
});
document.getElementById('btnExportarPdfSinDj').addEventListener('click', event => {
    const params = parametrosFiltros(reportes['sin-dj-escaneada']);
    descargarReporte(event.currentTarget, document.getElementById('estadoSinDj'), endpointSinDj, params, 'Reporte_Personal_Sin_DJ_Escaneada.pdf', 'pdf');
});

const modalPreview = document.getElementById('modalVistaPreviaReporte');
const tituloPreview = document.getElementById('tituloVistaPreviaReporte');
const subtituloPreview = document.getElementById('subtituloVistaPreviaReporte');
const contenidoPreview = document.getElementById('contenidoVistaPreviaReporte');
const definicionColumnasPreview = {
    'sin-foto-dni': [
        ['numero', 'N°'], ['codigo', 'Código'], ['personal', 'Apellidos y Nombres'], ['dni', 'N° Documento'], ['sucursal', 'Sucursal'],
        ['tipo', 'Tipo'], ['cargo', 'Cargo'], ['ingreso_solmar', 'Ingreso a Solmar'],
        ['ingreso_planilla', 'Ingreso a planillas'], ['foto', 'Foto'], ['dni_estado', 'DNI'],
        ['registrado_por', 'Registrado por'], ['fecha_registro', 'F. Registro'],
        ['modificado_por', 'Modificado por'], ['fecha_modificacion', 'F. Modificación'],
    ],
    'dni-hijos': [
        ['numero', 'N°'], ['codigo', 'Código'], ['personal', 'Apellidos y Nombres'], ['dni', 'N° Documento'], ['sucursal', 'Sucursal'],
        ['tipo', 'Tipo'], ['cargo', 'Cargo'], ['ingreso_solmar', 'Fecha de ingreso a Solmar'],
        ['ingreso_planilla', 'Fecha de ingreso a planillas'], ['asignacion_familiar', 'Asignación familiar'],
        ['hijos_menores', 'Hijos <18'], ['anverso', 'Anverso'],
        ['reverso', 'Reverso'], ['sin_imagen', 'Sin imagen'], ['detalle', 'Detalle de faltantes'],
    ],
    'asig-hijos-mayores': [
        ['numero', 'N°'], ['codigo', 'Código'], ['personal', 'Apellidos y nombres'], ['dni', 'N° documento'],
        ['sucursal', 'Sucursal'], ['tipo', 'Tipo'], ['cargo', 'Cargo'], ['estado', 'Estado'],
        ['ingreso_solmar', 'Ingreso a Solmar'], ['ingreso_planilla', 'Ingreso a planillas'],
        ['asignacion_familiar', 'Asignación familiar'], ['numero_hijos', 'N° hijos mayores'], ['detalle_hijos', 'Detalle de hijos mayores'],
    ],
    'sin-dj-escaneada': [
        ['numero', 'N°'], ['codigo', 'Código'], ['personal', 'Apellidos y nombres'], ['dni', 'N° documento'],
        ['sucursal', 'Sucursal'], ['tipo', 'Tipo'], ['cargo', 'Cargo'], ['estado', 'Estado'],
        ['ingreso_solmar', 'Ingreso a Solmar'], ['ingreso_planilla', 'Ingreso a planillas'], ['dj', 'DJ escaneada'],
    ],
};

function escaparHtml(valor) {
    return String(valor ?? '—').replace(/[&<>"']/g, caracter => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    })[caracter]);
}

function cerrarPreview() {
    modalPreview.classList.add('hidden');
    modalPreview.classList.remove('flex');
    modalPreview.style.display = 'none';
    document.body.classList.remove('overflow-hidden');
    window.dispatchEvent(new Event('dj:cerrar-vista-previa'));
}

async function abrirPreview(id, reporte, boton) {
    const titulos = {
        'sin-foto-dni': 'Personal Sin Foto o DNI',
        'dni-hijos': 'DNI de Hijo Menores de Edad',
        'asig-hijos-mayores': 'Asignación Familiar Con Hijos Mayores De Edad',
        'sin-dj-escaneada': 'Personal sin DJ escaneada',
    };
    const titulo = titulos[id];
    const params = parametrosFiltros(reporte);
    params.set('previsualizar', '1');
    params.set('incremental', '1');
    params.set('scan_id', nuevoIdEscaneo());
    tituloPreview.textContent = `Vista previa: ${titulo}`;
    subtituloPreview.textContent = id === 'asig-hijos-mayores'
        ? 'Aplicando filtros y consultando derechohabientes…'
        : 'Aplicando los filtros seleccionados…';
    contenidoPreview.innerHTML = id === 'asig-hijos-mayores'
        ? '<p class="text-sm text-slate-500">Consultando datos de personal e hijos…</p>'
        : '<p class="text-sm text-slate-500">Consultando datos y verificando documentos…</p>';
    modalPreview.classList.remove('hidden');
    modalPreview.classList.remove('flex');
    modalPreview.style.display = 'flex';
    document.body.classList.add('overflow-hidden');
    boton.disabled = true;

    try {
        const rutas = {
            'sin-foto-dni': '/dj/reporte/personal-documentos',
            'dni-hijos': '/dj/reporte/dni-hijos',
            'asig-hijos-mayores': endpointHijosMayores,
            'sin-dj-escaneada': endpointSinDj,
        };
        const ruta = rutas[id];
        if (id === 'sin-foto-dni') params.set('solo_faltantes', '1');
        let resultado;
        do {
            const response = await fetch(`${API}${ruta}?${params}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error(`Error HTTP ${response.status}`);
            resultado = await response.json();
            if (resultado.completo === false) {
                subtituloPreview.textContent = `Revisando documentos: ${Number(resultado.procesados || 0).toLocaleString('es-PE')} de ${Number(resultado.total || 0).toLocaleString('es-PE')} personas…`;
                await esperarSiguienteLote();
            }
        } while (resultado.completo === false);
        if (!Array.isArray(resultado.preview)) throw new Error(resultado.message || 'El servidor no devolvió la vista previa.');

        const columnas = definicionColumnasPreview[id];
        const filas = resultado.preview;
        subtituloPreview.textContent = `${Number(resultado.total || 0).toLocaleString('es-PE')} registros encontrados con estos filtros.`;
        if (filas.length === 0) {
            contenidoPreview.innerHTML = '<div class="py-10 text-center text-sm text-slate-500">No se encontraron registros para mostrar con los filtros seleccionados.</div>';
            return;
        }

        const encabezados = columnas.map(([, etiqueta]) => `<th class="whitespace-nowrap px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">${escaparHtml(etiqueta)}</th>`).join('');
        const cuerpo = filas.map(fila => `<tr class="border-t border-slate-100">${columnas.map(([clave]) => `<td class="whitespace-nowrap px-3 py-2 text-sm text-slate-700">${escaparHtml(fila[clave])}</td>`).join('')}</tr>`).join('');
        contenidoPreview.innerHTML = `<div class="h-full w-full overflow-x-scroll overflow-y-auto rounded-lg border border-slate-200"><table class="w-max min-w-full divide-y divide-slate-200"><thead class="sticky top-0 z-10 bg-slate-50"><tr>${encabezados}</tr></thead><tbody>${cuerpo}</tbody></table></div>`;
    } catch (error) {
        console.error('No se pudo cargar la vista previa:', error);
        subtituloPreview.textContent = 'No se pudo consultar el reporte.';
        contenidoPreview.innerHTML = `<p class="py-8 text-center text-sm text-red-600">${escaparHtml(error.message || 'Intenta nuevamente.')}</p>`;
    } finally {
        boton.disabled = false;
    }
}

document.getElementById('btnPrevisualizarSinFotoDni').addEventListener('click', event => abrirPreview('sin-foto-dni', reportes['sin-foto-dni'], event.currentTarget));
document.getElementById('btnPrevisualizarDniHijos').addEventListener('click', event => abrirPreview('dni-hijos', reportes['dni-hijos'], event.currentTarget));
document.getElementById('btnPrevisualizarMayores').addEventListener('click', event => abrirPreview('asig-hijos-mayores', reportes['asig-hijos-mayores'], event.currentTarget));
document.getElementById('btnPrevisualizarSinDj').addEventListener('click', event => abrirPreview('sin-dj-escaneada', reportes['sin-dj-escaneada'], event.currentTarget));
document.getElementById('cerrarVistaPreviaReporte').addEventListener('click', cerrarPreview);
document.getElementById('cerrarVistaPreviaReportePie').addEventListener('click', cerrarPreview);
modalPreview.addEventListener('click', event => { if (event.target === modalPreview) cerrarPreview(); });
document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modalPreview.classList.contains('hidden')) cerrarPreview(); });
