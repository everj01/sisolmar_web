import axios from 'axios';
import Swal from 'sweetalert2';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

let datos = null;

export function init() {
    document.getElementById('btnGenerarCarnet').addEventListener('click', generar);
    document.getElementById('btnExportPdfCarnet').addEventListener('click', exportarPdf);
    document.getElementById('btnExportExcelCarnet').addEventListener('click', exportarExcel);
}

function getSelText(id) {
    const s = document.getElementById(id);
    return s?.options[s.selectedIndex]?.text ?? '';
}

function formatFecha(f) {
    if (!f) return '-';
    return f.toString().split('T')[0].split(' ')[0];
}

function buildPorSucursal(lista) {
    const grupos = {};
    lista.forEach(r => {
        const k = r.SUCURSAL ?? 'SIN SUCURSAL';
        if (!grupos[k]) grupos[k] = [];
        grupos[k].push(r);
    });

    Object.values(grupos).forEach(g => g.sort((a, b) => (a.PERSONAL ?? '').localeCompare(b.PERSONAL ?? '', 'es')));

    return grupos;
}


function generar() {
    const categoria = document.getElementById('filtroCarnetCategoria').value;
    if (!categoria) {
        Swal.fire({ icon: 'warning', title: 'Categoría requerida', text: 'Selecciona una categoría para generar el reporte.' });
        return;
    }
    const sucursal = document.getElementById('filtroCarnetSucursal').value;
    const tipo_pers = document.getElementById('filtroCarnetTipoPers').value;
    const vigencia = document.getElementById('filtroCarnetVigencia').value;
    const estado = document.getElementById('filtroCarnetEstado').value;

    Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    axios.get(`${VITE_URL_APP}/api/reporte/carnet`, { params: { sucursal, tipo_pers, vigencia, estado, categoria } })
        .then(r => {
            if (!r.data.length) {
                Swal.fire({ icon: 'warning', title: 'Sin resultados', text: 'No se encontraron carnets con los filtros indicados.' });
                return;
            }
            datos = r.data;
            renderTabla(datos);
            Swal.close();
        })
        .catch(() => Swal.fire('Error', 'No se pudo cargar el reporte', 'error'));
}

function celdaSiNo(val) {
    const v = (val ?? '').toString().toUpperCase().trim();
    if (v === 'SI') return `<td class="celda-si">SI</td>`;
    if (v === 'NO') return `<td class="celda-no">NO</td>`;
    return `<td class="tc">${val ?? '-'}</td>`;
}

function celdaEstado(v) {
    const isActivo = v === '1' || (v ?? '').toString().toUpperCase() === 'ACTIVO';
    return isActivo
        ? `<td class="celda-si">ACTIVO</td>`
        : `<td class="celda-no">INACTIVO</td>`;
}

function renderTabla(lista) {
    const grupos = buildPorSucursal(lista);
    const catNombre = getSelText('filtroCarnetCategoria');
    const sucNombre = getSelText('filtroCarnetSucursal');
    const sucLabel = sucNombre === 'Todas' ? 'TODAS LAS SUCURSALES' : `SUCURSAL ${sucNombre}`;
    let totalPersonas = 0, html = '';

    Object.entries(grupos).forEach(([tipo, personas]) => {
        totalPersonas += personas.length;
        html += `<div class="mb-6">
              <div class="reporte-grupo-header">${tipo}</div>
              <div class="tabla-scroll">
              <table class="tabla-reporte" style="font-size:0.72rem">
                  <thead>
                      <tr>
                          <th rowspan="2" style="width:28px">It</th>
                          <th colspan="4">Datos del Personal</th>
                          <th rowspan="2" style="width:36px">Vig.</th>
                          <th colspan="6">Datos del Carnet – ${catNombre}</th>
                          <th colspan="2">Escaneo</th>
                      </tr>
                      <tr>
                          <th style="width:55px">Cód.</th>
                          <th style="min-width:160px;text-align:left">Apellidos y Nombres</th>
                          <th style="width:80px">DNI</th>
                          <th style="min-width:100px;text-align:left">Cargo</th>
                          <th style="width:90px">Nro Carnet</th>
                          <th style="min-width:90px">Matrícula</th>
                          <th style="width:80px">Emisión</th>
                          <th style="width:80px">Caduca</th>
                          <th style="width:72px">Estado</th>
                          <th style="min-width:100px;text-align:left">Observación</th>
                          <th style="width:36px">Anv.</th>
                          <th style="width:36px">Rev.</th>
                      </tr>
                  </thead>
                  <tbody>
                  ${personas.map((r, i) => `<tr>
                      <td class="tc">${i + 1}</td>
                      <td class="tc">${r.CODIGO}</td>
                      <td>${r.PERSONAL}</td>
                      <td class="tc">${r.DNI}</td>
                      <td>${r.CARGO ?? '-'}</td>
                      ${celdaSiNo(r.VIGENCIA)}
                      <td class="tc">${r.NRO_CARNET ?? '-'}</td>
                      <td class="tc">${r.MATRICULA ?? '-'}</td>
                      <td class="tc">${formatFecha(r.FEC_EMISION)}</td>
                      <td class="tc">${formatFecha(r.FEC_CADUCA)}</td>
                      ${celdaEstado(r.ESTADO)}
                      <td>${r.OBSERVACION ?? ''}</td>
                      ${celdaSiNo(r.ANVERSO)}
                      ${celdaSiNo(r.REVERSO)}
                  </tr>`).join('')}
                  </tbody>
              </table>
              </div>
          </div>`;
    });

    const sucursalLabel = sucNombre === 'Todas' ? 'TODAS LAS SUCURSALES' : `SUCURSAL ${sucNombre}`;
    document.getElementById('totalCarnet').textContent = `${totalPersonas} registro(s) · ${Object.keys(grupos).length} grupo(s)`;
    document.getElementById('tablaCarnet').innerHTML = `
          <div class="mb-3 text-center font-semibold text-default-700" style="font-size:0.9rem">
              CARNET DE ${catNombre.toUpperCase()} — ${sucursalLabel}
          </div>${html}`;
    document.getElementById('resultadosCarnet').classList.remove('hidden');
}

function exportarPdf() {
    if (!datos) return;
    const grupos = buildPorSucursal(datos);
    const catNombre = getSelText('filtroCarnetCategoria').toUpperCase();
    const sucNombre = getSelText('filtroCarnetSucursal');
    const sucLabel = sucNombre === 'Todas' ? 'TODAS LAS SUCURSALES' : `SUCURSAL ${sucNombre}`;
    const doc = new jsPDF({ orientation: 'landscape', format: 'a3' });
    const PW = doc.internal.pageSize.getWidth(), PH = doc.internal.pageSize.getHeight();
    const M = 5;
    const fecha = new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });
    const hora = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });

    function pdfSiNo(val) {
        const v = (val ?? '').toString().toUpperCase().trim();
        if (v === 'SI') return { content: 'SI', styles: { fillColor: [209, 250, 229], textColor: [6, 95, 70], fontStyle: 'bold', halign: 'center', fontSize: 5.5 } };
        if (v === 'NO') return { content: 'NO', styles: { fillColor: [254, 226, 226], textColor: [153, 27, 27], fontStyle: 'bold', halign: 'center', fontSize: 5.5 } };
        return { content: val ?? '-', styles: { halign: 'center', fontSize: 5.5 } };
    }
    function pdfEstado(v) {
        const isActivo = v === '1' || (v ?? '').toString().toUpperCase() === 'ACTIVO';
        return isActivo
            ? { content: 'ACTIVO', styles: { fillColor: [209, 250, 229], textColor: [6, 95, 70], fontStyle: 'bold', halign: 'center', fontSize: 5.5 } }
            : { content: 'INACTIVO', styles: { fillColor: [254, 226, 226], textColor: [153, 27, 27], fontStyle: 'bold', halign: 'center', fontSize: 5.5 } };
    }

    const head = [[
        { content: 'It', rowSpan: 2 },
        { content: 'Datos del Personal', colSpan: 4 },
        { content: 'Vig.', rowSpan: 2 },
        { content: `Datos del Carnet – ${catNombre}`, colSpan: 6 },
        { content: 'Escaneo', colSpan: 2 },
    ], [
        'Cód.', 'Apellidos y Nombres', 'DNI', 'Cargo',
        'Nro Carnet', 'Matrícula', 'Emisión', 'Caduca', 'Estado', 'Observación',
        'Anv.', 'Rev.',
    ]];

    let primera = true;
    Object.entries(grupos).forEach(([tipo, personas]) => {
        if (!primera) doc.addPage();
        primera = false;
        let y = 8;

        doc.setFontSize(9); doc.setFont(undefined, 'bold');
        doc.setFillColor(6, 10, 81); doc.rect(M, y, PW - M * 2, 7, 'F');
        doc.setTextColor(255, 255, 255);
        doc.text(`CARNET DE ${catNombre} — ${sucLabel}`, PW / 2, y + 5, { align: 'center' });
        doc.setTextColor(0, 0, 0);
        y += 10;

        doc.setFontSize(8); doc.setFont(undefined, 'bold');
        doc.setFillColor(220, 225, 245); doc.rect(M, y, PW - M * 2, 6, 'F');
        doc.setTextColor(6, 10, 81);
        doc.text(tipo, M + 2, y + 4.5);
        doc.setTextColor(0, 0, 0);
        y += 8;

        const body = personas.map((r, i) => [
            { content: i + 1, styles: { halign: 'center', fontSize: 5.5 } },
            { content: r.CODIGO, styles: { halign: 'center', fontSize: 5.5 } },
            { content: r.PERSONAL, styles: { fontSize: 5.5 } },
            { content: r.DNI, styles: { halign: 'center', fontSize: 5.5 } },
            { content: r.CARGO ?? '-', styles: { fontSize: 5.5 } },
            pdfSiNo(r.VIGENCIA),
            { content: r.NRO_CARNET ?? '-', styles: { halign: 'center', fontSize: 5.5 } },
            { content: r.MATRICULA ?? '-', styles: { halign: 'center', fontSize: 5.5 } },
            { content: formatFecha(r.FEC_EMISION), styles: { halign: 'center', fontSize: 5.5 } },
            { content: formatFecha(r.FEC_CADUCA), styles: { halign: 'center', fontSize: 5.5 } },
            pdfEstado(r.ESTADO),
            { content: r.OBSERVACION ?? '', styles: { fontSize: 5 } },
            pdfSiNo(r.ANVERSO),
            pdfSiNo(r.REVERSO),
        ]);

        const aNom = 32, aCar = 22, aObs = 18;
        const aFija = 6 + 9 + aNom + 18 + aCar + 7 + aObs;
        const aVar = Math.max(7, (PW - M * 2 - aFija) / 8);
        const cs = {
            0: { cellWidth: 6 }, 1: { cellWidth: 9 }, 2: { cellWidth: aNom }, 3: { cellWidth: 18 }, 4: { cellWidth: aCar },
            5: { cellWidth: 7, halign: 'center' },
            6: { cellWidth: aVar, halign: 'center' }, 7: { cellWidth: aVar, halign: 'center' },
            8: { cellWidth: aVar, halign: 'center' }, 9: { cellWidth: aVar, halign: 'center' },
            10: { cellWidth: aVar, halign: 'center' }, 11: { cellWidth: aObs },
            12: { cellWidth: aVar, halign: 'center' }, 13: { cellWidth: aVar, halign: 'center' },
        };

        autoTable(doc, {
            startY: y, head, body,
            styles: { fontSize: 5.5, cellPadding: { top: 1.5, bottom: 1.5, left: 1, right: 1 }, lineColor: [200, 200, 210], lineWidth: 0.15, overflow: 'ellipsize', minCellHeight: 7 },
            headStyles: { fillColor: [15, 23, 80], textColor: [255, 255, 255], fontStyle: 'bold', halign: 'center', fontSize: 5.5, cellPadding: { top: 2, bottom: 2, left: 1, right: 1 }, valign: 'middle' },
            alternateRowStyles: { fillColor: [248, 249, 252] },
            columnStyles: cs, margin: { left: M, right: M }, theme: 'grid',
        });
    });

    const pc = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pc; i++) {
        doc.setPage(i);
        doc.setDrawColor(210, 215, 230); doc.setLineWidth(0.4); doc.line(M, PH - 10, PW - M, PH - 10);
        doc.setFontSize(6); doc.setTextColor(150, 150, 160); doc.setFont(undefined, 'normal');
        doc.text('Sistema de Gestión de Recursos Humanos', M + 2, PH - 5);
        doc.text(`Generado el ${fecha} a las ${hora}`, PW / 2, PH - 5, { align: 'center' });
        doc.setFont(undefined, 'bold'); doc.setTextColor(80, 80, 100);
        doc.text(`Pág. ${i} de ${pc}`, PW - M - 2, PH - 5, { align: 'right' });
    }

    const link = document.createElement('a');
    link.href = URL.createObjectURL(doc.output('blob'));
    link.download = `carnet_${catNombre.replace(/ /g, '_')}.pdf`;
    link.click();
}

function exportarExcel() {
    if (!datos) return;
    const grupos = buildPorSucursal(datos);
    const catNombre = getSelText('filtroCarnetCategoria').toUpperCase();
    const sucNombre = getSelText('filtroCarnetSucursal');
    const sucLabel = sucNombre === 'Todas' ? 'TODAS LAS SUCURSALES' : `SUCURSAL ${sucNombre}`;
    const fecha = new Date().toLocaleDateString('es-PE');

    const BASE = 'border:1px solid #d1d5db;padding:3px 5px;font-size:8.5pt;';
    const TH = 'background:#0f1750;color:#fff;font-weight:bold;text-align:center;vertical-align:middle;border:1px solid #1e3a5f;padding:4px;font-size:8pt;white-space:nowrap';
    const TH_GRP = 'background:#dce1f5;color:#060a51;font-weight:bold;text-align:left;border:1px solid #b0b8d8;padding:4px;font-size:8.5pt;';

    function th(txt, rs = 1, cs = 1) { return `<th rowspan="${rs}" colspan="${cs}" style="${TH}">${txt}</th>`; }
    function tdSiNo(val) {
        const v = (val ?? '').toString().toUpperCase().trim();
        if (v === 'SI') return `<td style="${BASE}background:#d1fae5;color:#065f46;font-weight:bold;text-align:center">SI</td>`;
        if (v === 'NO') return `<td style="${BASE}background:#fee2e2;color:#991b1b;font-weight:bold;text-align:center">NO</td>`;
        return `<td style="${BASE}text-align:center">${val ?? '-'}</td>`;
    }
    function tdEstado(v) {
        const isActivo = v === '1' || (v ?? '').toString().toUpperCase() === 'ACTIVO';
        const label = isActivo ? 'ACTIVO' : 'INACTIVO';
        const bg = isActivo ? '#d1fae5' : '#fee2e2';
        const color = isActivo ? '#065f46' : '#991b1b';
        return `<td style="${BASE}background:${bg};color:${color};font-weight:bold;text-align:center">${label}</td>`;
    }

    let html = `<html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:excel"
            xmlns="http://www.w3.org/TR/REC-html40">
        <head><meta charset="UTF-8">
        <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets>
        <x:ExcelWorksheet><x:Name>Carnet</x:Name>
        <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
        </x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
        </head><body>
        <table border="0" cellspacing="0" cellpadding="0">
        <tr><td colspan="14" style="font-size:11pt;font-weight:bold;text-align:center;padding:6px;border:none">
            CARNET DE ${catNombre} — ${sucLabel} — ${fecha}
        </td></tr>
        <tr>
            ${th('It', 2)} ${th('Datos del Personal', 1, 4)} ${th('Vig.', 2)}
            ${th(`Datos del Carnet – ${catNombre}`, 1, 6)} ${th('Escaneo', 1, 2)}
        </tr>
        <tr>
            ${th('Cód.')}${th('Apellidos y Nombres')}${th('DNI')}${th('Cargo')}
            ${th('Nro Carnet')}${th('Matrícula')}${th('Emisión')}${th('Caduca')}${th('Estado')}${th('Observación')}
            ${th('Anv.')}${th('Rev.')}
        </tr>`;

    Object.entries(grupos).forEach(([tipo, personas]) => {
        html += `<tr><td colspan="14" style="${TH_GRP}">${tipo}</td></tr>`;
        personas.forEach((r, i) => {
            const alt = i % 2 !== 0 ? 'background:#f8f9fc;' : '';
            const tdN = (v, left = false) => `<td style="${BASE}${left ? '' : 'text-align:center;'}${alt}">${v ?? ''}</td>`;
            html += `<tr>
                  ${tdN(i + 1)} ${tdN(r.CODIGO)} ${tdN(r.PERSONAL, true)} ${tdN(r.DNI)} ${tdN(r.CARGO ?? '-', true)}
                  ${tdSiNo(r.VIGENCIA)}
                  ${tdN(r.NRO_CARNET ?? '-')} ${tdN(r.MATRICULA ?? '-')}
                  ${tdN(formatFecha(r.FEC_EMISION))} ${tdN(formatFecha(r.FEC_CADUCA))}
                  ${tdEstado(r.ESTADO)} ${tdN(r.OBSERVACION ?? '', true)}
                  ${tdSiNo(r.ANVERSO)} ${tdSiNo(r.REVERSO)}
              </tr>`;
        });
    });

    html += `</table></body></html>`;
    const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `carnet_${catNombre.replace(/ /g, '_')}.xls`;
    link.click();
}