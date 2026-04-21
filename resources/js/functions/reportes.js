import axios from 'axios';
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';
import Swal from 'sweetalert2';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';


document.addEventListener('DOMContentLoaded', () => {

    const filtros = {
        foliosVigentes: document.getElementById('filtrosFoliosVigentes'),
        foliosPendientesSucursal: document.getElementById('filtrosFoliosPendientesSucursal'),
        foliosPorVencer: document.getElementById('filtrosFoliosPorVencer'),
    };

    function ocultarTodosLosFiltros() {
        Object.values(filtros).forEach(div => { if (div) div.classList.add('hidden'); });
    }

    new TomSelect('#filtroClienteSelect', { placeholder: '-Seleccionar-', allowEmptyOption: true });
    new TomSelect('#filtroSucursalSelect', { placeholder: '-Seleccionar-', allowEmptyOption: true });

    const btnVigentes   = document.getElementById('btnReporteFoliosVigentes');
    const btnPendientes = document.getElementById('btnReporteFoliosPendientesSucursal');
    const btnPorVencer  = document.getElementById('btnReporteFoliosPorVencer');

    if (btnVigentes)   btnVigentes.addEventListener('click',   () => { ocultarTodosLosFiltros(); filtros.foliosVigentes?.classList.remove('hidden'); });
    if (btnPendientes) btnPendientes.addEventListener('click', () => { ocultarTodosLosFiltros(); filtros.foliosPendientesSucursal?.classList.remove('hidden'); });
    if (btnPorVencer)  btnPorVencer.addEventListener('click',  () => { ocultarTodosLosFiltros(); filtros.foliosPorVencer?.classList.remove('hidden'); });

    const radios            = document.querySelectorAll('input[name="tipoFiltro"]');
    const filtroSucursalDiv = document.getElementById('filtroSucursalDiv');
    const filtroClienteDiv  = document.getElementById('filtroClienteDiv');
    const filtroCodigoDiv   = document.getElementById('filtroCodigoDiv');

    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            filtroSucursalDiv.classList.add('hidden');
            filtroClienteDiv.classList.add('hidden');
            filtroCodigoDiv.classList.add('hidden');
            if (this.value === 'sucursal')       filtroSucursalDiv.classList.remove('hidden');
            else if (this.value === 'cliente')   filtroClienteDiv.classList.remove('hidden');
            else if (this.value === 'servicio')  filtroCodigoDiv.classList.remove('hidden');
        });
    });
});


// ==============================
// REPORTE FOLIOS VIGENTES
// ==============================
document.getElementById('btnGenerarPdfFoliosVigentes').addEventListener('click', () => {
    const tipo      = document.getElementById('filtroTipoFolio').value;
    const prioridad = document.getElementById('filtroPrioridad').value;

    Swal.fire({ title: 'Generando reporte', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    axios.get(`${VITE_URL_APP}/api/get-folios`).then(response => {
        let datos = response.data.filter(f => f.habilitado == "1");
        if (tipo)      datos = datos.filter(f => f.tipoFolio === tipo);
        if (prioridad) datos = datos.filter(f => f.prioridad === prioridad);

        if (datos.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Sin resultados', text: 'No existen folios con los filtros seleccionados' });
            return;
        }

        const doc         = new jsPDF();
        const fechaActual = new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });
        const pageWidth   = doc.internal.pageSize.getWidth();
        const img         = new Image();
        img.src = `${VITE_URL_APP}/images/banners/banner_folios_vigentes.jpeg`;

        img.onload = function () {
            doc.addImage(img, 'JPEG', 0, 0, pageWidth, 50);
            let yPosition = 60;

            doc.setFontSize(13); doc.setFont(undefined, 'bold');
            doc.setFillColor(6, 10, 81); doc.rect(10, yPosition, 190, 7, 'F');
            doc.setTextColor(255, 255, 255);
            doc.text('LISTADO GENERAL DE FOLIOS', 12, yPosition + 5);
            doc.setTextColor(0, 0, 0);
            yPosition += 10;

            autoTable(doc, {
                startY: yPosition,
                head: [['N°', 'Nombre del Folio', 'Tipo', 'Prioridad', 'Vencimiento']],
                body: datos.map((folio, index) => [index + 1, folio.nombre, folio.tipoFolio, folio.prioridad, folio.periodo || 'Sin vencimiento']),
                styles: { fontSize: 8, cellPadding: 2, lineColor: [189, 195, 199], lineWidth: 0.1 },
                headStyles: { fillColor: [6, 10, 81], textColor: [255, 255, 255], fontStyle: 'bold', halign: 'center', fontSize: 9, cellPadding: 3 },
                alternateRowStyles: { fillColor: [250, 250, 250] },
                columnStyles: { 0: { halign: 'center', cellWidth: 12 }, 1: { cellWidth: 70 }, 2: { halign: 'center', cellWidth: 30 }, 3: { halign: 'center', cellWidth: 30 }, 4: { halign: 'center', cellWidth: 38 } },
                margin: { left: 10, right: 10 },
                theme: 'grid'
            });

            const pageCount = doc.internal.getNumberOfPages();
            const hora      = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setDrawColor(189, 195, 199); doc.setLineWidth(0.5); doc.line(10, 282, 200, 282);
                doc.setFontSize(7); doc.setTextColor(127, 140, 141); doc.setFont(undefined, 'normal');
                doc.text('Sistema de Gestión de Recursos Humanos', 10, 287);
                doc.text(`Generado el ${fechaActual} a las ${hora}`, 105, 287, { align: 'center' });
                doc.setFont(undefined, 'bold');
                doc.text(`Página ${i} de ${pageCount}`, 200, 287, { align: 'right' });
            }

            window.open(URL.createObjectURL(doc.output('blob')), '_blank');
            Swal.close();
        };
    }).catch(() => Swal.fire('Error', 'No se pudo generar el reporte', 'error'));
});


// ==============================
// REPORTE FOLIOS PENDIENTES SUCURSAL
// ==============================
document.getElementById('btnGenerarPdfFoliosPendientesSucursal').addEventListener('click', () => {
    const sucursal = document.getElementById('sucursal').value;
    if (!sucursal) { Swal.fire({ icon: 'warning', title: 'Sucursal requerida', text: 'Seleccione una sucursal' }); return; }

    Swal.fire({ title: 'Generando reporte', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    axios.get(`${VITE_URL_APP}/api/reporte/folios-pendientes-sucursal`, { params: { sucursal } })
        .then(response => {
            const data = response.data;
            if (!data.length) { Swal.fire({ icon: 'warning', title: 'Sin resultados', text: 'No existen folios pendientes' }); return; }
            generarPdfFoliosPendientesSucursal(data);
        })
        .catch(() => Swal.fire('Error', 'No se pudo generar el reporte', 'error'));

    // ====== DATOS DE PRUEBA (descomentar si el backend aún no está listo) ======
    // const data = [...];
    // generarPdfFoliosPendientesSucursal(data);
});

// ==============================
// Diccionario de abreviaciones
// ==============================
const ABREV = {
    'ACTA DE COMPROMISO':                                    'ACTA\nCOMP',
    'ANTECEDENTE JUDICIAL':                                  'ANT\nJUD',
    'ANTECEDENTE PENAL':                                     'ANT\nPENAL',
    'ANTECEDENTE POLICIAL':                                  'ANT\nPOL',
    'BASICO DE MERCANCIAS PELIGROSAS':                       'MERC\nPEL',
    'BASICO DE SEGURIDAD PORTUARIA':                         'SEG\nPORT',
    'BASICO I DEL CODIGO PBIP':                              'PBIP\nI',
    'BASICO II DEL CODIGO PBIP':                             'PBIP\nII',
    'BREVETE':                                               'BREV',
    'CARNET DE MARINERO DE BAHIA':                           'CARN\nMAR',
    'CARNET PATRON DE BAHIA':                                'CARN\nPAT',
    'CARNET SUCAMEC':                                        'SUCA\nMEC',
    'CERTIFICADO DE EXAMEN MEDICO':                          'CERT\nMED',
    'CERTIFICADO DE EXAMEN PSICOLOGICO':                     'CERT\nPSICO',
    'CERTIFICADO DE EXAMEN TOXICOLOGICO EXTERNO':            'TOX\nEXT',
    'CERTIFICADO DE VACUNACION':                             'CERT\nVAC',
    'CERTIFICADO UNICO LABORAL - CUL':                       'CUL',
    'COMPROMISO DE CONFIABILIDAD':                           'CONF',
    'DECLARACION JURADA DE CUMPLIMIENTO DE DISPOSICIONES':   'DJ\nDISP',
    'DECLARACION JURADA DE TRABAJADORES (DJ)':               'DJ\nTRAB',
    'DJ DE BENEFICIARIO DE POLIZA VIDA LEY':                 'DJ\nPOL',
    'ESTUDIO DE SEGURIDAD DE TRABAJADORES':                  'EST\nSEG',
    'EVALUACION DEL POSTULANTE':                             'EVAL\nPOST',
    'FOTOCONTROL':                                           'FOTO\nCONT',
    'GESTION DE SEGURIDAD PORTUARIA':                        'GEST\nSEG',
    'GESTION MERCANCIAS PELIGROSAS':                         'GEST\nMERC',
    'INDUCCION DE SEGURIDAD':                                'IND\nSEG',
    'IPER - RAD N° 0025-2024 - APN-DIR':                     'IPER',
    'LICENCIA DE ARMAS':                                     'LIC\nARM',
    'POLIZA SCTR':                                           'SCTR',
    'POLIZA SEGURO VIDA LEY':                                'SEG\nVIDA',
    'VERIFICACION DOMICILIARIA':                             'VERIF\nDOM',
};

function abreviar(nombre) {
    const upper = nombre?.toUpperCase().trim();
    return ABREV[upper] || upper;
}

function generarPdfFoliosPendientesSucursal(data) {

    const porSucursal = {};
    data.forEach(g => {
        if (!porSucursal[g.sucursal]) porSucursal[g.sucursal] = [];
        g.personal.forEach(p => porSucursal[g.sucursal].push({
            nombre: p.personal,
            docs:   p.documentos
        }));
    });

    const todosDocsSet = new Set();
    data.forEach(g => g.personal.forEach(p => p.documentos.forEach(d => todosDocsSet.add(d.documento?.toUpperCase().trim()))));
    const todosLosDocs = [...todosDocsSet];

    const doc         = new jsPDF({ orientation: 'landscape' });
    const PW          = doc.internal.pageSize.getWidth();
    const PH          = doc.internal.pageSize.getHeight();
    const BANNER_H    = 50;
    const MARGEN      = 8;
    const fechaActual = new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });
    const hora        = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });

    // ── Sin columna IT: solo NOMBRE + docs ──
    const anchoNomb = 44;
    const anchoDoc  = Math.max(9, (PW - MARGEN * 2 - anchoNomb) / todosLosDocs.length);

    const cabecera = ['APELLIDOS Y NOMBRES', ...todosLosDocs.map(d => abreviar(d))];

    const columnStyles = {
        0: { cellWidth: anchoNomb, overflow: 'linebreak' },
    };
    todosLosDocs.forEach((_, i) => {
        columnStyles[i + 1] = { cellWidth: anchoDoc, halign: 'center' };
    });

    function construirFilas(personas) {
        return personas.map((persona) => {
            const setDocs  = new Set(persona.docs.map(d => d.documento?.toUpperCase().trim()));
            const mapaTipo = {};
            persona.docs.forEach(d => { mapaTipo[d.documento?.toUpperCase().trim()] = d.tipo_folio?.toUpperCase().trim(); });

            const celdas = todosLosDocs.map(docNombre => {
                const tiene = setDocs.has(docNombre);
                const tipo  = mapaTipo[docNombre];
                return {
                    content: tiene ? (tipo === 'PRINCIPAL' ? 'P' : 'A') : 'X',
                    styles: {
                        fillColor:   tiene ? (tipo === 'PRINCIPAL' ? [34, 139, 34] : [41, 128, 185]) : [192, 57, 43],
                        textColor:   [255, 255, 255],
                        fontStyle:   'bold',
                        halign:      'center',
                        fontSize:    7,
                        cellPadding: { top: 2, bottom: 2, left: 1, right: 1 },
                    }
                };
            });

            return [
                { content: persona.nombre, styles: { fontSize: 6.5, cellPadding: { top: 2, bottom: 2, left: 2, right: 2 } } },
                ...celdas
            ];
        });
    }

    function dibujarLeyenda(y) {
        const items = [
            { color: [34, 139, 34],  letra: 'P', label: '= Tiene (PRINCIPAL)' },
            { color: [41, 128, 185], letra: 'A', label: '= Tiene (ADICIONAL)'  },
            { color: [192, 57,  43], letra: 'X', label: '= PENDIENTE'          },
        ];
        let x = MARGEN;
        doc.setFontSize(6.5); doc.setFont(undefined, 'normal');
        items.forEach(item => {
            doc.setFillColor(...item.color);
            doc.roundedRect(x, y, 5, 4, 0.8, 0.8, 'F');
            doc.setTextColor(255, 255, 255); doc.setFont(undefined, 'bold');
            doc.text(item.letra, x + 2.5, y + 3, { align: 'center' });
            doc.setTextColor(60, 60, 60); doc.setFont(undefined, 'normal');
            doc.text(item.label, x + 7, y + 3);
            x += 50;
        });
    }

    function dibujarPie() {
        const pageCount = doc.internal.getNumberOfPages();
        for (let i = 1; i <= pageCount; i++) {
            doc.setPage(i);
            doc.setDrawColor(210, 215, 230); doc.setLineWidth(0.4);
            doc.line(MARGEN, PH - 12, PW - MARGEN, PH - 12);
            doc.setFontSize(6.5); doc.setTextColor(150, 150, 160); doc.setFont(undefined, 'normal');
            doc.text('Sistema de Gestión de Recursos Humanos', MARGEN + 2, PH - 7);
            doc.text(`Generado el ${fechaActual} a las ${hora}`, PW / 2, PH - 7, { align: 'center' });
            doc.setFont(undefined, 'bold'); doc.setTextColor(80, 80, 100);
            doc.text(`Página ${i} de ${pageCount}`, PW - MARGEN - 2, PH - 7, { align: 'right' });
        }
    }

    function renderPdf(imgElement) {
        let primeraHoja = true;

        Object.entries(porSucursal).forEach(([suc, personas]) => {
            if (!primeraHoja) doc.addPage();

            const yTop = primeraHoja && imgElement ? BANNER_H + 3 : 12;
            if (primeraHoja && imgElement) doc.addImage(imgElement, 'JPEG', 0, 0, PW, BANNER_H);

            let y = yTop;
            doc.setFontSize(8); doc.setFont(undefined, 'bold');
            doc.setFillColor(6, 10, 81);
            doc.rect(MARGEN, y, PW - MARGEN * 2, 6.5, 'F');
            doc.setTextColor(255, 255, 255);
            doc.text(`Sucursal: ${suc}`, MARGEN + 2, y + 4.5);
            doc.setTextColor(0, 0, 0);
            y += 9;

            dibujarLeyenda(y);
            y += 8;

            autoTable(doc, {
                startY: y,
                head: [cabecera],
                body: construirFilas(personas),
                styles: {
                    fontSize:      6,
                    cellPadding:   { top: 2, bottom: 2, left: 1, right: 1 },
                    lineColor:     [200, 200, 210],
                    lineWidth:     0.15,
                    overflow:      'linebreak',
                    minCellHeight: 8,
                },
                headStyles: {
                    fillColor:     [15, 23, 80],
                    textColor:     [255, 255, 255],
                    fontStyle:     'bold',
                    halign:        'center',
                    fontSize:      5.5,
                    cellPadding:   { top: 2, bottom: 2, left: 1, right: 1 },
                    minCellHeight: 14,
                    valign:        'middle',
                },
                alternateRowStyles: { fillColor: [248, 249, 252] },
                columnStyles,
                margin: { left: MARGEN, right: MARGEN },
                theme: 'grid',
            });

            primeraHoja = false;
        });

        dibujarPie();

        const link    = document.createElement('a');
        link.href     = URL.createObjectURL(doc.output('blob'));
        link.download = 'folios_pendientes_sucursal.pdf';
        link.click();
        Swal.close();
    }

    const img       = new Image();
    img.crossOrigin = 'anonymous';
    img.onload      = () => renderPdf(img);
    img.onerror     = () => { console.warn('Banner no cargó'); renderPdf(null); };
    img.src = `${VITE_URL_APP}/images/banners/banner_folios_pendientes.jpeg`;
}


// ==============================
// FOLIOS POR VENCER
// ==============================
let tipoFiltro  = 'sucursal';
let filtroValue = '';

document.querySelectorAll('input[name="tipoFiltro"]').forEach(radio => {
    radio.addEventListener('change', function() { tipoFiltro = this.value; filtroValue = ''; });
});
document.getElementById('filtroSucursalSelect').addEventListener('change', function() { filtroValue = this.value; });
document.getElementById('filtroClienteSelect').addEventListener('change',  function() { filtroValue = this.value; });

// ====== DATOS DE PRUEBA (eliminar cuando el backend esté listo) ======
const datosPrueba = [
    { codPersonal: '14991', personal: 'ACUÑA LOPEZ HUGO GERMAN',                sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/10/2022', cargo: 'AGENTE DE SEGURIDAD', documento: 'EVAL POST',  tipo_folio: 'PRINCIPAL',  dias_restantes: '9',  fecha_caducidad: '2026-03-27 00:00:00' },
    { codPersonal: '14991', personal: 'ACUÑA LOPEZ HUGO GERMAN',                sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/10/2022', cargo: 'AGENTE DE SEGURIDAD', documento: 'EST SEG',    tipo_folio: 'PRINCIPAL',  dias_restantes: '15', fecha_caducidad: '2026-04-02 00:00:00' },
    { codPersonal: '14991', personal: 'ACUÑA LOPEZ HUGO GERMAN',                sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/10/2022', cargo: 'AGENTE DE SEGURIDAD', documento: 'CAPA',       tipo_folio: 'SECUNDARIO', dias_restantes: '20', fecha_caducidad: '2026-04-07 00:00:00' },
    { codPersonal: '14991', personal: 'ACUÑA LOPEZ HUGO GERMAN',                sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/10/2022', cargo: 'AGENTE DE SEGURIDAD', documento: 'CV',         tipo_folio: 'PRINCIPAL',  dias_restantes: '5',  fecha_caducidad: '2026-03-23 00:00:00' },
    { codPersonal: '14991', personal: 'ACUÑA LOPEZ HUGO GERMAN',                sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/10/2022', cargo: 'AGENTE DE SEGURIDAD', documento: 'POLI',       tipo_folio: 'PRINCIPAL',  dias_restantes: '12', fecha_caducidad: '2026-03-30 00:00:00' },
    { codPersonal: '14991', personal: 'ACUÑA LOPEZ HUGO GERMAN',                sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/10/2022', cargo: 'AGENTE DE SEGURIDAD', documento: 'PENAL',      tipo_folio: 'PRINCIPAL',  dias_restantes: '7',  fecha_caducidad: '2026-03-25 00:00:00' },
    { codPersonal: '16804', personal: 'AGUILAR CAUSHI LUZ KARINA',              sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '13/01/2025', cargo: 'OPERADOR CCTV',        documento: 'EVAL POST',  tipo_folio: 'PRINCIPAL',  dias_restantes: '3',  fecha_caducidad: '2026-03-21 00:00:00' },
    { codPersonal: '16804', personal: 'AGUILAR CAUSHI LUZ KARINA',              sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '13/01/2025', cargo: 'OPERADOR CCTV',        documento: 'POLI',       tipo_folio: 'SECUNDARIO', dias_restantes: '25', fecha_caducidad: '2026-04-12 00:00:00' },
    { codPersonal: '16804', personal: 'AGUILAR CAUSHI LUZ KARINA',              sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '13/01/2025', cargo: 'OPERADOR CCTV',        documento: 'VERIF DOMI', tipo_folio: 'PRINCIPAL',  dias_restantes: '12', fecha_caducidad: '2026-03-30 00:00:00' },
    { codPersonal: '16804', personal: 'AGUILAR CAUSHI LUZ KARINA',              sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '13/01/2025', cargo: 'OPERADOR CCTV',        documento: 'TOX EXT',    tipo_folio: 'PRINCIPAL',  dias_restantes: '18', fecha_caducidad: '2026-04-05 00:00:00' },
    { codPersonal: '05485', personal: 'ALCANTARA CALDERON MANUEL ANTONIO',      sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/05/2019', cargo: 'AGENTE DE SEGURIDAD', documento: 'EVAL POST',  tipo_folio: 'PRINCIPAL',  dias_restantes: '18', fecha_caducidad: '2026-04-05 00:00:00' },
    { codPersonal: '05485', personal: 'ALCANTARA CALDERON MANUEL ANTONIO',      sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/05/2019', cargo: 'AGENTE DE SEGURIDAD', documento: 'EST SEG',    tipo_folio: 'PRINCIPAL',  dias_restantes: '7',  fecha_caducidad: '2026-03-25 00:00:00' },
    { codPersonal: '05485', personal: 'ALCANTARA CALDERON MANUEL ANTONIO',      sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/05/2019', cargo: 'AGENTE DE SEGURIDAD', documento: 'JUDI',       tipo_folio: 'SECUNDARIO', dias_restantes: '14', fecha_caducidad: '2026-04-01 00:00:00' },
    { codPersonal: '05485', personal: 'ALCANTARA CALDERON MANUEL ANTONIO',      sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/05/2019', cargo: 'AGENTE DE SEGURIDAD', documento: 'EMO',        tipo_folio: 'PRINCIPAL',  dias_restantes: '22', fecha_caducidad: '2026-04-09 00:00:00' },
    { codPersonal: '05485', personal: 'ALCANTARA CALDERON MANUEL ANTONIO',      sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/05/2019', cargo: 'AGENTE DE SEGURIDAD', documento: 'CERT VAC',   tipo_folio: 'SECUNDARIO', dias_restantes: '28', fecha_caducidad: '2026-04-15 00:00:00' },
    { codPersonal: '17024', personal: 'ALVARADO FERRER ANTOFELY',               sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/04/2025', cargo: 'AGENTE DE SEGURIDAD', documento: 'CV',         tipo_folio: 'PRINCIPAL',  dias_restantes: '6',  fecha_caducidad: '2026-03-24 00:00:00' },
    { codPersonal: '17024', personal: 'ALVARADO FERRER ANTOFELY',               sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/04/2025', cargo: 'AGENTE DE SEGURIDAD', documento: 'EXA PSICO',  tipo_folio: 'PRINCIPAL',  dias_restantes: '10', fecha_caducidad: '2026-03-28 00:00:00' },
    { codPersonal: '17024', personal: 'ALVARADO FERRER ANTOFELY',               sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/04/2025', cargo: 'AGENTE DE SEGURIDAD', documento: 'CAPA',       tipo_folio: 'SECUNDARIO', dias_restantes: '28', fecha_caducidad: '2026-04-15 00:00:00' },
    { codPersonal: '17562', personal: 'ALVAREZ VALLADARES JEAN PIERRE BRANDON', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '27/12/2025', cargo: 'AGENTE DE SEGURIDAD', documento: 'EVAL POST',  tipo_folio: 'PRINCIPAL',  dias_restantes: '4',  fecha_caducidad: '2026-03-22 00:00:00' },
    { codPersonal: '17562', personal: 'ALVAREZ VALLADARES JEAN PIERRE BRANDON', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '27/12/2025', cargo: 'AGENTE DE SEGURIDAD', documento: 'EST SEG',    tipo_folio: 'PRINCIPAL',  dias_restantes: '19', fecha_caducidad: '2026-04-06 00:00:00' },
    { codPersonal: '17562', personal: 'ALVAREZ VALLADARES JEAN PIERRE BRANDON', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '27/12/2025', cargo: 'AGENTE DE SEGURIDAD', documento: 'PENAL',      tipo_folio: 'PRINCIPAL',  dias_restantes: '11', fecha_caducidad: '2026-03-29 00:00:00' },
    { codPersonal: '17562', personal: 'ALVAREZ VALLADARES JEAN PIERRE BRANDON', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '27/12/2025', cargo: 'AGENTE DE SEGURIDAD', documento: 'CUL',        tipo_folio: 'SECUNDARIO', dias_restantes: '27', fecha_caducidad: '2026-04-14 00:00:00' },
];

document.getElementById('btnGenerarPdfFoliosPorVencer').addEventListener('click', () => {

    if (!tipoFiltro || !filtroValue) {
        Swal.fire('Atención', 'Selecciona un filtro antes de generar el PDF', 'warning');
        return;
    }

    Swal.fire({ title: 'Generando reporte', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    // ====== USAR DATOS DE PRUEBA (comentar cuando backend esté listo) ======
    generarPdfFoliosPorVencer(datosPrueba);

    // ====== ENDPOINT REAL (descomentar cuando el backend esté listo) ======
    // const endpoint = tipoFiltro === 'cliente'
    //     ? `${VITE_URL_APP}/reporte/folios-por-vencer-cliente`
    //     : `${VITE_URL_APP}/reporte/folios-por-vencer`;
    // const params = tipoFiltro === 'cliente' ? { cliente: filtroValue } : { sucursal: filtroValue };
    // axios.get(endpoint, { params })
    // .then(response => {
    //     if (!response.data.length) {
    //         Swal.fire({ icon: 'warning', title: 'Sin resultados', text: 'No existen folios con los filtros seleccionados' });
    //         return;
    //     }
    //     generarPdfFoliosPorVencer(response.data);
    // })
    // .catch(error => { console.error(error); Swal.fire('Error', 'No se pudo generar el reporte', 'error'); });
});

function generarPdfFoliosPorVencer(datos) {

    const groupKey     = tipoFiltro === 'cliente' ? 'cliente' : 'sucursal';
    const porGrupo     = {};
    const todosLosDocs = new Set();

    datos.forEach(d => {
        todosLosDocs.add(d.documento);
        const grupo = d[groupKey];
        if (!porGrupo[grupo]) porGrupo[grupo] = {};
        if (!porGrupo[grupo][d.codPersonal]) {
            porGrupo[grupo][d.codPersonal] = {
                codigo:        d.codPersonal,
                personal:      d.personal,
                ingresoSolmar: d.ingresoSolmar || '-',
                cargo:         d.cargo || '-',
                documentos:    {}
            };
        }
        porGrupo[grupo][d.codPersonal].documentos[d.documento] = { fecha_caducidad: d.fecha_caducidad };
    });

    const listaDocumentos = [...todosLosDocs];

    const doc          = new jsPDF({ orientation: 'landscape' });
    const pageWidth    = doc.internal.pageSize.getWidth();
    const pageHeight   = doc.internal.pageSize.getHeight();
    const bannerHeight = 40;
    const fechaActual  = new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });

    const img = new Image();
    img.src = `${VITE_URL_APP}/images/banners/BANNER REPORTES DE FOLIOS -02.jpeg`;

    img.onload = function () {
        doc.addImage(img, 'JPEG', 0, 0, pageWidth, bannerHeight);
        let yPosition = bannerHeight + 10;

        Object.entries(porGrupo).forEach(([grupo, personas]) => {
            if (yPosition > pageHeight - 40) { doc.addPage(); yPosition = 20; }

            const labelGrupo = tipoFiltro === 'cliente' ? 'Cliente' : 'Sucursal';
            doc.setFontSize(9); doc.setFont(undefined, 'bold');
            doc.setFillColor(220, 225, 245);
            doc.rect(10, yPosition, pageWidth - 20, 6, 'F');
            doc.setTextColor(6, 10, 81);
            doc.text(`${labelGrupo}: ${grupo}`, 12, yPosition + 4.5);
            doc.setTextColor(0, 0, 0);
            yPosition += 8;

            const cabecera   = ['IT', 'CÓDIGO', 'APELLIDOS Y NOMBRES', 'INGRESO SOLMAR', 'CARGO', ...listaDocumentos];
            const mapaEstado = {};

            const personasArray = Object.values(personas);
            const filas = personasArray.map((persona, filaIdx) => {
                const celdas = listaDocumentos.map((docNombre, docIdx) => {
                    const colIdx = docIdx + 5;
                    const info   = persona.documentos[docNombre];

                    if (!info) {
                        mapaEstado[`${filaIdx}-${colIdx}`] = { esSI: false };
                        return { content: '', styles: { fillColor: [214, 69, 69], cellPadding: { top: 3, bottom: 3, left: 2, right: 2 } } };
                    }

                    const fecha = info.fecha_caducidad ? info.fecha_caducidad.split(' ')[0] : '-';
                    mapaEstado[`${filaIdx}-${colIdx}`] = { esSI: true, fecha };
                    return { content: '', styles: { fillColor: [250, 220, 170], cellPadding: { top: 3, bottom: 3, left: 2, right: 2 } } };
                });

                return [
                    { content: filaIdx + 1,          styles: { halign: 'center', fontSize: 7, textColor: [60, 60, 60] } },
                    { content: persona.codigo,        styles: { halign: 'center', fontSize: 7, textColor: [30, 30, 30] } },
                    { content: persona.personal,      styles: { fontSize: 7, textColor: [30, 30, 30] } },
                    { content: persona.ingresoSolmar, styles: { halign: 'center', fontSize: 7, textColor: [30, 30, 30] } },
                    { content: persona.cargo,         styles: { fontSize: 7, textColor: [30, 30, 30] } },
                    ...celdas
                ];
            });

            const anchoIT      = 8;
            const anchoCodigo  = 18;
            const anchoNombre  = 36;
            const anchoIngreso = 18;
            const anchoCargo   = 26;
            const anchoDoc     = (pageWidth - 20 - anchoIT - anchoCodigo - anchoNombre - anchoIngreso - anchoCargo) / listaDocumentos.length;

            const columnStyles = {
                0: { cellWidth: anchoIT },
                1: { cellWidth: anchoCodigo },
                2: { cellWidth: anchoNombre },
                3: { cellWidth: anchoIngreso, halign: 'center' },
                4: { cellWidth: anchoCargo }
            };
            listaDocumentos.forEach((_, i) => {
                columnStyles[i + 5] = { cellWidth: anchoDoc, halign: 'center' };
            });

            autoTable(doc, {
                startY: yPosition,
                head: [cabecera],
                body: filas,
                styles: {
                    fontSize: 6.5,
                    cellPadding: { top: 3, bottom: 3, left: 2, right: 2 },
                    lineColor: [180, 180, 190],
                    lineWidth: 0.2,
                    overflow: 'linebreak'
                },
                headStyles: {
                    fillColor: [15, 23, 80],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    halign: 'center',
                    fontSize: 6.5,
                    cellPadding: 3
                },
                columnStyles,
                margin: { left: 10, right: 10 },
                theme: 'plain',
                didDrawCell: (data) => {
                    if (data.section !== 'body') return;

                    const key    = `${data.row.index}-${data.column.index}`;
                    const estado = mapaEstado[key];
                    if (!estado) return;

                    const { x, y, width, height } = data.cell;
                    const cx = x + width / 2;

                    if (estado.esSI) {
                        doc.setFillColor(200, 240, 200);
                        doc.rect(x, y, width, height, 'F');
                        doc.setFontSize(7.5); doc.setFont(undefined, 'bold'); doc.setTextColor(20, 100, 20);
                        doc.text('SI', cx, y + height / 2 - 1, { align: 'center' });
                        doc.setFontSize(5.5); doc.setFont(undefined, 'bold'); doc.setTextColor(0, 0, 0);
                        doc.text(estado.fecha, cx, y + height / 2 + 4, { align: 'center' });
                    } else {
                        doc.setFillColor(214, 69, 69);
                        doc.rect(x, y, width, height, 'F');
                        doc.setFontSize(7.5); doc.setFont(undefined, 'bold'); doc.setTextColor(255, 255, 255);
                        doc.text('NO', cx, y + height / 2 + 2.5, { align: 'center' });
                    }

                    doc.setDrawColor(180, 180, 190); doc.setLineWidth(0.2);
                    doc.rect(x, y, width, height, 'S');
                }
            });

            yPosition = doc.lastAutoTable.finalY + 8;
        });

        const pageCount = doc.internal.getNumberOfPages();
        const hora      = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
        for (let i = 1; i <= pageCount; i++) {
            doc.setPage(i);
            doc.setDrawColor(210, 215, 230); doc.setLineWidth(0.4);
            doc.line(10, pageHeight - 12, pageWidth - 10, pageHeight - 12);
            doc.setFontSize(6.5); doc.setTextColor(150, 150, 160); doc.setFont(undefined, 'normal');
            doc.text('Sistema de Gestión de Recursos Humanos', 12, pageHeight - 7);
            doc.text(`Generado el ${fechaActual} a las ${hora}`, pageWidth / 2, pageHeight - 7, { align: 'center' });
            doc.setFont(undefined, 'bold'); doc.setTextColor(80, 80, 100);
            doc.text(`Página ${i} de ${pageCount}`, pageWidth - 12, pageHeight - 7, { align: 'right' });
        }

        const link    = document.createElement('a');
        link.href     = URL.createObjectURL(doc.output('blob'));
        link.download = 'folios_por_vencer.pdf';
        link.click();
        Swal.close();
    };
}