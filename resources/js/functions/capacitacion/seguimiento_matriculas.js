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
        locale: "es-es",
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
            },
        },

        // ── Estilos de fila ──────────────────────────────────────────
        rowFormatter: function (row) {
            const el = row.getElement();
            el.style.cursor = "pointer";
            el.style.transition = "background 0.15s ease";

            el.addEventListener("mouseenter", () => {
                el.style.background = "rgba(99, 102, 241, 0.05)";
                el.style.boxShadow = "inset 3px 0 0 #6366f1";
            });
            el.addEventListener("mouseleave", () => {
                el.style.background = "";
                el.style.boxShadow = "";
            });
        },

        columns: [
            // ── Código ───────────────────────────────────────────────
            {
                title: "Código",
                field: "codigo_curso",
                width: 100,
                hozAlign: "center",
                headerSort: true,
                formatter: function (cell) {
                    const val = cell.getValue();
                    return `<span style="
                        display: inline-block;
                        padding: 2px 8px;
                        border-radius: 6px;
                        background: #eef2ff;
                        color: #4338ca;
                        font-size: 11px;
                        font-weight: 700;
                        letter-spacing: 0.04em;
                    ">${val ?? "—"}</span>`;
                },
            },

            // ── Nombre del curso ─────────────────────────────────────
            {
                title: "Nombre de curso",
                field: "nombre",
                minWidth: 250,
                headerSort: true,
                formatter: function (cell) {
                    const val = cell.getValue();
                    return `<span style="font-weight: 500; color: #111827;">${val ?? "—"}</span>`;
                },
            },

            // ── Responsable ──────────────────────────────────────────
            {
                title: "Responsable",
                field: "responsable",
                minWidth: 200,
                headerSort: true,
                formatter: function (cell) {
                    const val = cell.getValue();
                    if (!val) return "—";

                    const initials = val
                        .split(" ")
                        .slice(0, 2)
                        .map((w) => w[0])
                        .join("")
                        .toUpperCase();

                    return `
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="
                                width: 28px; height: 28px;
                                border-radius: 50%;
                                background: #e0e7ff;
                                color: #4338ca;
                                font-size: 10px;
                                font-weight: 700;
                                display: flex; align-items: center; justify-content: center;
                                flex-shrink: 0;
                            ">${initials}</div>
                            <span style="font-size: 13px; color: #374151;">${val}</span>
                        </div>`;
                },
            },

            // ── Total matriculados ───────────────────────────────────
            {
                title: "Matriculados",
                field: "total_matriculados",
                width: 130,
                hozAlign: "center",
                headerSort: true,
                formatter: function (cell) {
                    const val = cell.getValue() ?? 0;
                    return `
                        <div style="display: flex; align-items: center; justify-content: center; gap: 5px;">
                            <i class="ti ti-users" style="font-size: 13px; color: #6366f1;"></i>
                            <span style="font-weight: 600; color: #111827;">${val}</span>
                        </div>`;
                },
            },

            // ── Fecha de creación ────────────────────────────────────
            {
                title: "Creación",
                field: "fecha_creacion",
                width: 150,
                hozAlign: "center",
                headerSort: true,
                formatter: function (cell) {
                    const val = cell.getValue();
                    if (!val) return "—";

                    const d = new Date(val);
                    const fecha = d.toLocaleDateString("es-PE", {
                        day: "2-digit",
                        month: "2-digit",
                        year: "numeric",
                    });
                    const hora = d.toLocaleTimeString("es-PE", {
                        hour: "2-digit",
                        minute: "2-digit",
                        hour12: true,
                    });

                    return `
                        <div style="display: flex; flex-direction: column; align-items: center; line-height: 1.4;">
                            <span style="font-size: 12px; font-weight: 600; color: #374151;">${fecha}</span>
                            <span style="font-size: 11px; color: #9ca3af;">${hora}</span>
                        </div>`;
                },
            },
        ],
    });

    window.tabulatorCursos = table;

    table.on("rowClick", function (e, row) {
        const el = row.getElement();
        el.style.background = "rgba(99, 102, 241, 0.1)";
        setTimeout(() => (el.style.background = ""), 300);

        const data = row.getData();
        const modalEl = document.getElementById("modal-detalle-curso");
        const alpineComponent = modalEl?._x_dataStack?.[0];

        if (alpineComponent) {
            alpineComponent.mostrar(
                data,
                (codigoMoodle) =>
                    axios.get("/api/get-usuarios-curso-moodle/" + codigoMoodle),
                (payload) => axios.post("/api/mail/send", payload),
            );
        }
    });

    const searchInput = document.getElementById("buscarCursoSeguimiento");
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener("input", function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const term = this.value.trim().toLowerCase();
                if (term.length > 0) {
                    table.setFilter(function (data) {
                        const nombre = (data.nombre || "").toLowerCase();
                        const codigo = (data.codigo_curso || "").toString().toLowerCase();
                        return nombre.includes(term) || codigo.includes(term);
                    });
                } else {
                    table.clearFilter();
                }
            }, 300);
        });
    }

    function inicializarTablaPersonalSeguimiento() {
        window.tabulatorPersonal = new Tabulator("#tblPersonalSeguimiento", {
            data: [],
            layout: "fitColumns",
            placeholder: "No se encontraron empleados",
            pagination: "local",
            paginationSize: 15,
            paginationSizeSelector: [15, 25, 50],
            paginationCounter: "rows",

            locale: "es-es",
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
                },
            },
            columns: [
                { title: "DNI", field: "dni", width: 110, hozAlign: "center", headerSort: false },
                { title: "Nombre Completo", field: "nombre_completo", minWidth: 250 },
                {
                    title: "Cargo", field: "cargo", width: 150, headerSort: false,
                    formatter: function (cell) {
                        return `<span class="text-xs text-gray-600">${cell.getValue() || ''}</span>`;
                    }
                },
                {
                    title: "Sucursal", field: "sucursal", width: 130, headerSort: false,
                    formatter: function (cell) {
                        return `<span class="text-xs text-blue-600/80">${cell.getValue() || ''}</span>`;
                    }
                },
                {
                    title: "Acción", headerSort: false, width: 80, hozAlign: "center",
                    formatter: function () {
                        return `<button class="px-2 py-1 rounded bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors text-xs font-medium">
                                    <i class="ti ti-eye"></i> Ver
                                </button>`;
                    },
                    cellClick: function (e, cell) {
                        e.stopPropagation();
                        abrirModalPersonal(cell.getRow().getData());
                    }
                },
            ],
            rowClick: function (e, row) {
                abrirModalPersonal(row.getData());
            },
        });
    }

    function abrirModalPersonal(data) {
        const modalEl = document.getElementById("modal-usuario");
        if (modalEl) {
            const alpineData = Alpine.$data(modalEl);
            if (alpineData && alpineData.mostrar) {
                alpineData.mostrar(data);
            }
        }
    }

    inicializarTablaPersonalSeguimiento();

    function buscarPersonalSeguimiento(termino) {
        axios.get(`${VITE_URL_APP}/api/buscar-personal-capacitacion`, {
            params: { q: termino, limite: 100 }
        })
            .then(res => {
                if (res.data.success && window.tabulatorPersonal) {
                    const personal = (res.data.personal || []).filter(p => (p.total_capacitaciones || 0) > 0);
                    window.tabulatorPersonal.setData(personal);
                    cargarSucursalesPersonal(personal);
                    aplicarFiltrosPersonal();
                }
            })
            .catch(err => console.error("Error al buscar personal:", err));
    }

    buscarPersonalSeguimiento("");

    const searchPersonal = document.getElementById("buscarPersonalSeguimiento");
    if (searchPersonal) {
        let debounceTimer;
        searchPersonal.addEventListener("input", function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                buscarPersonalSeguimiento(this.value.trim());
            }, 400);
        });
    }

    function aplicarFiltrosPersonal() {
        const sucursal = (document.getElementById("filtroSucursalPersonal")?.value || "").toUpperCase();
        const tipo = (document.getElementById("filtroTipoPersonal")?.value || "");
        const tbl = window.tabulatorPersonal;
        if (!tbl) return;
        if (sucursal || tipo) {
            tbl.setFilter(function (data) {
                if (sucursal && (data.sucursal || "").toUpperCase() !== sucursal) return false;
                if (tipo && (data.cargo || "") !== tipo) return false;
                return true;
            });
        } else {
            tbl.clearFilter();
        }
    }

    function cargarSucursalesPersonal(lista) {
        const select = document.getElementById("filtroSucursalPersonal");
        if (!select) return;
        const actual = select.value;
        const sucursales = [...new Set(lista.map(p => p.sucursal).filter(Boolean))].sort();
        select.innerHTML = '<option value="">Todas las sucursales</option>' +
            sucursales.map(s => `<option value="${s}">${s}</option>`).join('');
        select.value = actual;
    }

    const filtroSucursal = document.getElementById("filtroSucursalPersonal");
    const filtroTipo = document.getElementById("filtroTipoPersonal");
    if (filtroSucursal) filtroSucursal.addEventListener("change", aplicarFiltrosPersonal);
    if (filtroTipo) filtroTipo.addEventListener("change", aplicarFiltrosPersonal);
});
