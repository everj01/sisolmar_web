// gestion_dj.js
import axios from 'axios';
import Swal from 'sweetalert2';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';

import Tagify from '@yaireo/tagify';
import '@yaireo/tagify/dist/tagify.css';
const API_URL = `${VITE_URL_APP}/api`;
document.addEventListener('DOMContentLoaded', function () {

    // =========================
    // REFERENCIAS DOM
    // =========================
    let registroSeleccionado = null;

    const modalDjGestion = document.getElementById('modalDjGestion');
    const form = document.getElementById('formDatos');

    const buscarPersonalInput = document.getElementById("buscarPersonal");
    const btnNuevaDJ = document.getElementById('btnNuevaDJ');
    const cerrarModalBtn = document.getElementById('cerrarModal');
    const btnPrevisualizar = document.getElementById("btnPrevisualizar");
    const pageSizeSelect = document.getElementById("page-size");
    const pageSizeMigradoSelect = document.getElementById("page-size-migrado");

    // Pestañas
    const tabBtnPendiente = document.getElementById('tabBtnPendiente');
    const tabBtnMigrado = document.getElementById('tabBtnMigrado');
    const panelPendiente = document.getElementById('panelPendiente');
    const panelMigrado = document.getElementById('panelMigrado');

    const container = document.getElementById('familyContainer');
    const addBtn = document.getElementById('addFamilyMember');

    const inputFoto = document.getElementById("inputFoto");
    const preview = document.getElementById("previewFoto");
    const placeholder = document.getElementById("placeholderFoto");
    const btnSubir = document.getElementById("btnSubirFoto");
    const btnEliminar = document.getElementById("btnEliminarFoto");

    const cursoSucamec = document.getElementById("curso_sucamec");
    const institucionContainer = document.getElementById("institucion_container");
    const institucionInput = document.getElementById("institucion_laboral");

    const departamentoSelect = document.getElementById("departamento_actual");
    const provinciaSelect = document.getElementById("provincia_actual");
    const distritoSelect = document.getElementById("distrito_actual");

    const departamentoSelectDni = document.getElementById("departamento_dni");
    const provinciaSelectDni = document.getElementById("provincia_dni");
    const distritoSelectDni = document.getElementById("distrito_dni");

    const nombreDJtxt = document.getElementById("nombres_apellidos");
    const dniDJtxt = document.getElementById("dni");
    const dniCaducaDJtxt = document.getElementById("caduca");
    const estadoCivilDJtxt = document.getElementById("estado_civil");
    const sexoDJtxt = document.getElementById("sexo");
    const fechaNacDJtxt = document.getElementById("fecha_nacimiento");
    const sabeNadarDJtxt = document.getElementById("sabe_nadar");

    const inputLicencia = document.getElementById("licencia_arma");
    const tagifyLicencia = inputLicencia
        ? new Tagify(inputLicencia, { maxTags: 2 })
        : null;

    const API_BASE = `${VITE_URL_APP}/api/ubicacion`;

    // =========================
    // TABLA 1: Pestaña Pendientes/Listos — SIN columna Migrado
    // =========================
    const tblPersonas = new Tabulator("#tblPersonas", {
        height: "100%",
        layout: "fitColumns",
        responsiveLayout: "collapse",
        pagination: true,
        paginationSize: 20,
        rowHeader: {
            formatter: "responsiveCollapse",
            width: 30,
            minWidth: 30,
            hozAlign: "center",
            resizable: false,
            headerSort: false
        },
        locale: "es",
        langs: {
            "es": {
                "pagination": {
                    "first": "Primero",
                    "first_title": "Primera Página",
                    "last": "Final",
                    "last_title": "Última Página",
                    "prev": "<",
                    "prev_title": "Página Anterior",
                    "next": ">",
                    "next_title": "Página Siguiente",
                    "all": "Todo"
                },
                "headerFilters": {
                    "default": "Filtrar...",
                },
                "ajax": {
                    "loading": "Cargando datos...",
                    "error": "Error al cargar datos"
                },
                "data": {
                    "empty": "No hay datos disponibles"
                }
            }
        },
        columns: [
            { title: "N°", formatter: "rownum", hozAlign: "center", width: 60 },
            {
                title: "Nombres",
                field: "nombres",
                hozAlign: "left",
                widthGrow: 3,
                formatter: function (cell) {
                    const data = cell.getData();
                    return `${data.nombres ?? ''} ${data.apellido1 ?? ''} ${data.apellido2 ?? ''}`.trim();
                }
            },
            { title: "DNI", field: "dni", hozAlign: "center", widthGrow: 2 },
            {
                title: "Estado",
                field: "estado",
                hozAlign: "center",
                widthGrow: 2,
                formatter: function (cell) {
                    const data = cell.getData();
                    let colorEstado = 'border-yellow-300 bg-yellow-100 text-yellow-800';
                    if(data.estado == 'listo'){
                        colorEstado = 'border-info bg-info text-white';
                    }
                    return `<span class="inline-flex items-center rounded-full border ${ colorEstado } px-3 py-1 text-sm font-medium ">
                    ${ capitalizeWords(data.estado ?? '') ?? '' }
                    </span>`.trim();
                }
            },
            {
                title: "Ultimo Cambio",
                field: "cambio",
                hozAlign: "center",
                widthGrow: 3,
                formatter: function (cell) {
                    const data = cell.getData();
                    console.log(data.cambio);
                    if(data.cambio != null ){
                            return `<div class="flex items-center justify-center gap-3 text-sm text-gray-700">
                            <span class="flex items-center gap-1">
                                 <i class='bx bx-calendar'></i> <span >${ formatearFechaHora(data.cambio).fecha }</span>
                            </span>
                            <span class="flex items-center gap-1">
                                 <i class='bx bx-time-five' ></i> <span >${ formatearFechaHora(data.cambio).hora }</span>
                            </span>
                            </div>`.trim();
                    }else{
                        return `${data.cambio ?? 'Sin cambios'}`.trim();
                    }
                }
            },
            {
                title: "Acciones",
                field: "acciones",
                hozAlign: "center",
                headerSort: false,
                widthGrow: 2,
                formatter: function (cell) {
                    const data = cell.getData();
                    if(data.estado == 'pendiente'){
                        return `<button 
                            type="button" 
                            class="btn rounded-full form-btn bg-success/25 text-success hover:bg-success hover:text-white">
                            DJ
                        </button>`;
                    }else{
                        return `<button 
                            type="button" 
                            class="btn rounded-full form-btn bg-success/25 text-success hover:bg-success hover:text-white">
                            DJ
                        </button>
                        <button 
                            type="button" 
                            class="btn rounded-full form-btn bg-info/25 text-info hover:bg-info hover:text-white ms-1" title="previsualizar">
                            <i class='bx bxs-file-pdf'></i>
                        </button>
                        `;
                    }         
                },
                cellClick: function (e, cell) {
                     const btn = e.target.closest('.form-btn');
                    if (!btn) return;

                    registroSeleccionado = cell.getRow().getData();
                    btnNuevaDJ?.click();
                }
            },
        ],
    });

    // =========================
    // TABLA 2: Pestaña Migración — SIN columna Estado, CON columna Migrado
    // =========================
    const tblPersonasMigrado = new Tabulator("#tblPersonasMigrado", {
        height: "100%",
        layout: "fitColumns",
        responsiveLayout: "collapse",
        pagination: true,
        paginationSize: 20,
        rowHeader: {
            formatter: "responsiveCollapse",
            width: 30,
            minWidth: 30,
            hozAlign: "center",
            resizable: false,
            headerSort: false
        },
        locale: "es",
        langs: {
            "es": {
                "pagination": {
                    "first": "Primero",
                    "first_title": "Primera Página",
                    "last": "Final",
                    "last_title": "Última Página",
                    "prev": "<",
                    "prev_title": "Página Anterior",
                    "next": ">",
                    "next_title": "Página Siguiente",
                    "all": "Todo"
                },
                "headerFilters": {
                    "default": "Filtrar...",
                },
                "ajax": {
                    "loading": "Cargando datos...",
                    "error": "Error al cargar datos"
                },
                "data": {
                    "empty": "No hay datos disponibles"
                }
            }
        },
        columns: [
            { title: "N°", formatter: "rownum", hozAlign: "center", width: 60 },
            {
                title: "Nombres",
                field: "nombres",
                hozAlign: "left",
                widthGrow: 3,
                formatter: function (cell) {
                    const data = cell.getData();
                    return `${data.nombres ?? ''} ${data.apellido1 ?? ''} ${data.apellido2 ?? ''}`.trim();
                }
            },
            { title: "DNI", field: "dni", hozAlign: "center", widthGrow: 2 },
            { title: "Sucursal", field: "sucursal", hozAlign: "center", widthGrow: 2 },
            { 
                title: "Tipo", 
                field: "tipoPer", 
                hozAlign: "center", 
                widthGrow: 2,
                formatter: function(cell) {
                    const val = cell.getValue() ?? '';
                    const color = val === 'OPERATIVO' 
                        ? 'border-blue-300 bg-blue-100 text-blue-800' 
                        : 'border-purple-300 bg-purple-100 text-purple-800';
                    return val 
                        ? `<span class="inline-flex items-center rounded-full border ${color} px-3 py-1 text-sm font-medium">${capitalizeWords(val)}</span>`
                        : '';
                }
            },
            {
                title: "Migrado",
                field: "migrado",
                hozAlign: "center",
                widthGrow: 2,
                formatter: function (cell) {
                    const data = cell.getData();
                    let colorEstado = 'border-dark-100 bg-dark-100 text-yellow-800';
                    if(data.migrado == 'Migrado'){
                        colorEstado = 'border-success bg-success text-white';
                    }
                    return `<span class="inline-flex items-center rounded-full border ${ colorEstado } px-3 py-1 text-sm font-medium ">
                    ${ capitalizeWords(data.migrado ?? '') ?? '' }
                    </span>`.trim();
                }
            },
            {
                title: "Ultimo Cambio",
                field: "cambio",
                hozAlign: "center",
                widthGrow: 3,
                formatter: function (cell) {
                    const data = cell.getData();
                    if(data.cambio != null ){
                            return `<div class="flex items-center justify-center gap-3 text-sm text-gray-700">
                            <span class="flex items-center gap-1">
                                 <i class='bx bx-calendar'></i> <span >${ formatearFechaHora(data.cambio).fecha }</span>
                            </span>
                            <span class="flex items-center gap-1">
                                 <i class='bx bx-time-five' ></i> <span >${ formatearFechaHora(data.cambio).hora }</span>
                            </span>
                            </div>`.trim();
                    }else{
                        return `${data.cambio ?? 'Sin cambios'}`.trim();
                    }
                }
            },
            {
                title: "Acciones",
                field: "acciones",
                hozAlign: "center",
                headerSort: false,
                widthGrow: 2,
                formatter: function (cell) {
                    const data = cell.getData();
                    if(data.migrado == 'Migrado'){
                        return `<button  disabled
                            type="button" 
                            class="btn rounded-full form-btn-migrado bg-success/25 text-success hover:bg-success hover:text-white"
                             data-hs-overlay="#modalDjGestion">
                            DJ
                        </button>`;
                    }else{
                        return `<button 
                            type="button" 
                            class="btn rounded-full form-btn-migrado bg-success/25 text-success hover:bg-success hover:text-white"
                             data-hs-overlay="#modalDjGestion">
                            DJ
                        </button>
                        
                        `;
                    }         
                },
                cellClick: function (e, cell) {
                    const btn = e.target.closest('.form-btn-migrado');
                    if (!btn) return;

                    btnNuevaDJ?.click();

                    // ✅ OBTENER DATOS DIRECTAMENTE DE TABULATOR
                    const rowData = cell.getRow().getData();
                    
                    // ✅ ENVIAR EL CODI_PERS
                    const codiPers = rowData.codPersonal || rowData.CODI_PERS || rowData.id;
                    
                    console.log('CODI_PERS:', codiPers);
                    console.log('Datos completos:', rowData);

                    abrirFormularioDJ(codiPers);
                }
            },
        ],
    });

    function formatearFechaHora(fechaStr) {
        const fecha = new Date(fechaStr);
        const dia = String(fecha.getDate()).padStart(2, '0');
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const anio = fecha.getFullYear();
        const horas = String(fecha.getHours()).padStart(2, '0');
        const minutos = String(fecha.getMinutes()).padStart(2, '0');
        return {
            fecha: `${dia}/${mes}/${anio}`,
            hora: `${horas}:${minutos}`
        };
    }

    function capitalizeWords(texto) {
        return texto
            .toLowerCase()
            .split(" ")
            .map(p => p.charAt(0).toUpperCase() + p.slice(1))
            .join(" ");
    }

    // =========================
    // TABLA DE COINCIDENCIAS
    // =========================
    const tblPersonasCN = new Tabulator("#tblPersonasCN", {
        height: "100%",
        layout: "fitDataFill",
        responsiveLayout: "collapse",
        columns: [
            { title: "Código", field: "CODI_PERS", hozAlign: "center", width: '10%' },
            { title: "Personal", field: "personal", hozAlign: "left", width: '30%' },
            { title: "Nro Documento", field: "nroDoc", hozAlign: "center", width: '15%' },
            { title: "Sucursal", field: "sucursal", hozAlign: "center", width: '18%' },
        ],
    });

    // =========================
    // HELPERS
    // =========================
    function setValue(id, value = '') {
        const el = document.getElementById(id);
        if (el) el.value = value ?? '';
    }

    function getValue(id) {
        const el = document.getElementById(id);
        return el ? (el.value || '') : '';
    }

    function limpiarPreviewFoto() {
        if (inputFoto) inputFoto.value = "";
        if (preview) {
            preview.src = "";
            preview.classList.add("hidden");
        }
        if (placeholder) placeholder.classList.remove("hidden");
        if (btnEliminar) btnEliminar.classList.add("hidden");
    }

    function actualizarInstitucionVisibility() {
        if (!cursoSucamec || !institucionContainer || !institucionInput) return;

        if (cursoSucamec.value === "SI") {
            institucionContainer.classList.remove("hidden");
        } else {
            institucionContainer.classList.add("hidden");
            institucionInput.value = "";
        }
    }

    function limpiarFormulario() {
        if (form) form.reset();

        if (tagifyLicencia) {
            tagifyLicencia.removeAllTags();
        }

        limpiarPreviewFoto();

        if (container) {
            container.innerHTML = '';
            container.insertAdjacentHTML('beforeend', makeFamilyRow());
        }

        if (institucionContainer) institucionContainer.classList.add("hidden");
        if (institucionInput) institucionInput.value = "";

        if (provinciaSelect) provinciaSelect.innerHTML = '<option value="">Seleccionar</option>';
        if (distritoSelect) distritoSelect.innerHTML = '<option value="">Seleccionar</option>';

        if (provinciaSelectDni) provinciaSelectDni.innerHTML = '<option value="">Seleccionar</option>';
        if (distritoSelectDni) distritoSelectDni.innerHTML = '<option value="">Seleccionar</option>';
    }

   function resaltarTexto(tabla, valor) {
    tabla.getRows().forEach(row => {
        row.getElement().querySelectorAll(".tabulator-cell").forEach((cell, i, cells) => {
            // ✅ Obtener el campo de la celda
            const field = cell.getAttribute('tabulator-field');
            
            // ✅ Si es la columna de acciones o migrado, no resaltar
            if (i === cells.length - 1 || field === 'migrado' || field === 'estado') {
                return;
            }

            const text = cell.textContent || '';
            if (valor && text.toLowerCase().includes(valor)) {
                const escaped = valor.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                const regex = new RegExp(`(${escaped})`, "gi");
                cell.innerHTML = text.replace(regex, "<span class='bg-warning/25'>$1</span>");
            } else {
                cell.innerHTML = text;
            }
        });
    });
}

    function makeFamilyRow() {
        return `
        <div class="family-row grid grid-cols-1 md:grid-cols-3 gap-4 p-4 border rounded-lg relative" data-familia-row>
            <div>
                <label class="text-sm font-medium inline-block mb-2">Parentesco</label>
                <select name="parentesco[]" class="form-select w-full">
                    <option value="">Seleccionar</option>
                    <option value="PADRE">Padre</option>
                    <option value="MADRE">Madre</option>
                    <option value="ESPOSO">Esposo</option>
                    <option value="ESPOSA">Esposa</option>
                    <option value="HIJO">Hijo</option>
                    <option value="HIJA">Hija</option>
                    <option value="HERMANO">Hermano</option>
                    <option value="HERMANA">Hermana</option>
                    <option value="ABUELO">Abuelo</option>
                    <option value="ABUELA">Abuela</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium inline-block mb-2">Apellidos y Nombres</label>
                <input type="text" name="apellidosNombres[]" class="form-input w-full" placeholder="Apellidos y nombres completos">
            </div>
            <div class="flex gap-2 items-end">
                <div class="flex-1">
                    <label class="text-sm font-medium inline-block mb-2">Fecha Nacimiento</label>
                    <input type="date" name="fechaNacimiento[]" class="form-input w-full">
                </div>
                <button type="button" class="remove-family self-end px-3 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200">
                    Eliminar
                </button>
            </div>
        </div>
        `;
    }

    async function cargarProvincias(selectProv, selectDist, departamentoId, selectedProvincia = null, selectedDistrito = null) {
        if (!selectProv || !selectDist) return;

        selectProv.innerHTML = '<option value="">Seleccionar</option>';
        selectDist.innerHTML = '<option value="">Seleccionar</option>';

        if (!departamentoId) return;

        try {
            const response = await axios.get(`${API_BASE}/provincias/${departamentoId}`);
            response.data.forEach(prov => {
                const option = new Option(prov.provi_descripcion, prov.provi_codigo);
                selectProv.add(option);
            });

            if (selectedProvincia) {
                selectProv.value = selectedProvincia;
                await cargarDistritos(selectDist, selectedProvincia, selectedDistrito);
            }
        } catch (error) {
            console.error("Error cargando provincias:", error);
        }
    }

    async function cargarDistritos(selectDist, provinciaId, selectedDistrito = null) {
        if (!selectDist) return;

        selectDist.innerHTML = '<option value="">Seleccionar</option>';

        if (!provinciaId) return;

        try {
            const response = await axios.get(`${API_BASE}/distritos/${provinciaId}`);
            response.data.forEach(dist => {
                const option = new Option(dist.dist_descripcion, dist.dist_codigo);
                selectDist.add(option);
            });

            if (selectedDistrito) {
                selectDist.value = selectedDistrito;
            }
        } catch (error) {
            console.error("Error cargando distritos:", error);
        }
    }

    // =========================
    // FUNCIONES GLOBALES
    // =========================
    window.abrirFormulario = async function (data = null) {
        registroSeleccionado = null;
        try {
            limpiarFormulario();

            if (modalDjGestion) {
                modalDjGestion.classList.remove('hidden');
            }

             console.log('HOLAAAA AQUI ESTA LA DATA ', data);

            if (data) {
               
                setValue("cod_postulante", data.id);
                setValue("nombres_apellidos", `${data.nombres ?? ''} ${data.apellido1 ?? ''} ${data.apellido2 ?? ''}`.trim());
                
                setValue("dni", data.dni);
                setValue("fecha_nacimiento", data.fecha_nacimiento);
                setValue("celular", data.celular);
                setValue("correo", data.correo);
                setValue("grado_instruccion", data.grado_instruccion);

                const sucamecValor = (data.sucamec && String(data.sucamec).toUpperCase() === "SI") ? "SI" : "NO";
                setValue("curso_sucamec", sucamecValor);

                actualizarInstitucionVisibility();

                if (departamentoSelect && data.departamento) {
                    departamentoSelect.value = data.departamento;
                    await cargarProvincias(
                        provinciaSelect,
                        distritoSelect,
                        data.departamento,
                        data.provincia ?? null,
                        data.distrito ?? null
                    );
                }

                let licencias = data.licencia_arma;

                if (typeof licencias === "string") {
                    try {
                        licencias = JSON.parse(licencias);
                    } catch (e) {
                        licencias = licencias ? [licencias] : [];
                    }
                }

                if (tagifyLicencia) {
                    tagifyLicencia.removeAllTags();
                    if (Array.isArray(licencias) && licencias.length > 0) {
                        tagifyLicencia.addTags(licencias);
                    }
                }
            } else {
                actualizarInstitucionVisibility();
            }

        } catch (error) {
            console.error("Error al abrir formulario:", error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Hubo un problema al abrir el formulario.'
            });
        }
    };

    window.cerrarFormulario = function () {
        registroSeleccionado = null;
    };

    // =========================
    // DATOS INICIALES
    // =========================
    function getPersonal() {
        axios.get(`${VITE_URL_APP}/api/get-personal-dj`)
        .then(response => {
            const datosTabla = response.data;
            tblPersonas.setData(datosTabla);
   
        })
        .catch(error => {
            console.error("Hubo un error:", error);
        });
    }

   function getPersonalMigracion() {
    axios.get(`${VITE_URL_APP}/api/get-personal-dj-migracion`)
    .then(response => {
        const datosTabla = response.data;
        tblPersonasMigrado.setData(datosTabla);

        const sucursales = [...new Map(
            datosTabla.filter(d => d.sucursal)
                .map(d => [d.codSucursal, { cod: d.codSucursal, nombre: d.sucursal }])
        ).values()];

        const filtroSucursal = document.getElementById('filtroSucursal');
        if (filtroSucursal) {
            filtroSucursal.innerHTML = '<option value="">Todas</option>';
            sucursales.sort((a, b) => a.nombre.localeCompare(b.nombre))
                .forEach(s => filtroSucursal.add(new Option(s.nombre, s.cod)));
        }

        // // ── Card: filtrados / total ──
        // animarContador(document.getElementById('contadorFiltrado'), 0, datosTabla.length);
        // animarContador(document.getElementById('contadorTotal'),    0, datosTabla.length);
        actualizarCardDesdeSP();
    })
    .catch(error => console.error("Hubo un error:", error));
}


function actualizarCardDesdeSP(sucursal = '', tipoPer = '') {
    const codSucursal = sucursal || '00';
    const codTipoPer  = tipoPer === 'OPERATIVO' ? '03' 
                      : tipoPer === 'ADMINISTRATIVO' ? '05' 
                      : '00';

    axios.get(`${VITE_URL_APP}/api/reporte-personal-sin-migracion`, {
        params: { codSucursal, codTipoPer }
    }).then(response => {
        if (!response.data.success) return;

        const datos  = response.data.data;
        const listos = datos.filter(d => d.SIP_CAMBIO === 'Ok').length;
        const total  = datos.length;

        const elFilt = document.getElementById('contadorFiltrado');
        const elTot  = document.getElementById('contadorTotal');

        animarContador(elFilt, parseInt(elFilt?.textContent.replace(/,/g,'')) || 0, listos);
        animarContador(elTot,  parseInt(elTot?.textContent.replace(/,/g,''))  || 0, total);
    }).catch(err => console.error('Error card SP:', err));
}

function aplicarFiltrosMigracion() {
    const sucursal = document.getElementById('filtroSucursal')?.value ?? '';
    const tipoPer  = document.getElementById('filtroTipoPer')?.value ?? '';

    const filtros = [];
    if (sucursal) filtros.push({ field: "codSucursal", type: "=", value: sucursal });
    if (tipoPer)  filtros.push({ field: "tipoPer",     type: "=", value: tipoPer });

    const texto = buscarPersonalInput?.value.toLowerCase().trim() ?? '';
    if (texto) {
        filtros.push([
            { field: "nombres", type: "like", value: texto },
            { field: "dni",     type: "like", value: texto },
        ]);
    }

    tblPersonasMigrado.setFilter(filtros);

    // ── Card siempre desde SP con filtros actuales ──
    actualizarCardDesdeSP(sucursal, tipoPer);
}
    document.getElementById('filtroSucursal')?.addEventListener('change', aplicarFiltrosMigracion);
    document.getElementById('filtroTipoPer')?.addEventListener('change', aplicarFiltrosMigracion);

//     document.getElementById('contadorFiltrado').textContent = filasFiltradas.length.toLocaleString();
// document.getElementById('contadorTotal').textContent = totalRegistros.toLocaleString();


    getPersonalMigracion();
    getPersonal();

    // =========================
    // LÓGICA DE PESTAÑAS
    // =========================
    let tabActiva = 'pendiente';

    function activarTab(tab) {
        tabActiva = tab;

        if (tab === 'pendiente') {
            panelPendiente.classList.remove('hidden');
            panelMigrado.classList.add('hidden');

            tabBtnPendiente.classList.add('bg-white', 'text-primary', 'border-gray-200');
            tabBtnPendiente.classList.remove('bg-gray-50', 'text-gray-500', 'border-transparent');

            tabBtnMigrado.classList.add('bg-gray-50', 'text-gray-500', 'border-transparent');
            tabBtnMigrado.classList.remove('bg-white', 'text-primary', 'border-gray-200');
        } else {
            panelPendiente.classList.add('hidden');
            panelMigrado.classList.remove('hidden');

            tabBtnMigrado.classList.add('bg-white', 'text-primary', 'border-gray-200');
            tabBtnMigrado.classList.remove('bg-gray-50', 'text-gray-500', 'border-transparent');

            tabBtnPendiente.classList.add('bg-gray-50', 'text-gray-500', 'border-transparent');
            tabBtnPendiente.classList.remove('bg-white', 'text-primary', 'border-gray-200');

            // Forzar re-render porque Tabulator se inicializó oculto
            tblPersonasMigrado.redraw(true);
        }
    }

    tabBtnPendiente?.addEventListener('click', () => activarTab('pendiente'));
    tabBtnMigrado?.addEventListener('click', () => activarTab('migrado'));

    // =========================
    // EVENTOS
    // =========================
    buscarPersonalInput?.addEventListener("keyup", function () {
    const valor = this.value.toLowerCase().trim();

    if (tabActiva === 'pendiente') {
        const filtro = [[
            { field: "nombres", type: "like", value: valor },
            { field: "dni",     type: "like", value: valor },
        ]];
        tblPersonas.setFilter(filtro);
        tblPersonas._ultimoFiltro = valor;
        setTimeout(() => resaltarTexto(tblPersonas, valor), 10);
    } else {
        // ✅ Usar función unificada que combina texto + filtros de select
        tblPersonasMigrado._ultimoFiltro = valor;
        aplicarFiltrosMigracion();
        setTimeout(() => resaltarTexto(tblPersonasMigrado, valor), 10);
    }
});

    tblPersonas.on("renderComplete", function () {
        if (tblPersonas._ultimoFiltro) {
            resaltarTexto(tblPersonas, tblPersonas._ultimoFiltro);
        }
    });

    tblPersonasMigrado.on("renderComplete", function () {
        if (tblPersonasMigrado._ultimoFiltro) {
            resaltarTexto(tblPersonasMigrado, tblPersonasMigrado._ultimoFiltro);
        }
    });

    btnNuevaDJ?.addEventListener('click', function () {
    if (registroSeleccionado && registroSeleccionado.codPersonal) {
        // Si hay un registro seleccionado de la tabla, usar su código
        abrirFormularioDJ(registroSeleccionado.codPersonal);
    } else {
        // Si no hay registro seleccionado, solo abrir modal vacío
        const modal = document.getElementById('modalDjGestion');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }
});

    document.addEventListener('click', function (event) {
        const modal = document.getElementById('modalDjGestion');
        if (!modal) return;

        const contenedor = modal.querySelector('.bg-white');
        if (!contenedor) return;

        if (event.target.closest('#btnNuevaDJ')) return;
    });

    cerrarModalBtn?.addEventListener('click', function () {
        cerrarFormulario();
    });

    addBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        if (container) {
            container.insertAdjacentHTML('beforeend', makeFamilyRow());
        }
    });

    container?.addEventListener('click', function (e) {
        const btn = e.target.closest('button.remove-family');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        const row = btn.closest('.family-row');
        if (row) row.remove();
    });

    cursoSucamec?.addEventListener("change", () => {
        actualizarInstitucionVisibility();
    });

    btnSubir?.addEventListener("click", () => {
        inputFoto?.click();
    });

    inputFoto?.addEventListener("change", () => {
        const file = inputFoto.files?.[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                if (preview) {
                    preview.src = e.target.result;
                    preview.classList.remove("hidden");
                }
                placeholder?.classList.add("hidden");
                btnEliminar?.classList.remove("hidden");
            };
            reader.readAsDataURL(file);
        }
    });

    btnEliminar?.addEventListener("click", () => {
        limpiarPreviewFoto();
    });

    // =========================
    // UBIGEOS
    // =========================
    if (departamentoSelect && departamentoSelectDni) {
        axios.get(`${API_BASE}/departamentos`)
            .then(response => {
                response.data.forEach(dep => {
                    const option1 = new Option(dep.depa_descripcion, dep.depa_codigo);
                    const option2 = new Option(dep.depa_descripcion, dep.depa_codigo);
                    departamentoSelect.add(option1);
                    departamentoSelectDni.add(option2);
                });
            })
            .catch(error => {
                console.error("Error cargando departamentos:", error);
            });
    }

    departamentoSelect?.addEventListener("change", async function () {
        await cargarProvincias(provinciaSelect, distritoSelect, this.value);
    });

    provinciaSelect?.addEventListener("change", async function () {
        await cargarDistritos(distritoSelect, this.value);
    });

    departamentoSelectDni?.addEventListener("change", async function () {
        await cargarProvincias(provinciaSelectDni, distritoSelectDni, this.value);
    });

    provinciaSelectDni?.addEventListener("change", async function () {
        await cargarDistritos(distritoSelectDni, this.value);
    });

    // =========================
    // PAGE SIZE
    // =========================
    pageSizeSelect?.addEventListener("change", function () {
        const size = parseInt(this.value);
        tblPersonas.setPageSize(size);
    });

    pageSizeMigradoSelect?.addEventListener("change", function () {
        const size = parseInt(this.value);
        tblPersonasMigrado.setPageSize(size);
    });

    // =========================
    // PREVISUALIZAR PDF
    // =========================
    btnPrevisualizar?.addEventListener("click", function (e) {
        e.preventDefault();
    
        const camposObligatorios = [
            { input: nombreDJtxt, nombre: 'Nombre' },
            { input: dniDJtxt, nombre: 'DNI' },
            { input: dniCaducaDJtxt, nombre: 'Caducidad de DNI' },
            { input: estadoCivilDJtxt, nombre: 'Estado civil' },
            { input: sexoDJtxt, nombre: 'Sexo' },
            { input: fechaNacDJtxt, nombre: 'Fecha de nacimiento' },
            { input: sabeNadarDJtxt, nombre: 'Sabe nadar' }
        ];

        const campoFaltante = camposObligatorios.find(campo => {
            return !campo.input || !String(campo.input.value ?? '').trim();
        });

        if (campoFaltante) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos obligatorios',
                text: `Falta completar: ${campoFaltante.nombre}`
            });

            campoFaltante.input?.focus();
            return;
        }

        if (!inputFoto.files || inputFoto.files.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Campo obligatorio',
                text: 'Debe selecionar una foto'
            });

            return;
        }

        generarDeclaracionJuradaPDF();
    });

    pageSizeSelect?.addEventListener("change", function () {
        const size = parseInt(this.value);
        tblPersonas.setPageSize(size);
    });

    async function drawFotoEnPDF(pdf, x, y, w, h) {
        try {
            let imageSrc = "";

            if (preview && preview.src && !preview.classList.contains("hidden")) {
                imageSrc = preview.src;
            }

            if (!imageSrc && inputFoto?.files?.[0]) {
                imageSrc = await new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = (e) => resolve(e.target.result);
                    reader.onerror = reject;
                    reader.readAsDataURL(inputFoto.files[0]);
                });
            }

            pdf.setDrawColor(0);
            pdf.setLineWidth(0.20);
            pdf.rect(x, y, w, h);

            if (!imageSrc) {
                pdf.setFontSize(8);
                pdf.setFont("helvetica", "normal");
                pdf.setTextColor(150);
                pdf.text("FOTO", x + w / 2, y + h / 2, { align: "center" });
                return;
            }

            pdf.setFillColor(255, 255, 255);
            pdf.rect(x, y, w, h, "F");
            pdf.setDrawColor(0);
            pdf.setLineWidth(0.20);
            pdf.rect(x, y, w, h);

            const props = pdf.getImageProperties(imageSrc);
            const imgW = props.width;
            const imgH = props.height;

            const ratio = Math.min(w / imgW, h / imgH);
            const finalW = imgW * ratio;
            const finalH = imgH * ratio;

            const offsetX = x + (w - finalW) / 2;
            const offsetY = y + (h - finalH) / 2;

            let format = "JPEG";
            if (imageSrc.startsWith("data:image/png")) {
                format = "PNG";
            } else if (imageSrc.startsWith("data:image/webp")) {
                format = "WEBP";
            }

            pdf.addImage(imageSrc, format, offsetX, offsetY, finalW, finalH);
        } catch (error) {
            console.error("Error dibujando foto en PDF:", error);

            pdf.setDrawColor(0);
            pdf.setLineWidth(0.20);
            pdf.rect(x, y, w, h);

            pdf.setFontSize(8);
            pdf.setFont("helvetica", "normal");
            pdf.setTextColor(150);
            pdf.text("FOTO", x + w / 2, y + h / 2, { align: "center" });
        }
    }

    async function generarDeclaracionJuradaPDF() {
        try {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({ unit: "mm", format: "a4", compress: true });

            const pageWidth = 210;
            const pageHeight = 297;
            const marginLeft = 10;
            const marginRight = 10;
            const marginTop = 10;
            const marginBottom = 10;
            const boxWidth = pageWidth - marginLeft - marginRight;
            const boxX = marginLeft;
            let y = marginTop;

            const colors = {
                headerText: [0, 0, 0],
                sectionBg: [220, 220, 220],
                sectionText: [0, 0, 0],
                labelBg: [220, 220, 220],
                labelText: [0, 0, 0],
                inputText: [0, 0, 0],
                borderColor: [0, 0, 0],
            };

            function fitText(text, maxWidth, initialFontSize = 8, minFontSize = 6) {
                pdf.setFontSize(initialFontSize);
                let textWidth = pdf.getTextWidth(text);
                let currentSize = initialFontSize;
                while (textWidth > maxWidth && currentSize > minFontSize) {
                    currentSize -= 0.3;
                    pdf.setFontSize(currentSize);
                    textWidth = pdf.getTextWidth(text);
                }
                return currentSize;
            }

            function getCleanSelectText(id) {
                const el = document.getElementById(id);
                if (!el) return "";
                const text = el.options[el.selectedIndex]?.text || "";
                const cleanText = text.toUpperCase();
                if (cleanText.includes("SELECCIONAR") || cleanText.includes("SELECCIONE")) {
                    return "";
                }
                return text;
            }

            function drawField(label, value, x, width, fieldY, inputHeight = 6, labelRatio = 0.35, alignValue = "left", omitTop = false, omitRight = false) {
                const labelWidth = width * labelRatio;
                const valueWidth = width * (1 - labelRatio);
                const labelPadding = 1;
                const valStr = String(value || "").toUpperCase();

                pdf.setFillColor(...colors.labelBg);
                pdf.rect(x, fieldY, labelWidth, inputHeight, "F");
                pdf.setFillColor(255, 255, 255);
                pdf.rect(x + labelWidth, fieldY, valueWidth, inputHeight, "F");

                pdf.setDrawColor(...colors.borderColor);
                pdf.setLineWidth(0.20);
                if (!omitTop) {
                    pdf.line(x, fieldY, x + width, fieldY);
                }
                pdf.line(x, fieldY, x, fieldY + inputHeight);
                if (!omitRight) {
                    pdf.line(x + width, fieldY, x + width, fieldY + inputHeight);
                }
                pdf.line(x, fieldY + inputHeight, x + width, fieldY + inputHeight);
                pdf.line(x + labelWidth, fieldY, x + labelWidth, fieldY + inputHeight);

                pdf.setFont("helvetica", "normal");
                pdf.setTextColor(...colors.labelText);
                pdf.setFontSize(8);
                const maxLabelW = labelWidth - 2;
                const labelTextWidth = pdf.getTextWidth(label);
                if (labelTextWidth <= maxLabelW) {
                    pdf.text(label, x + labelPadding, fieldY + inputHeight / 2 + 1, { align: "left" });
                } else {
                    const labelLines = pdf.splitTextToSize(label, maxLabelW);
                    const lblLineH = 8 * 0.3527 * 1.15;
                    const lblBlockH = labelLines.length * lblLineH;
                    const lblY = fieldY + (inputHeight - lblBlockH) / 2 + lblLineH;
                    pdf.text(labelLines, x + labelPadding, lblY, { align: "left", lineHeightFactor: 1.15 });
                }

                pdf.setFont("helvetica", "normal");
                pdf.setTextColor(...colors.inputText);
                const maxValW = valueWidth - (alignValue === "center" ? 1 : 2);
                const valFontSize = fitText(valStr, maxValW, 8, 6);
                pdf.setFontSize(valFontSize);
                const textY = fieldY + inputHeight / 2 + 1;
                const valX = alignValue === "center" ? x + labelWidth + valueWidth / 2 : x + labelWidth + 1;
                pdf.text(valStr, valX, textY, { maxWidth: maxValW, align: alignValue });
            }

            function drawSectionTitle(title, yPos) {
                pdf.setFillColor(...colors.sectionBg);
                pdf.rect(boxX, yPos, boxWidth, 5, "F");
                pdf.setDrawColor(...colors.borderColor);
                pdf.setLineWidth(0.20);
                pdf.rect(boxX, yPos, boxWidth, 5);

                pdf.setFontSize(8);
                pdf.setFont("helvetica", "bold");
                pdf.setTextColor(...colors.sectionText);
                pdf.text(title, boxX + boxWidth / 2, yPos + 3, { align: "center" });
            }

            function formatDateToDMY(fecha) {
                if (!fecha) return "";
                if (fecha instanceof Date) {
                    const y = fecha.getFullYear();
                    const m = String(fecha.getMonth() + 1).padStart(2, '0');
                    const d = String(fecha.getDate()).padStart(2, '0');
                    return `${d}/${m}/${y}`;
                }
                const fechaStr = String(fecha);
                if (!fechaStr.includes("-")) return fechaStr;
                const partes = fechaStr.split("-");
                return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : fechaStr;
            }

            function checkPageBreak(heightNeeded) {
                if (y + heightNeeded > pageHeight - marginBottom - 1) {
                    pdf.addPage();
                    y = marginTop;
                    return true;
                }
                return false;
            }

            const headerH = 19;
            const logoW = 30;
            const codeW = 20;
            const titleW = boxWidth - logoW - codeW;

            await drawLogo(boxX, y, logoW, headerH);

            const titleX = boxX + logoW;

            pdf.setFontSize(10);
            pdf.setTextColor(200, 0, 0);
            pdf.setFont("helvetica", "bold");
            pdf.text("SISTEMA INTEGRADO SOLMAR – SISOLMAR", titleX + titleW / 2, y + 6, { align: "center" });

            pdf.setFontSize(14);
            pdf.setTextColor(0, 0, 0);
            pdf.text("DECLARACION JURADA DEL TRABAJADOR", titleX + titleW / 2, y + 13, { align: "center" });

            const codeX = titleX + titleW;
            pdf.setFillColor(255, 255, 255);
            pdf.rect(codeX, y, codeW, headerH, "F");

            pdf.setDrawColor(0);
            pdf.setLineWidth(0.2);
            pdf.rect(boxX, y, boxWidth, headerH);
            pdf.line(boxX + logoW, y, boxX + logoW, y + headerH);
            pdf.line(codeX, y, codeX, y + headerH);
            pdf.setFontSize(18);
            pdf.setFont(undefined, "bold");
            pdf.setTextColor(0);
            pdf.text("RH 02", codeX + codeW / 2, y + 11, { align: "center" });

            y += headerH;

            const nombres = getValue("nombres_apellidos").toUpperCase().trim();
            const dni = getValue("dni").trim();

            pdf.setFontSize(8);
            const lineHeight = 3.5;
            const maxWidth = boxWidth - 4;
            let currentX = boxX + 2;
            let currentY = y + 3.5;

            const segments = [
                { text: "Yo, ", font: "normal" },
                { text: nombres, font: "bold" },
                { text: ", identificado con DNI ", font: "normal" },
                { text: dni, font: "bold" },
                { text: ", declaro bajo juramento que los datos personales, laborales y familiares que consigno en este documento son correctos, por lo que asumo la responsabilidad por su veracidad, cumplimiento y actualización, estando conforme con esta declaración jurada.", font: "normal" }
            ];

            let simX = 0;
            let simLines = 1;
            segments.forEach(seg => {
                pdf.setFont(undefined, seg.font);
                const words = seg.text.split(" ");
                words.forEach((word) => {
                    const wWidth = pdf.getTextWidth(word + " ");
                    if (simX + wWidth > maxWidth) {
                        simLines++;
                        simX = wWidth;
                    } else {
                        simX += wWidth;
                    }
                });
            });

            const declBoxH = (simLines * lineHeight) + 3;

            pdf.setDrawColor(0);
            pdf.setLineWidth(0.15);
            pdf.rect(boxX, y, boxWidth, declBoxH);

            currentX = boxX + 2;
            currentY = y + 3;

            segments.forEach(seg => {
                pdf.setFont(undefined, seg.font);
                const words = seg.text.split(/\s+/);

                words.forEach((word, i) => {
                    const wordWithSpace = word + ((i < words.length - 1) || seg.text.endsWith(" ") ? " " : "");
                    const wWidth = pdf.getTextWidth(wordWithSpace);

                    if (currentX + wWidth > boxX + maxWidth + 2) {
                        currentX = boxX + 2;
                        currentY += lineHeight;
                    }

                    pdf.text(word, currentX, currentY);
                    currentX += wWidth;
                });

                if (!seg.text.endsWith(" ") && !seg.text.startsWith(" ") && segments.indexOf(seg) < segments.length - 1) {
                    currentX += 1;
                }
            });

            y += declBoxH;

            drawSectionTitle("MIS DATOS PERSONALES", y);
            y += 5;

            const colMain = boxWidth - 35;
            const colFoto = 35;
            const rowH = 6.0;

            drawField("Nombres y Apellidos", nombres, boxX, colMain, y, rowH, 0.25);

            const fotoH = rowH * 6;
            await drawFotoEnPDF(pdf, boxX + colMain, y, colFoto, fotoH);
            y += rowH;

            const w1 = colMain / 4;
            drawField("DNI", dni, boxX, w1, y, rowH, 0.3);
            drawField("Caduca", getValue("caduca"), boxX + w1, boxWidth * 0.381 - w1, y, rowH, 0.461);
            drawField("Estado Civil", getValue("estado_civil"), boxX + boxWidth * 0.381, boxWidth * 0.6279 - boxWidth * 0.381, y, rowH, 0.589);
            drawField("Sexo", getValue("sexo"), boxX + boxWidth * 0.6279, colMain - boxWidth * 0.6279, y, rowH, 0.55);
            y += rowH;

            drawField("Fecha Nacimiento", formatDateToDMY(getValue("fecha_nacimiento")), boxX, boxWidth * 0.381, y, rowH, 0.394);
            drawField("Ciudad", getCleanSelectText("provincia_actual"), boxX + boxWidth * 0.381, colMain - boxWidth * 0.381, y, rowH, 0.334);
            y += rowH;

            const w3 = colMain / 4;
            drawField("Tipo Sangre", getValue("tipo_sangre"), boxX, w3, y, rowH, 0.735);
            drawField("Peso (Kg.)", getValue("peso"), boxX + w3, boxWidth * 0.381 - w3, y, rowH, 0.461);
            drawField("Talla (Mt.)", getValue("talla"), boxX + boxWidth * 0.381, boxWidth * 0.6279 - boxWidth * 0.381, y, rowH, 0.589);
            drawField("Celular", getValue("celular"), boxX + boxWidth * 0.6279, colMain - boxWidth * 0.6279, y, rowH, 0.55);
            y += rowH;

            const wMail = w3 * 3;
            drawField("Correo electrónico", getValue("correo"), boxX, boxWidth * 0.6279, y, rowH, 0.239);
            drawField("WhatsApp", getValue("whatsapp"), boxX + boxWidth * 0.6279, colMain - boxWidth * 0.6279, y, rowH, 0.55);
            y += rowH;

            const row6W = colMain;
            const row6LabelW = boxWidth * 0.5264;
            const row6InputW = row6W - row6LabelW;

            pdf.setFillColor(220);
            pdf.rect(boxX, y, row6LabelW, rowH, "F");
            pdf.setFillColor(255);
            pdf.rect(boxX + row6LabelW, y, row6InputW, rowH, "F");
            pdf.setDrawColor(0);
            pdf.setLineWidth(0.20);
            pdf.line(boxX, y, boxX + row6W, y);
            pdf.line(boxX, y, boxX, y + rowH);
            pdf.line(boxX + row6W, y, boxX + row6W, y + rowH);
            pdf.line(boxX, y + rowH, boxX + row6W, y + rowH);
            pdf.line(boxX + row6LabelW, y, boxX + row6LabelW, y + rowH);
            pdf.setTextColor(0);
            pdf.setFont(undefined, "normal");
            pdf.setFontSize(8);
            pdf.text("No estoy afiliado a ninguna AFP o ONP y deseo afiliarme a:", boxX + 2, y + 4);
            y += rowH;

            const sysPrev = getValue("sistema_previsional");
            const isAFP = sysPrev.includes("AFP");
            const isONP = sysPrev.includes("ONP");
            drawField("Estoy afiliado a la AFP", isAFP ? "X" : "", boxX, boxWidth * 0.5264, y, rowH, 0.3875, "center");
            drawField("Estoy afiliado a la ONP", isONP ? "X" : "", boxX + boxWidth * 0.5264, boxWidth * 0.4736, y, rowH, 0.441, "center");
            y += rowH;

            const drawAutoFitField = (label, value, x, w, y, h, labelPct) => {
                const labelW = w * labelPct;
                const valW = w - labelW;

                pdf.setFillColor(220);
                pdf.rect(x, y, labelW, h, "F");
                pdf.setFillColor(255);
                pdf.rect(x + labelW, y, valW, h, "F");

                pdf.setDrawColor(0);
                pdf.setLineWidth(0.20);
                pdf.rect(x, y, w, h);
                pdf.line(x + labelW, y, x + labelW, y + h);

                pdf.setFont(undefined, "normal");
                pdf.setTextColor(0);
                const lblFontSize = fitText(label, labelW - 2, 8, 6);
                pdf.setFontSize(lblFontSize);
                const maxLabelW = labelW - 2;
                const labelLines = pdf.splitTextToSize(label, maxLabelW);
                const lblLineH = lblFontSize * 0.3527 * 1.15;
                const lblBlockH = labelLines.length * lblLineH;
                const lblY = labelLines.length === 1
                    ? y + h / 2 + 1
                    : y + (h - lblBlockH) / 2 + lblLineH;
                pdf.text(labelLines, x + labelW / 2, lblY, { align: "center", lineHeightFactor: 1.15 });
                pdf.setFont(undefined, "normal");

                if (!value) return;

                let fontSize = 8;
                pdf.setFontSize(fontSize);
                const maxValW = valW - 2;

                while (pdf.getTextWidth(value) > maxValW && fontSize > 6) {
                    fontSize -= 0.3;
                    pdf.setFontSize(fontSize);
                }

                const MAX_LINES = 2;
                let lines = [value];

                if (pdf.getTextWidth(value) > maxValW) {
                    fontSize = 6;
                    pdf.setFontSize(fontSize);
                    const allLines = pdf.splitTextToSize(value, maxValW);

                    if (allLines.length <= MAX_LINES) {
                        lines = allLines;
                    } else {
                        lines = allLines.slice(0, MAX_LINES);
                        let last = lines[MAX_LINES - 1];
                        while (pdf.getTextWidth(last + "...") > maxValW && last.length > 1) {
                            last = last.slice(0, -1);
                        }
                        lines[MAX_LINES - 1] = last + "...";
                    }
                }

                const textX = x + labelW + valW / 2;
                const textY = lines.length === 1 ? y + h / 2 + 1 : y + 2;
                pdf.text(lines, textX, textY, { align: "center", lineHeightFactor: 1.1 });
            };

            const col1 = boxWidth * 0.285;
            const col2 = boxWidth * 0.2414;
            const col3 = (boxWidth * 0.5264 + boxWidth * 0.4736 * 0.441) - col1 - col2;
            const col4 = boxWidth - (boxWidth * 0.5264 + boxWidth * 0.4736 * 0.441);

            drawAutoFitField("Grado de Instrucción", getCleanSelectText("grado_instruccion"), boxX, col1, y, rowH, 0.526);
            drawAutoFitField("Institución", getCleanSelectText("institucion"), boxX + col1, col2, y, rowH, 0.398);
            drawAutoFitField("Carrera", getCleanSelectText("carrera"), boxX + col1 + col2, col3, y, rowH, 0.486);
            drawField("Año de egreso", getValue("anio_egreso"), boxX + col1 + col2 + col3, col4, y, rowH, 0.50);
            y += rowH;

            const embW = boxWidth * 0.381;
            const interbankStart = col1 + col2 + col3 * 0.486;
            const bcpW = interbankStart - embW;
            const bcpLabelRatio = ((wMail - embW) * 0.63) / bcpW;
            const interbankW = boxWidth - interbankStart;

            drawField("Embargos en instituciones financieras", getValue("embargos"), boxX, embW, y, rowH, 0.75);
            drawField("Cuenta sueldo BCP", "", boxX + embW, bcpW, y, rowH, bcpLabelRatio);
            drawField("Cuenta sueldo INTERBANK", "", boxX + interbankStart, interbankW, y, rowH, 0.644);
            y += rowH;

            drawField("Dirección Actual", getValue("direccion_actual"), boxX, boxWidth, y, rowH, 0.15);
            y += rowH;

            drawField("Dirección DNI", getValue("direccion_dni"), boxX, boxWidth, y, rowH, 0.15);
            y += rowH;

            drawField("En caso de Emergencia llamar a", getValue("contacto_emergencia"), boxX, boxWidth, y, rowH, 0.286);
            y += rowH;

            const wCelEmergencia = boxWidth * 0.5264;
            const wParEmergencia = boxWidth * 0.4736;
            drawField("Número de celular", getValue("celular_emergencia"), boxX, wCelEmergencia, y, rowH, 0.403);
            drawField("Parentesco", getValue("parentesco_emergencia"), boxX + wCelEmergencia, wParEmergencia, y, rowH, 0.25);
            y += rowH;

            checkPageBreak(5 * rowH + 5 + 3);
            drawSectionTitle("MIS DATOS LABORALES", y);
            y += 5;

            drawField("Profesión u Ocupación Principal", "", boxX, boxWidth * 0.5264, y, rowH, 0.475);
            drawField("Tiempo Experiencia", "", boxX + boxWidth * 0.5264, boxWidth * 0.4736, y, rowH, 0.4);
            y += rowH;

            drawField("Familiar en la Empresa", "", boxX, boxWidth * 0.25, y, rowH, 0.816);
            drawField("Nombre Completo", "", boxX + boxWidth * 0.25, boxWidth * 0.46584, y, rowH, 0.3);
            drawField("Parentesco", "", boxX + boxWidth * 0.71584, boxWidth * 0.28416, y, rowH, 0.4);
            y += rowH;

            const wLab3 = boxWidth / 6;
            drawField("SMO", getValue("smo"), boxX, wLab3, y, rowH, 0.4);
            drawField("Institución", getValue("institucion_laboral"), boxX + wLab3 * 0.75, wLab3 * 1.25, y, rowH, 0.379);
            drawField("Nº Brevete", getValue("brevete"), boxX + wLab3 * 1.85, wLab3 * 1.15, y, rowH, 0.522);
            drawField("Clase", getValue("clase_brevete"), boxX + wLab3 * 3, wLab3, y, rowH, 0.4);
            drawField("Tipo", "", boxX + wLab3 * 4, wLab3, y, rowH, 0.289);
            drawField("Vehículo Propio", getValue("vehiculo_propio"), boxX + boxWidth * 0.755, boxWidth * 0.245, y, rowH, 0.52);
            y += rowH;

            drawField("Empresa Anterior", getValue("empresa_anterior"), boxX, boxWidth * 0.375, y, rowH, 0.40);
            const cargoStart = boxX + wLab3 * 1.85;
            const duracionStart = boxX + boxWidth * 0.71584;
            drawField("Cargo", getValue("cargo_anterior"), cargoStart, duracionStart - cargoStart, y, rowH, 0.25);
            drawField("Duración", getValue("tiempo_servicio_anterior"), duracionStart, boxWidth - (duracionStart - boxX), y, rowH, 0.25);
            y += rowH;

            drawField("Profesión u Ocupación Alterna 1", "", boxX, boxWidth / 2, y, rowH, 0.45);
            drawField("Profesión u Ocupación Alterna 2", "", boxX + boxWidth / 2, boxWidth / 2, y, rowH, 0.45);
            y += rowH;

            checkPageBreak(40);
            drawSectionTitle("MIS DATOS FAMILIARES", y);
            y += 5;

            const fmC1 = boxWidth * 0.15;
            const fmC2 = boxWidth * 0.70;
            const fmC3 = boxWidth * 0.15;
            const fmHeaderH = rowH * 1.3;

            pdf.setFillColor(...colors.labelBg);
            pdf.rect(boxX, y, fmC1, fmHeaderH, "F");
            pdf.rect(boxX + fmC1, y, fmC2, fmHeaderH, "F");
            pdf.rect(boxX + fmC1 + fmC2, y, fmC3, fmHeaderH, "F");

            pdf.setDrawColor(0);
            pdf.setLineWidth(0.20);
            pdf.rect(boxX, y, boxWidth, fmHeaderH);
            pdf.line(boxX + fmC1, y, boxX + fmC1, y + fmHeaderH);
            pdf.line(boxX + fmC1 + fmC2, y, boxX + fmC1 + fmC2, y + fmHeaderH);

            pdf.setFontSize(8);
            pdf.setFont(undefined, "normal");
            pdf.text("Parentesco", boxX + fmC1 / 2, y + fmHeaderH / 2 + 1, { align: "center" });
            pdf.text("Apellidos y Nombres", boxX + fmC1 + fmC2 / 2, y + fmHeaderH / 2 + 1, { align: "center" });

            const fnLines = pdf.splitTextToSize("Fecha Nacimiento", fmC3 - 4);
            pdf.text(fnLines, boxX + fmC1 + fmC2 + fmC3 / 2, y + fmHeaderH / 2 - (fnLines.length > 1 ? 1.5 : 0) + 1, { align: "center" });
            y += fmHeaderH;

            const parentescos = document.getElementsByName("parentesco[]");
            const nombresFam = document.getElementsByName("apellidosNombres[]");
            const fechasFam = document.getElementsByName("fechaNacimiento[]");
            const rowCount = Math.max(parentescos.length, 5);

            for (let i = 0; i < rowCount; i++) {
                checkPageBreak(rowH);
                const par = parentescos[i]?.value || "";
                const nom = nombresFam[i]?.value || "";
                const fec = formatDateToDMY(fechasFam[i]?.value || "");

                pdf.setDrawColor(0);
                pdf.setLineWidth(0.15);
                pdf.rect(boxX, y, boxWidth, rowH);
                pdf.line(boxX + fmC1, y, boxX + fmC1, y + rowH);
                pdf.line(boxX + fmC1 + fmC2, y, boxX + fmC1 + fmC2, y + rowH);
                pdf.setTextColor(0);
                pdf.text(par.toUpperCase(), boxX + 2, y + 3);
                pdf.text(nom.toUpperCase(), boxX + fmC1 + 2, y + 3);
                pdf.text(fec, boxX + fmC1 + fmC2 + 2, y + 3);
                y += rowH;
            }

            checkPageBreak(60);
            drawSectionTitle("MI CONFORMIDAD CON LA DECLARACION JURADA", y);
            y += 5;

            pdf.setFontSize(8);
            pdf.setFont(undefined, "normal");
            const confText = "De acuerdo con lo dispuesto por mi empleador por norma interna, cumpliré con mi obligación de actualizar cada 12 meses esta Declaración Jurada y también hacerlo, cuando varíe cualquiera de mis datos registrados, asumiendo la responsabilidad en caso de incumplimiento.";

            const confLines = pdf.splitTextToSize(confText, boxWidth - 4);
            const confBoxH = confLines.length * 3.5 + 5;

            pdf.setDrawColor(0);
            pdf.setLineWidth(0.15);
            pdf.rect(boxX, y, boxWidth, confBoxH);
            pdf.text(confLines, boxX + 2, y + 4);
            y += confBoxH;

            const espacioDisponible = pageHeight - marginBottom - rowH - y - 2;
            const firmaH = Math.max(60, espacioDisponible);
            const firmaW = boxWidth * 0.6;
            const huellaW = boxWidth * 0.4;

            pdf.setLineWidth(0.20);
            pdf.rect(boxX, y, boxWidth, firmaH);
            pdf.line(boxX + firmaW, y, boxX + firmaW, y + firmaH);

            pdf.setFont(undefined, "bold");
            pdf.setFontSize(8);

            const footerY = y + firmaH - 5;
            pdf.text("Firma Registrada", boxX + firmaW / 2, footerY, { align: "center" });
            pdf.text("GRANDE Y CLARA SIMILAR AL DNI", boxX + firmaW / 2, footerY + 2.5, { align: "center" });

            pdf.text("Huella Registrada", boxX + firmaW + huellaW / 2, footerY, { align: "center" });
            pdf.text("INDICE DERECHO", boxX + firmaW + huellaW / 2, footerY + 2.5, { align: "center" });

            y += firmaH;

            const footerRowH = rowH;
            const fechaW = boxWidth * 0.25;
            const fechaValW = boxWidth * 0.15;
            const nombreLabelStart = boxX + fechaW + fechaValW;
            const nombreLabelEnd = boxX + firmaW;

            pdf.setFillColor(...colors.labelBg);
            pdf.rect(boxX, y, fechaW, footerRowH, "F");
            pdf.setFillColor(255);
            pdf.rect(boxX + fechaW, y, fechaValW, footerRowH, "F");
            pdf.setFillColor(...colors.labelBg);
            pdf.rect(nombreLabelStart, y, nombreLabelEnd - nombreLabelStart, footerRowH, "F");
            pdf.setFillColor(255);
            pdf.rect(nombreLabelEnd, y, boxWidth - (nombreLabelEnd - boxX), footerRowH, "F");

            pdf.setDrawColor(0);
            pdf.setLineWidth(0.20);
            pdf.rect(boxX, y, boxWidth, footerRowH);
            pdf.line(boxX + fechaW, y, boxX + fechaW, y + footerRowH);
            pdf.line(nombreLabelStart, y, nombreLabelStart, y + footerRowH);
            pdf.line(nombreLabelEnd, y, nombreLabelEnd, y + footerRowH);

            pdf.setTextColor(0);
            pdf.setFont(undefined, "normal");
            pdf.setFontSize(8);
            pdf.text("Fecha de la declaración", boxX + 2, y + 4);
            pdf.text(formatDateToDMY(new Date()), boxX + fechaW + 2, y + 4);
            pdf.text("Nombre", nombreLabelStart + 2, y + 4);
            pdf.text(getValue("trabajador"), nombreLabelEnd + 2, y + 4);

            async function drawLogo(x, y, w, h) {
                if (!window.logoUrl) {
                    pdf.setFontSize(8);
                    pdf.setTextColor(0);
                    pdf.setFont(undefined, "normal");
                    pdf.text("SOLMAR", x + w / 2, y + h / 2, { align: "center" });
                    return;
                }
                try {
                    const response = await fetch(window.logoUrl);
                    const blob = await response.blob();
                    const reader = new FileReader();
                    await new Promise(resolve => {
                        reader.onload = (e) => {
                            pdf.addImage(e.target.result, "PNG", x + 1, y + 1, w - 2, h - 2);
                            resolve();
                        };
                        reader.readAsDataURL(blob);
                    });
                } catch (e) {
                    console.error("error logo", e);
                    pdf.text("SOLMAR", x + w / 2, y + h / 2, { align: "center" });
                }
            }

            const f = new Date();
            const fechaHora =
                f.getFullYear() +
                String(f.getMonth() + 1).padStart(2, '0') +
                String(f.getDate()).padStart(2, '0') +
                '_' +
                String(f.getHours()).padStart(2, '0') +
                String(f.getMinutes()).padStart(2, '0');

            const nombreArchivo = `DJ_${dni}_${nombres.replace(/ /g, "-")}_${fechaHora}.pdf`;
            pdf.save(nombreArchivo);

        } catch (error) {
            console.error("Error al generar PDF:", error);
            Swal.fire({
                icon: 'error',
                title: 'Error de PDF',
                text: 'Hubo un error al generar el documento: ' + error.message,
            });
        }
    }

    // =========================
    // GUARDAR FORMULARIO
    // =========================
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btnGuardar = document.getElementById('btnGuardar');
            if (btnGuardar) btnGuardar.disabled = true;

            try {
                const formData = new FormData(form);
                
                // ✅ Convertir a objeto plano
                const data = Object.fromEntries(formData.entries());

                // ✅ AGREGAR ARRAYS DE FAMILIARES CON LOS NOMBRES QUE ESPERA EL BACKEND
                const payload = {
                    ...data,
                    // ✅ Nombres correctos para el backend
                    FAM_PARENTESCO: formData.getAll('parentesco[]'),
                    FAM_NOMBRES: formData.getAll('apellidosNombres[]'),
                    FAM_FECHA_NACI: formData.getAll('fechaNacimiento[]'),
                    
                    // ✅ Ocupaciones alternas
                    dj2026_descripcion: formData.getAll('ocupacion_alterna[]')
                };

                console.log('📤 Payload a enviar:', payload);

                // ✅ ENDPOINT CORRECTO
                const response = await axios.post(`${VITE_URL_APP}/api/dj/save-dj-completo`, payload);

                if (response.status === 200 || response.status === 201) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'La Declaración Jurada se guardó correctamente.'
                    });
                    
                    // ✅ Cerrar modal correctamente (limpiar backdrop de Preline)
                    const modal = document.getElementById('modalDjGestion');
                    if (modal) {
                        if (window.HSOverlay) {
                            try { HSOverlay.close(modal); } catch(e) {}
                        }
                        modal.classList.add('hidden');
                        modal.classList.remove('hs-overlay-open');
                        document.querySelectorAll('.hs-overlay-backdrop').forEach(el => el.remove());
                        document.body.classList.remove('overflow-hidden');
                        document.body.style.overflow = '';
                    }
                    
                    // ✅ Recargar tabla
                    getPersonal();
                    getPersonalMigracion();
                }
            } catch (error) {
                console.error("❌ Error al guardar DJ:", error);
                console.error("❌ Response:", error.response);
                
                let msg = 'Hubo un error al guardar los datos.';
                
                if (error.response?.data?.message) {
                    msg = error.response.data.message;
                } else if (error.response?.data?.errors) {
                    msg = Object.values(error.response.data.errors).flat().join('<br>');
                }
                
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Error', 
                    html: msg 
                });
            } finally {
                if (btnGuardar) btnGuardar.disabled = false;
            }
        });
    }


     

});


// async function abrirFormularioDJ(codiPers) {
//     try {

        
//         Swal.fire({
//             title: 'Cargando...',
//             allowOutsideClick: false,
//             didOpen: () => Swal.showLoading()
//         });

//         // 1. Cargar catálogos
//         await cargarCatalogos();

//         // 2. Cargar datos personales
//         await cargarDatosPersonales(codiPers);

//         Swal.close();

//         // 3. Abrir modal
//         const modal = document.getElementById('modalDjGestion');
//         if (modal) {
//             modal.classList.remove('hidden');
//         }

//     } catch (error) {
//         console.error('Error al abrir formulario DJ:', error);
//         Swal.fire({
//             icon: 'error',
//             title: 'Error',
//             text: 'No se pudo cargar el formulario'
//         });
//     }
// }

async function abrirFormularioDJ(codiPers) {
    console.log('🚀 1. Iniciando abrirFormularioDJ con código:', codiPers);
    
    try {
        Swal.fire({
            title: 'Cargando...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        console.log('🚀 2. Cargando catálogos...');
        await cargarCatalogos();
        console.log('✅ Catálogos cargados');

        console.log('🚀 3. Cargando datos personales...');
        await cargarDatosPersonales(codiPers);
        console.log('✅ Datos personales cargados');

        console.log('🚀 4. Cerrando Swal...');
        Swal.close();

        console.log('🚀 5. Abriendo modal...');
        const modal = document.getElementById('modalDjGestion');
        console.log('📦 Modal encontrado:', modal);
        
        if (modal) {
            if (window.HSOverlay) {
                HSOverlay.open(modal);
            } else {
                modal.classList.remove('hidden');
            }
            console.log('✅ MODAL ABIERTO CORRECTAMENTE');
        } else {
            console.error('❌ NO SE ENCONTRÓ EL MODAL #modalDjGestion');
        }

    } catch (error) {
        console.error('❌ ERROR GENERAL:', error);
        console.error('❌ Stack:', error.stack);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo cargar el formulario: ' + error.message
        });
    }
}

async function cargarCatalogos() {
    try {
        const response = await axios.get(`${API_URL}/dj/get-catalogs`);
        const { grados, carreras, instituciones, sangre, estados_civiles, tipos_arma } = response.data;

        populateSelect('#selGrado', grados);
        populateSelect('#selCarrera', carreras);
        populateSelect('#selInstitucion', instituciones);
        populateSelect('#PERS_GRUP_SANGRE', sangre);
        populateSelect('#PERS_ESTADO_CIVIL', estados_civiles);
        populateSelect('#LAB_TIPO_ARMA', tipos_arma);

        // Guardar carreras globalmente para filtrado
        window.allCarreras = carreras;

    } catch (error) {
        console.error('Error cargando catálogos:', error);
        throw error;
    }
}

async function cargarDatosPersonales(codiPers) {
    console.log('CODIGO PERSONAL:', codiPers);
    try {
        const response = await axios.get(`${API_URL}/dj/get-personal-data`, {
            params: { codi_pers: codiPers }
        });

        const { data, familiares, ocupaciones_alternas, is_migrado } = response.data;

        // Marcar si ya está migrado (opcional: deshabilitar edición)
        if (is_migrado) {
            console.log('✅ Este registro YA FUE MIGRADO a DJ2026_PERSONAL');
        }

        // Llenar campos del formulario
        llenarFormulario(data);

        // Llenar familiares
        renderFamiliares(familiares);

        // Llenar ocupaciones
        renderOcupaciones(ocupaciones_alternas);

    } catch (error) {
        console.error('Error cargando datos personales:', error);
        throw error;
    }
}

function llenarFormulario(data) {
    console.log('Datos personales recibidos:', data);
    // Identidad
    setValue('cod_postulante', data.CODI_PERS);
    setValue('#nombres_apellidos', `${data.NOMB_1 || ''} ${data.NOMB_2 || ''} ${data.APEL_1 || ''} ${data.APEL_2 || ''}`);
    setValue('#nombre1', data.NOMB_1 || '');
    setValue('#nombre2', data.NOMB_2 || '');
    setValue('#apellido_paterno', data.APEL_1 || '');
    setValue('#apellido_materno', data.APEL_2 || '');
    setValue('#dni', data.NRO_DOCU_IDEN);
    setValue('#caduca', data.PERS_FECHCADUCADNI); // ✅ FORMATEAR
    setValue('#estado_civil', data.ESCI_CODIGO);
    setValue('#sexo', data.PERS_SEXO || data.SEXO);
    setValue('#fecha_nacimiento', formatDateForInput(data.FECH_NACI)); // ✅ FORMATEAR
    setValue('#sabe_nadar', data.PERS_SNADAR);
    setValue('#dj2026_ciudad_naci', data.dj2026_ciudad_naci);

    // Contacto
    setValue('#celular', data.PERS_TELEFONO);
    setValue('#correo', data.PERS_EMAIL);
    setValue('#whatsapp', data.PERS_WHATSAPP);

    // Médica
    setValue('#tipo_sangre', data.tipo_sangr);
    setValue('#peso', data.peso_kilo);
    setValue('#talla', data.tall_metr);

    // Previsional
    setValue('#sistema_previsional', data.CODI_SIST_PENS);
    setValue('#essalud', data.ESSALUD);
    setValue('#pensionista', data.PERS_PENSIONISTA);

    // Educación
    setValue('#grado_instruccion', data.PERS_GRADO_INSTRUCCION);
    setValue('#institucion', data.IEDU_CODIGO);
    setValue('#carrera', data.CARR_CODIGO);
    setValue('#anio_egreso', data.EGRESO_EDUCATIVO);

    // Adicional
    setValue('#embargos', data.PERS_EMBARGO);
    setValue('#consumo_sustancias', data.PERS_CONSMO);
    setValue('#cuenta_banco', data.dj2026_banco);

    // Direcciones
    setValue('#direccion_actual', data.DIRECCION);
    setValue('#direccion_dni', data.PERS_DIREC_DNI);

    // ============================================
// DIRECCIONES Y UBICACIONES
// ============================================
console.log('🔄 Llenando DIRECCIONES Y UBICACIONES...');
setValue('direccion_actual', data.DIRECCION);
setValue('direccion_dni', data.PERS_DIREC_DNI);

// ✅ DEBUGGING UBICACIONES
console.log('═══════════════════════════════════════');
console.log('🗺️ UBICACIONES DESDE EL BACKEND:');
console.log('📍 Dirección Actual:');
console.log('   PERS_DEPT_ACT:', data.PERS_DEPT_ACT);
console.log('   PERS_PROV_ACT:', data.PERS_PROV_ACT);
console.log('   PERS_DIST_ACT:', data.PERS_DIST_ACT);
console.log('📍 Dirección DNI:');
console.log('   PERS_DPTO_DIRDNI:', data.PERS_DPTO_DIRDNI);
console.log('   PERS_PROV_DIRDNI:', data.PERS_PROV_DIRDNI);
console.log('   PERS_DIST_DIRDNI:', data.PERS_DIST_DIRDNI);
console.log('═══════════════════════════════════════');

// Cargar ubicaciones
console.log('🔄 Cargando ubicaciones cascada...');
cargarUbicaciones('actual', data.PERS_DEPT_ACT, data.PERS_PROV_ACT, data.PERS_DIST_ACT);
cargarUbicaciones('dni', data.PERS_DPTO_DIRDNI, data.PERS_PROV_DIRDNI, data.PERS_DIST_DIRDNI);

    // cargarUbicaciones('actual', data.PERS_DEPT_ACT, data.PERS_PROV_ACT, data.PERS_DIST_ACT);
    // cargarUbicaciones('dni', data.PERS_DPTO_DIRDNI, data.PERS_PROV_DIRDNI, data.PERS_DIST_DIRDNI);

    // Laboral
    
    setValue('#ocupacion_principal', data.dj2026_ocupacion_principal);
    const experiencia = data.dj2026_experiencia_anios;
    const experienciaLimpia = experiencia ? String(experiencia).replace(/[^0-9]/g, '') : '';
    setValue('#experiencia_anios', experienciaLimpia);
    
    setValue('#familiar_empresa', data.dj2026_familiar_empresa);
    setValue('#familiar_nombre', data.dj2026_familiar_nombre);
    setValue('#familiar_parentesco', data.dj2026_familiar_parentesco);

    setValue('#curso_sucamec', data.PERS_CONDISCAMEC);
    setValue('#sucamec_obs', data.PERS_NRODISCAMEC);
    setValue('#smo', data.PERS_SMO);

    setValue('#licencia_arma', data.PERS_CONLICARMAS);
    setValue('#tipo_arma', data.PERS_TIPOARMA);
    setValue('#arma_propia', data.PERS_CONARMAS);

    setValue('#brevete', data.PERS_BREVETE);
    setValue('#clase_brevete', data.CLASE_BREVETE);
    setValue('#tipo_vehiculo', data.PERS_TIPO_VEHICULO);
    setValue('#vehiculo_propio', data.PERS_VEHICULO_PROPIO);

    setValue('#empresa_anterior', data.PERS_CTRABANT);
    setValue('#cargo_anterior', data.PERS_CARGOTRABANT);
    setValue('#duracion_anterior', data.PERS_DURACIONANT);

    // Emergencia
    setValue('#contacto_emergencia', data.PERS_NOMCONTACTO);
    setValue('#celular_emergencia', data.PERS_NROEMERGENCIA);
    setValue('#parentesco_emergencia', data.PERS_CONYUGE);

    // Foto
    if (data.FOTO_PATH) {
        const img = document.getElementById('previewFoto');
        if (img) {
            img.src = data.FOTO_PATH;
            img.classList.remove('hidden');
        }
    }
}

function renderFamiliares(familiares) {
    console.log('═══════════════════════════════════════');
    console.log('👨‍👩‍👧‍👦 INICIANDO renderFamiliares');
    console.log('📦 Familiares recibidos:', familiares);
    
    const container = document.getElementById('familyContainer');
    console.log('📦 Container encontrado:', container);
    
    if (!container) {
        console.error('❌ NO SE ENCONTRÓ #familyContainer - SALTANDO renderFamiliares');
        console.log('═══════════════════════════════════════');
        return;
    }

    // Limpiar container
    container.innerHTML = '';
    console.log('🧹 Container limpiado');

    // Unir todos los familiares
    const allFam = [
        ...(familiares.padres || []),
        ...(familiares.madre || []),
        ...(familiares.hijos || []),
        ...(familiares.conyugue || [])
    ];

    console.log('📊 Total de familiares:', allFam.length);
    console.log('   - Padres:', familiares.padres?.length || 0);
    console.log('   - Madre:', familiares.madre?.length || 0);
    console.log('   - Hijos:', familiares.hijos?.length || 0);
    console.log('   - Cónyuge:', familiares.conyugue?.length || 0);

    if (allFam.length === 0) {
        console.warn('⚠️ No hay familiares - agregando fila vacía');
        addFamiliarRow({}, container);
    } else {
        allFam.forEach((f, index) => {
            console.log(`🔄 Agregando familiar ${index + 1}:`, f);
            addFamiliarRow(f, container);
        });
        console.log(`✅ ${allFam.length} familiares agregados`);
    }
    
    console.log('═══════════════════════════════════════');
}

function renderOcupaciones(ocupaciones) {
    const tbody = document.getElementById('bodyOcupAlterna');
    if (!tbody) return;

    tbody.innerHTML = '';

    ocupaciones.forEach(o => {
        addOcupacionRow(o.dj2026_descripcion);
    });
}

function addFamiliarRow(data = {}, container = null) {
    console.log('   📝 addFamiliarRow con data:', data);
    console.log('   🔍 FECH_NACI:', data.FECH_NACI);
    console.log('   🔍 TIPO:', typeof data.FECH_NACI);
    
    // Si no se pasa container, buscarlo
    if (!container) {
        container = document.getElementById('familyContainer');
    }
    
    if (!container) {
        console.error('❌ Container no encontrado para agregar familiar');
        return;
    }

    // ✅ FORMATEAR FECHA ANTES DE USARLA
    const fechaFormateada = formatDateForInput(data.FECH_NACI);
    console.log('   ✅ Fecha formateada para input:', fechaFormateada);

    const row = document.createElement('div');
    row.className = 'family-row';
    row.style.cssText = 'display:grid;grid-template-columns:1fr 2fr 1fr auto;gap:8px;align-items:end;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:8px 10px;';
    
    row.innerHTML = `
        <div>
            <label class="dj-label">Parentesco</label>
            <select name="parentesco[]" class="dj-select">
                <option value="">—</option>
                <option value="PADRE" ${data.TIPO_RELA === 'PADRE' ? 'selected' : ''}>Padre</option>
                <option value="MADRE" ${data.TIPO_RELA === 'MADRE' ? 'selected' : ''}>Madre</option>
                <option value="ESPOSO" ${data.TIPO_RELA === 'ESPOSO' ? 'selected' : ''}>Esposo</option>
                <option value="ESPOSA" ${data.TIPO_RELA === 'ESPOSA' ? 'selected' : ''}>Esposa</option>
                <option value="CONYUGE" ${data.TIPO_RELA === 'CONYUGE' ? 'selected' : ''}>Cónyuge</option>
                <option value="HIJO" ${data.TIPO_RELA === 'HIJO' ? 'selected' : ''}>Hijo</option>
                <option value="HIJA" ${data.TIPO_RELA === 'HIJA' ? 'selected' : ''}>Hija</option>
                <option value="HERMANO" ${data.TIPO_RELA === 'HERMANO' ? 'selected' : ''}>Hermano</option>
                <option value="HERMANA" ${data.TIPO_RELA === 'HERMANA' ? 'selected' : ''}>Hermana</option>
                <option value="ABUELO" ${data.TIPO_RELA === 'ABUELO' ? 'selected' : ''}>Abuelo</option>
                <option value="ABUELA" ${data.TIPO_RELA === 'ABUELA' ? 'selected' : ''}>Abuela</option>
            </select>
        </div>
        <div>
            <label class="dj-label">Apellidos y Nombres</label>
            <input type="text" name="apellidosNombres[]" class="dj-input" 
                   value="${data.Nombres || ''}" 
                   placeholder="Apellidos y nombres completos">
        </div>
        <div>
            <label class="dj-label">Fecha de Nacimiento</label>
            <input type="date" name="fechaNacimiento[]" class="dj-input" 
                   value="${fechaFormateada}">
        </div>
        <div>
            <button type="button" class="remove-family dj-btn-sm dj-btn-danger" style="margin-bottom:1px;">
                Eliminar
            </button>
        </div>
    `;
    
    container.appendChild(row);
    console.log('   ✅ Fila agregada al container');
    
    // Agregar evento de eliminar
    const btnEliminar = row.querySelector('.remove-family');
    btnEliminar?.addEventListener('click', function() {
        row.remove();
        console.log('   🗑️ Familiar eliminado');
    });
}

function addOcupacionRow(descripcion = '') {
    const tbody = document.getElementById('bodyOcupAlterna');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="text" name="dj2026_descripcion[]" value="${descripcion}" class="dj-input"></td>
        <td><button type="button" class="btn-remove-ocup btn-sm">Eliminar</button></td>
    `;
    tbody.appendChild(row);
}

async function cargarUbicaciones(tipo, dept, prov, dist) {
    const prefix = tipo === 'actual' ? '_actual' : '_dni';
    
    if (dept) {
        const depts = await axios.get(`${API_URL}/dj/get-ubicacion?type=dept`);
        populateSelect(`#departamento${prefix}`, depts.data);
        setValue(`#departamento${prefix}`, dept);

        if (prov) {
            const provs = await axios.get(`${API_URL}/dj/get-ubicacion?type=prov&dept=${dept}`);
            populateSelect(`#provincia${prefix}`, provs.data);
            setValue(`#provincia${prefix}`, prov);

            if (dist) {
                const dists = await axios.get(`${API_URL}/dj/get-ubicacion?type=dist&prov=${prov}`);
                populateSelect(`#distrito${prefix}`, dists.data);
                setValue(`#distrito${prefix}`, dist);
            }
        }
    }
}

function populateSelect(selector, data) {
    const sel = document.querySelector(selector);
    if (!sel) return;

    sel.innerHTML = '<option value="">Seleccionar...</option>';
    data.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = item.text;
        sel.appendChild(opt);
    });
}

function setValue(selector, value) {
    const id = selector.startsWith('#') ? selector : `#${selector}`;
    const el = document.querySelector(id);
    
    if (el) {
        el.value = value || '';
        
        // ✅ Si es un SELECT y el value no existe, loguear advertencia
        if (el.tagName === 'SELECT' && value && !el.querySelector(`option[value="${value}"]`)) {
            console.warn(`⚠️ SELECT "${selector}": valor "${value}" NO existe en las opciones`);
        } else {
            console.log(`✅ Campo "${selector}" = "${value}"`);
        }
    } else {
        console.error(`❌ Campo "${selector}" NO ENCONTRADO`);
    }
}

// Guardar DJ
document.getElementById('formDatos')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    try {
        Swal.fire({
            title: 'Guardando y migrando...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const formData = new FormData(this);

        const response = await axios.post(`${API_URL}/dj/save-dj-completo`, formData);

        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: response.data.message
        });

        // Cerrar modal correctamente via Preline
        const modalEl = document.getElementById('modalDjGestion');
        if (modalEl) {
            if (window.HSOverlay) {
                HSOverlay.close(modalEl);
            } else {
                modalEl.classList.add('hidden');
                document.querySelector('.hs-overlay-backdrop')?.remove();
                document.body.classList.remove('overflow-hidden');
            }
        }

        // Recargar tabla
        if (window.getPersonal) window.getPersonal();

    } catch (error) {
        console.error('Error guardando DJ:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo guardar'
        });
    }
});

function formatDateForInput(dateValue) {
    console.log('📅 Formateando fecha:', dateValue, '(tipo:', typeof dateValue, ')');
    
    if (!dateValue) {
        console.log('   ⚠️ Fecha vacía/null');
        return '';
    }
    
    // Si ya viene en formato YYYY-MM-DD
    if (typeof dateValue === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateValue)) {
        console.log('   ✅ Fecha ya está en formato correcto:', dateValue);
        return dateValue;
    }
    
    // Si viene con hora (2029-08-18 00:00:00.000)
    if (typeof dateValue === 'string' && dateValue.includes(' ')) {
        const fecha = dateValue.split(' ')[0];
        console.log('   ✅ Fecha extraída (con hora):', fecha);
        return fecha;
    }
    
    // ✅ NUEVO: Si viene en formato DD/MM/YYYY o DD-MM-YYYY
    if (typeof dateValue === 'string' && /^\d{2}[-/]\d{2}[-/]\d{4}$/.test(dateValue)) {
        const partes = dateValue.split(/[-/]/);
        const [dia, mes, anio] = partes;
        const fechaFormateada = `${anio}-${mes}-${dia}`;
        console.log('   ✅ Fecha convertida de DD/MM/YYYY a YYYY-MM-DD:', fechaFormateada);
        return fechaFormateada;
    }
    
    // Si es un objeto Date
    if (dateValue instanceof Date) {
        const year = dateValue.getFullYear();
        const month = String(dateValue.getMonth() + 1).padStart(2, '0');
        const day = String(dateValue.getDate()).padStart(2, '0');
        const formatted = `${year}-${month}-${day}`;
        console.log('   ✅ Fecha formateada desde Date:', formatted);
        return formatted;
    }
    
    console.warn('   ⚠️ Formato de fecha no reconocido:', dateValue);
    return '';
}
// ── BOTÓN FALTANTES ──
document.getElementById('btnReporteFaltantes')?.addEventListener('click', async function () {
    const sucursal = document.getElementById('filtroSucursal')?.value ?? '00';
    const tipoPer  = document.getElementById('filtroTipoPer')?.value ?? '00';
    const codTipoPer  = tipoPer === 'OPERATIVO' ? '03' : tipoPer === 'ADMINISTRATIVO' ? '05' : '00';
    const codSucursal = sucursal || '00';

    Swal.fire({ title: 'Generando reporte...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const response = await axios.get(`${VITE_URL_APP}/api/reporte-personal-sin-migracion-v2`, {
            params: { codSucursal, codTipoPer, tipo: 1 }
        });

        if (!response.data.success || !response.data.data.length) {
            Swal.fire({ icon: 'info', title: 'Sin datos', text: 'No hay registros.' });
            return;
        }

        const todosLosDatos = response.data.data;
        const soloFaltantes = todosLosDatos.filter(d => d.SIP_CAMBIO === 'Falta');

        Swal.close();

        if (!soloFaltantes.length) {
            Swal.fire({ icon: 'info', title: 'Sin faltantes', text: 'Todo el personal está actualizado.' });
            return;
        }

        // ✅ segundo parámetro = datos completos para los cards del PDF
        generarReporteFaltantesPDF(soloFaltantes, todosLosDatos);

    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo obtener los datos.' });
    }
});

// ── BOTÓN ACTUALIZACIÓN ──
document.getElementById('btnReporteActualizacion')?.addEventListener('click', async function () {
    const sucursal = document.getElementById('filtroSucursal')?.value ?? '00';
    const tipoPer  = document.getElementById('filtroTipoPer')?.value ?? '00';
    const codTipoPer  = tipoPer === 'OPERATIVO' ? '03' : tipoPer === 'ADMINISTRATIVO' ? '05' : '00';
    const codSucursal = sucursal || '00';

    Swal.fire({ title: 'Generando reporte...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const response = await axios.get(`${VITE_URL_APP}/api/reporte-personal-sin-migracion-v2`, {
            params: { codSucursal, codTipoPer, tipo: 1 }
        });

        if (!response.data.success || !response.data.data.length) {
            Swal.fire({ icon: 'info', title: 'Sin datos', text: 'No hay registros.' });
            return;
        }

        const todosLosDatos = response.data.data;
        const soloActualizados = todosLosDatos.filter(d => d.SIP_CAMBIO === 'Ok');

        Swal.close();

        if (!soloActualizados.length) {
            Swal.fire({ icon: 'info', title: 'Sin actualizados', text: 'No hay personal actualizado aún.' });
            return;
        }

        // ✅ segundo parámetro = datos completos para los cards del PDF
        generarReporteFaltantesPDF(soloActualizados, todosLosDatos);

    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo obtener los datos.' });
    }
});
async function generarReporteFaltantesPDF(data, todosLosDatos = null) {
     const datosParaCards = todosLosDatos ?? data;
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF({ unit: 'mm', format: 'a4', orientation: 'landscape', compress: true });

    const pageW = 297;
    const pageH = 210;
    const mL = 10, mR = 10, mT = 10, mB = 10;
    const usableW = pageW - mL - mR;
    let y = mT;

    // ── LOGO + CABECERA ──
    try {
        const res = await fetch(window.logoUrl);
        const blob = await res.blob();
        const b64 = await new Promise(r => { const rd = new FileReader(); rd.onload = e => r(e.target.result); rd.readAsDataURL(blob); });
        pdf.addImage(b64, 'PNG', mL, y, 28, 14);
    } catch (_) {}

    pdf.setFontSize(10); pdf.setFont('helvetica', 'bold'); pdf.setTextColor(180, 0, 0);
    pdf.text('SISTEMA INTEGRADO SOLMAR – SISOLMAR WEB', pageW / 2, y + 6, { align: 'center' });

    pdf.setFontSize(13); pdf.setTextColor(0);
    pdf.text(`REPORTE DE ACTUALIZACIÓN DE DATOS DE PERSONAL ${ data[0].TIPO_PER }`, pageW / 2, y + 12, { align: 'center' });

    pdf.setFontSize(11); pdf.setTextColor(0);
    pdf.text(`SOL ${ data[0].SUCURSAl }`, pageW / 2, y + 18, { align: 'center' });

    y += 5;

    const now = new Date();
    const fechaStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()}  ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;
    pdf.setFontSize(7); pdf.setFont('helvetica', 'normal'); pdf.setTextColor(100);
    pdf.text(`Generado: ${fechaStr}`, pageW - mR, y + 5, { align: 'right' });
    pdf.text(`Total registros: ${data.length}`, pageW - mR, y + 9, { align: 'right' });

    y += 18;

    // ── RESUMEN ──
      const totalFalta   = datosParaCards.filter(d => d.SIP_CAMBIO === 'Falta').length;
    const totalListo   = datosParaCards.filter(d => d.SIP_CAMBIO === 'Ok').length;
    const totalMigrado = datosParaCards.filter(d => d.MIGRADO_CAMBIO === 'Verificado').length;

    const cards = [
        { label: 'Total',          val: datosParaCards.length, r: 50,  g: 50,  b: 50  },
        { label: 'Sin actualizar', val: totalFalta,            r: 180, g: 0,   b: 0   },
        { label: 'Actualizados',   val: totalListo,            r: 0,   g: 100, b: 60  },
        { label: 'Verificados',    val: totalMigrado,          r: 0,   g: 80,  b: 160 },
    ];

    const cardW = 36, cardH = 13, cardGap = 5;
    let cx = mL;
    cards.forEach(c => {
        pdf.setFillColor(c.r, c.g, c.b);
        pdf.roundedRect(cx, y, cardW, cardH, 2, 2, 'F');
        pdf.setFontSize(16); pdf.setFont('helvetica', 'bold'); pdf.setTextColor(255);
        pdf.text(String(c.val), cx + cardW / 2, y + 8, { align: 'center' });
        pdf.setFontSize(7); pdf.setFont('helvetica', 'normal');
        pdf.text(c.label, cx + cardW / 2, y + 11.5, { align: 'center' });
        cx += cardW + cardGap;
    });

    y += cardH + 5;

    // ── TABLA ──
    // const cols = [
    //     { header: 'Estado',       key: 'SIP_CAMBIO',      w: 18 },
    //     { header: 'Verificado',    key: 'MIGRADO_CAMBIO',  w: 22 },
    //     { header: 'Nombre Completo', key: 'NOMBRE',       w: 72 },
    //     { header: 'DNI',          key: 'NRO_DOCU_IDEN',   w: 22 },
    //     { header: 'Fecha de ingreso',  key: 'FECH_INGRE',   w: 22 },
    //     // { header: 'Sucursal',     key: 'SUCURSAL',        w: 28 },
    //     // { header: 'Tipo',         key: 'TIPO_PER',        w: 26 },
    //     { header: 'Fecha Actualización',     key: 'SIP_CREACION',    w: 32 },
    //     { header: 'Fecha Verificado.',   key: 'MIGRADO_FECHA',   w: 32 },
    // ];

    const cols = [
    { header: 'N°',           key: '__rownum__',      w: 12 },  // ← AGREGAR ESTA LÍNEA
    { header: 'Estado',       key: 'SIP_CAMBIO',      w: 18 },
    { header: 'Verificado',   key: 'MIGRADO_CAMBIO',  w: 22 },
    { header: 'Nombre Completo', key: 'NOMBRE',       w: 66 },  // ← reducir un poco el ancho
    { header: 'DNI',          key: 'NRO_DOCU_IDEN',   w: 22 },
    { header: 'Fecha de ingreso',  key: 'FECH_INGRE', w: 22 },
    { header: 'Fecha Actualización', key: 'SIP_CREACION', w: 32 },
    { header: 'Fecha Verificado.', key: 'MIGRADO_FECHA',  w: 32 },
];

    const rowH = 5.5;
    const headerH = 7;

    function drawTableHeader() {
        pdf.setFillColor(40, 40, 40);
        pdf.rect(mL, y, usableW, headerH, 'F');
        pdf.setFontSize(7); pdf.setFont('helvetica', 'bold'); pdf.setTextColor(255);
        let hx = mL + 1;
        cols.forEach(c => {
            pdf.text(c.header, hx, y + 4.5);
            hx += c.w;
        });
        y += headerH;
    }

    drawTableHeader();

    data.forEach((row, idx) => {
        if (y + rowH > pageH - mB - 5) {
            pdf.addPage();
            y = mT;
            drawTableHeader();
        }

        // Fondo alternado
        if (idx % 2 === 0) {
            pdf.setFillColor(248, 248, 248);
            pdf.rect(mL, y, usableW, rowH, 'F');
        }

        // Color por estado
        const esFalta   = row.SIP_CAMBIO === 'Falta';
        const esMigrado = row.MIGRADO_CAMBIO === 'Migrado';

        pdf.setFontSize(6.5); pdf.setFont('helvetica', 'normal');

        let rx = mL + 1;
        cols.forEach(c => {
            let val = c.key === '__rownum__'
                ? String(idx + 1)              // ← número de orden
                : String(row[c.key] ?? '');
            const maxW = c.w - 2;

            // Color dinámico por columna
            if (c.key === 'SIP_CAMBIO') {
                pdf.setTextColor(esFalta ? 180 : 0, esFalta ? 0 : 120, 0);
            } else if (c.key === 'MIGRADO_CAMBIO') {
                pdf.setTextColor(esMigrado ? 0 : 150, esMigrado ? 100 : 70, esMigrado ? 160 : 0);
            } else {
                pdf.setTextColor(30, 30, 30);
            }

            // Truncar si es muy largo
            pdf.setFontSize(6.5);
            while (pdf.getTextWidth(val) > maxW && val.length > 1) val = val.slice(0, -1);

            pdf.text(val, rx, y + rowH - 1.5);
            rx += c.w;
        });

        // Línea separadora
        pdf.setDrawColor(220); pdf.setLineWidth(0.1);
        pdf.line(mL, y + rowH, mL + usableW, y + rowH);

        y += rowH;
    });

    // ── FOOTER ──
    const totalPages = pdf.getNumberOfPages();
    for (let i = 1; i <= totalPages; i++) {
        pdf.setPage(i);
        pdf.setFontSize(7); pdf.setFont('helvetica', 'normal'); pdf.setTextColor(130);
        pdf.text(`Página ${i} de ${totalPages}`, pageW / 2, pageH - 4, { align: 'center' });
        pdf.text('SISOLMAR – Recursos Humanos', mL, pageH - 4);
        pdf.text(fechaStr, pageW - mR, pageH - 4, { align: 'right' });
    }

    const ts = `${now.getFullYear()}${String(now.getMonth()+1).padStart(2,'0')}${String(now.getDate()).padStart(2,'0')}_${String(now.getHours()).padStart(2,'0')}${String(now.getMinutes()).padStart(2,'0')}`;
    pdf.save(`Reporte_Faltantes_DJ_${ts}.pdf`);
}

// ── ACTUALIZAR CARD CONTADOR ──
function actualizarContador(filasFiltradas, totalGeneral) {
    const elFilt = document.getElementById('contadorFiltrado');
    const elTot  = document.getElementById('contadorTotal');
    if (!elFilt || !elTot) return;

    const desde = parseInt(elFilt.textContent.replace(/,/g, '')) || 0;
    const hasta = filasFiltradas;

    animarContador(elFilt, desde, hasta);
    elTot.textContent = totalGeneral.toLocaleString();
}

function animarContador(el, desde, hasta, duracion = 400) {
    const inicio = performance.now();
    const diff = hasta - desde;

    function tick(ahora) {
        const t = Math.min((ahora - inicio) / duracion, 1);
        // Easing suave: ease-out cubic
        const progreso = 1 - Math.pow(1 - t, 3);
        el.textContent = Math.round(desde + diff * progreso).toLocaleString();
        if (t < 1) requestAnimationFrame(tick);
    }

    requestAnimationFrame(tick);
}