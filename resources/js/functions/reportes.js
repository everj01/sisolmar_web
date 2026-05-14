import axios from 'axios';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';
import Swal from 'sweetalert2';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

let datosVigentes = null;
let datosPendientes = null;
let datosPorVencer = null;
//let tsEscaneoPersonal = null;


document.addEventListener('DOMContentLoaded', () => {



    const modales = {
        foliosVigentes: document.getElementById('modalFoliosVigentes'),
        foliosPendientesSucursal: document.getElementById('modalFoliosPendientesSucursal'),
        foliosPorVencer: document.getElementById('modalFoliosPorVencer'),
        foliosPendientesEscaneo: document.getElementById('modalFoliosPendientesEscaneo'),
        foliosPendientesRegistro: document.getElementById('modalFoliosPendientesRegistro'),
        vigenciaDocumentos: document.getElementById('modalVigenciaDocumentos'),
        carnet: document.getElementById('modalCarnet'),
        certificados: document.getElementById('modalCertificados'),
        constanciasEntrega: document.getElementById('modalConstanciasEntrega'),
    };

    function abrirModal(modal) {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModal(modal) {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function cerrarTodo() {
        Object.values(modales).forEach(m => { if (m) cerrarModal(m); });
        ['resultadosFoliosVigentes', 'resultadosFoliosPendientes', 'resultadosFoliosPorVencer', 'resultadosEscaneo'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.add('hidden');
        });
        limpiarSelectorPersonalEscaneo();
    }

    document.querySelectorAll('.btnCerrarModal').forEach(btn => {
        btn.addEventListener('click', cerrarTodo);
    });

    Object.values(modales).forEach(modal => {
        if (!modal) return;
        modal.addEventListener('click', (e) => { if (e.target === modal) cerrarTodo(); });
    });

    new TomSelect('#filtroClienteSelect', { placeholder: '-Seleccionar-', allowEmptyOption: true });
    new TomSelect('#filtroSucursalSelect', { placeholder: '-Seleccionar-', allowEmptyOption: true });
     new TomSelect('#filtroEscaneoSucursal', {
      placeholder: 'Todas',
      allowEmptyOption: true,
      onChange: () => {
          personalSeleccionados.clear();
          actualizarContadorEscaneo();
          ejecutarBusquedaPersonal();
      }
  })
     new TomSelect('#filtroEscaneoCliente', {
      placeholder: 'Todos',
      allowEmptyOption: true,
      onChange: () => {
          personalSeleccionados.clear();
          actualizarContadorEscaneo();
          sincronizarCheckAll();
      }
  });
    const btnMap = {
        btnReporteFoliosVigentes: modales.foliosVigentes,
        btnReporteFoliosPendientesSucursal: modales.foliosPendientesSucursal,
        btnReporteFoliosPorVencer: modales.foliosPorVencer,
        btnReporteFoliosPendientesEscaneo: modales.foliosPendientesEscaneo,
        btnReporteFoliosPendientesRegistro: modales.foliosPendientesRegistro,
        btnReporteVigenciaDocumentos: modales.vigenciaDocumentos,
        btnReporteCarnet: modales.carnet,
        btnReporteCertificados: modales.certificados,
        btnReporteConstanciasEntrega: modales.constanciasEntrega,
    };

    Object.entries(btnMap).forEach(([id, modal]) => {
        const btn = document.getElementById(id);
        if (btn) btn.addEventListener('click', () => abrirModal(modal));
    });

    const radios = document.querySelectorAll('input[name="tipoFiltro"]');
    const filtroSucursalDiv = document.getElementById('filtroSucursalDiv');
    const filtroClienteDiv = document.getElementById('filtroClienteDiv');
    const filtroCodigoDiv = document.getElementById('filtroCodigoDiv');

    radios.forEach(radio => {
        radio.addEventListener('change', function () {
            filtroSucursalDiv.classList.add('hidden');
            filtroClienteDiv.classList.add('hidden');
            filtroCodigoDiv.classList.add('hidden');
            if (this.value === 'sucursal') filtroSucursalDiv.classList.remove('hidden');
            else if (this.value === 'cliente') filtroClienteDiv.classList.remove('hidden');
            else if (this.value === 'servicio') filtroCodigoDiv.classList.remove('hidden');
        });
    });
});


// ══════════════════════════════════════
// FOLIOS VIGENTES
// ══════════════════════════════════════
document.getElementById('btnGenerarFoliosVigentes').addEventListener('click', () => {
    const tipo = document.getElementById('filtroTipoFolio').value;
    const prioridad = document.getElementById('filtroPrioridad').value;

    Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    axios.get(`${VITE_URL_APP}/api/get-folios`).then(response => {
        let datos = response.data.filter(f => f.habilitado == "1");
        if (tipo) datos = datos.filter(f => f.tipoFolio === tipo);
        if (prioridad) datos = datos.filter(f => f.prioridad === prioridad);

        if (!datos.length) { Swal.fire({ icon: 'warning', title: 'Sin resultados', text: 'No hay folios con esos filtros' }); return; }

        datosVigentes = datos;
        renderTablaVigentes(datos);
        Swal.close();
    }).catch(() => Swal.fire('Error', 'No se pudo cargar el reporte', 'error'));
});

function renderTablaVigentes(datos) {
    document.getElementById('totalFoliosVigentes').textContent = `${datos.length} folios`;
    document.getElementById('tablaFoliosVigentes').innerHTML = `
          <table class="tabla-reporte">
              <thead><tr>
                  <th style="width:40px">N°</th>
                  <th style="text-align:left">Nombre del Folio</th>
                  <th>Tipo</th><th>Prioridad</th><th>Vencimiento</th>
              </tr></thead>
              <tbody>
                  ${datos.map((f, i) => `<tr>
                      <td class="tc">${i + 1}</td>
                      <td>${f.nombre}</td>
                      <td class="tc">${f.tipoFolio}</td>
                      <td class="tc">${f.prioridad}</td>
                      <td class="tc">${f.periodo || 'Sin vencimiento'}</td>
                  </tr>`).join('')}
              </tbody>
          </table>`;
    document.getElementById('resultadosFoliosVigentes').classList.remove('hidden');
}

document.getElementById('btnExportPdfVigentes').addEventListener('click', () => {
    if (!datosVigentes) return;
    const doc = new jsPDF();
    const fw = doc.internal.pageSize.getWidth();
    const fecha = new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });
    const hora = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
    const img = new Image();
    img.src = `${VITE_URL_APP}/images/banners/banner_folios_vigentes.jpeg`;
    img.onload = () => {
        doc.addImage(img, 'JPEG', 0, 0, fw, 50);
        let y = 60;
        doc.setFontSize(13); doc.setFont(undefined, 'bold');
        doc.setFillColor(6, 10, 81); doc.rect(10, y, 190, 7, 'F');
        doc.setTextColor(255, 255, 255); doc.text('LISTADO GENERAL DE FOLIOS', 12, y + 5);
        doc.setTextColor(0, 0, 0); y += 10;
        autoTable(doc, {
            startY: y,
            head: [['N°', 'Nombre del Folio', 'Tipo', 'Prioridad', 'Vencimiento']],
            body: datosVigentes.map((f, i) => [i + 1, f.nombre, f.tipoFolio, f.prioridad, f.periodo || 'Sin vencimiento']),
            styles: { fontSize: 8, cellPadding: 2, lineColor: [189, 195, 199], lineWidth: 0.1 },
            headStyles: { fillColor: [6, 10, 81], textColor: [255, 255, 255], fontStyle: 'bold', halign: 'center', fontSize: 9, cellPadding: 3 },
            alternateRowStyles: { fillColor: [250, 250, 250] },
            columnStyles: {
                0: { halign: 'center', cellWidth: 12 }, 1: { cellWidth: 70 }, 2: { halign: 'center', cellWidth: 30 }, 3: { halign: 'center', cellWidth: 30 }, 4: {
                    halign: 'center', cellWidth: 38
                }
            },
            margin: { left: 10, right: 10 }, theme: 'grid'
        });
        const pc = doc.internal.getNumberOfPages();
        for (let i = 1; i <= pc; i++) {
            doc.setPage(i);
            doc.setDrawColor(189, 195, 199); doc.setLineWidth(0.5); doc.line(10, 282, 200, 282);
            doc.setFontSize(7); doc.setTextColor(127, 140, 141); doc.setFont(undefined, 'normal');
            doc.text('Sistema de Gestión de Recursos Humanos', 10, 287);
            doc.text(`Generado el ${fecha} a las ${hora}`, 105, 287, { align: 'center' });
            doc.setFont(undefined, 'bold'); doc.text(`Página ${i} de ${pc}`, 200, 287, { align: 'right' });
        }
        window.open(URL.createObjectURL(doc.output('blob')), '_blank');
    };
});

document.getElementById('btnExportExcelVigentes').addEventListener('click', () => {
    if (!datosVigentes) return;
    const ws = XLSX.utils.json_to_sheet(datosVigentes.map((f, i) => ({
        'N°': i + 1, 'Nombre del Folio': f.nombre, 'Tipo': f.tipoFolio,
        'Prioridad': f.prioridad, 'Vencimiento': f.periodo || 'Sin vencimiento'
    })));
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Folios Vigentes');
    XLSX.writeFile(wb, 'folios_vigentes.xlsx');
});


// ══════════════════════════════════════
// FOLIOS PENDIENTES SUCURSAL
// ══════════════════════════════════════
document.getElementById('btnGenerarFoliosPendientes').addEventListener('click', () => {
    const sucursal = document.getElementById('sucursal').value;
    if (!sucursal) { Swal.fire({ icon: 'warning', title: 'Sucursal requerida', text: 'Seleccione una sucursal' }); return; }
    Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    axios.get(`${VITE_URL_APP}/api/reporte/folios-pendientes-sucursal`, { params: { sucursal } })
        .then(response => {
            if (!response.data.length) { Swal.fire({ icon: 'warning', title: 'Sin resultados', text: 'No hay folios pendientes' }); return; }
            datosPendientes = response.data;
            renderTablaPendientes(response.data);
            Swal.close();
        })
        .catch(() => Swal.fire('Error', 'No se pudo cargar el reporte', 'error'));
});

const ABREV = {
    'ACTA DE COMPROMISO': 'ACTA\nCOMP', 'ANTECEDENTE JUDICIAL': 'ANT\nJUD', 'ANTECEDENTE PENAL': 'ANT\nPENAL',
    'ANTECEDENTE POLICIAL': 'ANT\nPOL', 'BASICO DE MERCANCIAS PELIGROSAS': 'MERC\nPEL', 'BASICO DE SEGURIDAD PORTUARIA': 'SEG\nPORT',
    'BASICO I DEL CODIGO PBIP': 'PBIP\nI', 'BASICO II DEL CODIGO PBIP': 'PBIP\nII', 'BREVETE': 'BREV',
    'CARNET DE MARINERO DE BAHIA': 'CARN\nMAR', 'CARNET PATRON DE BAHIA': 'CARN\nPAT', 'CARNET SUCAMEC': 'SUCA\nMEC',
    'CERTIFICADO DE EXAMEN MEDICO': 'CERT\nMED', 'CERTIFICADO DE EXAMEN PSICOLOGICO': 'CERT\nPSICO',
    'CERTIFICADO DE EXAMEN TOXICOLOGICO EXTERNO': 'TOX\nEXT', 'CERTIFICADO DE VACUNACION': 'CERT\nVAC',
    'CERTIFICADO UNICO LABORAL - CUL': 'CUL', 'COMPROMISO DE CONFIABILIDAD': 'CONF',
    'DECLARACION JURADA DE CUMPLIMIENTO DE DISPOSICIONES': 'DJ\nDISP', 'DECLARACION JURADA DE TRABAJADORES (DJ)': 'DJ\nTRAB',
    'DJ DE BENEFICIARIO DE POLIZA VIDA LEY': 'DJ\nPOL', 'ESTUDIO DE SEGURIDAD DE TRABAJADORES': 'EST\nSEG',
    'EVALUACION DEL POSTULANTE': 'EVAL\nPOST', 'FOTOCONTROL': 'FOTO\nCONT', 'GESTION DE SEGURIDAD PORTUARIA': 'GEST\nSEG',
    'GESTION MERCANCIAS PELIGROSAS': 'GEST\nMERC', 'INDUCCION DE SEGURIDAD': 'IND\nSEG',
    'IPER - RAD N° 0025-2024 - APN-DIR': 'IPER', 'LICENCIA DE ARMAS': 'LIC\nARM',
    'POLIZA SCTR': 'SCTR', 'POLIZA SEGURO VIDA LEY': 'SEG\nVIDA', 'VERIFICACION DOMICILIARIA': 'VERIF\nDOM',
};
function abreviar(n) { const u = n?.toUpperCase().trim(); return ABREV[u] || u; }

function buildPorSucursal(data) {
    const r = {};
    data.forEach(g => {
        if (!r[g.sucursal]) r[g.sucursal] = [];
        g.personal.forEach(p => r[g.sucursal].push({ nombre: p.personal, docs: p.documentos }));
    });
    return r;
}
function buildTodosDocs(data) {
    const s = new Set();
    data.forEach(g => g.personal.forEach(p => p.documentos.forEach(d => s.add(d.documento?.toUpperCase().trim()))));
    return [...s];
}

function renderTablaPendientes(data) {
    const porSucursal = buildPorSucursal(data);
    const todosLosDocs = buildTodosDocs(data);
    let totalPersonas = 0, html = '';

    Object.entries(porSucursal).forEach(([suc, personas]) => {
        totalPersonas += personas.length;
        html += `<div class="mb-6">
              <div class="reporte-grupo-header">Sucursal: ${suc}</div>
              <div class="overflow-x-auto"><table class="tabla-reporte">
                  <thead><tr>
                      <th style="min-width:160px;text-align:left">APELLIDOS Y NOMBRES</th>
                      ${todosLosDocs.map(d => `<th style="min-width:44px">${abreviar(d).replace(/\n/g, '<br>')}</th>`).join('')}
                  </tr></thead>
                  <tbody>${personas.map(p => {
            const set = new Set(p.docs.map(d => d.documento?.toUpperCase().trim()));
            const map = {}; p.docs.forEach(d => { map[d.documento?.toUpperCase().trim()] = d.tipo_folio?.toUpperCase().trim(); });
            return `<tr><td>${p.nombre}</td>${todosLosDocs.map(doc => {
                if (!set.has(doc)) return `<td class="celda-x">X</td>`;
                return map[doc] === 'PRINCIPAL' ? `<td class="celda-p">P</td>` : `<td class="celda-a">A</td>`;
            }).join('')}</tr>`;
        }).join('')}</tbody>
              </table></div></div>`;
    });

    document.getElementById('totalFoliosPendientes').textContent = `${totalPersonas} personas · ${Object.keys(porSucursal).length} sucursal(es)`;
    document.getElementById('tablaFoliosPendientes').innerHTML = html;
    document.getElementById('resultadosFoliosPendientes').classList.remove('hidden');
}

document.getElementById('btnExportPdfPendientes').addEventListener('click', () => {
    if (!datosPendientes) return;
    const porSucursal = buildPorSucursal(datosPendientes);
    const todosLosDocs = buildTodosDocs(datosPendientes);
    const doc = new jsPDF({ orientation: 'landscape', format: 'a3' });
    const PW = doc.internal.pageSize.getWidth(), PH = doc.internal.pageSize.getHeight();
    const MARGEN = 8, BANNER_H = 50;
    const fecha = new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });
    const hora = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
    const anchoNomb = 44, anchoDoc = Math.max(9, (PW - MARGEN * 2 - anchoNomb) / todosLosDocs.length);
    const cabecera = ['APELLIDOS Y NOMBRES', ...todosLosDocs.map(d => abreviar(d))];
    const colStyles = { 0: { cellWidth: anchoNomb, overflow: 'linebreak' } };
    todosLosDocs.forEach((_, i) => { colStyles[i + 1] = { cellWidth: anchoDoc, halign: 'center' }; });

    function construirFilas(personas) {
        return personas.map(p => {
            const set = new Set(p.docs.map(d => d.documento?.toUpperCase().trim()));
            const map = {}; p.docs.forEach(d => { map[d.documento?.toUpperCase().trim()] = d.tipo_folio?.toUpperCase().trim(); });
            return [
                { content: p.nombre, styles: { fontSize: 6.5, cellPadding: { top: 2, bottom: 2, left: 2, right: 2 } } },
                ...todosLosDocs.map(doc => {
                    const tiene = set.has(doc), tipo = map[doc];
                    return {
                        content: tiene ? (tipo === 'PRINCIPAL' ? 'P' : 'A') : 'X', styles: {
                            fillColor: tiene ? (tipo === 'PRINCIPAL' ? [34, 139, 34] : [41, 128, 185]) : [192, 57, 43], textColor:
                                [255, 255, 255], fontStyle: 'bold', halign: 'center', fontSize: 7, cellPadding: { top: 2, bottom: 2, left: 1, right: 1 }
                        }
                    };
                })
            ];
        });
    }

    function renderPdf(imgEl) {
        let primera = true;
        Object.entries(porSucursal).forEach(([suc, personas]) => {
            if (!primera) doc.addPage();
            const yTop = primera && imgEl ? BANNER_H + 3 : 12;
            if (primera && imgEl) doc.addImage(imgEl, 'JPEG', 0, 0, PW, BANNER_H);
            let y = yTop;
            doc.setFontSize(8); doc.setFont(undefined, 'bold');
            doc.setFillColor(6, 10, 81); doc.rect(MARGEN, y, PW - MARGEN * 2, 6.5, 'F');
            doc.setTextColor(255, 255, 255); doc.text(`Sucursal: ${suc}`, MARGEN + 2, y + 4.5); doc.setTextColor(0, 0, 0);
            y += 9;
            // leyenda
            [{ color: [34, 139, 34], l: 'P', t: '= PRINCIPAL' }, { color: [41, 128, 185], l: 'A', t: '= ADICIONAL' }, { color: [192, 57, 43], l: 'X', t: '= PENDIENTE' }].forEach((item, xi) => {
                const lx = MARGEN + xi * 50;
                doc.setFillColor(...item.color); doc.roundedRect(lx, y, 5, 4, 0.8, 0.8, 'F');
                doc.setTextColor(255, 255, 255); doc.setFont(undefined, 'bold'); doc.setFontSize(6.5); doc.text(item.l, lx + 2.5, y + 3, { align: 'center' });
                doc.setTextColor(60, 60, 60); doc.setFont(undefined, 'normal'); doc.text(item.t, lx + 7, y + 3);
            });
            y += 8;
            autoTable(doc, {
                startY: y, head: [cabecera], body: construirFilas(personas), styles: {
                    fontSize: 6, cellPadding: { top: 2, bottom: 2, left: 1, right: 1 }, lineColor: [200, 200, 210], lineWidth: 0.15,
                    overflow: 'linebreak', minCellHeight: 8
                }, headStyles: {
                    fillColor: [15, 23, 80], textColor: [255, 255, 255], fontStyle: 'bold', halign: 'center', fontSize: 5.5, cellPadding: { top: 2, bottom: 2, left: 1, right: 1 },
                    minCellHeight: 14, valign: 'middle'
                }, alternateRowStyles: { fillColor: [248, 249, 252] }, columnStyles: colStyles, margin: { left: MARGEN, right: MARGEN }, theme: 'grid'
            });
            primera = false;
        });
        const pc = doc.internal.getNumberOfPages();
        for (let i = 1; i <= pc; i++) {
            doc.setPage(i);
            doc.setDrawColor(210, 215, 230); doc.setLineWidth(0.4); doc.line(MARGEN, PH - 12, PW - MARGEN, PH - 12);
            doc.setFontSize(6.5); doc.setTextColor(150, 150, 160); doc.setFont(undefined, 'normal');
            doc.text('Sistema de Gestión de Recursos Humanos', MARGEN + 2, PH - 7);
            doc.text(`Generado el ${fecha} a las ${hora}`, PW / 2, PH - 7, { align: 'center' });
            doc.setFont(undefined, 'bold'); doc.setTextColor(80, 80, 100);
            doc.text(`Página ${i} de ${pc}`, PW - MARGEN - 2, PH - 7, { align: 'right' });
        }
        const link = document.createElement('a'); link.href = URL.createObjectURL(doc.output('blob')); link.download = 'folios_pendientes_sucursal.pdf'; link.click();
    }

    const img = new Image(); img.crossOrigin = 'anonymous';
    img.onload = () => renderPdf(img);
    img.onerror = () => renderPdf(null);
    img.src = `${VITE_URL_APP}/images/banners/banner_folios_pendientes.jpeg`;
});

document.getElementById('btnExportExcelPendientes').addEventListener('click', () => {
    if (!datosPendientes) return;
    const porSucursal = buildPorSucursal(datosPendientes);
    const todosLosDocs = buildTodosDocs(datosPendientes);
    const rows = [];
    Object.entries(porSucursal).forEach(([suc, personas]) => {
        personas.forEach(p => {
            const set = new Set(p.docs.map(d => d.documento?.toUpperCase().trim()));
            const map = {}; p.docs.forEach(d => { map[d.documento?.toUpperCase().trim()] = d.tipo_folio?.toUpperCase().trim(); });
            const row = { 'Sucursal': suc, 'Apellidos y Nombres': p.nombre };
            todosLosDocs.forEach(doc => { row[doc] = set.has(doc) ? (map[doc] === 'PRINCIPAL' ? 'P' : 'A') : 'X'; });
            rows.push(row);
        });
    });
    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Folios Pendientes');
    XLSX.writeFile(wb, 'folios_pendientes_sucursal.xlsx');
});


// ══════════════════════════════════════
// FOLIOS POR VENCER
// ══════════════════════════════════════
let tipoFiltro = 'sucursal';
let filtroValue = '';

document.querySelectorAll('input[name="tipoFiltro"]').forEach(r => {
    r.addEventListener('change', function () { tipoFiltro = this.value; filtroValue = ''; });
});
document.getElementById('filtroSucursalSelect').addEventListener('change', function () { filtroValue = this.value; });
document.getElementById('filtroClienteSelect').addEventListener('change', function () { filtroValue = this.value; });

const datosPrueba = [
    {
        codPersonal: '14991', personal: 'ACUÑA LOPEZ HUGO GERMAN', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/10/2022', cargo: 'AGENTE DE SEGURIDAD', documento: 'EVAL POST',
        tipo_folio: 'PRINCIPAL', dias_restantes: '9', fecha_caducidad: '2026-03-27 00:00:00'
    },
    {
        codPersonal: '14991', personal: 'ACUÑA LOPEZ HUGO GERMAN', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/10/2022', cargo: 'AGENTE DE SEGURIDAD', documento: 'EST SEG',
        tipo_folio: 'PRINCIPAL', dias_restantes: '15', fecha_caducidad: '2026-04-02 00:00:00'
    },
    {
        codPersonal: '14991', personal: 'ACUÑA LOPEZ HUGO GERMAN', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/10/2022', cargo: 'AGENTE DE SEGURIDAD', documento: 'CAPA',
        tipo_folio: 'SECUNDARIO', dias_restantes: '20', fecha_caducidad: '2026-04-07 00:00:00'
    },
    {
        codPersonal: '14991', personal: 'ACUÑA LOPEZ HUGO GERMAN', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/10/2022', cargo: 'AGENTE DE SEGURIDAD', documento: 'CV',
        tipo_folio: 'PRINCIPAL', dias_restantes: '5', fecha_caducidad: '2026-03-23 00:00:00'
    },
    {
        codPersonal: '16804', personal: 'AGUILAR CAUSHI LUZ KARINA', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '13/01/2025', cargo: 'OPERADOR CCTV', documento: 'EVAL POST',
        tipo_folio: 'PRINCIPAL', dias_restantes: '3', fecha_caducidad: '2026-03-21 00:00:00'
    },
    {
        codPersonal: '16804', personal: 'AGUILAR CAUSHI LUZ KARINA', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '13/01/2025', cargo: 'OPERADOR CCTV', documento: 'POLI',
        tipo_folio: 'SECUNDARIO', dias_restantes: '25', fecha_caducidad: '2026-04-12 00:00:00'
    },
    {
        codPersonal: '05485', personal: 'ALCANTARA CALDERON MANUEL ANTONIO', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/05/2019', cargo: 'AGENTE DE SEGURIDAD', documento: 'EVAL POST',
        tipo_folio: 'PRINCIPAL', dias_restantes: '18', fecha_caducidad: '2026-04-05 00:00:00'
    },
    {
        codPersonal: '05485', personal: 'ALCANTARA CALDERON MANUEL ANTONIO', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/05/2019', cargo: 'AGENTE DE SEGURIDAD', documento: 'EST SEG',
        tipo_folio: 'PRINCIPAL', dias_restantes: '7', fecha_caducidad: '2026-03-25 00:00:00'
    },
    {
        codPersonal: '17024', personal: 'ALVARADO FERRER ANTOFELY', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/04/2025', cargo: 'AGENTE DE SEGURIDAD', documento: 'CV',
        tipo_folio: 'PRINCIPAL', dias_restantes: '6', fecha_caducidad: '2026-03-24 00:00:00'
    },
    {
        codPersonal: '17024', personal: 'ALVARADO FERRER ANTOFELY', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/04/2025', cargo: 'AGENTE DE SEGURIDAD', documento: 'EXA PSICO',
        tipo_folio: 'PRINCIPAL', dias_restantes: '10', fecha_caducidad: '2026-03-28 00:00:00'
    },
    {
        codPersonal: '17562', personal: 'ALVAREZ VALLADARES JEAN PIERRE BRANDON', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '27/12/2025', cargo: 'AGENTE DE SEGURIDAD', documento: 'EVAL POST',
        tipo_folio: 'PRINCIPAL', dias_restantes: '4', fecha_caducidad: '2026-03-22 00:00:00'
    },
    {
        codPersonal: '17562', personal: 'ALVAREZ VALLADARES JEAN PIERRE BRANDON', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '27/12/2025', cargo: 'AGENTE DE SEGURIDAD', documento: 'PENAL',
        tipo_folio: 'PRINCIPAL', dias_restantes: '11', fecha_caducidad: '2026-03-29 00:00:00'
    },
];

function buildPorVencer(datos) {
    const groupKey = tipoFiltro === 'cliente' ? 'cliente' : 'sucursal';
    const porGrupo = {}, docsSet = new Set();
    datos.forEach(d => {
        docsSet.add(d.documento);
        if (!porGrupo[d[groupKey]]) porGrupo[d[groupKey]] = {};
        if (!porGrupo[d[groupKey]][d.codPersonal]) {
            porGrupo[d[groupKey]][d.codPersonal] = { codigo: d.codPersonal, personal: d.personal, ingresoSolmar: d.ingresoSolmar || '-', cargo: d.cargo || '-', documentos: {} };
        }
        porGrupo[d[groupKey]][d.codPersonal].documentos[d.documento] = { fecha_caducidad: d.fecha_caducidad };
    });
    return { porGrupo, listaDocumentos: [...docsSet] };
}

document.getElementById('btnGenerarFoliosPorVencer').addEventListener('click', () => {
    if (!tipoFiltro || !filtroValue) { Swal.fire('Atención', 'Selecciona un filtro antes de generar', 'warning'); return; }
    Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    // DATOS DE PRUEBA — comentar cuando el backend esté listo
    datosPorVencer = datosPrueba;
    renderTablaPorVencer(datosPrueba);
    Swal.close();

    // ENDPOINT REAL — descomentar cuando el backend esté listo
    // const endpoint = tipoFiltro === 'cliente' ? `${VITE_URL_APP}/reporte/folios-por-vencer-cliente` : `${VITE_URL_APP}/reporte/folios-por-vencer`;
    // axios.get(endpoint, { params: tipoFiltro === 'cliente' ? { cliente: filtroValue } : { sucursal: filtroValue } })
    //     .then(r => { datosPorVencer = r.data; renderTablaPorVencer(r.data); Swal.close(); })
    //     .catch(e => { console.error(e); Swal.fire('Error', 'No se pudo cargar el reporte', 'error'); });
});

function renderTablaPorVencer(datos) {
    const { porGrupo, listaDocumentos } = buildPorVencer(datos);
    const labelGrupo = tipoFiltro === 'cliente' ? 'Cliente' : 'Sucursal';
    let totalPersonas = 0, html = '';

    Object.entries(porGrupo).forEach(([grupo, personas]) => {
        const arr = Object.values(personas);
        totalPersonas += arr.length;
        html += `<div class="mb-6">
              <div class="reporte-grupo-header">${labelGrupo}: ${grupo}</div>
              <div class="overflow-x-auto"><table class="tabla-reporte">
                  <thead><tr>
                      <th style="width:30px">IT</th>
                      <th style="width:60px">CÓDIGO</th>
                      <th style="min-width:150px;text-align:left">APELLIDOS Y NOMBRES</th>
                      <th style="width:80px">INGRESO</th>
                      <th style="min-width:100px;text-align:left">CARGO</th>
                      ${listaDocumentos.map(d => `<th style="min-width:80px">${d}</th>`).join('')}
                  </tr></thead>
                  <tbody>${arr.map((p, idx) => `<tr>
                      <td class="tc">${idx + 1}</td>
                      <td class="tc">${p.codigo}</td>
                      <td>${p.personal}</td>
                      <td class="tc">${p.ingresoSolmar}</td>
                      <td>${p.cargo}</td>
                      ${listaDocumentos.map(doc => {
            const info = p.documentos[doc];
            if (info) {
                const f = info.fecha_caducidad ? info.fecha_caducidad.split(' ')[0] : '-';
                return `<td class="celda-si">SI<br><small style="font-size:0.65rem">${f}</small></td>`;
            }
            return `<td class="celda-no">NO</td>`;
        }).join('')}
                  </tr>`).join('')}</tbody>
              </table></div></div>`;
    });

    document.getElementById('totalFoliosPorVencer').textContent = `${totalPersonas} persona(s) · ${Object.keys(porGrupo).length} ${labelGrupo.toLowerCase()}(s)`;
    document.getElementById('tablaFoliosPorVencer').innerHTML = html;
    document.getElementById('resultadosFoliosPorVencer').classList.remove('hidden');
}

document.getElementById('btnExportPdfPorVencer').addEventListener('click', () => {
    if (!datosPorVencer) return;
    const { porGrupo, listaDocumentos } = buildPorVencer(datosPorVencer);
    const doc = new jsPDF({ orientation: 'landscape' });
    const PW = doc.internal.pageSize.getWidth(), PH = doc.internal.pageSize.getHeight();
    const fecha = new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });
    const hora = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
    const labelGrupo = tipoFiltro === 'cliente' ? 'Cliente' : 'Sucursal';
    const img = new Image();
    img.src = `${VITE_URL_APP}/images/banners/BANNER REPORTES DE FOLIOS -02.jpeg`;
    img.onload = () => {
        doc.addImage(img, 'JPEG', 0, 0, PW, 40);
        let y = 50;
        Object.entries(porGrupo).forEach(([grupo, personas]) => {
            if (y > PH - 40) { doc.addPage(); y = 20; }
            doc.setFontSize(9); doc.setFont(undefined, 'bold');
            doc.setFillColor(220, 225, 245); doc.rect(10, y, PW - 20, 6, 'F');
            doc.setTextColor(6, 10, 81); doc.text(`${labelGrupo}: ${grupo}`, 12, y + 4.5); doc.setTextColor(0, 0, 0);
            y += 8;
            const cabecera = ['IT', 'CÓDIGO', 'APELLIDOS Y NOMBRES', 'INGRESO', 'CARGO', ...listaDocumentos];
            const mapaEstado = {};
            const arr = Object.values(personas);
            const filas = arr.map((p, fi) => {
                const celdas = listaDocumentos.map((doc, di) => {
                    const ci = di + 5, info = p.documentos[doc];
                    if (!info) { mapaEstado[`${fi}-${ci}`] = { esSI: false }; return { content: '', styles: { fillColor: [214, 69, 69] } }; }
                    const fecha = info.fecha_caducidad ? info.fecha_caducidad.split(' ')[0] : '-';
                    mapaEstado[`${fi}-${ci}`] = { esSI: true, fecha };
                    return { content: '', styles: { fillColor: [250, 220, 170] } };
                });
                return [
                    { content: fi + 1, styles: { halign: 'center', fontSize: 7 } },
                    { content: p.codigo, styles: { halign: 'center', fontSize: 7 } },
                    { content: p.personal, styles: { fontSize: 7 } },
                    { content: p.ingresoSolmar, styles: { halign: 'center', fontSize: 7 } },
                    { content: p.cargo, styles: { fontSize: 7 } },
                    ...celdas
                ];
            });
            const aIT = 8, aCod = 18, aNom = 36, aIng = 18, aCar = 26;
            const aDoc = (PW - 20 - aIT - aCod - aNom - aIng - aCar) / listaDocumentos.length;
            const cs = { 0: { cellWidth: aIT }, 1: { cellWidth: aCod }, 2: { cellWidth: aNom }, 3: { cellWidth: aIng, halign: 'center' }, 4: { cellWidth: aCar } };
            listaDocumentos.forEach((_, i) => { cs[i + 5] = { cellWidth: aDoc, halign: 'center' }; });
            autoTable(doc, {
                startY: y, head: [cabecera], body: filas,
                styles: { fontSize: 6.5, cellPadding: { top: 3, bottom: 3, left: 2, right: 2 }, lineColor: [180, 180, 190], lineWidth: 0.2, overflow: 'linebreak' },
                headStyles: { fillColor: [15, 23, 80], textColor: [255, 255, 255], fontStyle: 'bold', halign: 'center', fontSize: 6.5, cellPadding: 3 },
                columnStyles: cs, margin: { left: 10, right: 10 }, theme: 'plain',
                didDrawCell: (data) => {
                    if (data.section !== 'body') return;
                    const est = mapaEstado[`${data.row.index}-${data.column.index}`];
                    if (!est) return;
                    const { x, y, width, height } = data.cell, cx = x + width / 2;
                    if (est.esSI) {
                        doc.setFillColor(200, 240, 200); doc.rect(x, y, width, height, 'F');
                        doc.setFontSize(7.5); doc.setFont(undefined, 'bold'); doc.setTextColor(20, 100, 20);
                        doc.text('SI', cx, y + height / 2 - 1, { align: 'center' });
                        doc.setFontSize(5.5); doc.setTextColor(0, 0, 0);
                        doc.text(est.fecha, cx, y + height / 2 + 4, { align: 'center' });
                    } else {
                        doc.setFillColor(214, 69, 69); doc.rect(x, y, width, height, 'F');
                        doc.setFontSize(7.5); doc.setFont(undefined, 'bold'); doc.setTextColor(255, 255, 255);
                        doc.text('NO', cx, y + height / 2 + 2.5, { align: 'center' });
                    }
                    doc.setDrawColor(180, 180, 190); doc.setLineWidth(0.2); doc.rect(x, y, width, height, 'S');
                }
            });
            y = doc.lastAutoTable.finalY + 8;
        });
        const pc = doc.internal.getNumberOfPages();
        for (let i = 1; i <= pc; i++) {
            doc.setPage(i);
            doc.setDrawColor(210, 215, 230); doc.setLineWidth(0.4); doc.line(10, PH - 12, PW - 10, PH - 12);
            doc.setFontSize(6.5); doc.setTextColor(150, 150, 160); doc.setFont(undefined, 'normal');
            doc.text('Sistema de Gestión de Recursos Humanos', 12, PH - 7);
            doc.text(`Generado el ${fecha} a las ${hora}`, PW / 2, PH - 7, { align: 'center' });
            doc.setFont(undefined, 'bold'); doc.setTextColor(80, 80, 100);
            doc.text(`Página ${i} de ${pc}`, PW - 12, PH - 7, { align: 'right' });
        }
        const link = document.createElement('a'); link.href = URL.createObjectURL(doc.output('blob')); link.download = 'folios_por_vencer.pdf'; link.click();
    };
});

document.getElementById('btnExportExcelPorVencer').addEventListener('click', () => {
    if (!datosPorVencer) return;
    const { porGrupo, listaDocumentos } = buildPorVencer(datosPorVencer);
    const labelGrupo = tipoFiltro === 'cliente' ? 'Cliente' : 'Sucursal';
    const rows = [];
    Object.entries(porGrupo).forEach(([grupo, personas]) => {
        Object.values(personas).forEach((p, idx) => {
            const row = { 'IT': idx + 1, [labelGrupo]: grupo, 'Código': p.codigo, 'Apellidos y Nombres': p.personal, 'Ingreso Solmar': p.ingresoSolmar, 'Cargo': p.cargo };
            listaDocumentos.forEach(doc => {
                const info = p.documentos[doc];
                row[doc] = info ? `SI - ${info.fecha_caducidad ? info.fecha_caducidad.split(' ')[0] : '-'}` : 'NO';
            });
            rows.push(row);
        });
    });
    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Por Vencer');
    XLSX.writeFile(wb, 'folios_por_vencer.xlsx');
});


// ══════════════════════════════════════
// FOLIOS PENDIENTES DE ESCANEO
// ══════════════════════════════════════
let datosEscaneo = null;
let personalSeleccionados = new Map();
let resultadosBusqueda = [];
let buscarPersonalTimeout = null;

// Listener delegado — uno solo para todos los checkboxes de la tabla
document.getElementById('tbodyPersonalEscaneo').addEventListener('change', function (e) {
    if (!e.target.classList.contains('chkPersonalEscaneo')) return;
    const codigo = String(e.target.value);
    const persona = resultadosBusqueda.find(p => String(p.CODI_PERS) === codigo);
    if (e.target.checked && persona) {
        personalSeleccionados.set(codigo, persona);
    } else {
        personalSeleccionados.delete(codigo);
    }
    actualizarContadorEscaneo();
    sincronizarCheckAll();
});

function ejecutarBusquedaPersonal() {
    const query = document.getElementById('buscarPersonalEscaneo').value.trim();
    const sucursal = document.getElementById('filtroEscaneoSucursal').value || '0';
    const sinFiltro = query.length < 2 && sucursal === '0';

    if (sinFiltro) {
        document.getElementById('tbodyPersonalEscaneo').innerHTML =
            '<tr><td colspan="4" class="tc" style="color:#94a3b8;padding:12px">Selecciona una sucursal o escribe para buscar</td></tr>';
        resultadosBusqueda = [];
        sincronizarCheckAll();
        return;
    }

    document.getElementById('tbodyPersonalEscaneo').innerHTML =
        '<tr><td colspan="4" class="tc" style="color:#94a3b8;padding:10px">Buscando...</td></tr>';

    axios.get(`${VITE_URL_APP}/api/get-personal-total-reporte`, {
        params: { search: query || null, codSucursal: sucursal, page: 1, size: 9990, tipo_per: 'OPER', vigencia: 'SI'}
    }).then(r => {
        resultadosBusqueda = r.data.data ?? [];
        renderTablaPersonalEscaneo(resultadosBusqueda);
    }).catch(() => {
        document.getElementById('tbodyPersonalEscaneo').innerHTML =
            '<tr><td colspan="4" class="tc" style="color:#ef4444;padding:10px">Error al cargar</td></tr>';
    });
}

document.getElementById('buscarPersonalEscaneo').addEventListener('input', function () {
    clearTimeout(buscarPersonalTimeout);
    buscarPersonalTimeout = setTimeout(ejecutarBusquedaPersonal, 300);
});



function renderTablaPersonalEscaneo(personas) {
    const tbody = document.getElementById('tbodyPersonalEscaneo');
    if (!personas.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="tc" style="color:#94a3b8;padding:12px">Sin resultados</td></tr>';
        sincronizarCheckAll();
        return;
    }
    tbody.innerHTML = personas.map(p => `
          <tr>
              <td class="tc">
                  <input type="checkbox" class="chkPersonalEscaneo"
                      value="${p.CODI_PERS}"
                      ${personalSeleccionados.has(String(p.CODI_PERS)) ? 'checked' : ''}>
              </td>
              <td class="tc">${p.CODI_PERS}</td>
              <td>${p.personal ?? '-'}</td>
              <td class="tc">${p.TIPOTRAB2}</td>
          </tr>
      `).join('');
    sincronizarCheckAll();
}

document.getElementById('chkTodosEscaneo').addEventListener('change', function () {
    const marcar = this.checked;
    document.querySelectorAll('.chkPersonalEscaneo').forEach(chk => {
        chk.checked = marcar;
        const codigo = String(chk.value);
        const persona = resultadosBusqueda.find(p => String(p.CODI_PERS) === codigo);
        if (marcar && persona) personalSeleccionados.set(codigo, persona);
        else personalSeleccionados.delete(codigo);
    });
    actualizarContadorEscaneo();
});

function sincronizarCheckAll() {
    const checks = document.querySelectorAll('.chkPersonalEscaneo');
    const marked = document.querySelectorAll('.chkPersonalEscaneo:checked');
    const chkAll = document.getElementById('chkTodosEscaneo');
    if (!checks.length) { chkAll.checked = false; chkAll.indeterminate = false; }
    else if (marked.length === 0) { chkAll.checked = false; chkAll.indeterminate = false; }
    else if (marked.length === checks.length) { chkAll.checked = true; chkAll.indeterminate = false; }
    else { chkAll.checked = false; chkAll.indeterminate = true; }
}

function actualizarContadorEscaneo() {
    const n = personalSeleccionados.size;
    document.getElementById('contadorSeleccionadosEscaneo').textContent = n ? `${n} seleccionado(s)` : '';
}

function limpiarSelectorPersonalEscaneo() {
    personalSeleccionados.clear();
    resultadosBusqueda = [];
    document.getElementById('buscarPersonalEscaneo').value = '';
    document.getElementById('tbodyPersonalEscaneo').innerHTML =
        '<tr><td colspan="4" class="tc" style="color:#94a3b8;padding:12px">Selecciona una sucursal o escribe para buscar</td></tr>';
    actualizarContadorEscaneo();
    sincronizarCheckAll();
}

document.getElementById('btnGenerarEscaneo').addEventListener('click', () => {
    const sucursal = document.getElementById('filtroEscaneoSucursal').value;
    const cliente = document.getElementById('filtroEscaneoCliente').value;
    const parametros = [...personalSeleccionados.keys()].join(',');

    
    document.getElementById('resultadosEscaneo').classList.add('hidden');
    document.getElementById('tablaEscaneo').innerHTML = '';
    document.getElementById('totalEscaneo').textContent = '';
    Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    axios.get(`${VITE_URL_APP}/api/reporte/estado-legajos`, { params: { sucursal, cliente, parametros } })
        .then(response => {
            if (!response.data.length) {
                Swal.fire({ icon: 'warning', title: 'Sin resultados', text: 'No se encontraron registros con los filtros indicados.' });
                return;
            }
            datosEscaneo = response.data;
            renderTablaEscaneo(response.data);
            Swal.close();
        })
        .catch(() => Swal.fire('Error', 'No se pudo cargar el reporte', 'error'));
});

function celdaEstado(val) {
    const v = (val ?? '').toString().toUpperCase().trim();
    if (v === 'SI') return `<td class="celda-si">SI</td>`;
    if (v === 'NO') return `<td class="celda-no">NO</td>`;
    return `<td class="tc" style="font-size:0.7rem">${val ?? '-'}</td>`;
}

function renderTablaEscaneo(datos) {
     const sucursal = datos[0]?.SUCURSAL ?? '';
  const fecha = new Date().toLocaleDateString('es-PE');
  const clienteEl = document.getElementById('filtroEscaneoCliente');
  const clienteNombre = clienteEl.value ? clienteEl.options[clienteEl.selectedIndex]?.text : '';
    let html = `
      <div class="mb-2 text-center font-semibold text-default-700" style="font-size:0.85rem">
          PENDIENTES DE LA BASE DE DATOS GRÁFICA - SUCURSAL ${sucursal} AL ${fecha}
            ${clienteNombre ? `<br><span style="font-weight:normal;font-size:0.78rem">Cliente: ${clienteNombre}</span>` : ''}
        </div>
      <table class="tabla-reporte" style="font-size:0.7rem;min-width:max-content">
          <thead style="position:sticky;top:0;z-index:2">
              <tr>
                  <th rowspan="2">Cód.</th>
                  <th rowspan="2" style="text-align:left;min-width:130px">Apellidos y Nombres</th>
                  <th rowspan="2">Ingreso<br>SOLMAR</th>
                  <th rowspan="2" style="min-width:80px">Cargo</th>
                  <th rowspan="2">Tipo</th>
                  <th rowspan="2">Foto</th>
                  <th colspan="2">DNI</th>
                  <th colspan="2">Huella D</th>
                  <th rowspan="2">Firma</th>
                  <th colspan="2">FOTO<br>CONTRO</th>
                  <th colspan="2">CI<br>SUCAM</th>
                  <th rowspan="2">Lic.<br>Arma</th>
                  <th colspan="2">Brevete</th>
                  <th rowspan="2">Cert.<br>Estu<br>dios</th>
                  <th rowspan="2">Cert.<br>Labo<br>ral</th>
                  <th colspan="3">Domicilio</th>
                  <th colspan="3">Anteceden</th>
                  <th rowspan="2">Exam.<br>Toxi<br>col.</th>
                  <th rowspan="2">Exam.<br>Méd<br>ico</th>
                  <th rowspan="2">Cert.<br>Psi<br>col.</th>
                  <th rowspan="2">Cert.<br>Vacu<br>na</th>
                  <th rowspan="2">CVO1</th>
                  <th rowspan="2">Fich.<br>Sinto</th>
                  <th rowspan="2">CUL</th>
                  <th rowspan="2">DJ<br>Póliza</th>
              </tr>
              <tr>
                  <th>Anv</th><th>Rev</th>
                  <th>Indi</th><th>To<br>tal</th>
                  <th>Anv</th><th>Rev</th>
                  <th>Anv</th><th>Rev</th>
                  <th>Anv</th><th>Rev</th>
                  <th>Cro<br>quis</th><th>Fach<br>ada</th><th>Entor<br>no</th>
                  <th>Pol</th><th>Pen</th><th>Jud</th>
                 
              </tr>
          </thead>
          <tbody>
              ${datos.map(r => `<tr>
                  <td class="tc">${r.CODIGO}</td>
                  <td>${r.PERSONAL}</td>
                  <td class="tc">${r.INGRESO_PLANILLA ?? '-'}</td>
                  <td>${r.CARGO ?? '-'}</td>
                  <td class="tc">${r.TIPO ?? '-'}</td>
                  ${celdaEstado(r.FOTO)}
                  ${celdaEstado(r.DNI1)}${celdaEstado(r.DNI2)}
                  ${celdaEstado(r.HUELLA)}${celdaEstado(r.HUELLAS5)}
                  ${celdaEstado(r.FIRMA)}
                  ${celdaEstado(r.FOTOCONTROL1)}${celdaEstado(r.FOTOCONTROL2)}
                  ${celdaEstado(r.CD1)}${celdaEstado(r.CD2)}
                  ${celdaEstado(r.LA)}
                  ${celdaEstado(r.BREVETE1)}${celdaEstado(r.BREVETE2)}
                  ${celdaEstado(r.ESTUDIOS)}${celdaEstado(r.LABORAL)}
                  ${celdaEstado(r.CROQUIS)}${celdaEstado(r.FACHADA)}${celdaEstado(r.ENTORNO)}
                  ${celdaEstado(r.POLICIAL)}${celdaEstado(r.PENAL)}${celdaEstado(r.JUDICIAL)}
                  ${celdaEstado(r.TOXI_EXTERNO)}
                  ${celdaEstado(r.MEDICO)}${celdaEstado(r.PSICO)}${celdaEstado(r.VACUNA)}
                  ${celdaEstado(r.CV01)}${celdaEstado(r.FIASINTO)}
                  ${celdaEstado(r.CUL)}${celdaEstado(r.DJ)}
              </tr>`).join('')}
          </tbody>
      </table>`;

    document.getElementById('totalEscaneo').textContent = `${datos.length} registro(s)`;
    document.getElementById('tablaEscaneo').innerHTML = html;
    document.getElementById('resultadosEscaneo').classList.remove('hidden');
}

document.getElementById('btnExportPdfEscaneo').addEventListener('click', () => {
    if (!datosEscaneo) return;
    const doc = new jsPDF({ orientation: 'landscape', format: 'a3' });
    const PW = doc.internal.pageSize.getWidth();
    const PH = doc.internal.pageSize.getHeight();
    const M = 5;
    const fecha = new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });
    const hora = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
    const sucursal = datosEscaneo[0]?.SUCURSAL ?? '';
  const clienteEl = document.getElementById('filtroEscaneoCliente');
  const clienteNombre = clienteEl.value ? clienteEl.options[clienteEl.selectedIndex]?.text : '';

    doc.setFontSize(9); doc.setFont(undefined, 'bold');
    doc.setFillColor(6, 10, 81); doc.rect(M, 8, PW - M * 2, 7, 'F');
    doc.setTextColor(255, 255, 255);
    doc.text(`PENDIENTES DE LA BASE DE DATOS GRÁFICA - SUCURSAL ${sucursal}`, PW / 2, 13, { align: 'center' });
     doc.setTextColor(0, 0, 0);

      let startY = 17;
      if (clienteNombre) {
          doc.setFontSize(7); doc.setFont(undefined, 'normal'); doc.setTextColor(60, 60, 60);
          doc.text(`Cliente: ${clienteNombre}`, PW / 2, 19, { align: 'center' });
          doc.setTextColor(0, 0, 0);
          startY = 23;
      }

      function cellStyle(val) {
        const v = (val ?? '').toString().toUpperCase().trim();
        if (v === 'SI') return { content: 'SI', styles: { fillColor: [209, 250, 229], textColor: [6, 95, 70], fontStyle: 'bold', halign: 'center', fontSize: 5.5 } };
        if (v === 'NO') return { content: 'NO', styles: { fillColor: [254, 226, 226], textColor: [153, 27, 27], fontStyle: 'bold', halign: 'center', fontSize: 5.5 } };
        return { content: val ?? '-', styles: { halign: 'center', fontSize: 5 } };
    }

    const head = [[
        { content: 'Cód.', rowSpan: 2 }, { content: 'Apellidos y Nombres', rowSpan: 2 },
        { content: 'Ingreso\nSOLMAR', rowSpan: 2 }, { content: 'Cargo', rowSpan: 2 },
        { content: 'Tipo', rowSpan: 2 }, { content: 'Foto', rowSpan: 2 },
        { content: 'DNI', colSpan: 2 }, { content: 'Huella D', colSpan: 2 },
        { content: 'Firma', rowSpan: 2 }, { content: 'FOTO\nCONTRO', colSpan: 2 },
        { content: 'CI\nSUCAM', colSpan: 2 }, { content: 'Lic.\nArma', rowSpan: 2 },
        { content: 'Brevete', colSpan: 2 }, { content: 'Cert.\nEstu', rowSpan: 2 },
        { content: 'Cert.\nLab.', rowSpan: 2 }, { content: 'Domicilio', colSpan: 3 },
        { content: 'Anteceden', colSpan: 3 }, { content: 'Exam.\nToxicol.', rowSpan: 2 },
        { content: 'Exam.\nMéd.', rowSpan: 2 }, { content: 'Exam.\nPsico', rowSpan: 2 },
        { content: 'Cert.\nVac.', rowSpan: 2 }, { content: 'CV01', rowSpan: 2 },
        { content: 'Fich.\nSinto', rowSpan: 2 }, { content: 'CUL', rowSpan: 2 },
          { content: 'DJ\nPóliza', rowSpan: 2 },
    ], [
        'Anv', 'Rev', 'Indi', 'Total', 'Anv', 'Rev', 'Anv', 'Rev', 'Anv', 'Rev',
        'Cro', 'Fach', 'Ent', 'Pol', 'Pen', 'Jud', 'Toxi\nSol', 'Toxi\nExt',
    ]];

    const body = datosEscaneo.map(r => [
        { content: r.CODIGO, styles: { halign: 'center', fontSize: 5.5 } },
        { content: r.PERSONAL, styles: { fontSize: 5.5 } },
        { content: r.INGRESO_PLANILLA ?? '-', styles: { halign: 'center', fontSize: 5.5 } },
        { content: r.CARGO ?? '-', styles: { fontSize: 5.5 } },
        { content: r.TIPO ?? '-', styles: { halign: 'center', fontSize: 5.5 } },
        cellStyle(r.FOTO),
        cellStyle(r.DNI1), cellStyle(r.DNI2),
        cellStyle(r.HUELLA), cellStyle(r.HUELLAS5),
        cellStyle(r.FIRMA),
        cellStyle(r.FOTOCONTROL1), cellStyle(r.FOTOCONTROL2),
        cellStyle(r.CD1), cellStyle(r.CD2),
        cellStyle(r.LA),
        cellStyle(r.BREVETE1), cellStyle(r.BREVETE2),
        cellStyle(r.ESTUDIOS), cellStyle(r.LABORAL),
        cellStyle(r.CROQUIS), cellStyle(r.FACHADA), cellStyle(r.ENTORNO),
        cellStyle(r.POLICIAL), cellStyle(r.PENAL), cellStyle(r.JUDICIAL),
        cellStyle(r.TOXI_EXTERNO),
        cellStyle(r.MEDICO), cellStyle(r.PSICO), cellStyle(r.VACUNA),
        cellStyle(r.CV01), cellStyle(r.FIASINTO),
        cellStyle(r.CUL), cellStyle(r.DJ),
    ]);

    const cw = Math.max(5, (PW - M * 2 - 9 - 30 - 13 - 17 - 9) / 29);
    const cs = { 0: { cellWidth: 9 }, 1: { cellWidth: 30 }, 2: { cellWidth: 13 }, 3: { cellWidth: 17 }, 4: { cellWidth: 9 } };
    for (let i = 5; i < 34; i++) cs[i] = { cellWidth: cw, halign: 'center' };

    autoTable(doc, {
        startY: 17, head, body,
        styles: { fontSize: 5.5, cellPadding: { top: 1, bottom: 1, left: 1, right: 1 }, lineColor: [200, 200, 210], lineWidth: 0.15, overflow: 'ellipsize', minCellHeight: 6 },
        headStyles: { fillColor: [15, 23, 80], textColor: [255, 255, 255], fontStyle: 'bold', halign: 'center', fontSize: 5, cellPadding: { top: 1, bottom: 1, left: 1, right: 1 }, valign: 'middle' },
        alternateRowStyles: { fillColor: [248, 249, 252] },
        columnStyles: cs, margin: { left: M, right: M }, theme: 'grid',
    });

    const pc = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pc; i++) {
        doc.setPage(i);
        doc.setDrawColor(210, 215, 230); doc.setLineWidth(0.4); doc.line(M, PH - 10, PW - M, PH - 10);
        doc.setFontSize(6); doc.setTextColor(150, 150, 160); doc.setFont(undefined, 'normal');
        doc.text('Sistema de Gestión de Recursos Humanos', M + 2, PH - 5);
        doc.text(`Generado el ${fecha} a las ${hora}`, PW / 2, PH - 5, { align: 'center' });
        doc.setFont(undefined, 'bold'); doc.setTextColor(80, 80, 100);
        doc.text(`Página ${i} de ${pc}`, PW - M - 2, PH - 5, { align: 'right' });
    }
    const link = document.createElement('a');
    link.href = URL.createObjectURL(doc.output('blob'));
    link.download = 'pendientes_escaneo.pdf';
    link.click();
});

document.getElementById('btnExportExcelEscaneo').addEventListener('click', () => {
    if (!datosEscaneo) return;
     const sucursal = datosEscaneo[0]?.SUCURSAL ?? '';
  const fecha = new Date().toLocaleDateString('es-PE');
  const clienteEl = document.getElementById('filtroEscaneoCliente');
  const clienteNombre = clienteEl.value ? clienteEl.options[clienteEl.selectedIndex]?.text : '';

    const BASE = 'border:1px solid #d1d5db;padding:3px 5px;font-size:9pt;';
    const TH = 'background:#0f1750;color:#fff;font-weight:bold;text-align:center;vertical-align:middle;border:1px solid #1e3a5f;padding:4px;font-size:8pt;white-space:nowrap';

    function th(txt, rs = 1, cs = 1) {
        return `<th rowspan="${rs}" colspan="${cs}" style="${TH}">${txt}</th>`;
    }
    function tdSI(val) {
        const v = (val ?? '').toString().toUpperCase().trim();
        if (v === 'SI') return `<td style="${BASE}background:#d1fae5;color:#065f46;font-weight:bold;text-align:center">${val}</td>`;
        if (v === 'NO') return `<td style="${BASE}background:#fee2e2;color:#991b1b;font-weight:bold;text-align:center">${val}</td>`;
        return `<td style="${BASE}text-align:center">${val ?? '-'}</td>`;
    }

    let html = `<html xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns="http://www.w3.org/TR/REC-html40">
      <head><meta charset="UTF-8">
      <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets>
      <x:ExcelWorksheet><x:Name>Pend. Escaneo</x:Name>
      <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
      </x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
      </head><body>
      <table border="0" cellspacing="0" cellpadding="0">
      <tr><td colspan="35" style="font-size:11pt;font-weight:bold;text-align:center;padding:6px;border:none">
          PENDIENTES DE LA BASE DE DATOS GRÁFICA - SUCURSAL ${sucursal} AL ${fecha}
      </td></tr>
       </td></tr>
        ${clienteNombre ? `<tr><td colspan="34" style="font-size:9pt;text-align:center;padding:2px 6px;border:none">Cliente: ${clienteNombre}</td></tr>` : ''}
        <tr>
            ${th('Cód.', 2)}         ${th('Apellidos y Nombres', 2)} ${th('Ingreso SOLMAR', 2)}
          ${th('Cargo', 2)}        ${th('Tipo', 2)}                ${th('Foto', 2)}
          ${th('DNI', 1, 2)}        ${th('Huella D', 1, 2)}          ${th('Firma', 2)}
          ${th('FOTO CONTRO', 1, 2)} ${th('CI SUCAM', 1, 2)}         ${th('Lic. Arma', 2)}
          ${th('Brevete', 1, 2)}    ${th('Cert. Estudios', 2)}      ${th('Cert. Laboral', 2)}
          ${th('Domicilio', 1, 3)}  ${th('Anteceden', 1, 3)}        ${th('Exam. Toxicol.', 2)}
          ${th('Exam. Médico', 2)} ${th('Exam. Psicol.', 2)}       ${th('Cert. Vacuna', 2)}
          ${th('CV01', 2)}         ${th('Fich. Sinto', 2)}         ${th('CUL', 2)}    ${th('DJ Póliza', 2)}
      </tr>
      <tr>
          ${th('Anv')}${th('Rev')}
          ${th('Indi')}${th('Total')}
          ${th('Anv')}${th('Rev')}
          ${th('Anv')}${th('Rev')}
          ${th('Anv')}${th('Rev')}
          ${th('Croquis')}${th('Fachada')}${th('Entorno')}
          ${th('Pol')}${th('Pen')}${th('Jud')}
       
      </tr>`;

    datosEscaneo.forEach((r, i) => {
        const alt = i % 2 !== 0 ? 'background:#f8f9fc;' : '';
        const tdN = (v, left = false) => `<td style="${BASE}${left ? '' : 'text-align:center;'}${alt}">${v ?? ''}</td>`;
        html += `<tr>
              ${tdN(r.CODIGO)}    ${tdN(r.PERSONAL, true)} ${tdN(r.INGRESO_PLANILLA)}
              ${tdN(r.CARGO, true)} ${tdN(r.TIPO)}
              ${tdSI(r.FOTO)}
              ${tdSI(r.DNI1)}       ${tdSI(r.DNI2)}
              ${tdSI(r.HUELLA)}     ${tdSI(r.HUELLAS5)}
              ${tdSI(r.FIRMA)}
              ${tdSI(r.FOTOCONTROL1)} ${tdSI(r.FOTOCONTROL2)}
              ${tdSI(r.CD1)}        ${tdSI(r.CD2)}
              ${tdSI(r.LA)}
              ${tdSI(r.BREVETE1)}   ${tdSI(r.BREVETE2)}
              ${tdSI(r.ESTUDIOS)}   ${tdSI(r.LABORAL)}
              ${tdSI(r.CROQUIS)}    ${tdSI(r.FACHADA)}    ${tdSI(r.ENTORNO)}
              ${tdSI(r.POLICIAL)}   ${tdSI(r.PENAL)}      ${tdSI(r.JUDICIAL)}
               ${tdSI(r.TOXI_EXTERNO)}
              ${tdSI(r.MEDICO)}     ${tdSI(r.PSICO)}      ${tdSI(r.VACUNA)}
              ${tdSI(r.CV01)}       ${tdSI(r.FIASINTO)}
              ${tdSI(r.CUL)}        ${tdSI(r.DJ)}
          </tr>`;
    });

    html += `</table></body></html>`;

    const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'pendientes_escaneo.xls';
    link.click();
});