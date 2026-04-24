/**
 * reporteAvancesExcel.js
 * ----------------------
 * Genera el reporte de avances en formato Excel (.xlsx) con estilos completos.
 *
 * Librería requerida:
 *   <script src="https://unpkg.com/exceljs@4.4.0/dist/exceljs.min.js"></script>
 *
 * Acceso global: window.ExcelJS
 */

// ---------------------------------------------------------------------------
// CONSTANTES
// ---------------------------------------------------------------------------

const NOMBRE_ARCHIVO = 'Reporte_Avances_RRHH.xlsx';
const NOMBRE_HOJA    = 'Avances';

const COLOR = {
    cabecera      : 'FF1E40AF',
    filaPar       : 'FFEFF6FF',
    filaImpar     : 'FFFFFFFF',
    completo      : 'FF15803D',
    incompleto    : 'FFB91C1C',
    textoCabecera : 'FFFFFFFF',
    textoBody     : 'FF1F2937',
    borde         : 'FFD1D5DB',
    fondoTitulo   : 'FF1E3A8A',
    textoFiltros  : 'FF4B5563',
    fondoFiltros  : 'FFF1F5F9',

    // Cards contadores
    cardTotal     : 'FF1E40AF',   // azul
    cardSi        : 'FF15803D',   // verde
    cardNo        : 'FFB91C1C',   // rojo
    cardTexto     : 'FFFFFFFF',   // blanco
};

const COLUMNAS = [
    { header: 'COD.',                 width: 10  },
    { header: 'NOMBRES',              width: 38  },
    { header: 'DOC.',                 width: 14  },
    { header: 'SUCURSAL',             width: 14  },
    { header: 'TIPO',                 width: 16  },
    { header: 'DJ ESCANEADA',            width: 13  },
    { header: 'FIRMA ACTUALIZADA',    width: 20  },
    { header: 'HUELLA ACTUALIZADA',   width: 20  },
    { header: 'ESTADO',               width: 14  },
    { header: 'ULTIMA ACTUALIZACION', width: 22  },
];

const BORDE_FINO = {
    top   : { style: 'thin', color: { argb: COLOR.borde } },
    left  : { style: 'thin', color: { argb: COLOR.borde } },
    bottom: { style: 'thin', color: { argb: COLOR.borde } },
    right : { style: 'thin', color: { argb: COLOR.borde } },
};

// ---------------------------------------------------------------------------
// FUNCIÓN PRINCIPAL
// ---------------------------------------------------------------------------

async function generarExcel(datos, meta) {
    const ExcelJS = window.ExcelJS ?? globalThis.ExcelJS ?? null;

    if (!ExcelJS) {
        console.error('[ReporteAvancesExcel] ExcelJS no encontrado.');
        alert('Error: la librería ExcelJS no está cargada. Revisa la consola.');
        return;
    }

    const libro = new ExcelJS.Workbook();
    libro.creator = 'Sistema RRHH';
    libro.created = new Date();

    const hoja = libro.addWorksheet(NOMBRE_HOJA, {
        // Congela las 6 primeras filas (título + filtros + vacía + cards x2 + vacía + encabezados)
        views: [{ state: 'frozen', ySplit: 7 }],
    });

    hoja.columns = COLUMNAS.map(c => ({ width: c.width }));

    agregarFilaTitulo(hoja);
    agregarFilaFiltros(hoja, meta);
    agregarFilaVacia(hoja);
    agregarFilasContadores(hoja, datos);   // ← 3 filas de cards (etiquetas + valores + vacía)
    agregarEncabezadosColumnas(hoja);
    agregarFilasDatos(hoja, datos);

    const buffer = await libro.xlsx.writeBuffer();
    descargarBuffer(buffer, NOMBRE_ARCHIVO);
}

// ---------------------------------------------------------------------------
// SECCIONES DE LA HOJA
// ---------------------------------------------------------------------------

function agregarFilaTitulo(hoja) {
    const fila  = hoja.addRow(['REPORTE DE AVANCES - RECURSOS HUMANOS']);
    fila.height = 24;

    const celda     = fila.getCell(1);
    celda.font      = { bold: true, color: { argb: COLOR.textoCabecera }, size: 13 };
    celda.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: COLOR.fondoTitulo } };
    celda.alignment = { horizontal: 'center', vertical: 'middle' };
    hoja.mergeCells(1, 1, 1, COLUMNAS.length);
}

function agregarFilaFiltros(hoja, meta) {
    const texto = `Sucursal: ${meta.sucursal}   |   Tipo: ${etiquetaTipo(meta.tipo)}   |   Generado: ${meta.fecha}`;
    const fila  = hoja.addRow([texto]);
    fila.height = 16;

    const celda     = fila.getCell(1);
    celda.font      = { italic: true, color: { argb: COLOR.textoFiltros }, size: 8.5 };
    celda.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: COLOR.fondoFiltros } };
    celda.alignment = { horizontal: 'center', vertical: 'middle' };
    hoja.mergeCells(2, 1, 2, COLUMNAS.length);
}

function agregarFilaVacia(hoja) {
    hoja.addRow([]);
}

/**
 * Cards resumen en Excel: tres bloques de 3 columnas cada uno.
 * Fila de etiquetas (fila 4) + fila de valores (fila 5) + fila vacía (fila 6).
 *
 * Layout en columnas (1-10):
 *   [1-3] TOTAL PERSONAL  |  [4-6] DJ SUBIDO  |  [7-9] DJ NO SUBIDO
 *   Columna 10 queda libre para no cortar el borde de la hoja.
 *
 * @param {ExcelJS.Worksheet} hoja
 * @param {Array<Object>} datos
 */
function agregarFilasContadores(hoja, datos) {
    const con   = datos.filter(r => r.dj_subido).length;
    const sin   = datos.length - con;
    const total = datos.length;

    const cards = [
        { label: 'TOTAL PERSONAL', valor: total, color: COLOR.cardTotal, cols: [1, 3] },
        { label: 'DJ ESCANEADAS',      valor: con,   color: COLOR.cardSi,    cols: [4, 6] },
        { label: 'DJ SIN ESCANEAR',   valor: sin,   color: COLOR.cardNo,    cols: [7, 9] },
    ];

    // ── Fila de etiquetas ────────────────────────────────────────────────────
    const filaEtiq = hoja.addRow([]);
    filaEtiq.height = 16;

    cards.forEach(card => {
        const celda = filaEtiq.getCell(card.cols[0]);
        celda.value     = card.label;
        celda.font      = { bold: true, color: { argb: COLOR.cardTexto }, size: 8.5 };
        celda.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: card.color } };
        celda.alignment = { horizontal: 'center', vertical: 'middle' };
        // Fusionar las 3 columnas del card
        hoja.mergeCells(filaEtiq.number, card.cols[0], filaEtiq.number, card.cols[1]);
    });

    // ── Fila de valores ──────────────────────────────────────────────────────
    const filaVal = hoja.addRow([]);
    filaVal.height = 22;

    cards.forEach(card => {
        const celda = filaVal.getCell(card.cols[0]);
        celda.value     = card.valor;
        celda.font      = { bold: true, color: { argb: COLOR.cardTexto }, size: 14 };
        celda.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: card.color } };
        celda.alignment = { horizontal: 'center', vertical: 'middle' };
        hoja.mergeCells(filaVal.number, card.cols[0], filaVal.number, card.cols[1]);
    });

    // ── Fila vacía separadora ────────────────────────────────────────────────
    hoja.addRow([]);
}

function agregarEncabezadosColumnas(hoja) {
    const fila  = hoja.addRow(COLUMNAS.map(c => c.header));
    fila.height = 30;

    fila.eachCell(celda => {
        celda.font      = { bold: true, color: { argb: COLOR.textoCabecera }, size: 9 };
        celda.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: COLOR.cabecera } };
        celda.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        celda.border    = BORDE_FINO;
    });
}

function agregarFilasDatos(hoja, datos) {
    datos.forEach((registro, indice) => {
        const valores = construirFila(registro);
        const fila    = hoja.addRow(valores);
        const esPar   = indice % 2 === 0;

        fila.height = 16;

        fila.eachCell({ includeEmpty: true }, (celda, numCol) => {
            celda.fill = {
                type    : 'pattern',
                pattern : 'solid',
                fgColor : { argb: esPar ? COLOR.filaPar : COLOR.filaImpar },
            };
            celda.border    = BORDE_FINO;
            celda.font      = { color: { argb: COLOR.textoBody }, size: 8.5 };
            celda.alignment = {
                horizontal: numCol === 2 ? 'left' : 'center',
                vertical  : 'middle',
            };
        });

        aplicarEstiloEstado(fila.getCell(9), valores[8]);
    });
}

// ---------------------------------------------------------------------------
// ESTILO DE ESTADO
// ---------------------------------------------------------------------------

function aplicarEstiloEstado(celda, valorEstado) {
    const esCompleto = valorEstado === 'COMPLETO';
    celda.font = {
        bold : true,
        size : 8.5,
        color: { argb: esCompleto ? COLOR.completo : COLOR.incompleto },
    };
}

// ---------------------------------------------------------------------------
// DESCARGA
// ---------------------------------------------------------------------------

function descargarBuffer(buffer, nombreArchivo) {
    const blob = new Blob([buffer], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    const url  = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href     = url;
    link.download = nombreArchivo;
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 500);
}

// ---------------------------------------------------------------------------
// UTILIDADES
// ---------------------------------------------------------------------------

function construirFila(r) {
    return [
        r.cod,
        r.nombres,
        r.doc,
        r.sucursal,
        etiquetaTipo(r.tipo),
        r.dj_subido          ? 'SI' : 'NO',
        r.firma_actualizada  ? 'SI' : 'NO',
        r.huella_actualizada ? 'SI' : 'NO',
        calcularEstado(r),
        formatearFecha(r.ultima_actualizacion),
    ];
}

function formatearFecha(fechaIso) {
    if (!fechaIso) return '-';
    const [anio, mes, dia] = fechaIso.split('-');
    if (!anio || !mes || !dia) return '-';
    return `${dia}/${mes}/${anio}`;
}

function etiquetaTipo(tipo) {
    const mapa = { OPER: 'Operativo', ADMIN: 'Administrativo', Todos: 'Todos' };
    return mapa[tipo] ?? tipo;
}

function calcularEstado(registro) {
    const completo = registro.dj_subido && registro.firma_actualizada && registro.huella_actualizada;
    return completo ? 'COMPLETO' : 'INCOMPLETO';
}

// ---------------------------------------------------------------------------
// EXPORTACIÓN
// ---------------------------------------------------------------------------

export { generarExcel };