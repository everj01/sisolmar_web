import axios from 'axios';
import Swal from 'sweetalert2';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

let datos = null;
let visorImgs = { anverso: null, reverso: null };
let visorMostrando = 'anverso';
let memorandumActivo = false;
let reporteGeneradoAlgVez = false;

export function init() {
    document.getElementById('filtroVigDocumento').addEventListener('change', function () {
        const div = document.getElementById('filtrosBreveteDiv');
        if (this.value === 'BREVETE') {
            div.classList.remove('hidden');
        } else {
            div.classList.add('hidden');
        }
        invalidarMemorandum();
    });

    document.getElementById('filtroVigSucursal').addEventListener('change', invalidarMemorandum);
    document.getElementById('filtroVigTipoPers').addEventListener('change', invalidarMemorandum);
    document.getElementById('filtroVigClase').addEventListener('change', () => { cargarCategorias(); invalidarMemorandum(); });
    document.getElementById('filtroVigCategoria').addEventListener('change', invalidarMemorandum);

    document.querySelectorAll('input[name="filtroVigEstado"]').forEach(radio => {
        radio.addEventListener('change', invalidarMemorandum);
    });

    document.getElementById('btnGenerarVigencia').addEventListener('click', generar);
    document.getElementById('btnExportPdfVigencia').addEventListener('click', exportarPdf);
    document.getElementById('btnExportExcelVigencia').addEventListener('click', exportarExcel);
    document.getElementById('btnMemorandum').addEventListener('click', generarMemorandum);

    // Visor DNI — delegación de eventos sobre la tabla dinámica
    document.getElementById('tablaVigencia').addEventListener('click', e => {
        const btn = e.target.closest('.btn-ver-dni');
        if (btn) abrirVisorDNI(btn.dataset.cod, btn.dataset.nombre);
    });

    document.getElementById('btnCerrarVisorDNI').addEventListener('click', cerrarVisorDNI);
    document.getElementById('btnVolverVisor').addEventListener('click', cerrarVisorDNI);

    document.getElementById('btnVisorToggle').addEventListener('click', () => {
        visorMostrando = visorMostrando === 'anverso' ? 'reverso' : 'anverso';
        mostrarLadoDNI();
    });

    document.getElementById('visorDniImg').addEventListener('click', function () {
        window.open(this.src, '_blank');
    });
}

// ── Memorandum ──────────────────────────────────────────────────────────────

function actualizarBtnMemorandum() {
    const btn = document.getElementById('btnMemorandum');
    const msg = document.getElementById('msgMemorandum');
    if (!btn) return;

    if (memorandumActivo) {
        btn.disabled = false;
        btn.className = 'px-4 py-2 rounded-lg border border-primary bg-primary text-white text-sm flex items-center gap-1 hover:bg-primary/90 cursor-pointer';
        if (msg) msg.classList.add('hidden');
    } else {
        btn.disabled = true;
        btn.className = 'px-4 py-2 rounded-lg border border-default-200 text-default-400 bg-default-50 text-sm cursor-not-allowed flex items-center gap-1';
        if (msg) {
            if (reporteGeneradoAlgVez) msg.classList.remove('hidden');
            else msg.classList.add('hidden');
        }
    }
}

function invalidarMemorandum() {
    memorandumActivo = false;
    actualizarBtnMemorandum();
}

// ── Categorías Brevete ──────────────────────────────────────────────────────

async function cargarCategorias() {
    const selectClase = document.getElementById('filtroVigClase');
    const selectCat = document.getElementById('filtroVigCategoria');
    const claseVal = selectClase.value;
    const codClase = selectClase.options[selectClase.selectedIndex]?.getAttribute('data-cod');

    selectCat.innerHTML = '<option value="T">Todas</option>';
    if (!codClase || claseVal === 'T') return;

    try {
        const res = await axios.get(`${VITE_URL_APP}/api/reporte/categorias-brevete`, { params: { cod_clase: codClase } });
        res.data.forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat.nombre;
            opt.textContent = cat.nombre;
            selectCat.appendChild(opt);
        });
    } catch { /* silencioso */ }
}

function getSelText(id) {
    const s = document.getElementById(id);
    return s?.options[s.selectedIndex]?.text ?? '';
}

// ── Generar ─────────────────────────────────────────────────────────────────

function generar() {
    const documento = document.getElementById('filtroVigDocumento').value;
    const sucursal = document.getElementById('filtroVigSucursal').value;
    const tipoPers = document.getElementById('filtroVigTipoPers').value;
    const vigente = document.querySelector('input[name="filtroVigEstado"]:checked')?.value ?? 'NO';

    if (!documento) { Swal.fire({ icon: 'warning', title: 'Requerido', text: 'Selecciona un tipo de documento.' }); return; }
    if (!sucursal) { Swal.fire({ icon: 'warning', title: 'Requerido', text: 'Selecciona una sucursal.' }); return; }
    if (!tipoPers) { Swal.fire({ icon: 'warning', title: 'Requerido', text: 'Selecciona el tipo de personal.' }); return; }

    let url, params;
    if (documento === 'DNI') {
        url = `${VITE_URL_APP}/api/reporte/vigencia-dni`;
        params = { sucursal, tipo_pers: tipoPers, vigente };
    } else {
        const clase = document.getElementById('filtroVigClase').value;
        const categoria = document.getElementById('filtroVigCategoria').value;
        url = `${VITE_URL_APP}/api/reporte/vigencia-brevete`;
        params = { sucursal, tipo_pers: tipoPers, vigente, clase, categoria };
    }

    Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    axios.get(url, { params })
        .then(r => {
            if (!r.data.length) {
                Swal.fire({ icon: 'warning', title: 'Sin resultados', text: 'No se encontraron registros con esos filtros.' });
                return;
            }
            datos = r.data;
            renderTabla(datos, documento, vigente);
            Swal.close();

            reporteGeneradoAlgVez = true;
            memorandumActivo = (vigente === 'NO');
            actualizarBtnMemorandum();
        })
        .catch(() => Swal.fire('Error', 'No se pudo generar el reporte.', 'error'));
}

// ── Tabla ───────────────────────────────────────────────────────────────────

function celdaEscaneo(val, codigo, nombre) {
    const v = (val ?? '').toString().toUpperCase().trim();
    if (v === 'SI') {
        const safe = (nombre ?? '').replace(/"/g, '&quot;');
        return `<td class="celda-si"><button class="btn-ver-dni" style="background:none;border:none;color:inherit;font-weight:bold;text-decoration:underline;cursor:pointer;padding:0" data-cod="${codigo ??
            ''}" data-nombre="${safe}">SI</button></td>`;
    }
    if (v === 'NO') return `<td class="celda-no">NO</td>`;
    if (v === 'CADUCADO') return `<td class="celda-x">CADUCADO</td>`;
    return `<td class="tc">${val ?? '-'}</td>`;
}

function renderTabla(lista, tipo, vigente) {
    const sucNombre = getSelText('filtroVigSucursal');
    const estadoLabel = vigente === 'SI' ? 'VIGENTES' : 'NO VIGENTES';
    const fecha = new Date().toLocaleDateString('es-PE');
    const esBrevete = tipo === 'BREVETE';
    const title = `${tipo} ${estadoLabel} — SUCURSAL ${sucNombre.toUpperCase()} AL ${fecha}`;

    const theadDNI = `
          <th style="width:28px">IT</th>
          <th style="width:55px">Cód.</th>
          <th style="text-align:left;min-width:180px">Personal</th>
          <th>Tipo Doc.</th>
          <th style="width:88px">Doc. Iden.</th>
          <th style="width:72px">Tipo</th>
          <th style="width:82px">Ing. SOLMAR</th>
          <th style="width:82px">Ing. Planilla</th>
          <th style="width:82px">Fec. Caduca</th>
          <th style="text-align:left;min-width:140px">Cliente</th>
          <th style="text-align:left;min-width:160px">Cargo</th>
          <th style="width:68px">Escaneo</th>`;

    const theadBrevete = `
          <th style="width:28px">IT</th>
          <th style="width:55px">Cód.</th>
          <th style="text-align:left;min-width:180px">Personal</th>
          <th>Tipo Doc.</th>
          <th style="width:88px">Doc. Iden.</th>
          <th style="width:72px">Tipo</th>
          <th style="width:82px">Ing. SOLMAR</th>
          <th style="width:82px">Ing. Planilla</th>
          <th style="text-align:left;min-width:140px">Cliente</th>
          <th style="text-align:left;min-width:160px">Cargo</th>
          <th style="width:68px">Escaneo</th>
          <th style="width:80px">Brevete</th>
          <th style="width:60px">Clase</th>
          <th style="width:75px">Categoría</th>
          <th style="width:80px">Fec. Exp.</th>
          <th style="width:80px">Fec. Reval.</th>
          <th style="width:80px">Restricción</th>`;

    const rows = lista.map((r, i) => `<tr>
          <td class="tc">${i + 1}</td>
          <td class="tc">${r.CODIGO ?? ''}</td>
          <td>${r.PERSONAL ?? ''}</td>
          <td class="tc">${r.TIPO_DOCU ?? ''}</td>
          <td class="tc">${r.NRO_DOCU_IDEN ?? ''}</td>
          <td class="tc">${r.TIPO ?? ''}</td>
          <td class="tc">${r.INGRESO_SOLMAR ?? ''}</td>
          <td class="tc">${r.INGRESO_PLAN ?? ''}</td>
          ${!esBrevete ? `<td class="tc">${r.PERS_FECHCADUCADNI ?? ''}</td>` : ''}
          <td>${r.CLIENTE ?? ''}</td>
          <td>${r.CARGO ?? ''}</td>
          ${celdaEscaneo(r.ESCANEO, r.CODIGO, r.PERSONAL)}
          ${esBrevete ? `
          <td class="tc">${r.PERS_BREVETE ?? ''}</td>
          <td class="tc">${r.CLASE_BREVETE ?? ''}</td>
          <td class="tc">${r.CATEGORIA_BREVETE ?? ''}</td>
          <td class="tc">${r.FECH_EXP_BREVETE ?? ''}</td>
          <td class="tc">${r.FECH_REVAL_BREVETE ?? ''}</td>
          <td class="tc">${r.RESTRICCION_BREVETE ?? ''}</td>
          ` : ''}
      </tr>`).join('');

    document.getElementById('totalVigencia').textContent = `${lista.length} registro(s)`;
    document.getElementById('tablaVigencia').innerHTML = `
          <div class="mb-3 text-center font-semibold text-default-700" style="font-size:0.88rem">${title}</div>
          <div class="tabla-scroll">
              <table class="tabla-reporte" style="font-size:0.72rem">
                  <thead><tr>${esBrevete ? theadBrevete : theadDNI}</tr></thead>
                  <tbody>${rows}</tbody>
              </table>
          </div>`;
    document.getElementById('resultadosVigencia').classList.remove('hidden');
}

// ── Visor DNI ──────────────────────────────────────────────────────────────

function isImg(v) {
    return v && typeof v === 'string' && v.length > 50;
}

function abrirVisorDNI(codigo, nombre) {
    document.getElementById('visorDniNombre').textContent = nombre || '—';
    document.getElementById('visorDniCodigo').textContent = codigo || '—';
    document.getElementById('visorDniCargando').classList.remove('hidden');
    document.getElementById('visorDniImagen').classList.add('hidden');
    document.getElementById('visorDniError').classList.add('hidden');

    const modal = document.getElementById('modalVisorDNI');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    axios.get(`${VITE_URL_APP}/api/get-biometrico/${codigo}`)
        .then(r => {
            const d = r.data;
            visorImgs.anverso = isImg(d.dni_anverso_nuevo) ? d.dni_anverso_nuevo
                : isImg(d.dni_anverso_antigua) ? d.dni_anverso_antigua : null;
            visorImgs.reverso = isImg(d.dni_reverso_nuevo) ? d.dni_reverso_nuevo
                : isImg(d.dni_reverso_antigua) ? d.dni_reverso_antigua : null;

            document.getElementById('visorDniCargando').classList.add('hidden');

            if (!visorImgs.anverso && !visorImgs.reverso) {
                document.getElementById('visorDniError').classList.remove('hidden');
                return;
            }

            visorMostrando = 'anverso';
            mostrarLadoDNI();
            document.getElementById('visorDniImagen').classList.remove('hidden');
        })
        .catch(() => {
            document.getElementById('visorDniCargando').classList.add('hidden');
            document.getElementById('visorDniError').classList.remove('hidden');
        });
}

function mostrarLadoDNI() {
    const src = visorMostrando === 'anverso' ? visorImgs.anverso : visorImgs.reverso;
    document.getElementById('visorDniImg').src = `${src}`;
    document.getElementById('visorDniBadge').textContent = visorMostrando === 'anverso' ? 'ANVERSO' : 'REVERSO';

    const btnToggle = document.getElementById('btnVisorToggle');
    if (visorMostrando === 'anverso') {
        btnToggle.textContent = 'Ver reverso';
        btnToggle.disabled = !visorImgs.reverso;
    } else {
        btnToggle.textContent = 'Ver anverso';
        btnToggle.disabled = false;
    }
}

function cerrarVisorDNI() {
    const modal = document.getElementById('modalVisorDNI');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('visorDniImg').src = '';
}

// ── Exportar PDF ────────────────────────────────────────────────────────────

function exportarPdf() {
    if (!datos) return;
    const tipo = document.getElementById('filtroVigDocumento').value;
    const sucNombre = getSelText('filtroVigSucursal');
    const vigente = document.querySelector('input[name="filtroVigEstado"]:checked')?.value ?? 'NO';
    const estadoLabel = vigente === 'SI' ? 'VIGENTES' : 'NO VIGENTES';
    const fecha = new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });
    const hora = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
    const esBrevete = tipo === 'BREVETE';
    const doc = new jsPDF({ orientation: 'landscape', format: esBrevete ? 'a3' : 'a4' });
    const PW = doc.internal.pageSize.getWidth();
    const PH = doc.internal.pageSize.getHeight();
    const M = 5;

    function pdfEsc(val) {
        const v = (val ?? '').toString().toUpperCase().trim();
        if (v === 'SI') return { content: 'SI', styles: { fillColor: [209, 250, 229], textColor: [6, 95, 70], fontStyle: 'bold', halign: 'center', fontSize: 5.5 } };
        if (v === 'NO') return { content: 'NO', styles: { fillColor: [254, 226, 226], textColor: [153, 27, 27], fontStyle: 'bold', halign: 'center', fontSize: 5.5 } };
        if (v === 'CADUCADO') return { content: 'CADUCADO', styles: { fillColor: [254, 243, 199], textColor: [146, 64, 14], fontStyle: 'bold', halign: 'center', fontSize: 5 } };
        return { content: val ?? '-', styles: { halign: 'center', fontSize: 5.5 } };
    }
    const c = (v, center = true) => ({ content: v ?? '', styles: { halign: center ? 'center' : 'left', fontSize: 5.5 } });

    const title = `${tipo} ${estadoLabel} — SUCURSAL ${sucNombre.toUpperCase()}`;
    let y = 8;
    doc.setFontSize(9); doc.setFont(undefined, 'bold');
    doc.setFillColor(6, 10, 81); doc.rect(M, y, PW - M * 2, 7, 'F');
    doc.setTextColor(255, 255, 255);
    doc.text(title, PW / 2, y + 5, { align: 'center' });
    doc.setTextColor(0, 0, 0);
    y += 10;

    let head, body, columnStyles;

    if (!esBrevete) {
        head = [['IT', 'Cód.', 'Personal', 'Tipo Doc.', 'Doc. Iden.', 'Tipo', 'Ing. SOLMAR', 'Ing. Planilla', 'Fec. Caduca', 'Cliente', 'Cargo', 'Escaneo']];
        body = datos.map((r, i) => [
            c(i + 1), c(r.CODIGO), c(r.PERSONAL, '', false), c(r.TIPO_DOCU), c(r.NRO_DOCU_IDEN),
            c(r.TIPO), c(r.INGRESO_SOLMAR), c(r.INGRESO_PLAN), c(r.PERS_FECHCADUCADNI),
            c(r.CLIENTE, '', false), c(r.CARGO, '', false), pdfEsc(r.ESCANEO),
        ]);
        columnStyles = {
            0: { cellWidth: 6, halign: 'center' }, 1: { cellWidth: 9, halign: 'center' }, 2: { cellWidth: 32 },
            3: { cellWidth: 13, halign: 'center' }, 4: { cellWidth: 16, halign: 'center' }, 5: { cellWidth: 12, halign: 'center' },
            6: { cellWidth: 16, halign: 'center' }, 7: { cellWidth: 16, halign: 'center' }, 8: { cellWidth: 16, halign: 'center' },
            9: { cellWidth: 26 }, 10: { cellWidth: 28 }, 11: { cellWidth: 12, halign: 'center' },
        };
    } else {
        head = [['IT', 'Cód.', 'Personal', 'Tipo Doc.', 'Doc. Iden.', 'Tipo', 'Ing. SOLMAR', 'Ing. Planilla', 'Cliente', 'Cargo', 'Escaneo', 'Brevete', 'Clase', 'Categoría', 'Fec. Exp.', 'Fec. Reval.',
            'Restricción']];
        body = datos.map((r, i) => [
            c(i + 1), c(r.CODIGO), c(r.PERSONAL, '', false), c(r.TIPO_DOCU), c(r.NRO_DOCU_IDEN),
            c(r.TIPO), c(r.INGRESO_SOLMAR), c(r.INGRESO_PLAN),
            c(r.CLIENTE, '', false), c(r.CARGO, '', false), pdfEsc(r.ESCANEO),
            c(r.PERS_BREVETE), c(r.CLASE_BREVETE), c(r.CATEGORIA_BREVETE),
            c(r.FECH_EXP_BREVETE), c(r.FECH_REVAL_BREVETE), c(r.RESTRICCION_BREVETE),
        ]);
        columnStyles = {
            0: { cellWidth: 5, halign: 'center' }, 1: { cellWidth: 8, halign: 'center' }, 2: { cellWidth: 26 },
            3: { cellWidth: 11, halign: 'center' }, 4: { cellWidth: 14, halign: 'center' }, 5: { cellWidth: 11, halign: 'center' },
            6: { cellWidth: 14, halign: 'center' }, 7: { cellWidth: 14, halign: 'center' },
            8: { cellWidth: 22 }, 9: { cellWidth: 22 }, 10: { cellWidth: 12, halign: 'center' },
            11: { cellWidth: 14, halign: 'center' }, 12: { cellWidth: 10, halign: 'center' }, 13: { cellWidth: 13, halign: 'center' },
            14: { cellWidth: 14, halign: 'center' }, 15: { cellWidth: 14, halign: 'center' }, 16: { cellWidth: 14, halign: 'center' },
        };
    }

    autoTable(doc, {
        startY: y, head, body,
        styles: { fontSize: 5.5, cellPadding: { top: 1.5, bottom: 1.5, left: 1, right: 1 }, lineColor: [200, 200, 210], lineWidth: 0.15, overflow: 'ellipsize', minCellHeight: 7 },
        headStyles: { fillColor: [15, 23, 80], textColor: [255, 255, 255], fontStyle: 'bold', halign: 'center', fontSize: 5.5, cellPadding: { top: 2, bottom: 2, left: 1, right: 1 }, valign: 'middle' },
        alternateRowStyles: { fillColor: [248, 249, 252] },
        columnStyles, margin: { left: M, right: M }, theme: 'grid',
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
    link.download = `vigencia_${tipo.toLowerCase()}_${sucNombre.replace(/ /g, '_').toLowerCase()}.pdf`;
    link.click();
}

// ── Exportar Excel ──────────────────────────────────────────────────────────

function exportarExcel() {
    if (!datos) return;
    const tipo = document.getElementById('filtroVigDocumento').value;
    const sucNombre = getSelText('filtroVigSucursal');
    const vigente = document.querySelector('input[name="filtroVigEstado"]:checked')?.value ?? 'NO';
    const estadoLabel = vigente === 'SI' ? 'VIGENTES' : 'NO VIGENTES';
    const fecha = new Date().toLocaleDateString('es-PE');
    const esBrevete = tipo === 'BREVETE';
    const colSpan = esBrevete ? 17 : 12;
    const title = `${tipo} ${estadoLabel} — SUCURSAL ${sucNombre.toUpperCase()} AL ${fecha}`;

    const BASE = 'border:1px solid #d1d5db;padding:3px 5px;font-size:8.5pt;';
    const TH = 'background:#0f1750;color:#fff;font-weight:bold;text-align:center;vertical-align:middle;border:1px solid #1e3a5f;padding:4px;font-size:8pt;white-space:nowrap';
    const th = t => `<th style="${TH}">${t}</th>`;

    function tdEsc(val) {
        const v = (val ?? '').toString().toUpperCase().trim();
        if (v === 'SI') return `<td style="${BASE}background:#d1fae5;color:#065f46;font-weight:bold;text-align:center">SI</td>`;
        if (v === 'NO') return `<td style="${BASE}background:#fee2e2;color:#991b1b;font-weight:bold;text-align:center">NO</td>`;
        if (v === 'CADUCADO') return `<td style="${BASE}background:#fef3c7;color:#92400e;font-weight:bold;text-align:center">CADUCADO</td>`;
        return `<td style="${BASE}text-align:center">${val ?? ''}</td>`;
    }

    let html = `<html xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns="http://www.w3.org/TR/REC-html40">
          <head><meta charset="UTF-8">
          <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets>
          <x:ExcelWorksheet><x:Name>Vigencia ${tipo}</x:Name>
          <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
          </x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
          </head><body>
          <table border="0" cellspacing="0" cellpadding="0">
          <tr><td colspan="${colSpan}" style="font-size:11pt;font-weight:bold;text-align:center;padding:6px;border:none">${title}</td></tr>
          <tr>
              ${th('IT')}${th('Cód.')}${th('Personal')}${th('Tipo Doc.')}${th('Doc. Iden.')}${th('Tipo')}
              ${th('Ing. SOLMAR')}${th('Ing. Planilla')}
              ${!esBrevete ? th('Fec. Caduca') : ''}
              ${th('Cliente')}${th('Cargo')}${th('Escaneo')}
              ${esBrevete ? th('Brevete') + th('Clase') + th('Categoría') + th('Fec. Exp.') + th('Fec. Reval.') + th('Restricción') : ''}
          </tr>`;

    datos.forEach((r, i) => {
        const alt = i % 2 !== 0 ? 'background:#f8f9fc;' : '';
        const tdN = (v, left = false) => `<td style="${BASE}${left ? '' : 'text-align:center;'}${alt}">${v ?? ''}</td>`;
        html += `<tr>
              ${tdN(i + 1)} ${tdN(r.CODIGO)} ${tdN(r.PERSONAL, true)} ${tdN(r.TIPO_DOCU)} ${tdN(r.NRO_DOCU_IDEN)} ${tdN(r.TIPO)}
              ${tdN(r.INGRESO_SOLMAR)} ${tdN(r.INGRESO_PLAN)}
              ${!esBrevete ? tdN(r.PERS_FECHCADUCADNI) : ''}
              ${tdN(r.CLIENTE, true)} ${tdN(r.CARGO, true)}
              ${tdEsc(r.ESCANEO)}
              ${esBrevete ? `${tdN(r.PERS_BREVETE)} ${tdN(r.CLASE_BREVETE)} ${tdN(r.CATEGORIA_BREVETE)} ${tdN(r.FECH_EXP_BREVETE)} ${tdN(r.FECH_REVAL_BREVETE)} ${tdN(r.RESTRICCION_BREVETE)}` : ''}
          </tr>`;
    });

    html += `</table></body></html>`;
    const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `vigencia_${tipo.toLowerCase()}_${sucNombre.replace(/ /g, '_').toLowerCase()}.xls`;
    link.click();
}

// ── Memorandum PDF ──────────────────────────────────────────────────────────

function generarMemorandum() {
    if (!datos || !datos.length) return;

    const tipo = document.getElementById('filtroVigDocumento').value;
    const sucNombre = getSelText('filtroVigSucursal');

    // ── Firmante — editar aquí cuando se defina ──
    const FIRMANTE_NOMBRE = 'NOMBRE DEL FIRMANTE';
    const FIRMANTE_CARGO = `ADMINISTRADOR SUCURSAL ${sucNombre.toUpperCase()}`;

    const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    const hoy = new Date();
    const fechaStr = `${hoy.getDate()} de ${meses[hoy.getMonth()]} del ${hoy.getFullYear()}`;
    const anio = hoy.getFullYear();

    const doc = new jsPDF({ orientation: 'portrait', format: 'a4' });
    const PW = doc.internal.pageSize.getWidth();
    const PH = doc.internal.pageSize.getHeight();
    const ML = 22;
    const MR = 22;
    const CW = PW - ML - MR;
    const LH = 5.2;

    const asunto = tipo === 'DNI'
        ? 'Actualización de Documento Nacional de Identidad (DNI)'
        : 'Actualización de Brevete de Conducir';

    function obtenerParrafos(cliente) {
        const ent = cliente || 'la empresa cliente';
        if (tipo === 'DNI') return [
            `Como es de su conocimiento, ${ent}, para poder destacar personal de vigilancia privada a las instalaciones de sus clientes, debe cumplir con el requisito previo de mantener la documentación de
  su personal totalmente vigente.`,
            `En tal sentido, esta Administración ha tomado conocimiento a través de la Oficina de Recursos Humanos, que su Documento Nacional de Identidad (DNI), se encuentra vencido, por lo que se le otorga
   el plazo de 15 días hábiles a partir de la recepción de la presente para regularizar dicho documento, a fin de continuar laborando normalmente.`,
            `Cabe mencionar que la carencia del DNI vigente, no solamente podría afectar su desempeño laboral, sino que tampoco podrá hacer uso de sus facultades civiles, tales como celebrar contratos de
  índole laboral, civil o mercantil, además de la imposibilidad de ser atendido en centros de salud y otros, establecidos en el Reglamento De Inscripciones Del Registro Nacional De Identificación Y Estado
  Civil Artículo 84, inciso a) los casos en que la persona requiera acreditar su identidad; inciso f) celebrar cualquier tipo de contrato; inciso i) Inscribirse en cualquier sistema de seguridad o previsión
  social y la Ley Reniec Nº 26497.`,
            `Por lo expuesto, lo invitamos a que nos presente un DNI vigente, antes del plazo otorgado, para ser ingresado a nuestro sistema y pueda continuar desempeñándose normalmente como colaborador de
  nuestra empresa.`,
        ];
        return [
            `Como es de su conocimiento, ${ent}, para poder destacar personal de vigilancia privada a las instalaciones de sus clientes, debe cumplir con el requisito previo de mantener la documentación de
  su personal totalmente vigente.`,
            `En tal sentido, esta Administración ha tomado conocimiento a través de la Oficina de Recursos Humanos, que su Brevete de Conducir se encuentra vencido, por lo que se le otorga el plazo de 15
  días hábiles a partir de la recepción de la presente para regularizar dicho documento, a fin de continuar laborando normalmente.`,
            `Por lo expuesto, lo invitamos a que nos presente el Brevete vigente, antes del plazo otorgado, para ser ingresado a nuestro sistema y pueda continuar desempeñándose normalmente como colaborador
  de nuestra empresa.`,
        ];
    }

    datos.forEach((r, idx) => {
        if (idx > 0) doc.addPage();

        let y = 14;

        // ── Espacio logo (reservado) ──
        doc.setDrawColor(190); doc.setLineWidth(0.3);
        doc.setLineDash([1.5, 1.5]);
        doc.rect(ML, y, 18, 14);
        doc.setFontSize(5.5); doc.setTextColor(190);
        doc.text('LOGO', ML + 9, y + 8, { align: 'center' });
        doc.setLineDash([]); doc.setTextColor(0);

        // Nombre empresa
        doc.setFontSize(11); doc.setFont(undefined, 'bold');
        doc.text('SOL SECURITY SAC', ML + 22, y + 5);
        doc.setFontSize(7); doc.setFont(undefined, 'italic');
        doc.text('Seguros de la Seguridad', ML + 22, y + 10);
        doc.setFont(undefined, 'normal');

        // Línea header
        y += 18;
        doc.setDrawColor(0); doc.setLineWidth(0.5);
        doc.line(ML, y, PW - MR, y);

        y += 13;

        // ── Título ──
        const titulo = `MEMORANDUM Nº          -${anio}/ADM ${sucNombre.toUpperCase()}`;
        doc.setFontSize(11.5); doc.setFont(undefined, 'bold');
        doc.text(titulo, PW / 2, y, { align: 'center' });
        const tw = doc.getTextWidth(titulo);
        doc.setLineWidth(0.35);
        doc.line(PW / 2 - tw / 2, y + 1.3, PW / 2 + tw / 2, y + 1.3);

        y += 14;

        // ── Campos ──
        const LX = ML;
        const CX = ML + 17;   // ":"
        const VX = ML + 21;   // valor

        doc.setFontSize(10);

        // Para
        doc.setFont(undefined, 'normal'); doc.text('Para', LX, y);
        doc.text(':', CX, y);
        doc.setFont(undefined, 'bold');
        doc.text(`Sr. ${r.PERSONAL ?? ''}`, VX, y);
        y += LH;
        doc.setFont(undefined, 'normal');
        doc.text(r.CARGO ?? '', VX, y);
        y += LH + 3;

        // De
        doc.setFont(undefined, 'normal'); doc.text('De', LX, y);
        doc.text(':', CX, y);
        doc.setFont(undefined, 'bold');
        doc.text(FIRMANTE_NOMBRE, VX, y);
        y += LH;
        doc.setFont(undefined, 'normal');
        doc.text(FIRMANTE_CARGO, VX, y);
        y += LH + 3;

        // Fecha
        doc.text('Fecha', LX, y);
        doc.text(':', CX, y);
        doc.text(fechaStr, VX, y);
        y += LH + 3;

        // Asunto
        doc.text('Asunto', LX, y);
        doc.text(':', CX, y);
        doc.text(asunto, VX, y);
        y += LH + 5;

        // ── Doble separador ──
        doc.setLineWidth(0.9); doc.line(ML, y, PW - MR, y);
        y += 2.5;
        doc.setLineWidth(0.3); doc.line(ML, y, PW - MR, y);
        y += 9;

        // ── Párrafos ──
        doc.setFont(undefined, 'normal'); doc.setFontSize(10);
        const numX = ML + 1;
        const txtX = ML + 8;
        const txtW = CW - 8;

        obtenerParrafos(r.CLIENTE).forEach((texto, i) => {
            doc.text(`${i + 1}.`, numX, y);
            const lineas = doc.splitTextToSize(texto, txtW);
            doc.text(lineas, txtX, y, { align: 'justify', maxWidth: txtW });
            y += lineas.length * LH + 7;
        });

        y += 6;

        // ── Atentamente ──
        doc.text('Atentamente,', PW / 2, y, { align: 'center' });

        y += 24;

        // ── Firma ──
        const sigW = 78;
        doc.setLineWidth(0.4);
        doc.line(PW / 2 - sigW / 2, y, PW / 2 + sigW / 2, y);
        y += 5;
        doc.setFont(undefined, 'bold');
        doc.text(FIRMANTE_NOMBRE, PW / 2, y, { align: 'center' });
        y += 5.5;
        doc.setFont(undefined, 'normal');
        doc.text(FIRMANTE_CARGO, PW / 2, y, { align: 'center' });
    });

    doc.save(`memorandum_${tipo.toLowerCase()}_${sucNombre.replace(/ /g, '_').toLowerCase()}.pdf`);
}