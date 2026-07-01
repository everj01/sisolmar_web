import axios from 'axios';
import Swal from 'sweetalert2';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
//  import XLSXStyle from 'xlsx-style';

let datos = null;

export function init() {
    document.getElementById('btnGenerarFoliosVigentes').addEventListener('click', generar);
    document.getElementById('btnExportPdfVigentes').addEventListener('click', exportarPdf);
    document.getElementById('btnExportExcelVigentes').addEventListener('click', exportarExcel);
}

function generar() {
    const tipo = document.getElementById('filtroTipoFolio').value;
    const prioridad = document.getElementById('filtroPrioridad').value;

    Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    axios.get(`${VITE_URL_APP}/api/get-folios`).then(response => {
        let result = response.data.filter(f => f.habilitado == '1');
        if (tipo) result = result.filter(f => f.tipoFolio === tipo);
        if (prioridad) result = result.filter(f => f.prioridad === prioridad);

        if (!result.length) {
            Swal.fire({ icon: 'warning', title: 'Sin resultados', text: 'No hay folios con esos filtros' });
            return;
        }
        datos = result;
        renderTabla(datos);
        Swal.close();
    }).catch(() => Swal.fire('Error', 'No se pudo cargar el reporte', 'error'));
}

function renderTabla(lista) {
    document.getElementById('totalFoliosVigentes').textContent = `${lista.length} folios`;
    document.getElementById('tablaFoliosVigentes').innerHTML = `
          <div class="tabla-scroll">
              <table class="tabla-reporte">
                  <thead><tr>
                      <th style="width:40px">N°</th>
                      <th style="text-align:left">Nombre del Folio</th>
                      <th>Tipo</th><th>Prioridad</th><th>Vencimiento</th>
                  </tr></thead>
                  <tbody>
                      ${lista.map((f, i) => `<tr>
                          <td class="tc">${i + 1}</td>
                          <td>${f.nombre}</td>
                          <td class="tc">${f.tipoFolio}</td>
                          <td class="tc">${f.prioridad}</td>
                          <td class="tc">${f.periodo || 'Sin vencimiento'}</td>
                      </tr>`).join('')}
                  </tbody>
              </table>
          </div>`;
    document.getElementById('resultadosFoliosVigentes').classList.remove('hidden');
}

function exportarPdf() {
    if (!datos) return;
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
            body: datos.map((f, i) => [i + 1, f.nombre, f.tipoFolio, f.prioridad, f.periodo || 'Sin vencimiento']),
            styles: { fontSize: 8, cellPadding: 2, lineColor: [189, 195, 199], lineWidth: 0.1 },
            headStyles: { fillColor: [6, 10, 81], textColor: [255, 255, 255], fontStyle: 'bold', halign: 'center', fontSize: 9, cellPadding: 3 },
            alternateRowStyles: { fillColor: [250, 250, 250] },
            columnStyles: {
                0: { halign: 'center', cellWidth: 12 }, 1: { cellWidth: 70 },
                2: { halign: 'center', cellWidth: 30 }, 3: { halign: 'center', cellWidth: 30 },
                4: { halign: 'center', cellWidth: 38 }
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
}

function exportarExcel() {
    if (!datos) return;
    const fecha = new Date().toLocaleDateString('es-PE');

    const BASE = 'border:1px solid #d1d5db;padding:3px 6px;font-size:9pt;';
    const TH = 'background:#060a51;color:#fff;font-weight:bold;text-align:center;vertical-align:middle;border:1px solid #1e3a5f;padding:4px;font-size:8pt;white-space:nowrap';

    const th = (txt) => `<th style="${TH}">${txt}</th>`;

    let html = `<html xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns="http://www.w3.org/TR/REC-html40">
      <head><meta charset="UTF-8">
      <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets>
      <x:ExcelWorksheet><x:Name>Folios Vigentes</x:Name>
      <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
      </x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
      </head><body>
      <table border="0" cellspacing="0" cellpadding="0">
      <tr><td colspan="5" style="font-size:11pt;font-weight:bold;text-align:center;padding:6px;border:none">
          LISTADO GENERAL DE FOLIOS VIGENTES — ${fecha}
      </td></tr>
      <tr>${th('N°')}${th('Nombre del Folio')}${th('Tipo')}${th('Prioridad')}${th('Vencimiento')}</tr>`;

    datos.forEach((f, i) => {
        const alt = i % 2 !== 0 ? 'background:#f5f5f5;' : '';
        const tdN = (v, left = false) => `<td style="${BASE}${left ? '' : 'text-align:center;'}${alt}">${v ?? ''}</td>`;
        html += `<tr>
              ${tdN(i + 1)}
              ${tdN(f.nombre, true)}
              ${tdN(f.tipoFolio)}
              ${tdN(f.prioridad)}
              ${tdN(f.periodo || 'Sin vencimiento')}
          </tr>`;
    });

    html += `</table></body></html>`;
    const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'folios_vigentes.xls';
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