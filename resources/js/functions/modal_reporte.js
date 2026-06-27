// ============================================================
// MÓDULO: Modal Reporte — Datos Generales del Personal
// Requiere: axios, Swal, jspdf + jspdf-autotable (ya en la vista)
// ============================================================
import axios from 'axios';
import Swal from 'sweetalert2';

(function initModalReporte() {

    // ── Referencias DOM ───────────────────────────────────────
    const modal        = document.getElementById('modalReporte');
    const btnAbrir     = document.getElementById('btnAbrirReporte');
    const btnCerrar    = document.getElementById('btnCerrarReporte');
    const btnBuscar    = document.getElementById('btnBuscarReporte');
    const tbody        = document.getElementById('rptTbody');
    const cbAll        = document.getElementById('rptCbAll');
    const selInfo      = document.getElementById('rptSelInfo');
    const pagInfo      = document.getElementById('rptPagInfo');
    const pageNum      = document.getElementById('rptPageNum');
    const btnPrev      = document.getElementById('rptBtnPrev');
    const btnNext      = document.getElementById('rptBtnNext');
    const pageSizeSel  = document.getElementById('rptPageSize');
    const lblVig       = document.getElementById('rptLblVig');
    const lblNoVig     = document.getElementById('rptLblNoVig');
    const btnDescFoto  = document.getElementById('btnRptDescFoto');
    const btnDescDNI   = document.getElementById('btnRptDescDNI');
    const btnDescCUL   = document.getElementById('btnRptDescCUL');
    const btnExportPDF = document.getElementById('btnRptExportPDF');
    const btnExportXLS = document.getElementById('btnRptExportExcel');
    const selSucursal  = document.getElementById('rptSucursal');
    const selTipo      = document.getElementById('rptTipoPer');
    const inputApPat   = document.getElementById('rptApPaterno');
    const inputDoc     = document.getElementById('rptDocIdentidad');

    if (!modal) return;

    // ── Estado ────────────────────────────────────────────────
    let filtrados       = [];
    let paginaActual    = 1;
    let tamanoPagina    = 20;
    const seleccionados = new Set();

    // ── Constantes ────────────────────────────────────────────
    const TITULO_REPORTE  = 'DATOS BASICOS DEL PERSONAL';
    const EMPRESA_REPORTE = 'Sol Security S.A.C.';

    // Columnas para exportar PDF/Excel (sin checkbox)
    const COLUMNAS_EXPORT = [
        { label: 'It.',              key: '_it' },
        { label: 'Sucursal',         key: 'sucursal' },
        { label: 'Cód.',             key: 'codPersonal' },
        { label: 'Apellidos y Nombres', key: 'nombre' },
        { label: 'País',             key: 'nacionalidad' },
        { label: 'Tipo Doc.',        key: 'tipoDoc' },
        { label: 'Doc. Iden.',       key: 'nroDocIden' },
        { label: 'Caduca Doc.',      key: 'cadDni' },
        { label: 'Sexo',             key: 'sexo' },
        { label: 'Edad',             key: 'edad' },
        { label: 'Email',            key: 'email' },
        { label: 'Teléfonos',        key: 'telefono' },
        { label: 'Dirección Actual', key: 'direccion' },
        { label: 'Fecha Ingreso',    key: 'fechIngreso' },
        { label: 'Cargo',            key: 'cargo' },
        { label: 'Tipo Pers.',       key: 'tipoPer' },
        { label: 'Caduca EMO',       key: 'caducaEmo' },
        { label: 'Fin Contrato',     key: 'finContrato' },
    ];

    // ── Abrir / cerrar ────────────────────────────────────────
    function abrirModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    btnAbrir?.addEventListener('click', abrirModal);
    btnCerrar?.addEventListener('click', cerrarModal);
    modal?.addEventListener('click', e => { if (e.target === modal) cerrarModal(); });

    // ── Fetch ─────────────────────────────────────────────────
    async function fetchPersonal() {
        const codSucursal  = selSucursal?.value.trim()  ?? '';
        const tipoPer      = selTipo?.value.trim()      ?? '';
        const vigente      = document.querySelector('input[name="rptVigente"]:checked')?.value ?? '1';
        const apPaterno    = inputApPat?.value.trim()   ?? '';
        const docIdentidad = inputDoc?.value.trim()     ?? '';

        Swal.fire({ title: 'Cargando registros...', allowOutsideClick: false, allowEscapeKey: false, didOpen: () => Swal.showLoading() });

        try {
            const response = await axios.get(`${VITE_URL_APP}/api/dj/reporte-personal-datos-generales`, {
                params: { codSucursal, tipoPer, vigente, apPaterno, docIdentidad }
            });

            const raw = response.data?.data ?? response.data;

            filtrados = raw.map((r, i) => ({
                _it:          i + 1,
                codPersonal:  trim(r.codPersonal  ?? r.CODI_PERS    ?? ''),
                codSucursal:  trim(r.codSucursal  ?? r.SUCU_CODIGO  ?? ''),
                sucursal:     trim(r.sucursal     ?? '—'),
                nombre:       r.nombre ?? trim(
                    `${r.NOMB_1??''} ${r.NOMB_2??''} ${r.APEL_1??''} ${r.APEL_2??''}`
                ),
                nacionalidad: trim(r.nacionalidad ?? r.NACIONALIDAD ?? 'PERU'),
                tipoDoc:      trim(r.tipoDoc      ?? r.CODI_TIPO_DOCU ?? 'DNI'),
                nroDocIden:   trim(r.nroDocIden   ?? r.NRO_DOCU_IDEN  ?? '—'),
                cadDni:       r.cadDni      ?? formatFecha(r.PERS_FECHCADUCADNI),
                sexo:         trim(r.sexo         ?? r.PERS_SEXO  ?? '—'),
                edad:         r.edad        ?? calcularEdad(r.FECH_NACI),
                email:        trim(r.email        ?? r.PERS_EMAIL ?? '—'),
                telefono:     trim(r.telefono     ?? r.PERS_TELEFONO ?? '—'),
                direccion:    trim(r.direccion    ?? r.DIRECCION  ?? '—'),
                fechIngreso:  r.fechIngreso  ?? formatFecha(r.FECH_INGRE),
                cargo:        trim(r.cargo        ?? r.CODI_CARG  ?? '—'),
                tipoPer:      r.tipoPer      ?? mapTipoTexto(r.PERS_TIPOTRAB),
                caducaEmo:    r.caducaEmo    ?? formatFecha(r.VCMTO) ?? '—',
                finContrato:  r.finContrato  ?? formatFecha(r.FECH_CESE) ?? '—',
            }));

            // Reasignar _it tras filtrado
            filtrados.forEach((r, i) => r._it = i + 1);

            Swal.close();
            paginaActual = 1;
            seleccionados.clear();
            renderTabla();

        } catch (err) {
            console.error('Error cargando reporte:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el reporte.' });
        }
    }

    // ── Helpers ───────────────────────────────────────────────
    function trim(val) { return String(val ?? '').trim(); }

    function formatFecha(valor) {
        if (!valor) return '—';
        const str = String(valor);
        const m = str.match(/^(\d{4})-(\d{2})-(\d{2})/);
        return m ? `${m[3]}/${m[2]}/${m[1]}` : str.split('T')[0];
    }

    function calcularEdad(fechNaci) {
        if (!fechNaci) return '—';
        const naci = new Date(fechNaci);
        const hoy  = new Date();
        let edad   = hoy.getFullYear() - naci.getFullYear();
        const m    = hoy.getMonth() - naci.getMonth();
        if (m < 0 || (m === 0 && hoy.getDate() < naci.getDate())) edad--;
        return isNaN(edad) ? '—' : edad;
    }

    function mapTipoTexto(val) {
        const v = String(val ?? '').trim().toUpperCase();
        if (v === 'OP' || v === '03') return 'OPERATIVO';
        if (v === 'AD' || v === '05') return 'ADMINISTRATIVO';
        return v || '—';
    }

    // ── Render tabla ──────────────────────────────────────────
    function renderTabla() {
        const inicio = (paginaActual - 1) * tamanoPagina;
        const slice  = filtrados.slice(inicio, inicio + tamanoPagina);

        if (!slice.length) {
            tbody.innerHTML = `<tr><td colspan="19" class="text-center py-12 text-gray-300 italic text-xs">Sin resultados para los filtros aplicados</td></tr>`;
            actualizarFooter(0, 0, 0);
            return;
        }

        tbody.innerHTML = slice.map((r, i) => {
            const chk    = seleccionados.has(r.codPersonal) ? 'checked' : '';
            const tipoBadge = (() => {
                const t = String(r.tipoPer ?? '').toUpperCase();
                if (t.includes('OPERATIVO') && t.includes('4'))
                    return `<span class="inline-flex rounded-full border border-blue-200 bg-blue-50 text-blue-600 px-1.5 py-0.5 text-[9px] font-medium whitespace-nowrap">OPER 4°</span>`;
                if (t.includes('OPERATIVO') && t.includes('5'))
                    return `<span class="inline-flex rounded-full border border-blue-300 bg-blue-100 text-blue-800 px-1.5 py-0.5 text-[9px] font-medium whitespace-nowrap">OPER 5°</span>`;
                if (t.includes('ADMINISTRATIVO') && t.includes('4'))
                    return `<span class="inline-flex rounded-full border border-purple-200 bg-purple-50 text-purple-600 px-1.5 py-0.5 text-[9px] font-medium whitespace-nowrap">ADM 4°</span>`;
                if (t.includes('ADMINISTRATIVO') && t.includes('5'))
                    return `<span class="inline-flex rounded-full border border-purple-300 bg-purple-100 text-purple-800 px-1.5 py-0.5 text-[9px] font-medium whitespace-nowrap">ADM 5°</span>`;
                if (t.includes('ESPECIAL'))
                    return `<span class="inline-flex rounded-full border border-amber-300 bg-amber-100 text-amber-800 px-1.5 py-0.5 text-[9px] font-medium whitespace-nowrap">ESP</span>`;
                return `<span class="inline-flex rounded-full border border-gray-300 bg-gray-100 text-gray-600 px-1.5 py-0.5 text-[9px] font-medium whitespace-nowrap">${r.tipoPer ?? '—'}</span>`;
            })();

            return `<tr class="border-b border-gray-100 hover:bg-blue-50/40 transition-colors ${seleccionados.has(r.codPersonal) ? 'bg-blue-50' : ''}">
                <td class="py-1 px-1 text-center">
                    <input type="checkbox" class="rpt-cb-row w-3.5 h-3.5 accent-primary cursor-pointer" data-cod="${r.codPersonal}" ${chk}>
                </td>
                <td class="py-1 px-1 text-center text-gray-400">${inicio + i + 1}</td>
                <td class="py-1 px-1 font-medium whitespace-nowrap">${r.sucursal}</td>
                <td class="py-1 px-1 text-gray-500 whitespace-nowrap">${r.codPersonal}</td>
                <td class="py-1 px-1 font-medium whitespace-nowrap" title="${r.nombre}">${r.nombre}</td>
                <td class="py-1 px-1 text-gray-600 whitespace-nowrap">${r.nacionalidad}</td>
                <td class="py-1 px-1 text-gray-600 whitespace-nowrap">${r.tipoDoc}</td>
                <td class="py-1 px-1 whitespace-nowrap">${r.nroDocIden}</td>
                <td class="py-1 px-1 whitespace-nowrap">${r.cadDni}</td>
                <td class="py-1 px-1 text-center whitespace-nowrap">${r.sexo}</td>
                <td class="py-1 px-1 text-center whitespace-nowrap">${r.edad}</td>
                <td class="py-1 px-1 text-gray-600 max-w-[130px] truncate" title="${r.email}">${r.email}</td>
                <td class="py-1 px-1 whitespace-nowrap">${r.telefono}</td>
                <td class="py-1 px-1 text-gray-600 max-w-[150px] truncate" title="${r.direccion}">${r.direccion}</td>
                <td class="py-1 px-1 whitespace-nowrap">${r.fechIngreso}</td>
                <td class="py-1 px-1 text-gray-600 max-w-[120px] truncate" title="${r.cargo}">${r.cargo}</td>
                <td class="py-1 px-1">${tipoBadge}</td>
                <td class="py-1 px-1 whitespace-nowrap">${r.caducaEmo}</td>
                <td class="py-1 px-1 whitespace-nowrap">${r.finContrato}</td>
            </tr>`;
        }).join('');

        actualizarFooter(inicio + 1, Math.min(inicio + tamanoPagina, filtrados.length), filtrados.length);
        actualizarCbAll();
        actualizarSelInfo();
        bindCheckboxes();
    }

    function actualizarFooter(desde, hasta, total) {
        pagInfo.textContent = total > 0 ? `Mostrando ${desde}–${hasta} de ${total}` : 'Sin resultados';
        pageNum.textContent = paginaActual;
        btnPrev.disabled    = paginaActual <= 1;
        btnNext.disabled    = paginaActual >= Math.ceil(total / tamanoPagina);
    }

    function actualizarCbAll() {
        const slice = filtrados.slice((paginaActual - 1) * tamanoPagina, paginaActual * tamanoPagina);
        cbAll.checked       = slice.length > 0 && slice.every(r => seleccionados.has(r.codPersonal));
        cbAll.indeterminate = !cbAll.checked && slice.some(r => seleccionados.has(r.codPersonal));
    }

    function actualizarSelInfo() {
        selInfo.textContent = `${seleccionados.size} seleccionado${seleccionados.size === 1 ? '' : 's'}`;
    }

    function bindCheckboxes() {
        tbody.querySelectorAll('.rpt-cb-row').forEach(cb => {
            cb.addEventListener('change', function () {
                this.checked ? seleccionados.add(this.dataset.cod) : seleccionados.delete(this.dataset.cod);
                this.closest('tr').classList.toggle('bg-blue-50', this.checked);
                actualizarCbAll();
                actualizarSelInfo();
            });
        });
    }

    cbAll?.addEventListener('change', function () {
        filtrados.slice((paginaActual - 1) * tamanoPagina, paginaActual * tamanoPagina)
            .forEach(r => this.checked ? seleccionados.add(r.codPersonal) : seleccionados.delete(r.codPersonal));
        renderTabla();
    });

    // ── Paginación ────────────────────────────────────────────
    btnPrev?.addEventListener('click', () => { if (paginaActual > 1) { paginaActual--; renderTabla(); } });
    btnNext?.addEventListener('click', () => {
        if (paginaActual < Math.ceil(filtrados.length / tamanoPagina)) { paginaActual++; renderTabla(); }
    });
    pageSizeSel?.addEventListener('change', function () { tamanoPagina = parseInt(this.value); paginaActual = 1; renderTabla(); });

    // ── Buscar ────────────────────────────────────────────────
    btnBuscar?.addEventListener('click', fetchPersonal);
    [inputApPat, inputDoc].forEach(inp => inp?.addEventListener('keyup', e => { if (e.key === 'Enter') fetchPersonal(); }));

    // ── Radio vigente ─────────────────────────────────────────
    document.querySelectorAll('input[name="rptVigente"]').forEach(r => {
        r.addEventListener('change', function () {
            const esVig = this.value === '1';
            lblVig?.classList.toggle('bg-primary',    esVig);
            lblVig?.classList.toggle('text-white',    esVig);
            lblVig?.classList.toggle('bg-white',      !esVig);
            lblVig?.classList.toggle('text-gray-500', !esVig);
            lblNoVig?.classList.toggle('bg-primary',    !esVig);
            lblNoVig?.classList.toggle('text-white',    !esVig);
            lblNoVig?.classList.toggle('bg-white',      esVig);
            lblNoVig?.classList.toggle('text-gray-500', esVig);
        });
    });

    // ── Descargas archivos ────────────────────────────────────
    const BASE_URL = 'http://190.116.178.163/Biblioteca_Grafica';

    const URLS_TIPO = {
        foto: cod => [`${BASE_URL}/Fotos/${cod}.jpg`],
        dni:  cod => [
            `${BASE_URL}/DNI1_1/${cod}.jpg`,
            `${BASE_URL}/DNI1_2/${cod}.jpg`,
        ],
        cul:  cod => [`${BASE_URL}/CERTIFICADOS/CUL/${cod}.jpg`],
    };

    function getSeleccionados() { return filtrados.filter(r => seleccionados.has(r.codPersonal)); }
    function getModoDescarga()  { return document.querySelector('input[name="rptDescCon"]:checked')?.value ?? 'doc'; }
    function alertarSinSel()    { Swal.fire({ icon: 'warning', title: 'Sin selección', text: 'Marca al menos un registro.' }); }

    function getNombreBase(reg) {
        return getModoDescarga() === 'doc' ? reg.nroDocIden : reg.codPersonal;
    }

    function triggerDownload(blob, nombre) {
        const url = URL.createObjectURL(blob);
        const a   = document.createElement('a');
        a.href     = url;
        a.download = nombre;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // Proxy Laravel para evitar CORS
    async function fetchImagen(urlRemota) {
        try {
            const proxyUrl = `${VITE_URL_APP}/api/dj/reporte/proxy-imagen?url=${encodeURIComponent(urlRemota)}`;
            const res = await fetch(proxyUrl, { signal: AbortSignal.timeout(12000) });
            if (!res.ok) return null;
            const blob = await res.blob();
            if (!blob || blob.size === 0) return null;
            return blob;
        } catch (e) {
            console.error('fetchImagen error:', e);
            return null;
        }
    }

    function nombreUnico(nombre, ext, usedNames) {
        let candidato = `${nombre}.${ext}`;
        let contador  = 1;
        while (usedNames.has(candidato)) {
            candidato = `${nombre}_${contador}.${ext}`;
            contador++;
        }
        usedNames.add(candidato);
        return candidato;
    }

    async function descargarArchivos(tipo) {
        const regs = getSeleccionados();
        if (!regs.length) { alertarSinSel(); return; }

        const etiquetas = { foto: 'Foto', dni: 'DNI', cul: 'CUL' };
        const label     = etiquetas[tipo];
        const esMultiple = regs.length > 1;

        const { isConfirmed } = await Swal.fire({
            icon: 'question',
            title: `Descargar ${label}`,
            html: esMultiple
                ? `Se generará un <b>ZIP</b> con los archivos de <b>${regs.length}</b> registro(s) seleccionado(s).`
                : `Se descargará el archivo de <b>1</b> registro seleccionado.`,
            showCancelButton: true,
            confirmButtonText: 'Sí, descargar',
            cancelButtonText:  'Cancelar',
        });
        if (!isConfirmed) return;

        Swal.fire({
            title: esMultiple ? `Generando ZIP de ${label}s...` : `Descargando ${label}...`,
            html: `Procesando <b>0</b> de <b>${regs.length}</b>`,
            allowOutsideClick: false,
            allowEscapeKey:    false,
            didOpen: () => Swal.showLoading(),
        });

        const usedNames = new Set();
        let ok = 0, sinArchivo = 0, errores = [];

        // ZIP solo si hay más de 1 registro
        const zip = esMultiple ? new JSZip() : null;

        for (let i = 0; i < regs.length; i++) {
            const reg  = regs[i];
            const cod  = reg.codPersonal;
            const base = getNombreBase(reg);
            const urls = URLS_TIPO[tipo](cod);

            Swal.update({
                html: `Procesando <b>${i + 1}</b> de <b>${regs.length}</b><br>
                       <small class="text-gray-400">${reg.nombre}</small>`
            });

            let descargadoAlgo = false;

            for (let u = 0; u < urls.length; u++) {
                const blob = await fetchImagen(urls[u]);
                if (!blob) continue;

                const sufijo = tipo === 'dni'
                    ? (u === 0 ? '_anverso' : '_reverso')
                    : '';

                const nombreFinal = nombreUnico(`${base}${sufijo}`, 'jpg', usedNames);

                if (zip) {
                    // Múltiples: agregar al ZIP
                    const buffer = await blob.arrayBuffer();
                    zip.file(nombreFinal, buffer);
                } else {
                    // Solo 1: descarga directa
                    triggerDownload(blob, nombreFinal);
                }

                ok++;
                descargadoAlgo = true;
                await new Promise(r => setTimeout(r, 200));
            }

            if (!descargadoAlgo) {
                sinArchivo++;
                errores.push(reg.nombre);
            }
        }

        // Si es múltiple, generar y descargar el ZIP
        if (zip && ok > 0) {
            Swal.update({ html: 'Comprimiendo archivos...' });
            try {
                const f   = new Date();
                const ts  = f.getFullYear()
                    + String(f.getMonth() + 1).padStart(2, '0')
                    + String(f.getDate()).padStart(2, '0')
                    + '_' + String(f.getHours()).padStart(2, '0')
                    + String(f.getMinutes()).padStart(2, '0');
                const zipBlob = await zip.generateAsync({ type: 'blob' });
                triggerDownload(zipBlob, `${label}_${ts}.zip`);
            } catch (e) {
                console.error('Error generando ZIP:', e);
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar el ZIP.' });
                return;
            }
        }

        const hayErrores = errores.length > 0;
        Swal.fire({
            icon:  hayErrores && ok === 0 ? 'error' : hayErrores ? 'warning' : 'success',
            title: 'Descarga completada',
            html: `
                <div style="text-align:left;font-size:13px;line-height:1.8">
                    <div>✅ Archivos: <b>${ok}</b>${esMultiple ? ' — descargados en ZIP' : ''}</div>
                    ${sinArchivo > 0 ? `
                    <div>⚠️ Sin archivo: <b>${sinArchivo}</b></div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:4px;">
                        ${errores.map(n => `• ${n}`).join('<br>')}
                    </div>` : ''}
                </div>
            `,
        });
    }

    btnDescFoto?.addEventListener('click', () => descargarArchivos('foto'));
    btnDescDNI?.addEventListener('click',  () => descargarArchivos('dni'));
    btnDescCUL?.addEventListener('click',  () => descargarArchivos('cul'));

    // ── EXPORTAR PDF ──────────────────────────────────────────
    btnExportPDF?.addEventListener('click', async function () {
        if (!filtrados.length) { Swal.fire({ icon: 'warning', title: 'Sin datos', text: 'Realiza una búsqueda antes de exportar.' }); return; }

        try {
            const { jsPDF } = window.jspdf;
            // Oficio landscape (216 x 356 mm) — más ancho que A4 para que entren las 18 columnas
            const doc   = new jsPDF({ orientation: 'landscape', unit: 'mm', format: [216, 356] });
            const pageW = doc.internal.pageSize.getWidth(); // 356mm
            let y = 12;

            // Título subrayado azul
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(0, 0, 180);
            doc.text(TITULO_REPORTE, pageW / 2, y, { align: 'center' });
            const tW = doc.getTextWidth(TITULO_REPORTE);
            doc.setDrawColor(0, 0, 180);
            doc.setLineWidth(0.3);
            doc.line((pageW - tW) / 2, y + 0.8, (pageW + tW) / 2, y + 0.8);
            y += 6;

            // Empresa
            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(0);
            doc.text(EMPRESA_REPORTE, pageW / 2, y, { align: 'center' });
            y += 5;

            const head = [COLUMNAS_EXPORT.map(c => c.label)];
            const body = filtrados.map((r, i) =>
                COLUMNAS_EXPORT.map(c => c.key === '_it' ? String(i + 1) : String(r[c.key] ?? '—'))
            );

            doc.autoTable({
                startY: y,
                head,
                body,
                styles: {
                    fontSize:    5.5,
                    cellPadding: 1.2,
                    overflow:    'linebreak',
                    valign:      'middle',
                    lineColor:   [210, 218, 240],
                    lineWidth:   0.1,
                },
                headStyles: {
                    fillColor:  [30, 64, 175],
                    textColor:  255,
                    fontStyle:  'bold',
                    fontSize:   6,
                    halign:     'center',
                },
                alternateRowStyles: { fillColor: [246, 248, 255] },
                margin: { left: 4, right: 4 },
                columnStyles: {
                    0:  { cellWidth: 5,  halign: 'center' }, // It
                    1:  { cellWidth: 12, halign: 'center' }, // Sucursal
                    2:  { cellWidth: 9,  halign: 'center' }, // Cód
                    3:  { cellWidth: 40 },                   // Nombres
                    4:  { cellWidth: 12 },                   // País
                    5:  { cellWidth: 8,  halign: 'center' }, // Tipo Doc
                    6:  { cellWidth: 18, halign: 'center' }, // Doc Iden
                    7:  { cellWidth: 16, halign: 'center' }, // Caduca Doc
                    8:  { cellWidth: 7,  halign: 'center' }, // Sexo
                    9:  { cellWidth: 7,  halign: 'center' }, // Edad
                    10: { cellWidth: 40 },                   // Email
                    11: { cellWidth: 20 },                   // Teléfono
                    12: { cellWidth: 48 },                   // Dirección
                    13: { cellWidth: 16, halign: 'center' }, // F. Ingreso
                    14: { cellWidth: 28 },                   // Cargo
                    15: { cellWidth: 14, halign: 'center' }, // Tipo Pers
                    16: { cellWidth: 16, halign: 'center' }, // Caduca EMO
                    17: { cellWidth: 16, halign: 'center' }, // Fin Contrato
                },
                didDrawPage: data => {
                    const total = doc.internal.getNumberOfPages();
                    doc.setFontSize(7);
                    doc.setTextColor(150);
                    doc.text(
                        `Página ${data.pageNumber} de ${total}`,
                        pageW - 8,
                        doc.internal.pageSize.getHeight() - 6,
                        { align: 'right' }
                    );
                    doc.setTextColor(0);
                },
            });

            doc.save(`DatosPersonal_${timestamp()}.pdf`);

        } catch (err) {
            console.error('Error exportando PDF:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar el PDF. Verifique que jsPDF esté cargado.' });
        }
    });

    // ── EXPORTAR EXCEL ────────────────────────────────────────
    btnExportXLS?.addEventListener('click', async function () {
        if (!filtrados.length) { Swal.fire({ icon: 'warning', title: 'Sin datos', text: 'Realiza una búsqueda antes de exportar.' }); return; }

        // Cargar xlsx-js-style que soporta estilos correctamente
        if (!window.XLSXStyle) {
            try {
                await new Promise((resolve, reject) => {
                    const s   = document.createElement('script');
                    s.src     = 'https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js';
                    s.onload  = () => { window.XLSXStyle = window.XLSX; resolve(); };
                    s.onerror = reject;
                    document.head.appendChild(s);
                });
            } catch {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar la librería de Excel.' });
                return;
            }
        }

        try {
            const XLSX = window.XLSXStyle;
            const cols = COLUMNAS_EXPORT.length;

            // ── Estilos ───────────────────────────────────────
            const borde = (color) => ({
                top:    { style: 'thin', color: { rgb: color } },
                bottom: { style: 'thin', color: { rgb: color } },
                left:   { style: 'thin', color: { rgb: color } },
                right:  { style: 'thin', color: { rgb: color } },
            });

            const estTitulo = {
                font:      { bold: true, sz: 14, color: { rgb: '1E40AF' } },
                alignment: { horizontal: 'center', vertical: 'center' },
            };
            const estEmpresa = {
                font:      { bold: true, sz: 11 },
                alignment: { horizontal: 'center', vertical: 'center' },
            };
            const estHeader = {
                font:      { bold: true, sz: 9, color: { rgb: 'FFFFFF' } },
                fill:      { patternType: 'solid', fgColor: { rgb: '1E40AF' } },
                alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
                border:    borde('FFFFFF'),
            };
            const estDato = {
                font:      { sz: 8 },
                alignment: { vertical: 'center' },
                border:    borde('D1D5DB'),
            };
            const estDatoAlt = {
                font:      { sz: 8 },
                fill:      { patternType: 'solid', fgColor: { rgb: 'EFF6FF' } },
                alignment: { vertical: 'center' },
                border:    borde('D1D5DB'),
            };

            // ── Datos ─────────────────────────────────────────
            const ws_data = [
                [TITULO_REPORTE],
                [EMPRESA_REPORTE],
                [],
                COLUMNAS_EXPORT.map(c => c.label),
                ...filtrados.map((r, i) =>
                    COLUMNAS_EXPORT.map(c => c.key === '_it' ? i + 1 : (r[c.key] ?? ''))
                ),
            ];

            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(ws_data);

            // ── Anchos ────────────────────────────────────────
            ws['!cols'] = [
                { wch: 5  }, { wch: 10 }, { wch: 8  }, { wch: 35 },
                { wch: 12 }, { wch: 8  }, { wch: 13 }, { wch: 13 },
                { wch: 5  }, { wch: 5  }, { wch: 32 }, { wch: 15 },
                { wch: 40 }, { wch: 12 }, { wch: 26 }, { wch: 14 },
                { wch: 13 }, { wch: 13 },
            ];

            // ── Altos ─────────────────────────────────────────
            ws['!rows'] = [{ hpt: 22 }, { hpt: 16 }, { hpt: 6 }, { hpt: 28 }];

            // ── Merges ────────────────────────────────────────
            ws['!merges'] = [
                { s: { r: 0, c: 0 }, e: { r: 0, c: cols - 1 } },
                { s: { r: 1, c: 0 }, e: { r: 1, c: cols - 1 } },
            ];

            // ── Helper: ref de celda ──────────────────────────
            const ref = (r, c) => XLSX.utils.encode_cell({ r, c });

            // Estilos título y empresa
            if (ws[ref(0, 0)]) ws[ref(0, 0)].s = estTitulo;
            if (ws[ref(1, 0)]) ws[ref(1, 0)].s = estEmpresa;

            // Encabezados (fila 3)
            for (let c = 0; c < cols; c++) {
                const cell = ref(3, c);
                if (ws[cell]) ws[cell].s = estHeader;
            }

            // Datos (fila 4 en adelante)
            filtrados.forEach((_, rowIdx) => {
                const est = rowIdx % 2 === 1 ? estDatoAlt : estDato;
                for (let c = 0; c < cols; c++) {
                    const cell = ref(4 + rowIdx, c);
                    if (ws[cell]) ws[cell].s = est;
                }
            });

            XLSX.utils.book_append_sheet(wb, ws, 'Datos Personal');
            XLSX.writeFile(wb, `DatosPersonal_${timestamp()}.xlsx`);

        } catch (err) {
            console.error('Error exportando Excel:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar el Excel.' });
        }
    });

    // ── Timestamp ─────────────────────────────────────────────
    function timestamp() {
        const f = new Date();
        return f.getFullYear()
            + String(f.getMonth() + 1).padStart(2, '0')
            + String(f.getDate()).padStart(2, '0')
            + '_'
            + String(f.getHours()).padStart(2, '0')
            + String(f.getMinutes()).padStart(2, '0');
    }

    // ── API pública ───────────────────────────────────────────
    window.ModalReporte = { abrir: abrirModal, cerrar: cerrarModal };

})();