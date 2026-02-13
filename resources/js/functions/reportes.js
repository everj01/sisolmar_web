import axios from 'axios';
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';
import Swal from 'sweetalert2';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';


document.addEventListener('DOMContentLoaded', () => {

    const filtros = {
        foliosVigentes: document.getElementById('filtrosFoliosVigentes'),
        foliosPendientesSucursal: document.getElementById('filtrosFoliosPendientesSucursal'),
        // luego agregas más:
        // foliosPendientes: ...
        // legajosClienteCargo: ...
    };

    function ocultarTodosLosFiltros() {
        Object.values(filtros).forEach(div => {
            if (div) div.classList.add('hidden');
        });
    }

    document.getElementById('btnReporteFoliosVigentes')
        .addEventListener('click', () => {
            ocultarTodosLosFiltros();
            filtros.foliosVigentes.classList.remove('hidden');
        });

    document.getElementById('btnReporteFoliosPendientesSucursal')
        .addEventListener('click', () => {
            ocultarTodosLosFiltros();
            filtros.foliosPendientesSucursal.classList.remove('hidden');
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

            // ================= PDF =================
            const doc = new jsPDF();

            const fechaActual = new Date().toLocaleDateString('es-PE', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            const pageWidth = doc.internal.pageSize.getWidth();
            const bannerHeight = 50; // Puedes bajarlo a 30 si lo ves muy grande

            // Cargar imagen
            const img = new Image();
            img.src = `${VITE_URL_APP}/images/banners/banner_folios_pendientes.jpeg`;

            img.onload = function () {

                // ====== BANNER SOLO EN PRIMERA PÁGINA ======
                doc.addImage(img, 'JPEG', 0, 0, pageWidth, bannerHeight);

                // Primera página empieza debajo del banner
                let yPosition = bannerHeight + 10;

                // ================= CONTENIDO =================
                data.forEach((grupoSucursal, indexSucursal) => {

                    // Control de salto de página
                    if (yPosition > 260) {
                        doc.addPage();
                        yPosition = 20; // ← YA NO bannerHeight
                    }

                    // ===== TÍTULO SUCURSAL =====
                    doc.setFontSize(14);
                    doc.setFont(undefined, 'bold');
                    doc.setTextColor(44, 62, 80);
                    doc.text(`Sucursal: ${grupoSucursal.sucursal}`, 10, yPosition);
                    yPosition += 6;

                    grupoSucursal.personal.forEach(persona => {

                        if (yPosition > 260) {
                            doc.addPage();
                            yPosition = 20; // ← corregido
                        }

                        // ===== NOMBRE PERSONAL =====
                        doc.setFontSize(11);
                        doc.setFont(undefined, 'bold');
                        doc.setTextColor(0, 0, 0);
                        doc.text(`Personal: ${persona.personal}`, 12, yPosition);
                        yPosition += 4;

                        autoTable(doc, {
                            startY: yPosition,
                            head: [['N°', 'Documento', 'Tipo de folio']],
                            body: persona.documentos.map((docu, i) => [
                                i + 1,
                                docu.documento,
                                docu.tipo_folio
                            ]),
                            styles: {
                                fontSize: 8,
                                cellPadding: 2
                            },
                            headStyles: {
                                fillColor: [6, 10, 81],
                                textColor: [255, 255, 255],
                                fontStyle: 'bold',
                                halign: 'center'
                            },
                            columnStyles: {
                                0: { halign: 'center', cellWidth: 12 },
                                1: { cellWidth: 120 },
                                2: { halign: 'center', cellWidth: 40 }
                            },
                            margin: { left: 12, right: 10 },
                            theme: 'grid'
                        });

                        yPosition = doc.lastAutoTable.finalY + 6;

                        if (yPosition > 260) {
                            doc.addPage();
                            yPosition = 20; // ← corregido
                        }
                    });

                    if (indexSucursal < data.length - 1) {
                        doc.addPage();
                        yPosition = 20; // ← corregido
                    }
                });

                // ================= PIE DE PÁGINA =================
                const pageCount = doc.internal.getNumberOfPages();
                const ahora = new Date();
                const horaGeneracion = ahora.toLocaleTimeString('es-PE', {
                    hour: '2-digit',
                    minute: '2-digit'
                });

                for (let i = 1; i <= pageCount; i++) {
                    doc.setPage(i);

                    doc.setFontSize(7);
                    doc.setTextColor(120);
                    doc.text(`Generado el ${fechaActual} a las ${horaGeneracion}`, 105, 287, { align: 'center' });
                    doc.text(`Página ${i} de ${pageCount}`, 200, 287, { align: 'right' });
                }

                // ================= ABRIR PDF =================
                const pdfBlob = doc.output('blob');
                const pdfUrl = URL.createObjectURL(pdfBlob);
                window.open(pdfUrl, '_blank');

                Swal.close();
            };


            // ================= PDF =================
            // const doc = new jsPDF();

            // const fechaActual = new Date().toLocaleDateString('es-PE', {
            //     year: 'numeric',
            //     month: 'long',
            //     day: 'numeric'
            // });

            // const pageWidth = doc.internal.pageSize.getWidth();
            // const pageHeight = doc.internal.pageSize.getHeight();
            // const bannerHeight = 50;

            // // Cargar imagen
            // const img = new Image();
            // img.src = `${VITE_URL_APP}/images/banners/banner_folios_pendientes.jpeg`;

            // img.onload = function () {

            //     const addBanner = () => {
            //         doc.addImage(img, 'JPEG', 0, 0, pageWidth, bannerHeight);
            //     };

            //     // Agregar banner en primera página
            //     addBanner();

            //     let yPosition = bannerHeight + 10;

            //     // ================= CONTENIDO =================
            //     data.forEach((grupoSucursal, indexSucursal) => {

            //         // Verificar espacio antes de escribir sucursal
            //         if (yPosition > 260) {
            //             doc.addPage();
            //             //addBanner();
            //             yPosition = bannerHeight + 10;
            //         }

            //         // Título sucursal
            //         doc.setFontSize(14);
            //         doc.setFont(undefined, 'bold');
            //         doc.setTextColor(44, 62, 80);
            //         doc.text(`Sucursal: ${grupoSucursal.sucursal}`, 10, yPosition);
            //         yPosition += 6;

            //         grupoSucursal.personal.forEach(persona => {

            //             if (yPosition > 260) {
            //                 doc.addPage();
            //                 //addBanner();
            //                 yPosition = bannerHeight + 10;
            //             }

            //             // Nombre personal
            //             doc.setFontSize(11);
            //             doc.setFont(undefined, 'bold');
            //             doc.setTextColor(0, 0, 0);
            //             doc.text(`Personal: ${persona.personal}`, 12, yPosition);
            //             yPosition += 4;

            //             autoTable(doc, {
            //                 startY: yPosition,
            //                 head: [['N°', 'Documento', 'Tipo de folio']],
            //                 body: persona.documentos.map((docu, i) => [
            //                     i + 1,
            //                     docu.documento,
            //                     docu.tipo_folio
            //                 ]),
            //                 styles: {
            //                     fontSize: 8,
            //                     cellPadding: 2
            //                 },
            //                 headStyles: {
            //                     fillColor: [231, 76, 60],
            //                     textColor: [255, 255, 255],
            //                     fontStyle: 'bold',
            //                     halign: 'center'
            //                 },
            //                 columnStyles: {
            //                     0: { halign: 'center', cellWidth: 12 },
            //                     1: { cellWidth: 120 },
            //                     2: { halign: 'center', cellWidth: 40 }
            //                 },
            //                 margin: { left: 12, right: 10 },
            //                 theme: 'grid'
            //             });

            //             yPosition = doc.lastAutoTable.finalY + 6;

            //             if (yPosition > 260) {
            //                 doc.addPage();
            //                 //addBanner();
            //                 yPosition = bannerHeight + 10;
            //             }
            //         });

            //         if (indexSucursal < data.length - 1) {
            //             doc.addPage();
            //             //addBanner();
            //             yPosition = bannerHeight + 10;
            //         }
            //     });

            //     // ================= PIE DE PÁGINA =================
            //     const pageCount = doc.internal.getNumberOfPages();
            //     const ahora = new Date();
            //     const horaGeneracion = ahora.toLocaleTimeString('es-PE', {
            //         hour: '2-digit',
            //         minute: '2-digit'
            //     });

            //     for (let i = 1; i <= pageCount; i++) {
            //         doc.setPage(i);

            //         doc.setFontSize(7);
            //         doc.setTextColor(120);
            //         doc.text(`Generado el ${fechaActual} a las ${horaGeneracion}`, 105, 287, { align: 'center' });
            //         doc.text(`Página ${i} de ${pageCount}`, 200, 287, { align: 'right' });
            //     }

            //     // ================= ABRIR PDF =================
            //     const pdfBlob = doc.output('blob');
            //     const pdfUrl = URL.createObjectURL(pdfBlob);
            //     window.open(pdfUrl, '_blank');

            //     Swal.close();
            // };

        })
        .catch(() => {
            Swal.fire('Error', 'No se pudo generar el reporte', 'error');
        });
    });
