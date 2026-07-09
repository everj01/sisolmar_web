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

    // 🔥 FILTRAMOS LAS COLUMNAS DEPENDIENDO SI ES ETAPA 4
    let columnasActivas = COLUMNAS;
    if (meta.origen === 'etapa4') {
        columnasActivas = COLUMNAS.filter(c => c.header !== 'FIRMA ACTUALIZADA' && c.header !== 'HUELLA ACTUALIZADA');
    }

    const libro = new ExcelJS.Workbook();
    libro.creator = 'Sistema RRHH';
    libro.created = new Date();

    const hoja = libro.addWorksheet(NOMBRE_HOJA, {
        views: [{ state: 'frozen', ySplit: 7 }],
    });

    hoja.columns = columnasActivas.map(c => ({ width: c.width }));

    agregarFilaTitulo(hoja, columnasActivas.length, meta.tituloReporte);
    agregarFilaFiltros(hoja, meta, columnasActivas.length);
    agregarFilaVacia(hoja);
    agregarFilasContadores(hoja, datos); 
    agregarEncabezadosColumnas(hoja, columnasActivas);
    agregarFilasDatos(hoja, datos, meta);

    const buffer = await libro.xlsx.writeBuffer();
    const f = new Date();
    const fStr = `${String(f.getDate()).padStart(2, '0')}_${String(f.getMonth() + 1).padStart(2, '0')}_${f.getFullYear()}`;
    const sucursalLimpia = meta.sucursal.trim().replace(/\s+/g, '_');
    
    let nombreArchivoDin = `Reporte_Avances_RRHH_${sucursalLimpia}_${fStr}.xlsx`;
    if (meta.origen === 'etapa4') {
        nombreArchivoDin = `Etapa4_Carga_DJ_${meta.estadoArchivo}_${meta.tipoArchivo}_${sucursalLimpia}_${fStr}.xlsx`;
    } else if (meta.origen === 'etapa5') {
        nombreArchivoDin = `Etapa5_Validacion_Imagenes_${meta.estadoArchivo}_${meta.tipoArchivo}_${sucursalLimpia}_${fStr}.xlsx`;
    }

    descargarBuffer(buffer, nombreArchivoDin);
}

// ---------------------------------------------------------------------------
// SECCIONES DE LA HOJA
// ---------------------------------------------------------------------------

function agregarFilaTitulo(hoja, totalCols, tituloReporte) {
    const tituloFinal = tituloReporte || 'REPORTE DE AVANCES - RECURSOS HUMANOS';
    const fila  = hoja.addRow([tituloFinal]);
    fila.height = 24;

    const celda     = fila.getCell(1);
    celda.font      = { bold: true, color: { argb: COLOR.textoCabecera }, size: 13 };
    celda.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: COLOR.fondoTitulo } };
    celda.alignment = { horizontal: 'center', vertical: 'middle' };
    hoja.mergeCells(1, 1, 1, totalCols);
}

function agregarFilaFiltros(hoja, meta, totalCols) {
    const texto = `Sucursal: ${meta.sucursal}   |   Tipo: ${etiquetaTipo(meta.tipo)}   |   Generado: ${meta.fecha}`;
    const fila  = hoja.addRow([texto]);
    fila.height = 16;

    const celda     = fila.getCell(1);
    celda.font      = { italic: true, color: { argb: COLOR.textoFiltros }, size: 8.5 };
    celda.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: COLOR.fondoFiltros } };
    celda.alignment = { horizontal: 'center', vertical: 'middle' };
    hoja.mergeCells(2, 1, 2, totalCols);
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

function agregarEncabezadosColumnas(hoja, columnasActivas) {
    const fila  = hoja.addRow(columnasActivas.map(c => c.header));
    fila.height = 30;

    fila.eachCell(celda => {
        celda.font      = { bold: true, color: { argb: COLOR.textoCabecera }, size: 9 };
        celda.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: COLOR.cabecera } };
        celda.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        celda.border    = BORDE_FINO;
    });
}

function agregarFilasDatos(hoja, datos, meta) {
    datos.forEach((registro, indice) => {
        const valores = construirFila(registro, meta);
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

        // La celda de estado cambia de índice según el origen
        const estadoCol = meta.origen === 'etapa4' ? 7 : 9;
        const estadoVal = meta.origen === 'etapa4' ? valores[6] : valores[8];
        aplicarEstiloEstado(fila.getCell(estadoCol), estadoVal);
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

function construirFila(r, meta) {
    if (meta.origen === 'etapa4') {
        return [
            r.cod, r.nombres, r.doc, r.sucursal, etiquetaTipo(r.tipo),
            r.dj_subido ? 'SI' : 'NO',
            calcularEstado(r, meta), formatearFecha(r.ultima_actualizacion),
        ];
    }

    return [
        r.cod, r.nombres, r.doc, r.sucursal, etiquetaTipo(r.tipo),
        r.dj_subido ? 'SI' : 'NO', r.firma_actualizada ? 'SI' : 'NO', r.huella_actualizada ? 'SI' : 'NO',
        calcularEstado(r, meta), formatearFecha(r.ultima_actualizacion),
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

function calcularEstado(registro, meta) {
    if (meta.origen === 'etapa4') {
        return registro.dj_subido ? 'COMPLETO' : 'INCOMPLETO';
    }
    const completo = registro.dj_subido && registro.firma_actualizada && registro.huella_actualizada;
    return completo ? 'COMPLETO' : 'INCOMPLETO';
}

// ---------------------------------------------------------------------------
// EXPORTACIÓN
// ---------------------------------------------------------------------------

export { generarExcel };