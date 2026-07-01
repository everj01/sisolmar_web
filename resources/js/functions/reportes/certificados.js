import axios from 'axios';
import Swal from 'sweetalert2';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

let datos = null;

export function init() {
    new TomSelect('#filtroCertSucursal', { placeholder: '— Seleccionar —', allowEmptyOption: true });
    new TomSelect('#filtroCertTipoPers', { placeholder: '— Seleccionar —', allowEmptyOption: true });
    new TomSelect('#filtroCertCertificado', { placeholder: '— Seleccionar —', allowEmptyOption: true, plugins: ['dropdown_input'] });
    new TomSelect('#filtroCertVigencia', { allowEmptyOption: true });
    new TomSelect('#filtroCertEstado', { allowEmptyOption: true });

    document.getElementById('btnGenerarCertificados').addEventListener('click', generar);
    document.getElementById('btnExportPdfCertificados').addEventListener('click', exportarPdf);
    document.getElementById('btnExportExcelCertificados').addEventListener('click', exportarExcel);
}

function getSelText(id) {
    const s = document.getElementById(id);
    return s?.options[s.selectedIndex]?.text ?? '';
}

function fmtFecha(val) {
    if (!val) return '';
    const d = new Date(val);
    return isNaN(d) ? val : d.toLocaleDateString('es-PE');
}

function celdaEsc(val) {
    if (val === 1 || val === '1') return `<td class="celda-si">SI</td>`;
    if (val === 0 || val === '0') return `<td class="celda-no">NO</td>`;
    return `<td class="tc">—</td>`;
}

function generar() {
    const sucursal = document.getElementById('filtroCertSucursal').value;
    const tipoPers = document.getElementById('filtroCertTipoPers').value;
    const certificado = document.getElementById('filtroCertCertificado').value;
    const vigencia = document.getElementById('filtroCertVigencia').value;
    const estado = document.getElementById('filtroCertEstado').value;
    const fechaVenc = document.getElementById('filtroCertFechaVenc').value;

    if (!sucursal) { Swal.fire({ icon: 'warning', title: 'Requerido', text: 'Selecciona una sucursal.' }); return; }
    if (!certificado) { Swal.fire({ icon: 'warning', title: 'Requerido', text: 'Selecciona un certificado.' }); return; }
    if (!fechaVenc) { Swal.fire({ icon: 'warning', title: 'Requerido', text: 'Ingresa la fecha de vencimiento.' }); return; }

    Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    axios.get(`${VITE_URL_APP}/api/reporte/certificados`, {
        params: { sucursal, tipo_pers: tipoPers, certificado, vigencia, estado, fecha_venc: fechaVenc }
    })
        .then(r => {
            if (!r.data.length) {
                Swal.fire({ icon: 'warning', title: 'Sin resultados', text: 'No se encontraron registros.' });
                return;
            }
            datos = r.data;
            renderTabla(datos);
            Swal.close();
        })
        .catch(() => Swal.fire('Error', 'No se pudo generar el reporte.', 'error'));
}

 function renderTabla(lista) {
      const sucNombre  = getSelText('filtroCertSucursal');
      const certNombre = getSelText('filtroCertCertificado');

      const grupos = {};
      lista.forEach(r => {
          const tipo = r.TIPO || 'SIN TIPO';
          if (!grupos[tipo]) grupos[tipo] = [];
          grupos[tipo].push(r);
      });

      const thead = `
          <th style="width:28px">It</th>
          <th style="width:50px">Cód.</th>
          <th style="text-align:left;min-width:180px">Apellidos y Nombres</th>
          <th style="width:82px">Ingreso</th>
          <th style="text-align:left;min-width:140px">Cargo</th>
          <th style="width:36px">Vig.</th>
          <th style="width:90px">Resultado</th>
          <th style="width:80px">Emisión</th>
          <th style="width:80px">Caduca</th>
          <th style="width:65px">Estado</th>
          <th style="text-align:left;min-width:160px">Observación</th>
          <th style="width:46px">Esc.</th>`;

      let html = `
          <div class="mb-1 text-center font-bold text-default-900" style="font-size:0.95rem;text-decoration:underline;text-transform:uppercase">
              ${certNombre}
          </div>
          <div class="mb-3 font-bold text-default-800" style="font-size:0.85rem">
              SUCURSAL ${sucNombre.toUpperCase()}
          </div>`;

      Object.entries(grupos).forEach(([tipo, rows]) => {
          html += `<div class="font-bold text-default-700 mt-4 mb-1 ml-1" style="font-size:0.8rem">${tipo}</div>`;
          html += `<div class="tabla-scroll mb-5">
              <table class="tabla-reporte" style="font-size:0.72rem">
                  <thead><tr>${thead}</tr></thead>
                  <tbody>`;
          rows.forEach((r, i) => {
              const esInactivo = !r.ESTADO || r.ESTADO === 'NO';
              const estadoLabel = r.ESTADO === 'SI' ? 'ACTIVO' : r.ESTADO === 'NO' ? 'INACTIVO' : (r.ESTADO || '');
              const estadoStyle = esInactivo && r.ESTADO ? 'style="color:#dc2626;font-weight:bold"' : '';
              html += `<tr>
                  <td class="tc">${i + 1}</td>
                  <td class="tc">${r.CODIGO ?? ''}</td>
                  <td>${r.PERSONAL ?? ''}</td>
                  <td class="tc">${fmtFecha(r.INGRESO)}</td>
                  <td>${r.CARGO ?? ''}</td>
                  <td class="tc">${r.VIGENCIA ?? ''}</td>
                  <td class="tc">${r.RESULTADO ?? ''}</td>
                  <td class="tc">${fmtFecha(r.FEC_EMISION)}</td>
                  <td class="tc">${fmtFecha(r.FEC_CADUCA)}</td>
                  <td class="tc" ${estadoStyle}>${estadoLabel}</td>
                  <td>${r.OBSERVACION ?? ''}</td>
                  ${celdaEsc(r.ESCANEO)}
              </tr>`;
          });
          html += `</tbody></table></div>`;
      });

      document.getElementById('totalCertificados').textContent = `${lista.length} registro(s)`;
      document.getElementById('tablaCertificados').innerHTML = html;
      document.getElementById('resultadosCertificados').classList.remove('hidden');
  }

 function exportarPdf() {
      if (!datos) return;
      const sucNombre  = getSelText('filtroCertSucursal');
      const certNombre = getSelText('filtroCertCertificado');
      const fechaLarga = new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });
      const fechaCorta = new Date().toLocaleDateString('es-PE');
      const hora       = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });

      const doc = new jsPDF({ orientation: 'landscape', format: 'a3' });
      const PW  = doc.internal.pageSize.getWidth();
      const PH  = doc.internal.pageSize.getHeight();
      const M   = 8;

      // Título
      let y = 12;
      doc.setFontSize(11); doc.setFont(undefined, 'bold');
      doc.text(certNombre.toUpperCase(), PW / 2, y, { align: 'center' });
      const tw = doc.getTextWidth(certNombre.toUpperCase());
      doc.setLineWidth(0.3);
      doc.line(PW / 2 - tw / 2, y + 1.2, PW / 2 + tw / 2, y + 1.2);
      y += 7;

      // Sucursal
      doc.setFontSize(9);
      doc.text(`SUCURSAL ${sucNombre.toUpperCase()}`, M, y);
      y += 7;

      // Agrupar
      const grupos = {};
      datos.forEach(r => {
          const tipo = r.TIPO || 'SIN TIPO';
          if (!grupos[tipo]) grupos[tipo] = [];
          grupos[tipo].push(r);
      });

      const c = (v, left = false) => ({ content: v ?? '', styles: { halign: left ? 'left' : 'center', fontSize: 5.5 } });

      Object.entries(grupos).forEach(([tipo, rows]) => {
          doc.setFontSize(8); doc.setFont(undefined, 'bold'); doc.setTextColor(0);
          doc.text(tipo, M + 2, y);
          y += 5;

          const body = rows.map((r, i) => {
              const esInactivo = !r.ESTADO || r.ESTADO === 'NO';
              const estadoLabel = r.ESTADO === 'SI' ? 'ACTIVO' : r.ESTADO === 'NO' ? 'INACTIVO' : (r.ESTADO || '');
              const estadoCelda = (esInactivo && r.ESTADO)
                  ? { content: estadoLabel, styles: { textColor: [220,38,38], fontStyle: 'bold', halign: 'center', fontSize: 5.5 } }
                  : c(estadoLabel);
              const escCelda = (r.ESCANEO === 1 || r.ESCANEO === '1')
                  ? { content: 'SI', styles: { fillColor: [209,250,229], textColor: [6,95,70],   fontStyle: 'bold', halign: 'center', fontSize: 5.5 } }
                  : (r.ESCANEO === 0 || r.ESCANEO === '0')
                  ? { content: 'NO', styles: { fillColor: [254,226,226], textColor: [153,27,27], fontStyle: 'bold', halign: 'center', fontSize: 5.5 } }
                  : c('—');
              return [
                  c(i + 1), c(r.CODIGO), c(r.PERSONAL, true), c(fmtFecha(r.INGRESO)),
                  c(r.CARGO, true), c(r.VIGENCIA), c(r.RESULTADO),
                  c(fmtFecha(r.FEC_EMISION)), c(fmtFecha(r.FEC_CADUCA)),
                  estadoCelda, c(r.OBSERVACION, true), escCelda,
              ];
          });

          autoTable(doc, {
              startY: y,
              head: [['It','Cód.','Apellidos y Nombres','Ingreso','Cargo','Vig.','Resultado','Emisión','Caduca','Estado','Observación','Esc.']],
              body,
              styles: { fontSize: 5.5, cellPadding: {top:1.5,bottom:1.5,left:1,right:1}, lineColor:[200,200,210], lineWidth:0.15, overflow:'ellipsize', minCellHeight:7 },
              headStyles: { fillColor:[15,23,80], textColor:[255,255,255], fontStyle:'bold', halign:'center', fontSize:5.5, cellPadding:{top:2,bottom:2,left:1,right:1}, valign:'middle' },
              alternateRowStyles: { fillColor:[248,249,252] },
              columnStyles: {
                  0:{cellWidth:6},  1:{cellWidth:9},  2:{cellWidth:30}, 3:{cellWidth:15},
                  4:{cellWidth:28}, 5:{cellWidth:7},  6:{cellWidth:18}, 7:{cellWidth:15},
                  8:{cellWidth:15}, 9:{cellWidth:13}, 10:{cellWidth:28},11:{cellWidth:8},
              },
              margin: { left: M, right: M }, theme: 'grid',
          });

          y = doc.lastAutoTable.finalY + 9;
      });

      // Pie de página
      const pc = doc.internal.getNumberOfPages();
      for (let i = 1; i <= pc; i++) {
          doc.setPage(i);
          doc.setFontSize(7); doc.setFont(undefined, 'normal'); doc.setTextColor(100,100,100);
          doc.text(`Pág. ${i} de ${pc}  ${fechaCorta}  ${hora}`, PW / 2, PH - 6, { align: 'center' });
      }

      doc.save(`certificados_${sucNombre.replace(/ /g,'_').toLowerCase()}.pdf`);
  }

function exportarExcel() {
      if (!datos) return;
      const sucNombre  = getSelText('filtroCertSucursal');
      const certNombre = getSelText('filtroCertCertificado');
      const fecha      = new Date().toLocaleDateString('es-PE');

      const BASE = 'border:1px solid #d1d5db;padding:3px 5px;font-size:8.5pt;';
      const TH   = 'background:#0f1750;color:#fff;font-weight:bold;text-align:center;vertical-align:middle;border:1px solid #1e3a5f;padding:4px;font-size:8pt;white-space:nowrap';
      const th   = t => `<th style="${TH}">${t}</th>`;
      const cabeceras = `${th('It')}${th('Cód.')}${th('Apellidos y Nombres')}${th('Ingreso')}${th('Cargo')}${th('Vig.')}${th('Resultado')}${th('Emisión')}${th('Caduca')}${th('Estado')}${th('Observación')}${th('Esc.')}`;

      const grupos = {};
      datos.forEach(r => {
          const tipo = r.TIPO || 'SIN TIPO';
          if (!grupos[tipo]) grupos[tipo] = [];
          grupos[tipo].push(r);
      });

      let html = `<html xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns="http://www.w3.org/TR/REC-html40">
          <head><meta charset="UTF-8">
          <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets>
          <x:ExcelWorksheet><x:Name>Certificados</x:Name>
          <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
          </x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
          </head><body>
          <table border="0" cellspacing="0" cellpadding="0">
          <tr><td colspan="12" style="font-size:13pt;font-weight:bold;text-align:center;text-decoration:underline;padding:8px;border:none">${certNombre.toUpperCase()}</td></tr>
          <tr><td colspan="12" style="font-size:10pt;font-weight:bold;padding:4px 3px;border:none">SUCURSAL ${sucNombre.toUpperCase()}</td></tr>`;

      Object.entries(grupos).forEach(([tipo, rows]) => {
          html += `<tr><td colspan="12" style="font-size:9.5pt;font-weight:bold;padding:5px 6px;border:none">${tipo}</td></tr>`;
          html += `<tr>${cabeceras}</tr>`;
          rows.forEach((r, i) => {
              const alt = i % 2 !== 0 ? 'background:#f8f9fc;' : '';
              const tdC = (v) => `<td style="${BASE}text-align:center;${alt}">${v ?? ''}</td>`;
              const tdL = (v) => `<td style="${BASE}${alt}">${v ?? ''}</td>`;
              const esInactivo = !r.ESTADO || r.ESTADO === 'NO';
              const estadoLabel = r.ESTADO === 'SI' ? 'ACTIVO' : r.ESTADO === 'NO' ? 'INACTIVO' : (r.ESTADO || '');
              const tdEstado = (esInactivo && r.ESTADO)
                  ? `<td style="${BASE}text-align:center;color:#dc2626;font-weight:bold;${alt}">${estadoLabel}</td>`
                  : tdC(estadoLabel);
              const escLabel = r.ESCANEO === 1 || r.ESCANEO === '1' ? 'SI' : r.ESCANEO === 0 || r.ESCANEO === '0' ? 'NO' : '—';
              const escColor = r.ESCANEO === 0 || r.ESCANEO === '0' ? 'color:#dc2626;font-weight:bold;' : r.ESCANEO === 1 || r.ESCANEO === '1' ? 'color:#16a34a;font-weight:bold;' : '';
              const tdEsc = `<td style="${BASE}text-align:center;${escColor}${alt}">${escLabel}</td>`;
              html += `<tr>
                  ${tdC(i+1)} ${tdC(r.CODIGO)} ${tdL(r.PERSONAL)} ${tdC(fmtFecha(r.INGRESO))}
                  ${tdL(r.CARGO)} ${tdC(r.VIGENCIA)} ${tdC(r.RESULTADO)}
                  ${tdC(fmtFecha(r.FEC_EMISION))} ${tdC(fmtFecha(r.FEC_CADUCA))}
                  ${tdEstado} ${tdL(r.OBSERVACION)} ${tdEsc}
              </tr>`;
          });
          html += `<tr><td colspan="12" style="border:none;padding:5px"></td></tr>`;
      });

      html += `</table></body></html>`;
      const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8' });
      const link = document.createElement('a');
      link.href     = URL.createObjectURL(blob);
      link.download = `certificados_${sucNombre.replace(/ /g,'_').toLowerCase()}_${fecha.replace(/\//g,'-')}.xls`;
      link.click();
  }