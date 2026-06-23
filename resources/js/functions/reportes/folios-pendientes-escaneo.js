import axios from 'axios';
  import Swal from 'sweetalert2';
  import jsPDF from 'jspdf';
  import autoTable from 'jspdf-autotable';

  let datosEscaneo        = null;
  let personalSeleccionados = new Map();
  let resultadosBusqueda  = [];
  let buscarPersonalTimeout = null;

  export function init() {
      new TomSelect('#filtroEscaneoSucursal', {
          placeholder: 'Todas',
          allowEmptyOption: true,
          onChange: () => {
              personalSeleccionados.clear();
              actualizarContadorEscaneo();
              ejecutarBusquedaPersonal();
          }
      });

      new TomSelect('#filtroEscaneoCliente', {
          placeholder: 'Todos',
          allowEmptyOption: true,
          onChange: () => {
              personalSeleccionados.clear();
              actualizarContadorEscaneo();
              sincronizarCheckAll();
          }
      });

      document.getElementById('tbodyPersonalEscaneo').addEventListener('change', function (e) {
          if (!e.target.classList.contains('chkPersonalEscaneo')) return;
          const codigo  = String(e.target.value);
          const persona = resultadosBusqueda.find(p => String(p.CODI_PERS) === codigo);
          if (e.target.checked && persona) personalSeleccionados.set(codigo, persona);
          else personalSeleccionados.delete(codigo);
          actualizarContadorEscaneo();
          sincronizarCheckAll();
      });

      document.getElementById('buscarPersonalEscaneo').addEventListener('input', function () {
          clearTimeout(buscarPersonalTimeout);
          buscarPersonalTimeout = setTimeout(ejecutarBusquedaPersonal, 300);
      });

      document.getElementById('chkTodosEscaneo').addEventListener('change', function () {
          const marcar = this.checked;
          document.querySelectorAll('.chkPersonalEscaneo').forEach(chk => {
              chk.checked = marcar;
              const codigo  = String(chk.value);
              const persona = resultadosBusqueda.find(p => String(p.CODI_PERS) === codigo);
              if (marcar && persona) personalSeleccionados.set(codigo, persona);
              else personalSeleccionados.delete(codigo);
          });
          actualizarContadorEscaneo();
      });

      document.getElementById('btnGenerarEscaneo').addEventListener('click', generar);
      document.getElementById('btnExportPdfEscaneo').addEventListener('click', exportarPdf);
      document.getElementById('btnExportExcelEscaneo').addEventListener('click', exportarExcel);
  }

  export function limpiarSelectorPersonalEscaneo() {
      personalSeleccionados.clear();
      resultadosBusqueda = [];
      document.getElementById('buscarPersonalEscaneo').value = '';
      document.getElementById('tbodyPersonalEscaneo').innerHTML =
          '<tr><td colspan="4" class="tc" style="color:#94a3b8;padding:12px">Selecciona una sucursal o escribe para buscar</td></tr>';
      actualizarContadorEscaneo();
      sincronizarCheckAll();
  }

  function ejecutarBusquedaPersonal() {
      const query    = document.getElementById('buscarPersonalEscaneo').value.trim();
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
          params: { search: query || null, codSucursal: sucursal, page: 1, size: 9999, tipo_per : 'OPER',  vigencia: 'SI' }
      }).then(r => {
          resultadosBusqueda = r.data.data ?? [];
          renderTablaPersonal(resultadosBusqueda);
      }).catch(() => {
          document.getElementById('tbodyPersonalEscaneo').innerHTML =
              '<tr><td colspan="4" class="tc" style="color:#ef4444;padding:10px">Error al cargar</td></tr>';
      });
  }

  function renderTablaPersonal(personas) {
      const tbody = document.getElementById('tbodyPersonalEscaneo');
      if (!personas.length) {
          tbody.innerHTML = '<tr><td colspan="4" class="tc" style="color:#94a3b8;padding:12px">Sin resultados</td></tr>';
          sincronizarCheckAll();
          return;
      }
      tbody.innerHTML = personas.map(p => `
          <tr>
              <td class="tc">
                  <input type="checkbox" class="chkPersonalEscaneo" value="${p.CODI_PERS}"
                      ${personalSeleccionados.has(String(p.CODI_PERS)) ? 'checked' : ''}>
              </td>
              <td class="tc">${p.CODI_PERS}</td>
              <td>${p.personal ?? '-'}</td>
              <td class="tc">${p.TIPOTRAB2}</td>
          </tr>
      `).join('');
      sincronizarCheckAll();
  }

  function sincronizarCheckAll() {
      const checks = document.querySelectorAll('.chkPersonalEscaneo');
      const marked = document.querySelectorAll('.chkPersonalEscaneo:checked');
      const chkAll = document.getElementById('chkTodosEscaneo');
      if (!checks.length)               { chkAll.checked = false; chkAll.indeterminate = false; }
      else if (marked.length === 0)      { chkAll.checked = false; chkAll.indeterminate = false; }
      else if (marked.length === checks.length) { chkAll.checked = true;  chkAll.indeterminate = false; }
      else                               { chkAll.checked = false; chkAll.indeterminate = true; }
  }

  function actualizarContadorEscaneo() {
      const n = personalSeleccionados.size;
      document.getElementById('contadorSeleccionadosEscaneo').textContent = n ? `${n} seleccionado(s)` : '';
  }

  function generar() {
      const sucursal   = document.getElementById('filtroEscaneoSucursal').value;
      const cliente    = document.getElementById('filtroEscaneoCliente').value;
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
              renderTabla(datosEscaneo);
              Swal.close();
          })
          .catch(() => Swal.fire('Error', 'No se pudo cargar el reporte', 'error'));
  }

  function celdaEstado(val) {
      const v = (val ?? '').toString().toUpperCase().trim();
      if (v === 'SI') return `<td class="celda-si">SI</td>`;
      if (v === 'NO') return `<td class="celda-no">NO</td>`;
      return `<td class="tc" style="font-size:0.7rem">${val ?? '-'}</td>`;
  }

  function renderTabla(lista) {
      const sucursal = lista[0]?.SUCURSAL ?? '';
      const fecha    = new Date().toLocaleDateString('es-PE');
      const clienteEl    = document.getElementById('filtroEscaneoCliente');
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
                    <th rowspan="2">CV01</th>
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
                ${lista.map(r => `<tr>
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
                    ${celdaEstado(r.CV01)}
                    ${celdaEstado(r.FIASINTO)}
                    ${celdaEstado(r.CUL)}${celdaEstado(r.DJ)}
                </tr>`).join('')}
            </tbody>
        </table>`;

      document.getElementById('totalEscaneo').textContent = `${lista.length} registro(s)`;
      document.getElementById('tablaEscaneo').innerHTML = html;
      document.getElementById('resultadosEscaneo').classList.remove('hidden');
  }

  function exportarPdf() {
      if (!datosEscaneo) return;
      const doc  = new jsPDF({ orientation: 'landscape', format: 'a3' });
      const PW   = doc.internal.pageSize.getWidth();
      const PH   = doc.internal.pageSize.getHeight();
      const M    = 5;
      const fecha = new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });
      const hora  = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
      const sucursal = datosEscaneo[0]?.SUCURSAL ?? '';
      const clienteEl     = document.getElementById('filtroEscaneoCliente');
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
          { content: 'Anteceden', colSpan: 3 },
          { content: 'Exam.\nToxicol.', rowSpan: 2 },
          { content: 'Exam.\nMéd.', rowSpan: 2 }, { content: 'Exam.\nPsico', rowSpan: 2 },
          { content: 'Cert.\nVac.', rowSpan: 2 }, { content: 'CV01', rowSpan: 2 },
          { content: 'Fich.\nSinto', rowSpan: 2 }, { content: 'CUL', rowSpan: 2 },
          { content: 'DJ\nPóliza', rowSpan: 2 },
      ], [
          'Anv', 'Rev', 'Indi', 'Total', 'Anv', 'Rev', 'Anv', 'Rev', 'Anv', 'Rev',
          'Cro', 'Fach', 'Ent', 'Pol', 'Pen', 'Jud',
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
          cellStyle(r.CV01),
          cellStyle(r.FIASINTO),
          cellStyle(r.CUL), cellStyle(r.DJ),
      ]);

      const cw = Math.max(5, (PW - M * 2 - 9 - 30 - 13 - 17 - 9) / 29);
      const cs = { 0: { cellWidth: 9 }, 1: { cellWidth: 30 }, 2: { cellWidth: 13 }, 3: { cellWidth: 17 }, 4: { cellWidth: 9 } };
      for (let i = 5; i < 34; i++) cs[i] = { cellWidth: cw, halign: 'center' };

      autoTable(doc, {
          startY, head, body,
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
  }

  function exportarExcel() {
      if (!datosEscaneo) return;
      const sucursal = datosEscaneo[0]?.SUCURSAL ?? '';
      const fecha    = new Date().toLocaleDateString('es-PE');
      const clienteEl     = document.getElementById('filtroEscaneoCliente');
      const clienteNombre = clienteEl.value ? clienteEl.options[clienteEl.selectedIndex]?.text : '';

      const BASE = 'border:1px solid #d1d5db;padding:3px 5px;font-size:9pt;';
      const TH   = 'background:#0f1750;color:#fff;font-weight:bold;text-align:center;vertical-align:middle;border:1px solid #1e3a5f;padding:4px;font-size:8pt;white-space:nowrap';

      function th(txt, rs = 1, cs = 1) { return `<th rowspan="${rs}" colspan="${cs}" style="${TH}">${txt}</th>`; }
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
        <tr><td colspan="34" style="font-size:11pt;font-weight:bold;text-align:center;padding:6px;border:none">
            PENDIENTES DE LA BASE DE DATOS GRÁFICA - SUCURSAL ${sucursal} AL ${fecha}
        </td></tr>
        ${clienteNombre ? `<tr><td colspan="34" style="font-size:9pt;text-align:center;padding:2px 6px;border:none">Cliente: ${clienteNombre}</td></tr>` : ''}
        <tr>
            ${th('Cód.', 2)}          ${th('Apellidos y Nombres', 2)} ${th('Ingreso SOLMAR', 2)}
            ${th('Cargo', 2)}         ${th('Tipo', 2)}                ${th('Foto', 2)}
            ${th('DNI', 1, 2)}         ${th('Huella D', 1, 2)}          ${th('Firma', 2)}
            ${th('FOTO CONTRO', 1, 2)}  ${th('CI SUCAM', 1, 2)}         ${th('Lic. Arma', 2)}
            ${th('Brevete', 1, 2)}     ${th('Cert. Estudios', 2)}      ${th('Cert. Laboral', 2)}
            ${th('Domicilio', 1, 3)}   ${th('Anteceden', 1, 3)}
            ${th('Exam. Toxicol.', 2)} ${th('Exam. Médico', 2)}        ${th('Exam. Psicol.', 2)}
            ${th('Cert. Vacuna', 2)}   ${th('CV01', 2)}                ${th('Fich. Sinto', 2)}
            ${th('CUL', 2)}            ${th('DJ Póliza', 2)}
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
                ${tdSI(r.DNI1)}          ${tdSI(r.DNI2)}
                ${tdSI(r.HUELLA)}        ${tdSI(r.HUELLAS5)}
                ${tdSI(r.FIRMA)}
                ${tdSI(r.FOTOCONTROL1)}  ${tdSI(r.FOTOCONTROL2)}
                ${tdSI(r.CD1)}           ${tdSI(r.CD2)}
                ${tdSI(r.LA)}
                ${tdSI(r.BREVETE1)}      ${tdSI(r.BREVETE2)}
                ${tdSI(r.ESTUDIOS)}      ${tdSI(r.LABORAL)}
                ${tdSI(r.CROQUIS)}       ${tdSI(r.FACHADA)}    ${tdSI(r.ENTORNO)}
                ${tdSI(r.POLICIAL)}      ${tdSI(r.PENAL)}      ${tdSI(r.JUDICIAL)}
                ${tdSI(r.TOXI_EXTERNO)}
                ${tdSI(r.MEDICO)}        ${tdSI(r.PSICO)}      ${tdSI(r.VACUNA)}
                ${tdSI(r.CV01)}
                ${tdSI(r.FIASINTO)}
                ${tdSI(r.CUL)}           ${tdSI(r.DJ)}
            </tr>`;
      });

      html += `</table></body></html>`;

      const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'pendientes_escaneo.xls';
      link.click();
  }