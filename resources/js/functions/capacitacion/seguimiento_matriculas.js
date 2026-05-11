import { TabulatorFull as Tabulator } from "tabulator-tables";
import "tabulator-tables/dist/css/tabulator_simple.min.css";

import axios from "axios";
import Alpine from "alpinejs";

axios.defaults.withCredentials = true;

axios.defaults.headers.common["X-CSRF-TOKEN"] = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

document.addEventListener("DOMContentLoaded", function () {
    const table = new Tabulator("#tblCursosSeguimiento", {
        ajaxURL: "/api/get-cursos-seguimiento",
        layout: "fitColumns",
        placeholder: "No se encontraron cursos",
        pagination: "local",
        paginationSize: 15,
        paginationSizeSelector: [15, 25, 50],
        paginationCounter: "rows",
        locale: true,
        langs: {
            "es-es": {
                pagination: {
                    page_size: "Filas por página",
                    first: "Primero",
                    last: "Último",
                    prev: "Anterior",
                    next: "Siguiente",

                    counter: {
                        showing: "Mostrando",
                        of: "de",
                        rows: "filas",
                        pages: "páginas",
                    },
                },

                data: {
                    loading: "Cargando...",
                    error: "Error al cargar los datos",
                },

                ajax: {
                    loading: "Cargando datos...",
                    error: "Error al cargar los datos",
                },

                headerFilters: {
                    default: "Filtrar...",
                },
            },
        },
        columns: [
            {
                title: "Código",
                field: "codigo_curso",
                width: 100,
                hozAlign: "center",
                headerSort: true,
            },

            {
                title: "Nombre de curso",
                field: "nombre",
                minWidth: 250,
                headerSort: true,
            },

            {
                title: "Responsable",
                field: "responsable",
                minWidth: 200,
                headerSort: true,
            },

            {
                title: "Total matriculados",
                field: "total_matriculados",
                width: 160,
                hozAlign: "center",
                headerSort: true,
            },

            {
                title: "Fecha de creación",
                field: "fecha_creacion",
                width: 130,
                hozAlign: "center",
                headerSort: true,
                formatter: function (cell) {
                    const val = cell.getValue();

                    if (!val) return "";

                    const d = new Date(val);

                    return (
                        d.toLocaleDateString("es-PE", {
                            day: "2-digit",
                            month: "2-digit",
                            year: "numeric",
                        }) +
                        " " +
                        d.toLocaleTimeString("es-PE", {
                            hour: "2-digit",
                            minute: "2-digit",
                            hour12: true,
                        })
                    );
                },
            },
        ],
    });

    table.on("rowClick", function (e, row) {
        const data = row.getData();
        const modalEl = document.getElementById("modal-detalle-curso");
        const alpineComponent = modalEl._x_dataStack?.[0];

        if (alpineComponent) {
            alpineComponent.mostrar(
                data,
                (codigoMoodle) =>
                    axios.get("/api/get-usuarios-curso-moodle/" + codigoMoodle),
                (payload) => axios.post("/api/mail/send", payload),
            );
        }
    });
});
