import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    base: '/apps/sisolmarweb/',
    plugins: [
        laravel({
            input: [
                //css
                'resources/scss/app.scss',
                'resources/css/app.css',
                'resources/css/icons.css',
                'resources/css/styles.css',
                'node_modules/jsvectormap/dist/css/jsvectormap.min.css',
                'node_modules/glightbox/dist/css/glightbox.min.css',
                'node_modules/jsvectormap/dist/css/jsvectormap.min.css',
                'node_modules/quill/dist/quill.core.css',
                'node_modules/quill/dist/quill.bubble.css',
                'node_modules/quill/dist/quill.snow.css',
                'node_modules/tabulator-tables/dist/css/tabulator.min.css', // ✅ Tabulator CSS
                'node_modules/tabulator-tables/dist/js/tabulator.min.js',   // ✅ Tabulator JS
                'node_modules/axios/dist/axios.min.js', // ✅ Axios global
                "node_modules/sweetalert2/dist/sweetalert2.min.css",
                "node_modules/sweetalert2/dist/sweetalert2.js",
                "node_modules/boxicons/dist/boxicons.js",
                'node_modules/vanilla-datatables/dist/vanilla-dataTables.min.js',
                'node_modules/vanilla-datatables/dist/vanilla-dataTables.min.css',
                //js
                'resources/js/app.js',
                'resources/js/pages/app-calendar.js',
                'resources/js/pages/dashboard.js',
                'resources/js/pages/gallery.js',
                'resources/js/pages/charts-apex.js',
                'resources/js/pages/maps-vector.js',
                'resources/js/pages/form-editor.js',
                'resources/js/pages/file-upload.js',
                'resources/js/pages/form-inputmask.js',
                /* FILE CONTROL */
                'resources/js/functions/cargo_.js',
                'resources/js/functions/cargo.js',
                'resources/js/functions/changeFilePers.js',
                'resources/js/functions/chargeFile.js',
                'resources/js/functions/folios.js',
                'resources/js/functions/legajo.js',
                'resources/js/functions/legajo_comercial.js',
                'resources/js/functions/legajo_.js',
                'resources/js/functions/legajos_pdf.js',
                'resources/js/functions/search_legajos.js',
                /* CAPACITACION */
                'resources/js/functions/capacitacion/gestion_cursos.js',
                'resources/js/functions/capacitacion/gestion_programacion.js',
            ],
            refresh: true,
        }),
    ],
});
