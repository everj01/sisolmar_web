import { TabulatorFull as Tabulator } from "tabulator-tables";

document.addEventListener("DOMContentLoaded", function () {
    if (document.getElementById("tblTrabajosFallidos")) {
        new Tabulator("#tblTrabajosFallidos", {
            layout: "fitColumns",
            responsiveLayout: "collapse",
            placeholder: "No se encontraron trabajos fallidos.",
            pagination: "local",
            paginationSize: 10,
            paginationSizeSelector: [10, 20, 50, 100],
            tooltips: true,
        });
    }
});
