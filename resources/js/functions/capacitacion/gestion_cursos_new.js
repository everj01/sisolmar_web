import { TabulatorFull as Tabulator } from "tabulator-tables";
import "tabulator-tables/dist/css/tabulator_simple.min.css";

document.addEventListener("DOMContentLoaded", () => {
    const elTabla = document.getElementById("tblCursosNew");
    if (!elTabla) return;

    const elements = {
        search: document.getElementById("buscarCursoNew"),
        filtroPlan: document.getElementById("filtroPlanCursoNew"),
        filtroTipo: document.getElementById("filtroTipoCursoNew"),
        filtroAnio: document.getElementById("filtroAnioCursoNew"),
        filtroDesde: document.getElementById("filtroDesdeCursoNew"),
        filtroHasta: document.getElementById("filtroHastaCursoNew"),
        btnLimpiar: document.getElementById("btnLimpiarFiltrosCursosNew"),
        statTotal: document.getElementById("statTotalCursos"),
    };

    // ── Botón "Ingresar directo BD" + modal nativo (<dialog>) ──
    // Inserción directa a sw_cursos sin wizard ni Moodle.
    initModalDirectoBD();

    const state = { term: "", plan: "", tipo: "", anio: "", desde: "", hasta: "" };

    const esLocale = {
        pagination: {
            page_size: "Filas por página",
            first: "Primero",
            last: "Último",
            prev: "Anterior",
            next: "Siguiente",
            counter: { showing: "Mostrando", of: "de", rows: "filas", pages: "páginas" },
        },
        data: { loading: "Cargando...", error: "Error al cargar los datos" },
    };

    const estilosTipo = {
        INDUCCIÓN: "bg-sky-500/10 text-sky-600",
        CHARLA: "bg-amber-500/10 text-amber-600",
        CAPACITACIÓN: "bg-primary/10 text-primary",
        ENTRENAMIENTO: "bg-emerald-500/10 text-emerald-600",
        "SIMULACROS DE EMERGENCIA": "bg-rose-500/10 text-rose-600",
    };

    const esc = (v) => String(v ?? "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");

    // Fecha creación: API envía "dd/mm/YYYY" (display) + "YYYY-MM-DD" (ISO). Normalizar a ISO para filtrar/ordenar.
    function fechaAISO(v) {
        if (!v) return "";
        const s = String(v).trim();
        if (/^\d{4}-\d{2}-\d{2}/.test(s)) return s.slice(0, 10);
        const m = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/);
        if (m) return `${m[3]}-${String(m[2]).padStart(2, "0")}-${String(m[1]).padStart(2, "0")}`;
        return "";
    }

    function anioDe(data) {
        const iso = data.CURS_CREADO_FECHA_ISO || fechaAISO(data.CURS_CREADO_FECHA);
        return iso ? iso.slice(0, 4) : "";
    }

    // Dispatcher desacoplado: la tabla no depende del JS del modal.
    // Guarda el pedido en cola (por si Alpine aún no inicializa) y avisa por evento.
    window.solicitarEdicionCursoNew = (cod) => {
        if (!cod) return;
        window.__pendingEdicionNew = String(cod);
        window.dispatchEvent(new CustomEvent("open-modal-edicion-new", { detail: { codigo: String(cod) } }));
    };

    window.solicitarAperturaCursoNew = (cod) => {
        if (!cod || typeof window.abrirModalAperturaNew !== "function") {
            if (!cod) return;
            window.__pendingAperturaNew = String(cod);
            window.dispatchEvent(new CustomEvent("open-modal-apertura-new", { detail: { codigo: String(cod) } }));
            return;
        }
        window.abrirModalAperturaNew(cod);
    };

    window.solicitarAplazarCursoNew = (cod) => {
        if (!cod || typeof window.abrirModalAplazarNew !== "function") {
            if (!cod) return;
            window.__pendingAplazarNew = String(cod);
            window.dispatchEvent(new CustomEvent("open-modal-aplazar-new", { detail: { codigo: String(cod) } }));
            return;
        }
        window.abrirModalAplazarNew(cod);
    };

    // Editar y deshabilitar bloqueados si ya tiene VIGENTE; aperturar igual; aplazar / deshabilitar (próximamente)
    function botonesAccionVisual(cod, tieneVigente) {
        const safeCod = String(cod ?? "").replace(/'/g, "\\'");
        const bloqueado = "btn btn-sm rounded bg-gray-100/50 text-gray-400 cursor-not-allowed";
        const btnEditar = tieneVigente
            ? `<button type="button" disabled title="No se puede editar: tiene un periodo VIGENTE activo"
                class="${bloqueado}">
                <i class="bx bxs-edit text-base pointer-events-none"></i>
            </button>`
            : `<button type="button" title="Editar curso" onclick="window.solicitarEdicionCursoNew('${safeCod}')"
                class="btn btn-sm rounded bg-info/10 text-info hover:bg-info hover:text-white transition-colors">
                <i class="bx bxs-edit text-base pointer-events-none"></i>
            </button>`;
        const btnAperturar = tieneVigente
            ? `<button type="button" disabled title="Ya tiene un periodo VIGENTE activo"
                class="${bloqueado}">
                <i class="bx bx-calendar-star text-base pointer-events-none"></i>
            </button>`
            : `<button type="button" title="Aperturar curso" onclick="window.solicitarAperturaCursoNew('${safeCod}')"
                class="btn btn-sm rounded bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors">
                <i class="bx bx-calendar-star text-base pointer-events-none"></i>
            </button>`;
        const btnEliminar = tieneVigente
            ? `<button type="button" disabled title="No se puede deshabilitar: tiene un periodo VIGENTE activo"
                class="${bloqueado}">
                <i class="bx bx-trash text-base pointer-events-none"></i>
            </button>`
            : `<button type="button" title="Deshabilitar curso (próximamente)"
                class="btn btn-sm rounded bg-danger/10 text-danger hover:bg-danger hover:text-white transition-colors">
                <i class="bx bx-trash text-base pointer-events-none"></i>
            </button>`;
        const btnAplazar = tieneVigente
            ? `<button type="button" title="Extender plazo del curso" onclick="window.solicitarAplazarCursoNew('${safeCod}')"
                class="btn btn-sm rounded bg-success/10 text-success hover:bg-success hover:text-white transition-colors">
                <i class="bx bx-time-five text-base pointer-events-none"></i>
            </button>`
            : `<button type="button" disabled title="Solo disponible con un periodo VIGENTE activo"
                class="${bloqueado}">
                <i class="bx bx-time-five text-base pointer-events-none"></i>
            </button>`;
        return `
        <div class="flex items-center justify-center gap-1.5">
            ${btnEditar}
            ${btnAperturar}
            ${btnAplazar}
            ${btnEliminar}
        </div>`;
    }

    const table = new Tabulator("#tblCursosNew", {
        ajaxURL: `${VITE_URL_APP}/api/obtener-cursos-new`,
        ajaxResponse(_url, _params, res) {
            const rows = Array.isArray(res) ? res : (res?.data ?? []);
            if (elements.statTotal) {
                const total = res?.total ?? rows.length;
                elements.statTotal.textContent = total;
            }
            return rows;
        },
        layout: "fitDataFill",
        placeholder: "No se encontraron cursos",
        pagination: "local",
        paginationSize: 5,
        paginationSizeSelector: [5, 15, 25, 50],
        paginationCounter: "rows",
        locale: "es-es",
        langs: { "es-es": esLocale },
        initialSort: [{ column: "CURS_CREADO_FECHA", dir: "desc" }],
        rowFormatter(row) {
            row.getElement().style.cursor = "pointer";
            row.getElement().setAttribute("title", "Clic para editar el curso");
        },
        rowClick(e, row) {
            // Los botones de acción tienen su propio onclick; el clic en fila también abre edición
            if (e?.target?.closest?.("button")) return;
            window.solicitarEdicionCursoNew(row.getData()?.CURS_COD);
        },
        columns: [
            {
                title: "Nombre de curso",
                field: "CURS_NOMBRE",
                minWidth: 250,
                headerSort: true,
                formatter: (cell) =>
                    `<span style="font-weight:500; color:#111827;">${esc(cell.getValue()) || "—"}</span>`,
            },
            {
                title: "Tipo",
                field: "CURS_TIPO",
                width: 200,
                headerSort: true,
                headerHozAlign: "center",
                hozAlign: "center",
                formatter(cell) {
                    const v = cell.getValue() || "—";
                    const cls = estilosTipo[v] || "bg-gray-500/10 text-gray-600";
                    return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap ${cls}">${esc(v)}</span>`;
                },
            },
            {
                title: "Plan",
                field: "CURS_PLAN_CAPAC_NOMBRE",
                width: 110,
                hozAlign: "center",
                headerHozAlign: "center",
                headerSort: true,
                formatter: (cell) =>
                    `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">${esc(cell.getValue()) || "—"}</span>`,
            },
            {
                title: "Sist. gestión",
                field: "CURS_SIST_GESTION_NOMBRE",
                width: 130,
                hozAlign: "center",
                headerSort: true,
                formatter: (cell) =>
                    `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-600">${esc(cell.getValue()) || "—"}</span>`,
            },
            {
                title: "Creación",
                field: "CURS_CREADO_FECHA",
                width: 130,
                hozAlign: "center",
                headerHozAlign: "center",
                headerSort: true,
                sorter: (a, b, aRow, bRow) => {
                    const isoA = aRow.getData().CURS_CREADO_FECHA_ISO || fechaAISO(a);
                    const isoB = bRow.getData().CURS_CREADO_FECHA_ISO || fechaAISO(b);
                    return String(isoA || "").localeCompare(String(isoB || ""));
                },
                formatter: (cell) =>
                    `<span style="font-size:12px; font-weight:600; color:#374151;">${esc(cell.getValue()) || "—"}</span>`,
            },
            {
                title: "Acciones",
                field: "ACCIONES",
                hozAlign: "center",
                headerHozAlign: "center",
                headerSort: false,
                formatter: (cell) => {
                    const d = cell.getData() || {};
                    return botonesAccionVisual(d.CURS_COD, d.CURS_TIENE_VIGENTE === true);
                },
                // Intencionalmente sin cellClick: botones solo visuales por ahora
            },
        ],
    });

    window.tablaCursosNew = table;

    table.on("dataLoaded", (rows) => {
        const uniq = (arr) => [...new Set(arr.filter(Boolean))].sort();

        if (elements.filtroPlan) {
            const actual = elements.filtroPlan.value;
            elements.filtroPlan.innerHTML =
                `<option value="">Todos los planes</option>` +
                uniq(rows.map((r) => r.CURS_PLAN_CAPAC_NOMBRE))
                    .map((v) => `<option value="${esc(v)}">${esc(v)}</option>`)
                    .join("");
            elements.filtroPlan.value = actual;
        }
        if (elements.filtroTipo) {
            const actual = elements.filtroTipo.value;
            elements.filtroTipo.innerHTML =
                `<option value="">Todos los tipos</option>` +
                uniq(rows.map((r) => r.CURS_TIPO))
                    .map((v) => `<option value="${esc(v)}">${esc(v)}</option>`)
                    .join("");
            elements.filtroTipo.value = actual;
        }
        if (elements.filtroAnio) {
            const actual = elements.filtroAnio.value;
            const anios = [...new Set(rows.map(anioDe).filter(Boolean))].sort().reverse();
            elements.filtroAnio.innerHTML =
                `<option value="">Todos los años</option>` +
                anios.map((v) => `<option value="${esc(v)}">${esc(v)}</option>`).join("");
            if (actual && anios.includes(actual)) elements.filtroAnio.value = actual;
        }
    });

    function aplicarFiltros() {
        const { term, plan, tipo, anio, desde, hasta } = state;
        if (term || plan || tipo || anio || desde || hasta) {
            table.setFilter((data) => {
                if (plan && (data.CURS_PLAN_CAPAC_NOMBRE || "") !== plan) return false;
                if (tipo && (data.CURS_TIPO || "") !== tipo) return false;
                if (anio && anioDe(data) !== anio) return false;
                if (desde || hasta) {
                    const iso = data.CURS_CREADO_FECHA_ISO || fechaAISO(data.CURS_CREADO_FECHA);
                    if (!iso) return false;
                    if (desde && iso < desde) return false;
                    if (hasta && iso > hasta) return false;
                }
                if (term) {
                    const nom = String(data.CURS_NOMBRE || "").toLowerCase();
                    if (!nom.includes(term)) return false;
                }
                return true;
            });
        } else {
            table.clearFilter();
        }
    }

    let searchT;
    elements.search?.addEventListener("input", function () {
        clearTimeout(searchT);
        searchT = setTimeout(() => {
            state.term = (this.value || "").trim().toLowerCase();
            aplicarFiltros();
        }, 250);
    });
    elements.filtroPlan?.addEventListener("change", function () {
        state.plan = this.value;
        aplicarFiltros();
    });
    elements.filtroTipo?.addEventListener("change", function () {
        state.tipo = this.value;
        aplicarFiltros();
    });
    elements.filtroAnio?.addEventListener("change", function () {
        state.anio = this.value;
        aplicarFiltros();
    });
    elements.filtroDesde?.addEventListener("change", function () {
        state.desde = this.value;
        aplicarFiltros();
    });
    elements.filtroHasta?.addEventListener("change", function () {
        state.hasta = this.value;
        aplicarFiltros();
    });
    elements.btnLimpiar?.addEventListener("click", () => {
        state.term = "";
        state.plan = "";
        state.tipo = "";
        state.anio = "";
        state.desde = "";
        state.hasta = "";
        if (elements.search) elements.search.value = "";
        if (elements.filtroPlan) elements.filtroPlan.value = "";
        if (elements.filtroTipo) elements.filtroTipo.value = "";
        if (elements.filtroAnio) elements.filtroAnio.value = "";
        if (elements.filtroDesde) elements.filtroDesde.value = "";
        if (elements.filtroHasta) elements.filtroHasta.value = "";
        table.clearFilter();
    });

    // ─────────────────────────────────────────────────────────────
    // Modal nativo "Ingresar directo BD" (dialog HTML, sin Alpine).
    // ─────────────────────────────────────────────────────────────
    function initModalDirectoBD() {
        const base = (typeof VITE_URL_APP !== "undefined" ? VITE_URL_APP : "").replace(/\/$/, "");
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

        // 1) Botón junto a "Crear un curso" / "Limpiar filtros".
        if (!document.getElementById("btnDirectoBDNew")) {
            const ref = document.querySelector('button[onclick*="abrirModalRegistroNew"]') || elements.btnLimpiar;
            const btn = document.createElement("button");
            btn.type = "button";
            btn.id = "btnDirectoBDNew";
            btn.title = "Inserción directa a la tabla sw_cursos";
            btn.className =
                "h-10 px-4 inline-flex items-center justify-center gap-1.5 bg-slate-800 text-white text-xs font-semibold rounded-lg shadow-sm hover:bg-slate-700 transition whitespace-nowrap";
            btn.innerHTML = '<i class="ti ti-database text-sm"></i> Ingresar directo BD';
            btn.addEventListener("click", abrirDirectoBD);
            if (ref?.parentElement) ref.parentElement.insertBefore(btn, ref.nextSibling);
            else document.body.appendChild(btn);
        }

        // 2) Dialog nativo (se crea una sola vez).
        if (document.getElementById("dlgDirectoBDNew")) return;

        const dlg = document.createElement("dialog");
        dlg.id = "dlgDirectoBDNew";
        dlg.style.cssText = "border:none;border-radius:16px;padding:0;width:min(860px,94vw);max-height:90vh;";
        dlg.innerHTML = `
        <style>
            #dlgDirectoBDNew::backdrop{background:rgba(15,23,42,.55);}
            .ddb-head{padding:18px 22px;border-bottom:1px solid #eef2f7;display:flex;justify-content:space-between;align-items:flex-start;gap:12px;}
            .ddb-body{padding:20px 22px;overflow-y:auto;max-height:calc(90vh - 140px);}
            .ddb-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
            @media(max-width:640px){.ddb-grid{grid-template-columns:1fr;}}
            .ddb-field{display:flex;flex-direction:column;gap:4px;}
            .ddb-field.full{grid-column:1/-1;}
            .ddb-field label{font-size:12px;font-weight:700;color:#334155;}
            .ddb-field input[type=text],.ddb-field input[type=date],.ddb-field select{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px;font-size:13px;color:#0f172a;background:#fff;width:100%;box-sizing:border-box;}
            .ddb-field input:read-only,.ddb-field input[disabled]{background:#f1f5f9;color:#64748b;}
            .ddb-check{display:flex;align-items:center;gap:8px;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:13px;font-weight:600;color:#334155;background:#f8fafc;}
            .ddb-check input{width:16px;height:16px;accent-color:#2563eb;}
            .ddb-check .tag{margin-left:auto;font-size:11px;font-weight:800;padding:2px 8px;border-radius:999px;background:#e2e8f0;color:#475569;}
            .ddb-check.on .tag{background:#dcfce7;color:#15803d;}
            .ddb-foot{padding:14px 22px;border-top:1px solid #eef2f7;display:flex;justify-content:flex-end;gap:8px;background:#f8fafc;border-radius:0 0 16px 16px;}
            .ddb-btn{height:38px;padding:0 16px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;border:1px solid transparent;}
            .ddb-btn.cancel{background:#fff;border-color:#cbd5e1;color:#334155;}
            .ddb-btn.save{background:#1e293b;color:#fff;}
            .ddb-btn.save:disabled{opacity:.6;cursor:not-allowed;}
            .ddb-err{display:none;margin-top:10px;font-size:12px;font-weight:600;color:#b91c1c;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:8px 10px;}
        </style>
        <form method="dialog" id="frmDirectoBDNew" style="margin:0;">
            <div class="ddb-head">
                <div>
                    <h3 style="margin:0;font-size:17px;font-weight:800;color:#0f172a;">Ingresar directo BD</h3>
                    <p style="margin:4px 0 0;font-size:12px;color:#64748b;">Inserción directa a <b>sw_cursos</b>. Sin wizard, sin Moodle.</p>
                </div>
                <button type="button" id="ddbCerrar" title="Cerrar" style="width:32px;height:32px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;cursor:pointer;">✕</button>
            </div>
            <div class="ddb-body">
                <div class="ddb-grid">
                    <div class="ddb-field"><label>codigo_curso (auto)</label><input type="text" id="ddb_codigo_curso" readonly placeholder="…" title="Recientemente creado + 1"></div>
                    <div class="ddb-field"><label>nombre *</label><input type="text" id="ddb_nombre" required maxlength="100" placeholder="Nombre del curso"></div>
                    <div class="ddb-field"><label>creado_por</label><input type="text" id="ddb_creado_por" maxlength="50" placeholder="Usuario creador"></div>
                    <div class="ddb-field"><label>fecha_creacion</label><input type="date" id="ddb_fecha_creacion"></div>
                    <div class="ddb-field"><label>modificado_por</label><input type="text" id="ddb_modificado_por" maxlength="50" placeholder="Usuario modifica"></div>
                    <div class="ddb-field"><label>fecha_modificacion</label><input type="date" id="ddb_fecha_modificacion"></div>
                    <div class="ddb-field"><label>habilitado</label><label class="ddb-check on"><input type="checkbox" id="ddb_habilitado" checked> Habilitado <span class="tag">SI</span></label></div>
                    <div class="ddb-field"><label>periodicidad (fijo NULL)</label><input type="text" value="NULL" disabled></div>
                    <div class="ddb-field"><label>es_periodico</label><label class="ddb-check"><input type="checkbox" id="ddb_es_periodico"> Periódico <span class="tag">NO</span></label></div>
                    <div class="ddb-field"><label>frecuencia (fijo NULL)</label><input type="text" value="NULL" disabled></div>
                    <div class="ddb-field"><label>aplica_evaluacion</label><label class="ddb-check"><input type="checkbox" id="ddb_aplica_evaluacion"> Aplica evaluación <span class="tag">NO</span></label></div>
                    <div class="ddb-field"><label>obligatorio_alta</label><label class="ddb-check"><input type="checkbox" id="ddb_obligatorio_alta"> Obligatorio al alta <span class="tag">NO</span></label></div>
                    <div class="ddb-field"><label>cod_responsable</label><select id="ddb_cod_responsable"><option value="">Seleccione…</option><option value="__NULL__">NULL</option></select></div>
                    <div class="ddb-field"><label>codigo_moodle</label><input type="text" id="ddb_codigo_moodle" maxlength="50" placeholder="ID Moodle (opcional)"></div>
                    <div class="ddb-field full"><label>descripcion</label><input type="text" id="ddb_descripcion" placeholder="Descripción del curso"></div>
                    <div class="ddb-field"><label>dirigido_a</label><select id="ddb_dirigido_a"><option value="">Seleccione…</option><option value="__NULL__">NULL</option></select></div>
                    <div class="ddb-field"><label>cod_cliente</label><select id="ddb_cod_cliente"><option value="">Seleccione…</option><option value="__NULL__">NULL</option></select></div>
                    <div class="ddb-field"><label>sucursal</label><select id="ddb_sucursal"><option value="">Seleccione…</option><option value="__NULL__">NULL</option><option value="TODAS">TODAS - Todas las sucursales</option></select></div>
                    <div class="ddb-field"><label>codEmpresa (fijo 01)</label><input type="text" value="01" disabled></div>
                    <div class="ddb-field full"><label>target_group (fijo TODOS)</label><input type="text" value="TODOS" disabled></div>
                    <div class="ddb-field"><label>es_demanda</label><label class="ddb-check"><input type="checkbox" id="ddb_es_demanda"> Es demanda <span class="tag">NO</span></label></div>
                    <div class="ddb-field"><label>tipo_curso</label><select id="ddb_tipo_curso"><option value="">Seleccione…</option><option value="__NULL__">NULL</option></select></div>
                    <div class="ddb-field"><label>area_conocimiento</label><select id="ddb_area_conocimiento"><option value="">Seleccione…</option><option value="__NULL__">NULL</option></select></div>
                    <div class="ddb-field"><label>area</label><select id="ddb_area"><option value="">Seleccione…</option><option value="__NULL__">NULL</option></select></div>
                    <div class="ddb-field"><label>categoria</label><select id="ddb_categoria"><option value="">Seleccione…</option><option value="__NULL__">NULL</option><option value="1">1 - Inducción</option><option value="2">2 - Charla</option><option value="3">3 - Capacitación</option><option value="4">4 - Entrenamiento</option><option value="5">5 - Simulacros de emergencia</option></select></div>
                </div>
                <div class="ddb-err" id="ddbError"></div>
            </div>
            <div class="ddb-foot">
                <button type="button" class="ddb-btn cancel" id="ddbCancelar">Cancelar</button>
                <button type="submit" class="ddb-btn save" id="ddbGuardar">Guardar directo</button>
            </div>
        </form>`;
        document.body.appendChild(dlg);

        const $ = (id) => dlg.querySelector(id);
        const errBox = $("#ddbError");

        const setCheck = (input) => {
            const wrap = input.closest(".ddb-check");
            const tag = wrap?.querySelector(".tag");
            if (tag) tag.textContent = input.checked ? "SI" : "NO";
            wrap?.classList.toggle("on", input.checked);
        };
        dlg.querySelectorAll('.ddb-check input[type="checkbox"]').forEach((c) => {
            setCheck(c);
            c.addEventListener("change", () => setCheck(c));
        });

        $("#ddbCerrar").addEventListener("click", () => dlg.close());
        $("#ddbCancelar").addEventListener("click", () => dlg.close());

        const fill = (sel, items, valFn, txtFn) => {
            const s = $(sel);
            if (!s) return;
            const cur = s.value;
            const extra = sel === "#ddb_sucursal" ? '<option value="TODAS">TODAS - Todas las sucursales</option>' : '';
            s.innerHTML = '<option value="">Seleccione…</option><option value="__NULL__">NULL</option>' + extra + (items || []).map((it) => {
                const v = esc(String(valFn(it) ?? ""));
                const t = esc(String(txtFn(it) ?? ""));
                return `<option value="${v}">${t}</option>`;
            }).join("");
            // Restaurar selección previa si sigue existiendo; si no, dejar en placeholder.
            const exists = [...s.options].some((o) => o.value === cur);
            s.value = exists ? cur : "";
        };
        // Envía null cuando el dropdown está en placeholder ("") o en la opción explícita NULL ("__NULL__").
        const selOrNull = (sel) => {
            const v = $(sel)?.value;
            return (!v || v === "__NULL__") ? null : v;
        };

        async function cargarCombos() {
            // codigo_curso = último + 1 (placeholder bloqueado)
            try {
                const r = await fetch(`${base}/api/obtener-ultimo-codigo-curso-new`, { headers: { Accept: "application/json" } });
                const j = await r.json();
                if (j?.success) $("#ddb_codigo_curso").value = j.siguiente || "";
            } catch {}
            // Responsables (igual que wizard: listar-jefaturas)
            try {
                const r = await fetch(`${base}/api/listar-jefaturas`, { headers: { Accept: "application/json" } });
                const j = await r.json();
                fill("#ddb_cod_responsable", j?.personal || [], (p) => p.codigo, (p) => `${p.codigo} - ${p.nombre_completo}`);
            } catch {}
            // Dirigido a (igual que wizard: window.dirigidosNew, fallback API nueva)
            try {
                let lista = Array.isArray(window.dirigidosNew) ? window.dirigidosNew : [];
                if (!lista.length) {
                    const r = await fetch(`${base}/api/obtener-dirigidos-new`, { headers: { Accept: "application/json" } });
                    const j = await r.json();
                    lista = (j?.data || []).map((d) => ({ codigo: d.codigo, texto: d.texto }));
                }
                fill("#ddb_dirigido_a", lista, (d) => d.codigo, (d) => `${d.codigo} - ${d.texto}`);
                const sel = $("#ddb_dirigido_a");
                if (sel && ![...sel.options].some((o) => o.value === "OTROS")) {
                    const op = document.createElement("option");
                    op.value = "OTROS";
                    op.textContent = "OTROS - Otros";
                    sel.appendChild(op);
                }
            } catch {}
            // Clientes (igual que wizard: get-clientes-pac)
            try {
                const r = await fetch(`${base}/api/get-clientes-pac`, { headers: { Accept: "application/json" } });
                const j = await r.json();
                const arr = Array.isArray(j) ? j : j?.data || [];
                fill("#ddb_cod_cliente", arr, (c) => c.cod_legacy ?? c.codigo, (c) => `${c.codigo} - ${c.descripcion}`);
            } catch {}
            // Sucursales (igual que wizard: obtener-sucursales)
            try {
                const r = await fetch(`${base}/api/obtener-sucursales`, { headers: { Accept: "application/json" } });
                const j = await r.json();
                fill("#ddb_sucursal", j?.data || [], (s) => s.Codigo, (s) => `${s.Codigo} - ${s.Sucursal}`);
            } catch {}
            // tipo_curso (planes del wizard: get-capacitacion-tipo-cursos)
            try {
                const r = await fetch(`${base}/api/get-capacitacion-tipo-cursos`, { headers: { Accept: "application/json" } });
                const j = await r.json();
                const arr = Array.isArray(j) ? j : j?.data || [];
                fill("#ddb_tipo_curso", arr, (t) => t.codigo, (t) => `${t.codigo} - ${t.descripcion}`);
            } catch {}
            // area_conocimiento (sistemas del wizard: obtener-capacitacion-sistemas)
            try {
                const r = await fetch(`${base}/api/obtener-capacitacion-sistemas`, { headers: { Accept: "application/json" } });
                const j = await r.json();
                const arr = Array.isArray(j) ? j : j?.data || [];
                fill("#ddb_area_conocimiento", arr, (a) => a.codigo, (a) => `${a.codigo} - ${a.abreviatura || a.descripcion}`);
            } catch {}
            // area (áreas responsables del wizard: obtener-areas)
            try {
                const r = await fetch(`${base}/api/obtener-areas`, { headers: { Accept: "application/json" } });
                const j = await r.json();
                fill("#ddb_area", j?.areas || [], (a) => a.codArea ?? a.codigo, (a) => `${a.codArea ?? a.codigo} - ${a.Area || a.nombre}`);
            } catch {}
        }

        async function abrirDirectoBD() {
            errBox.style.display = "none";
            errBox.textContent = "";
            if (typeof dlg.showModal === "function" && !dlg.open) {
                dlg.showModal();
            } else {
                dlg.setAttribute("open", "");
            }
            await cargarCombos();
        }
        window.abrirModalDirectoBDNew = abrirDirectoBD;

        $("#frmDirectoBDNew").addEventListener("submit", async (e) => {
            e.preventDefault();
            errBox.style.display = "none";
            const nombre = $("#ddb_nombre").value.trim();
            if (!nombre) {
                errBox.textContent = "El campo nombre es obligatorio.";
                errBox.style.display = "block";
                return;
            }
            const btnSave = $("#ddbGuardar");
            btnSave.disabled = true;
            btnSave.textContent = "Guardando…";
            try {
                const payload = {
                    codigo_curso: $("#ddb_codigo_curso").value.trim() || null,
                    nombre,
                    creado_por: $("#ddb_creado_por").value.trim() || null,
                    fecha_creacion: $("#ddb_fecha_creacion").value || null,
                    modificado_por: $("#ddb_modificado_por").value.trim() || null,
                    fecha_modificacion: $("#ddb_fecha_modificacion").value || null,
                    habilitado: $("#ddb_habilitado").checked ? 1 : 0,
                    es_periodico: $("#ddb_es_periodico").checked ? 1 : 0,
                    aplica_evaluacion: $("#ddb_aplica_evaluacion").checked ? 1 : 0,
                    obligatorio_alta: $("#ddb_obligatorio_alta").checked ? 1 : 0,
                    es_demanda: $("#ddb_es_demanda").checked ? 1 : 0,
                    cod_responsable: selOrNull("#ddb_cod_responsable"),
                    codigo_moodle: $("#ddb_codigo_moodle").value.trim() || null,
                    descripcion: $("#ddb_descripcion").value.trim() || null,
                    dirigido_a: selOrNull("#ddb_dirigido_a"),
                    cod_cliente: selOrNull("#ddb_cod_cliente"),
                    sucursal: selOrNull("#ddb_sucursal"),
                    tipo_curso: selOrNull("#ddb_tipo_curso"),
                    area_conocimiento: selOrNull("#ddb_area_conocimiento"),
                    area: selOrNull("#ddb_area"),
                    categoria: selOrNull("#ddb_categoria"),
                };
                const res = await fetch(`${base}/api/guardar-curso-directo-new`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json", Accept: "application/json", ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}) },
                    body: JSON.stringify(payload),
                });
                const out = await res.json().catch(() => ({}));
                if (!res.ok || out?.success !== true) {
                    const det = out?.errors ? Object.entries(out.errors).map(([k, v]) => `${k}: ${(Array.isArray(v) ? v : [v]).join(", ")}`).join(" | ") : "";
                    throw new Error(out?.message || `Error HTTP ${res.status}` + (det ? ` — ${det}` : ""));
                }
                dlg.close();
                try {
                    await table.setData(`${base}/api/obtener-cursos-new`);
                } catch {}
                alert(`Curso ${out.codigo_curso} registrado directo en BD.`);
            } catch (err) {
                errBox.textContent = err?.message || "No se pudo guardar.";
                errBox.style.display = "block";
            } finally {
                btnSave.disabled = false;
                btnSave.textContent = "Guardar directo";
            }
        });
    }
});
