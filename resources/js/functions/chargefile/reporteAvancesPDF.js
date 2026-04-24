/**
 * reporteAvancesPDF.js
 * --------------------
 * Genera el reporte de avances en formato PDF (landscape A4).
 *
 * Librería requerida (incluir en el HTML antes del bundle):
 *   - jsPDF       : https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js
 *   - AutoTable   : https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js
 *
 * Acceso global: window.jspdf.jsPDF
 */

// ---------------------------------------------------------------------------
// CONSTANTES DE ESTILO
// ---------------------------------------------------------------------------

/** Colores corporativos del reporte (RGB) */
const COLOR = {
    cabecera  : [30,  64, 175],   // Azul oscuro  (equivalente a blue-800)
    filaPar   : [239, 246, 255],  // Azul muy claro (blue-50)
    filaImpar : [255, 255, 255],  // Blanco
    completo  : [21,  128, 61],   // Verde  (green-700)
    incompleto: [185, 28,  28],   // Rojo   (red-700)
    textoCab  : [255, 255, 255],  // Blanco
    textoBody : [31,  41,  55],   // Gris oscuro (gray-800)
    borde     : [209, 213, 219],  // Gris claro (gray-300)
    subtitulo : [75,  85,  99],   // Gris medio (gray-600)
};

/** Nombre del archivo descargado */
const NOMBRE_ARCHIVO = 'Reporte_Avances_RRHH.pdf';

// ---------------------------------------------------------------------------
// COLUMNAS DE LA TABLA
// ---------------------------------------------------------------------------

/**
 * Define el encabezado y la clave de cada columna del reporte.
 * Ajusta el array si se agregan o quitan campos en el futuro.
 */
const COLUMNAS = [
    { header: 'COD.',               dataKey: 'cod'                },
    { header: 'NOMBRES',            dataKey: 'nombres'            },
    { header: 'DOC.',               dataKey: 'doc'                },
    { header: 'SUCURSAL',           dataKey: 'sucursal'           },
    { header: 'TIPO',               dataKey: 'tipo'               },
    { header: 'DJ ESCANEADA',          dataKey: 'dj_subido'          },
    { header: 'FIRMA ACTUALIZADA',  dataKey: 'firma_actualizada'  },
    { header: 'HUELLA ACTUALIZADA', dataKey: 'huella_actualizada' },
    { header: 'ESTADO',              dataKey: 'estado'              },
    { header: 'ULTIMA ACTUALIZACION', dataKey: 'ultima_actualizacion' },
];

// ---------------------------------------------------------------------------
// FUNCIÓN PRINCIPAL
// ---------------------------------------------------------------------------

/**
 * Genera y descarga el PDF del reporte de avances.
 *
 * @param {Array<Object>} datos - Registros filtrados del reporte
 * @param {{ sucursal: string, tipo: string, fecha: string }} meta - Datos del encabezado
 */
async function generarPDF(datos, meta) {
    const { jsPDF } = window.jspdf;
 
    if (!jsPDF) {
        console.error('[ReporteAvancesPDF] jsPDF no está disponible en window.jspdf');
        return;
    }
 
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
 
    agregarCabecera(doc, meta);
    const startY = agregarContadores(doc, datos);   // ← cards resumen
    agregarTabla(doc, datos, startY);
    agregarPieDePagina(doc);
 
    doc.save(NOMBRE_ARCHIVO);
}


/**
 * Tres "cards" resumen: Total personal · DJ Subido · DJ No Subido.
 * Se dibujan entre la cabecera y la tabla.
 *
 * @param {jsPDF} doc
 * @param {Array<Object>} datos
 * @returns {number} startY - posición Y donde debe arrancar la tabla
 */
function agregarContadores(doc, datos) {
    const con   = datos.filter(r => r.dj_subido).length;
    const sin   = datos.length - con;
    const total = datos.length;
    const ancho = doc.internal.pageSize.getWidth();
 
    const cardW  = 60;
    const cardH  = 14;
    const gap    = 6;
    const startY = 26;   // justo bajo la cabecera
 
    const totalW = cardW * 3 + gap * 2;
    let   x      = (ancho - totalW) / 2;
 
    const cards = [
        { label: 'TOTAL PERSONAL', valor: total, bg: [30, 64, 175]  },  // azul
        { label: 'DJ ESCANEADAS',      valor: con,   bg: [21, 128, 61]  },  // verde
        { label: 'DJ SIN ESCANEAR',   valor: sin,   bg: [185, 28, 28]  },  // rojo
    ];
 
    cards.forEach(card => {
        // Fondo redondeado
        doc.setFillColor(...card.bg);
        doc.roundedRect(x, startY, cardW, cardH, 2, 2, 'F');
 
        // Etiqueta
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(6.5);
        doc.setTextColor(255, 255, 255);
        doc.text(card.label, x + cardW / 2, startY + 4.5, { align: 'center' });
 
        // Número
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(11);
        doc.text(String(card.valor), x + cardW / 2, startY + 11, { align: 'center' });
 
        x += cardW + gap;
    });
 
    return startY + cardH + 4;   // margen de 4 mm antes de la tabla
}

// ---------------------------------------------------------------------------
// SECCIONES DEL DOCUMENTO
// ---------------------------------------------------------------------------

/**
 * Dibuja el encabezado del PDF: franja de color, título y subtítulos con los filtros.
 *
 * @param {jsPDF} doc
 * @param {{ sucursal: string, tipo: string, fecha: string }} meta
 */
function agregarCabecera(doc, meta) {
    const ancho = doc.internal.pageSize.getWidth();
 
    doc.setFillColor(...COLOR.cabecera);
    doc.rect(0, 0, ancho, 22, 'F');
 
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(14);
    doc.setTextColor(...COLOR.textoCab);
    doc.text('REPORTE DE AVANCES - RECURSOS HUMANOS', ancho / 2, 10, { align: 'center' });
 
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.text(
        `Sucursal: ${meta.sucursal}   |   Tipo: ${etiquetaTipo(meta.tipo)}   |   Generado: ${meta.fecha}`,
        ancho / 2, 17,
        { align: 'center' }
    );
}

/**
 * Dibuja la tabla de datos con autoTable.
 *
 * @param {jsPDF} doc
 * @param {Array<Object>} datos
 */
function agregarTabla(doc, datos, startY = 26) {
    const filas = datos.map(r => ({
        cod                 : r.cod,
        nombres             : r.nombres,
        doc                 : r.doc,
        sucursal            : r.sucursal,
        tipo                : etiquetaTipo(r.tipo),
        dj_subido           : r.dj_subido          ? 'SI' : 'NO',
        firma_actualizada   : r.firma_actualizada   ? 'SI' : 'NO',
        huella_actualizada  : r.huella_actualizada  ? 'SI' : 'NO',
        estado              : calcularEstado(r),
        ultima_actualizacion: formatearFecha(r.ultima_actualizacion),
    }));
 
    doc.autoTable({
        columns : COLUMNAS,
        body    : filas,
        startY,
        margin  : { left: 8, right: 8 },
        tableWidth: 'auto',
 
        styles: {
            fontSize   : 7.5,
            cellPadding: 2.5,
            textColor  : COLOR.textoBody,
            lineColor  : COLOR.borde,
            lineWidth  : 0.2,
            overflow   : 'linebreak',
        },
 
        headStyles: {
            fillColor  : COLOR.cabecera,
            textColor  : COLOR.textoCab,
            fontStyle  : 'bold',
            halign     : 'center',
            fontSize   : 8,
            cellPadding: 3,
        },
 
        alternateRowStyles: {
            fillColor: COLOR.filaPar,
        },
 
        columnStyles: {
            cod                 : { halign: 'center', cellWidth: 14 },
            nombres             : { cellWidth: 55                    },
            doc                 : { halign: 'center', cellWidth: 22 },
            sucursal            : { halign: 'center', cellWidth: 24 },
            tipo                : { halign: 'center', cellWidth: 28 },
            dj_subido           : { halign: 'center', cellWidth: 22 },
            firma_actualizada   : { halign: 'center', cellWidth: 30 },
            huella_actualizada  : { halign: 'center', cellWidth: 30 },
            estado              : { halign: 'center', cellWidth: 24 },
            ultima_actualizacion: { halign: 'center', cellWidth: 32 },
        },
 
        willDrawCell(data) {
            if (data.section !== 'body' || data.column.dataKey !== 'estado') return;
            const esCompleto = data.cell.raw === 'COMPLETO';
            data.cell.styles.textColor = esCompleto ? COLOR.completo : COLOR.incompleto;
            data.cell.styles.fontStyle = 'bold';
        },
    });
}

/**
 * Agrega el número de página centrado en el pie de cada página.
 *
 * @param {jsPDF} doc
 */
function agregarPieDePagina(doc) {
    const totalPaginas = doc.internal.getNumberOfPages();
    const ancho = doc.internal.pageSize.getWidth();
    const alto  = doc.internal.pageSize.getHeight();
 
    for (let i = 1; i <= totalPaginas; i++) {
        doc.setPage(i);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7);
        doc.setTextColor(...COLOR.subtitulo);
        doc.text(`Página ${i} de ${totalPaginas}`, ancho / 2, alto - 5, { align: 'center' });
    }
}

// ---------------------------------------------------------------------------
// UTILIDADES
// ---------------------------------------------------------------------------

/**
 * Formatea una fecha ISO (YYYY-MM-DD) al formato DD/MM/YYYY para mostrar en el reporte.
 * Si el valor es nulo o inválido, devuelve un guion como valor neutro.
 *
 * @param {string|null} fechaIso
 * @returns {string}
 */
function formatearFecha(fechaIso) {
    if (!fechaIso) return '-';
    const [anio, mes, dia] = fechaIso.split('-');
    if (!anio || !mes || !dia) return '-';
    return `${dia}/${mes}/${anio}`;
}

/**
 * Devuelve la etiqueta legible del tipo de personal.
 * @param {string} tipo
 * @returns {string}
 */
function etiquetaTipo(tipo) {
    const mapa = { OPER: 'Operativo', ADMIN: 'Administrativo', Todos: 'Todos' };
    return mapa[tipo] ?? tipo;
}

/**
 * Calcula si el registro está completo (todos los campos en true).
 * Modifica esta función si cambia la lógica de negocio.
 *
 * @param {Object} registro
 * @returns {'COMPLETO'|'INCOMPLETO'}
 */
function calcularEstado(registro) {
    const completo = registro.dj_subido && registro.firma_actualizada && registro.huella_actualizada;
    return completo ? 'COMPLETO' : 'INCOMPLETO';
}

// ---------------------------------------------------------------------------
// EXPORTACIÓN
// ---------------------------------------------------------------------------

export { generarPDF };