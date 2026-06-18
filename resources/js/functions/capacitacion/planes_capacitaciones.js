export default document.addEventListener("alpine:init", () => {
    Alpine.data("planesCapacApp", () => ({
        abrirPDF() {
            window.dispatchEvent(new CustomEvent("abrir-pdf-pce"));
        },
    }));

    Alpine.data("modalPDF", () => ({
        open: false,

        init() {
            window.addEventListener("abrir-pdf-pce", () => {
                this.open = true;
            });
        },

        cerrar() {
            this.open = false;
        },
    }));
});
