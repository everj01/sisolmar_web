import Swal from "sweetalert2";
import axios from "axios";
import imageCompression from "browser-image-compression";

/**
 * Modal Nuevo — Registrar curso (vista gestion_cursos_new).
 * Componente Alpine independiente: window.formCursoNew()
 * Mantiene los mismos endpoints que gestion_cursos.js:
 *  - GET get-capacitacion-tipo-cursos (planes)
 *  - GET obtener-capacitacion-sistemas (sistemas de gestión)
 *  - GET obtener-areas-por-sistema/{id} / obtener-areas (áreas responsables)
 *  - GET get-areas-encargadas / get-clientes-pac / obtener-sucursales / listar-jefaturas
 *  - POST capacitacion/procesar-examen-word (Word -> preguntas)
 *  - POST save-cursos (guardado multipart)
 * Al abrir el modal se precargan sucursales, áreas, planes, dirigido a, etc.
 */

window.abrirModalRegistroNew = () => {
    const el = document.querySelector('#modalRegistroCursoNew [x-data^="formCursoNew"]')
        || document.getElementById("formCursoNewRoot");
    if (el && window.Alpine) {
        try {
            const data = window.Alpine.$data(el);
            if (data && typeof data.abrir === "function") {
                data.abrir();
                return;
            }
        } catch (e) {
            console.warn("No se pudo obtener Alpine data, se abre por evento", e);
        }
    }
    window.dispatchEvent(new CustomEvent("open-modal-registro-new"));
};

window.cerrarModalRegistroNew = () => {
    window.dispatchEvent(new CustomEvent("close-modal-registro-new"));
};

window.formCursoNew = function () {
    return {
        // ── Modal / pasos ──
        showModal: false,
        paso: 1,
        totalPasos: 3,
        cargandoCombos: false,
        combosError: "",
        puntosCarga: 3,
        intervaloCarga: null,

        // ── Paso 1: datos generales ──
        nombre: "",
        descripcion: "",
        categoria: "",
        codResponsable: "",
        personalJefaturas: [],
        esPeriodico: false,
        frecuencia: "",

        // ── Paso 2: planificación ──
        planes: [],
        tipoCurso: "5",
        esPAC: false,
        clientesDisponibles: [],
        clienteSeleccionado: "",
        busquedaCliente: "",
        areasEncargadas: [],
        areasAsignadas: [],
        busquedaAreaPCI: "",
        sucursalesDisponibles: [],
        sucursalesAsignadas: [],
        busquedaSucursal: "",
        sucursalesOpciones: [],
        sistemas: [],
        areaConocimiento: "",
        area: "",
        areasResponsables: [],
        areaResponsable: "",
        codMoodleArea: "",
        sucursal: "",
        dirigido: "",
        lastSistemaId: null,

        // ── Paso 3: evaluación + recursos ──
        aplicaEvaluacion: true,
        limiteTiempo: "30",
        nota: "10",
        intentos: "1",
        cantidadPreguntas: "1",
        preguntasBalotario: "1",
        archivoWord: null,
        archivoWordNombre: "",
        cargandoWord: false,
        preguntasExamen: [],
        wordMetrics: { tokensInput: 0, tokensOutput: 0, tokensTotal: 0, costoUSD: 0, tiempoSeg: 0 },
        imageFilePortada: null,
        imagePreviewPortada: null,
        imageFileAfiche: null,
        imagePreviewAfiche: null,
        modalPreviewAbierto: false,
        modalPreviewSrc: "",
        modalPreviewTitulo: "",

        // ── Computed ──
        get dirigidosBase() {
            const raw = window.dirigidosNew || [];
            return Array.isArray(raw) ? raw : [];
        },
        get opcionesDirigido() {
            // Misma regla que el modal original:
            // tipo 5 -> lista fija / tipo 6 (PCU) -> según cliente / resto -> catálogo completo
            if (this.tipoCurso == "5") {
                return [
                    { codigo: "1", texto: "Todos" },
                    { codigo: "7", texto: "Personal Administrativo (Todos)" },
                    { codigo: "8", texto: "Personal Administrativo (4°)" },
                    { codigo: "9", texto: "Personal Administrativo (5°)" },
                    { codigo: "10", texto: "Personal Operativo (Todos)" },
                    { codigo: "11", texto: "Personal Operativo (4°)" },
                    { codigo: "12", texto: "Personal Operativo (5°)" },
                    { codigo: "OTROS", texto: "Otros" },
                ];
            }
            if (this.tipoCurso == "6") {
                const cliente = (this.clientesDisponibles || []).find((c) => String(c.codigo) === String(this.clienteSeleccionado));
                const desc = String(cliente?.descripcion || "").toLowerCase();
                if (desc.includes("linea 2") || desc.includes("línea 2")) {
                    return this.dirigidosBase.filter((o) => ["Todos", "PTSA", "Estaciones"].includes(o.texto));
                }
                if (this.clienteSeleccionado) {
                    const base = this.dirigidosBase.filter((o) => o.texto === "Todos");
                    return [...base, { codigo: "OTROS", texto: "Otros" }];
                }
            }
            return this.dirigidosBase;
        },
        get clientesFiltrados() {
            if (!this.busquedaCliente) return this.clientesDisponibles;
            const s = this.busquedaCliente.toLowerCase();
            return this.clientesDisponibles.filter(
                (c) => String(c.descripcion || "").toLowerCase().includes(s) || String(c.codigo || "").toLowerCase().includes(s)
            );
        },
        get sucursalesFiltradas() {
            if (!this.busquedaSucursal) return this.sucursalesDisponibles;
            const s = this.busquedaSucursal.toLowerCase();
            return this.sucursalesDisponibles.filter((x) => String(x).toLowerCase().includes(s));
        },
        get areasPCIFiltradas() {
            if (!this.busquedaAreaPCI) return this.areasEncargadas;
            const s = this.busquedaAreaPCI.toLowerCase();
            return this.areasEncargadas.filter(
                (a) => String(a.descripcion || "").toLowerCase().includes(s) || String(a.codigo || "").toLowerCase().includes(s)
            );
        },
        get puedeAvanzarPaso1() {
            const base = this.nombre.trim().length > 0 && !!this.categoria && !!this.codResponsable;
            if (!base) return false;
            // Si es periódico, la frecuencia es obligatoria (incluye PERSONALIZADO sin fechas extra:
            // las fechas se definen al aperturar el curso).
            if (this.esPeriodico && !this.frecuencia) return false;
            return true;
        },
        get puedeAvanzarPaso2() {
            if (!this.tipoCurso || !this.areaResponsable || !this.sucursal || !this.dirigido) return false;
            if (this.tipoCurso != "6" && !this.areaConocimiento) return false;
            if (this.tipoCurso == "6" && !this.clienteSeleccionado) return false;
            if (this.esPAC && this.sucursalesAsignadas.length === 0) return false;
            return true;
        },
        get formularioCompleto() {
            const base = this.puedeAvanzarPaso1 && this.puedeAvanzarPaso2;
            if (!base) return false;
            if (!this.aplicaEvaluacion) return true;
            return (
                parseInt(this.limiteTiempo) >= 5 &&
                parseFloat(this.nota) >= 5 &&
                parseInt(this.intentos) >= 1 &&
                parseInt(this.cantidadPreguntas) >= 5 &&
                parseInt(this.preguntasBalotario) >= 5 &&
                !!this.archivoWord &&
                this.preguntasExamen.length > 0
            );
        },
        get tituloCamposFaltantes() {
            const f = [];
            if (!this.nombre.trim()) f.push("Nombre");
            if (!this.categoria) f.push("Tipo de curso");
            if (!this.codResponsable) f.push("Responsable");
            if (!this.tipoCurso) f.push("Plan de capacitación");
            if (this.tipoCurso != "6" && !this.areaConocimiento) f.push("Sistema de gestión");
            if (!this.areaResponsable) f.push("Área responsable");
            if (!this.sucursal) f.push("Sucursal");
            if (!this.dirigido) f.push("Dirigido a");
            if (this.aplicaEvaluacion) {
                if (parseInt(this.limiteTiempo) < 5) f.push("Tiempo (mín. 5)");
                if (parseFloat(this.nota) < 5) f.push("Nota (mín. 5)");
                if (parseInt(this.cantidadPreguntas) < 5) f.push("Preguntas (mín. 5)");
                if (!this.archivoWord || this.preguntasExamen.length === 0) f.push("Analizar Word");
            }
            return f.length ? "Faltan: " + f.join(", ") : "Completa los campos requeridos";
        },

        // ── Apertura / cierre ──
        get textoCarga() {
            return "Cargando información" + ".".repeat(this.puntosCarga);
        },
        iniciarTextoCarga() {
            this.puntosCarga = 3;
            if (this.intervaloCarga) clearInterval(this.intervaloCarga);
            this.intervaloCarga = setInterval(() => {
                this.puntosCarga = this.puntosCarga <= 1 ? 3 : this.puntosCarga - 1;
            }, 400);
        },
        detenerTextoCarga() {
            if (this.intervaloCarga) {
                clearInterval(this.intervaloCarga);
                this.intervaloCarga = null;
            }
        },
        async abrir() {
            this.limpiarCampos();
            this.paso = 1;
            this.showModal = true;
            document.body.style.overflow = "hidden";
            this.iniciarTextoCarga();
            await this.cargarCombosIniciales();
            this.detenerTextoCarga();
        },
        cerrar() {
            this.detenerTextoCarga();
            this.showModal = false;
            document.body.style.overflow = "";
            window.dispatchEvent(new CustomEvent("close-modal-registro-new"));
        },
        siguiente() {
            if (this.paso < this.totalPasos) this.paso++;
        },
        anterior() {
            if (this.paso > 1) this.paso--;
        },
        irPaso(n) {
            // Solo permite retroceder o avanzar si el paso actual es válido
            if (n < this.paso) this.paso = n;
            else if (n === 2 && this.puedeAvanzarPaso1) this.paso = 2;
            else if (n === 3 && this.puedeAvanzarPaso1 && this.puedeAvanzarPaso2) this.paso = 3;
        },

        // ── Combos (se cargan al abrir) ──
        async cargarCombosIniciales() {
            this.cargandoCombos = true;
            this.combosError = "";
            try {
                const [planesRes, sistemasRes, jefRes, sucRes, cliRes, areasEncRes] = await Promise.all([
                    axios.get(`${VITE_URL_APP}/api/get-capacitacion-tipo-cursos`).catch(() => null),
                    axios.get(`${VITE_URL_APP}/api/obtener-capacitacion-sistemas`).catch(() => null),
                    axios.get(`${VITE_URL_APP}/api/listar-jefaturas`).catch(() => null),
                    axios.get(`${VITE_URL_APP}/api/obtener-sucursales`).catch(() => null),
                    axios.get(`${VITE_URL_APP}/api/get-clientes-pac`).catch(() => null),
                    axios.get(`${VITE_URL_APP}/api/get-areas-encargadas`).catch(() => null),
                ]);
                if (planesRes?.data) this.planes = Array.isArray(planesRes.data) ? planesRes.data : [];
                if (sistemasRes?.data) {
                    const arr = Array.isArray(sistemasRes.data) ? sistemasRes.data : [];
                    this.sistemas = arr.map((a) => ({ codigo: a.codigo, descripcion: a.abreviatura || a.descripcion }));
                }
                if (jefRes?.data) this.personalJefaturas = jefRes.data.personal || [];
                if (sucRes?.data?.success) {
                    this.sucursalesOpciones = sucRes.data.data || [];
                    this.sucursalesDisponibles = (sucRes.data.data || []).map((s) => s.Sucursal);
                }
                if (cliRes?.data) this.clientesDisponibles = cliRes.data || [];
                if (areasEncRes?.data) this.areasEncargadas = areasEncRes.data || [];
            } catch (e) {
                console.error("Error cargando combos del modal nuevo:", e);
                this.combosError = "No se pudieron cargar algunos catálogos. Intenta cerrar y reabrir.";
            } finally {
                this.cargandoCombos = false;
            }
        },
        async cargarAreasResponsables(sistemaId) {
            if (!sistemaId) {
                this.areasResponsables = [];
                this.areaResponsable = "";
                this.lastSistemaId = null;
                return;
            }
            if (String(sistemaId) === String(this.lastSistemaId)) return;
            // Al cambiar de sistema, se invalida el área elegida hasta cargar la nueva lista
            this.areaResponsable = "";
            this.areasResponsables = [];
            try {
                this.lastSistemaId = sistemaId;
                const res = await axios.get(`${VITE_URL_APP}/api/obtener-areas-por-sistema/${sistemaId}`);
                if (res.data?.success) this.areasResponsables = res.data.areas || [];
            } catch (e) {
                console.error("Error cargando áreas responsables:", e);
                this.lastSistemaId = null;
            }
        },
        async cargarAreasResponsablesPCA() {
            try {
                const res = await axios.get(`${VITE_URL_APP}/api/obtener-areas`);
                if (res.data?.success && Array.isArray(res.data.areas)) this.areasResponsables = res.data.areas;
                this.lastSistemaId = null;
            } catch (e) {
                console.error("Error cargando áreas PCA:", e);
            }
        },
        checkEsPACByText(text) {
            this.esPAC = String(text || "").toUpperCase().includes("PAC");
            if (!this.esPAC) this.sucursalesAsignadas = [];
        },

        // ── Word / imágenes ──
        previewImage(src, titulo) {
            this.modalPreviewSrc = src;
            this.modalPreviewTitulo = titulo;
            this.modalPreviewAbierto = true;
        },
        async handleImageUpload(event, type = "portada") {
            let file = event.target.files?.[0];
            if (!file) return;
            const allowed = ["image/jpeg", "image/jpg", "image/png"];
            if (!allowed.includes(file.type)) {
                Swal.fire("Atención", "Solo se permiten imágenes .jpg, .jpeg o .png", "warning");
                event.target.value = "";
                return;
            }
            if (file.size > 1990 * 1024) {
                await Swal.fire({
                    title: "Imagen pesada",
                    text: "Supera 1.9 MB, se intentará comprimir automáticamente.",
                    icon: "info",
                    confirmButtonText: "Entendido",
                });
                try {
                    file = await imageCompression(file, { maxSizeMB: 1.9, maxWidthOrHeight: 1920, useWebWorker: true });
                } catch {
                    Swal.fire("Error", "No se pudo comprimir la imagen.", "error");
                    event.target.value = "";
                    return;
                }
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                if (type === "portada") {
                    this.imagePreviewPortada = e.target.result;
                    this.imageFilePortada = file;
                } else {
                    this.imagePreviewAfiche = e.target.result;
                    this.imageFileAfiche = file;
                }
            };
            reader.readAsDataURL(file);
        },
        async analizarExamenWord() {
            if (!this.archivoWord) return;
            const ext = this.archivoWord.name.split(".").pop().toLowerCase();
            if (ext === "doc" || ext === "dot") {
                Swal.fire({
                    title: "Formato deprecado",
                    html: "Abre el archivo y <b>guárdalo como .docx</b> antes de subirlo.",
                    icon: "warning",
                    confirmButtonText: "Entendido",
                });
                return;
            }
            this.cargandoWord = true;
            try {
                const fd = new FormData();
                fd.append("archivo", this.archivoWord);
                const res = await axios.post(`${VITE_URL_APP}/api/capacitacion/procesar-examen-word`, fd, {
                    headers: { "Content-Type": "multipart/form-data" },
                });
                if (res.data?.success) {
                    this.preguntasExamen = res.data.preguntas || [];
                    if (res.data.metrics) {
                        this.wordMetrics = {
                            tokensInput: res.data.metrics.tokens_input,
                            tokensOutput: res.data.metrics.tokens_output,
                            tokensTotal: res.data.metrics.tokens_total,
                            costoUSD: res.data.metrics.costo_usd,
                            tiempoSeg: res.data.metrics.tiempo_seg,
                        };
                    }
                    const count = this.preguntasExamen.length;
                    this.cantidadPreguntas = String(count);
                    this.preguntasBalotario = String(count);
                    this.verVistaPrevia();
                } else {
                    Swal.fire("Error", res.data?.message || "No se pudo procesar el Word", "error");
                }
            } catch (e) {
                console.error(e);
                Swal.fire("Error", "No se pudo procesar el archivo Word", "error");
            } finally {
                this.cargandoWord = false;
            }
        },
        verVistaPrevia() {
            if (!this.preguntasExamen.length) {
                Swal.fire("Sin preguntas", "Primero analiza un archivo Word.", "info");
                return;
            }
            window.dispatchEvent(
                new CustomEvent("abrir-modal-word-new", {
                    detail: {
                        preguntas: this.preguntasExamen,
                        nombreArc: this.archivoWordNombre,
                        metrics: this.wordMetrics,
                    },
                })
            );
        },

        // ── Guardado (POST save-cursos) ──
        async registrar(e) {
            e?.preventDefault?.();
            if (this.esPAC && this.sucursalesAsignadas.length === 0) {
                Swal.fire("Atención", "Debe asignar al menos una sucursal para cursos PAC", "warning");
                return;
            }
            if (this.tipoCurso == "6" && !this.clienteSeleccionado) {
                Swal.fire("Atención", "Debe seleccionar un cliente para cursos PCU", "warning");
                return;
            }
            if (!this.codResponsable) {
                Swal.fire("Atención", "Debe seleccionar un responsable", "warning");
                return;
            }
            if (!this.areaResponsable) {
                Swal.fire("Atención", "Debe seleccionar un área responsable", "warning");
                return;
            }
            if (!this.dirigido) {
                Swal.fire("Atención", "Debe seleccionar a quién va dirigido el curso", "warning");
                return;
            }
            if (!this.nombre.trim() || !this.tipoCurso || !this.categoria) {
                Swal.fire("Atención", "Completa los campos obligatorios (*)", "warning");
                return;
            }
            if (this.aplicaEvaluacion) {
                const ok =
                    parseInt(this.limiteTiempo) >= 5 &&
                    parseFloat(this.nota) >= 5 &&
                    parseInt(this.intentos) >= 1 &&
                    parseInt(this.cantidadPreguntas) >= 5 &&
                    parseInt(this.preguntasBalotario) >= 5;
                if (!ok) {
                    Swal.fire("Atención", "Revisa los datos del examen (mínimos requeridos).", "warning");
                    return;
                }
                if (!this.archivoWord || this.preguntasExamen.length === 0) {
                    Swal.fire("Atención", "Adjunta un Word (.docx) y analízalo para crear el curso con examen.", "warning");
                    return;
                }
            }

            const fd = new FormData();
            fd.append("nombre", this.nombre);
            fd.append("tipo_curso", this.tipoCurso);
            fd.append("categoria", this.categoria);
            fd.append("area_conocimiento", this.areaConocimiento);
            fd.append("area", this.area);
            fd.append("frecuencia", this.frecuencia);
            fd.append("es_periodico", this.esPeriodico ? 1 : 0);
            fd.append("aplica_evaluacion", this.aplicaEvaluacion ? 1 : 0);
            fd.append("obligatorio_alta", 1);
            fd.append("es_demanda", 0);
            fd.append("target_group", "TODOS");
            if (this.aplicaEvaluacion) {
                fd.append("tiempo", this.limiteTiempo);
                fd.append("nota", this.nota);
                fd.append("intentos", this.intentos);
                fd.append("cantidad_preguntas", this.cantidadPreguntas);
                fd.append("preguntas_balotario", this.preguntasBalotario);
                if (this.preguntasExamen.length > 0) fd.append("preguntas_word", JSON.stringify(this.preguntasExamen));
            }
            if (this.esPAC && this.sucursalesAsignadas.length > 0) {
                this.sucursalesAsignadas.forEach((s) => fd.append("sucursales_asignadas[]", s));
            }
            if (this.tipoCurso == "6" && this.clienteSeleccionado) {
                fd.append("sucursales_asignadas[]", this.clienteSeleccionado);
            }
            if (this.tipoCurso == "7" && this.areasAsignadas.length > 0) {
                this.areasAsignadas.forEach((a) => fd.append("sucursales_asignadas[]", a));
            }
            const resp = (this.personalJefaturas || []).find((p) => String(p.codigo) === String(this.codResponsable));
            fd.append("cod_responsable", this.codResponsable);
            fd.append("area_responsable", this.areaResponsable);
            fd.append("cod_moodle_area", this.codMoodleArea);
            fd.append("descripcion", this.descripcion);
            fd.append("dirigido_a", this.dirigido);
            fd.append("sucursal", this.sucursal);
            if (this.archivoWord) fd.append("archivo", this.archivoWord);
            if (this.imageFilePortada) fd.append("image_portada", this.imageFilePortada);
            if (this.imageFileAfiche) fd.append("image_afiche", this.imageFileAfiche);
            if (resp?.nombre_completo) fd.append("nombre_responsable", resp.nombre_completo);

            Swal.fire({
                title: "Registrando curso...",
                html: "Por favor espera mientras se procesa la información.",
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading(),
            });
            try {
                const res = await axios.post(`${VITE_URL_APP}/api/save-cursos`, fd, {
                    headers: { "Content-Type": "multipart/form-data" },
                });
                if (res.status === 200 && res.data?.success) {
                    Swal.fire("Éxito", res.data.message || "Curso registrado correctamente", "success");
                    this.cerrar();
                    if (window.tablaCursosNew) {
                        try {
                            await window.tablaCursosNew.setData(`${VITE_URL_APP}/api/obtener-cursos-new`);
                        } catch {}
                    }
                } else {
                    Swal.fire("Error", res.data?.message || "No se pudo registrar el curso", "error");
                }
            } catch (err) {
                console.error(err);
                if (err.response?.status === 422) {
                    const errors = err.response.data.errors || {};
                    let html = '<ul class="text-left text-sm">';
                    for (const [k, msgs] of Object.entries(errors)) html += `<li><b>${k}:</b> ${msgs[0]}</li>`;
                    html += "</ul>";
                    Swal.fire({ title: "Errores de validación", html, icon: "warning" });
                } else {
                    Swal.fire("Error", "Ocurrió un problema al registrar el curso", "error");
                }
            }
        },

        limpiarCampos() {
            this.paso = 1;
            this.nombre = "";
            this.descripcion = "";
            this.categoria = "";
            this.codResponsable = "";
            this.esPeriodico = false;
            this.frecuencia = "";
            this.tipoCurso = "5";
            this.esPAC = false;
            this.clienteSeleccionado = "";
            this.busquedaCliente = "";
            this.areasAsignadas = [];
            this.busquedaAreaPCI = "";
            this.sucursalesAsignadas = [];
            this.busquedaSucursal = "";
            this.areaConocimiento = "";
            this.area = "";
            this.areaResponsable = "";
            this.codMoodleArea = "";
            this.sucursal = "";
            this.dirigido = "";
            this.aplicaEvaluacion = true;
            this.limiteTiempo = "30";
            this.nota = "10";
            this.intentos = "1";
            this.cantidadPreguntas = "1";
            this.preguntasBalotario = "1";
            this.archivoWord = null;
            this.archivoWordNombre = "";
            this.preguntasExamen = [];
            this.imageFilePortada = null;
            this.imagePreviewPortada = null;
            this.imageFileAfiche = null;
            this.imagePreviewAfiche = null;
        },

        init() {
            // Escucha apertura por evento (fallback si no hay Alpine.$data)
            window.addEventListener("open-modal-registro-new", async () => {
                this.limpiarCampos();
                this.paso = 1;
                this.showModal = true;
                document.body.style.overflow = "hidden";
                this.iniciarTextoCarga();
                await this.cargarCombosIniciales();
                this.detenerTextoCarga();
            });
            window.addEventListener("close-modal-registro-new", () => {
                this.showModal = false;
                document.body.style.overflow = "";
            });
            this.$watch("areaConocimiento", (val) => {
                if (this.tipoCurso != "6") this.cargarAreasResponsables(val);
            });
            this.$watch("tipoCurso", (val) => {
                if (val == "6") {
                    this.areaConocimiento = "";
                    this.area = "";
                    this.cargarAreasResponsablesPCA();
                } else {
                    this.areasResponsables = [];
                    this.areaResponsable = "";
                    this.lastSistemaId = null;
                }
            });
        },
    };
};

// ── Edición (modal simple, solo cursos inactivos) ──
// Regla: VIGENTE o PENDIENTE bloquean. Solo editable si no hay programación
// o todas están CERRADAS. Campos editables: nombre, descripción, tipo,
// responsable, periódico, frecuencia, sistema (PCE) y área responsable.
window.abrirModalEdicionNew = (cod) => {
    const el = document.getElementById("formCursoEditNewRoot");
    if (el && window.Alpine) {
        try {
            const data = window.Alpine.$data(el);
            if (data && typeof data.abrir === "function") {
                data.abrir(cod);
                return;
            }
        } catch (e) {
            console.warn("Edición: no se pudo usar Alpine.$data, uso evento", e);
        }
    }
    window.dispatchEvent(new CustomEvent("open-modal-edicion-new", { detail: { codigo: cod } }));
};

window.cerrarModalEdicionNew = () => {
    window.dispatchEvent(new CustomEvent("close-modal-edicion-new"));
};

window.formCursoEditNew = function () {
    return {
        showModal: false,
        cargando: false,
        guardando: false,
        error: "",
        codigo: "",
        planNombre: "—",
        tipoCursoCodigo: "",
        estadoProg: "",
        // editables
        nombre: "",
        descripcion: "",
        categoria: "",
        codResponsable: "",
        personalJefaturas: [],
        esPeriodico: false,
        frecuencia: "",
        sistemas: [],
        areaConocimiento: "",
        areasResponsables: [],
        areaResponsable: "",
        codMoodleArea: "",
        lastSistemaId: null,
        _original: {},

        get esPCU() {
            return String(this.tipoCursoCodigo) === "6";
        },
        get areaBloqueada() {
            return !this.esPCU && !this.areaConocimiento;
        },
        get formularioCompleto() {
            if (!this.nombre.trim() || !this.categoria || !this.codResponsable || !this.areaResponsable) return false;
            if (!this.esPCU && !this.areaConocimiento) return false;
            if (this.esPeriodico && !this.frecuencia) return false;
            return true;
        },
        get hayCambios() {
            const o = this._original || {};
            return ["nombre", "descripcion", "categoria", "codResponsable", "esPeriodico", "frecuencia", "areaConocimiento", "areaResponsable"]
                .some((k) => String(this[k] ?? "") !== String(o[k] ?? ""));
        },
        get tituloBoton() {
            if (!this.formularioCompleto) return "Completa los campos obligatorios (*)";
            if (!this.hayCambios) return "Sin cambios por guardar";
            return "";
        },

        async abrir(cod) {
            this.codigo = cod;
            this.error = "";
            this.showModal = true;
            document.body.style.overflow = "hidden";
            this.cargando = true;
            try {
                const [cursoRes, progRes, sistRes, jefRes] = await Promise.all([
                    axios.get(`${VITE_URL_APP}/api/get-curso-id/${cod}`),
                    axios.get(`${VITE_URL_APP}/api/get-curso-programacion/${cod}`).catch(() => null),
                    axios.get(`${VITE_URL_APP}/api/obtener-capacitacion-sistemas`).catch(() => null),
                    axios.get(`${VITE_URL_APP}/api/listar-jefaturas`).catch(() => null),
                ]);
                const curso = cursoRes?.data?.curso;
                if (!cursoRes?.data?.success || !curso) {
                    throw new Error("No se pudo cargar el curso.");
                }
                // ── Regla de negocio: bloquear VIGENTE / PENDIENTE ──
                const progs = progRes?.data?.programaciones || [];
                const estados = progs.map((p) => String(p.estado_periodo || "").toUpperCase());
                const hasVigente = estados.includes("VIGENTE") || curso.tiene_vigente === true;
                const hasPendiente = estados.includes("PENDIENTE");
                if (hasVigente || hasPendiente) {
                    this.showModal = false;
                    document.body.style.overflow = "";
                    Swal.fire(
                        "No se puede editar",
                        hasVigente
                            ? "El curso tiene una programación VIGENTE (aperturado). Solo se editan cursos inactivos."
                            : "El curso tiene una programación PENDIENTE. Solo se editan cursos inactivos (solo CERRADOS o sin programación).",
                        "warning"
                    );
                    return;
                }
                this.estadoProg = progs.length === 0 ? "Sin programación" : "Solo programaciones cerradas";

                // ── Combos ──
                if (sistRes?.data) {
                    const arr = Array.isArray(sistRes.data) ? sistRes.data : [];
                    this.sistemas = arr.map((a) => ({ codigo: a.codigo, descripcion: a.abreviatura || a.descripcion }));
                }
                if (jefRes?.data) this.personalJefaturas = jefRes.data.personal || [];

                // ── Datos del curso ──
                this.planNombre = curso.tipo_curso?.descripcion || "—";
                this.tipoCursoCodigo = curso.tipo_curso?.codigo ? String(curso.tipo_curso.codigo) : String(curso.tipo_curso || "");
                this.nombre = curso.nombre || "";
                this.descripcion = curso.descripcion || "";
                this.categoria = curso.categoria != null ? String(curso.categoria) : "";
                this.codResponsable = curso.cod_responsable ? String(curso.cod_responsable) : "";
                this.esPeriodico = Number(curso.es_periodico) === 1;
                this.frecuencia = curso.frecuencia || "";
                this.areaConocimiento = curso.area_conocimiento ? String(curso.area_conocimiento) : "";
                this.codMoodleArea = curso.cod_moodle_area || "";

                // Áreas responsables según plan
                if (this.esPCU) {
                    try {
                        const r = await axios.get(`${VITE_URL_APP}/api/obtener-areas`);
                        if (r.data?.success) this.areasResponsables = r.data.areas || [];
                    } catch {}
                    this.lastSistemaId = null;
                } else if (this.areaConocimiento) {
                    await this.cargarAreasResponsables(this.areaConocimiento, true);
                } else {
                    this.areasResponsables = [];
                }
                this.areaResponsable = curso.area != null ? String(curso.area) : "";

                this._original = {
                    nombre: this.nombre,
                    descripcion: this.descripcion,
                    categoria: this.categoria,
                    codResponsable: this.codResponsable,
                    esPeriodico: this.esPeriodico,
                    frecuencia: this.frecuencia,
                    areaConocimiento: this.areaConocimiento,
                    areaResponsable: this.areaResponsable,
                };
            } catch (e) {
                console.error("Error abriendo edición:", e);
                this.error = "No se pudo cargar la información del curso.";
            } finally {
                this.cargando = false;
            }
        },
        cerrar() {
            this.showModal = false;
            document.body.style.overflow = "";
            window.dispatchEvent(new CustomEvent("close-modal-edicion-new"));
        },
        async cargarAreasResponsables(sistemaId, forzar = false) {
            if (!sistemaId) {
                this.areasResponsables = [];
                this.areaResponsable = "";
                this.lastSistemaId = null;
                return;
            }
            if (!forzar && String(sistemaId) === String(this.lastSistemaId)) return;
            this.areaResponsable = "";
            this.areasResponsables = [];
            try {
                this.lastSistemaId = sistemaId;
                const res = await axios.get(`${VITE_URL_APP}/api/obtener-areas-por-sistema/${sistemaId}`);
                if (res.data?.success) this.areasResponsables = res.data.areas || [];
            } catch (e) {
                console.error("Error cargando áreas responsables:", e);
                this.lastSistemaId = null;
            }
        },
        async guardar(e) {
            e?.preventDefault?.();
            if (!this.formularioCompleto || !this.hayCambios || this.guardando) return;
            const o = this._original || {};
            const changed = (k) => String(this[k] ?? "") !== String(o[k] ?? "");
            const fd = new FormData();
            if (changed("nombre")) fd.append("nombre", this.nombre);
            if (changed("descripcion")) fd.append("descripcion", this.descripcion);
            if (changed("categoria")) fd.append("categoria", this.categoria);
            if (changed("codResponsable")) fd.append("cod_responsable", this.codResponsable);
            if (changed("areaConocimiento") && !this.esPCU) fd.append("area_conocimiento", this.areaConocimiento);
            if (changed("areaResponsable")) fd.append("area_responsable", this.areaResponsable);
            if (this.codMoodleArea) fd.append("cod_moodle_area", this.codMoodleArea);
            fd.append("es_periodico", this.esPeriodico ? 1 : 0);
            if (changed("frecuencia")) fd.append("frecuencia", this.frecuencia);
            this.guardando = true;
            try {
                const res = await axios.post(`${VITE_URL_APP}/api/actualizar-curso/${this.codigo}`, fd, {
                    headers: { "Content-Type": "multipart/form-data" },
                });
                if (res.data?.success) {
                    Swal.fire("Éxito", res.data.message || "Curso actualizado correctamente", "success");
                    this.cerrar();
                    if (window.tablaCursosNew) {
                        try {
                            await window.tablaCursosNew.setData(`${VITE_URL_APP}/api/obtener-cursos-new`);
                        } catch {}
                    }
                } else {
                    Swal.fire("Error", res.data?.message || "No se pudo actualizar el curso", "error");
                }
            } catch (err) {
                console.error(err);
                Swal.fire("Error", "Ocurrió un problema al actualizar el curso", "error");
            } finally {
                this.guardando = false;
            }
        },
        init() {
            window.addEventListener("open-modal-edicion-new", (e) => this.abrir(e.detail?.codigo));
            window.addEventListener("close-modal-edicion-new", () => {
                this.showModal = false;
                document.body.style.overflow = "";
            });
            this.$watch("areaConocimiento", (val) => {
                if (!this.esPCU && this.showModal && !this.cargando) this.cargarAreasResponsables(val);
            });
        },
    };
};

// Modal simple de revisión Word para la vista nueva (solo lectura/confirmación)
window.modalExamenWordNew = function () {
    return {
        mostrarModal: false,
        preguntas: [],
        preguntasOriginales: [],
        archivoNombre: "",
        abrirModalWord(preguntas, nombreArc) {
            this.preguntas = Array.isArray(preguntas) ? preguntas : [];
            this.preguntasOriginales = JSON.parse(JSON.stringify(this.preguntas));
            this.archivoNombre = nombreArc || "";
            this.mostrarModal = true;
        },
        undoChanges() {
            this.preguntas = JSON.parse(JSON.stringify(this.preguntasOriginales));
        },
        chr(code) {
            return String.fromCharCode(code);
        },
        init() {
            window.addEventListener("abrir-modal-word-new", (e) => {
                this.abrirModalWord(e.detail.preguntas, e.detail.nombreArc);
            });
        },
    };
};
