import axios from 'axios';
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';
import Swal from 'sweetalert2';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';


document.addEventListener('DOMContentLoaded', () => {

   // ==============================
    // CONTENEDORES DE FILTROS
    // ==============================
    const filtros = {
        foliosVigentes: document.getElementById('filtrosFoliosVigentes'),
        foliosPendientesSucursal: document.getElementById('filtrosFoliosPendientesSucursal'),
        foliosPorVencer: document.getElementById('filtrosFoliosPorVencer'),
    };

    function ocultarTodosLosFiltros() {
        Object.values(filtros).forEach(div => {
            if (div) div.classList.add('hidden');
        });
    }

            new TomSelect('#filtroClienteSelect', {
            placeholder: '-Seleccionar-',
            allowEmptyOption: true
        });

        new TomSelect('#filtroSucursalSelect', {
            placeholder: '-Seleccionar-',
            allowEmptyOption: true
        });

    // ==============================
    // BOTONES PRINCIPALES
    // ==============================
    const btnVigentes = document.getElementById('btnReporteFoliosVigentes');
    const btnPendientes = document.getElementById('btnReporteFoliosPendientesSucursal');
    const btnPorVencer = document.getElementById('btnReporteFoliosPorVencer');

    if (btnVigentes) {
        btnVigentes.addEventListener('click', () => {
            ocultarTodosLosFiltros();
            filtros.foliosVigentes?.classList.remove('hidden');
        });
    }

    if (btnPendientes) {
        btnPendientes.addEventListener('click', () => {
            ocultarTodosLosFiltros();
            filtros.foliosPendientesSucursal?.classList.remove('hidden');
        });
    }

    if (btnPorVencer) {
        btnPorVencer.addEventListener('click', () => {
            ocultarTodosLosFiltros();
            filtros.foliosPorVencer?.classList.remove('hidden');
        });
    }

    // ==============================
    // RADIOS – FOLIOS POR VENCER
    // ==============================
    const radios = document.querySelectorAll('input[name="tipoFiltro"]');
const filtroSucursalDiv = document.getElementById('filtroSucursalDiv');    const filtroClienteDiv = document.getElementById('filtroClienteDiv');
     const filtroCodigoDiv = document.getElementById('filtroCodigoDiv');



    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            // Ocultar todos al inicio
            filtroSucursalDiv.classList.add('hidden');
            filtroClienteDiv.classList.add('hidden');
            filtroCodigoDiv.classList.add('hidden')

            // Mostrar según el seleccionado
            if(this.value === 'sucursal') {
                filtroSucursalDiv.classList.remove('hidden');
            } else if(this.value === 'cliente') {
                filtroClienteDiv.classList.remove('hidden');
            }else if(this.value === 'servicio'){
                filtroCodigoDiv.classList.remove('hidden');
            }

        });
    });


    // ==============================
      // MODALES
      // ==============================
      const modales = {
          foliosVigentes:           document.getElementById('modalFoliosVigentes'),
          foliosPendientesSucursal: document.getElementById('modalFoliosPendientesSucursal'),
          foliosPorVencer:          document.getElementById('modalFoliosPorVencer'),
      };

      function abrirModal(modal) {
          modal.classList.remove('hidden');
          modal.classList.add('flex');
      }

      function cerrarModal(modal) {
          modal.classList.add('hidden');
          modal.classList.remove('flex');
      }

      // Cerrar con botón X o Cancelar
      document.querySelectorAll('.btnCerrarModal').forEach(btn => {
          btn.addEventListener('click', () => {
              Object.values(modales).forEach(m => cerrarModal(m));
          });
      });

      // Cerrar al hacer click en el overlay
      Object.values(modales).forEach(modal => {
          modal.addEventListener('click', (e) => {
              if (e.target === modal) cerrarModal(modal);
          });
      });

      // TomSelect
      new TomSelect('#filtroClienteSelect', {
          placeholder: '-Seleccionar-',
          allowEmptyOption: true
      });

      new TomSelect('#filtroSucursalSelect', {
          placeholder: '-Seleccionar-',
          allowEmptyOption: true
      });

      // ==============================
      // BOTONES PRINCIPALES
      // ==============================
      const btnVigentes  = document.getElementById('btnReporteFoliosVigentes');
      const btnPendientes = document.getElementById('btnReporteFoliosPendientesSucursal');
      const btnPorVencer  = document.getElementById('btnReporteFoliosPorVencer');

      if (btnVigentes)   btnVigentes.addEventListener('click',   () =>
  abrirModal(modales.foliosVigentes));
      if (btnPendientes) btnPendientes.addEventListener('click', () =>
  abrirModal(modales.foliosPendientesSucursal));
      if (btnPorVencer)  btnPorVencer.addEventListener('click',  () =>
  abrirModal(modales.foliosPorVencer));

      // ==============================
      // RADIOS – FOLIOS POR VENCER
      // ==============================
      const radios           = document.querySelectorAll('input[name="tipoFiltro"]');
      const filtroSucursalDiv = document.getElementById('filtroSucursalDiv');
      const filtroClienteDiv  = document.getElementById('filtroClienteDiv');
      const filtroCodigoDiv   = document.getElementById('filtroCodigoDiv');

      radios.forEach(radio => {
          radio.addEventListener('change', function () {
              filtroSucursalDiv.classList.add('hidden');
              filtroClienteDiv.classList.add('hidden');
              filtroCodigoDiv.classList.add('hidden');

              if      (this.value === 'sucursal') filtroSucursalDiv.classList.remove('hidden');
              else if (this.value === 'cliente')  filtroClienteDiv.classList.remove('hidden');
              else if (this.value === 'servicio') filtroCodigoDiv.classList.remove('hidden');
          });
      });
});


document.getElementById('btnGenerarPdfFoliosVigentes')
    .addEventListener('click', () => {

        const tipo = document.getElementById('filtroTipoFolio').value;
        const prioridad = document.getElementById('filtroPrioridad').value;

        Swal.fire({
            title: 'Generando reporte',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        axios.get(`${VITE_URL_APP}/api/get-folios`)
            .then(response => {

                let datos = response.data.filter(f => f.habilitado == "1");

                if (tipo) {
                    datos = datos.filter(f => f.tipoFolio === tipo);
                }

                if (prioridad) {
                    datos = datos.filter(f => f.prioridad === prioridad);
                }

                if (datos.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin resultados',
                        text: 'No existen folios con los filtros seleccionados'
                    });
                    return;
                }


                // ================= PDF =================
                const doc = new jsPDF();

                const fechaActual = new Date().toLocaleDateString('es-PE', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });

                const pageWidth = doc.internal.pageSize.getWidth();
                const bannerHeight = 50; // Ajusta si lo ves muy alto o muy bajo

                // Cargar imagen desde public
                const img = new Image();
                img.src = `${VITE_URL_APP}/images/banners/banner_folios_vigentes.jpeg`;

                img.onload = function () {

                    // ====== BANNER SUPERIOR ======
                    doc.addImage(img, 'JPEG', 0, 0, pageWidth, bannerHeight);

                    // Posición inicial después del banner
                    let yPosition = bannerHeight + 10;

                    // ====== TÍTULO DE SECCIÓN ======
                    doc.setFontSize(13);
                    doc.setFont(undefined, 'bold');
                    doc.setFillColor(6, 10, 81);
                    doc.rect(10, yPosition, 190, 7, 'F');
                    doc.setTextColor(255, 255, 255);
                    doc.text('LISTADO GENERAL DE FOLIOS', 12, yPosition + 5);
                    doc.setTextColor(0, 0, 0);

                    yPosition += 10;

                    // ====== TABLA ======
                    autoTable(doc, {
                        startY: yPosition,
                        head: [['N°', 'Nombre del Folio', 'Tipo', 'Prioridad', 'Vencimiento']],
                        body: datos.map((folio, index) => [
                            index + 1,
                            folio.nombre,
                            folio.tipoFolio,
                            folio.prioridad,
                            folio.periodo || 'Sin vencimiento'
                        ]),
                        styles: {
                            fontSize: 8,
                            cellPadding: 2,
                            lineColor: [189, 195, 199],
                            lineWidth: 0.1
                        },
                        headStyles: {
                            fillColor: [6, 10, 81],
                            textColor: [255, 255, 255],
                            fontStyle: 'bold',
                            halign: 'center',
                            fontSize: 9,
                            cellPadding: 3
                        },
                        alternateRowStyles: {
                            fillColor: [250, 250, 250]
                        },
                        columnStyles: {
                            0: { halign: 'center', cellWidth: 12 },
                            1: { cellWidth: 70 },
                            2: { halign: 'center', cellWidth: 30 },
                            3: { halign: 'center', cellWidth: 30 },
                            4: { halign: 'center', cellWidth: 38 }
                        },
                        margin: { left: 10, right: 10 },
                        theme: 'grid'
                    });

                    // ====== PIE DE PÁGINA ======
                    const pageCount = doc.internal.getNumberOfPages();
                    const ahora = new Date();
                    const horaGeneracion = ahora.toLocaleTimeString('es-PE', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    for (let i = 1; i <= pageCount; i++) {
                        doc.setPage(i);

                        doc.setDrawColor(189, 195, 199);
                        doc.setLineWidth(0.5);
                        doc.line(10, 282, 200, 282);

                        doc.setFontSize(7);
                        doc.setTextColor(127, 140, 141);
                        doc.setFont(undefined, 'normal');
                        doc.text('Sistema de Gestión de Recursos Humanos', 10, 287);
                        doc.text(`Generado el ${fechaActual} a las ${horaGeneracion}`, 105, 287, { align: 'center' });
                        doc.setFont(undefined, 'bold');
                        doc.text(`Página ${i} de ${pageCount}`, 200, 287, { align: 'right' });
                    }

                    // ====== ABRIR PDF ======
                    const pdfBlob = doc.output('blob');
                    const pdfUrl = URL.createObjectURL(pdfBlob);
                    window.open(pdfUrl, '_blank');

                    Swal.close();
                };
            })
            .catch(() => {
                Swal.fire('Error', 'No se pudo generar el reporte', 'error');
            });
    });
document.getElementById('btnGenerarPdfFoliosPendientesSucursal')
    .addEventListener('click', () => {

        const sucursal = document.getElementById('sucursal').value;

        if (!sucursal) {
            Swal.fire({
                icon: 'warning',
                title: 'Sucursal requerida',
                text: 'Seleccione una sucursal para generar el reporte'
            });
            return;
        }

        Swal.fire({
            title: 'Generando reporte',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        axios.get(`${VITE_URL_APP}/api/reporte/folios-pendientes-sucursal`, {
            params: { sucursal }
        })
        .then(response => {

            const data = response.data;

            if (!data.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin resultados',
                    text: 'No existen folios pendientes para la sucursal seleccionada'
                });
                return;
            }

            const doc = new jsPDF();

            const fechaActual = new Date().toLocaleDateString('es-PE', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            const pageWidth   = doc.internal.pageSize.getWidth();
            const bannerHeight = 50;

            const img = new Image();
            img.src = `${VITE_URL_APP}/images/banners/banner_folios_pendientes.jpeg`;

            img.onload = function () {

                // ====== BANNER primera pagina ======
                doc.addImage(img, 'JPEG', 0, 0, pageWidth, bannerHeight);
                let yPosition = bannerHeight + 10;

                data.forEach((grupoSucursal, indexSucursal) => {

                    // ====== TITULO SUCURSAL ======
                    if (yPosition > 260) {
                        doc.addPage();
                        yPosition = 20;
                    }

                    doc.setFontSize(11);
                    doc.setFont(undefined, 'bold');
                    doc.setFillColor(6, 10, 81);
                    doc.rect(10, yPosition, 190, 7, 'F');
                    doc.setTextColor(255, 255, 255);
                    doc.text(`Sucursal: ${grupoSucursal.sucursal}`, 12, yPosition + 5);
                    doc.setTextColor(0, 0, 0);
                    yPosition += 12;

                    // ====== TABLA POR PERSONA ======
                    grupoSucursal.personal.forEach(persona => {

                        if (yPosition > 250) {
                            doc.addPage();
                            yPosition = 20;
                        }

                        autoTable(doc, {
                            startY: yPosition,
                            head: [
                                // Fila 1: nombre de la persona
                                [
                                    {
                                        content: `Personal: ${persona.personal}`,
                                        colSpan: 3,
                                        styles: {
                                            fillColor:   [220, 225, 245],
                                            textColor:   [6, 10, 81],
                                            fontStyle:   'bold',
                                            fontSize:    9,
                                            cellPadding: { top: 4, bottom: 4, left: 6, right: 4 }
                                        }
                                    }
                                ],
                                // Fila 2: cabeceras de columnas
                                ['N°', 'Documento pendiente', 'Tipo de folio']
                            ],
                            body: persona.documentos.map((docu, idx) => [
                                idx + 1,
                                docu.documento,
                                docu.tipo_folio
                            ]),
                            styles: {
                                fontSize:    8,
                                cellPadding: 2,
                                lineColor:   [189, 195, 199],
                                lineWidth:   0.1,
                                overflow:    'linebreak'
                            },
                            headStyles: {
                                fillColor:   [6, 10, 81],
                                textColor:   [255, 255, 255],
                                fontStyle:   'bold',
                                halign:      'center',
                                fontSize:    9,
                                cellPadding: 3
                            },
                            alternateRowStyles: {
                                fillColor: [245, 246, 250]
                            },
                            columnStyles: {
                                0: { halign: 'center', cellWidth: 12  },
                                1: { cellWidth: 130 },
                                2: { halign: 'center', cellWidth: 48  }
                            },
                            margin: { left: 10, right: 10 },
                            theme: 'grid'
                        });

                        yPosition = doc.lastAutoTable.finalY + 6;
                    });

                    // Nueva pagina entre sucursales (excepto la ultima)
                    if (indexSucursal < data.length - 1) {
                        doc.addPage();
                        yPosition = 20;
                    }
                });

                // ====== PIE DE PAGINA ======
                const pageCount = doc.internal.getNumberOfPages();
                const ahora = new Date();
                const horaGeneracion = ahora.toLocaleTimeString('es-PE', {
                    hour: '2-digit',
                    minute: '2-digit'
                });

                for (let i = 1; i <= pageCount; i++) {
                    doc.setPage(i);

                    doc.setDrawColor(189, 195, 199);
                    doc.setLineWidth(0.5);
                    doc.line(10, 282, 200, 282);

                    doc.setFontSize(7);
                    doc.setTextColor(127, 140, 141);
                    doc.setFont(undefined, 'normal');
                    doc.text('Sistema de Gestion de Recursos Humanos', 10, 287);
                    doc.text(`Generado el ${fechaActual} a las ${horaGeneracion}`, 105, 287, { align: 'center' });
                    doc.setFont(undefined, 'bold');
                    doc.text(`Pagina ${i} de ${pageCount}`, 200, 287, { align: 'right' });
                }

                // ====== ABRIR PDF ======
                const pdfBlob = doc.output('blob');
                const pdfUrl  = URL.createObjectURL(pdfBlob);
                window.open(pdfUrl, '_blank');

                Swal.close();
            };
        })
        .catch(() => {
            Swal.fire('Error', 'No se pudo generar el reporte', 'error');
        });
    });
// Botón para generar PDF de folios por vencer
let tipoFiltro = 'sucursal';
let filtroValue = '';

document.querySelectorAll('input[name="tipoFiltro"]').forEach(radio => {
    radio.addEventListener('change', function() {
        tipoFiltro = this.value;
        filtroValue = '';
    });
});

document.getElementById('filtroSucursalSelect').addEventListener('change', function() {
    filtroValue = this.value;
});

document.getElementById('filtroClienteSelect').addEventListener('change', function() {
    filtroValue = this.value;
});

document.getElementById('btnGenerarPdfFoliosPorVencer').addEventListener('click', () => {

    if (!tipoFiltro || !filtroValue) {
        Swal.fire('Atención', 'Selecciona un filtro antes de generar el PDF', 'warning');
        return;
    }

    Swal.fire({
        title: 'Generando reporte',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    // ====== ENDPOINT SEGÚN FILTRO ======
    const endpoint = tipoFiltro === 'cliente'
        ? `${VITE_URL_APP}/reporte/folios-por-vencer-cliente`
        : `${VITE_URL_APP}/reporte/folios-por-vencer`;

    const params = tipoFiltro === 'cliente'
        ? { cliente: filtroValue }
        : { sucursal: filtroValue };

    axios.get(endpoint, { params })
    .then(response => {
        let datos = response.data;

        if (datos.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin resultados',
                text: 'No existen folios con los filtros seleccionados'
            });
            return;
        }

        // ====== AGRUPAR POR SUCURSAL O CLIENTE > PERSONA ======
        const groupKey = tipoFiltro === 'cliente' ? 'cliente' : 'sucursal';
        const porGrupo = {};
        datos.forEach(d => {
            const grupo = d[groupKey];
            if (!porGrupo[grupo]) porGrupo[grupo] = {};
            if (!porGrupo[grupo][d.codPersonal]) {
                porGrupo[grupo][d.codPersonal] = {
                    personal: d.personal,
                    documentos: []
                };
            }
            porGrupo[grupo][d.codPersonal].documentos.push({
                documento: d.documento,
                tipo: d.tipo_folio,
                dias: parseInt(d.dias_restantes),
                fecha_caducidad: d.fecha_caducidad
            });
        });

        // ====== PDF HORIZONTAL ======
        const doc = new jsPDF({ orientation: 'landscape' });
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        const bannerHeight = 40;

        const fechaActual = new Date().toLocaleDateString('es-PE', {
            year: 'numeric', month: 'long', day: 'numeric'
        });

        const img = new Image();
        img.src = `${VITE_URL_APP}/images/banners/BANNER REPORTES DE FOLIOS -02.jpeg`;
        

        img.onload = function () {

            doc.addImage(img, 'JPEG', 0, 0, pageWidth, bannerHeight);
            let yPosition = bannerHeight + 10;

            // ====== POR GRUPO ======
            Object.entries(porGrupo).forEach(([grupo, personas]) => {

                if (yPosition > pageHeight - 40) {
                    doc.addPage();
                    yPosition = 20;
                }

                // Título grupo
                const labelGrupo = tipoFiltro === 'cliente' ? 'Cliente' : 'Sucursal';
                doc.setFontSize(10);
                doc.setFont(undefined, 'bold');
                doc.setFillColor(220, 225, 245);
                doc.rect(10, yPosition, pageWidth - 20, 7, 'F');
                doc.setTextColor(6, 10, 81);
                doc.text(`${labelGrupo}: ${grupo}`, 12, yPosition + 5);
                doc.setTextColor(0, 0, 0);
                yPosition += 10;

                // ====== MINI-TABLA POR PERSONA ======
                Object.values(personas).forEach(persona => {

                    if (yPosition > pageHeight - 40) {
                        doc.addPage();
                        yPosition = 20;
                    }

                    const documentos = persona.documentos;

                    const cabeceras = [
                        [{
                            content: `Personal: ${persona.personal}`,
                            colSpan: documentos.length + 1,
                            styles: { fillColor: [6, 10, 81], textColor: [255, 255, 255], fontStyle: 'bold', fontSize: 8 }
                        }],
                        [
                            { content: 'Documento por vencer', styles: { fillColor: [6, 10, 81], textColor: [255, 255, 255], fontStyle: 'bold', fontSize: 7 } },
                            ...documentos.map(d => ({
                                content: d.documento,
                                styles: { fillColor: [6, 10, 81], textColor: [255, 255, 255], fontStyle: 'bold', fontSize: 7, halign: 'center' }
                            }))
                        ]
                    ];

                    const filaTipo = [
                        { content: 'Tipo', styles: { fillColor: [240, 240, 240], textColor: [100, 100, 100], fontSize: 7, fontStyle: 'bold' } },
                        ...documentos.map(d => ({
                            content: d.tipo,
                            styles: {
                                halign: 'center',
                                fontStyle: d.tipo === 'PRINCIPAL' ? 'bold' : 'normal',
                                textColor: d.tipo === 'PRINCIPAL' ? [6, 10, 81] : [100, 100, 100],
                                fillColor: d.tipo === 'PRINCIPAL' ? [220, 225, 245] : [245, 245, 245],
                                fontSize: 7
                            }
                        }))
                    ];

                    const filaFecha = [
                        { content: 'Vence', styles: { fillColor: [240, 240, 240], textColor: [100, 100, 100], fontSize: 7, fontStyle: 'bold' } },
                        ...documentos.map(d => {
                            const fecha = d.fecha_caducidad ? d.fecha_caducidad.split(' ')[0] : '-';
                            return {
                                content: fecha,
                                styles: { halign: 'center', fontSize: 6, textColor: [80, 80, 80] }
                            };
                        })
                    ];

                    const filaDias = [
                        { content: 'Días', styles: { fillColor: [240, 240, 240], textColor: [100, 100, 100], fontSize: 7, fontStyle: 'bold' } },
                        ...documentos.map(d => {
                            const dias = d.dias;
                            const color = dias <= 5 ? [220, 50, 50] : dias <= 15 ? [200, 120, 0] : [0, 150, 0];
                            return {
                                content: `${dias} días`,
                                styles: { halign: 'center', fontSize: 6, textColor: color, fontStyle: 'bold' }
                            };
                        })
                    ];

                    autoTable(doc, {
                        startY: yPosition,
                        head: cabeceras,
                        body: [filaTipo, filaFecha, filaDias],
                        styles: {
                            fontSize: 7,
                            cellPadding: 2,
                            lineColor: [189, 195, 199],
                            lineWidth: 0.1,
                            overflow: 'linebreak'
                        },
                        columnStyles: {
                            0: { cellWidth: 40 }
                        },
                        margin: { left: 10, right: 10 },
                        theme: 'grid'
                    });

                    yPosition = doc.lastAutoTable.finalY + 4;
                });

                yPosition += 4;
            });

            // ====== PIE DE PÁGINA ======
            const pageCount = doc.internal.getNumberOfPages();
            const ahora = new Date();
            const horaGeneracion = ahora.toLocaleTimeString('es-PE', {
                hour: '2-digit', minute: '2-digit'
            });

            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setDrawColor(189, 195, 199);
                doc.setLineWidth(0.5);
                doc.line(10, pageHeight - 10, pageWidth - 10, pageHeight - 10);
                doc.setFontSize(7);
                doc.setTextColor(127, 140, 141);
                doc.setFont(undefined, 'normal');
                doc.text('Sistema de Gestión de Recursos Humanos', 10, pageHeight - 5);
                doc.text(`Generado el ${fechaActual} a las ${horaGeneracion}`, pageWidth / 2, pageHeight - 5, { align: 'center' });
                doc.setFont(undefined, 'bold');
                doc.text(`Página ${i} de ${pageCount}`, pageWidth - 10, pageHeight - 5, { align: 'right' });
            }

            // ====== DESCARGAR PDF ======
            const pdfBlob = doc.output('blob');
            const pdfUrl = URL.createObjectURL(pdfBlob);
            const link = document.createElement('a');
            link.href = pdfUrl;
            link.download = 'folios_por_vencer.pdf';
            link.click();

            Swal.close();
        };
    })
    .catch(error => {
        console.error(error);
        Swal.fire('Error', 'No se pudo generar el reporte', 'error');
    });
});