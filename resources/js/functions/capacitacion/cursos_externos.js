import axios from "axios";

/** Escapa texto antes de inyectarlo como HTML (datos provenientes de la API). */
function _escapeHtml(valor) {
    if (valor === null || valor === undefined) return "";
    return String(valor)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

export default document.addEventListener("alpine:init", () => {
    Alpine.data("cursosExternosApp", () => ({
        abrirModalCursosPortuarios() {
            window.dispatchEvent(new CustomEvent("abrir-cursos-portuarios"));
        },
    }));

    Alpine.data("modalCursosPortuarios", () => ({
        open: false,

        loadingSucursales: false,
        loadingTiposPers: false,

        sucursales: [],
        tiposPers: [],

        selectedSucursal: "",
        selectedTipoPersonal: "",
        selectedVigencia: "",
        selectedEstado: "",
        selectedVencimiento: "",

        init() {
            window.addEventListener("abrir-cursos-portuarios", () => {
                this.abrir();
            });
        },

        async abrir() {
            this.open = true;
            await this.cargarCatalogos();
        },

        async cargarCatalogos() {
            await Promise.all([
                this.cargarSucursales(),
                this.cargarTiposPers(),
            ]);
        },

        async cargarSucursales() {
            this.loadingSucursales = true;
            try {
                const { data } = await axios.get(
                    `${VITE_URL_APP}/api/obtener-sucursales`,
                );
                this.sucursales =
                    data.success && Array.isArray(data.data) ? data.data : [];
            } catch (e) {
                console.error("Error al cargar sucursales", e);
                this.sucursales = [];
            } finally {
                this.loadingSucursales = false;
            }
        },

        async cargarTiposPers() {
            this.loadingTiposPers = true;
            try {
                const { data } = await axios.get(
                    `${VITE_URL_APP}/api/obtener-tipos-pers`,
                );
                this.tiposPers =
                    data.success && Array.isArray(data.data) ? data.data : [];
            } catch (e) {
                console.error("Error al cargar tipos de personal", e);
                this.tiposPers = [];
            } finally {
                this.loadingTiposPers = false;
            }
        },

        /** Traduce el valor 1/2 de los dropdowns SI/NO a texto legible. */
        _siNo(valor) {
            if (valor === "1") return "SI";
            if (valor === "2") return "NO";
            return "Todos";
        },

        /** Resumen legible de los filtros seleccionados. */
        get resumenFiltros() {
            const sucursal = this.sucursales.find(
                (s) => String(s.Codigo) === String(this.selectedSucursal),
            );
            const tipo = this.tiposPers.find(
                (t) => String(t.CODIGO) === String(this.selectedTipoPersonal),
            );

            return [
                {
                    label: "Sucursal",
                    valor: sucursal ? sucursal.Sucursal : "Todas",
                },
                {
                    label: "Tipo de personal",
                    valor: tipo ? tipo.DESCRIPCION : "Todos",
                },
                { label: "Vigencia", valor: this._siNo(this.selectedVigencia) },
                { label: "Estado", valor: this._siNo(this.selectedEstado) },
                {
                    label: "Vencimiento",
                    valor: this.selectedVencimiento || "Sin fecha",
                },
            ];
        },

        /**
         * Placeholder: la generación del reporte aún no está conectada a un
         * endpoint backend. Muestra los filtros elegidos para validarlos.
         */
        generarReporte() {
            const filas = this.resumenFiltros
                .map(
                    (f) =>
                        `<div style="display:flex;justify-content:space-between;gap:24px;padding:5px 0;border-bottom:1px solid #f1f5f9;">
                            <span style="color:#64748b;">${_escapeHtml(f.label)}</span>
                            <strong style="color:#0f172a;text-align:right;">${_escapeHtml(f.valor)}</strong>
                        </div>`,
                )
                .join("");

            Swal.fire({
                title: "Reporte de cursos portuarios",
                html: `
                    <div style="text-align:left;font-size:13px;">${filas}</div>
                    <p style="margin-top:14px;font-size:12px;color:#94a3b8;line-height:1.5;">
                        La generación de este reporte estará disponible próximamente.
                    </p>`,
                icon: "info",
                confirmButtonText: "Entendido",
                customClass: { popup: "text-left" },
            });
        },

        cerrar() {
            this.open = false;
            this.selectedSucursal = "";
            this.selectedTipoPersonal = "";
            this.selectedVigencia = "";
            this.selectedEstado = "";
            this.selectedVencimiento = "";
        },
    }));
});
