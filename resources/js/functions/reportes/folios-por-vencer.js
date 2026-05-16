import Swal from 'sweetalert2';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
// import XLSX from 'xlsx-style';

let datos = null;
let tipoFiltro = 'sucursal';
let filtroValue = '';

const datosPrueba = [
    {
        codPersonal: '14991', personal: 'ACUÑA LOPEZ HUGO GERMAN', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/10/2022', cargo: 'AGENTE DE SEGURIDAD', documento: 'EVAL POST', tipo_folio:
            'PRINCIPAL', dias_restantes: '9', fecha_caducidad: '2026-03-27 00:00:00'
    },
    {
        codPersonal: '14991', personal: 'ACUÑA LOPEZ HUGO GERMAN', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/10/2022', cargo: 'AGENTE DE SEGURIDAD', documento: 'EST SEG', tipo_folio:
            'PRINCIPAL', dias_restantes: '15', fecha_caducidad: '2026-04-02 00:00:00'
    },
    {
        codPersonal: '14991', personal: 'ACUÑA LOPEZ HUGO GERMAN', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/10/2022', cargo: 'AGENTE DE SEGURIDAD', documento: 'CAPA', tipo_folio: 'SECUNDARIO',
        dias_restantes: '20', fecha_caducidad: '2026-04-07 00:00:00'
    },
    {
        codPersonal: '14991', personal: 'ACUÑA LOPEZ HUGO GERMAN', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/10/2022', cargo: 'AGENTE DE SEGURIDAD', documento: 'CV', tipo_folio: 'PRINCIPAL',
        dias_restantes: '5', fecha_caducidad: '2026-03-23 00:00:00'
    },
    {
        codPersonal: '16804', personal: 'AGUILAR CAUSHI LUZ KARINA', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '13/01/2025', cargo: 'OPERADOR CCTV', documento: 'EVAL POST', tipo_folio: 'PRINCIPAL',
        dias_restantes: '3', fecha_caducidad: '2026-03-21 00:00:00'
    },
    {
        codPersonal: '16804', personal: 'AGUILAR CAUSHI LUZ KARINA', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '13/01/2025', cargo: 'OPERADOR CCTV', documento: 'POLI', tipo_folio: 'SECUNDARIO',
        dias_restantes: '25', fecha_caducidad: '2026-04-12 00:00:00'
    },
    {
        codPersonal: '05485', personal: 'ALCANTARA CALDERON MANUEL ANTONIO', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/05/2019', cargo: 'AGENTE DE SEGURIDAD', documento: 'EVAL POST',
        tipo_folio: 'PRINCIPAL', dias_restantes: '18', fecha_caducidad: '2026-04-05 00:00:00'
    },
    {
        codPersonal: '05485', personal: 'ALCANTARA CALDERON MANUEL ANTONIO', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/05/2019', cargo: 'AGENTE DE SEGURIDAD', documento: 'EST SEG', tipo_folio:
            'PRINCIPAL', dias_restantes: '7', fecha_caducidad: '2026-03-25 00:00:00'
    },
    {
        codPersonal: '17024', personal: 'ALVARADO FERRER ANTOFELY', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/04/2025', cargo: 'AGENTE DE SEGURIDAD', documento: 'CV', tipo_folio: 'PRINCIPAL',
        dias_restantes: '6', fecha_caducidad: '2026-03-24 00:00:00'
    },
    {
        codPersonal: '17024', personal: 'ALVARADO FERRER ANTOFELY', sucursal: 'HAYDUK', cliente: 'HAYDUK', ingresoSolmar: '01/04/2025', cargo: 'AGENTE DE SEGURIDAD', documento: 'EXA PSICO', tipo_folio:
            'PRINCIPAL', dias_restantes: '10', fecha_caducidad: '2026-03-28 00:00:00'
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

export function init() {
    document.querySelectorAll('input[name="tipoFiltro"]').forEach(r => {
        r.addEventListener('change', function () { tipoFiltro = this.value; filtroValue = ''; });
    });
    document.getElementById('filtroSucursalSelect').addEventListener('change', function () { filtroValue = this.value; });
    document.getElementById('filtroClienteSelect').addEventListener('change', function () { filtroValue = this.value; });

    document.getElementById('btnGenerarFoliosPorVencer').addEventListener('click', generar);
    document.getElementById('btnExportPdfPorVencer').addEventListener('click', exportarPdf);
    document.getElementById('btnExportExcelPorVencer').addEventListener('click', exportarExcel);
}

function buildPorVencer(lista) {
    const groupKey = tipoFiltro === 'cliente' ? 'cliente' : 'sucursal';
    const porGrupo = {}, docsSet = new Set();
    lista.forEach(d => {
        docsSet.add(d.documento);
        if (!porGrupo[d[groupKey]]) porGrupo[d[groupKey]] = {};
        if (!porGrupo[d[groupKey]][d.codPersonal]) {
            porGrupo[d[groupKey]][d.codPersonal] = { codigo: d.codPersonal, personal: d.personal, ingresoSolmar: d.ingresoSolmar || '-', cargo: d.cargo || '-', documentos: {} };
        }
        porGrupo[d[groupKey]][d.codPersonal].documentos[d.documento] = { fecha_caducidad: d.fecha_caducidad };
    });
    return { porGrupo, listaDocumentos: [...docsSet] };
}

function generar() {
    if (!tipoFiltro || !filtroValue) { Swal.fire('Atención', 'Selecciona un filtro antes de generar', 'warning'); return; }
    Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    // DATOS DE PRUEBA — comentar cuando el backend esté listo
    datos = datosPrueba;
    renderTabla(datosPrueba);
    Swal.close();

    // ENDPOINT REAL — descomentar cuando el backend esté listo
    // const endpoint = tipoFiltro === 'cliente' ? `${VITE_URL_APP}/reporte/folios-por-vencer-cliente` : `${VITE_URL_APP}/reporte/folios-por-vencer`;
    // axios.get(endpoint, { params: tipoFiltro === 'cliente' ? { cliente: filtroValue } : { sucursal: filtroValue } })
    //     .then(r => { datos = r.data; renderTabla(r.data); Swal.close(); })
    //     .catch(e => { console.error(e); Swal.fire('Error', 'No se pudo cargar el reporte', 'error'); });
}

function renderTabla(lista) {
    const { porGrupo, listaDocumentos } = buildPorVencer(lista);
    const labelGrupo = tipoFiltro === 'cliente' ? 'Cliente' : 'Sucursal';
    let totalPersonas = 0, html = '';

    Object.entries(porGrupo).forEach(([grupo, personas]) => {
        const arr = Object.values(personas);
        totalPersonas += arr.length;
        html += `<div class="mb-6">
              <div class="reporte-grupo-header">${labelGrupo}: ${grupo}</div>
              <div class="tabla-scroll"><table class="tabla-reporte">
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

function exportarPdf() {
    if (!datos) return;
    const { porGrupo, listaDocumentos } = buildPorVencer(datos);
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
                const celdas = listaDocumentos.map((doc2, di) => {
                    const ci = di + 5, info = p.documentos[doc2];
                    if (!info) { mapaEstado[`${fi}-${ci}`] = { esSI: false }; return { content: '', styles: { fillColor: [214, 69, 69] } }; }
                    const fec = info.fecha_caducidad ? info.fecha_caducidad.split(' ')[0] : '-';
                    mapaEstado[`${fi}-${ci}`] = { esSI: true, fecha: fec };
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
                    const { x, y: cy, width, height } = data.cell, cx = x + width / 2;
                    if (est.esSI) {
                        doc.setFillColor(200, 240, 200); doc.rect(x, cy, width, height, 'F');
                        doc.setFontSize(7.5); doc.setFont(undefined, 'bold'); doc.setTextColor(20, 100, 20);
                        doc.text('SI', cx, cy + height / 2 - 1, { align: 'center' });
                        doc.setFontSize(5.5); doc.setTextColor(0, 0, 0);
                        doc.text(est.fecha, cx, cy + height / 2 + 4, { align: 'center' });
                    } else {
                        doc.setFillColor(214, 69, 69); doc.rect(x, cy, width, height, 'F');
                        doc.setFontSize(7.5); doc.setFont(undefined, 'bold'); doc.setTextColor(255, 255, 255);
                        doc.text('NO', cx, cy + height / 2 + 2.5, { align: 'center' });
                    }
                    doc.setDrawColor(180, 180, 190); doc.setLineWidth(0.2); doc.rect(x, cy, width, height, 'S');
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
}

function exportarExcel() {
    if (!datos) return;
    const { porGrupo, listaDocumentos } = buildPorVencer(datos);
    const labelGrupo = tipoFiltro === 'cliente' ? 'Cliente' : 'Sucursal';
    const fecha = new Date().toLocaleDateString('es-PE');

    const BASE = 'border:1px solid #d1d5db;padding:3px 4px;font-size:8pt;';
    const TH = 'background:#0f1750;color:#fff;font-weight:bold;text-align:center;vertical-align:middle;border:1px solid #1e3a5f;padding:4px;font-size:7.5pt;white-space:nowrap';
    const TH_GRP = 'background:#dce1f5;color:#060a51;font-weight:bold;text-align:left;border:1px solid #b0b8d8;padding:4px;font-size:8.5pt;';

    const th = (txt) => `<th style="${TH}">${txt}</th>`;
    const totalCols = 6 + listaDocumentos.length;

    let html = `<html xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns="http://www.w3.org/TR/REC-html40">
      <head><meta charset="UTF-8">
      <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets>
      <x:ExcelWorksheet><x:Name>Por Vencer</x:Name>
      <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
      </x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
      </head><body>
      <table border="0" cellspacing="0" cellpadding="0">
      <tr><td colspan="${totalCols}" style="font-size:11pt;font-weight:bold;text-align:center;padding:6px;border:none">
          FOLIOS POR VENCER — ${fecha}
      </td></tr>
      <tr>
          ${th('IT')}${th(labelGrupo)}${th('Código')}${th('Apellidos y Nombres')}${th('Ingreso Solmar')}${th('Cargo')}
          ${listaDocumentos.map(d => th(d)).join('')}
      </tr>`;

    Object.entries(porGrupo).forEach(([grupo, personas]) => {
        html += `<tr><td colspan="${totalCols}" style="${TH_GRP}">${labelGrupo}: ${grupo}</td></tr>`;

        Object.values(personas).forEach((p, idx) => {
            const alt = idx % 2 !== 0 ? 'background:#f8f9fc;' : '';
            const tdN = (v, left = false) => `<td style="${BASE}${left ? '' : 'text-align:center;'}${alt}">${v ?? ''}</td>`;

            const celdas = listaDocumentos.map(doc => {
                const info = p.documentos[doc];
                if (info) {
                    const f = info.fecha_caducidad ? info.fecha_caducidad.split(' ')[0] : '-';
                    return `<td style="${BASE}background:#c8f0c8;color:#146414;font-weight:bold;text-align:center">SI<br><span style="font-size:7pt">${f}</span></td>`;
                }
                return `<td style="${BASE}background:#d64545;color:#fff;font-weight:bold;text-align:center">NO</td>`;
            }).join('');

            html += `<tr>
                  ${tdN(idx + 1)}${tdN(grupo)}${tdN(p.codigo)}${tdN(p.personal, true)}${tdN(p.ingresoSolmar)}${tdN(p.cargo, true)}
                  ${celdas}
              </tr>`;
        });
    });

    html += `</table></body></html>`;
    const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'folios_por_vencer.xls';
    link.click();
}

function _descargar(wb, nombre) {
    const out = XLSX.write(wb, { bookType: 'xlsx', bookSST: true, type: 'binary' });
    const buf = new ArrayBuffer(out.length);
    const view = new Uint8Array(buf);
    for (let i = 0; i < out.length; i++) view[i] = out.charCodeAt(i) & 0xFF;
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([buf], { type: 'application/octet-stream' }));
    a.download = nombre;
    a.click();
}




