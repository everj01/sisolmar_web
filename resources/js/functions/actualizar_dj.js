// ============================================================
// gestion_dj.js — Lógica principal del módulo Gestión DJ
// ============================================================
// PDF separado en: ./dj_pdf.js
// ============================================================
import axios from 'axios';
import Swal from 'sweetalert2';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';
import Tagify from '@yaireo/tagify';
import '@yaireo/tagify/dist/tagify.css';
import ExcelJS from 'exceljs'

import { generarDeclaracionJuradaPDF, generarReporteFaltantesPDF } from './dj_pdf.js';
import { generarPDF } from './chargefile/reporteAvancesPDF.js';
import { generarExcel } from './chargefile/reporteAvancesExcel.js';
import { obtenerDatos } from './chargefile/reporteAvances.js';

const API_URL = `${VITE_URL_APP}/api`;
let registroSeleccionado = null;

const categoriasSe = {
    'A': [
        { val: 'A-I', text: 'A-I: Particulares' },
        { val: 'A-IIa', text: 'A-IIa: Taxi / Ambulancia' },
        { val: 'A-IIb', text: 'A-IIb: Microbús / Pickup' },
        { val: 'A-IIIa', text: 'A-IIIa: Ómnibus' },
        { val: 'A-IIIb', text: 'A-IIIb: Camiones' },
        { val: 'A-IIIc', text: 'A-IIIc: Todos los anteriores' }
    ],
    'B': [
        { val: 'B-IIa', text: 'B-IIa: Bicimotos' },
        { val: 'B-IIb', text: 'B-IIb: Motocicletas' },
        { val: 'B-IIc', text: 'B-IIc: Mototaxis' }
    ]
};

const PAUSA_ENTRE_REGISTROS = 800;

async function esperarConBackoff(intento, baseMs = 1000) {
    const espera = baseMs * Math.pow(2, intento); // 1s, 2s, 4s, 8s...
    const jitter = Math.random() * 300;           // evita que todo reintente al mismo tiempo
    await new Promise(r => setTimeout(r, espera + jitter));
}

// ③ Wrapper para obtener datos con retry ante 429
async function obtenerDatosConRetry(codiPers, source = 'migracion', maxReintentos = 4) {
    for (let intento = 0; intento <= maxReintentos; intento++) {
        try {
            const response = await axios.get(`${API_URL}/dj/get-personal-data`, {
                params: { codi_pers: codiPers, source }
            });
            return response.data;
        } catch (err) {
            const status = err?.response?.status;

            if (status === 429 && intento < maxReintentos) {
                // Leer el header Retry-After si el servidor lo manda
                const retryAfter = err.response?.headers?.['retry-after'];
                const esperaMs = retryAfter
                    ? parseInt(retryAfter) * 1000
                    : null;

                console.warn(`[429] ${codiPers} — reintento ${intento + 1}/${maxReintentos}`);

                if (esperaMs) {
                    await new Promise(r => setTimeout(r, esperaMs + 200));
                } else {
                    await esperarConBackoff(intento);
                }
                continue;
            }

            // Si no es 429 o se agotaron reintentos, propagar el error
            throw err;
        }
    }
}


function actualizarCategorias() {
    const claseSel = document.getElementById('clase_brevete').value;
    const catSelect = document.getElementById('tipo_vehiculo');

    // Limpiar opciones previas
    catSelect.innerHTML = '<option value="">-- Seleccione Categoría --</option>';

    if (claseSel && categoriasSe[claseSel]) {
        categoriasSe[claseSel].forEach(item => {
            let opt = document.createElement('option');
            opt.value = item.val;
            opt.textContent = item.text;
            catSelect.appendChild(opt);
        });
    }
}


function marcarDJGeneradosBatch(items) {
    const data = getDJGenerados();

    items.forEach(({ codPersonal, fechaCambio }) => {
        data[codPersonal] = {
            fechaMarcado: new Date().toISOString(),
            fechaCambio: fechaCambio || null,
        };
    });

    localStorage.setItem(DJ_STORAGE_KEY, JSON.stringify(data));
}

// ============================================================
// DOCUMENT READY
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('clase_brevete').addEventListener('change', actualizarCategorias);

    // ============================================================
    // TIMELINE TABS (NUEVO)
    // ============================================================
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (btn.classList.contains('cursor-not-allowed')) return;

            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('bg-primary', 'text-white', 'active');
                b.classList.add('text-gray-500');
                if (b.dataset.target === 'etapa5') b.classList.add('border', 'border-gray-200');
            });
            document.querySelectorAll('.tab-content').forEach(c => {
                c.classList.add('hidden');
                c.classList.remove('active');
            });

            btn.classList.remove('text-gray-500');
            btn.classList.add('bg-primary', 'text-white', 'active');
            if (btn.dataset.target === 'etapa5') btn.classList.remove('border', 'border-gray-200');

            const targetId = btn.getAttribute('data-target');
            document.getElementById(targetId).classList.remove('hidden');
            document.getElementById(targetId).classList.add('active');

            if (targetId === 'etapa1' && typeof tblEtapa1 !== 'undefined') tblEtapa1.redraw();
            if (targetId === 'etapa2' && typeof tblPersonasVerificado !== 'undefined') tblPersonasVerificado.redraw();
            if (targetId === 'etapa3' && typeof tblPersonasEtapa3 !== 'undefined') tblPersonasEtapa3.redraw();
            if (targetId === 'etapa4' && typeof tblEtapa4 !== 'undefined') tblEtapa4.redraw();
            if (targetId === 'etapa5' && typeof tblPersonasMigrado !== 'undefined') tblPersonasMigrado.redraw();
        });
    });

    const tblEtapa1 = new Tabulator("#tblEtapa1", {
        height: "500px",
        layout: "fitColumns",
        responsiveLayout: "collapse",
        pagination: true,
        paginationSize: 20,
        locale: "es",
        // --- AQUÍ TRADUCIMOS EL PAGINADOR ---
        langs: {
            "es": {
                "pagination": {
                    "first": "Primero",
                    "prev": "Anterior",
                    "next": "Siguiente",
                    "last": "Último"
                }
            }
        },
        // ------------------------------------
        columns: [
            {
                title: "N°",
                hozAlign: "center",
                width: 60,
                formatter: function (cell) {
                    const table = cell.getTable();
                    const page = table.getPage();
                    const size = table.getPageSize();

                    // Si la paginación está activa, calcula el número continuo
                    if (page && size) {
                        return ((page - 1) * size) + cell.getRow().getPosition(true);
                    }

                    return cell.getRow().getPosition(true);
                }
            },
            {
                title: "Estado", field: "SIP_CAMBIO", hozAlign: "center", width: 140,
                formatter: cell => {
                    const val = cell.getValue();
                    const color = val === 'Ok' ? 'bg-green-100 text-green-800 border-green-300' : 'bg-red-100 text-red-800 border-red-300';
                    return `<span class="inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-bold tracking-wider ${color}">${val === 'Ok' ? 'ACTUALIZADO' : 'SIN ACTUALIZAR'}</span>`;
                }
            },
            { title: "Nombres", field: "NOMBRE", hozAlign: "left", widthGrow: 3 },
            { title: "DNI", field: "NRO_DOCU_IDEN", hozAlign: "center", width: 110 },
            { title: "Sucursal", field: "SUCURSAL", hozAlign: "center", widthGrow: 1 },
            {
                title: "Tipo", field: "TIPO_PER", hozAlign: "center", widthGrow: 2,
                formatter: cell => {
                    const val = cell.getValue() ?? '';
                    let color = 'border-gray-300 bg-gray-100 text-gray-800';
                    if (val.toUpperCase().includes('OPERATIVO')) { color = 'border-blue-300 bg-blue-100 text-blue-800'; }
                    else if (val.toUpperCase().includes('ADMINISTRATIVO')) { color = 'border-purple-300 bg-purple-100 text-purple-800'; }
                    else if (val.toUpperCase().includes('ESPECIAL')) { color = 'border-orange-300 bg-orange-100 text-orange-800'; }

                    return val ? `<span class="inline-flex items-center rounded-full border ${color} px-3 py-1 text-[11px] font-bold tracking-wider whitespace-nowrap">${val}</span>` : '—';
                }
            },
            {
                title: "Fecha de Ingreso",
                field: "FECH_INGRE",
                hozAlign: "center",
                widthGrow: 3,
                formatter: cell => {
                    const val = cell.getValue();
                    if (val && val !== 'sin cambios') {
                        const f = formatearFechaHora(val);
                        return `<div class="flex items-center justify-center gap-3 text-sm text-gray-700 whitespace-nowrap">
                            <span class="flex items-center gap-1"><i class='bx bx-calendar text-blue-500'></i> <span>${f.fecha}</span></span>
                            <span class="flex items-center gap-1"><i class='bx bx-time-five text-orange-500'></i> <span>${f.hora}</span></span>
                        </div>`;
                    }
                    return '—';
                }
            },
            {
                title: "Fecha de Actualización",
                field: "SIP_CREACION",
                hozAlign: "center",
                widthGrow: 3,
                formatter: cell => {
                    const val = cell.getValue();
                    if (val && val !== 'sin cambios') {
                        const f = formatearFechaHora(val);
                        return `<div class="flex items-center justify-center gap-3 text-sm text-gray-700 whitespace-nowrap">
                            <span class="flex items-center gap-1"><i class='bx bx-calendar text-blue-500'></i> <span>${f.fecha}</span></span>
                            <span class="flex items-center gap-1"><i class='bx bx-time-five text-orange-500'></i> <span>${f.hora}</span></span>
                        </div>`;
                    }
                    return '—';
                }
            }
        ],
    });

    //  NUEVA FUNCIÓN DE FILTRADO LOCAL
    function aplicarFiltrosLocalesE1() {
        const texto = document.getElementById('buscarPersonalE1')?.value.toLowerCase().trim() || '';
        const radioVal = document.querySelector('input[name="filtroEstadoE1"]:checked')?.value || 'null';

        let filtros = [];

        if (texto) {
            // Buscamos en Nombre Completo o en DNI
            filtros.push([
                { field: "NOMBRE", type: "like", value: texto },
                { field: "NRO_DOCU_IDEN", type: "like", value: texto }
            ]);
        }

        // Filtramos localmente por el Radio Button
        if (radioVal === '0') {
            filtros.push({ field: "SIP_CAMBIO", type: "=", value: "Ok" });
        } else if (radioVal === '1') {
            filtros.push({ field: "SIP_CAMBIO", type: "=", value: "Falta" });
        }

        tblEtapa1.setFilter(filtros);
        // Las cards ya no se actualizan aquí para que queden estáticas al filtrar
    }

    function cargarDatosEtapa1() {
        const codSucursal = document.getElementById('filtroSucursalE1')?.value || '00';
        const codTipoPer = document.getElementById('filtroTipoE1')?.value || '00';

        // Ya no enviamos el filtro del radio button a la BD (mandamos null) para traer SIEMPRE todo
        axios.get(`${VITE_URL_APP}/api/reporte-personal-sin-migracion-v2`, { params: { codSucursal, codTipoPer, tipo: null } })
            .then(response => {
                if (!response.data.success) return;

                const datosLimpios = response.data.data.filter(d => {
                    const tipoPersonal = d.TIPO_PER ? d.TIPO_PER.toUpperCase() : '';
                    return !tipoPersonal.includes('ESPECIAL');
                });

                // 1. Calculamos y fijamos las cards con LA DATA TOTAL (sin importar el filtro local)
                document.getElementById('countTotalE1').textContent = datosLimpios.length;
                document.getElementById('countActualizadosE1').textContent = datosLimpios.filter(d => d.SIP_CAMBIO === 'Ok').length;
                document.getElementById('countSinActualizarE1').textContent = datosLimpios.filter(d => d.SIP_CAMBIO === 'Falta').length;

                // 2. Mandamos la data a la tabla
                tblEtapa1.setData(datosLimpios);

                // 3. Aplicamos filtros locales por si hay un radio button o texto ya seleccionado
                aplicarFiltrosLocalesE1();
            });
    }

    document.getElementById('filtroSucursalE1')?.addEventListener('change', cargarDatosEtapa1);
    document.getElementById('filtroTipoE1')?.addEventListener('change', cargarDatosEtapa1);
    document.querySelectorAll('input[name="filtroEstadoE1"]').forEach(radio => radio.addEventListener('change', aplicarFiltrosLocalesE1));

    // 🔥 EVENTOS DEL BUSCADOR: Filtrar y Resaltar texto
    document.getElementById('buscarPersonalE1')?.addEventListener('keyup', function () {
        const valor = this.value.toLowerCase().trim();
        tblEtapa1._ultimoFiltro = valor; // Guardamos estado para re-renderizado
        aplicarFiltrosLocalesE1();
        setTimeout(() => resaltarTexto(tblEtapa1, valor), 10);
    });

    // Mantiene el resaltado amarillo si cambias de página en Tabulator
    tblEtapa1.on("renderComplete", () => {
        if (tblEtapa1._ultimoFiltro) resaltarTexto(tblEtapa1, tblEtapa1._ultimoFiltro);
    });
    // ============================================================
    // EXPORTACIÓN EXCEL Y PDF (PERSONALIZADO TIPO SISOLMAR)
    function getFiltrosTexto() {
        const selSucursal = document.getElementById('filtroSucursalE1');
        const selTipo = document.getElementById('filtroTipoE1');
        const txtSucursal = selSucursal.options[selSucursal.selectedIndex]?.text?.toUpperCase() || '';
        const txtTipo = selTipo.options[selTipo.selectedIndex]?.text?.toUpperCase() || '';

        return {
            sucursal: txtSucursal === 'TODAS' ? 'TODAS LAS SUCURSALES' : txtSucursal,
            tipo: txtTipo === 'TODOS' ? '' : txtTipo
        };
    }

    // EXPORTAR EXCEL PERSONALIZADO (Con ExcelJS - Incluyendo contadores)
    document.getElementById("btnExportExcelE1")?.addEventListener("click", async () => {
        let data = tblEtapa1.getData("active");
        if (!data.length) return Swal.fire('Sin datos', 'No hay datos para exportar', 'warning');

        // 🔥 FILTRO DE SEGURIDAD: Garantizamos que no pase ningún "ESPECIAL" al Excel
        data = data.filter(d => {
            const tipoPersonal = d.TIPO_PER ? d.TIPO_PER.toUpperCase() : '';
            return !tipoPersonal.includes('ESPECIAL');
        });

        // --- Calcular totales exactos basados estrictamente en la data a imprimir ---
        const totalRegistros = data.length;
        const totalActualizados = data.filter(d => d.SIP_CAMBIO === 'Ok').length;
        const totalSinActualizar = totalRegistros - totalActualizados;

        const filtros = getFiltrosTexto();
        const f = new Date();
        const fechaStr = `${String(f.getDate()).padStart(2, '0')}/${String(f.getMonth() + 1).padStart(2, '0')}/${f.getFullYear()} ${String(f.getHours()).padStart(2, '0')}:${String(f.getMinutes()).padStart(2, '0')}`;

        Swal.fire({ title: 'Generando Excel...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const workbook = new ExcelJS.Workbook();
            const worksheet = workbook.addWorksheet("1° Etapa");

            // 1. INCORPORAR LOGO
            if (window.logoUrl) {
                try {
                    const response = await fetch(window.logoUrl);
                    const blob = await response.blob();
                    const base64 = await new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.onloadend = () => resolve(reader.result);
                        reader.readAsDataURL(blob);
                    });
                    const imageId = workbook.addImage({ base64: base64, extension: 'png' });
                    worksheet.addImage(imageId, { tl: { col: 0, row: 0 }, ext: { width: 170, height: 50 } });
                } catch (e) { console.warn("No se pudo cargar logo", e); }
            }

            // 2. TÍTULOS
            worksheet.mergeCells('A1:G1');
            const title1 = worksheet.getCell('A1');
            title1.value = "SISTEMA INTEGRADO SOLMAR – SISOL WEB";
            title1.font = { bold: true, color: { argb: 'FF990000' }, size: 11 };
            title1.alignment = { horizontal: 'center', vertical: 'middle' };

            worksheet.mergeCells('A2:G2');
            const title2 = worksheet.getCell('A2');
            title2.value = `REPORTE PENDIENTES DE ACTUALIZACIÓN DE DATOS DE PERSONAL ${filtros.tipo}`.trim();
            title2.font = { bold: true, size: 14 };
            title2.alignment = { horizontal: 'center', vertical: 'middle' };

            worksheet.mergeCells('A3:G3');
            const title3 = worksheet.getCell('A3');
            title3.value = filtros.sucursal === 'TODAS LAS SUCURSALES' ? '' : `Sol ${capitalizeWords(filtros.sucursal)}`;
            title3.font = { bold: true, size: 12 };
            title3.alignment = { horizontal: 'center', vertical: 'middle' };

            // 3. ESTADÍSTICAS HORIZONTALES (Encima de la tabla)
            const statsHeaders = ['A6', 'B6', 'C6', 'D6'];
            const statsValues = ['A7', 'B7', 'C7', 'D7'];

            worksheet.getCell('A6').value = "Generado";
            worksheet.getCell('B6').value = "Total";
            worksheet.getCell('C6').value = "Actualizados";
            worksheet.getCell('D6').value = "Sin Actualizar";

            worksheet.getCell('A7').value = fechaStr;
            worksheet.getCell('B7').value = totalRegistros;
            worksheet.getCell('C7').value = totalActualizados;
            worksheet.getCell('D7').value = totalSinActualizar;

            // Estilos para las cabeceras de estadísticas (Fondo gris claro)
            statsHeaders.forEach(cell => {
                const c = worksheet.getCell(cell);
                c.font = { bold: true, color: { argb: 'FF000000' } };
                c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE5E7EB' } };
                c.alignment = { horizontal: 'center', vertical: 'middle' };
                c.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'thin' }, right: { style: 'thin' } };
            });

            // Estilos para los valores numéricos/fecha
            statsValues.forEach(cell => {
                const c = worksheet.getCell(cell);
                c.font = { bold: true };
                c.alignment = { horizontal: 'center', vertical: 'middle' };
                c.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'thin' }, right: { style: 'thin' } };
            });

            // 4. CABECERAS TABLA (Fila 10)
            const headers = ["N°", "Estado", "Nombres", "DNI", "Sucursal", "Tipo", "Fecha de Ingreso", "Fecha de Actualización"];
            const headerRow = worksheet.getRow(10);
            headerRow.values = headers;
            headerRow.eachCell((cell) => {
                cell.font = { bold: true, color: { argb: 'FFFFFFFF' } };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF4B5563' } };
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                cell.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'thin' }, right: { style: 'thin' } };
            });

            // 5. DATOS
            data.forEach((d, index) => { // 🔥 Agregamos 'index' aquí
                const row = worksheet.addRow([
                    index + 1, // 🔥 Reemplazamos d.NRO por index + 1
                    d.SIP_CAMBIO === 'Ok' ? 'ACTUALIZADO' : 'SIN ACTUALIZAR',
                    d.NOMBRE, d.NRO_DOCU_IDEN, d.SUCURSAL, d.TIPO_PER,
                    d.FECH_INGRE !== 'sin cambios' ? d.FECH_INGRE.split(' ')[0] : '—',
                    (d.SIP_CREACION && d.SIP_CREACION !== 'sin cambios') ? d.SIP_CREACION : '—'
                ]);
                row.eachCell((cell, colNumber) => {
                    cell.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'thin' }, right: { style: 'thin' } };
                    cell.alignment = { vertical: 'middle', horizontal: (colNumber === 3 ? 'left' : 'center') };
                });
                const estadoCell = row.getCell(2);
                estadoCell.font = { color: { argb: d.SIP_CAMBIO === 'Ok' ? 'FF15803D' : 'FFB91C1C' }, bold: true };
            });

            worksheet.columns = [{ width: 8 }, { width: 18 }, { width: 45 }, { width: 14 }, { width: 15 }, { width: 22 }, { width: 18 }, { width: 22 }];
            const buffer = await workbook.xlsx.writeBuffer();
            const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            const fStr = `${String(f.getDate()).padStart(2, '0')}_${String(f.getMonth() + 1).padStart(2, '0')}_${f.getFullYear()}`;
            link.download = `Reporte_Actualizacion_${filtros.sucursal.replace(/ /g, '_')}_${fStr}.xlsx`;
            document.body.appendChild(link); link.click(); document.body.removeChild(link);
            Swal.close();
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'Problema al generar el Excel.', 'error');
        }
    });

    // EXPORTAR PDF PERSONALIZADO (Con jsPDF y autotable manual)
    document.getElementById("btnExportPdfE1")?.addEventListener("click", async () => {
        let data = tblEtapa1.getData("active");
        if (!data.length) return Swal.fire('Sin datos', 'No hay datos para exportar', 'warning');

        // 🔥 FILTRO DE SEGURIDAD: Garantizamos que no pase ningún "ESPECIAL" al PDF
        data = data.filter(d => {
            const tipoPersonal = d.TIPO_PER ? d.TIPO_PER.toUpperCase() : '';
            return !tipoPersonal.includes('ESPECIAL');
        });

        // --- 1. Calcular totales para las cards del PDF ---
        const totalRegistros = data.length;
        const totalActualizados = data.filter(d => d.SIP_CAMBIO === 'Ok').length;
        const totalSinActualizar = totalRegistros - totalActualizados;

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('landscape'); // Horizontal para que quepa bien
        const totalWidth = doc.internal.pageSize.getWidth();

        const filtros = getFiltrosTexto();
        const f = new Date();
        const fechaStr = `${String(f.getDate()).padStart(2, '0')}/${String(f.getMonth() + 1).padStart(2, '0')}/${f.getFullYear()} ${String(f.getHours()).padStart(2, '0')}:${String(f.getMinutes()).padStart(2, '0')}`;

        // Convertir logo de Blade a Base64
        let logoBase64 = null;
        try {
            if (window.logoUrl) {
                const response = await fetch(window.logoUrl);
                const blob = await response.blob();
                logoBase64 = await new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result);
                    reader.readAsDataURL(blob);
                });
            }
        } catch (e) {
            console.warn("No se pudo cargar el logo para el PDF", e);
        }

        doc.autoTable({
            startY: 60, // 2. Bajamos el inicio de la tabla a 60 para dar espacio a las cards
            theme: 'grid',
            headStyles: { fillColor: [243, 244, 246], textColor: [55, 65, 81], fontStyle: 'bold', halign: 'center' },
            bodyStyles: { fontSize: 8 },
            columnStyles: { 0: { halign: 'center' } }, // Centrar los números de la nueva columna
            // 3. Agregamos "N°" a la cabecera
            head: [["N°", "Estado", "Nombres", "DNI", "Sucursal", "Tipo", "Fecha Ingreso", "Fecha Actualización"]],
            // 4. Mapeamos el index para enumerar
            body: data.map((d, index) => [ // 🔥 Agregamos 'index' aquí
                index + 1, // 🔥 Reemplazamos d.NRO por index + 1
                d.SIP_CAMBIO === 'Ok' ? 'ACTUALIZADO' : 'SIN ACTUALIZAR',
                d.NOMBRE,
                d.NRO_DOCU_IDEN,
                d.SUCURSAL,
                d.TIPO_PER,
                d.FECH_INGRE !== 'sin cambios' ? d.FECH_INGRE.split(' ')[0] : '—',
                (d.SIP_CREACION && d.SIP_CREACION !== 'sin cambios') ? d.SIP_CREACION : '—'
            ]),
            didDrawPage: function (dataPage) {
                if (dataPage.pageNumber !== 1) {
                    return;
                }

                // Dibujar el Logo
                if (logoBase64) {
                    doc.addImage(logoBase64, 'PNG', 14, 10, 40, 12);
                }

                // Títulos Centrales
                doc.setFontSize(10);
                doc.setTextColor(180, 0, 0); // Rojo tipo corporativo
                doc.setFont("helvetica", "bold");
                doc.text("SISTEMA INTEGRADO SOLMAR – SISOL WEB", totalWidth / 2, 14, { align: "center" });

                doc.setFontSize(12);
                doc.setTextColor(0, 0, 0); // Negro
                doc.text(`REPORTE PENDIENTES DE ACTUALIZACIÓN DE DATOS DE PERSONAL ${filtros.tipo}`.trim(), totalWidth / 2, 20, { align: "center" });

                const subtitulo = filtros.sucursal === 'TODAS LAS SUCURSALES' ? '' : `Sol ${capitalizeWords(filtros.sucursal)}`;
                if (subtitulo !== '') {
                    doc.text(subtitulo, totalWidth / 2, 26, { align: "center" });
                }

                // Texto Derecha (Generado)
                doc.setFontSize(8);
                doc.setFont("helvetica", "normal");
                doc.setTextColor(100, 100, 100); // Gris
                doc.text(`Generado: ${fechaStr}`, totalWidth - 14, 14, { align: "right" });

                // --- 5. DIBUJAR CARDS ---
                const cardW = 45;
                const cardH = 18;
                const gap = 10;
                const totalCardsW = (cardW * 3) + (gap * 2);
                const startX = (totalWidth - totalCardsW) / 2;
                const cardY = 32; // Posición Y de las cards debajo del título

                const cards = [
                    { title: "Total", value: totalRegistros, color: [75, 85, 99] }, // Gris oscuro
                    { title: "Sin actualizar", value: totalSinActualizar, color: [185, 28, 28] }, // Rojo
                    { title: "Actualizados", value: totalActualizados, color: [4, 120, 87] } // Verde
                ];

                cards.forEach((card, i) => {
                    const x = startX + (i * (cardW + gap));

                    // Fondo de la card (Bordes redondeados)
                    doc.setFillColor(...card.color);
                    doc.roundedRect(x, cardY, cardW, cardH, 2, 2, 'F');

                    // Valor numérico (Grande)
                    doc.setTextColor(255, 255, 255); // Blanco
                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(16);
                    doc.text(String(card.value), x + (cardW / 2), cardY + 10, { align: "center" });

                    // Etiqueta (Pequeña)
                    doc.setFontSize(8);
                    doc.setFont("helvetica", "normal");
                    doc.text(card.title, x + (cardW / 2), cardY + 15, { align: "center" });
                });
            }
        });

        const fStr = `${String(f.getDate()).padStart(2, '0')}_${String(f.getMonth() + 1).padStart(2, '0')}_${f.getFullYear()}`;
        doc.save(`Reporte_Actualizacion_${filtros.sucursal.replace(/ /g, '_')}_${fStr}.pdf`);
    });

    cargarDatosEtapa1();

    // ============================================================
    // FIN 1° ETAPA / LÓGICA LEGACY (5° ETAPA) A CONTINUACIÓN
    // ============================================================

    // ============================================================
    // 2° ETAPA: TABLA DE COMPARACIÓN (VERIFICADOS)
    // ============================================================
    const tblPersonasVerificado = new Tabulator("#tblPersonasVerificado", {
        height: "500px",
        layout: "fitColumns",
        responsiveLayout: "collapse",
        pagination: true,
        paginationSize: 10,
        locale: "es",
        langs: { "es": { "pagination": { "first": "Primero", "prev": "Anterior", "next": "Siguiente", "last": "Último" } } },
        columns: [
            {
                title: "N°",
                hozAlign: "center",
                width: 60,
                headerSort: false,
                formatter: function (cell) {
                    const span = document.createElement("span");

                    const actualizarNumero = () => {
                        const table = cell.getTable();
                        const page = table.getPage() || 1;
                        const size = table.getPageSize() || 10;

                        const posicionFila = cell.getRow().getPosition(true);

                        if (posicionFila > 0) {
                            // Sumamos el desfase de la página anterior a la posición actual
                            span.innerText = ((page - 1) * size) + posicionFila;
                        }
                    };

                    actualizarNumero();
                    cell.getRow().watchPosition(actualizarNumero);

                    return span;
                }
            },
            {
                title: "Verificado", field: "migrado", hozAlign: "center", widthGrow: 1.2,
                formatter: cell => {
                    const valor = (cell.getValue() ?? '').toUpperCase().trim();
                    const esVerificado = valor === 'SI';

                    // Si viene otra cosa que no sea SI o NO, lo mostramos tal cual, sino usamos SI/NO
                    const texto = (valor === 'SI' || valor === 'NO') ? valor : (valor || '—');

                    const color = esVerificado
                        ? 'border-success bg-success text-white'
                        : 'border-yellow-300 bg-yellow-50 text-yellow-800';

                    return `<span class="inline-flex items-center rounded-full border ${color} px-4 py-0.5 text-[11px] font-bold tracking-wider whitespace-nowrap">${texto}</span>`;
                }
            },
            {
                title: "Apellidos", field: "apellidos", hozAlign: "left", widthGrow: 2,
                formatter: cell => { const d = cell.getData(); return `${d.apellido1 ?? d.APEL_1 ?? ''} ${d.apellido2 ?? d.APEL_2 ?? ''}`.trim(); }
            },
            {
                title: "Nombres", field: "nombres", hozAlign: "left", widthGrow: 1.5,
                formatter: cell => { const d = cell.getData(); return `${d.nombres ?? d.NOMB_1 ?? ''} ${d.NOMB_2 ?? ''}`.trim(); }
            },
            { title: "DNI", field: "dni", hozAlign: "center", width: 110 },
            { title: "Sucursal", field: "sucursal", hozAlign: "center", widthGrow: 1 },
            { title: "Tipo", field: "tipoPer", hozAlign: "center", widthGrow: 2 },
            {
                title: "Fecha Verificado",
                field: "cambio",
                hozAlign: "center",
                widthGrow: 2,
                formatter: cell => {
                    const val = cell.getValue();
                    if (val && val !== 'sin cambios') {
                        const f = formatearFechaHora(val);
                        return `<div class="flex items-center justify-center gap-3 text-sm text-gray-700 whitespace-nowrap">
                            <span class="flex items-center gap-1"><i class='bx bx-calendar text-blue-500'></i> <span>${f.fecha}</span></span>
                            <span class="flex items-center gap-1"><i class='bx bx-time-five text-orange-500'></i> <span>${f.hora}</span></span>
                        </div>`;
                    }
                    return '—';
                }
            },
            {
                title: "Acciones", hozAlign: "center", headerSort: false, widthGrow: 1,
                formatter: cell => {
                    const d = cell.getData();
                    // Verificamos si el estado es 'SI' (Verificado)
                    const esVerificado = d.migrado === 'SI';

                    const disabled = esVerificado ? 'disabled' : '';
                    const opacityClass = esVerificado ? 'opacity-50 cursor-not-allowed' : 'hover:bg-success hover:text-white';

                    // Si está verificado, le quitamos el atributo del modal para que no se abra accidentalmente
                    const modalAttr = esVerificado ? '' : 'data-hs-overlay="#modalDjGestion"';

                    return `<button ${disabled} type="button" class="btn rounded-full form-btn-verificado bg-success/25 text-success ${opacityClass}" ${modalAttr}>DJ</button>`;
                },
                cellClick: (e, cell) => {
                    const btn = e.target.closest('.form-btn-verificado');
                    if (!btn) return;

                    // Si el botón está deshabilitado, evitamos la ejecución del JS
                    if (btn.hasAttribute('disabled')) return;

                    const rowData = cell.getRow().getData();
                    const codiPers = rowData.codPersonal || rowData.id;
                    abrirFormularioDJ(codiPers, 'migracion');
                }
            }
        ],
    });

    // ============================================================
    // EXPORTAR EXCEL PERSONALIZADO ETAPA 2 (Diseño Mejorado)
    // ============================================================
    document.getElementById("btnExportExcelE2")?.addEventListener("click", async () => {
        let data = tblPersonasVerificado.getData("active");
        if (!data.length) return Swal.fire('Sin datos', 'No hay datos para exportar', 'warning');

        // Calcular estadísticas con la corrección del 'SI'
        const totalRegistros = data.length;
        const totalVerificados = data.filter(d => {
            const estado = d.VERIFICADO_CAMBIO ? d.VERIFICADO_CAMBIO.toUpperCase().trim() : (d.migrado || '');
            return estado === 'SI' || estado === 'VERIFICADO';
        }).length;
        const totalSinVerificar = totalRegistros - totalVerificados;

        // Textos para filtros
        const selSucursal = document.getElementById('filtroSucursalE2');
        const txtSucursal = selSucursal.options[selSucursal.selectedIndex]?.text?.toUpperCase() || 'TODAS LAS SUCURSALES';
        const f = new Date();
        const fechaStr = `${String(f.getDate()).padStart(2, '0')}/${String(f.getMonth() + 1).padStart(2, '0')}/${f.getFullYear()} ${String(f.getHours()).padStart(2, '0')}:${String(f.getMinutes()).padStart(2, '0')}`;

        Swal.fire({ title: 'Generando Excel...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const workbook = new ExcelJS.Workbook();
            // 🔥 DISEÑO: Ocultar líneas de cuadrícula para un look más limpio
            const worksheet = workbook.addWorksheet("Etapa 2 - Verificados", {
                views: [{ showGridLines: false }]
            });

            // 1. INCORPORAR LOGO
            if (window.logoUrl) {
                try {
                    const response = await fetch(window.logoUrl);
                    const blob = await response.blob();
                    const base64 = await new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.onloadend = () => resolve(reader.result);
                        reader.readAsDataURL(blob);
                    });
                    const imageId = workbook.addImage({ base64: base64, extension: 'png' });
                    worksheet.addImage(imageId, { tl: { col: 0, row: 0 }, ext: { width: 170, height: 50 } });
                } catch (e) { console.warn("No se pudo cargar logo", e); }
            }

            // 2. TÍTULOS (Aplicando fuentes Arial del nuevo diseño)
            worksheet.mergeCells('A1:G1');
            const title1 = worksheet.getCell('A1');
            title1.value = "SOL SECURITY";
            title1.font = { name: 'Arial', size: 16, bold: true, color: { argb: 'FF990000' } };
            title1.alignment = { horizontal: 'center', vertical: 'middle' };

            worksheet.mergeCells('A2:G2');
            const title2 = worksheet.getCell('A2');
            title2.value = "SISTEMA INTEGRADO SOLMAR - SISOL WEB";
            title2.font = { name: 'Arial', size: 12, bold: true };
            title2.alignment = { horizontal: 'center', vertical: 'middle' };

            worksheet.mergeCells('A3:G3');
            const title3 = worksheet.getCell('A3');
            title3.value = `REPORTE DE PERSONAL - ETAPA 2 (VERIFICADOS) | Sucursal: ${txtSucursal}`;
            title3.font = { name: 'Arial', size: 11, bold: true };
            title3.alignment = { horizontal: 'center', vertical: 'middle' };

            // 3. ESTADÍSTICAS HORIZONTALES (Mantenemos tu formato de cards, pero con Arial)
            const statsHeaders = ['A6', 'B6', 'C6', 'D6'];
            const statsValues = ['A7', 'B7', 'C7', 'D7'];

            worksheet.getCell('A6').value = "Generado";
            worksheet.getCell('B6').value = "Total";
            worksheet.getCell('C6').value = "Sin Verificar";
            worksheet.getCell('D6').value = "Verificados";

            worksheet.getCell('A7').value = fechaStr;
            worksheet.getCell('B7').value = totalRegistros;
            worksheet.getCell('C7').value = totalSinVerificar;
            worksheet.getCell('D7').value = totalVerificados;

            statsHeaders.forEach(cell => {
                const c = worksheet.getCell(cell);
                c.font = { name: 'Arial', bold: true, color: { argb: 'FF000000' } };
                c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE5E7EB' } };
                c.alignment = { horizontal: 'center', vertical: 'middle' };
                c.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'thin' }, right: { style: 'thin' } };
            });

            statsValues.forEach(cell => {
                const c = worksheet.getCell(cell);
                c.font = { name: 'Arial', bold: true };
                c.alignment = { horizontal: 'center', vertical: 'middle' };
                c.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'thin' }, right: { style: 'thin' } };
            });

            // 4. CABECERAS TABLA (Fila 10) - 🔥 DISEÑO AZUL OSCURO
            const headers = ["N°", "Verificado", "Nombres", "DNI", "Sucursal", "Tipo", "Fecha Verificado"];
            const headerRow = worksheet.getRow(10);
            headerRow.values = headers;
            headerRow.height = 25; // Altura de la cabecera

            headerRow.eachCell((cell) => {
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1F4E78' } }; // Azul oscuro
                cell.font = { color: { argb: 'FFFFFFFF' }, bold: true, name: 'Arial', size: 10 };
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                cell.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'thin' }, right: { style: 'thin' } };
            });

            // 5. DATOS DE LA TABLA - 🔥 BORDES GRISES SUTILES Y COLORES CONDICIONALES
            data.forEach((d, index) => {
                const estado = d.VERIFICADO_CAMBIO ? d.VERIFICADO_CAMBIO.toUpperCase().trim() : (d.migrado || '');
                const verificadoTxt = (estado === 'SI' || estado === 'VERIFICADO') ? 'SI' : 'NO';
                const nombreCompleto = d.NOMBRE || d.PERSONAL || `${d.nombres ?? d.NOMB_1 ?? ''} ${d.apellido1 ?? d.APEL_1 ?? ''} ${d.apellido2 ?? d.APEL_2 ?? ''}`.trim();

                let fechaVerif = '—';
                if (d.VERIFICADO_FECHA && d.VERIFICADO_FECHA !== 'sin cambios') {
                    fechaVerif = d.VERIFICADO_FECHA.replace('T', ' ').substring(0, 16);
                } else if (d.cambio && d.cambio !== 'sin cambios') {
                    fechaVerif = d.cambio.replace('T', ' ').substring(0, 16);
                }

                const row = worksheet.addRow([
                    d.NRO || index + 1,
                    verificadoTxt,
                    nombreCompleto,
                    d.dni ?? d.NRO_DOCU_IDEN ?? '',
                    d.sucursal ?? d.SUCURSAL ?? '',
                    d.tipoPer ?? d.TIPO_PER ?? '',
                    fechaVerif
                ]);

                row.eachCell((cell, colNumber) => {
                    cell.font = { name: 'Arial', size: 9 };
                    cell.border = {
                        top: { style: 'thin', color: { argb: 'FFBFBFBF' } }, // Bordes grises sutiles
                        left: { style: 'thin', color: { argb: 'FFBFBFBF' } },
                        bottom: { style: 'thin', color: { argb: 'FFBFBFBF' } },
                        right: { style: 'thin', color: { argb: 'FFBFBFBF' } }
                    };

                    // Alineación
                    if (colNumber === 3) {
                        cell.alignment = { vertical: 'middle', horizontal: 'left' };
                    } else {
                        cell.alignment = { vertical: 'middle', horizontal: 'center' };
                    }

                    // Condicional de color para "Verificado"
                    if (colNumber === 2) {
                        if (verificadoTxt === 'NO') {
                            cell.font = { color: { argb: 'FFFF0000' }, bold: true, size: 9, name: 'Arial' }; // Rojo
                        } else {
                            cell.font = { color: { argb: 'FF00B050' }, bold: true, size: 9, name: 'Arial' }; // Verde
                        }
                    }
                });
            });

            // 6. ANCHOS DE COLUMNA EXACTOS DEL DISEÑO
            worksheet.columns = [
                { width: 5 },  // N°
                { width: 12 }, // Verificado
                { width: 45 }, // Nombres
                { width: 15 }, // DNI
                { width: 15 }, // Sucursal
                { width: 20 }, // Tipo
                { width: 20 }  // Fecha Verificado
            ];

            // 7. DESCARGAR ARCHIVO NATIVAMENTE
            const buffer = await workbook.xlsx.writeBuffer();
            const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            const fStr = `${String(f.getDate()).padStart(2, '0')}_${String(f.getMonth() + 1).padStart(2, '0')}_${f.getFullYear()}`;
            link.download = `Reporte_Etapa2_${txtSucursal.replace(/ /g, '_')}_${fStr}.xlsx`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            Swal.close();

        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'Problema al generar el Excel.', 'error');
        }
    });

    function cargarDatosEtapa2() {
        axios.get(`${VITE_URL_APP}/api/reporte-personal-sin-migracion`)
            .then(response => {
                if (!response.data.success) return;

                // 🔥 FILTRO DE SEGURIDAD: Excluimos al personal "ESPECIAL" de la data base
                const datosLimpios = response.data.data.filter(d => {
                    const tipoPersonal = d.tipoPer ? d.tipoPer.toUpperCase() : (d.TIPO_PER ? d.TIPO_PER.toUpperCase() : '');
                    return !tipoPersonal.includes('ESPECIAL');
                });

                // 1. Calculamos y fijamos las cards con LA DATA TOTAL LIMPIA
                const total = datosLimpios.length;
                const verificados = datosLimpios.filter(d => d.migrado === 'SI').length;
                const sinVerificar = total - verificados;

                document.getElementById('contadorTotalE2').textContent = total;
                document.getElementById('contadorFiltradoE2').textContent = verificados;
                document.getElementById('contadorSinVerificarE2').textContent = sinVerificar;

                // 2. Mandamos la data limpia a la tabla
                tblPersonasVerificado.setData(datosLimpios);
                aplicarFiltrosE2();
            });
    }

    function aplicarFiltrosE2() {
        const codSucursal = document.getElementById('filtroSucursalE2')?.value || '00';
        const codTipoPer = document.getElementById('filtroTipoPerE2')?.value || '00';
        const texto = document.getElementById('buscarPersonalE2')?.value.toLowerCase().trim() || '';
        const radioVal = document.querySelector('input[name="filtroEstadoE2"]:checked')?.value || 'null';

        let filtros = [];
        let tipoTxt = ''; // 🔥 Lo sacamos afuera para poder usarlo en el cálculo de abajo

        if (codSucursal !== '00') filtros.push({ field: "codSucursal", type: "=", value: codSucursal });

        if (codTipoPer !== '00') {
            if (codTipoPer === '01') tipoTxt = 'OPERATIVO 4°';
            if (codTipoPer === '03') tipoTxt = 'OPERATIVO 5°';
            if (codTipoPer === '02') tipoTxt = 'ADMINISTRATIVO 4°';
            if (codTipoPer === '05') tipoTxt = 'ADMINISTRATIVO 5°';
            // (Ya no incluimos el 06 de ESPECIAL por la regla que pusimos antes)

            if (tipoTxt) filtros.push({ field: "tipoPer", type: "=", value: tipoTxt });
        }

        if (texto) {
            filtros.push([
                { field: "nombres", type: "like", value: texto },
                { field: "apellido1", type: "like", value: texto },
                { field: "apellido2", type: "like", value: texto },
                { field: "dni", type: "like", value: texto }
            ]);
        }

        // Filtramos localmente por el Radio Button para la tabla visual
        if (radioVal === '0') {
            filtros.push({ field: "migrado", type: "=", value: "SI" });
        } else if (radioVal === '1') {
            filtros.push({ field: "migrado", type: "!=", value: "SI" });
        }

        // Aplicamos el filtro visual a Tabulator
        tblPersonasVerificado.setFilter(filtros);

        // =========================================================================
        // 🔥 LÓGICA DE INDICADORES: Calculamos SOLO en base a Sucursal y Tipo
        // =========================================================================

        // .getData() nos trae TODA la data base original, ignorando si hay texto o radio buttons aplicados
        const todaLaData = tblPersonasVerificado.getData();

        const dataParaTarjetas = todaLaData.filter(d => {
            const cumpleSucursal = (codSucursal === '00') || (d.codSucursal === codSucursal);
            const cumpleTipo = (codTipoPer === '00') || (d.tipoPer === tipoTxt);

            return cumpleSucursal && cumpleTipo;
        });

        // Calculamos la matemática
        const total = dataParaTarjetas.length;
        const verificados = dataParaTarjetas.filter(d => d.migrado === 'SI').length;
        const sinVerificar = total - verificados;

        // Mandamos los números a las tarjetas
        document.getElementById('contadorTotalE2').textContent = total;
        document.getElementById('contadorFiltradoE2').textContent = verificados;

        // Asegúrate de tener este ID en tu HTML para la tercera tarjeta (Sin Verificar)
        const elSinVerificar = document.getElementById('contadorSinVerificarE2');
        if (elSinVerificar) elSinVerificar.textContent = sinVerificar;
    }

    document.getElementById('filtroSucursalE2')?.addEventListener('change', aplicarFiltrosE2);
    document.getElementById('filtroTipoPerE2')?.addEventListener('change', aplicarFiltrosE2);
    document.getElementById('buscarPersonalE2')?.addEventListener('keyup', aplicarFiltrosE2);
    // Escuchando los radio buttons
    document.querySelectorAll('input[name="filtroEstadoE2"]').forEach(radio => radio.addEventListener('change', aplicarFiltrosE2));

    document.getElementById('page-size-verificado')?.addEventListener('change', function () {
        tblPersonasVerificado.setPageSize(parseInt(this.value));
    });

    cargarDatosEtapa2();

    // ============================================================
    // 3° ETAPA: CONTROL DE IMPRESIONES (MISMO SP + BOTONES ACTIVOS)
    // ============================================================
    const tblPersonasEtapa3 = new Tabulator("#tblPersonasEtapa3", {
        height: "500px",
        layout: "fitColumns",
        responsiveLayout: "collapse",
        pagination: true,
        paginationSize: 10,
        locale: "es",
        langs: { "es": { "pagination": { "first": "Primero", "prev": "Anterior", "next": "Siguiente", "last": "Último" } } },
        columns: [
            { title: "N°", formatter: "rownum", hozAlign: "center", width: 60 },
            // {
            //     title: "Verificado", field: "migrado", hozAlign: "center", widthGrow: 1.5,
            //     formatter: cell => {
            //         const esVerificado = cell.getValue() === 'SI';
            //         const texto = esVerificado ? 'VERIFICADO' : 'SIN VERIFICAR';
            //         const color = esVerificado ? 'border-success bg-success text-white' : 'border-yellow-300 bg-yellow-50 text-yellow-800';
            //         return `<span class="inline-flex items-center rounded-full border ${color} px-3 py-1 text-[10px] font-bold tracking-wider whitespace-nowrap">${texto}</span>`;
            //     }
            // },
            {
                title: "Apellidos", field: "apellidos", hozAlign: "left", widthGrow: 2,
                formatter: cell => { const d = cell.getData(); return `${d.apellido1 ?? d.APEL_1 ?? ''} ${d.apellido2 ?? d.APEL_2 ?? ''}`.trim(); }
            },
            {
                title: "Nombres", field: "nombres", hozAlign: "left", widthGrow: 1.5,
                formatter: cell => { const d = cell.getData(); return `${d.nombres ?? d.NOMB_1 ?? ''} ${d.NOMB_2 ?? ''}`.trim(); }
            },
            { title: "DNI", field: "dni", hozAlign: "center", width: 110 },
            { title: "Sucursal", field: "sucursal", hozAlign: "center", widthGrow: 1 },
            { title: "Tipo", field: "tipoPer", hozAlign: "center", widthGrow: 2 },
            {
                title: "Fecha Verificado",
                field: "cambio",
                hozAlign: "center",
                widthGrow: 2,
                formatter: cell => {
                    const val = cell.getValue();
                    if (val && val !== 'sin cambios') {
                        const f = formatearFechaHora(val);
                        return `<div class="flex items-center justify-center gap-3 text-sm text-gray-700 whitespace-nowrap">
                            <span class="flex items-center gap-1"><i class='bx bx-calendar text-blue-500'></i> <span>${f.fecha}</span></span>
                            <span class="flex items-center gap-1"><i class='bx bx-time-five text-orange-500'></i> <span>${f.hora}</span></span>
                        </div>`;
                    }
                    return '—';
                }
            },
            // 🔥 COLUMNA DE VERIFICACIÓN PDF (Marca de impreso)
            {
                title: "PDF", field: "pdf_generado", hozAlign: "center", widthGrow: 1,
                headerSort: false,
                formatter: cell => {
                    const d = cell.getData();
                    const cod = d.codPersonal || d.id;
                    const gen = estaGenerado(cod, d.cambio);
                    const titulo = gen ? 'Generado — click para resetear' : 'Pendiente';
                    const color = gen ? 'color:#16a34a;font-size:18px;cursor:pointer;' : 'color:#d1d5db;font-size:18px;cursor:default;';
                    return `<span title="${titulo}" style="${color}" data-pdf-cod-e3="${cod}" data-pdf-cambio-e3="${d.cambio || ''}">${gen ? '✅' : '○'}</span>`;
                },
                cellClick: (e, cell) => {
                    const span = e.target.closest('[data-pdf-cod-e3]');
                    if (!span) return;
                    const cod = span.getAttribute('data-pdf-cod-e3');
                    if (!estaGenerado(cod, span.getAttribute('data-pdf-cambio-e3'))) return;

                    Swal.fire({
                        icon: 'question',
                        title: '¿Resetear marca en Etapa 3?',
                        text: 'Se marcará este registro como pendiente de generar PDF.',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, resetear',
                        cancelButtonText: 'Cancelar',
                    }).then(r => {
                        if (!r.isConfirmed) return;
                        desmarcarDJGenerado(cod);
                        cell.getRow().reformat();
                    });
                }
            },
            {
                title: "Acciones", hozAlign: "center", headerSort: false, widthGrow: 1,
                formatter: cell => {
                    // 🔥 Le metemos un diseño más acorde a un PDF (Rojito y con icono)
                    return `<button type="button" class="btn rounded-full btn-export-pdf-e3 hover:bg-danger hover:text-white bg-danger/10 text-danger px-3 py-1 flex items-center justify-center gap-1 mx-auto" title="Exportar PDF"><i class='bx bxs-file-pdf text-lg'></i> PDF</button>`;
                },
                cellClick: async (e, cell) => {
                    const btn = e.target.closest('.btn-export-pdf-e3');
                    if (!btn) return;

                    const rowData = cell.getRow().getData();
                    const dni = rowData.dni ?? rowData.NRO_DOCU_IDEN ?? 'Desconocido';

                    // 🔥 Disparamos la generación individual usando tu función global masiva
                    const resultadoGen = await _generarUnificado([rowData], `DJ_${dni}`, 'migracion');

                    // Si todo salió bien, guardamos la marca de "impreso" y repintamos la fila
                    if (resultadoGen?.ok && resultadoGen.generadosOk.length) {
                        marcarDJGeneradosBatch(
                            resultadoGen.generadosOk.map(f => ({
                                codPersonal: f.codPersonal || f.id,
                                fechaCambio: f.cambio
                            }))
                        );
                        // Forzamos el render para que aparezca el check ✅ al instante
                        cell.getRow().reformat();
                    }
                }
            }
        ],
    });

    function cargarDatosEtapa3() {
        axios.get(`${VITE_URL_APP}/api/reporte-personal-sin-migracion`)
            .then(response => {
                if (!response.data.success) return;

                // 🔥 Filtramos en duro: Verificados (migrado='SI') y Ocultamos 'ESPECIAL'
                const datosVerificados = response.data.data.filter(d => {
                    const tipoPersonal = d.tipoPer ? d.tipoPer.toUpperCase() : (d.TIPO_PER ? d.TIPO_PER.toUpperCase() : '');
                    return d.migrado === 'SI' && !tipoPersonal.includes('ESPECIAL');
                });

                tblPersonasEtapa3.setData(datosVerificados);
                aplicarFiltrosE3();
            });
    }

    function aplicarFiltrosE3() {
        const codSucursal = document.getElementById('filtroSucursalE3')?.value || '00';
        const codTipoPer = document.getElementById('filtroTipoPerE3')?.value || '00';
        const texto = document.getElementById('buscarPersonalE3')?.value.toLowerCase().trim() || '';
        const radioVal = document.querySelector('input[name="filtroEstadoE3"]:checked')?.value || 'null';

        let tipoTxt = '';
        if (codTipoPer !== '00') {
            if (codTipoPer === '01') tipoTxt = 'OPERATIVO 4°';
            if (codTipoPer === '03') tipoTxt = 'OPERATIVO 5°';
            if (codTipoPer === '02') tipoTxt = 'ADMINISTRATIVO 4°';
            if (codTipoPer === '05') tipoTxt = 'ADMINISTRATIVO 5°';
        }

        // Limpiamos filtros anteriores
        tblPersonasEtapa3.clearFilter();

        // Aplicamos un filtro personalizado que evalúa todas las condiciones a la vez
        tblPersonasEtapa3.setFilter(function (data) {
            let matchSucursal = true;
            let matchTipo = true;
            let matchTexto = true;
            let matchRadio = true;

            if (codSucursal !== '00') matchSucursal = data.codSucursal === codSucursal;
            if (codTipoPer !== '00') matchTipo = data.tipoPer === tipoTxt;

            if (texto) {
                const nombre = (data.nombres || '').toLowerCase();
                const ape1 = (data.apellido1 || '').toLowerCase();
                const ape2 = (data.apellido2 || '').toLowerCase();
                const dni = (data.dni || '').toLowerCase();
                matchTexto = nombre.includes(texto) || ape1.includes(texto) || ape2.includes(texto) || dni.includes(texto);
            }

            // Lógica para saber si está generado o pendiente leyendo de tu localStorage (estaGenerado)
            if (radioVal !== 'null') {
                const cod = data.codPersonal || data.id;
                const gen = estaGenerado(cod, data.cambio);
                if (radioVal === '0') matchRadio = gen === true;  // Generados
                if (radioVal === '1') matchRadio = gen === false; // Pendientes
            }

            return matchSucursal && matchTipo && matchTexto && matchRadio;
        });

        // =========================================================================
        // 🔥 LÓGICA DE INDICADORES: Calculamos SOLO en base a Sucursal y Tipo
        // =========================================================================
        const todaLaData = tblPersonasEtapa3.getData();

        const dataParaTarjetas = todaLaData.filter(d => {
            const cumpleSucursal = (codSucursal === '00') || (d.codSucursal === codSucursal);
            const cumpleTipo = (codTipoPer === '00') || (d.tipoPer === tipoTxt);
            return cumpleSucursal && cumpleTipo;
        });

        const total = dataParaTarjetas.length;
        let generados = 0;

        // Contamos cuántos están generados
        dataParaTarjetas.forEach(d => {
            const cod = d.codPersonal || d.id;
            if (estaGenerado(cod, d.cambio)) generados++;
        });

        const pendientes = total - generados;

        // Pintamos los números en las Cards
        document.getElementById('contadorTotalE3').textContent = total;

        const elGenerados = document.getElementById('contadorGeneradosE3');
        if (elGenerados) elGenerados.textContent = generados;

        const elPendientes = document.getElementById('contadorPendientesE3');
        if (elPendientes) elPendientes.textContent = pendientes;
    }

    // Event Listeners
    document.getElementById('filtroSucursalE3')?.addEventListener('change', aplicarFiltrosE3);
    document.getElementById('filtroTipoPerE3')?.addEventListener('change', aplicarFiltrosE3);
    document.getElementById('buscarPersonalE3')?.addEventListener('keyup', aplicarFiltrosE3);
    document.querySelectorAll('input[name="filtroEstadoE3"]').forEach(radio => radio.addEventListener('change', aplicarFiltrosE3));

    document.getElementById('page-size-etapa3')?.addEventListener('change', function () {
        tblPersonasEtapa3.setPageSize(parseInt(this.value));
    });

    // 🔥 ACCIÓN: DJ UNIFICADO PARA ETAPA 3
    document.getElementById('btnDJUnificadoE3')?.addEventListener('click', async function () {
        const todasEtapa3 = tblPersonasEtapa3.getData("active");
        if (!todasEtapa3.length) {
            Swal.fire({ icon: 'info', title: 'Sin resultados', text: 'No hay registros visibles en la tabla.' });
            return;
        }

        const pendientes = todasEtapa3.filter(f => !estaGenerado(f.codPersonal || f.id, f.cambio));
        const yaGenerados = todasEtapa3.filter(f => estaGenerado(f.codPersonal || f.id, f.cambio));

        const { value: opcion, isConfirmed } = await Swal.fire({
            title: 'DJ Masivo — Etapa 3',
            html: `
            <div style="display:flex;flex-direction:column;gap:10px;text-align:left;font-size:13px;padding:4px 0;">
                <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;" id="lbl-pend-e3">
                    <input type="radio" name="djopcion_e3" value="pendientes" ${pendientes.length ? '' : 'disabled'} style="width:16px;height:16px;cursor:pointer;accent-color:#6366f1;">
                    <div>
                        <div style="font-weight:600;color:${pendientes.length ? '#111827' : '#9ca3af'};">
                            Solo pendientes <span style="margin-left:6px;background:${pendientes.length ? '#dcfce7' : '#f3f4f6'};color:${pendientes.length ? '#16a34a' : '#9ca3af'};font-size:11px;padding:1px 8px;border-radius:20px;font-weight:700;">${pendientes.length}</span>
                        </div>
                    </div>
                </label>
                <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;" id="lbl-todos-e3">
                    <input type="radio" name="djopcion_e3" value="todos" style="width:16px;height:16px;cursor:pointer;accent-color:#6366f1;">
                    <div>
                        <div style="font-weight:600;color:#111827;">
                            Todos los registros <span style="margin-left:6px;background:#dbeafe;color:#1e40af;font-size:11px;padding:1px 8px;border-radius:20px;font-weight:700;">${todasEtapa3.length}</span>
                        </div>
                    </div>
                </label>
            </div>`,
            showCancelButton: true, confirmButtonText: 'Generar PDF', cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const sel = document.querySelector('input[name="djopcion_e3"]:checked');
                if (!sel) { Swal.showValidationMessage('Selecciona una opción.'); return false; }
                return sel.value;
            }
        });

        if (!isConfirmed) return;
        const filasFinales = opcion === 'pendientes' ? pendientes : todasEtapa3;

        const confirmacion = await Swal.fire({
            icon: 'question', title: 'Confirmar generación',
            html: `Se generará <b>1 PDF</b> con <b>${filasFinales.length}</b> declaración(es).<br>¿Desea continuar?`,
            showCancelButton: true, confirmButtonText: 'Sí, generar', cancelButtonText: 'Cancelar'
        });
        if (!confirmacion.isConfirmed) return;

        // 🔥 Ahora el archivo se llamará DJ_Masivo_Etapa3
        const resultadoGen = await _generarUnificado(filasFinales, 'DJ_Masivo_Etapa3', 'migracion');
        if (resultadoGen?.ok && resultadoGen.generadosOk.length) {
            marcarDJGeneradosBatch(
                resultadoGen.generadosOk.map(f => ({
                    codPersonal: f.codPersonal || f.id,
                    fechaCambio: f.cambio
                }))
            );
        }
        tblPersonasEtapa3.redraw(true);
    });

    // 🔥 ACCIÓN: RESETEAR MARCAS PARA ETAPA 3
    document.getElementById('btnResetearDJsE3')?.addEventListener('click', async function () {
        const generados = getDJGenerados();
        const totalMarcados = Object.keys(generados).length;
        if (totalMarcados === 0) {
            Swal.fire({ icon: 'info', title: 'Sin marcas', text: 'No hay registros marcados como generados.' });
            return;
        }

        const { isConfirmed } = await Swal.fire({
            icon: 'warning', title: 'Resetear marcas en Etapa 3',
            html: `Se eliminarán las marcas ✅ de <b>${totalMarcados}</b> registro(s).`,
            showCancelButton: true, confirmButtonText: 'Sí, resetear todo', cancelButtonText: 'Cancelar', confirmButtonColor: '#ef4444',
        });

        if (!isConfirmed) return;
        limpiarDJGenerados();
        tblPersonasEtapa3.redraw(true);
    });

    // 🔥 ACCIÓN: REPORTE DE AVANCES PARA ETAPA 3
    document.getElementById('btnReporteAvanceE3')?.addEventListener('click', async function () {
        const codSucursal = document.getElementById('filtroSucursalE3')?.value || '00';
        const codTipoPerRaw = document.getElementById('filtroTipoPerE3')?.value || '00';

        // Mapeamos el UI al formato que espera tu SP ('OPER', 'ADMIN', '00')
        let tipoMapped = '00';
        if (['01', '03'].includes(codTipoPerRaw)) tipoMapped = 'OPER';
        if (['02', '05'].includes(codTipoPerRaw)) tipoMapped = 'ADMIN';

        Swal.fire({ title: 'Generando reporte...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const response = await axios.get(`${VITE_URL_APP}/api/reporte-avances-dj`, {
                params: { sucursal: codSucursal, tipo: tipoMapped }
            });

            if (!response.data.success || !response.data.data.length) {
                Swal.fire({ icon: 'info', title: 'Sin datos', text: 'No hay registros en el reporte.' });
                return;
            }

            let datos = response.data.data;

            // Filtro local por si usaron el input de búsqueda
            const textoBusqueda = document.getElementById('buscarPersonalE3')?.value.toLowerCase().trim() || '';
            if (textoBusqueda) {
                datos = datos.filter(d => {
                    const str = `${d.nombreCompleto} ${d.doc}`.toLowerCase();
                    return str.includes(textoBusqueda);
                });
            }

            if (!datos.length) {
                Swal.fire({ icon: 'info', title: 'Sin datos', text: 'No hay registros que coincidan con la búsqueda.' });
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('landscape');
            const totalWidth = doc.internal.pageSize.getWidth();

            const selSucursal = document.getElementById('filtroSucursalE3');
            const txtSucursal = selSucursal?.options[selSucursal.selectedIndex]?.text?.toUpperCase() || 'TODAS LAS SUCURSALES';
            const f = new Date();
            const fechaStr = `${String(f.getDate()).padStart(2, '0')}/${String(f.getMonth() + 1).padStart(2, '0')}/${f.getFullYear()} ${String(f.getHours()).padStart(2, '0')}:${String(f.getMinutes()).padStart(2, '0')}`;

            let logoBase64 = null;
            try {
                if (window.logoUrl) {
                    const res = await fetch(window.logoUrl);
                    const blob = await res.blob();
                    logoBase64 = await new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.onloadend = () => resolve(reader.result);
                        reader.readAsDataURL(blob);
                    });
                }
            } catch (e) { }

            doc.autoTable({
                startY: 40,
                theme: 'grid',
                headStyles: { fillColor: [243, 244, 246], textColor: [55, 65, 81], fontStyle: 'bold', halign: 'center' },
                bodyStyles: { fontSize: 8 },
                columnStyles: { 0: { halign: 'center' }, 5: { halign: 'center' }, 6: { halign: 'center' }, 7: { halign: 'center' } },
                // 🔥 AQUÍ SE OMITEN "Firma Act." y "Huella Act."
                head: [["N°", "Nombres", "DNI", "Sucursal", "Tipo", "DJ Subido", "Estado", "Última Act."]],
                body: datos.map((d, index) => {
                    const fechaFormat = d.fechaAct ? d.fechaAct.replace('T', ' ').substring(0, 16) : '—';
                    return [
                        index + 1,
                        d.nombreCompleto || '',
                        d.doc || '',
                        d.sucursal || '',
                        d.tipo || '',
                        d.djSubido || 'NO',
                        d.estado || '',
                        fechaFormat
                    ];
                }),
                didDrawPage: function (dataPage) {
                    if (dataPage.pageNumber !== 1) return;

                    if (logoBase64) doc.addImage(logoBase64, 'PNG', 14, 10, 40, 12);

                    doc.setFontSize(10);
                    doc.setTextColor(180, 0, 0);
                    doc.setFont("helvetica", "bold");
                    doc.text("SISTEMA INTEGRADO SOLMAR – SISOL WEB", totalWidth / 2, 14, { align: "center" });

                    doc.setFontSize(12);
                    doc.setTextColor(0, 0, 0);
                    doc.text("REPORTE DE AVANCES DJ 2026", totalWidth / 2, 20, { align: "center" });

                    const subtitulo = txtSucursal === 'TODAS LAS SUCURSALES' ? '' : `Sol ${txtSucursal}`;
                    if (subtitulo !== '') doc.text(subtitulo, totalWidth / 2, 26, { align: "center" });

                    doc.setFontSize(8);
                    doc.setFont("helvetica", "normal");
                    doc.setTextColor(100, 100, 100);
                    doc.text(`Generado: ${fechaStr}`, totalWidth - 14, 14, { align: "right" });
                }
            });

            const fStr = `${String(f.getDate()).padStart(2, '0')}_${String(f.getMonth() + 1).padStart(2, '0')}_${f.getFullYear()}`;
            doc.save(`Reporte_Avances_${txtSucursal.replace(/ /g, '_')}_${fStr}.pdf`);
            Swal.close();

        } catch (error) {
            console.error(error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Problema al generar el reporte de avances.' });
        }
    });

    cargarDatosEtapa3();

    // ============================================================
    // 4° ETAPA: ESCANEO DJ (Paginación Local - Estilo Etapa 1, 2 y 3)
    // ============================================================
    const tblEtapa4 = new Tabulator("#tblPersonasEtapa4", {
        height: "500px", // Mismo alto que las otras tablas para mantener diseño
        layout: "fitColumns",
        responsiveLayout: "collapse",
        pagination: true,
        paginationSize: 10, // Paginación local súper rápida
        locale: "es",
        langs: { "es": { "pagination": { "first": "Primero", "prev": "Anterior", "next": "Siguiente", "last": "Último" } } },
        columns: [
            { title: "N°", formatter: "rownum", hozAlign: "center", width: 60 },
            { title: "Nro Doc", field: "nroDoc", hozAlign: "center", width: 110, formatter: (cell) => cell.getValue() || cell.getData().NRODOC },
            {
                title: "Apellidos", field: "apellidos", hozAlign: "left", widthGrow: 2,
                formatter: cell => {
                    const d = cell.getData();
                    if (d.APEL_1 || d.apellido1) return `${d.apellido1 ?? d.APEL_1 ?? ''} ${d.apellido2 ?? d.APEL_2 ?? ''}`.trim();
                    // Fallback si el SP solo trae el nombre concatenado
                    const partes = (d.PERSONAL || d.personal || '').split(' ');
                    return partes.length >= 3 ? `${partes[0]} ${partes[1]}` : (partes[0] || '');
                }
            },
            {
                title: "Nombres", field: "nombres", hozAlign: "left", widthGrow: 1.5,
                formatter: cell => {
                    const d = cell.getData();
                    if (d.NOMB_1 || d.nombres) return `${d.nombres ?? d.NOMB_1 ?? ''} ${d.NOMB_2 ?? ''}`.trim();
                    // Fallback si el SP solo trae el nombre concatenado
                    const partes = (d.PERSONAL || d.personal || '').split(' ');
                    return partes.length >= 3 ? partes.slice(2).join(' ') : (partes.slice(1).join(' ') || '');
                }
            },
            { title: "Sucursal", field: "sucursal", hozAlign: "center", widthGrow: 1, formatter: (cell) => cell.getValue() || cell.getData().SUCURSAL },
            {
                title: "Tipo Trabajador",
                field: "TIPOTRAB2",
                hozAlign: "center",
                widthGrow: 1.5,
                formatter: (cell) => {
                    let val = cell.getValue() || cell.getData().tipotrab2 || '';

                    // Reemplazamos la abreviatura por la palabra completa sin tocar la Base de Datos
                    val = val.replace('OPER', 'OPERATIVO').replace('ADMIN', 'ADMINISTRATIVO');

                    // Le damos el estilo visual "Badge"
                    let color = 'border-gray-300 bg-gray-100 text-gray-800';
                    if (val.includes('OPERATIVO')) color = 'border-blue-300 bg-blue-100 text-blue-800';
                    else if (val.includes('ADMINISTRATIVO')) color = 'border-purple-300 bg-purple-100 text-purple-800';
                    else if (val.includes('ESPECIAL')) color = 'border-orange-300 bg-orange-100 text-orange-800';

                    return val ? `<span class="inline-flex items-center rounded-full border ${color} px-3 py-1 text-[11px] font-bold tracking-wider whitespace-nowrap">${val}</span>` : '—';
                }
            },
            {
                title: "Escaneo DJ",
                field: "djSubido",
                hozAlign: "center",
                widthGrow: 1,
                headerSort: false,
                formatter: cell => {
                    const valor = cell.getValue() || cell.getData().djsubido || cell.getData().DJSUBIDO;
                    if (valor === 'SI') {
                        return `<span title="Escaneo Completado" class="text-xl cursor-default">✅</span>`;
                    } else {
                        return ``;
                    }
                }
            },
            // 🔥 COLUMNA ACCIONES
            {
                title: "Acciones", field: "acciones", hozAlign: "center", widthGrow: 1, headerSort: false,
                formatter: function (cell) {
                    const d = cell.getData();
                    const dj = d.djSubido || d.djsubido || d.DJSUBIDO;

                    if (dj === 'SI') {
                        return `<button type="button" class="btn rounded-full bio-btn bg-info/25 text-info hover:bg-info hover:text-white" title="Validación Huella / Firma">
                        <i class="bx bx-fingerprint text-xl bio-btn"></i>
                    </button>`;
                    }
                    return ``;
                },
                cellClick: function (e, cell) {
                    // closest captura el click aunque se haga en el padding del botón o en el SVG
                    const btn = e.target.closest('.bio-btn');
                    if (btn) {
                        const d = cell.getData();
                        // Cubrimos cualquier forma en que SQL Server envíe la columna
                        const codPersonal = d.CODI_PERS || d.codPersonal || d.nroDoc || d.NRODOC || d.NRO_DOCU_IDEN || '';
                        const personal = d.personal || d.PERSONAL || '';

                        if (!codPersonal) {
                            console.error("No se encontró un código válido para el biométrico:", d);
                            return;
                        }

                        const event = new CustomEvent('solicitarBiometrico', {
                            detail: { codigo: codPersonal, persona: personal }
                        });
                        window.dispatchEvent(event);
                    }
                }
            }
        ],
    });

    // Función para traer datos una sola vez con Axios
    function cargarDatosEtapa4() {
        const codSucursal = document.getElementById('filtroSucursalE4')?.value || '00';

        Swal.fire({ title: 'Cargando datos...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        axios.get(`${VITE_URL_APP}/api/reporte-etapa4-dj`, { params: { sucursal: codSucursal } })
            .then(response => {
                Swal.close();
                if (!response.data.success) return;
                const datos = response.data.data;

                // 🔥 NUEVO: Filtramos para EXCLUIR a los "ESPECIALES" antes de pintar la tabla
                const dataSinEspeciales = datos.filter(d => {
                    const tipo = (d.TIPOTRAB2 || d.tipotrab2 || '').toUpperCase();
                    return !tipo.includes('ESPECIAL');
                });

                tblEtapa4.setData(dataSinEspeciales); // Inyectamos la data limpia (Paginación local instantánea)
                aplicarFiltrosE4();
            })
            .catch(error => {
                Swal.close();
                console.error("Error etapa 4:", error);
                Swal.fire('Error', 'No se pudieron cargar los datos.', 'error');
            });
    }

    // Buscador y Filtros combinados en tiempo real (milisegundos)
    function aplicarFiltrosE4() {
        const texto = document.getElementById('buscarPersonalE4')?.value.toLowerCase().trim() || '';
        const codTipoPer = document.getElementById('filtroTipoPerE4')?.value || '00';
        const radioVal = document.querySelector('input[name="filtroEstadoE4"]:checked')?.value || 'null';

        let tipoTxt = '';
        if (codTipoPer === '01') tipoTxt = 'OPER 4°';
        if (codTipoPer === '03') tipoTxt = 'OPER 5°';
        if (codTipoPer === '02') tipoTxt = 'ADMIN 4°';
        if (codTipoPer === '05') tipoTxt = 'ADMIN 5°';
        if (codTipoPer === '06') tipoTxt = 'ESPECIAL';

        // Filtro visual en Tabulator
        tblEtapa4.setFilter(function (data) {
            let matchTipo = true;
            let matchTexto = true;
            let matchRadio = true;

            // 1. Evaluamos el select de Tipo
            if (tipoTxt) {
                const valTipo = data.TIPOTRAB2 || data.tipotrab2 || '';
                matchTipo = (valTipo === tipoTxt);
            }

            // 2. Evaluamos el buscador de texto
            if (texto) {
                const valPersonal = String(data.personal || data.PERSONAL || '').toLowerCase();
                const valDoc = String(data.nroDoc || data.NRODOC || data.NRO_DOCU_IDEN || '').toLowerCase();
                matchTexto = valPersonal.includes(texto) || valDoc.includes(texto);
            }

            // 3. Evaluamos los Radio Buttons
            if (radioVal !== 'null') {
                const dj = data.djSubido || data.djsubido || data.DJSUBIDO;
                const esEscaneado = (dj === 'SI');
                if (radioVal === '0') matchRadio = esEscaneado;
                if (radioVal === '1') matchRadio = !esEscaneado;
            }

            // Mostrar solo si cumple las condiciones
            return matchTipo && matchTexto && matchRadio;
        });

        // =========================================================================
        // 🔥 LÓGICA DE INDICADORES: Se calcula en base a la Sucursal (API) y Tipo
        // =========================================================================
        const todaLaData = tblEtapa4.getData();

        const dataParaTarjetas = todaLaData.filter(d => {
            const valTipo = d.TIPOTRAB2 || d.tipotrab2 || '';
            const cumpleTipo = (codTipoPer === '00') || (valTipo === tipoTxt);
            return cumpleTipo;
        });

        // Matemáticas para los indicadores
        const total = dataParaTarjetas.length;
        const escaneados = dataParaTarjetas.filter(d => {
            const dj = d.djSubido || d.djsubido || d.DJSUBIDO;
            return dj === 'SI';
        }).length;
        const pendientes = total - escaneados;

        // Mandar los números a la vista
        if (document.getElementById('contadorTotalE4')) document.getElementById('contadorTotalE4').textContent = total;
        if (document.getElementById('contadorEscaneadosE4')) document.getElementById('contadorEscaneadosE4').textContent = escaneados;
        if (document.getElementById('contadorPendientesE4')) document.getElementById('contadorPendientesE4').textContent = pendientes;
    }

    // Eventos
    document.getElementById('filtroSucursalE4')?.addEventListener('change', cargarDatosEtapa4);
    document.getElementById('filtroTipoPerE4')?.addEventListener('change', aplicarFiltrosE4);

    // Escuchando los radio buttons de estado para E4
    document.querySelectorAll('input[name="filtroEstadoE4"]').forEach(radio => radio.addEventListener('change', aplicarFiltrosE4));

    document.getElementById('buscarPersonalE4')?.addEventListener('keyup', function () {
        const valor = this.value.toLowerCase().trim();
        tblEtapa4._ultimoFiltro = valor;
        aplicarFiltrosE4();
        setTimeout(() => resaltarTexto(tblEtapa4, valor), 10);
    });

    // Cargar datos SOLO la primera vez que se hace clic en la pestaña 4
    let etapa4Cargada = false;
    document.querySelector('button[data-target="etapa4"]')?.addEventListener('click', () => {
        if (!etapa4Cargada) {
            cargarDatosEtapa4();
            etapa4Cargada = true;
        }
        // Redibujar siempre que se cambia de pestaña para ajustar anchos
        setTimeout(() => tblEtapa4.redraw(), 100);
    });

    // ============================================================
    // MODAL REPORTE DE AVANCES (ETAPA 4) — USANDO LA API DEL COMPAÑERO
    // ============================================================
    document.getElementById('btnModalPdfE4')?.addEventListener('click', async () => generarReporteModalE4('pdf'));
    document.getElementById('btnModalExcelE4')?.addEventListener('click', async () => generarReporteModalE4('excel'));

    async function generarReporteModalE4(formato) {
        const selectSucursal = document.getElementById('modalSucursalE4');
        const codSucursal = selectSucursal.value;
        const txtSucursal = selectSucursal.options[selectSucursal.selectedIndex].text;

        // Asumiendo que el value de los radio buttons es 'OPER', 'ADMIN' o '00'
        const tipoMapped = document.querySelector('input[name="modalTipoPerE4"]:checked').value;

        Swal.fire({ title: `Generando ${formato.toUpperCase()}...`, allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            // 1. Obtenemos la data original con todas las columnas (huella, firma, etc)
            let datos = await obtenerDatos(codSucursal, tipoMapped);

            if (!datos || datos.length === 0) {
                Swal.fire({ icon: 'info', title: 'Sin datos', text: 'No hay registros.' });
                return;
            }

            // 2. Filtramos para matar a los "Especiales" y que cuadre a 396
            datos = datos.filter(d => {
                const tipoTrabajador = (d.tipo || d.TIPO || d.tipoPer || d.TIPO_PER || d.tipotrab2 || '').toUpperCase();
                return !tipoTrabajador.includes('ESPECIAL');
            });

            // 3. Metadatos
            const tipoTexto = tipoMapped === 'OPER' ? 'Operativo' : (tipoMapped === 'ADMIN' ? 'Administrativo' : 'Todos');
            const meta = {
                sucursal: txtSucursal.toUpperCase() === 'TODAS' ? 'Todas' : txtSucursal,
                tipo: tipoTexto,
                fecha: new Date().toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' })
            };

            // 4. 🔥 LA CLAVE: AMBOS DEBEN RECIBIR LA VARIABLE "datos"
            if (formato === 'pdf') {
                await generarPDF(datos, meta);
            } else if (formato === 'excel') {
                await generarExcel(datos, meta);
            }

            Swal.close();
            if (window.HSOverlay) HSOverlay.close(document.getElementById('modal-reporte-avances'));

        } catch (error) {
            console.error('[Error de Reporte]', error);
            Swal.close();
            Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Error al generar el reporte.' });
        }
    }
    // ============================================================
    // FIN 1°, 2°, 3° Y 4° ETAPA / LÓGICA LEGACY (5° ETAPA) A CONTINUACIÓN
    // ============================================================

    document.getElementById('clase_brevete').addEventListener('change', actualizarCategorias);

    // ── Referencias DOM ──────────────────────────────────────
    const modalDjGestion = document.getElementById('modalDjGestion');
    const form = document.getElementById('formDatos');
    const buscarPersonalInput = document.getElementById("buscarPersonal");
    const btnNuevaDJ = document.getElementById('btnNuevaDJ');
    const cerrarModalBtn = document.getElementById('cerrarModal');
    const btnPrevisualizar = document.getElementById("btnPrevisualizar");
    const pageSizeSelect = document.getElementById("page-size");
    const pageSizeMigradoSelect = document.getElementById("page-size-migrado");

    // Pestañas
    const tabBtnPendiente = document.getElementById('tabBtnPendiente');
    const tabBtnMigrado = document.getElementById('tabBtnMigrado');
    const panelPendiente = document.getElementById('panelPendiente');
    const panelMigrado = document.getElementById('panelMigrado');

    // Familia
    const container = document.getElementById('familyContainer');
    const addBtn = document.getElementById('addFamilyMember');

    // Foto
    const inputFoto = document.getElementById("inputFoto");
    const preview = document.getElementById("previewFoto");
    const placeholder = document.getElementById("placeholderFoto");
    const btnSubir = document.getElementById("btnSubirFoto");
    const btnEliminar = document.getElementById("btnEliminarFoto");

    // SUCAMEC
    const cursoSucamec = document.getElementById("curso_sucamec");
    const institucionContainer = document.getElementById("institucion_container");
    const institucionInput = document.getElementById("institucion_laboral");

    // Ubigeos
    const departamentoSelect = document.getElementById("departamento_actual");
    const provinciaSelect = document.getElementById("provincia_actual");
    const distritoSelect = document.getElementById("distrito_actual");

    const departamentoSelectDni = document.getElementById("departamento_dni");
    const provinciaSelectDni = document.getElementById("provincia_dni");
    const distritoSelectDni = document.getElementById("distrito_dni");

    const departamentoSelectNac = document.getElementById("departamento_nac");
    const provinciaSelectNac = document.getElementById("provincia_nac");
    const distritoSelectNac = document.getElementById("distrito_nac");

    // Campos validación PDF
    const nombreDJtxt = document.getElementById("nombres_apellidos");
    const dniDJtxt = document.getElementById("dni");

    // Tagify licencia
    const inputLicencia = document.getElementById("licencia_arma");
    // const tagifyLicencia = inputLicencia ? new Tagify(inputLicencia, { maxTags: 2 }) : null;

    const API_BASE = `${VITE_URL_APP}/api/ubicacion`;

    // ============================================================
    // TABLAS TABULATOR
    // ============================================================

    // ── Tabla 2: Migración ───────────────────────────────────
    const tblPersonasMigrado = new Tabulator("#tblPersonasMigrado", {
        height: "100%",
        layout: "fitColumns",
        responsiveLayout: "collapse",
        pagination: true,
        paginationSize: 20,
        rowHeader: { formatter: "responsiveCollapse", width: 30, minWidth: 30, hozAlign: "center", resizable: false, headerSort: false },
        locale: "es",
        langs: {
            "es": {
                pagination: { first: "Primero", first_title: "Primera Página", last: "Final", last_title: "Última Página", prev: "<", prev_title: "Página Anterior", next: ">", next_title: "Página Siguiente", all: "Todo" },
                headerFilters: { default: "Filtrar..." },
                ajax: { loading: "Cargando datos...", error: "Error al cargar datos" },
                data: { empty: "No hay datos disponibles" }
            }
        },
        columns: [
            { title: "N°", formatter: "rownum", hozAlign: "center", width: 60 },
            {
                title: "Apellidos", field: "apellidos", hozAlign: "left", widthGrow: 2,
                formatter: cell => { const d = cell.getData(); return `${d.apellido1 ?? ''} ${d.apellido2 ?? ''}`.trim(); }
            },
            {
                title: "Nombres", field: "nombres", hozAlign: "left", widthGrow: 2,
                formatter: cell => { const d = cell.getData(); return `${d.nombres ?? ''} `.trim(); }
            },
            { title: "DNI", field: "dni", hozAlign: "center", widthGrow: 2 },
            { title: "Sucursal", field: "sucursal", hozAlign: "center", widthGrow: 2 },
            {
                title: "Tipo", field: "tipoPer", hozAlign: "center", widthGrow: 2,
                formatter: cell => {
                    const val = cell.getValue() ?? '';
                    let color = 'border-gray-300 bg-gray-100 text-gray-800'; // Color por defecto

                    // Usamos .includes() para que agarre tanto 4° como 5°
                    if (val.toUpperCase().includes('OPERATIVO')) {
                        color = 'border-blue-300 bg-blue-100 text-blue-800';
                    } else if (val.toUpperCase().includes('ADMINISTRATIVO')) {
                        color = 'border-purple-300 bg-purple-100 text-purple-800';
                    } else if (val.toUpperCase().includes('ESPECIAL')) {
                        color = 'border-orange-300 bg-orange-100 text-orange-800';
                    }

                    return val ? `<span class="inline-flex items-center rounded-full border ${color} px-3 py-1 text-sm font-medium whitespace-nowrap">${capitalizeWords(val)}</span>` : '';
                }
            },
            {
                title: "Migrado", field: "migrado", hozAlign: "center", widthGrow: 2,
                formatter: cell => {
                    const d = cell.getData();
                    // Ahora la BD sí enviará correctamente 'Migrado' o 'Sin Migrar'
                    const estado = d.migrado || 'NO';

                    const color = estado === 'SI'
                        ? 'border-success bg-success text-white'
                        : 'border-dark-100 bg-dark-100 text-yellow-800';

                    return `<span class="inline-flex items-center rounded-full border ${color} px-3 py-1 text-sm font-medium whitespace-nowrap">${estado}</span>`;
                }
            },

            // 🔥 NUEVA COLUMNA: FECHA DE CREACIÓN 🔥
            {
                title: "Creación",
                field: "fechaCreacionDJSip",
                hozAlign: "center",
                widthGrow: 3,
                formatter: cell => {
                    const d = cell.getData();
                    if (d.fechaCreacionDJSip != null) {
                        return `<div class="flex items-center justify-center gap-3 text-sm text-gray-700">
                            <span class="flex items-center gap-1"><i class='bx bx-calendar-plus'></i> <span>${formatearFechaHora(d.fechaCreacionDJSip).fecha}</span></span>
                            <span class="flex items-center gap-1"><i class='bx bx-time-five'></i> <span>${formatearFechaHora(d.fechaCreacionDJSip).hora}</span></span>
                        </div>`.trim();
                    }
                    return '—';
                }
            },
            {
                title: "Ultimo Cambio", field: "cambio", hozAlign: "center", widthGrow: 3,
                formatter: cell => {
                    const d = cell.getData();
                    if (d.cambio != null) {
                        return `<div class="flex items-center justify-center gap-3 text-sm text-gray-700">
                            <span class="flex items-center gap-1"><i class='bx bx-calendar'></i> <span>${formatearFechaHora(d.cambio).fecha}</span></span>
                            <span class="flex items-center gap-1"><i class='bx bx-time-five'></i> <span>${formatearFechaHora(d.cambio).hora}</span></span>
                        </div>`.trim();
                    }
                    return `${d.cambio ?? 'Sin cambios'}`.trim();
                }
            },
            // ── NUEVA COLUMNA ──────────────────────────────────────
            {
                title: "PDF", field: "pdf_generado", hozAlign: "center", widthGrow: 1,
                headerSort: false,
                formatter: cell => {
                    const d = cell.getData();
                    const cod = d.codPersonal || d.CODI_PERS || d.id;
                    const gen = estaGenerado(cod, d.cambio);
                    const titulo = gen ? 'Generado — click para resetear' : 'Pendiente';
                    const color = gen
                        ? 'color:#16a34a;font-size:18px;cursor:pointer;'
                        : 'color:#d1d5db;font-size:18px;cursor:default;';
                    return `<span title="${titulo}" style="${color}" data-pdf-cod="${cod}" data-pdf-cambio="${d.cambio || ''}">
                                ${gen ? '✅' : '○'}
                            </span>`;
                },
                cellClick: (e, cell) => {
                    const span = e.target.closest('[data-pdf-cod]');
                    if (!span) return;
                    const cod = span.getAttribute('data-pdf-cod');
                    if (!estaGenerado(cod, span.getAttribute('data-pdf-cambio'))) return;

                    Swal.fire({
                        icon: 'question',
                        title: '¿Resetear marca?',
                        text: 'Se marcará este registro como pendiente de generar PDF.',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, resetear',
                        cancelButtonText: 'Cancelar',
                    }).then(r => {
                        if (!r.isConfirmed) return;
                        desmarcarDJGenerado(cod);
                        cell.getTable().updateOrAddData([{ ...cell.getData() }]);
                        // Forzar re-render de la fila
                        cell.getRow().reformat();
                    });
                }
            },
            // ── FIN NUEVA COLUMNA ──────────────────────────────────
            {
                title: "Acciones", field: "acciones", hozAlign: "center", headerSort: false, widthGrow: 2,
                formatter: cell => {
                    const d = cell.getData();
                    const estado = d.migrado ? String(d.migrado).toUpperCase() : 'NO';

                    // Bloqueamos el botón si el estado es 'SI'
                    const disabled = estado === 'SI' ? 'disabled' : '';

                    // Agregamos clases de opacidad para que visualmente se note que está bloqueado
                    const opacityClass = disabled ? 'opacity-50 cursor-not-allowed' : 'hover:bg-success hover:text-white';

                    return `<button ${disabled} type="button" class="btn rounded-full form-btn-migrado bg-success/25 text-success ${opacityClass}" data-hs-overlay="#modalDjGestion">DJ</button>`;
                },
                cellClick: (e, cell) => {
                    const btn = e.target.closest('.form-btn-migrado');
                    if (!btn) return;
                    // Si el botón está deshabilitado, evitamos que haga clic
                    if (btn.hasAttribute('disabled')) return;

                    const rowData = cell.getRow().getData();
                    const codiPers = rowData.codPersonal || rowData.CODI_PERS || rowData.id;

                    personalDataCache.delete(`${codiPers}_pendiente`);
                    personalDataCache.delete(`${codiPers}_migracion`);

                    abrirFormularioDJ(codiPers, 'migracion');
                }
            },
        ],
    });

    // ── Tabla coincidencias ──────────────────────────────────
    const tblPersonasCN = new Tabulator("#tblPersonasCN", {
        height: "100%",
        layout: "fitDataFill",
        responsiveLayout: "collapse",
        columns: [
            { title: "Código", field: "CODI_PERS", hozAlign: "center", width: '10%' },
            { title: "Personal", field: "personal", hozAlign: "left", width: '30%' },
            { title: "Nro Documento", field: "nroDoc", hozAlign: "center", width: '15%' },
            { title: "Sucursal", field: "sucursal", hozAlign: "center", width: '18%' },
        ],
    });

    // ============================================================
    // HELPERS INTERNOS
    // ============================================================
    function getValue(id) {
        const el = document.getElementById(id);
        return el ? (el.value || '') : '';
    }

    function formatearFechaHora(fechaStr) {
        const fecha = new Date(fechaStr);
        return {
            fecha: `${String(fecha.getDate()).padStart(2, '0')}/${String(fecha.getMonth() + 1).padStart(2, '0')}/${fecha.getFullYear()}`,
            hora: `${String(fecha.getHours()).padStart(2, '0')}:${String(fecha.getMinutes()).padStart(2, '0')}`
        };
    }

    function capitalizeWords(texto) {
        return texto.toLowerCase().split(" ").map(p => p.charAt(0).toUpperCase() + p.slice(1)).join(" ");
    }

    function limpiarPreviewFoto() {
        if (inputFoto) inputFoto.value = "";
        if (preview) { preview.src = ""; preview.classList.add("hidden"); }
        if (placeholder) placeholder.classList.remove("hidden");
        if (btnEliminar) btnEliminar.classList.add("hidden");
    }

    function actualizarInstitucionVisibility() {
        if (!cursoSucamec || !institucionContainer || !institucionInput) return;
        if (cursoSucamec.value === "SI") {
            institucionContainer.classList.remove("hidden");
        } else {
            institucionContainer.classList.add("hidden");
            institucionInput.value = "";
        }
    }

    function makeFamilyRow() {
        return `
        <div class="family-row grid grid-cols-1 md:grid-cols-3 gap-4 p-4 border rounded-lg relative" data-familia-row>
            <div>
                <label class="text-sm font-medium inline-block mb-2">Parentesco</label>
                <select name="parentesco[]" class="form-select w-full">
                    <option value="">Seleccionar</option>
                    <option value="PADRE">Padre</option>    <option value="MADRE">Madre</option>
                    <option value="CONYUGE">Conyuge</option>  
                    <option value="HIJO">Hijo(a)</option>     
                  
                   
                </select>
            </div>
            <div>
                <label class="text-sm font-medium inline-block mb-2">Apellidos y Nombres</label>
                <input type="text" name="apellidosNombres[]" class="form-input w-full" placeholder="Apellidos y nombres completos">
            </div>
            <div class="flex gap-2 items-end">
                <div class="flex-1">
                    <label class="text-sm font-medium inline-block mb-2">Fecha Nacimiento</label>
                    <input type="date" name="fechaNacimiento[]" class="form-input w-full">
                </div>
                <button type="button" class="remove-family self-end px-3 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200">Eliminar</button>
            </div>
        </div>`;
        // return `
        // <div class="family-row grid grid-cols-1 md:grid-cols-3 gap-4 p-4 border rounded-lg relative" data-familia-row>
        //     <div>
        //         <label class="text-sm font-medium inline-block mb-2">Parentesco</label>
        //         <select name="parentesco[]" class="form-select w-full">
        //             <option value="">Seleccionar</option>
        //             <option value="PADRE">Padre</option>    <option value="MADRE">Madre</option>
        //             <option value="ESPOSO">Esposo</option>  <option value="ESPOSA">Esposa</option>
        //             <option value="HIJO">Hijo</option>      <option value="HIJA">Hija</option>
        //             <option value="HERMANO">Hermano</option><option value="HERMANA">Hermana</option>
        //             <option value="ABUELO">Abuelo</option>  <option value="ABUELA">Abuela</option>
        //         </select>
        //     </div>
        //     <div>
        //         <label class="text-sm font-medium inline-block mb-2">Apellidos y Nombres</label>
        //         <input type="text" name="apellidosNombres[]" class="form-input w-full" placeholder="Apellidos y nombres completos">
        //     </div>
        //     <div class="flex gap-2 items-end">
        //         <div class="flex-1">
        //             <label class="text-sm font-medium inline-block mb-2">Fecha Nacimiento</label>
        //             <input type="date" name="fechaNacimiento[]" class="form-input w-full">
        //         </div>
        //         <button type="button" class="remove-family self-end px-3 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200">Eliminar</button>
        //     </div>
        // </div>`;
    }

    function limpiarFormulario() {
        if (form) form.reset();
        //if (tagifyLicencia) tagifyLicencia.removeAllTags();

        limpiarPreviewFoto();

        if (container) { container.innerHTML = ''; container.insertAdjacentHTML('beforeend', makeFamilyRow()); }
        if (institucionContainer) institucionContainer.classList.add("hidden");
        if (institucionInput) institucionInput.value = "";

        if (provinciaSelect) provinciaSelect.innerHTML = '<option value="">Seleccionar</option>';
        if (distritoSelect) distritoSelect.innerHTML = '<option value="">Seleccionar</option>';

        if (provinciaSelectDni) provinciaSelectDni.innerHTML = '<option value="">Seleccionar</option>';
        if (distritoSelectDni) distritoSelectDni.innerHTML = '<option value="">Seleccionar</option>';

        if (provinciaSelectNac) provinciaSelectNac.innerHTML = '<option value="">Seleccionar</option>';
        if (distritoSelectNac) distritoSelectNac.innerHTML = '<option value="">Seleccionar</option>';

        setValue('dj2026_laboral_1', '');
        setValue('dj2026_laboral_2', '');
        limpiarSplitView();

        document.querySelectorAll('[data-tipo]').forEach(el => { el.style.display = ''; });
        const badgeLimp = document.getElementById('tipoBadgeModal');
        if (badgeLimp) badgeLimp.textContent = '';
    }

    function resaltarTexto(tabla, valor) {
        tabla.getRows().forEach(row => {
            row.getElement().querySelectorAll(".tabulator-cell").forEach((cell, i, cells) => {
                const field = cell.getAttribute('tabulator-field');
                if (i === cells.length - 1 || field === 'migrado' || field === 'estado' || field === 'tipoPer' || field === 'cambio') return;
                const text = cell.textContent || '';
                if (valor && text.toLowerCase().includes(valor)) {
                    const escaped = valor.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    cell.innerHTML = text.replace(new RegExp(`(${escaped})`, "gi"), "<span class='bg-warning/25'>$1</span>");
                } else {
                    cell.innerHTML = text;
                }
            });
        });
    }

    // ============================================================
    // UBIGEOS
    // ============================================================
    async function cargarProvincias(selectProv, selectDist, departamentoId, selectedProvincia = null, selectedDistrito = null) {
        if (!selectProv || !selectDist) return;
        selectProv.innerHTML = '<option value="">Seleccionar</option>';
        selectDist.innerHTML = '<option value="">Seleccionar</option>';
        if (!departamentoId) return;
        try {
            const response = await axios.get(`${API_BASE}/provincias/${departamentoId}`);
            response.data.forEach(prov => selectProv.add(new Option(prov.provi_descripcion, prov.provi_codigo)));
            if (selectedProvincia) {
                selectProv.value = selectedProvincia;
                await cargarDistritos(selectDist, selectedProvincia, selectedDistrito);
            }
        } catch (error) { console.error("Error cargando provincias:", error); }
    }

    async function cargarDistritos(selectDist, provinciaId, selectedDistrito = null) {
        if (!selectDist) return;
        selectDist.innerHTML = '<option value="">Seleccionar</option>';
        if (!provinciaId) return;
        try {
            const response = await axios.get(`${API_BASE}/distritos/${provinciaId}`);
            response.data.forEach(dist => selectDist.add(new Option(dist.dist_descripcion, dist.dist_codigo)));
            if (selectedDistrito) selectDist.value = selectedDistrito;
        } catch (error) { console.error("Error cargando distritos:", error); }
    }

    if (departamentoSelect && departamentoSelectDni && departamentoSelectNac) {
        axios.get(`${API_BASE}/departamentos`)
            .then(response => {
                response.data.forEach(dep => {
                    departamentoSelect.add(new Option(dep.depa_descripcion, dep.depa_codigo));
                    departamentoSelectDni.add(new Option(dep.depa_descripcion, dep.depa_codigo));
                    departamentoSelectNac.add(new Option(dep.depa_descripcion, dep.depa_codigo));
                });
            })
            .catch(error => console.error("Error cargando departamentos:", error));
    }



    departamentoSelect?.addEventListener("change", async function () { await cargarProvincias(provinciaSelect, distritoSelect, this.value); });
    provinciaSelect?.addEventListener("change", async function () { await cargarDistritos(distritoSelect, this.value); });

    departamentoSelectDni?.addEventListener("change", async function () { await cargarProvincias(provinciaSelectDni, distritoSelectDni, this.value); });
    provinciaSelectDni?.addEventListener("change", async function () { await cargarDistritos(distritoSelectDni, this.value); });

    departamentoSelectNac?.addEventListener("change", async function () { await cargarProvincias(provinciaSelectNac, distritoSelectNac, this.value); });
    provinciaSelectNac?.addEventListener("change", async function () { await cargarDistritos(distritoSelectNac, this.value); });

    // ============================================================
    // CARGA DE DATOS (API)
    // ============================================================
    function getPersonalMigracion() {
        axios.get(`${VITE_URL_APP}/api/get-personal-dj-migracion`)
            .then(response => {
                const datosTabla = response.data;
                tblPersonasMigrado.setData(datosTabla);
                // const sucursales = [...new Map(datosTabla.filter(d => d.sucursal).map(d => [d.codSucursal, { cod: d.codSucursal, nombre: d.sucursal }])).values()];
                // const filtroSucursal = document.getElementById('filtroSucursal');
                // if (filtroSucursal) {
                //     filtroSucursal.innerHTML = '<option value="">Todas</option>';
                //     sucursales.sort((a, b) => a.nombre.localeCompare(b.nombre)).forEach(s => filtroSucursal.add(new Option(s.nombre, s.cod)));
                // }
                aplicarFiltrosMigracion();  // ← agrega esta línea al final
                actualizarCardDesdeSP();
            })
            .catch(error => console.error("Hubo un error:", error));
    }


    window.getPersonalSoloDJMigracion = function () {

        axios.get(`${VITE_URL_APP}/api/get-personal-dj-migracion`)
            .then(response => {
                const datosTabla = response.data;
                tblPersonasMigrado.setData(datosTabla);
                // const sucursales = [...new Map(datosTabla.filter(d => d.sucursal).map(d => [d.codSucursal, { cod: d.codSucursal, nombre: d.sucursal }])).values()];
                // const filtroSucursal = document.getElementById('filtroSucursal');
                // if (filtroSucursal) {
                //     filtroSucursal.innerHTML = '<option value="">Todas</option>';
                //     sucursales.sort((a, b) => a.nombre.localeCompare(b.nombre)).forEach(s => filtroSucursal.add(new Option(s.nombre, s.cod)));
                // }
                aplicarFiltrosMigracion();  // ← agrega esta línea al final
                actualizarCardDesdeSP();
            })
            .catch(error => console.error("Hubo un error:", error));
    }


    function actualizarCardDesdeSP(sucursal = '', tipoPer = '') {
        const codSucursal = sucursal || '00';
        let codTipoPer = '00';

        if (tipoPer === 'OPERATIVO 4°') codTipoPer = '01';
        else if (tipoPer === 'OPERATIVO 5°') codTipoPer = '03';
        else if (tipoPer === 'ADMINISTRATIVO 4°') codTipoPer = '02';
        else if (tipoPer === 'ADMINISTRATIVO 5°') codTipoPer = '05';
        else if (tipoPer === 'ESPECIAL') codTipoPer = '06';

        axios.get(`${VITE_URL_APP}/api/reporte-personal-sin-migracion`, { params: { codSucursal, codTipoPer } })
            .then(response => {
                if (!response.data.success) return;
                const datos = response.data.data;
                const listos = datos.filter(d => d.SIP_CAMBIO === 'Ok').length;
                const total = datos.length;
                const elFilt = document.getElementById('contadorFiltrado');
                const elTot = document.getElementById('contadorTotal');
                animarContador(elFilt, parseInt(elFilt?.textContent.replace(/,/g, '')) || 0, listos);
                animarContador(elTot, parseInt(elTot?.textContent.replace(/,/g, '')) || 0, total);
            })
            .catch(err => console.error('Error card SP:', err));
    }


    function matchBusqueda(data, texto) {
        const palabras = texto.toLowerCase().split(/\s+/).filter(p => p);
        const campos = [
            (data.nombres ?? '').toLowerCase(),
            (data.apellido1 ?? '').toLowerCase(),
            (data.apellido2 ?? '').toLowerCase(),
            (data.dni ?? '').toLowerCase(),
        ];
        return palabras.every(palabra => campos.some(campo => campo.includes(palabra)));
    }

    // ============================================================
    // FILTROS
    // ============================================================
    function aplicarFiltrosMigracion() {
        const sucursal = document.getElementById('filtroSucursal')?.value ?? '';
        const tipoPer = document.getElementById('filtroTipoPer')?.value ?? '';
        const filtros = [];

        if (sucursal) filtros.push({ field: "codSucursal", type: "=", value: sucursal });
        if (tipoPer) filtros.push({ field: "tipoPer", type: "=", value: tipoPer });

        const texto = buscarPersonalInput?.value.toLowerCase().trim() ?? '';
        if (texto) {
            // Se manda un array interno para que Tabulator lo interprete como "OR"
            filtros.push([
                { field: "nombres", type: "like", value: texto },
                { field: "apellido1", type: "like", value: texto },
                { field: "apellido2", type: "like", value: texto },
                { field: "dni", type: "like", value: texto }
            ]);
        }

        tblPersonasMigrado.setFilter(filtros);
        actualizarCardDesdeSP(sucursal, tipoPer);
    }

    document.getElementById('filtroSucursal')?.addEventListener('change', aplicarFiltrosMigracion);
    document.getElementById('filtroTipoPer')?.addEventListener('change', aplicarFiltrosMigracion);
    document.getElementById('filtroMigrado')?.addEventListener('change', aplicarFiltrosMigracion); // ← Asegúrate de tener este

    getPersonalMigracion();

    // ============================================================
    // ESTADO ACTIVO (Migración)
    // ============================================================
    let tabActiva = 'migrado';

    // ============================================================
    // BÚSQUEDA Y RESALTADO
    // ============================================================
    buscarPersonalInput?.addEventListener("keyup", function () {
        const valor = this.value.toLowerCase().trim();
        tblPersonasMigrado._ultimoFiltro = valor;
        aplicarFiltrosMigracion();
        setTimeout(() => resaltarTexto(tblPersonasMigrado, valor), 10);
    });

    tblPersonasMigrado.on("renderComplete", () => { if (tblPersonasMigrado._ultimoFiltro) resaltarTexto(tblPersonasMigrado, tblPersonasMigrado._ultimoFiltro); });

    // ============================================================
    // BOTONES MODAL
    // ============================================================
    // btnNuevaDJ?.addEventListener('click', async function () {
    //     const { value: tipoCod, isConfirmed } = await Swal.fire({
    //         title: 'Nueva Declaración Jurada',
    //         text: 'Selecciona el tipo de personal:',
    //         input: 'radio',
    //         inputOptions: {
    //             '03': 'Operativo',
    //             '05': 'Administrativo',
    //         },
    //         inputValidator: (value) => !value && 'Debes seleccionar un tipo.',
    //         showCancelButton: true,
    //         confirmButtonText: 'Continuar',
    //         cancelButtonText: 'Cancelar',
    //     });

    //     if (!isConfirmed || !tipoCod) return;

    //     // Abrir modal con catálogos cargados
    //     await abrirFormularioDJ(null);

    //     // Setear tipo DESPUÉS de que el modal esté abierto
    //     setTimeout(() => {
    //         setValue('tipo_personal', tipoCod);
    //         aplicarVisibilidadPorTipo(tipoCod);
    //     }, 150);
    // });

    btnNuevaDJ?.addEventListener('click', function () {
        if (window.NuevaDJ) window.NuevaDJ.abrir();

    });


    cerrarModalBtn?.addEventListener('click', function () { registroSeleccionado = null; });

    // Familia
    addBtn?.addEventListener('click', e => { e.preventDefault(); if (container) container.insertAdjacentHTML('beforeend', makeFamilyRow()); });
    container?.addEventListener('click', e => {
        const btn = e.target.closest('button.remove-family');
        if (!btn) return;
        e.preventDefault(); e.stopPropagation();
        btn.closest('.family-row')?.remove();
    });

    // SUCAMEC
    cursoSucamec?.addEventListener("change", () => actualizarInstitucionVisibility());

    // Foto
    btnSubir?.addEventListener("click", () => inputFoto?.click());
    inputFoto?.addEventListener("change", () => {
        const file = inputFoto.files?.[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => {
                if (preview) { preview.src = e.target.result; preview.classList.remove("hidden"); }
                placeholder?.classList.add("hidden");
                btnEliminar?.classList.remove("hidden");
            };
            reader.readAsDataURL(file);
        }
    });
    btnEliminar?.addEventListener("click", () => limpiarPreviewFoto());

    // Page size
    pageSizeMigradoSelect?.addEventListener("change", function () { tblPersonasMigrado.setPageSize(parseInt(this.value)); });

    // ============================================================
    // PREVISUALIZAR PDF
    // ============================================================
    btnPrevisualizar?.addEventListener("click", function (e) {
        e.preventDefault();
        const camposObligatorios = [{ input: nombreDJtxt, nombre: 'Nombre' }, { input: dniDJtxt, nombre: 'DNI' }];
        const campoFaltante = camposObligatorios.find(c => !c.input || !String(c.input.value ?? '').trim());
        if (campoFaltante) {
            Swal.fire({ icon: 'warning', title: 'Campos obligatorios', text: `Falta completar: ${campoFaltante.nombre}` });
            campoFaltante.input?.focus();
            return;
        }
        generarDeclaracionJuradaPDF();
    });

    // ============================================================
    // GUARDAR FORMULARIO
    // ============================================================
    if (form) {
        console.log('✅ form encontrado, registrando listener submit');

        form.addEventListener('submit', async (e) => {
            console.log('🔥 submit disparado');
            e.preventDefault();
            e.stopPropagation(); // ← AGREGAR ESTO

            console.log('🔍 form element:', form);
            console.log('🔍 form action:', form.action);

            const btnGuardar = document.getElementById('btnGuardar');
            if (btnGuardar) btnGuardar.disabled = true;

            try {
                const formData = new FormData(form);

                // Verificar que formData tiene datos
                console.log('📋 FormData entries:');
                for (let [key, val] of formData.entries()) {
                    console.log(`  ${key}:`, val);
                }

                const data = Object.fromEntries(formData.entries());
                console.log('📦 data object:', data);

                const tabActiva = document.querySelector('.tab-btn.border-b-white')?.dataset?.tab ?? 'pendiente';
                const payload = {
                    ...data,
                    source: tabActiva,
                    FAM_PARENTESCO: formData.getAll('parentesco[]'),
                    FAM_NOMBRES: formData.getAll('apellidosNombres[]'),
                    FAM_FECHA_NACI: formData.getAll('fechaNacimiento[]'),
                    dj2026_descripcion: formData.getAll('ocupacion_alterna[]')
                };

                console.log('📤 Payload completo:', payload);
                console.log('🌐 URL:', `${VITE_URL_APP}/api/dj/save-dj-completo`);

                const esNuevaDJ = !payload.cod_postulante || String(payload.cod_postulante).trim() === '';

                const url = esNuevaDJ
                    ? `${VITE_URL_APP}/api/dj/save-nueva-dj`      // ← endpoint para nueva
                    : `${VITE_URL_APP}/api/dj/save-dj-completo`;  // ← endpoint para existente

                console.log(esNuevaDJ ? '🆕 Nueva DJ' : '✏️ DJ Existente', payload);


                const response = await axios.post(url, payload);
                console.log('✅ Response:', response);

                if (response.status === 200 || response.status === 201) {
                    Swal.fire({ icon: 'success', title: '¡Éxito!', text: 'La Declaración Jurada se guardó correctamente.' });

                    const modal = document.getElementById('modalDjGestion');
                    if (modal) {
                        if (window.HSOverlay) { try { HSOverlay.close(modal); } catch (e) { } }
                        modal.classList.add('hidden');
                        modal.classList.remove('hs-overlay-open');
                        document.querySelectorAll('.hs-overlay-backdrop').forEach(el => el.remove());
                        document.body.classList.remove('overflow-hidden');
                        document.body.style.overflow = '';
                    }

                    getPersonalMigracion();
                }
            } catch (error) {
                console.error('❌ Error completo:', error);
                console.error('❌ error.response:', error.response);
                console.error('❌ error.message:', error.message);

                let msg = 'Hubo un error al guardar los datos.';
                if (error.response?.data?.message) msg = error.response.data.message;
                else if (error.response?.data?.errors) msg = Object.values(error.response.data.errors).flat().join('<br>');

                Swal.fire({ icon: 'error', title: 'Error', html: msg });
            } finally {
                if (btnGuardar) btnGuardar.disabled = false;
            }
        });
    }

    // ============================================================
    // DESCARGA MASIVA (ZIP)
    // ============================================================
    const btnDescargarDJs = document.getElementById('btnDescargarDJs');

    btnDescargarDJs?.addEventListener('click', async function () {
        const filasVisibles = tblPersonasMigrado.getData("active");
        if (!filasVisibles || filasVisibles.length === 0) {
            Swal.fire({ icon: 'info', title: 'Sin resultados', text: 'No hay registros visibles para descargar.' });
            return;
        }

        const confirmacion = await Swal.fire({
            icon: 'question', title: 'Descarga masiva de DJ\'s',
            html: `Se generarán <b>${filasVisibles.length}</b> PDF(s) en un archivo ZIP.<br>¿Desea continuar?`,
            showCancelButton: true, confirmButtonText: 'Sí, descargar', cancelButtonText: 'Cancelar'
        });
        if (!confirmacion.isConfirmed) return;

        const zip = new JSZip();
        let generados = 0, errores = 0;

        Swal.fire({ title: 'Generando PDFs...', html: `Procesando <b>0</b> de <b>${filasVisibles.length}</b>`, allowOutsideClick: false, allowEscapeKey: false, didOpen: () => Swal.showLoading() });

        try { await cargarCatalogos(); } catch { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los catálogos.' }); return; }

        for (let i = 0; i < filasVisibles.length; i++) {
            const fila = filasVisibles[i];
            const codiPers = fila.codPersonal || fila.CODI_PERS || fila.id;
            Swal.update({ html: `Procesando <b>${i + 1}</b> de <b>${filasVisibles.length}</b><br><small>${fila.nombres || codiPers}</small>` });
            try {
                await cargarDatosPersonales(codiPers);
                await new Promise(resolve => setTimeout(resolve, 600));
                const resultado = await generarDeclaracionJuradaPDF(true);
                if (resultado?.blob) { zip.file(resultado.filename, resultado.blob); generados++; } else errores++;
            } catch (err) { console.error(`Error generando PDF para ${codiPers}:`, err); errores++; }
        }

        if (generados === 0) { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar ningún PDF.' }); return; }

        Swal.update({ html: 'Comprimiendo archivos...' });
        try {
            const contenidoZip = await zip.generateAsync({ type: 'blob' });
            const f = new Date();
            const ts = f.getFullYear() + String(f.getMonth() + 1).padStart(2, '0') + String(f.getDate()).padStart(2, '0') + '_' + String(f.getHours()).padStart(2, '0') + String(f.getMinutes()).padStart(2, '0');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(contenidoZip);
            link.download = `DJ_Masivo_${ts}.zip`;
            document.body.appendChild(link); link.click(); document.body.removeChild(link);
            URL.revokeObjectURL(link.href);
            Swal.fire({ icon: 'success', title: 'Descarga completada', html: `Se generaron <b>${generados}</b> PDF(s) correctamente.` + (errores > 0 ? `<br><small class="text-red-500">${errores} con error.</small>` : '') });
        } catch { Swal.fire({ icon: 'error', title: 'Error', text: 'Hubo un error al generar el archivo ZIP.' }); }
    });

    // ============================================================
    // DJ UNIFICADO (un solo PDF — Migración)
    // ============================================================
    const btnDJUnificado = document.getElementById('btnDJUnificado');
    const btnDJUnificadoMigrado = document.getElementById('btnDJUnificadoMigrado');

    btnDJUnificado?.addEventListener('click', async function () {
        const filasVisibles = tblPersonasMigrado.getData("active");
        if (!filasVisibles || filasVisibles.length === 0) {
            Swal.fire({ icon: 'info', title: 'Sin resultados', text: 'No hay registros visibles para descargar.' });
            return;
        }

        const confirmacion = await Swal.fire({
            icon: 'question', title: 'DJ Unificado',
            html: `Se generará <b>1 PDF</b> con las <b>${filasVisibles.length}</b> declaraciones juradas.<br>¿Desea continuar?`,
            showCancelButton: true, confirmButtonText: 'Sí, generar', cancelButtonText: 'Cancelar'
        });
        if (!confirmacion.isConfirmed) return;

        await _generarUnificado(filasVisibles, 'DJ_Unificado');
    });


    btnDJUnificadoMigrado?.addEventListener('click', async function () {

        const todasMigradas = tblPersonasMigrado.getData("active");

        if (!todasMigradas.length) {
            Swal.fire({ icon: 'info', title: 'Sin resultados', text: 'No hay registros visibles en la tabla.' });
            return;
        }

        const pendientes = todasMigradas.filter(f => !estaGenerado(f.codPersonal || f.CODI_PERS || f.id, f.cambio));
        const yaGenerados = todasMigradas.filter(f => estaGenerado(f.codPersonal || f.CODI_PERS || f.id, f.cambio));

        const { value: opcion, isConfirmed } = await Swal.fire({
            title: 'DJ Unificado — Migrados',
            html: `
            <div style="display:flex;flex-direction:column;gap:10px;text-align:left;font-size:13px;padding:4px 0;">
                <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;" id="lbl-pend">
                    <input type="radio" name="djopcion" value="pendientes" ${pendientes.length ? '' : 'disabled'}
                        style="width:16px;height:16px;cursor:pointer;accent-color:#6366f1;">
                    <div>
                        <div style="font-weight:600;color:${pendientes.length ? '#111827' : '#9ca3af'};">
                            Solo pendientes
                            <span style="margin-left:6px;background:${pendientes.length ? '#dcfce7' : '#f3f4f6'};color:${pendientes.length ? '#16a34a' : '#9ca3af'};font-size:11px;padding:1px 8px;border-radius:20px;font-weight:700;">
                                ${pendientes.length}
                            </span>
                        </div>
                        <div style="font-size:11px;color:#6b7280;margin-top:2px;">
                            Registros sin ✅ o con cambios nuevos desde la última generación
                        </div>
                    </div>
                </label>
                <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;" id="lbl-todos">
                    <input type="radio" name="djopcion" value="todos"
                        style="width:16px;height:16px;cursor:pointer;accent-color:#6366f1;">
                    <div>
                        <div style="font-weight:600;color:#111827;">
                            Todos los migrados
                            <span style="margin-left:6px;background:#dbeafe;color:#1e40af;font-size:11px;padding:1px 8px;border-radius:20px;font-weight:700;">
                                ${todasMigradas.length}
                            </span>
                        </div>
                        <div style="font-size:11px;color:#6b7280;margin-top:2px;">
                            Incluye los ${yaGenerados.length} ya generados anteriormente
                        </div>
                    </div>
                </label>
            </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Generar PDF',
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                const radios = document.querySelectorAll('input[name="djopcion"]');
                radios.forEach(r => {
                    r.addEventListener('change', () => {
                        document.getElementById('lbl-pend').style.borderColor = r.value === 'pendientes' && r.checked ? '#6366f1' : '#e5e7eb';
                        document.getElementById('lbl-todos').style.borderColor = r.value === 'todos' && r.checked ? '#6366f1' : '#e5e7eb';
                    });
                });
                const def = pendientes.length ? 'pendientes' : 'todos';
                const defRadio = document.querySelector(`input[name="djopcion"][value="${def}"]`);
                if (defRadio) {
                    defRadio.checked = true;
                    document.getElementById(def === 'pendientes' ? 'lbl-pend' : 'lbl-todos').style.borderColor = '#6366f1';
                }
            },
            preConfirm: () => {
                const sel = document.querySelector('input[name="djopcion"]:checked');
                if (!sel) { Swal.showValidationMessage('Selecciona una opción.'); return false; }
                return sel.value;
            }
        });

        if (!isConfirmed) return;

        const filasSeleccionadas = opcion === 'pendientes' ? pendientes : todasMigradas;

        if (!filasSeleccionadas.length) {
            Swal.fire({ icon: 'info', title: 'Sin pendientes', text: 'Todos los registros ya fueron generados. Usa "Todos" para regenerar.' });
            return;
        }

        const confirmacion = await Swal.fire({
            icon: 'question',
            title: 'Confirmar generación',
            html: `Se generará <b>1 PDF</b> con <b>${filasSeleccionadas.length}</b> declaración(es).<br>¿Desea continuar?`,
            showCancelButton: true,
            confirmButtonText: 'Sí, generar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmacion.isConfirmed) return;

        // Se usa 'pendiente' como source para que el endpoint devuelva los datos más frescos
        const resultadoGen = await _generarUnificado(filasSeleccionadas, 'DJ_Unificado_Migrados', 'pendiente');

        if (resultadoGen?.ok && resultadoGen.generadosOk.length) {
            const marcados = resultadoGen.generadosOk.map(fila1 => {
                return {
                    codPersonal: fila1.codPersonal || fila1.CODI_PERS || fila1.id,
                    fechaCambio: fila1.cambio
                };
            }).filter(x => x.codPersonal);

            marcarDJGeneradosBatch(marcados);
        }

        tblPersonasMigrado.redraw(true);
    });

    // btnDJUnificadoMigrado?.addEventListener('click', async function () {

    //     // Todas las filas migradas visibles en la tabla
    //     const todasMigradas = tblPersonasMigrado.getData("active")
    //         .filter(fila => fila.migrado === 'Migrado');

    //     if (!todasMigradas.length) {
    //         Swal.fire({ icon: 'info', title: 'Sin resultados', text: 'No hay registros migrados visibles.' });
    //         return;
    //     }

    //     // Separar pendientes vs ya generados
    //     const pendientes   = todasMigradas.filter(f => !estaGenerado(f.codPersonal || f.CODI_PERS || f.id, f.cambio));
    //     const yaGenerados  = todasMigradas.filter(f =>  estaGenerado(f.codPersonal || f.CODI_PERS || f.id, f.cambio));

    //     // Elegir qué generar
    //     const { value: opcion, isConfirmed } = await Swal.fire({
    //         title: 'DJ Unificado — Migrados',
    //         html: `
    //             <div style="display:flex;flex-direction:column;gap:10px;text-align:left;font-size:13px;padding:4px 0;">
    //                 <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;" id="lbl-pend">
    //                     <input type="radio" name="djopcion" value="pendientes" ${pendientes.length ? '' : 'disabled'}
    //                         style="width:16px;height:16px;cursor:pointer;accent-color:#6366f1;">
    //                     <div>
    //                         <div style="font-weight:600;color:${pendientes.length ? '#111827' : '#9ca3af'};">
    //                             Solo pendientes
    //                             <span style="margin-left:6px;background:${pendientes.length ? '#dcfce7' : '#f3f4f6'};color:${pendientes.length ? '#16a34a' : '#9ca3af'};font-size:11px;padding:1px 8px;border-radius:20px;font-weight:700;">
    //                                 ${pendientes.length}
    //                             </span>
    //                         </div>
    //                         <div style="font-size:11px;color:#6b7280;margin-top:2px;">
    //                             Registros sin ✅ o con cambios nuevos desde la última generación
    //                         </div>
    //                     </div>
    //                 </label>
    //                 <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;" id="lbl-todos">
    //                     <input type="radio" name="djopcion" value="todos"
    //                         style="width:16px;height:16px;cursor:pointer;accent-color:#6366f1;">
    //                     <div>
    //                         <div style="font-weight:600;color:#111827;">
    //                             Todos los migrados
    //                             <span style="margin-left:6px;background:#dbeafe;color:#1e40af;font-size:11px;padding:1px 8px;border-radius:20px;font-weight:700;">
    //                                 ${todasMigradas.length}
    //                             </span>
    //                         </div>
    //                         <div style="font-size:11px;color:#6b7280;margin-top:2px;">
    //                             Incluye los ${yaGenerados.length} ya generados anteriormente
    //                         </div>
    //                     </div>
    //                 </label>
    //             </div>
    //         `,
    //         showCancelButton: true,
    //         confirmButtonText: 'Generar PDF',
    //         cancelButtonText: 'Cancelar',
    //         didOpen: () => {
    //             // Seleccionar por defecto "pendientes" si hay, sino "todos"
    //             const radios = document.querySelectorAll('input[name="djopcion"]');
    //             radios.forEach(r => {
    //                 r.addEventListener('change', () => {
    //                     document.getElementById('lbl-pend').style.borderColor  = r.value === 'pendientes' && r.checked ? '#6366f1' : '#e5e7eb';
    //                     document.getElementById('lbl-todos').style.borderColor = r.value === 'todos'      && r.checked ? '#6366f1' : '#e5e7eb';
    //                 });
    //             });
    //             const def = pendientes.length ? 'pendientes' : 'todos';
    //             const defRadio = document.querySelector(`input[name="djopcion"][value="${def}"]`);
    //             if (defRadio) {
    //                 defRadio.checked = true;
    //                 document.getElementById(def === 'pendientes' ? 'lbl-pend' : 'lbl-todos').style.borderColor = '#6366f1';
    //             }
    //         },
    //         preConfirm: () => {
    //             const sel = document.querySelector('input[name="djopcion"]:checked');
    //             if (!sel) { Swal.showValidationMessage('Selecciona una opción.'); return false; }
    //             return sel.value;
    //         }
    //     });

    //     if (!isConfirmed) return;

    //     const filasFinales = opcion === 'pendientes' ? pendientes : todasMigradas;

    //     if (!filasFinales.length) {
    //         Swal.fire({ icon: 'info', title: 'Sin pendientes', text: 'Todos los registros ya fueron generados. Usa "Todos" para regenerar.' });
    //         return;
    //     }

    //     const confirmacion = await Swal.fire({
    //         icon: 'question',
    //         title: 'Confirmar generación',
    //         html: `Se generará <b>1 PDF</b> con <b>${filasFinales.length}</b> declaración(es).<br>¿Desea continuar?`,
    //         showCancelButton: true,
    //         confirmButtonText: 'Sí, generar',
    //         cancelButtonText: 'Cancelar'
    //     });
    //     if (!confirmacion.isConfirmed) return;

    //     // Generar y marcar al terminar
    //     // await _generarUnificado(filasFinales, 'DJ_Unificado_Migrados');

    //     // // Marcar todos los incluidos como generados
    //     // filasFinales.forEach(fila => {
    //     //     const cod = fila.codPersonal || fila.CODI_PERS || fila.id;
    //     //     marcarDJGenerado(cod, fila.cambio);
    //     // });

    //     const resultadoGen = await _generarUnificado(filasFinales, 'DJ_Unificado_Migrados');

    //     // if (resultadoGen?.ok) {
    //     //     resultadoGen.generadosOk.forEach(fila => {
    //     //         const cod = fila.codPersonal || fila.CODI_PERS || fila.id;
    //     //         marcarDJGenerado(cod, fila.cambio);
    //     //     });
    //     // }
    //     if (resultadoGen?.ok && resultadoGen.generadosOk.length) {
    //         marcarDJGeneradosBatch(
    //             resultadoGen.generadosOk.map(fila => ({
    //                 codPersonal: fila.codPersonal || fila.CODI_PERS || fila.id,
    //                 fechaCambio: fila.cambio
    //             }))
    //         );
    //     }

    //     tblPersonasMigrado.redraw(true);

    //     // Refrescar columna PDF en la tabla
    //     tblPersonasMigrado.redraw(true);
    // });

    // ============================================================
    // DJ UNIFICADO — Pendientes
    // ============================================================
    const btnDJUnificado_PEN = document.getElementById('btnDJUnificado_PEN');

    btnDJUnificado_PEN?.addEventListener('click', async function () {
        const filasVisibles = tblPersonas.getData("active");
        if (!filasVisibles || filasVisibles.length === 0) {
            Swal.fire({ icon: 'info', title: 'Sin resultados', text: 'No hay registros visibles para descargar.' });
            return;
        }

        const confirmacion = await Swal.fire({
            icon: 'question', title: 'DJ Unificado — Pendientes',
            html: `Se generará <b>1 PDF</b> con las <b>${filasVisibles.length}</b> declaraciones.<br>¿Desea continuar?`,
            showCancelButton: true, confirmButtonText: 'Sí, generar', cancelButtonText: 'Cancelar'
        });
        if (!confirmacion.isConfirmed) return;

        await _generarUnificado(filasVisibles, 'DJ_Unificado_Pendientes', 'pendiente');
    });

    async function obtenerDatosPersonales(codiPers, source = 'migracion') {
        const response = await axios.get(`${API_URL}/dj/get-personal-data`, {
            params: { codi_pers: codiPers, source }
        });
        return response.data;
    }


    // Helper interno para unificar PDFs
    // async function _generarUnificado(filas, nombreBase, source = 'migracion') {
    //     const pdfBlobs = [];
    //     let errores = 0;

    //     Swal.fire({ 
    //         title: 'Generando DJ Unificado...', 
    //         html: `Procesando <b>0</b> de <b>${filas.length}</b>`, allowOutsideClick: false, showCloseButton: false, showConfirmButton: false,
    //         allowEscapeKey: false, didOpen: () => Swal.showLoading() });

    //     try { await cargarCatalogos(); } catch { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los catálogos.' }); return; }

    //     for (let i = 0; i < filas.length; i++) {
    //         const fila = filas[i];
    //         const codiPers = fila.codPersonal || fila.CODI_PERS || fila.id;

    //         Swal.update({
    //             html: `Procesando <b>${i + 1}</b> de <b>${filas.length}</b><br><small>${fila.nombres || codiPers}</small>`
    //         });

    //         try {
    //             const payload = await obtenerDatosPersonales(codiPers, source);
    //             await llenarFormulario(payload.data);
    //             renderFamiliares(payload.familiares);

    //             const resultado = await generarDeclaracionJuradaPDF(true);
    //             if (resultado?.blob) {
    //                 pdfBlobs.push(await resultado.blob.arrayBuffer());
    //             } else {
    //                 errores++;
    //             }
    //         } catch (err) {
    //             console.error(`Error generando PDF para ${codiPers}:`, err);
    //             errores++;
    //         }
    //         await new Promise(resolve => setTimeout(resolve, 250));
    //     }

    //     if (pdfBlobs.length === 0) { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar ningún PDF.' }); return; }

    //     Swal.update({ html: 'Unificando documentos...' });
    //     try {
    //         const { PDFDocument } = PDFLib;
    //         const mergedPdf = await PDFDocument.create();
    //         for (const buf of pdfBlobs) {
    //             const donor = await PDFDocument.load(buf);
    //             const pages = await mergedPdf.copyPages(donor, donor.getPageIndices());
    //             pages.forEach(p => mergedPdf.addPage(p));
    //         }
    //         const mergedBytes = await mergedPdf.save();
    //         const f  = new Date();
    //         const ts = f.getFullYear() + String(f.getMonth()+1).padStart(2,'0') + String(f.getDate()).padStart(2,'0') + '_' + String(f.getHours()).padStart(2,'0') + String(f.getMinutes()).padStart(2,'0');
    //         const blob = new Blob([mergedBytes], { type: 'application/pdf' });
    //         const link = document.createElement('a');
    //         link.href = URL.createObjectURL(blob);
    //         link.download = `${nombreBase}_${ts}.pdf`;
    //         document.body.appendChild(link); link.click(); document.body.removeChild(link);
    //         URL.revokeObjectURL(link.href);
    //         Swal.fire({ icon: 'success', title: 'DJ Unificado generado', html: `Se unificaron <b>${pdfBlobs.length}</b> declaraciones.` + (errores > 0 ? `<br><small class="text-red-500">${errores} con error.</small>` : '') });
    //     } catch { Swal.fire({ icon: 'error', title: 'Error', text: 'Hubo un error al unificar los documentos.' }); }
    // }
    async function _generarUnificado(filas, nombreBase, source = 'migracion') {
        const pdfBlobs = [];
        const generadosOk = [];
        const generadosError = [];

        Swal.fire({
            title: 'Generando DJ Unificado...',
            html: `Procesando <b>0</b> de <b>${filas.length}</b>`,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            await cargarCatalogos();
        } catch {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los catálogos.' });
            return { ok: false, generadosOk, generadosError };
        }

        for (let i = 0; i < filas.length; i++) {
            const fila = filas[i];
            const codiPers = fila.codPersonal || fila.CODI_PERS || fila.id;

            Swal.update({
                html: `Procesando <b>${i + 1}</b> de <b>${filas.length}</b><br><small>${fila.nombres || codiPers}</small>`
            });

            try {
                //const payload = await obtenerDatosPersonales(codiPers, source);
                const payload = await obtenerDatosConRetry(codiPers, source);
                await llenarFormulario(payload.data);
                renderFamiliares(payload.familiares);

                const resultado = await generarDeclaracionJuradaPDF(true);

                if (!resultado?.blob) {
                    generadosError.push({ fila, motivo: 'No se obtuvo blob' });
                    continue;
                }

                const buffer = await resultado.blob.arrayBuffer();

                if (!buffer || buffer.byteLength === 0) {
                    generadosError.push({ fila, motivo: 'PDF vacío' });
                    continue;
                }

                pdfBlobs.push(buffer);
                generadosOk.push(fila);

            } catch (err) {
                console.error(`Error generando PDF para ${codiPers}:`, err);
                generadosError.push({ fila, motivo: err?.message || 'Error desconocido' });
            }

            // await new Promise(resolve => setTimeout(resolve, 250));
            // await new Promise(r => setTimeout(r, PAUSA_ENTRE_REGISTROS));
            const pausa = filas.length > 30
                ? PAUSA_ENTRE_REGISTROS * 1.5
                : PAUSA_ENTRE_REGISTROS;
            await new Promise(r => setTimeout(r, pausa));
        }

        if (pdfBlobs.length === 0) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar ningún PDF.' });
            return { ok: false, generadosOk, generadosError };
        }

        Swal.update({ html: 'Unificando documentos...' });

        try {
            const { PDFDocument } = PDFLib;
            const mergedPdf = await PDFDocument.create();

            for (const buf of pdfBlobs) {
                const donor = await PDFDocument.load(buf);
                const pages = await mergedPdf.copyPages(donor, donor.getPageIndices());
                pages.forEach(p => mergedPdf.addPage(p));
            }

            const mergedBytes = await mergedPdf.save();

            if (!mergedBytes || mergedBytes.length === 0) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'El PDF unificado se generó vacío.' });
                return { ok: false, generadosOk: [], generadosError: filas.map(f => ({ fila: f, motivo: 'PDF unificado vacío' })) };
            }

            const f = new Date();
            const ts = f.getFullYear()
                + String(f.getMonth() + 1).padStart(2, '0')
                + String(f.getDate()).padStart(2, '0')
                + '_'
                + String(f.getHours()).padStart(2, '0')
                + String(f.getMinutes()).padStart(2, '0');

            const blob = new Blob([mergedBytes], { type: 'application/pdf' });

            if (blob.size === 0) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'El archivo final quedó vacío.' });
                return { ok: false, generadosOk: [], generadosError: filas.map(f => ({ fila: f, motivo: 'Blob final vacío' })) };
            }

            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `${nombreBase}_${ts}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(link.href);

            Swal.fire({
                icon: generadosError.length > 0 ? 'warning' : 'success',
                title: 'Resultado de generación',
                html: `
                    <div style="text-align:left; font-size:14px; line-height:1.6;">
                        <div>
                            <span style="font-weight:600;">Generados:</span>
                            <span style="color:#15803d; font-weight:700;">${generadosOk.length} / ${filas.length}</span>
                        </div>
                        <div>
                            <span style="font-weight:600;">Fallidos:</span>
                            <span style="color:#b91c1c; font-weight:700;">${generadosError.length}</span>
                        </div>

                        ${generadosError.length > 0
                        ? `
                                <div style="margin-top:10px; font-size:12px; color:#b91c1c;">
                                    <b>Detalle de fallas:</b><br>
                                    ${generadosError.map(x => {
                            const fila = x.fila || {};
                            const nombre = fila.nombres || fila.CODI_PERS || fila.id || 'Registro';
                            return `• ${nombre}: ${x.motivo}`;
                        }).join('<br>')}
                                </div>
                                `
                        : ''
                    }
                    </div>
                `
            });

            return { ok: true, generadosOk, generadosError };

        } catch (err) {
            console.error('Error unificando documentos:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Hubo un error al unificar los documentos.' });
            return { ok: false, generadosOk: [], generadosError: filas.map(f => ({ fila: f, motivo: 'Fallo en unificación' })) };
        }
    }

    // ============================================================
    // RESIZER SPLIT VIEW
    // ============================================================
    (function initResizer() {
        const wrapper = document.getElementById('djSplitWrapper');
        const resizer = document.getElementById('djResizer');
        const panelBk = document.getElementById('panelBackup');
        if (!wrapper || !resizer || !panelBk) return;

        let isResizing = false, startX = 0, startW = 0;

        resizer.addEventListener('mousedown', e => {
            isResizing = true; startX = e.clientX; startW = panelBk.offsetWidth;
            resizer.classList.add('dragging');
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
            e.preventDefault();
        });
        document.addEventListener('mousemove', e => {
            if (!isResizing) return;
            const newW = Math.max(220, Math.min(startW + (e.clientX - startX), wrapper.offsetWidth - 280));
            panelBk.style.width = newW + 'px'; panelBk.style.flexBasis = newW + 'px';
        });
        document.addEventListener('mouseup', () => {
            if (!isResizing) return;
            isResizing = false; resizer.classList.remove('dragging');
            document.body.style.cursor = ''; document.body.style.userSelect = '';
        });
        resizer.addEventListener('touchstart', e => {
            isResizing = true; startX = e.touches[0].clientX; startW = panelBk.offsetWidth;
            e.preventDefault();
        }, { passive: false });
        document.addEventListener('touchmove', e => {
            if (!isResizing) return;
            const newW = Math.max(220, Math.min(startW + (e.touches[0].clientX - startX), wrapper.offsetWidth - 280));
            panelBk.style.width = newW + 'px'; panelBk.style.flexBasis = newW + 'px';
        });
        document.addEventListener('touchend', () => { isResizing = false; });
        resizer.addEventListener('dblclick', () => { panelBk.style.width = '38%'; panelBk.style.flexBasis = '38%'; });
    })();

}); // fin DOMContentLoaded

// ============================================================
// FUNCIONES GLOBALES (fuera del DOMContentLoaded)
// ============================================================

// ── Abrir modal DJ ──────────────────────────────────────────
async function abrirFormularioDJ(codiPers = null, source = 'migracion') {
    try {
        const modal = document.getElementById('modalDjGestion');

        if (codiPers == null) {
            Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            // En lugar de limpiarFormulario() — reset manual con lo que sí es global
            limpiarSplitView();
            setValue('cod_postulante', '');
            setValue('tipo_personal', '');

            await cargarCatalogos();

            // Cargar departamentos para los 3 ubigeos
            const depts = await getUbicacionCached({ type: 'dept' });
            populateSelect('#departamento_actual', depts);
            populateSelect('#departamento_dni', depts);
            populateSelect('#departamento_nac', depts);

            Swal.close();

            if (modal) {
                if (window.HSOverlay) HSOverlay.open(modal);
                else modal.classList.remove('hidden');
            }

            setTimeout(() => {
                const tipo = document.getElementById('tipo_personal')?.value?.trim() ?? '';
                aplicarVisibilidadPorTipo(tipo);
            }, 80);

        } else {
            // ← DJ existente: flujo normal sin cambios
            Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            await cargarCatalogos(source);
            await cargarDatosPersonales(codiPers, source);

            Swal.close();

            if (modal) {
                if (window.HSOverlay) HSOverlay.open(modal);
                else modal.classList.remove('hidden');
            }

            if (source === 'migracion' || source === 'pendiente') await cargarDatosBackup(codiPers);
            else limpiarSplitView();
        }

    } catch (error) {
        console.error('Error:', error);
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el formulario: ' + error.message });
    }
}

// ── Catálogos ────────────────────────────────────────────────
let catalogosCache = null;
let catalogosPromise = null;

async function cargarCatalogos(source = 'migracion') {
    if (catalogosCache) return catalogosCache;
    if (catalogosPromise) return catalogosPromise;

    catalogosPromise = axios.get(`${API_URL}/dj/get-catalogs`)
        .then(response => {
            const { grados, carreras, instituciones, sangre, estados_civiles, tipos_arma } = response.data;

            populateSelect('#selGrado', grados);
            populateSelect('#selCarrera', carreras);
            populateSelect('#selInstitucion', instituciones);
            populateSelect('#PERS_GRUP_SANGRE', sangre);
            populateSelect('#PERS_ESTADO_CIVIL', estados_civiles);
            populateSelect('#LAB_TIPO_ARMA', tipos_arma);
            // Poblar estado civil del modal original
            populateSelect('#estado_civil', response.data.estados_civiles ?? []);

            // Poblar sistema previsional del modal original
            populateSelect('#sistema_previsional', response.data.sistemas_previsionales ?? []);

            window.allCarreras = carreras;
            catalogosCache = response.data;
            return response.data;
        })
        .finally(() => {
            catalogosPromise = null;
        });

    return catalogosPromise;
}

// ── Datos personales ─────────────────────────────────────────
const personalDataCache = new Map();
const personalDataPromise = new Map();

async function cargarDatosPersonales(codiPers, source = 'migracion') {
    const key = `${codiPers}_${source}`;

    if (personalDataCache.has(key)) {
        const cached = personalDataCache.get(key);
        await llenarFormulario(cached.data);
        renderFamiliares(cached.familiares);
        return cached;
    }

    if (personalDataPromise.has(key)) {
        const pending = await personalDataPromise.get(key);
        await llenarFormulario(pending.data);
        renderFamiliares(pending.familiares);
        return pending;
    }

    const req = axios.get(`${API_URL}/dj/get-personal-data`, {
        params: { codi_pers: codiPers, source }
    }).then(response => {
        personalDataCache.set(key, response.data);
        return response.data;
    }).finally(() => {
        personalDataPromise.delete(key);
    });

    personalDataPromise.set(key, req);

    const result = await req;
    await llenarFormulario(result.data);
    renderFamiliares(result.familiares);
    return result;
}

// ── Llenar formulario ────────────────────────────────────────
async function llenarFormulario(data) {
    setValue('cod_postulante', data.CODI_PERS);

    const tipotrab = data.PERS_TIPOTRAB ? String(data.PERS_TIPOTRAB).trim() : '';
    console.log('TIPO TRAB:', tipotrab); // ← agregar esto

    setValue('tipo_personal', tipotrab);
    aplicarVisibilidadPorTipo(tipotrab);

    setValue('#nombres_apellidos', `${data.NOMB_1 || ''} ${data.NOMB_2 || ''} ${data.APEL_1 || ''} ${data.APEL_2 || ''}`);
    setValue('#nombre1', data.NOMB_1 || '');
    setValue('#nombre2', data.NOMB_2 || '');
    setValue('#apellido_paterno', data.APEL_1 || '');
    setValue('#apellido_materno', data.APEL_2 || '');
    setValue('#dni', data.NRO_DOCU_IDEN ? data.NRO_DOCU_IDEN.trim() : '');
    setValue('#caduca', formatDateForInput(data.PERS_FECHCADUCADNI) ? formatDateForInput(data.PERS_FECHCADUCADNI) : '');
    setValue('#estado_civil', data.ESCI_CODIGO ? data.ESCI_CODIGO.trim() : '');
    setValue('#sexo', data.PERS_SEXO ? data.PERS_SEXO.trim() : data.SEXO ? data.SEXO.trim() : '');
    setValue('#fecha_nacimiento', formatDateForInput(data.FECH_NACI));
    setValue('#sabe_nadar', data.PERS_SNADAR ? data.PERS_SNADAR.trim() : '');
    setValue('#ciudad_nacimiento', data.dj2026_ciudad_naci ? data.dj2026_ciudad_naci.trim() : '');

    // setValue('#departamento_nac',data.DEPA_CODIGO_NACI ? data.DEPA_CODIGO_NACI.trim() : '');
    // setValue('#provincia_nac',data.PROVI_CODIGO_NACI ? data.PROVI_CODIGO_NACI.trim() : '');
    // setValue('#distrito_nac',data.DIST_NACI ? data.DIST_NACI.trim() : '');

    setValue('#dj2026_laboral_1', data.dj2026_laboral_1 ? data.dj2026_laboral_1.trim() : '');
    setValue('#dj2026_laboral_2', data.dj2026_laboral_2 ? data.dj2026_laboral_2.trim() : '');

    setValue('#celular', data.PERS_TELEFONO ? data.PERS_TELEFONO.trim() : '');
    setValue('#correo', data.PERS_EMAIL ? data.PERS_EMAIL.trim() : '');
    setValue('#whatsapp', data.PERS_WHATSAPP ? data.PERS_WHATSAPP.trim() : '');

    setValue('#tipo_sangre', data.tipo_sangr ? data.tipo_sangr.trim() : '');
    setValue('#peso', data.peso_kilo ? data.peso_kilo.trim() : '');
    setValue('#talla', data.tall_metr ? data.tall_metr.trim() : '');

    setValue('#sistema_previsional', data.CODI_SIST_PENS ? data.CODI_SIST_PENS.trim() : '');
    setValue('#essalud', data.ESSALUD ? data.ESSALUD.trim() : '');
    setValue('#pensionista', data.PERS_PENSIONISTA ? data.PERS_PENSIONISTA.trim() : '');

    setValue('#grado_instruccion', data.PERS_GRADO_INSTRUCCION ? data.PERS_GRADO_INSTRUCCION.trim() : '');
    if (data.CARR_CODIGO == '999999') {
        setValue('#institucion', data.IEDU_CODIGO ? data.IEDU_CODIGO.trim() : '299999999');
        if (data.IEDU_CODIGO && window.allCarreras) {
            const selCarrera = document.getElementById('carrera');
            if (selCarrera) {
                selCarrera.innerHTML = '<option value="">—</option>';
                window.allCarreras
                    .filter(c => c.IEDU_CODIGO === data.IEDU_CODIGO.trim())
                    .forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.text;
                        selCarrera.appendChild(opt);
                    });
                selCarrera.value = data.CARR_CODIGO ? data.CARR_CODIGO.trim() : '';
            }
        }

    } else {
        setValue('#institucion', data.IEDU_CODIGO ? data.IEDU_CODIGO.trim() : '999999');
        if (data.IEDU_CODIGO && window.allCarreras) {
            const selCarrera = document.getElementById('carrera');
            if (selCarrera) {
                selCarrera.innerHTML = '<option value="">—</option>';
                window.allCarreras
                    .filter(c => c.IEDU_CODIGO === data.IEDU_CODIGO.trim())
                    .forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.text;
                        selCarrera.appendChild(opt);
                    });
                selCarrera.value = data.CARR_CODIGO ? data.CARR_CODIGO.trim() : '';
            }
        }
    }

    setValue('#carrera', data.CARR_CODIGO ? data.CARR_CODIGO.trim() : '999999');
    setValue('#anio_egreso', data.EGRESO_EDUCATIVO ? data.EGRESO_EDUCATIVO.trim() : '');

    setValue('#embargos', data.PERS_EMBARGO ? data.PERS_EMBARGO.trim() : '');
    setValue('#consumo_sustancias', data.PERS_SMO ? data.PERS_SMO.trim() : '');
    setValue('#cuenta_banco', data.dj2026_banco ? data.dj2026_banco.trim() : '');

    setValue('#direccion_actual', data.DIRECCION ? data.DIRECCION.trim() : '');
    setValue('#direccion_dni', data.PERS_DIREC_DNI ? data.PERS_DIREC_DNI.trim() : '');

    cargarUbicaciones('actual', data.PERS_DEPT_ACT?.trim() ?? '', data.PERS_PROV_ACT?.trim() ?? '', data.PERS_DIST_ACT?.trim() ?? '');
    cargarUbicaciones('dni', data.PERS_DPTO_DIRDNI?.trim() ?? '', data.PERS_PROV_DIRDNI?.trim() ?? '', data.PERS_DIST_DIRDNI?.trim() ?? '');
    cargarUbicaciones('nac', data.DEPA_CODIGO_NACI?.trim() ?? '', data.PROVI_CODIGO_NACI?.trim() ?? '', data.DIST_NACI?.trim() ?? '');


    setValue('#ocupacion_principal', data.dj2026_ocupacion_principal);
    setValue('#experiencia_anios', data.dj2026_experiencia_anios ? String(data.dj2026_experiencia_anios).replace(/[^0-9]/g, '') : '');
    setValue('#familiar_empresa', data.dj2026_familiar_empresa ? data.dj2026_familiar_empresa.trim() : '');
    setValue('#familiar_nombre', data.dj2026_familiar_nombre ? data.dj2026_familiar_nombre.trim() : '');
    setValue('#familiar_parentesco', data.dj2026_familiar_parentesco ? data.dj2026_familiar_parentesco.trim() : '');

    setValue('#curso_sucamec', data.PERS_CONDISCAMEC ? data.PERS_CONDISCAMEC.trim() : '');
    setValue('#sucamec_obs', data.PERS_NRODISCAMEC ? data.PERS_NRODISCAMEC.trim() : '');
    setValue('#smo', data.PERS_SMO ? data.PERS_SMO.trim() : '');
    setValue('#licencia_arma', data.PERS_NROLICENCIA ? data.PERS_NROLICENCIA.trim() : '');
    setValue('#tipo_arma', data.PERS_TIPOARMA ? data.PERS_TIPOARMA.trim() : '');
    setValue('#arma_propia', data.PERS_CONARMAS ? data.PERS_CONARMAS.trim() : '');
    setValue('#brevete', data.PERS_BREVETE ? data.PERS_BREVETE.trim() : '');
    setValue('#clase_brevete', data.CLASE_BREVETE ? data.CLASE_BREVETE.trim() : '');
    actualizarCategorias();  // ← puebla el select tipo_vehiculo según la clase
    setValue('#tipo_vehiculo', data.CATEGORIA_BREVETE ? data.CATEGORIA_BREVETE.trim() : '');
    setValue('#vehiculo_propio', data.PERS_VEHICULO_PROPIO ? data.PERS_VEHICULO_PROPIO.trim() : '');

    setValue('#empresa_anterior', data.PERS_CTRABANT ? data.PERS_CTRABANT.trim() : '');
    setValue('#cargo_anterior', data.PERS_CARGOTRABANT ? data.PERS_CARGOTRABANT.trim() : '');
    setValue('#duracion_anterior', data.PERS_DURACIONANT ? data.PERS_DURACIONANT.trim() : '');

    setValue('#contacto_emergencia', data.PERS_NOMCONTACTO ? data.PERS_NOMCONTACTO.trim() : '');
    setValue('#celular_emergencia', data.PERS_NROEMERGENCIA ? data.PERS_NROEMERGENCIA.trim() : '');
    setValue('#parentesco_emergencia', data.PERS_EMERC_FAMILIAR ? data.PERS_EMERC_FAMILIAR.trim() : '');

    if (data.FOTO_PATH) {
        const img = document.getElementById('previewFoto');
        const placeholderEl = document.getElementById('placeholderFoto');
        if (img) {
            img.src = data.FOTO_PATH + '?v=' + (Math.floor(Math.random() * 900) + 100);
            img.classList.remove('hidden');
            if (placeholderEl) placeholderEl.classList.add('hidden');
            document.getElementById('btnEliminarFoto')?.classList.remove('hidden');
        }
    }
}

// ── Familiares ───────────────────────────────────────────────
function renderFamiliares(familiares) {
    const container = document.getElementById('familyContainer');
    if (!container) return;
    container.innerHTML = '';

    const allFam = [
        ...(familiares.padres || []),
        ...(familiares.madre || []),
        ...(familiares.hijos || []),
        ...(familiares.conyugue || [])
    ];

    if (allFam.length === 0) addFamiliarRow({}, container);
    else allFam.forEach(f => addFamiliarRow(f, container));
}

function addFamiliarRow(data = {}, container = null) {
    if (!container) container = document.getElementById('familyContainer');
    if (!container) return;

    let fechaFormateada = '';
    if (data.FECH_NACI) {
        const f = String(data.FECH_NACI);
        fechaFormateada = (f.length >= 8 && !f.includes('-') && !f.includes('/'))
            ? `${f.substring(0, 4)}-${f.substring(4, 6)}-${f.substring(6, 8)}`
            : formatDateForInput(f);
    }

    const row = document.createElement('div');
    row.className = 'family-row';
    row.style.cssText = 'display:grid;grid-template-columns:1fr 2fr 1fr auto;gap:8px;align-items:end;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:8px 10px;';
    row.innerHTML = `
        <div>
            <label class="dj-label">Parentesco</label>
            <select name="parentesco[]" class="dj-select">
                <option value="">—</option>
                ${['PADRE', 'MADRE', 'CONYUGE', 'HIJO']
            .map(p => `<option value="${p}" ${data.TIPO_RELA === p ? 'selected' : ''}>${p.charAt(0) + p.slice(1).toLowerCase()}</option>`).join('')}
            </select>
        </div>
        <div>
            <label class="dj-label">Apellidos y Nombres</label>
            <input type="text" name="apellidosNombres[]" class="dj-input" value="${data.Nombres || ''}" placeholder="Apellidos y nombres completos">
        </div>
        <div>
            <label class="dj-label">Fecha de Nacimiento</label>
            <input type="date" name="fechaNacimiento[]" class="dj-input" value="${fechaFormateada}">
        </div>
        <div>
            <button type="button" class="remove-family dj-btn-sm dj-btn-danger" style="margin-bottom:1px;">Eliminar</button>
        </div>`;
    // row.innerHTML = `
    // <div>
    //     <label class="dj-label">Parentesco</label>
    //     <select name="parentesco[]" class="dj-select">
    //         <option value="">—</option>
    //         ${['PADRE','MADRE','ESPOSO','ESPOSA','CONYUGE','HIJO','HIJA','HERMANO','HERMANA','ABUELO','ABUELA']
    //             .map(p => `<option value="${p}" ${data.TIPO_RELA===p?'selected':''}>${p.charAt(0)+p.slice(1).toLowerCase()}</option>`).join('')}
    //     </select>
    // </div>
    // <div>
    //     <label class="dj-label">Apellidos y Nombres</label>
    //     <input type="text" name="apellidosNombres[]" class="dj-input" value="${data.Nombres||''}" placeholder="Apellidos y nombres completos">
    // </div>
    // <div>
    //     <label class="dj-label">Fecha de Nacimiento</label>
    //     <input type="date" name="fechaNacimiento[]" class="dj-input" value="${fechaFormateada}">
    // </div>
    // <div>
    //     <button type="button" class="remove-family dj-btn-sm dj-btn-danger" style="margin-bottom:1px;">Eliminar</button>
    // </div>`;

    container.appendChild(row);
    row.querySelector('.remove-family')?.addEventListener('click', () => row.remove());
}

// ── Ubicaciones cascada ──────────────────────────────────────
const ubicacionCache = new Map();
const ubicacionPromise = new Map();

async function getUbicacionCached(params) {
    const key = JSON.stringify(params);

    if (ubicacionCache.has(key)) return ubicacionCache.get(key);
    if (ubicacionPromise.has(key)) return ubicacionPromise.get(key);

    const req = axios.get(`${API_URL}/dj/get-ubicacion`, { params })
        .then(res => {
            ubicacionCache.set(key, res.data);
            return res.data;
        })
        .finally(() => {
            ubicacionPromise.delete(key);
        });

    ubicacionPromise.set(key, req);
    return req;
}


async function cargarUbicaciones(tipo, dept, prov, dist) {
    const prefix = tipo === 'actual' ? '_actual' : tipo === 'dni' ? '_dni' : '_nac';
    if (!dept) return;

    const depts = await getUbicacionCached({ type: 'dept' });
    populateSelect(`#departamento${prefix}`, depts);
    setValue(`#departamento${prefix}`, dept);

    if (!prov) return;

    const provs = await getUbicacionCached({ type: 'prov', dept });
    populateSelect(`#provincia${prefix}`, provs);
    setValue(`#provincia${prefix}`, prov);

    if (!dist) return;

    const dists = await getUbicacionCached({ type: 'dist', prov });
    populateSelect(`#distrito${prefix}`, dists);
    setValue(`#distrito${prefix}`, dist);
}

// ── Helpers de DOM ───────────────────────────────────────────
function populateSelect(selector, data) {
    const sel = document.querySelector(selector);
    if (!sel) return;
    sel.innerHTML = '<option value="">Seleccionar...</option>';
    data.forEach(item => { const opt = document.createElement('option'); opt.value = item.id; opt.textContent = item.text; sel.appendChild(opt); });
}

function setValue(selector, value) {
    const id = selector.startsWith('#') ? selector : `#${selector}`;
    const el = document.querySelector(id);
    if (el) el.value = value || '';
}

function formatDateForInput(dateValue) {
    if (!dateValue) return '';

    // Si tiene T, extraer solo la parte YYYY-MM-DD directamente sin parsear
    if (typeof dateValue === 'string' && dateValue.includes('T')) {
        return dateValue.split('T')[0];
    }

    if (typeof dateValue === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateValue)) return dateValue;
    if (typeof dateValue === 'string' && dateValue.includes(' ')) return dateValue.split(' ')[0];
    if (typeof dateValue === 'string' && /^\d{2}[-/]\d{2}[-/]\d{4}$/.test(dateValue)) {
        const [dia, mes, anio] = dateValue.split(/[-/]/);
        return `${anio}-${mes}-${dia}`;
    }
    if (dateValue instanceof Date) {
        return `${dateValue.getFullYear()}-${String(dateValue.getMonth() + 1).padStart(2, '0')}-${String(dateValue.getDate()).padStart(2, '0')}`;
    }
    return '';
}

function animarContador(el, desde, hasta, duracion = 400) {
    if (!el) return;
    const inicio = performance.now();
    const diff = hasta - desde;
    function tick(ahora) {
        const t = Math.min((ahora - inicio) / duracion, 1);
        el.textContent = Math.round(desde + diff * (1 - Math.pow(1 - t, 3))).toLocaleString();
        if (t < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
}

// ============================================================
// SPLIT VIEW — Backup, diferencias, interactividad
// ============================================================
const CAMPO_MAP = {
    'apellido_paterno': 'APEL_1', 'apellido_materno': 'APEL_2',
    'nombre1': 'NOMB_1', 'nombre2': 'NOMB_2',
    'dni': 'NRO_DOCU_IDEN', 'caduca': 'PERS_FECHCADUCADNI',
    'estado_civil': 'ESCI_DESCRIPCION', 'sexo': 'PERS_SEXO',
    'fecha_nacimiento': 'FECH_NACI', 'sabe_nadar': 'PERS_SNADAR',
    'celular': 'PERS_TELEFONO', 'correo': 'PERS_EMAIL',
    'tipo_sangre': 'tipo_sangr', 'peso': 'peso_kilo',
    'talla': 'tall_metr', 'sistema_previsional': 'DESC_SIST_PENS',
    'essalud': 'ESSALUD', 'pensionista': 'PERS_PENSIONISTA',
    'grado_instruccion': 'NIED_ABREVIADO', 'anio_egreso': 'EGRESO_EDUCATIVO',
    'embargos': 'PERS_EMBARGO', 'consumo_sustancias': 'PERS_SMO',
    'direccion_actual': 'DIRECCION', 'direccion_dni': 'PERS_DIREC_DNI',
    'contacto_emergencia': 'PERS_NOMCONTACTO', 'celular_emergencia': 'PERS_NROEMERGENCIA',
    'parentesco_emergencia': 'PERS_EMERC_FAMILIAR', 'ocupacion_principal': 'PERS_PROFESION',
    'curso_sucamec': 'PERS_CONDISCAMEC', 'licencia_arma': 'PERS_NROLICENCIA',
    'tipo_arma': 'PERS_TIPOARMA', 'arma_propia': 'PERS_CONARMAS',
    'brevete': 'PERS_BREVETE', 'clase_brevete': 'CLASE_BREVETE',
    'empresa_anterior': 'PERS_CTRABANT', 'cargo_anterior': 'PERS_CARGOTRABANT',
    'smo': 'PERS_CONSMO',
};

const FECHA_FIELDS_BK = ['FECH_NACI', 'PERS_FECHCADUCADNI', 'FECH_INGRE', 'FECH_CESE'];

let _backupData = null;

async function cargarDatosBackup(codiPers) {
    const wrapper = document.getElementById('djSplitWrapper');
    const panelBk = document.getElementById('panelBackup');
    const badgeSplit = document.getElementById('splitModeBadge');
    const contDiffs = document.getElementById('contadorDiffs');
    if (!wrapper) return;

    try {
        const response = await axios.get(`${API_URL}/dj/get-backup-data`, { params: { codi_pers: codiPers } });

        if (!response.data.success) {
            wrapper.classList.add('no-backup');
            if (panelBk) panelBk.style.display = 'none';
            if (badgeSplit) badgeSplit.style.display = 'none';
            _backupData = null;
            return;
        }

        _backupData = response.data.data;
        wrapper.classList.remove('no-backup');
        if (panelBk) panelBk.style.display = 'block';
        if (badgeSplit) badgeSplit.style.display = 'flex';

        // Badge fecha mod
        const badge = document.getElementById('bkFechaModBadge');
        if (badge && _backupData.USUA_FECHA_MOD) {
            const f = new Date(_backupData.USUA_FECHA_MOD);
            if (!isNaN(f)) badge.textContent = `Últ. mod: ${String(f.getDate()).padStart(2, '0')}/${String(f.getMonth() + 1).padStart(2, '0')}/${f.getFullYear()}`;
        }

        // Reset visibilidad secciones tipo (fuera del forEach)
        // document.querySelectorAll('.bk-tipo-section').forEach(el => { el.style.display = ''; });

        const tipoActual = document.getElementById('tipo_personal')?.value?.trim() ?? '';
        aplicarVisibilidadBackup(tipoActual); // ← aplica DESPUÉS de que el backup ya está renderizado

        activarInteractividad();

        // Llenar campos backup
        wrapper.querySelectorAll('.bk-val[data-field]').forEach(el => {
            const field = el.getAttribute('data-field');
            let val = _backupData[field] ?? '';
            // if (FECHA_FIELDS_BK.includes(field) && val) {
            //     const d = new Date(val);
            //     if (!isNaN(d)) val = `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`;
            // }
            if (FECHA_FIELDS_BK.includes(field) && val) {
                const valStr = String(val);
                // Extraer YYYY-MM-DD sin crear Date (evita timezone)
                const match = valStr.match(/^(\d{4})-(\d{2})-(\d{2})/);
                if (match) {
                    val = `${match[3]}/${match[2]}/${match[1]}`; // DD/MM/YYYY
                }
            }
            el.textContent = val ? String(val).toUpperCase().trim() : '—';
        });

        if (window.allCarreras) {
            // Institución
            const bkInstEl = wrapper.querySelector('.bk-val[data-field="IEDU_CODIGO"]');
            if (bkInstEl && _backupData.IEDU_CODIGO) {
                const carreraMatch = window.allCarreras.find(c => c.IEDU_CODIGO === _backupData.IEDU_CODIGO?.trim());
                // La descripción de institución no está en allCarreras directamente,
                // pero sí podemos buscar en el select
                const selInst = document.getElementById('institucion');
                const optInst = selInst?.querySelector(`option[value="${_backupData.IEDU_CODIGO?.trim()}"]`);
                if (optInst) bkInstEl.textContent = optInst.textContent;
            }

            // Carrera
            const bkCarrEl = wrapper.querySelector('.bk-val[data-field="CARR_CODIGO"]');
            if (bkCarrEl && _backupData.CARR_CODIGO) {
                const carrera = window.allCarreras.find(c => c.id === _backupData.CARR_CODIGO?.trim());
                if (carrera) bkCarrEl.textContent = carrera.text;
            }
        }

        // Familiares backup
        // Quitar botón de previsualizar
        const btnPrev = document.getElementById("btnPrevisualizar");
        if (btnPrev) btnPrev.style.display = 'none';

        // Foto en DJ Antiguo (Panel Backup)
        let imgBk = document.getElementById('previewFotoBackup');
        let placeholderBk = document.getElementById('placeholderFotoBackup');

        if (imgBk) {
            // Como el backend de backup no trae FOTO_PATH, armamos la ruta directa con el codiPers
            const fotoUrl = `http://190.116.178.163/Biblioteca_Grafica/Fotos/${codiPers}.jpg`;

            // Asignamos la imagen
            imgBk.src = fotoUrl + '?v=' + (Math.floor(Math.random() * 900) + 100);

            // Si carga bien, mostramos la foto y ocultamos el placeholder
            imgBk.onload = function () {
                imgBk.classList.remove('hidden');
                if (placeholderBk) placeholderBk.classList.add('hidden');
            };

            // Si no hay foto en el servidor, dejamos el placeholder visible
            imgBk.onerror = function () {
                imgBk.classList.add('hidden');
                if (placeholderBk) placeholderBk.classList.remove('hidden');
            };
        } else {
            // Fallback por si acaso no pusiste los IDs en el blade
            const panelInfo = document.querySelector('#panelBackup');
            if (panelInfo) {
                const fotoUrl = `http://190.116.178.163/Biblioteca_Grafica/Fotos/${codiPers}.jpg`;
                panelInfo.insertAdjacentHTML('afterbegin', `<div class="mb-4 text-center mt-4"><img src="${fotoUrl}" class="rounded-lg mx-auto border border-gray-300 shadow-sm" style="max-height: 140px;" onerror="this.style.display='none'" /></div>`);
            }
        }

        // Familiares backup
        const tbody = document.getElementById('bodyBackupFamiliares');
        if (tbody) {
            const familiares = response.data.familiares ?? [];
            tbody.innerHTML = familiares.length === 0
                ? `<tr><td colspan="3" style="padding:6px 8px;color:#9ca3af;font-style:italic;font-size:11px;">Sin familiares registrados</td></tr>`
                : familiares.map(f => `<tr><td>${f.TIPO_RELA ?? '—'}</td><td>${f.Nombres ? f.Nombres.toUpperCase().trim() : '—'}</td><td>${f.FECH_NACI ?? '—'}</td></tr>`).join('');
        }

        // Diferencias (con delay para que el form esté lleno)
        setTimeout(() => {
            const diffs = marcarDiferencias();
            if (contDiffs) {
                if (diffs > 0) { contDiffs.style.display = 'inline-block'; contDiffs.textContent = `${diffs} campo${diffs > 1 ? 's' : ''} diferente${diffs > 1 ? 's' : ''}`; }
                else contDiffs.style.display = 'none';
            }
        }, 300);

        activarInteractividad();

    } catch (err) {
        console.warn('Sin backup DJ:', err);
        wrapper.classList.add('no-backup');
        if (panelBk) panelBk.style.display = 'none';
        if (badgeSplit) badgeSplit.style.display = 'none';
        _backupData = null;
    }
}

function marcarDiferencias() {
    if (!_backupData) return 0;
    let totalDiffs = 0;

    document.querySelectorAll('[data-compare]').forEach(el => el.classList.remove('has-diff'));
    document.querySelectorAll('.bk-field').forEach(el => el.classList.remove('is-diff'));

    Object.entries(CAMPO_MAP).forEach(([formId, bkField]) => {
        const inputEl = document.getElementById(formId);
        if (!inputEl) return;

        let valForm = String(inputEl.value ?? '').trim();
        let valBk = String(_backupData[bkField] ?? '').trim();

        if (FECHA_FIELDS_BK.includes(bkField) && valBk) {
            valBk = valBk.replace('T', ' ').split(' ')[0];
        }

        let normForm = valForm.toUpperCase();
        let normBk = valBk.toUpperCase();

        console.log('a ', normForm);
        console.log('b ', normBk);
        console.log('c ', formId);

        if (formId === 'estado_civil') {
            const estadosCiviles = {
                '2007000001': 'SOLTERO',
                '2007000002': 'CASADO',
                '2007000003': 'DIVORCIADO',
                '2007000004': 'VIUDO',
                '2007000008': 'CONVIVIENTE'
            };

            normForm = (estadosCiviles[valForm] || '').toUpperCase();
        }

        if (formId === 'sistema_previsional') {
            const sistemasPensiones = {
                '01': 'SISTEMA NACIONAL DE PENSIONES',
                '02': 'AFP INTEGRA',
                '03': 'PROFUTURO AFP',
                '04': 'AFP HORIZONTE',
                '05': 'AFP UNION VIDA',
                '06': 'CAJA DE BENEFICIO DEL PESCADOR',
                '07': 'NO APORTACION',
                '10': 'AFP PRIMA',
                '11': 'AFP EL ROBLE',
                '27': 'AFP HABITAT'
            };

            normForm = (sistemasPensiones[valForm] || '').toUpperCase();
        }

        if (formId === 'grado_instruccion') {
            const gradosInstruccion = {
                '01': 'SIN EDUCACIÓN FORMAL',
                '02': 'ESPECIAL INCOMPLETA',
                '03': 'ESPECIAL COMPLETA',
                '04': 'PRIMARIA INCOMPLETA',
                '05': 'PRIMARIA COMPLETA',
                '06': 'SECUNDARIA INCOMPLETA',
                '07': 'SECUNDARIA COMPLETA',
                '08': 'TÉCNICA INCOMPLETA',
                '09': 'TÉCNICA COMPLETA',
                '10': 'SUPERIOR INCOMPLETA (INSTIT. SUPER)',
                '11': 'SUPERIOR COMPLETA (INSTIT SUPER)',
                '12': 'UNIVERSITARIA INCOMPLETA',
                '13': 'UNIVERSITARIA COMPLETA',
                '14': 'GRADO DE BACHILLER',
                '15': 'TITULADO',
                '16': 'ESTUD. MAESTRÍA INCOMPLETA',
                '17': 'ESTUD. MAESTRÍA COMPLETA',
                '18': 'GRADO DE MAESTRÍA',
                '19': 'ESTUD. DOCTORADO INCOMPLETO',
                '20': 'ESTUD. DOCTORADO COMPLETO',
                '21': 'GRADO DE DOCTOR'
            };

            normForm = (gradosInstruccion[valForm] || '').toUpperCase();
        }


        if (normForm && normBk && normForm !== normBk) {
            totalDiffs++;
            inputEl.classList.add('has-diff');
            document.querySelector(`.bk-field[data-bk="${formId}"]`)?.classList.add('is-diff');
        }
    });

    return totalDiffs;
}

function activarInteractividad() {
    const panelForm = document.getElementById('panelForm');
    if (!panelForm) return;

    if (panelForm._splitFocusIn) panelForm.removeEventListener('focusin', panelForm._splitFocusIn);
    if (panelForm._splitFocusOut) panelForm.removeEventListener('focusout', panelForm._splitFocusOut);

    panelForm._splitFocusIn = e => {
        const compareId = e.target.getAttribute('data-compare') || e.target.id;
        if (!compareId) return;
        document.querySelectorAll('.bk-field.is-active').forEach(el => el.classList.remove('is-active'));
        const bkEl = document.querySelector(`.bk-field[data-bk="${compareId}"]`);
        if (bkEl) {
            bkEl.classList.add('is-active');
            const panelBk = document.getElementById('panelBackup');
            if (panelBk) panelBk.scrollTop += (bkEl.getBoundingClientRect().top - panelBk.getBoundingClientRect().top) - 80;
        }
    };
    panelForm._splitFocusOut = () => {
        setTimeout(() => {
            if (!panelForm.contains(document.activeElement))
                document.querySelectorAll('.bk-field.is-active').forEach(el => el.classList.remove('is-active'));
        }, 150);
    };

    panelForm.addEventListener('focusin', panelForm._splitFocusIn);
    panelForm.addEventListener('focusout', panelForm._splitFocusOut);
}

function aplicarVisibilidadBackup(tipoCod) {
    const esOperativo = tipoCod == '03' || tipoCod == '01';
    const esAdministrativo = tipoCod == '05' || tipoCod == '02';

    document.querySelectorAll('.bk-tipo-section[data-bk-tipo="operativo"]')
        .forEach(el => { el.style.display = esAdministrativo ? 'none' : ''; });
    document.querySelectorAll('.bk-tipo-section[data-bk-tipo="administrativo"]')
        .forEach(el => { el.style.display = esOperativo ? 'none' : ''; });
}

// ── La función original queda sin los querySelectorAll del backup ──
function aplicarVisibilidadPorTipo(tipoCod) {
    const esOperativo = tipoCod == '03' || tipoCod == '01';
    const esAdministrativo = tipoCod == '05' || tipoCod == '02';
    const esEspecial = tipoCod == '06';

    // Solo afecta el panel del formulario (derecho)
    document.querySelectorAll('[data-tipo="operativo"]')
        .forEach(el => { el.style.display = esAdministrativo ? 'none' : ''; });
    document.querySelectorAll('[data-tipo="administrativo"]')
        .forEach(el => { el.style.display = esOperativo ? 'none' : ''; });

    let badge = document.getElementById('tipoBadgeModal');
    if (!badge) {
        badge = document.createElement('span');
        badge.id = 'tipoBadgeModal';
        badge.style.cssText = 'font-size:10px;font-weight:700;padding:2px 10px;border-radius:20px;margin-left:8px;letter-spacing:.04em;text-transform:uppercase;display:inline-block;';
        const headerTitle = document.querySelector('#modalDjGestion [style*="font-size:13px"]');
        if (headerTitle) headerTitle.parentNode.insertBefore(badge, headerTitle.nextSibling);
    }

    if (esOperativo) { badge.textContent = 'Operativo — RH 01'; badge.style.background = '#dbeafe'; badge.style.color = '#1e40af'; }
    else if (esAdministrativo) { badge.textContent = 'Administrativo — RH 02'; badge.style.background = '#d1fae5'; badge.style.color = '#065f46'; }
    else { badge.textContent = ''; }
}

function limpiarSplitView() {
    _backupData = null;
    const wrapper = document.getElementById('djSplitWrapper');
    const panelBk = document.getElementById('panelBackup');
    const badgeSplit = document.getElementById('splitModeBadge');
    const contDiffs = document.getElementById('contadorDiffs');

    if (wrapper) wrapper.classList.add('no-backup');
    if (panelBk) panelBk.style.display = 'none';
    if (badgeSplit) badgeSplit.style.display = 'none';
    if (contDiffs) contDiffs.style.display = 'none';

    document.querySelectorAll('.bk-val').forEach(el => el.textContent = '—');
    document.querySelectorAll('.bk-field').forEach(el => el.classList.remove('is-diff', 'is-active'));
    document.querySelectorAll('[data-compare]').forEach(el => el.classList.remove('has-diff'));
    document.querySelectorAll('.bk-tipo-section').forEach(el => { el.style.display = ''; });

    const tbody = document.getElementById('bodyBackupFamiliares');
    if (tbody) tbody.innerHTML = `<tr><td colspan="3" style="padding:6px;color:#9ca3af;font-style:italic;">Cargando...</td></tr>`;

    const badge = document.getElementById('bkFechaModBadge');
    if (badge) badge.textContent = '';
}

// ============================================================
// BOTONES REPORTES
// ============================================================
document.getElementById('btnReporteFaltantes')?.addEventListener('click', async function () {
    const sucursal = document.getElementById('filtroSucursal')?.value ?? '00';
    const tipoPer = document.getElementById('filtroTipoPer')?.value ?? '00';

    // Nuevo mapeo para los tipos de personal V2
    let codTipoPer = '00';
    if (tipoPer === 'OPERATIVO 4°') codTipoPer = '01';
    else if (tipoPer === 'OPERATIVO 5°') codTipoPer = '03';
    else if (tipoPer === 'ADMINISTRATIVO 4°') codTipoPer = '02';
    else if (tipoPer === 'ADMINISTRATIVO 5°') codTipoPer = '05';
    else if (tipoPer === 'ESPECIAL') codTipoPer = '06';

    const codSucursal = sucursal || '00';

    Swal.fire({ title: 'Generando reporte...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    try {
        const response = await axios.get(`${VITE_URL_APP}/api/reporte-personal-sin-migracion-v2`, { params: { codSucursal, codTipoPer, tipo: 1 } });

        if (!response.data.success || !response.data.data.length) {
            Swal.fire({ icon: 'info', title: 'Sin datos', text: 'No hay registros.' });
            return;
        }

        const todosLosDatos = response.data.data;
        const soloFaltantes = todosLosDatos.filter(d => d.SIP_CAMBIO === 'Falta');
        Swal.close();

        if (!soloFaltantes.length) {
            Swal.fire({ icon: 'info', title: 'Sin faltantes', text: 'Todo el personal está actualizado.' });
            return;
        }

        generarReporteFaltantesPDF(soloFaltantes, todosLosDatos);
    } catch {
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo obtener los datos.' });
    }
});

document.getElementById('btnReporteActualizacion')?.addEventListener('click', async function () {
    const sucursal = document.getElementById('filtroSucursal')?.value ?? '00';
    const tipoPer = document.getElementById('filtroTipoPer')?.value ?? '00';
    const codTipoPer = tipoPer === 'OPERATIVO' ? '03' : tipoPer === 'ADMINISTRATIVO' ? '05' : '00';
    const codSucursal = sucursal || '00';

    Swal.fire({ title: 'Generando reporte...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    try {
        const response = await axios.get(`${VITE_URL_APP}/api/reporte-personal-sin-migracion-v2`, { params: { codSucursal, codTipoPer, tipo: null } });
        if (!response.data.success || !response.data.data.length) { Swal.fire({ icon: 'info', title: 'Sin datos', text: 'No hay registros.' }); return; }
        const todosLosDatos = response.data.data;
        const soloActualizados = todosLosDatos.filter(d => d.SIP_CAMBIO === 'Ok');
        Swal.close();
        if (!soloActualizados.length) { Swal.fire({ icon: 'info', title: 'Sin actualizados', text: 'No hay personal actualizado aún.' }); return; }
        generarReporteFaltantesPDF(soloActualizados, todosLosDatos);
    } catch { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo obtener los datos.' }); }
});

// Toggle colapsable DJ Anterior
document.getElementById('headerDJAnterior')?.addEventListener('click', function () {
    const cuerpo = document.getElementById('cuerpoDJAnterior');
    const icono = document.getElementById('iconoDJAnterior');
    if (!cuerpo) return;
    const abierto = cuerpo.style.display !== 'none';
    cuerpo.style.display = abierto ? 'none' : 'block';
    if (icono) icono.textContent = abierto ? '▼' : '▲';
});

// ── Filtrar carreras según institución seleccionada ──
document.getElementById('institucion')?.addEventListener('change', function () {
    const ieduCodigo = this.value;
    const selCarrera = document.getElementById('carrera');
    if (!selCarrera) return;

    selCarrera.innerHTML = '<option value="">—</option>';

    if (!ieduCodigo || !window.allCarreras) return;

    const carrerasFiltradas = window.allCarreras.filter(c => c.IEDU_CODIGO === ieduCodigo);

    carrerasFiltradas.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.text;
        selCarrera.appendChild(opt);
    });
});


// ============================================================
// DJ GENERADOS — localStorage helpers
// ============================================================
const DJ_STORAGE_KEY = 'dj_generados';

function getDJGenerados() {
    try {
        return JSON.parse(localStorage.getItem(DJ_STORAGE_KEY) || '{}');
    } catch { return {}; }
}

function marcarDJGenerado(codPersonal, fechaCambio) {
    const data = getDJGenerados();
    data[codPersonal] = {
        fechaMarcado: new Date().toISOString(),
        fechaCambio: fechaCambio || null,
    };
    localStorage.setItem(DJ_STORAGE_KEY, JSON.stringify(data));
}

function desmarcarDJGenerado(codPersonal) {
    const data = getDJGenerados();
    delete data[codPersonal];
    localStorage.setItem(DJ_STORAGE_KEY, JSON.stringify(data));
}

function estaGenerado(codPersonal, fechaCambioActual) {
    const data = getDJGenerados();
    const reg = data[codPersonal];
    if (!reg) return false;

    // Si el registro tuvo cambios DESPUÉS de que se marcó → ya no vale
    if (fechaCambioActual && reg.fechaCambio) {
        const cambio = new Date(fechaCambioActual);
        const marcado = new Date(reg.fechaMarcado);
        if (cambio > marcado) return false;
    }
    return true;
}

function limpiarDJGenerados() {
    localStorage.removeItem(DJ_STORAGE_KEY);
    /* tblPersonasMigrado.redraw(true);
     Swal.fire({ icon: 'success', title: 'Listo', text: 'Todas las marcas fueron eliminadas.', timer: 1800, showConfirmButton: false });*/
}


document.getElementById('btnResetearDJs')?.addEventListener('click', async function () {

    const generados = getDJGenerados();
    const totalMarcados = Object.keys(generados).length;

    if (totalMarcados === 0) {
        Swal.fire({ icon: 'info', title: 'Sin marcas', text: 'No hay registros marcados como generados.' });
        return;
    }

    const { isConfirmed } = await Swal.fire({
        icon: 'warning',
        title: 'Resetear todas las marcas',
        html: `Se eliminarán las marcas ✅ de <b>${totalMarcados}</b> registro(s).<br>
               <span style="font-size:12px;color:#6b7280;">Todos volverán a aparecer como pendientes.</span>`,
        showCancelButton: true,
        confirmButtonText: 'Sí, resetear todo',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });

    if (!isConfirmed) return;

    limpiarDJGenerados();
    tblPersonasMigrado.redraw(true);

    Swal.fire({ icon: 'success', title: 'Listo', text: 'Todas las marcas fueron eliminadas.', timer: 1800, showConfirmButton: false });
});

// ============================================================
// EXTRAER FIRMA Y HUELLA — Extracción desde PDF DJ
// ============================================================
(function initExtractorFirmaHuella() {

    let _pdfDoc = null;
    let _lastPageCvs = null;

    const el = id => document.getElementById(id);

    // ── Conversión mm → px ────────────────────────────────────
    // A4 = 210mm × 297mm. El canvas renderizado tiene canvas.width = 210 * mmX
    const mmX = cvs => cvs.width / 210;   // px por mm horizontal
    const mmY = cvs => cvs.height / 297;   // px por mm vertical

    // ── Detectar borde horizontal en el canvas por píxeles ───────
    // Escanea filas horizontales buscando líneas oscuras (bordes del PDF).
    // Devuelve la Y en px del ÚLTIMO borde encontrado en [fromMm, toMm].
    // Funciona con cualquier escala de renderizado.
    function findHorizontalBorder(cvs, fromMm, toMm) {
        const scY = cvs.height / 297;
        const scX = cvs.width / 210;
        const yS = Math.round(fromMm * scY);
        const yE = Math.round(toMm * scY);
        // Muestrear solo la franja interior del box (interior a los márgenes)
        const xL = Math.round(12 * scX);
        const xR = Math.round(198 * scX);
        const sw = xR - xL;

        const { data } = cvs.getContext('2d').getImageData(xL, yS, sw, yE - yS);
        let last = -1, inBdr = false;

        for (let r = 0; r < yE - yS; r++) {
            let dark = 0;
            for (let c = 0; c < sw; c++) {
                const p = (r * sw + c) * 4;
                if ((data[p] + data[p + 1] + data[p + 2]) / 3 < 80) dark++;
            }
            if (dark / sw > 0.14 && !inBdr) { last = yS + r; inBdr = true; }
            else if (dark / sw <= 0.07) { inBdr = false; }
        }
        return last >= 0 ? last : null;
    }

    // ── Calcular región de recorte exacta ─────────────────────
    // Usa detección de píxeles para encontrar el borde superior de la
    // celda firma/huella — funciona igual para PDFs de 1 o 2 páginas.
    function getCropRegion(cvs) {
        const scX = mmX(cvs);
        const scY = mmY(cvs);
        const n = _pdfDoc?.numPages ?? 1;

        let firmaTopPx;
        if (n >= 2) {
            // Pág 2: titulo(5.5mm) + conformidad(17-28mm) → firma entre 22mm y 52mm
            firmaTopPx = findHorizontalBorder(cvs, 22, 52) ?? Math.round(32 * scY);
        } else {
            // Pág 1: firma al final — zona entre 205mm y 258mm
            firmaTopPx = findHorizontalBorder(cvs, 205, 258) ?? Math.round(236 * scY);
        }

        // Y de recorte: saltar el grosor del borde de la celda
        // La celda mide 45mm; excluir últimos 9mm de etiquetas → 36mm de contenido
        const inYTop = 2.0 * scY;   // clearance del borde (~0.3mm dibujado en PDF)
        const inYBot = 0.5 * scY;
        const yTop = firmaTopPx + inYTop;
        const yBottom = firmaTopPx + (45 - 9) * scY - inYBot;

        // X fijos (marginLeft=10, firmaW=114mm, huellaW=76mm), inset 3mm c/lado
        const firmaX = Math.round((10 + 3) * scX);   // → 108mm de ancho neto
        const firmaW = Math.round((114 - 6) * scX);
        const huellaX = Math.round((124 + 3) * scX);   // → 70mm de ancho neto
        const huellaW = Math.round((76 - 6) * scX);

        return {
            yTop: Math.max(0, Math.round(yTop)),
            yBottom: Math.min(cvs.height, Math.round(yBottom)),
            firmaX,
            firmaW,
            huellaX,
            huellaW,
        };
    }

    // ── Reiniciar modal ───────────────────────────────────────
    function resetModal() {
        el('fhStep1')?.classList.remove('hidden');
        el('fhStep2')?.classList.add('hidden');
        el('fhStep3')?.classList.add('hidden');
        el('fhFileInfo')?.classList.add('hidden');
        const inp = el('fhInputPdf');
        if (inp) inp.value = '';
        const cont = el('fhPagesContainer');
        if (cont) cont.innerHTML = '';
        _pdfDoc = null;
        _lastPageCvs = null;
    }

    // ── Abrir / cerrar modal ──────────────────────────────────
    function abrirModal() {
        resetModal();
        const modal = document.getElementById('modalExtFirmaHuella');
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModal() {
        const modal = document.getElementById('modalExtFirmaHuella');
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        resetModal();
    }

    // ── Cargar y validar PDF ──────────────────────────────────
    async function cargarPDF(file) {
        if (!file || file.type !== 'application/pdf') {
            Swal.fire({ icon: 'warning', title: 'Archivo inválido', text: 'Selecciona un archivo PDF.' });
            return;
        }
        if (!window.pdfjsLib) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'PDF.js no está disponible. Recarga la página.' });
            return;
        }
        try {
            const buf = await file.arrayBuffer();
            const pdfDoc = await pdfjsLib.getDocument({ data: buf }).promise;

            if (pdfDoc.numPages > 2) {
                Swal.fire({
                    icon: 'warning', title: 'Más de 2 páginas',
                    text: `El PDF tiene ${pdfDoc.numPages} páginas. Solo se procesará la última (donde está la firma).`,
                });
            }

            _pdfDoc = pdfDoc;
            const pages = Math.min(pdfDoc.numPages, 2);
            el('fhFileName').textContent = file.name;
            el('fhPageCount').textContent = `${pages} página${pages > 1 ? 's' : ''}`;
            el('fhFileInfo')?.classList.remove('hidden');
        } catch (err) {
            console.error('Error cargando PDF:', err);
            Swal.fire({ icon: 'error', title: 'Error al leer el PDF', text: err.message });
        }
    }

    // ── Renderizar páginas del PDF ────────────────────────────
    async function previsualizarPDF() {
        if (!_pdfDoc) return;

        const container = el('fhPagesContainer');
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                <i class='bx bx-loader-alt bx-spin text-3xl'></i>
                <p class="text-sm mt-2">Renderizando documento...</p>
            </div>`;

        el('fhStep2')?.classList.remove('hidden');
        el('fhStep3')?.classList.add('hidden');

        await new Promise(r => setTimeout(r, 60));
        container.innerHTML = '';

        const numPages = Math.min(_pdfDoc.numPages, 2);
        const scale = 1.4;

        for (let i = 1; i <= numPages; i++) {
            const page = await _pdfDoc.getPage(i);
            const viewport = page.getViewport({ scale });

            const wrapper = document.createElement('div');
            wrapper.style.cssText = 'display:flex;flex-direction:column;align-items:center;';

            const label = document.createElement('div');
            label.style.cssText = 'font-size:11px;color:#9ca3af;margin-bottom:4px;';
            label.textContent = `Página ${i} de ${numPages}`;
            wrapper.appendChild(label);

            const canvasBox = document.createElement('div');
            canvasBox.style.cssText = 'position:relative;display:inline-block;max-width:100%;';

            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            canvas.style.cssText = 'display:block;max-width:100%;border:1px solid #d1d5db;border-radius:4px;box-shadow:0 1px 4px rgba(0,0,0,.08);';

            await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
            canvasBox.appendChild(canvas);

            // Overlay solo en la última página
            if (i === numPages) {
                _lastPageCvs = canvas;
                const r = getCropRegion(canvas);
                const cW = canvas.width;
                const cH = canvas.height;

                // Zona firma (amarillo)
                const mkZone = (x, w, label) => {
                    const z = document.createElement('div');
                    z.style.cssText = `
                        position:absolute;
                        left:${x / cW * 100}%;
                        width:${w / cW * 100}%;
                        top:${r.yTop / cH * 100}%;
                        height:${(r.yBottom - r.yTop) / cH * 100}%;
                        border:2px dashed #f59e0b;
                        border-radius:2px;
                        background:rgba(245,158,11,.07);
                        pointer-events:none;
                        box-sizing:border-box;
                    `;
                    const b = document.createElement('div');
                    b.style.cssText = 'position:absolute;bottom:2px;left:50%;transform:translateX(-50%);font-size:9px;color:#d97706;white-space:nowrap;font-weight:700;';
                    b.textContent = label;
                    z.appendChild(b);
                    return z;
                };

                canvasBox.appendChild(mkZone(r.firmaX, r.firmaW, '✏ Firma'));
                canvasBox.appendChild(mkZone(r.huellaX, r.huellaW, '● Huella'));
            }

            wrapper.appendChild(canvasBox);
            container.appendChild(wrapper);
        }
    }

    // ── Extraer firma y huella del canvas ────────────────────
    function extraerImagenes() {
        if (!_lastPageCvs) {
            Swal.fire({ icon: 'warning', title: 'Sin previsualización', text: 'Presiona "Previsualizar PDF" primero.' });
            return;
        }

        const src = _lastPageCvs;
        const r = getCropRegion(src);
        const cH = r.yBottom - r.yTop;

        // Firma
        const cvF = el('fhCanvasFirma');
        cvF.width = r.firmaW;
        cvF.height = cH;
        cvF.getContext('2d').drawImage(src, r.firmaX, r.yTop, r.firmaW, cH, 0, 0, r.firmaW, cH);

        // Huella
        const cvH = el('fhCanvasHuella');
        cvH.width = r.huellaW;
        cvH.height = cH;
        cvH.getContext('2d').drawImage(src, r.huellaX, r.yTop, r.huellaW, cH, 0, 0, r.huellaW, cH);

        el('fhStep2')?.classList.add('hidden');
        el('fhStep3')?.classList.remove('hidden');
    }

    // ── Descargar canvas como PNG ─────────────────────────────
    function descargarCanvas(canvas, nombre) {
        if (!canvas || canvas.width === 0) return;
        const a = document.createElement('a');
        a.href = canvas.toDataURL('image/png');
        a.download = nombre + '.png';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    // ── Event listeners ───────────────────────────────────────
    const inputPdf = el('fhInputPdf');
    const dropZone = el('fhDropZone');

    inputPdf?.addEventListener('change', e => {
        const f = e.target.files?.[0];
        if (f) cargarPDF(f);
    });

    dropZone?.addEventListener('dragover', e => { e.preventDefault(); e.stopPropagation(); dropZone.classList.add('fh-drag-over'); });
    dropZone?.addEventListener('dragleave', () => dropZone.classList.remove('fh-drag-over'));
    dropZone?.addEventListener('drop', e => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('fh-drag-over');
        const f = e.dataTransfer.files?.[0];
        if (f) cargarPDF(f);
    });

    el('fhBtnCambiar')?.addEventListener('click', e => {
        e.stopPropagation();
        el('fhFileInfo')?.classList.add('hidden');
        el('fhStep2')?.classList.add('hidden');
        el('fhStep3')?.classList.add('hidden');
        if (inputPdf) inputPdf.value = '';
        _pdfDoc = null;
        _lastPageCvs = null;
    });

    el('fhBtnPreview')?.addEventListener('click', previsualizarPDF);
    el('fhBtnExtract')?.addEventListener('click', extraerImagenes);
    el('fhBtnReintentar')?.addEventListener('click', () => {
        el('fhStep2')?.classList.remove('hidden');
        el('fhStep3')?.classList.add('hidden');
    });

    el('fhBtnDownloadFirma')?.addEventListener('click', () => descargarCanvas(el('fhCanvasFirma'), 'firma_registrada'));
    el('fhBtnDownloadHuella')?.addEventListener('click', () => descargarCanvas(el('fhCanvasHuella'), 'huella_registrada'));

    el('btnExtFirmaHuella')?.addEventListener('click', abrirModal);
    el('btnCerrarFHModal')?.addEventListener('click', cerrarModal);
    document.getElementById('modalExtFirmaHuella')?.addEventListener('click', function (e) {
        if (e.target === this) cerrarModal();
    });

})();

// ============================================================
// EXPORTACIÓN PDF (ETAPA 2)
// ============================================================
document.getElementById("btnReporteVerificados")?.addEventListener("click", async () => {
    // 1. Rescatamos los filtros actuales de la etapa 2
    const codSucursal = document.getElementById('filtroSucursalE2')?.value || '00';
    const codTipoPer = document.getElementById('filtroTipoPerE2')?.value || '00';
    const textoBusqueda = document.getElementById('buscarPersonalE2')?.value.toLowerCase().trim() || '';

    Swal.fire({ title: 'Generando reporte...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        // 2. Le pegamos al nuevo endpoint que ejecuta el SP SW_REPORTE_LISTAR_PERSONAL_SIN_MIGRACION_2_ETAPA
        const response = await axios.get(`${VITE_URL_APP}/api/reporte-personal-etapa2`, {
            params: { codSucursal, codTipoPer }
        });

        if (!response.data.success || !response.data.data.length) {
            Swal.fire({ icon: 'info', title: 'Sin datos', text: 'No hay registros en el reporte.' });
            return;
        }

        let datos = response.data.data;

        // 3. Filtro local adicional por si el usuario escribió algo en el buscador
        if (textoBusqueda) {
            datos = datos.filter(d => {
                const nombreCompleto = d.NOMBRE || d.PERSONAL || `${d.nombres ?? ''} ${d.apellido1 ?? ''} ${d.apellido2 ?? ''}`.trim();
                const documento = d.dni || d.NRO_DOCU_IDEN || '';
                const str = `${nombreCompleto} ${documento}`.toLowerCase();
                return str.includes(textoBusqueda);
            });
        }

        if (!datos.length) {
            Swal.fire({ icon: 'info', title: 'Sin datos', text: 'No hay registros que coincidan con la búsqueda.' });
            return;
        }

        // 4. Cálculos para las Cards (Total, Verificados, Sin verificar)
        const totalRegistros = datos.length;
        const totalVerificados = datos.filter(d => {
            const estado = d.VERIFICADO_CAMBIO ? d.VERIFICADO_CAMBIO.toUpperCase().trim() : '';
            return estado === 'SI' || estado === 'VERIFICADO';
        }).length;
        const totalSinVerificar = totalRegistros - totalVerificados;
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('landscape');
        const totalWidth = doc.internal.pageSize.getWidth();

        // 5. Texto para el subtítulo (Sucursal)
        const selSucursal = document.getElementById('filtroSucursalE2');
        const txtSucursal = selSucursal?.options[selSucursal.selectedIndex]?.text?.toUpperCase() || 'TODAS LAS SUCURSALES';
        const f = new Date();
        const fechaStr = `${String(f.getDate()).padStart(2, '0')}/${String(f.getMonth() + 1).padStart(2, '0')}/${f.getFullYear()} ${String(f.getHours()).padStart(2, '0')}:${String(f.getMinutes()).padStart(2, '0')}`;

        // 6. Cargar Logo de Sol Security
        let logoBase64 = null;
        try {
            if (window.logoUrl) {
                const res = await fetch(window.logoUrl);
                const blob = await res.blob();
                logoBase64 = await new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result);
                    reader.readAsDataURL(blob);
                });
            }
        } catch (e) { console.warn("No se pudo cargar el logo", e); }

        // 7. Dibujar la tabla PDF
        doc.autoTable({
            startY: 60,
            theme: 'grid',
            headStyles: { fillColor: [243, 244, 246], textColor: [55, 65, 81], fontStyle: 'bold', halign: 'center' },
            bodyStyles: { fontSize: 8 },
            columnStyles: { 0: { halign: 'center' } },
            // Estructura de cabeceras basada en la 2da foto
            head: [["N°", "Verificado", "Nombres", "DNI", "Sucursal", "Tipo", "Fecha Verificado"]],
            body: datos.map((d, index) => {
                // Hacemos un mapeo seguro dependiendo de lo que devuelva el SP
                const nombreCompleto = d.NOMBRE || d.PERSONAL || `${d.nombres ?? ''} ${d.apellido1 ?? ''} ${d.apellido2 ?? ''}`.trim();

                // 🔥 Usamos directamente VERIFICADO_CAMBIO y VERIFICADO_FECHA de tu SP
                const verificado = d.VERIFICADO_CAMBIO ? d.VERIFICADO_CAMBIO.toUpperCase() : 'SIN VERIFICAR';
                const fechaVerif = d.VERIFICADO_FECHA && d.VERIFICADO_FECHA !== 'sin cambios'
                    ? d.VERIFICADO_FECHA.replace('T', ' ').substring(0, 16)
                    : '—';

                return [
                    d.NRO || index + 1, // Prioriza el NRO del SP
                    verificado,
                    nombreCompleto,
                    d.dni ?? d.NRO_DOCU_IDEN ?? '',
                    d.sucursal ?? d.SUCURSAL ?? '',
                    d.tipoPer ?? d.TIPO_PER ?? '',
                    fechaVerif
                ];
            }),
            didDrawPage: function (dataPage) {
                if (dataPage.pageNumber !== 1) return; // Solo dibuja cards en la primera página

                if (logoBase64) doc.addImage(logoBase64, 'PNG', 14, 10, 40, 12);

                // Títulos
                doc.setFontSize(10);
                doc.setTextColor(180, 0, 0);
                doc.setFont("helvetica", "bold");
                doc.text("SISTEMA INTEGRADO SOLMAR – SISOL WEB", totalWidth / 2, 14, { align: "center" });

                doc.setFontSize(12);
                doc.setTextColor(0, 0, 0);
                doc.text("REPORTE DE PERSONAL - ETAPA 2 (VERIFICADOS)", totalWidth / 2, 20, { align: "center" });

                const subtitulo = txtSucursal === 'TODAS LAS SUCURSALES' ? '' : `Sol ${txtSucursal}`;
                if (subtitulo !== '') doc.text(subtitulo, totalWidth / 2, 26, { align: "center" });

                doc.setFontSize(8);
                doc.setFont("helvetica", "normal");
                doc.setTextColor(100, 100, 100);
                doc.text(`Generado: ${fechaStr}`, totalWidth - 14, 14, { align: "right" });

                // Dibujando las 3 Cards superiores
                const cardW = 45; const cardH = 18; const gap = 10;
                const totalCardsW = (cardW * 3) + (gap * 2);
                const startX = (totalWidth - totalCardsW) / 2;
                const cardY = 32;

                const cards = [
                    { title: "Total", value: totalRegistros, color: [75, 85, 99] }, // Gris
                    { title: "Sin verificar", value: totalSinVerificar, color: [202, 138, 4] }, // Amarillo
                    { title: "Verificados", value: totalVerificados, color: [4, 120, 87] } // Verde
                ];

                cards.forEach((card, i) => {
                    const x = startX + (i * (cardW + gap));
                    doc.setFillColor(...card.color);
                    doc.roundedRect(x, cardY, cardW, cardH, 2, 2, 'F');
                    doc.setTextColor(255, 255, 255);
                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(16);
                    doc.text(String(card.value), x + (cardW / 2), cardY + 10, { align: "center" });
                    doc.setFontSize(8);
                    doc.setFont("helvetica", "normal");
                    doc.text(card.title, x + (cardW / 2), cardY + 15, { align: "center" });
                });
            }
        });

        const fStr = `${String(f.getDate()).padStart(2, '0')}_${String(f.getMonth() + 1).padStart(2, '0')}_${f.getFullYear()}`;
        doc.save(`Reporte_Etapa2_${txtSucursal.replace(/ /g, '_')}_${fStr}.pdf`);
        Swal.close();

    } catch (error) {
        console.error(error);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Problema al generar el reporte PDF de la etapa 2.' });
    }
}); // <--- AQUÍ CERRAMOS CORRECTAMENTE EL EVENTO CLICK DEL REPORTE

// ============================================================
// BIOMÉTRICO (Idéntico a chargeFile.js)
// ============================================================
window.addEventListener('solicitarBiometrico', function (e) {
    const { codigo, persona } = e.detail;

    Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    axios.get(`${VITE_URL_APP}/api/get-biometrico/${codigo}`)
        .then(response => {
            Swal.close();
            const data = response.data;
            document.getElementById('modal-bio-title').textContent = persona;

            // Renderizamos DNI, huellas y firmas
            document.getElementById('bio-huella-antigua').innerHTML = renderImagen(data.huella_antigua);
            document.getElementById('bio-huella-nueva').innerHTML = renderImagen(data.huella_nueva);
            document.getElementById('bio-firma-antigua').innerHTML = renderImagen(data.firma_antigua);
            document.getElementById('bio-firma-nueva').innerHTML = renderImagen(data.firma_nueva);
            document.getElementById('bio-doc-dni-antiguo').innerHTML = renderImagen(data.dni_anverso_antigua, true, data.dni_reverso_antigua);
            document.getElementById('bio-doc-firma-nueva').innerHTML = renderImagen(data.firma_nueva);
            document.getElementById('bio-doc-huella-nueva').innerHTML = renderImagen(data.huella_nueva);

            // === AQUÍ INYECTAMOS LA COLUMNA DE LA FOTO DINÁMICAMENTE ===
            const dniDiv = document.getElementById('bio-doc-dni-antiguo');
            if (dniDiv) {
                // Seleccionamos la grilla que contiene el DNI y las Huellas
                const gridContainer = dniDiv.parentElement.parentElement;

                // Le cambiamos el diseño de 2 a 3 columnas (DNI más ancho, Foto y Huellas iguales)
                gridContainer.style.gridTemplateColumns = '2fr 1fr 1fr';

                // Verificamos si ya creamos la caja antes para no duplicarla si hacen click varias veces
                let cajaFoto = document.getElementById('caja-foto-inyectada');
                if (!cajaFoto) {
                    cajaFoto = document.createElement('div');
                    cajaFoto.id = 'caja-foto-inyectada';
                    // Lo insertamos justo en el medio, antes de la columna de las huellas
                    gridContainer.insertBefore(cajaFoto, gridContainer.lastElementChild);
                }

                const fotoUrl = `http://190.116.178.163/Biblioteca_Grafica/Fotos/${codigo}.jpg?v=${new Date().getTime()}`;

                cajaFoto.innerHTML = `
                    <div style="display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                        <i class="fa fa-user" style="color:#6366f1; font-size:12px;"></i>
                        <span style="font-size:12px; font-weight:600; color:#374151;">FOTO</span>
                        <span style="font-size:10px; color:#9ca3af; font-weight:500; margin-left:2px;">ROSTRO</span>
                    </div>
                    <div style="border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.06);width:100%;">
                        <div style="position:relative;width:100%;height:420px;background:#f8fafc;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                            <img id="foto_rostro_${codigo}" src="${fotoUrl}" 
                                 style="max-width:100%;max-height:100%;width:95%;height:auto;object-fit:contain;display:block;cursor:zoom-in;" 
                                 onclick="if(window.abrirLightbox) abrirLightbox('foto_rostro_${codigo}')"
                                 onerror="this.parentElement.innerHTML='<div style=\\'width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:12px;flex-direction:column;gap:6px;\\'><svg width=32 height=32 fill=none stroke=currentColor stroke-width=1.5 viewBox=\\'0 0 24 24\\'><rect x=3 y=3 width=18 height=18 rx=3/><circle cx=8.5 cy=8.5 r=1.5/><path d=\\'m21 15-5-5L5 21\\'/></svg>Sin foto en servidor</div>'" />
                        </div>
                    </div>
                `;
            }
            // ============================================================

            window.bioSwitchTab('doc');

            // Ocultar menú superior de pestañas
            const menuPestañas = document.getElementById('bio-tab-fh')?.parentElement;
            if (menuPestañas) {
                menuPestañas.style.display = 'none';
            }

            document.getElementById('btn-modal-biometrico').click();
        })
        .catch(() => Swal.fire({ title: 'Error al obtener biométrico', icon: 'error' }));
});

window.bioSwitchTab = function (tab) {
    const esFH = tab === 'fh';
    const panelFh = document.getElementById('bio-panel-fh');
    const panelDoc = document.getElementById('bio-panel-doc');

    if (panelFh) panelFh.style.display = esFH ? 'flex' : 'none';
    if (panelDoc) panelDoc.style.display = esFH ? 'none' : 'block';

    const tabFh = document.getElementById('bio-tab-fh');
    const tabDoc = document.getElementById('bio-tab-doc');

    if (tabFh) {
        tabFh.classList.toggle('border-indigo-500', esFH);
        tabFh.classList.toggle('text-indigo-600', esFH);
        tabFh.classList.toggle('border-transparent', !esFH);
        tabFh.classList.toggle('text-gray-500', !esFH);
    }

    if (tabDoc) {
        tabDoc.classList.toggle('border-indigo-500', !esFH);
        tabDoc.classList.toggle('text-indigo-600', !esFH);
        tabDoc.classList.toggle('border-transparent', esFH);
        tabDoc.classList.toggle('text-gray-500', esFH);
    }
};
// ============================================================
// RENDER DE IMÁGENES (biométrico) hola
// ============================================================
function renderImagen(img, esDni = false, reverso = null) {
    if (!img || typeof img !== 'string' || !img.startsWith('data:')) {
        return `
            <div style="width:100%;${esDni ? 'height:420px;' : 'height:180px;'}
                display:flex;flex-direction:column;align-items:center;justify-content:center;
                background:#f8fafc;border:1.5px dashed #e2e8f0;border-radius:12px;color:#94a3b8;gap:8px;">
                <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="18" height="18" rx="3"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <path d="m21 15-5-5L5 21"/>
                </svg>
                <span style="font-size:12px;font-weight:500;">Sin imagen</span>
            </div>`;
    }

    const id = 'img_' + Math.random().toString(36).substr(2, 9);
    let mostrandoReverso = false;

    const toggleBtn = esDni ? `
        <button onclick="toggleDni_${id}()" id="toggleBtn_${id}" style="
            width:100%;font-size:12px;padding:7px 0;background:#f1f5f9;border:none;
            border-top:1px solid #e2e8f0;border-radius:0 0 12px 12px;
            cursor:pointer;color:#475569;font-weight:500;transition:background .15s;">
            <svg style="display:inline;vertical-align:-2px;margin-right:4px;" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
            </svg>Ver reverso
        </button>` : '';

    setTimeout(() => {
        if (esDni) {
            window[`toggleDni_${id}`] = function () {
                mostrandoReverso = !mostrandoReverso;
                document.getElementById(id).src = mostrandoReverso ? (reverso || img) : img;
                const badge = document.getElementById('badge_' + id);
                if (badge) badge.textContent = mostrandoReverso ? 'REVERSO' : 'ANVERSO';
                document.getElementById('toggleBtn_' + id).innerHTML = mostrandoReverso
                    ? `<svg style="display:inline;vertical-align:-2px;margin-right:4px;" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg> Ver anverso`
                    : `<svg style="display:inline;vertical-align:-2px;margin-right:4px;" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg> Ver reverso`;
            };
        }
        const btn = document.getElementById('toggleBtn_' + id);
        if (btn) {
            btn.onmouseover = () => btn.style.background = '#e2e8f0';
            btn.onmouseout = () => btn.style.background = '#f1f5f9';
        }
    }, 0);

    const btnAccion = esDni
        ? `<button onclick="abrirLightbox('${id}')" style="
                position:absolute;bottom:8px;right:8px;background:rgba(99,102,241,0.9);color:white;
                border:none;border-radius:8px;padding:5px 10px;font-size:11px;font-weight:500;
                cursor:pointer;z-index:11;display:flex;align-items:center;gap:5px;
                box-shadow:0 2px 8px rgba(99,102,241,0.4);transition:background .15s;"
                onmouseover="this.style.background='#4f46e5'" onmouseout="this.style.background='rgba(99,102,241,0.9)'">
                <svg width="12" height="12" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="M15 3h6m0 0v6m0-6-7 7M9 21H3m0 0v-6m0 6 7-7"/>
                </svg>Ver
           </button>`
        : `<button onclick="toggleLupa('${id}')" id="lupaBtn_${id}" style="
                position:absolute;bottom:8px;right:8px;background:rgba(99,102,241,0.9);color:white;
                border:none;border-radius:50%;width:30px;height:30px;cursor:pointer;z-index:11;
                display:flex;align-items:center;justify-content:center;
                box-shadow:0 2px 8px rgba(99,102,241,0.4);transition:transform .15s,background .15s;"
                onmouseover="this.style.transform='scale(1.1)';this.style.background='#4f46e5'"
                onmouseout="this.style.transform='scale(1)';this.style.background='rgba(99,102,241,0.9)'"
                title="Activar lupa">
                <svg width="13" height="13" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>
           </button>`;

    const lupaDiv = !esDni ? `
        <div id="lupa_${id}" style="
            display:none;position:absolute;width:130px;height:130px;border-radius:50%;
            border:2.5px solid #6366f1;box-shadow:0 0 0 3px rgba(99,102,241,0.15);
            pointer-events:none;background-repeat:no-repeat;z-index:10;"></div>` : '';

    return `
        <div style="border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.06);width:100%;">
            <div id="cont_${id}" class="m-0 p-0" style="position:relative;width:100%;
                ${esDni ? 'height:420px;' : 'height:180px;'}
                background:#f8fafc;overflow:hidden;
                display:flex;align-items:center;justify-content:center;
                ${!esDni ? 'cursor:crosshair;' : ''}">
                <img id="${id}" src="${img}"
                     style="max-width:100%;max-height:100%;width:95%;height:auto;object-fit:contain;display:block;cursor:${esDni ? 'zoom-in' : 'crosshair'};"
                     ${esDni ? `onclick="abrirLightbox('${id}')"` : ''}
                     onerror="this.parentElement.innerHTML='<div style=\'width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:12px;flex-direction:column;gap:6px;\'><svg width=32 height=32 fill=none stroke=currentColor stroke-width=1.5 viewBox=\'0 0 24 24\'><rect x=3 y=3 width=18 height=18 rx=3/><circle cx=8.5 cy=8.5 r=1.5/><path d=\'m21 15-5-5L5 21\'/></svg>Sin imagen</div>'" />
                ${lupaDiv}
                ${esDni ? `<span id="badge_${id}" style="position:absolute;top:8px;left:8px;background:rgba(99,102,241,0.9);color:#fff;font-size:10px;font-weight:600;padding:3px 8px;border-radius:20px;letter-spacing:0.5px;z-index:5;">ANVERSO</span>` : ''}
                ${btnAccion}
            </div>
            ${toggleBtn}
        </div>`;
}

// ============================================================
// LUPA
// ============================================================
window.toggleLupa = function (id) {
    const lupa = document.getElementById('lupa_' + id);
    const img = document.getElementById(id);
    const cont = document.getElementById('cont_' + id);
    if (!lupa || !img || !cont) return;

    const activa = lupa.style.display === 'block';

    if (!activa) {
        lupa.style.display = 'block';
        cont.style.overflow = 'visible';

        cont.onmousemove = function (e) {
            const contRect = cont.getBoundingClientRect();
            const imgRect = img.getBoundingClientRect();
            const cx = e.clientX - contRect.left;
            const cy = e.clientY - contRect.top;
            const ix = e.clientX - imgRect.left;
            const iy = e.clientY - imgRect.top;
            const lw = lupa.offsetWidth, lh = lupa.offsetHeight, scale = 2.8;

            lupa.style.left = (cx - lw / 2) + 'px';
            lupa.style.top = (cy - lh / 2) + 'px';
            lupa.style.backgroundImage = `url('${img.src}')`;
            lupa.style.backgroundSize = `${imgRect.width * scale}px ${imgRect.height * scale}px`;
            lupa.style.backgroundPosition = `${-(ix * scale - lw / 2)}px ${-(iy * scale - lh / 2)}px`;
        };

        cont.onmouseleave = function () {
            lupa.style.display = 'none';
            cont.style.overflow = 'hidden';
            cont.onmousemove = cont.onmouseleave = null;
        };
    } else {
        lupa.style.display = 'none';
        cont.style.overflow = 'hidden';
        cont.onmousemove = cont.onmouseleave = null;
    }
};

// ============================================================
// LIGHTBOX
// ============================================================
(function () {
    const lb = document.createElement('div');
    lb.id = 'lb-overlay';
    lb.style.cssText = `display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.92);flex-direction:column;align-items:center;justify-content:center;`;

    // (Aquí va el mismo innerHTML larguísimo del Lightbox que ya tenías, no le borres nada)
    lb.innerHTML = `
        <div style="width:100%;padding:10px 20px;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,0.1);">
            <span id="lb-titulo" style="color:#e5e7eb;font-size:13px;font-weight:500;">Vista de imagen</span>
            <button id="lb-close-top" style="background:rgba(220,38,38,0.7);border:1px solid rgba(220,38,38,0.5);color:white;border-radius:8px;width:34px;height:34px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                <svg width="14" height="14" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="lb-canvas" style="flex:1;width:100%;display:flex;align-items:center;justify-content:center;overflow:hidden;cursor:grab;user-select:none;position:relative;">
            <img id="lb-img" style="max-width:90vw;max-height:75vh;transform-origin:center center;pointer-events:none;display:block;"/>
            <div style="position:absolute;bottom:16px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.65);backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,0.15);border-radius:12px;padding:8px 14px;display:flex;align-items:center;gap:8px;">
                <button id="lb-zout" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);color:white;border-radius:7px;width:34px;height:34px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s;">
                    <svg width="15" height="15" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3M8 11h6"/></svg>
                </button>
                <span id="lb-zoom-label" style="color:#e5e7eb;font-size:12px;font-weight:600;min-width:40px;text-align:center;">100%</span>
                <button id="lb-zin" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);color:white;border-radius:7px;width:34px;height:34px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s;">
                    <svg width="15" height="15" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3M11 8v6M8 11h6"/></svg>
                </button>
                <div style="width:1px;height:24px;background:rgba(255,255,255,0.2);"></div>
                <button id="lb-reset" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);color:white;border-radius:7px;width:34px;height:34px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s;">
                    <svg width="14" height="14" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                </button>
                <div style="width:1px;height:24px;background:rgba(255,255,255,0.2);"></div>
                <button id="lb-fullscreen" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);color:white;border-radius:7px;width:34px;height:34px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s;">
                    <svg id="lb-fs-icon" width="14" height="14" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 3h6m0 0v6m0-6-7 7M9 21H3m0 0v-6m0 6 7-7"/></svg>
                </button>
            </div>
        </div>`;

    document.body.appendChild(lb);

    let scale = 1, posX = 0, posY = 0, dragging = false, startX = 0, startY = 0;
    const lbImg = document.getElementById('lb-img');
    const lbCanvas = document.getElementById('lb-canvas');
    const lbLabel = document.getElementById('lb-zoom-label');

    const applyTransform = () => {
        lbImg.style.transform = `translate(${posX}px, ${posY}px) scale(${scale})`;
        lbLabel.textContent = Math.round(scale * 100) + '%';
    };
    const resetView = () => { scale = 1; posX = 0; posY = 0; applyTransform(); };

    lbCanvas.addEventListener('wheel', (e) => {
        e.preventDefault();
        scale = Math.min(Math.max(scale + (e.deltaY > 0 ? -0.15 : 0.15), 0.3), 8);
        applyTransform();
    }, { passive: false });

    lbCanvas.addEventListener('mousedown', (e) => {
        if (e.target.closest('button')) return;
        dragging = true; startX = e.clientX - posX; startY = e.clientY - posY;
        lbCanvas.style.cursor = 'grabbing';
    });
    document.addEventListener('mousemove', (e) => {
        if (!dragging) return;
        posX = e.clientX - startX; posY = e.clientY - startY; applyTransform();
    });
    document.addEventListener('mouseup', () => { dragging = false; lbCanvas.style.cursor = 'grab'; });

    document.getElementById('lb-zin').onclick = () => { scale = Math.min(scale + 0.25, 8); applyTransform(); };
    document.getElementById('lb-zout').onclick = () => { scale = Math.max(scale - 0.25, 0.3); applyTransform(); };
    document.getElementById('lb-reset').onclick = resetView;
    document.getElementById('lb-close-top').onclick = cerrarLightbox;
    document.getElementById('lb-fullscreen').onclick = () => {
        if (!document.fullscreenElement) {
            lb.requestFullscreen?.();
            document.getElementById('lb-fs-icon').innerHTML = `<path d="M8 3H3m0 0v5m0-5 7 7M16 21h5m0 0v-5m0 5-7-7"/>`;
        } else {
            document.exitFullscreen?.();
            document.getElementById('lb-fs-icon').innerHTML = `<path d="M15 3h6m0 0v6m0-6-7 7M9 21H3m0 0v-6m0 6 7-7"/>`;
        }
    };

    document.addEventListener('keydown', (e) => {
        if (lb.style.display === 'none') return;
        if (e.key === 'Escape') cerrarLightbox();
        if (e.key === '+' || e.key === '=') { scale = Math.min(scale + 0.25, 8); applyTransform(); }
        if (e.key === '-') { scale = Math.max(scale - 0.25, 0.3); applyTransform(); }
        if (e.key === '0') resetView();
    });

    ['lb-zin', 'lb-zout', 'lb-reset', 'lb-fullscreen'].forEach(id => {
        const b = document.getElementById(id);
        b.onmouseover = () => b.style.background = 'rgba(255,255,255,0.25)';
        b.onmouseout = () => b.style.background = 'rgba(255,255,255,0.12)';
    });

    window.abrirLightbox = function (imgId) {
        const imgEl = document.getElementById(imgId);
        if (!imgEl) return;
        lbImg.src = imgEl.src;
        lb.style.display = 'flex';
        resetView();
    };

    function cerrarLightbox() {
        lb.style.display = 'none';
        lbImg.src = '';
        if (document.fullscreenElement) document.exitFullscreen?.();
    }
})();