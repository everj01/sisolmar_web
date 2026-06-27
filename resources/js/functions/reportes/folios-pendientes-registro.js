import axios from 'axios';
  import Swal from 'sweetalert2';
  import jsPDF from 'jspdf';
  import autoTable from 'jspdf-autotable';

  let datosRegistro = null;
  let personalSeleccionados = new Map();
  let resultadosBusqueda = [];
  let buscarPersonalTimeout = null;

  export function init() {
      new TomSelect('#filtroRegistroSucursal', {
          placeholder: 'Todas',
          allowEmptyOption: true,
          onChange: () => {
              personalSeleccionados.clear();
              actualizarContador();
              ejecutarBusquedaPersonal();
          }
      });

      new TomSelect('#filtroRegistroCliente', {
          placeholder: 'Todos',
          allowEmptyOption: true,
          onChange: () => {
              personalSeleccionados.clear();
              actualizarContador();
              sincronizarCheckAll();
          }
      });

      document.getElementById('tbodyPersonalRegistro').addEventListener('change', function (e) {
          if (!e.target.classList.contains('chkPersonalRegistro')) return;
          const codigo  = String(e.target.value);
          const persona = resultadosBusqueda.find(p => String(p.CODI_PERS) === codigo);
          if (e.target.checked && persona) personalSeleccionados.set(codigo, persona);
          else personalSeleccionados.delete(codigo);
          actualizarContador();
          sincronizarCheckAll();
      });

      document.getElementById('buscarPersonalRegistro').addEventListener('input', function () {
          clearTimeout(buscarPersonalTimeout);
          buscarPersonalTimeout = setTimeout(ejecutarBusquedaPersonal, 300);
      });

      document.getElementById('chkTodosRegistro').addEventListener('change', function () {
          const marcar = this.checked;
          document.querySelectorAll('.chkPersonalRegistro').forEach(chk => {
              chk.checked = marcar;
              const codigo  = String(chk.value);
              const persona = resultadosBusqueda.find(p => String(p.CODI_PERS) === codigo);
              if (marcar && persona) personalSeleccionados.set(codigo, persona);
              else personalSeleccionados.delete(codigo);
          });
          actualizarContador();
      });

      document.getElementById('btnGenerarRegistro').addEventListener('click', generar);
      document.getElementById('btnExportPdfRegistro').addEventListener('click', exportarPdf);
      document.getElementById('btnExportExcelRegistro').addEventListener('click', exportarExcel);
  }

  export function limpiarSelectorPersonalRegistro() {
      personalSeleccionados.clear();
      resultadosBusqueda = [];
      document.getElementById('buscarPersonalRegistro').value = '';
      document.getElementById('tbodyPersonalRegistro').innerHTML =
          '<tr><td colspan="4" class="tc" style="color:#94a3b8;padding:12px">Selecciona una sucursal o escribe para buscar</td></tr>';
      actualizarContador();
      sincronizarCheckAll();
  }

  function ejecutarBusquedaPersonal() {
      const query    = document.getElementById('buscarPersonalRegistro').value.trim();
      const sucursal = document.getElementById('filtroRegistroSucursal').value || '0';
      const sinFiltro = query.length < 2 && sucursal === '0';

      if (sinFiltro) {
          document.getElementById('tbodyPersonalRegistro').innerHTML =
              '<tr><td colspan="4" class="tc" style="color:#94a3b8;padding:12px">Selecciona una sucursal o escribe para buscar</td></tr>';
          resultadosBusqueda = [];
          sincronizarCheckAll();
          return;
      }

      document.getElementById('tbodyPersonalRegistro').innerHTML =
          '<tr><td colspan="4" class="tc" style="color:#94a3b8;padding:10px">Buscando...</td></tr>';

      axios.get(`${VITE_URL_APP}/api/get-personal-total-reporte`, {
          params: { search: query || null, codSucursal: sucursal, page: 1, size: 9999, tipo_per: 'OPER', vigencia: 'SI' }
      }).then(r => {
          resultadosBusqueda = r.data.data ?? [];
          renderTablaPersonal(resultadosBusqueda);
      }).catch(() => {
          document.getElementById('tbodyPersonalRegistro').innerHTML =
              '<tr><td colspan="4" class="tc" style="color:#ef4444;padding:10px">Error al cargar</td></tr>';
      });
  }

  function renderTablaPersonal(personas) {
      const tbody = document.getElementById('tbodyPersonalRegistro');
      if (!personas.length) {
          tbody.innerHTML = '<tr><td colspan="4" class="tc" style="color:#94a3b8;padding:12px">Sin resultados</td></tr>';
          sincronizarCheckAll();
          return;
      }
      tbody.innerHTML = personas.map(p => `
          <tr>
              <td class="tc"><input type="checkbox" class="chkPersonalRegistro" value="${p.CODI_PERS}" ${personalSeleccionados.has(String(p.CODI_PERS)) ? 'checked' : ''}></td>
              <td class="tc">${p.CODI_PERS}</td>
              <td>${p.personal ?? '-'}</td>
              <td class="tc">${p.TIPOTRAB2}</td>
          </tr>
      `).join('');
      sincronizarCheckAll();
  }

  function sincronizarCheckAll() {
      const checks = document.querySelectorAll('.chkPersonalRegistro');
      const marked = document.querySelectorAll('.chkPersonalRegistro:checked');
      const chkAll = document.getElementById('chkTodosRegistro');
      if (!checks.length)                      { chkAll.checked = false; chkAll.indeterminate = false; }
      else if (marked.length === 0)             { chkAll.checked = false; chkAll.indeterminate = false; }
      else if (marked.length === checks.length) { chkAll.checked = true;  chkAll.indeterminate = false; }
      else                                      { chkAll.checked = false; chkAll.indeterminate = true; }
  }

  function actualizarContador() {
      const n = personalSeleccionados.size;
      document.getElementById('contadorSeleccionadosRegistro').textContent = n ? `${n} seleccionado(s)` : '';
  }

  function generar() {
      const sucursal   = document.getElementById('filtroRegistroSucursal').value;
      const cliente    = document.getElementById('filtroRegistroCliente').value;
      const parametros = [...personalSeleccionados.keys()].join(',');

      document.getElementById('resultadosRegistro').classList.add('hidden');
      document.getElementById('tablaRegistro').innerHTML = '';
      document.getElementById('totalRegistro').textContent = '';

      Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

      axios.get(`${VITE_URL_APP}/api/reporte/folios-pendientes-registro`, { params: { sucursal, cliente, parametros } })
          .then(response => {
              if (!response.data.length) {
                  Swal.fire({ icon: 'warning', title: 'Sin resultados', text: 'No se encontraron registros con los filtros indicados.' });
                  return;
              }
              datosRegistro = response.data;
              renderTabla(datosRegistro);
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
      const sucursal      = lista[0]?.SUCURSAL ?? '';
      const fecha         = new Date().toLocaleDateString('es-PE');
      const clienteEl     = document.getElementById('filtroRegistroCliente');
      const clienteNombre = clienteEl.value ? clienteEl.options[clienteEl.selectedIndex]?.text : '';

      let html = `
        <div class="mb-2 text-center font-semibold text-default-700" style="font-size:0.85rem">
            PENDIENTES DE REGISTROS PARA LEGAJOS - SUCURSAL ${sucursal} AL ${fecha}
            ${clienteNombre ? `<br><span style="font-weight:normal;font-size:0.78rem">Cliente: ${clienteNombre}</span>` : ''}
        </div>
        <table class="tabla-reporte" style="font-size:0.7rem;min-width:max-content">
            <thead style="position:sticky;top:0;z-index:2">
                <tr>
                    <th>It</th>
                    <th>Cód.</th>
                    <th style="text-align:left;min-width:160px">Apellidos y Nombres</th>
                    <th>Ingreso<br>SOLMAR</th>
                    <th style="min-width:90px">Cargo</th>
                    <th>Tipo</th>
                    <th>Eval<br>Postula</th>
                    <th>Estud<br>Segurid</th>
                    <th>Verif<br>Domicilio</th>
                    <th>Capaci<br>taciones</th>
                    <th>CV</th>
                    <th>Policial</th>
                    <th>Penal</th>
                    <th>Judicial</th>
                    <th>Exam.<br>Toxicol<br>Exter</th>
                    <th>EMO</th>
                    <th>Exam<br>Psicol</th>
                    <th>Cert.<br>Vacuna</th>
                    <th>CUL</th>
                </tr>
            </thead>
            <tbody>
                ${lista.map((r, i) => `<tr>
                    <td class="tc">${i + 1}</td>
                    <td class="tc">${r.CODIGO}</td>
                    <td>${r.PERSONAL}</td>
                    <td class="tc">${r.INGRESO_PLANILLA ?? '-'}</td>
                    <td>${r.CARGO ?? '-'}</td>
                    <td class="tc">${r.TIPO ?? '-'}</td>
                    ${celdaEstado(r.EVAL_POSTULA)}
                    ${celdaEstado(r.ESTUD_SEGURIDAD)}
                    ${celdaEstado(r.VERIF_DOMICILIO)}
                    ${celdaEstado(r.CAPACITACIONES)}
                    ${celdaEstado(r.CV)}
                    ${celdaEstado(r.POLICIALES)}
                    ${celdaEstado(r.PENALES)}
                    ${celdaEstado(r.JUDICIALES)}
                    ${celdaEstado(r.TOXI_EXTERNO)}
                    ${celdaEstado(r.MEDICO)}
                    ${celdaEstado(r.PSICOLOGICO)}
                    ${celdaEstado(r.VACUNA)}
                    ${celdaEstado(r.CUL)}
                </tr>`).join('')}
            </tbody>
        </table>`;

      document.getElementById('totalRegistro').textContent = `${lista.length} registro(s)`;
      document.getElementById('tablaRegistro').innerHTML = html;
      document.getElementById('resultadosRegistro').classList.remove('hidden');
  }

  function exportarPdf() {
      if (!datosRegistro) return;
      const doc   = new jsPDF({ orientation: 'landscape', format: 'a3' });
      const PW    = doc.internal.pageSize.getWidth();
      const PH    = doc.internal.pageSize.getHeight();
      const M     = 5;
      const fecha = new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });
      const hora  = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
      const sucursal      = datosRegistro[0]?.SUCURSAL ?? '';
      const clienteEl     = document.getElementById('filtroRegistroCliente');
      const clienteNombre = clienteEl.value ? clienteEl.options[clienteEl.selectedIndex]?.text : '';

      doc.setFontSize(9); doc.setFont(undefined, 'bold');
      doc.setFillColor(6, 10, 81); doc.rect(M, 8, PW - M * 2, 7, 'F');
      doc.setTextColor(255, 255, 255);
      doc.text(`PENDIENTES DE REGISTROS PARA LEGAJOS - SUCURSAL ${sucursal}`, PW / 2, 13, { align: 'center' });
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
          if (v === 'SI') return { content: 'SI', styles: { fillColor: [209, 250, 229], textColor: [6, 95, 70],   fontStyle: 'bold', halign: 'center', fontSize: 5.5 } };
          if (v === 'NO') return { content: 'NO', styles: { fillColor: [254, 226, 226], textColor: [153, 27, 27], fontStyle: 'bold', halign: 'center', fontSize: 5.5 } };
          return { content: val ?? '-', styles: { halign: 'center', fontSize: 5 } };
      }

      const head = [[
          '#', 'Cód.', 'Apellidos y Nombres', 'Ingreso\nSOLMAR', 'Cargo', 'Tipo',
          'Eval\nPostula', 'Estud\nSegurid', 'Verif\nDomicilio', 'Capaci\ntaciones', 'CV',
          'Policial', 'Penal', 'Judicial',
          'Exam.\nToxicol\nExter', 'EMO', 'Exam\nPsicol', 'Cert.\nVacuna', 'CUL',
      ]];

      const body = datosRegistro.map((r, i) => [
          { content: i + 1,                      styles: { halign: 'center', fontSize: 5.5 } },
          { content: r.CODIGO,                   styles: { halign: 'center', fontSize: 5.5 } },
          { content: r.PERSONAL,                 styles: { fontSize: 5.5 } },
          { content: r.INGRESO_PLANILLA ?? '-',  styles: { halign: 'center', fontSize: 5.5 } },
          { content: r.CARGO ?? '-',             styles: { fontSize: 5.5 } },
          { content: r.TIPO ?? '-',              styles: { halign: 'center', fontSize: 5.5 } },
          cellStyle(r.EVAL_POSTULA),
          cellStyle(r.ESTUD_SEGURIDAD),
          cellStyle(r.VERIF_DOMICILIO),
          cellStyle(r.CAPACITACIONES),
          cellStyle(r.CV),
          cellStyle(r.POLICIALES),
          cellStyle(r.PENALES),
          cellStyle(r.JUDICIALES),
          cellStyle(r.TOXI_EXTERNO),
          cellStyle(r.MEDICO),
          cellStyle(r.PSICOLOGICO),
          cellStyle(r.VACUNA),
          cellStyle(r.CUL),
      ]);

      const cw = Math.max(7, (PW - M * 2 - 6 - 9 - 35 - 13 - 22 - 10) / 13);
      const cs = { 0: { cellWidth: 6 }, 1: { cellWidth: 9 }, 2: { cellWidth: 35 }, 3: { cellWidth: 13 }, 4: { cellWidth: 22 }, 5: { cellWidth: 10 } };
      for (let i = 6; i < 19; i++) cs[i] = { cellWidth: cw, halign: 'center' };

      autoTable(doc, {
          startY, head, body,
          styles: { fontSize: 5.5, cellPadding: { top: 1.5, bottom: 1.5, left: 1, right: 1 }, lineColor: [200, 200, 210], lineWidth: 0.15, overflow: 'ellipsize', minCellHeight: 7 },
          headStyles: { fillColor: [15, 23, 80], textColor: [255, 255, 255], fontStyle: 'bold', halign: 'center', fontSize: 5.5, cellPadding: { top: 2, bottom: 2, left: 1, right: 1 }, valign: 'middle' },
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
      link.download = 'folios_pendientes_registro.pdf';
      link.click();
  }

  function exportarExcel() {
      if (!datosRegistro) return;
      const sucursal      = datosRegistro[0]?.SUCURSAL ?? '';
      const fecha         = new Date().toLocaleDateString('es-PE');
      const clienteEl     = document.getElementById('filtroRegistroCliente');
      const clienteNombre = clienteEl.value ? clienteEl.options[clienteEl.selectedIndex]?.text : '';

      const BASE = 'border:1px solid #d1d5db;padding:3px 5px;font-size:9pt;';
      const TH   = 'background:#0f1750;color:#fff;font-weight:bold;text-align:center;vertical-align:middle;border:1px solid #1e3a5f;padding:4px;font-size:8pt;white-space:nowrap';

      function th(txt) { return `<th style="${TH}">${txt}</th>`; }
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
        <x:ExcelWorksheet><x:Name>Pend. Registro</x:Name>
        <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
        </x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
        </head><body>
        <table border="0" cellspacing="0" cellpadding="0">
        <tr><td colspan="19" style="font-size:11pt;font-weight:bold;text-align:center;padding:6px;border:none">
            PENDIENTES DE REGISTROS PARA LEGAJOS - SUCURSAL ${sucursal} AL ${fecha}
        </td></tr>
        ${clienteNombre ? `<tr><td colspan="19" style="font-size:9pt;text-align:center;padding:2px 6px;border:none">Cliente: ${clienteNombre}</td></tr>` : ''}
        <tr>
            ${th('#')}${th('Cód.')}${th('Apellidos y Nombres')}${th('Ingreso SOLMAR')}${th('Cargo')}${th('Tipo')}
            ${th('Eval Postula')}${th('Estud Segurid')}${th('Verif Domicilio')}${th('Capacitaciones')}${th('CV')}
            ${th('Policial')}${th('Penal')}${th('Judicial')}
            ${th('Exam. Toxicol Exter')}${th('EMO')}${th('Exam Psicol')}${th('Cert. Vacuna')}${th('CUL')}
        </tr>`;

      datosRegistro.forEach((r, i) => {
          const alt = i % 2 !== 0 ? 'background:#f8f9fc;' : '';
          const tdN = (v, left = false) => `<td style="${BASE}${left ? '' : 'text-align:center;'}${alt}">${v ?? ''}</td>`;
          html += `<tr>
                ${tdN(i + 1)}
                ${tdN(r.CODIGO)}
                ${tdN(r.PERSONAL, true)}
                ${tdN(r.INGRESO_PLANILLA)}
                ${tdN(r.CARGO, true)}
                ${tdN(r.TIPO)}
                ${tdSI(r.EVAL_POSTULA)}
                ${tdSI(r.ESTUD_SEGURIDAD)}
                ${tdSI(r.VERIF_DOMICILIO)}
                ${tdSI(r.CAPACITACIONES)}
                ${tdSI(r.CV)}
                ${tdSI(r.POLICIALES)}
                ${tdSI(r.PENALES)}
                ${tdSI(r.JUDICIALES)}
                ${tdSI(r.TOXI_EXTERNO)}
                ${tdSI(r.MEDICO)}
                ${tdSI(r.PSICOLOGICO)}
                ${tdSI(r.VACUNA)}
                ${tdSI(r.CUL)}
            </tr>`;
      });

      html += `</table></body></html>`;
      const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'folios_pendientes_registro.xls';
      link.click();
  }