import { TabulatorFull as Tabulator } from "tabulator-tables";
import "tabulator-tables/dist/css/tabulator_simple.min.css";

import axios from "axios";
import Alpine from "alpinejs";

axios.defaults.withCredentials = true;
axios.defaults.headers.common["X-CSRF-TOKEN"] = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

document.addEventListener("DOMContentLoaded", () => {
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

    function fillSelect(id, values) {
        const select = document.getElementById(id);
        if (!select) return;
        const placeholder = select.options[0];
        select.innerHTML = "";
        select.appendChild(placeholder);
        values.forEach((val) => {
            const opt = document.createElement("option");
            opt.value = val;
            opt.textContent = val;
            select.appendChild(opt);
        });
    }

    function populateFilters(data) {
        const unique = (field) =>
            [...new Set(data.map((d) => d[field]).filter(Boolean))].sort();

        fillSelect("filtroTipoCurso", unique("Tipo"));
        fillSelect("filtroAreaCurso", unique("Area"));
        fillSelect("filtroSistemaCurso", unique("Sistema"));
        fillSelect("filtroJefaturaCurso", unique("Responsable"));
    }

    function applyFilters(table) {
        const tipo = document.getElementById("filtroTipoCurso")?.value;
        const area = document.getElementById("filtroAreaCurso")?.value;
        const sistema = document.getElementById("filtroSistemaCurso")?.value;
        const jefatura = document.getElementById("filtroJefaturaCurso")?.value;
        const desde = document.getElementById("filtroFechaDesde")?.value;
        const hasta = document.getElementById("filtroFechaHasta")?.value;

        const filters = [];

        if (tipo) filters.push({ field: "Tipo", type: "=", value: tipo });
        if (area) filters.push({ field: "Area", type: "=", value: area });
        if (sistema)
            filters.push({ field: "Sistema", type: "=", value: sistema });
        if (jefatura)
            filters.push({ field: "Responsable", type: "=", value: jefatura });

        if (desde)
            filters.push({
                field: "Fecha_Creacion",
                type: ">=",
                value: new Date(desde).getTime() / 1000,
            });
        if (hasta)
            filters.push({
                field: "Fecha_Creacion",
                type: "<=",
                value: new Date(hasta).getTime() / 1000 + 86399,
            });

        table.clearFilter();
        if (filters.length) table.setFilter(filters);
    }

    function clearFilters(table) {
        [
            "filtroTipoCurso",
            "filtroAreaCurso",
            "filtroSistemaCurso",
            "filtroJefaturaCurso",
            "filtroFechaDesde",
            "filtroFechaHasta",
        ].forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.value = "";
        });
        table.clearFilter();
    }

    function initTableCursos() {
        const table = new Tabulator("#tblCursos", {
            ajaxURL: "/api/obtener-cursos",
            ajaxResponse: function (url, params, response) {
                return response.Cursos;
            },
            layout: "fitColumns",
            placeholder: "No hay cursos disponibles...",
            pagination: "local",
            paginationSize: 5,
            paginationSizeSelector: [5, 10, 15, 20],
            paginationCounter: "rows",
            locale: "es-es",
            langs: { "es-es": esLocale },

            rowFormatter(row) {
                setRowHoverEffect(row);
            },

            dataLoaded(data) {
                populateFilters(data);
            },

            columns: [
                {
                    title: "Nombre de curso",
                    field: "Nombre",
                    width: 375,
                    formatter(cell) {
                        const val = cell.getValue();
                        return `<span style="font-weight:500; color:#111827;">${val ?? "—"}</span>`;
                    },
                },
                {
                    title: "Responsable",
                    field: "Responsable",
                    width: 375,
                    headerSort: true,
                    formatter(cell) {
                        const val = cell.getValue();
                        if (!val) return "—";
                        const initials = getInitials(val);
                        return `
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="width:28px; height:28px; border-radius:50%; background:#e0e7ff; color:#4338ca; font-size:10px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0;">${initials}</div>
                            <span style="font-size:13px; color:#374151;">${val}</span>
                        </div>
                    `;
                    },
                },
                {
                    title: "Matriculados",
                    field: "Total_Matriculados",
                    width: 160,
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
                    field: "Fecha_Creacion",
                    width: 150,
                    hozAlign: "center",
                    headerSort: true,
                    formatter(cell) {
                        const { fecha, hora } = formatDateTime(
                            cell.getValue() * 1000,
                        ); // ← * 1000
                        if (fecha === "—") return "—";
                        return `
            <div style="display:flex; flex-direction:column; align-items:center; line-height:1.4;">
                <span style="font-size:12px; font-weight:600; color:#374151;">${fecha}</span>
                <span style="font-size:11px; color:#9ca3af;">${hora}</span>
            </div>`;
                    },
                },
                {
                    title: "Acciones",
                    width: 200,
                    hozAlign: "center",
                    headerSort: false,
                    formatter(cell) {
                        return `
                        <button class="btn-detalle-curso" data-row-id="${cell.getRow().getIndex()}" style="
                            background: #4f46e5;
                            color: white;
                            border: none;
                            border-radius: 6px;
                            padding: 6px 12px;
                            font-size: 12px;
                            font-weight: 500;
                            cursor: pointer;
                            display: inline-flex;
                            align-items: center;
                            gap: 6px;
                            transition: all 0.2s;
                        ">
                            <i class="ti ti-eye" style="font-size: 14px;"></i>
                            Gestionar matrículas
                        </button>
                    `;
                    },
                    cellClick: function (e, cell) {
                        e.stopPropagation();
                        const row = cell.getRow();
                        const data = row.getData();

                        const modalEl = document.getElementById(
                            "modal-lista-matriculados",
                        );
                        const alpineComponent = modalEl?._x_dataStack?.[0];

                        if (alpineComponent?.mostrar) {
                            alpineComponent.mostrar(data);
                        } else {
                            console.warn(
                                "No se encontró el componente Alpine del modal",
                            );
                        }
                    },
                },
            ],
        });

        table.on("dataLoaded", function (data) {
            populateFilters(data);
        });

        [
            "filtroTipoCurso",
            "filtroAreaCurso",
            "filtroSistemaCurso",
            "filtroJefaturaCurso",
            "filtroFechaDesde",
            "filtroFechaHasta",
        ].forEach((id) => {
            document
                .getElementById(id)
                ?.addEventListener("change", () => applyFilters(table));
        });

        document
            .getElementById("btnLimpiarFiltrosCursos")
            ?.addEventListener("click", () => clearFilters(table));

        window.tabulatorCursos = table;
    }

    function formatDate(dateStr) {
        if (!dateStr) return '??';
        const d = new Date(dateStr.includes(' ') ? dateStr.replace(' ', 'T') : dateStr);
        if (isNaN(d.getTime())) return '??';
        return d.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function updateMatricularButton() {
        const btn = document.getElementById('btnGuardarMatriculas');
        if (!btn) return;
        const table = window.tabulatorPersonalMatriculado;
        if (!table) {
            btn.innerHTML = '<i class="ti ti-user-plus"></i> Matricular personal (0)';
            btn.disabled = true;
            return;
        }
        const data = table.getData() || [];
        let count = 0;
        data.forEach(d => {
            if (d._seleccionado && !d._matriculado) count++;
        });
        btn.innerHTML = `<i class="ti ti-user-plus"></i> Matricular personal (${count})`;
        const slcVal = document.getElementById('slcProgramacion')?.value;
        btn.disabled = count === 0 || !slcVal;
    }

    window.limpiarModalMatriculados = function() {
        if (window.tabulatorPersonalMatriculado) {
            window.tabulatorPersonalMatriculado.destroy();
            window.tabulatorPersonalMatriculado = null;
        }
        window._matriculadosData = [];
        window._searchTerm = '';
        window._estadoFilter = '';
        document.querySelectorAll('.cnt-filter-btn').forEach(b => b.classList.remove('active'));
        const txtBuscar = document.getElementById('txtBuscarPersonal');
        if (txtBuscar) { txtBuscar.value = ''; }
        const btnLimpiar = document.getElementById('btnLimpiarBusqueda');
        if (btnLimpiar) { btnLimpiar.style.display = 'none'; }
        document.getElementById('slcProgramacion').innerHTML = '<option value="">Seleccione...</option>';
        ['slcFiltroCliente', 'slcFiltroSucursal', 'slcFiltroCargo', 'slcFiltroTipoTrabajador'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<option value="">' + (id.includes('Cliente') ? 'Todos' : id.includes('Sucursal') ? 'Todas' : id.includes('Cargo') ? 'Todos' : 'Todos') + '</option>';
        });
        document.getElementById('cntMatriculados').textContent = '0';
        document.getElementById('cntSinMatricular').textContent = '0';
        const btn = document.getElementById('btnGuardarMatriculas');
        if (btn) {
            btn.innerHTML = '<i class="ti ti-user-plus"></i> Matricular personal (0)';
            btn.disabled = true;
        }
    }

    function actualizarContadores() {
        const table = window.tabulatorPersonalMatriculado;
        const data = table ? table.getData() || [] : [];
        const matriculados = data.filter(d => d._matriculado).length;
        const sinMatricular = data.filter(d => !d._matriculado).length;
        const elM = document.getElementById('cntMatriculados');
        const elS = document.getElementById('cntSinMatricular');
        if (elM) elM.textContent = matriculados;
        if (elS) elS.textContent = sinMatricular;
    }

    window.cargarDatosModalMatriculados = async function(cursoId, alpineComponent) {
        if (!cursoId) return;

        try {
            const resProgramaciones = await axios.get(`/api/obtener-programaciones/${cursoId}`);
            const programaciones = resProgramaciones.data.programaciones || resProgramaciones.data.Programaciones || resProgramaciones.data || [];
            
            const slcProg = document.getElementById('slcProgramacion');
            if (slcProg) {
                slcProg.innerHTML = '<option value="">Seleccione una programación...</option>';
                let firstVigente = null;
                programaciones.forEach(p => {
                    const opt = document.createElement('option');
                    const codProg = (p.codigo_programacion || p.cod_programacion || p.codigo || '').toString().trim();
                    opt.value = codProg;
                    const fInicio = formatDate(p.fecha_inicio);
                    const fFinal = formatDate(p.fecha_final);
                    const estado = p.estado_periodo || p.estado || '';
                    opt.textContent = `Programación ${codProg} | ${fInicio} - ${fFinal} (${estado})`;
                    if (estado !== 'VIGENTE') {
                        opt.disabled = true;
                    } else if (!firstVigente) {
                        firstVigente = codProg;
                    }
                    slcProg.appendChild(opt);
                });
                if (firstVigente) {
                    slcProg.value = firstVigente;
                }
            }

            const [resPersonal, resMatriculados] = await Promise.all([
                axios.get('/api/obtener-personal'),
                axios.get(`/api/obtener-matriculados/${cursoId}`)
            ]);

            const personalData = resPersonal.data.personal || resPersonal.data || [];
            const matriculadosData = resMatriculados.data.matriculados || resMatriculados.data.Matriculados || resMatriculados.data || [];
            window._matriculadosData = matriculadosData;

            const selectedCodProg = slcProg?.value || '';
            const filteredMatriculados = selectedCodProg
                ? matriculadosData.filter(m => String(m.cod_programacion || m.Cod_Programacion || '').toString().trim() === selectedCodProg)
                : [];
            const matriculadosSet = new Set(filteredMatriculados.map(m => String(m.cod_personal || m.Id_Personal || m.id || m.Id)));

            const responsableCodigo = alpineComponent?.codResponsable;
            const tableData = personalData
                .filter(p => String(p.codigo) !== String(responsableCodigo))
                .map(p => {
                const personalId = String(p.codigo || p.dni || p.id);
                const match = filteredMatriculados.find(m => String(m.cod_personal || m.Id_Personal || m.id || m.Id) === personalId);
                const yaMatriculado = !!match;
                return {
                    ...p,
                    _matriculado: yaMatriculado,
                    _seleccionado: yaMatriculado,
                    _fecha_matricula: match ? match.fecha_matricula || match.Fecha_Matricula || null : null,
                    _estado: match ? (match.estado || match.Estado || 'MATRICULADO') : null
                };
            });

            const unique = (field) => [...new Set(tableData.map(d => d[field]).filter(Boolean))].sort();
            fillSelect("slcFiltroCliente", unique("cliente"));
            fillSelect("slcFiltroSucursal", unique("sucursal"));
            fillSelect("slcFiltroCargo", unique("cargo"));
            fillSelect("slcFiltroTipoTrabajador", unique("tipo_trabajador"));

            if (window.tabulatorPersonalMatriculado) {
                window.tabulatorPersonalMatriculado.destroy();
            }

            return new Promise((resolve) => {
                setTimeout(() => {
                    window.tabulatorPersonalMatriculado = new Tabulator(
                        "#tblPersonalMatriculado",
                        {
                            data: tableData,
                            layout: "fitDataStretch",
                            pagination: "local",
                            paginationSize: 10,
                            langs: { "es-es": esLocale },
                            locale: "es-es",
                            initialSort: [
                                { column: "nombre_completo", dir: "asc" },
                            ],
                            rowFormatter(row) {
                                setRowHoverEffect(row);
                            },
                            columns: [
                                {
                                    title: "<input type='checkbox' class='checkbox-select-all'>",
                                    field: "_seleccionado",
                                    width: 50,
                                    hozAlign: "center",
                                    headerSort: false,
                                    formatter: function (cell) {
                                        const isChecked = cell.getValue();
                                        const rowData = cell.getRow().getData();
                                        const locked = rowData._matriculado;
                                        return `<div style="position:relative; display:inline-block; width:16px; height:16px;">
                                                <input type="checkbox" class="checkbox-personal-row" style="width:100%; height:100%; cursor:${locked ? "not-allowed" : "pointer"}; opacity:${locked ? "0.5" : "1"};" ${isChecked ? "checked" : ""} ${locked ? "disabled" : ""}>
                                            </div>`;
                                    },
                                    cellClick: function (e, cell) {
                                        e.preventDefault();
                                        const rowData = cell.getRow().getData();
                                        if (rowData._matriculado) return;
                                        cell.setValue(!cell.getValue());
                                        updateMatricularButton();
                                        actualizarContadores();
                                    },
                                },
                                {
                                    title: "Nombres",
                                    field: "nombre_completo",
                                    minWidth: 200,
                                },
                                {
                                    title: "Fecha",
                                    field: "_fecha_matricula",
                                    width: 120,
                                    hozAlign: "center",
                                    formatter(cell) {
                                        const val = cell.getValue();
                                        if (!val)
                                            return '<span style="color:#cbd5e1;">—</span>';

                                        const date = new Date(val);
                                        if (isNaN(date))
                                            return '<span style="color:#cbd5e1;">—</span>';

                                        const fecha = date.toLocaleDateString(
                                            "es-PE",
                                            {
                                                day: "2-digit",
                                                month: "2-digit",
                                                year: "numeric",
                                            },
                                        );
                                        const hora = date.toLocaleTimeString(
                                            "es-PE",
                                            {
                                                hour: "2-digit",
                                                minute: "2-digit",
                                            },
                                        );

                                        return `
            <div style="line-height:1.3; text-align:center;">
                <div style="font-size:12px;">${fecha}</div>
                <div style="font-size:10px; color:#94a3b8;">${hora}</div>
            </div>`;
                                    },
                                },
                                {
                                    title: "Cliente",
                                    field: "cliente",
                                    sorter: function(a, b) {
                                        const va = a || "Sin cliente";
                                        const vb = b || "Sin cliente";
                                        return va.localeCompare(vb, "es");
                                    },
                                    formatter(cell) {
                                        return (
                                            cell.getValue() ||
                                            '<span style="color:#94a3b8;">Sin cliente</span>'
                                        );
                                    },
                                },
                                {
                                    title: "Sucursal",
                                    field: "sucursal",
                                },
                                { title: "Cargo", field: "cargo" },
                                {
                                    title: "Tipo",
                                    field: "tipo_trabajador",
                                },
                                {
                                    title: "Estado",
                                    field: "_estado",
                                    hozAlign: "center",
                                    sorter: function(a, b) {
                                        const va = a || "—";
                                        const vb = b || "—";
                                        const order = { "—": 0, "SUSPENDIDO": 1, "MATRICULADO": 2, "FINALIZADO": 3 };
                                        return (order[va] || 0) - (order[vb] || 0);
                                    },
                                    formatter(cell) {
                                        const val = cell.getValue();
                                        const rowData = cell.getRow().getData();
                                        if (!val || !rowData._matriculado)
                                            return '<span style="color:#cbd5e1;">—</span>';
                                        const colors = {
                                            MATRICULADO: {
                                                bg: "#dcfce7",
                                                text: "#166534",
                                                icon: "ti ti-circle-check-filled",
                                            },
                                            SUSPENDIDO: {
                                                bg: "#fef3c7",
                                                text: "#92400e",
                                                icon: "ti ti-alert-circle",
                                            },
                                            FINALIZADO: {
                                                bg: "#dbeafe",
                                                text: "#1e40af",
                                                icon: "ti ti-circle-check-filled",
                                            },
                                        };
                                        const style = colors[
                                            val.toUpperCase()
                                        ] || {
                                            bg: "#f1f5f9",
                                            text: "#475569",
                                            icon: "ti ti-circle",
                                        };
                                        return `<button type="button" class="btn-desmatricular" style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;background:${style.bg};color:${style.text};white-space:nowrap;border:none;cursor:pointer;"><i class="${style.icon} estado-icon" style="font-size:12px;"></i><span class="estado-label">${val}</span></button>`;
                                    },
                                    cellClick(e, cell) {
                                        const rowData = cell.getRow().getData();
                                        if (!rowData._matriculado) return;
                                        e.stopPropagation();
                                        const nombre = rowData.nombre_completo || 'este usuario';
                                        Swal.fire({
                                            icon: 'question',
                                            title: '¿Desmatricular?',
                                            text: `¿Está seguro de desmatricular a ${nombre}?`,
                                            showCancelButton: true,
                                            confirmButtonText: 'Sí, desmatricular',
                                            cancelButtonText: 'Cancelar',
                                            confirmButtonColor: '#ef4444',
                                            cancelButtonColor: '#6366f1',
                                            reverseButtons: true,
                                        }).then(async (result) => {
                                            if (!result.isConfirmed) return;
                                            const codPersonal = String(rowData.codigo || rowData.dni || rowData.id);
                                            try {
                                                Swal.fire({ title: 'Desmatriculando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                                                const resp = await axios.post(`/api/capacitacion/desmatricular-usuario`, { codPersonal, cursoId });
                                                Swal.close();
                                                if (resp.data.success) {
                                                    const row = cell.getRow();
                                                    row.update({
                                                        _matriculado: false,
                                                        _seleccionado: false,
                                                        _estado: null,
                                                        _fecha_matricula: null,
                                                    });
                                                    await Swal.fire({ icon: 'success', title: 'Desmatriculado', text: 'El personal fue desmatriculado correctamente.', confirmButtonColor: '#6366f1', timer: 2000, showConfirmButton: false });
                                                    updateMatricularButton();
                                                    actualizarContadores();
                                                } else {
                                                    await Swal.fire({ icon: 'error', title: 'Error', text: resp.data.message || 'Error desconocido', confirmButtonColor: '#6366f1' });
                                                }
                                            } catch (err) {
                                                Swal.close();
                                                await Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message || 'Error al desmatricular', confirmButtonColor: '#6366f1' });
                                            }
                                        });
                                    },
                                },
                            ],
                        },
                    );

                    window.tabulatorPersonalMatriculado.on("tableBuilt", function() {
                        const headerCheckbox = document.querySelector('.checkbox-select-all');

                        // Reset header checkbox al cambiar de página
                        window.tabulatorPersonalMatriculado.on("pageLoaded", function() {
                            if (headerCheckbox) headerCheckbox.checked = false;
                        });

                        if (headerCheckbox) {
                            headerCheckbox.addEventListener('change', function(e) {
                                const isChecked = e.target.checked;
                                const rows = window.tabulatorPersonalMatriculado.getRows();
                                rows.forEach(row => {
                                    const el = row.getElement();
                                    if (el && el.style.display === 'none') return;
                                    const d = row.getData();
                                    if (!d._matriculado) {
                                        row.update({ _seleccionado: isChecked });
                                    }
                                });
                                updateMatricularButton();
                                actualizarContadores();
                            });
                        }
                        
                        function aplicarFiltrosCombinados() {
                            const cli = document.getElementById("slcFiltroCliente")?.value;
                            const suc = document.getElementById("slcFiltroSucursal")?.value;
                            const car = document.getElementById("slcFiltroCargo")?.value;
                            const tip = document.getElementById("slcFiltroTipoTrabajador")?.value;
                            const term = window._searchTerm || '';
                            const est = window._estadoFilter || '';

                            const filters = [];
                            if (cli) filters.push({ field: "cliente", type: "=", value: cli });
                            if (suc) filters.push({ field: "sucursal", type: "=", value: suc });
                            if (car) filters.push({ field: "cargo", type: "=", value: car });
                            if (tip) filters.push({ field: "tipo_trabajador", type: "=", value: tip });
                            if (est === "matriculados") filters.push({ field: "_matriculado", type: "=", value: true });
                            if (est === "sin-matricular") filters.push({ field: "_matriculado", type: "=", value: false });

                            window.tabulatorPersonalMatriculado.clearFilter();
                            if (filters.length) window.tabulatorPersonalMatriculado.setFilter(filters);

                            if (term) {
                                window.tabulatorPersonalMatriculado.addFilter(data => {
                                    const nombre = (data.nombre_completo || '').toLowerCase();
                                    const codigo = String(data.codigo || '');
                                    const dni = String(data.dni || '');
                                    return nombre.includes(term) || codigo.includes(term) || dni.includes(term);
                                });
                            }
                        }

                        const txtBuscar = document.getElementById('txtBuscarPersonal');
                        const btnLimpiarBusqueda = document.getElementById('btnLimpiarBusqueda');
                        if (txtBuscar) {
                            txtBuscar.addEventListener('input', function() {
                                window._searchTerm = this.value.toLowerCase().trim();
                                if (btnLimpiarBusqueda) btnLimpiarBusqueda.style.display = this.value ? '' : 'none';
                                aplicarFiltrosCombinados();
                            });
                        }
                        if (btnLimpiarBusqueda) {
                            btnLimpiarBusqueda.addEventListener('click', function() {
                                txtBuscar.value = '';
                                window._searchTerm = '';
                                this.style.display = 'none';
                                aplicarFiltrosCombinados();
                            });
                        }

                        document.querySelectorAll('.cnt-filter-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const filter = this.dataset.filter;
                                if (window._estadoFilter === filter) {
                                    window._estadoFilter = '';
                                    this.classList.remove('active');
                                } else {
                                    document.querySelectorAll('.cnt-filter-btn').forEach(b => b.classList.remove('active'));
                                    window._estadoFilter = filter;
                                    this.classList.add('active');
                                }
                                aplicarFiltrosCombinados();
                            });
                        });

                        ["slcFiltroCliente", "slcFiltroSucursal", "slcFiltroCargo", "slcFiltroTipoTrabajador"].forEach(id => {
                            document.getElementById(id)?.addEventListener('change', aplicarFiltrosCombinados);
                        });

                        // Programación change → re-seleccionar filas
                        slcProg.onchange = function() {
                            const codProg = this.value;
                            const filtered = codProg
                                ? (window._matriculadosData || []).filter(m => String(m.cod_programacion || m.Cod_Programacion || '').toString().trim() === codProg)
                                : [];
                            const allRows = window.tabulatorPersonalMatriculado?.getRows(true) || [];
                            allRows.forEach(row => {
                                const d = row.getData();
                                const personalId = String(d.codigo || d.dni || d.id);
                                const match = filtered.find(m => String(m.cod_personal || m.Id_Personal || m.id || m.Id) === personalId);
                                const yaMatriculado = !!match;
                                row.update({
                                    _matriculado: yaMatriculado,
                                    _seleccionado: yaMatriculado,
                                    _fecha_matricula: match ? match.fecha_matricula || match.Fecha_Matricula || null : null
                                });
                            });
                            updateMatricularButton();
                            actualizarContadores();
                        };
                        // Sincronizar filas por si el usuario cambió dropdown antes de que la tabla estuviera lista
                        if (slcProg.value) slcProg.onchange();

                        // Botón guardar matrícula
                        const btnGuardar = document.getElementById('btnGuardarMatriculas');
                        if (btnGuardar) {
                            btnGuardar.onclick = async function() {
                                const modalEl = document.getElementById('modal-lista-matriculados');
                                const alpineComponent = modalEl?._x_dataStack?.[0];
                                const cId = alpineComponent?.cursoId;
                                const progId = slcProg?.value;

                                if (!progId) {
                                    Swal.fire({ icon: 'warning', title: 'Atención', text: 'Seleccione una programación', confirmButtonColor: '#6366f1' });
                                    return;
                                }

                                const allData = window.tabulatorPersonalMatriculado?.getData() || [];
                                const personalIds = allData.filter(d => d._seleccionado && !d._matriculado).map(d => String(d.codigo || d.dni || d.id)).filter(Boolean);

                                if (!personalIds.length) {
                                    Swal.fire({ icon: 'warning', title: 'Atención', text: 'Seleccione al menos un personal sin matricular', confirmButtonColor: '#6366f1' });
                                    return;
                                }

                                // Cerrar modal inmediatamente
                                if (alpineComponent?.cerrar) alpineComponent.cerrar();

                                Swal.fire({ title: 'Matriculando personal...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                                try {
                                    const resp = await axios.post(
                                        "/capacitacion/save-matricula",
                                        {
                                            cursoId: String(cId),
                                            programacionId: String(progId),
                                            personalIds,
                                        },
                                    );
                                    Swal.close();
                                    if (resp.data.success) {
                                        await Swal.fire({ icon: 'success', title: '¡Solicitud enviada!', text: 'Recibirá una notificación sobre el progreso en tiempo real de la matriculación', confirmButtonColor: '#6366f1' });
                                    } else {
                                        await Swal.fire({ icon: 'error', title: 'Error', text: resp.data.message || 'Error desconocido', confirmButtonColor: '#6366f1' });
                                    }
                                } catch (err) {
                                    Swal.close();
                                    console.error(err);
                                    await Swal.fire({ icon: 'error', title: 'Error', text: 'Error al guardar la matrícula', confirmButtonColor: '#6366f1' });
                                }
                            };
                        }

                        updateMatricularButton();
                        actualizarContadores();
                        if (alpineComponent) alpineComponent.isLoading = false;
                    });

                    resolve();
                }, 300);
            });

        } catch (error) {
            console.error("Error cargando datos del modal", error);
            if (alpineComponent) alpineComponent.isLoading = false;
        }
    };

    initTableCursos();
});
