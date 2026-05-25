import { TabulatorFull as Tabulator } from "tabulator-tables";
import "tabulator-tables/dist/css/tabulator_simple.min.css";

import axios from "axios";
import Alpine from "alpinejs";

axios.defaults.withCredentials = true;
axios.defaults.headers.common["X-CSRF-TOKEN"] = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

document.addEventListener("DOMContentLoaded", () => {
    Alpine.start?.();

    const state = {
        memoSearchTerm: "",
        memoCliente: "",
        memoNivel: null,
        memoSucursal: "",
        memoTipo: "",
    };

    const elements = {
        searchCurso: document.getElementById("buscarCursoSeguimiento"),
        searchPersonal: document.getElementById("buscarPersonalSeguimiento"),
        searchMemos: document.getElementById("buscarMemosEnviados"),
        filtroClienteMemos: document.getElementById("filtroClienteMemos"),
        btnBuscarMemos: document.getElementById("btnBuscarMemosEnviados"),
        btnLimpiarMemos: document.getElementById("btnLimpiarFiltroMemos"),
        filtroSucursalPersonal: document.getElementById(
            "filtroSucursalPersonal",
        ),
        filtroTipoPersonal: document.getElementById("filtroTipoPersonal"),
    };

    const esLocale = {
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
    };

    function getInitials(fullName = "") {
        return fullName
            .split(" ")
            .filter(Boolean)
            .slice(0, 2)
            .map((word) => word[0] || "")
            .join("")
            .toUpperCase();
    }

    function formatDateTime(value, locale = "es-PE") {
        if (!value) return { fecha: "—", hora: "—" };

        const d = new Date(value);
        if (Number.isNaN(d.getTime())) return { fecha: "—", hora: "—" };

        return {
            fecha: d.toLocaleDateString(locale, {
                day: "2-digit",
                month: "2-digit",
                year: "numeric",
            }),
            hora: d.toLocaleTimeString(locale, {
                hour: "2-digit",
                minute: "2-digit",
                hour12: true,
            }),
        };
    }

    function setRowHoverEffect(
        row,
        color = "rgba(99, 102, 241, 0.05)",
        accent = "#6366f1",
    ) {
        const el = row.getElement();
        el.style.cursor = "pointer";
        el.style.transition = "all .15s ease";

        el.addEventListener("mouseenter", () => {
            el.style.background = color;
            el.style.boxShadow = `inset 3px 0 0 ${accent}`;
        });

        el.addEventListener("mouseleave", () => {
            el.style.background = "";
            el.style.boxShadow = "";
        });
    }

    function abrirModalPersonal(data) {
        const modalEl = document.getElementById("modal-usuario");
        if (!modalEl) return;

        const alpineData = Alpine.$data(modalEl);
        if (alpineData?.mostrar) {
            alpineData.mostrar(data);
        }
    }

    function cargarClientesMemos(lista = []) {
        const select = elements.filtroClienteMemos;
        if (!select) return;

        const actual = select.value;
        const clientes = [
            ...new Set(lista.map((m) => m.CLIENTE).filter(Boolean)),
        ].sort();

        select.innerHTML =
            `<option value="">Todos los clientes</option>` +
            clientes.map((c) => `<option value="${c}">${c}</option>`).join("");

        select.value = actual;
    }

    function cargarSucursalesPersonal(lista = []) {
        const select = elements.filtroSucursalPersonal;
        if (!select) return;

        const actual = select.value;
        const sucursales = [
            ...new Set(lista.map((p) => p.sucursal).filter(Boolean)),
        ].sort();

        select.innerHTML =
            `<option value="">Todas las sucursales</option>` +
            sucursales
                .map((s) => `<option value="${s}">${s}</option>`)
                .join("");

        select.value = actual;
    }

    function aplicarFiltrosMemos() {
        const tbl = window.tabulatorMemos;
        if (!tbl) return;

        const term = state.memoSearchTerm.toLowerCase().trim();
        const cliente = state.memoCliente;
        const nivel = state.memoNivel;
        const sucursal = state.memoSucursal.toUpperCase();
        const tipo = state.memoTipo;

        tbl.setFilter((data) => {
            const nombre = (data.NOMBRE_COMPLETO || "").toLowerCase();
            const dni = String(data.NRO_DOCU_IDEN || "").toLowerCase();
            const cli = data.CLIENTE || "";
            const niv = Number(data.NUM_MEMO);

            const matchTexto =
                !term || nombre.includes(term) || dni.includes(term);

            const matchCliente = !cliente || cli === cliente;

            const matchNivel = !nivel || niv === nivel;

            const matchSucursal = !sucursal || (data.SUCURSAL || "").toUpperCase() === sucursal;

            const matchTipo = !tipo || (data.TIPO_TRABAJADOR || "").toUpperCase() === tipo.toUpperCase();

            return matchTexto && matchCliente && matchNivel && matchSucursal && matchTipo;
        });
    }

    function ejecutarBusquedaMemos() {
        state.memoSearchTerm = elements.searchMemos?.value || "";
        aplicarFiltrosMemos();
    }

    function aplicarFiltrosPersonal() {
        const tbl = window.tabulatorPersonal;
        if (!tbl) return;

        const sucursal = (
            elements.filtroSucursalPersonal?.value || ""
        ).toUpperCase();
        const tipo = elements.filtroTipoPersonal?.value || "";

        if (!sucursal && !tipo) {
            tbl.clearFilter();
            return;
        }

        tbl.setFilter((data) => {
            if (sucursal && (data.sucursal || "").toUpperCase() !== sucursal)
                return false;
            if (tipo && (data.cargo || "") !== tipo) return false;
            return true;
        });
    }

    function initTablaCursosSeguimiento() {
        const table = new Tabulator("#tblCursosSeguimiento", {
            ajaxURL: "/api/get-cursos-seguimiento",
            layout: "fitColumns",
            placeholder: "No se encontraron cursos",
            pagination: "local",
            paginationSize: 15,
            paginationSizeSelector: [15, 25, 50],
            paginationCounter: "rows",
            locale: "es-es",
            langs: { "es-es": esLocale },

            rowFormatter(row) {
                setRowHoverEffect(row);
            },

            columns: [
                {
                    title: "Código",
                    field: "codigo_curso",
                    width: 100,
                    hozAlign: "center",
                    headerSort: true,
                    formatter(cell) {
                        const val = cell.getValue();
                        return `
                            <span style="
                                display:inline-block;
                                padding:2px 8px;
                                border-radius:6px;
                                background:#eef2ff;
                                color:#4338ca;
                                font-size:11px;
                                font-weight:700;
                                letter-spacing:0.04em;
                            ">${val ?? "—"}</span>
                        `;
                    },
                },
                {
                    title: "Nombre de curso",
                    field: "nombre",
                    minWidth: 250,
                    headerSort: true,
                    formatter(cell) {
                        const val = cell.getValue();
                        return `<span style="font-weight:500; color:#111827;">${val ?? "—"}</span>`;
                    },
                },
                {
                    title: "Responsable",
                    field: "responsable",
                    minWidth: 200,
                    headerSort: true,
                    formatter(cell) {
                        const val = cell.getValue();
                        if (!val) return "—";

                        const initials = getInitials(val);

                        return `
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="
                                    width:28px;
                                    height:28px;
                                    border-radius:50%;
                                    background:#e0e7ff;
                                    color:#4338ca;
                                    font-size:10px;
                                    font-weight:700;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    flex-shrink:0;
                                ">${initials}</div>
                                <span style="font-size:13px; color:#374151;">${val}</span>
                            </div>
                        `;
                    },
                },
                {
                    title: "Matriculados",
                    field: "total_matriculados",
                    width: 130,
                    hozAlign: "center",
                    headerSort: true,
                    formatter(cell) {
                        const val = cell.getValue() ?? 0;
                        return `
                            <div style="display:flex; align-items:center; justify-content:center; gap:5px;">
                                <i class="ti ti-users" style="font-size:13px; color:#6366f1;"></i>
                                <span style="font-weight:600; color:#111827;">${val}</span>
                            </div>
                        `;
                    },
                },
                {
                    title: "Creación",
                    field: "fecha_creacion",
                    width: 150,
                    hozAlign: "center",
                    headerSort: true,
                    formatter(cell) {
                        const { fecha, hora } = formatDateTime(cell.getValue());
                        if (fecha === "—") return "—";

                        return `
                            <div style="display:flex; flex-direction:column; align-items:center; line-height:1.4;">
                                <span style="font-size:12px; font-weight:600; color:#374151;">${fecha}</span>
                                <span style="font-size:11px; color:#9ca3af;">${hora}</span>
                            </div>
                        `;
                    },
                },
            ],
        });

        window.tabulatorCursos = table;

        table.on("rowClick", (e, row) => {
            const el = row.getElement();
            el.style.background = "rgba(99, 102, 241, 0.1)";
            setTimeout(() => (el.style.background = ""), 300);

            const data = row.getData();
            const modalEl = document.getElementById("modal-detalle-curso");
            const alpineComponent = modalEl?._x_dataStack?.[0];

            if (alpineComponent?.mostrar) {
                alpineComponent.mostrar(
                    data,
                    (codigoMoodle) =>
                        axios.get(
                            "/api/get-usuarios-curso-moodle/" + codigoMoodle,
                        ),
                    (payload) => axios.post("/api/mail/send", payload),
                );
            }
        });

        if (elements.searchCurso) {
            let debounceTimer;
            elements.searchCurso.addEventListener("input", function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const term = this.value.trim().toLowerCase();

                    if (term) {
                        table.setFilter((data) => {
                            const nombre = (data.nombre || "").toLowerCase();
                            const codigo = String(
                                data.codigo_curso || "",
                            ).toLowerCase();
                            return (
                                nombre.includes(term) || codigo.includes(term)
                            );
                        });
                    } else {
                        table.clearFilter();
                    }
                }, 300);
            });
        }
    }

    function initTablaMemosEnviados() {
        const table = new Tabulator("#tblMemosEnviados", {
            ajaxURL: "/api/obtener-memos-enviados",
            ajaxResponse(url, params, response) {
                const data = response.data || [];

                const alpineEl = document.querySelector(
                    '[x-data="memosStats()"]',
                );
                if (alpineEl?._x_dataStack?.[0]?.actualizar) {
                    alpineEl._x_dataStack[0].actualizar(data);
                }

                cargarClientesMemos(data);
                return data;
            },

            layout: "fitData",
            placeholder: "No se encontraron MEMOs enviados",
            pagination: "local",
            paginationSize: 15,
            paginationSizeSelector: [15, 25, 50],
            paginationCounter: "rows",
            columnDefaults: {
                headerHozAlign: "center",
                vertAlign: "middle",
            },
            rowHeight: 58,
            locale: "es-es",
            langs: { "es-es": esLocale },

            rowFormatter(row) {
                setRowHoverEffect(row, "rgba(59,130,246,.04)", "#3b82f6");
            },

            columns: [
                {
                    title: "#",
                    formatter: "rownum",
                    width: 60,
                    hozAlign: "center",
                    headerSort: false,
                    cssClass: "font-semibold text-default-500",
                },
                {
                    title: "Personal",
                    field: "NOMBRE_COMPLETO",
                    minWidth: 300,
                    widthGrow: 3,
                    formatter(cell) {
                        const row = cell.getRow().getData();
                        const nombre = row.NOMBRE_COMPLETO || "Sin nombre";
                        const tipo = row.TIPO_TRABAJADOR || "Sin tipo";

                        return `
                            <div style="display:flex; flex-direction:column; overflow:hidden;">
                                <span style="
                                    font-size:13px;
                                    font-weight:600;
                                    color:#111827;
                                    white-space:nowrap;
                                    overflow:hidden;
                                    text-overflow:ellipsis;
                                ">${nombre}</span>
                                <span style="font-size:11px; color:#9ca3af;">${tipo}</span>
                            </div>
                        `;
                    },
                },
                {
                    title: "DNI",
                    field: "NRO_DOCU_IDEN",
                    width: 120,
                    hozAlign: "center",
                    formatter(cell) {
                        return `
                            <span style="
                                font-family:monospace;
                                font-size:12px;
                                font-weight:700;
                                color:#374151;
                                letter-spacing:.03em;
                            ">${cell.getValue() || "—"}</span>
                        `;
                    },
                },
                {
                    title: "Sucursal",
                    field: "SUCURSAL",
                    width: 140,
                    hozAlign: "center",
                    formatter(cell) {
                        const val = cell.getValue() || "—";
                        return `
                            <span style="
                                display:inline-flex;
                                align-items:center;
                                justify-content:center;
                                padding:4px 10px;
                                border-radius:999px;
                                background:#f3f4f6;
                                color:#374151;
                                font-size:11px;
                                font-weight:600;
                            ">${val}</span>
                        `;
                    },
                },
                {
                    title: "Nivel",
                    field: "NUM_MEMO",
                    width: 150,
                    hozAlign: "center",
                    formatter(cell) {
                        const val = Number(cell.getValue());

                        let bg = "#dbeafe";
                        let color = "#1d4ed8";
                        let icon = "ti-info-circle";
                        let text = "NIVEL UNO";

                        if (val === 2) {
                            bg = "#ffedd5";
                            color = "#c2410c";
                            icon = "ti-alert-triangle";
                            text = "NIVEL DOS";
                        }

                        if (val === 3) {
                            bg = "#fee2e2";
                            color = "#b91c1c";
                            icon = "ti-bell-ringing";
                            text = "NIVEL TRES";
                        }

                        return `
                            <span style="
                                display:inline-flex;
                                align-items:center;
                                gap:6px;
                                padding:5px 12px;
                                border-radius:999px;
                                background:${bg};
                                color:${color};
                                font-size:11px;
                                font-weight:700;
                            ">
                                <i class="ti ${icon}"></i>
                                ${text}
                            </span>
                        `;
                    },
                },
                {
                    title: "Cliente",
                    field: "CLIENTE",
                    minWidth: 180,
                    widthGrow: 1,
                    hozAlign: "center",
                    headerSort: true,
                    sorter: "string",
                    formatter(cell) {
                        const val = cell.getValue();
                        if (!val) {
                            return `
                                <span style="font-size:11px; color:#9ca3af; font-style:italic;">
                                    Sin cliente
                                </span>
                            `;
                        }

                        return `
                            <span style="
                                font-size:12px;
                                color:#374151;
                                font-weight:500;
                            ">${val}</span>
                        `;
                    },
                },
                {
                    title: "Total",
                    field: "TOTAL_MEMOS",
                    width: 90,
                    hozAlign: "center",
                    formatter(cell) {
                        return `
                            <div style="display:flex; justify-content:center;">
                                <span style="
                                    min-width:32px;
                                    height:32px;
                                    padding:0 10px;
                                    border-radius:10px;
                                    background:#eef2ff;
                                    color:#4338ca;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    font-size:12px;
                                    font-weight:700;
                                ">${cell.getValue() || 0}</span>
                            </div>
                        `;
                    },
                },
                {
                    title: "Último envío",
                    field: "ULTIMO_ENVIO",
                    width: 155,
                    hozAlign: "center",
                    formatter(cell) {
                        const { fecha, hora } = formatDateTime(cell.getValue());
                        if (fecha === "—") return "—";

                        return `
                            <div style="display:flex; flex-direction:column; align-items:center; line-height:1.35;">
                                <span style="font-size:12px; font-weight:600; color:#374151;">${fecha}</span>
                                <span style="font-size:10px; color:#9ca3af;">${hora}</span>
                            </div>
                        `;
                    },
                },
            ],
        });

        window.tabulatorMemos = table;

        table.on("rowClick", (e, row) => {
            const data = row.getData();
            const nroDoc = data.NRO_DOCU_IDEN;
            const nombre = data.NOMBRE_COMPLETO;
            const nivel = Number(data.NUM_MEMO) || 1;

            const modalEl = document.getElementById("modal-memos-persona");
            const alpine = modalEl?._x_dataStack?.[0];
            if (alpine?.mostrar) {
                alpine.mostrar(nroDoc, nivel, nombre);
            }
        });

        if (elements.btnBuscarMemos) {
            elements.btnBuscarMemos.addEventListener(
                "click",
                ejecutarBusquedaMemos,
            );
        }

        if (elements.searchMemos) {
            elements.searchMemos.addEventListener("keydown", (e) => {
                if (e.key === "Enter") ejecutarBusquedaMemos();
            });
        }

        if (elements.filtroClienteMemos) {
            elements.filtroClienteMemos?.addEventListener("change", () => {
                state.memoCliente = elements.filtroClienteMemos.value;
                aplicarFiltrosMemos();
            });
        }

        if (elements.btnLimpiarMemos) {
            elements.btnLimpiarMemos?.addEventListener("click", () => {
                state.memoSearchTerm = "";
                state.memoCliente = "";
                state.memoNivel = null;
                state.memoSucursal = "";
                state.memoTipo = "";

                if (elements.searchMemos) elements.searchMemos.value = "";
                if (elements.filtroClienteMemos)
                    elements.filtroClienteMemos.value = "";
                if (elements.filtroSucursalPersonal)
                    elements.filtroSucursalPersonal.value = "";
                if (elements.filtroTipoPersonal)
                    elements.filtroTipoPersonal.value = "";

                const alpineEl = document.querySelector(
                    '[x-data="memosStats()"]',
                );
                alpineEl?._x_dataStack?.[0] &&
                    (alpineEl._x_dataStack[0].nivelActivo = null);

                aplicarFiltrosPersonal();
                aplicarFiltrosMemos();
            });
        }
    }

    function initTablaPersonalSeguimiento() {
        const table = new Tabulator("#tblPersonalSeguimiento", {
            data: [],
            layout: "fitColumns",
            placeholder: "No se encontraron empleados",
            pagination: "local",
            paginationSize: 15,
            paginationSizeSelector: [15, 25, 50],
            paginationCounter: "rows",
            locale: "es-es",
            langs: { "es-es": esLocale },

            columns: [
                {
                    title: "DNI",
                    field: "dni",
                    width: 110,
                    hozAlign: "center",
                    headerSort: false,
                },
                {
                    title: "Nombre Completo",
                    field: "nombre_completo",
                    minWidth: 250,
                },
                {
                    title: "Tipo de trabajador",
                    field: "cargo",
                    width: 150,
                    headerSort: true,
                    formatter(cell) {
                        return `<span class="text-xs text-gray-600">${cell.getValue() || ""}</span>`;
                    },
                },
                {
                    title: "Sucursal",
                    field: "sucursal",
                    width: 130,
                    headerSort: false,
                    formatter(cell) {
                        return `<span class="text-xs text-blue-600/80">${cell.getValue() || ""}</span>`;
                    },
                },
                {
                    title: "Acción",
                    headerSort: false,
                    width: 80,
                    hozAlign: "center",
                    formatter() {
                        return `
                            <button class="px-2 py-1 rounded bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors text-xs font-medium">
                                <i class="ti ti-eye"></i> Ver
                            </button>
                        `;
                    },
                    cellClick(e, cell) {
                        e.stopPropagation();
                        abrirModalPersonal(cell.getRow().getData());
                    },
                },
            ],

            rowClick(e, row) {
                abrirModalPersonal(row.getData());
            },
        });

        window.tabulatorPersonal = table;

        const aplicarFiltrosCombinados = () => {
            state.memoSucursal = elements.filtroSucursalPersonal?.value || "";
            state.memoTipo = elements.filtroTipoPersonal?.value || "";
            aplicarFiltrosPersonal();
            aplicarFiltrosMemos();
        };

        if (elements.filtroSucursalPersonal) {
            elements.filtroSucursalPersonal.addEventListener(
                "change",
                aplicarFiltrosCombinados,
            );
        }

        if (elements.filtroTipoPersonal) {
            elements.filtroTipoPersonal.addEventListener(
                "change",
                aplicarFiltrosCombinados,
            );
        }
    }

    function buscarPersonalSeguimiento(termino) {
        axios
            .get(`${VITE_URL_APP}/api/buscar-personal-capacitacion`, {
                params: { q: termino, limite: 100 },
            })
            .then((res) => {
                if (res.data.success && window.tabulatorPersonal) {
                    const personal = (res.data.personal || []).filter(
                        (p) => (p.total_capacitaciones || 0) > 0,
                    );

                    window.tabulatorPersonal.setData(personal);
                    cargarSucursalesPersonal(personal);
                    aplicarFiltrosPersonal();
                }
            })
            .catch((err) => console.error("Error al buscar personal:", err));
    }

    if (elements.searchPersonal) {
        let debounceTimer;
        elements.searchPersonal.addEventListener("input", function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                buscarPersonalSeguimiento(this.value.trim());
            }, 400);
        });
    }

    window.__memoState = state;
    window.__aplicarFiltrosMemos = aplicarFiltrosMemos;

    initTablaCursosSeguimiento();
    initTablaMemosEnviados();
    initTablaPersonalSeguimiento();

    buscarPersonalSeguimiento("");
});
