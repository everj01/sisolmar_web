window.GestionActualizacionesDJ = {
    personalData: [], // NUEVO: Aquí vivirá la lista temporal para poder filtrarla sin ir a la BD

    init: function () {
        this.cargarTablaPrincipal();
        this.bindEvents();
        this.bindFiltros(); // NUEVO: Iniciamos los escuchadores del buscador
    },

    bindEvents: function() {
        // Autocalcular el año cuando seleccionen la Fecha de Inicio
        const inputInicio = document.getElementById('fecha_inicio');
        if(inputInicio) {
            inputInicio.addEventListener('change', function() {
                // Al leer de un input type date (YYYY-MM-DD), extraemos el primer segmento (el año)
                const anio = this.value.split('-')[0];
                if(anio) {
                    document.getElementById('anio_periodo').value = anio;
                }
            });
        }
    },


    bindFiltros: function() {
        const _this = this;
        // Cada vez que se escriba o se cambie un select, filtramos la tabla
        ['filtro_buscador', 'filtro_tipo', 'filtro_sucursal'].forEach(id => {
            const el = document.getElementById(id);
            if(el) {
                el.addEventListener('input', () => _this.renderizarPersonalBuscador());
                el.addEventListener('change', () => _this.renderizarPersonalBuscador());
            }
        });
        
        // Magia para que el checkbox de la cabecera marque todos los filtrados
        const checkAll = document.getElementById('checkAllPersonal');
        if(checkAll) {
            checkAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.chk-empleado');
                checkboxes.forEach(chk => chk.checked = this.checked);
            });
        }
    },

    llenarFiltrosDinamicos: function() {
        // Sacamos valores únicos para llenar los selects basados en los datos traídos
        const tipos = [...new Set(this.personalData.map(item => item.tipo).filter(Boolean))].sort();
        const sucursales = [...new Set(this.personalData.map(item => item.sucursal).filter(Boolean))].sort();
        
        const selTipo = document.getElementById('filtro_tipo');
        const selSucursal = document.getElementById('filtro_sucursal');
        
        selTipo.innerHTML = '<option value="">Todos los tipos</option>';
        tipos.forEach(t => selTipo.innerHTML += `<option value="${t}">${t}</option>`);
        
        selSucursal.innerHTML = '<option value="">Todas las sucursales</option>';
        sucursales.forEach(s => selSucursal.innerHTML += `<option value="${s}">${s}</option>`);
    },

    renderizarPersonalBuscador: function() {
        const tbody = document.getElementById('tbodyPersonalCorte');
        if(!this.personalData || this.personalData.length === 0) return;

        // Leer los valores actuales de los 3 filtros
        const texto = (document.getElementById('filtro_buscador').value || '').toLowerCase();
        const tipo = document.getElementById('filtro_tipo').value;
        const sucursal = document.getElementById('filtro_sucursal').value;

        // Filtrar la data en memoria (instantáneo)
        const dataFiltrada = this.personalData.filter(emp => {
            const matchTexto = !texto || (emp.dni || '').toLowerCase().includes(texto) || (emp.nombres + ' ' + emp.apellidos).toLowerCase().includes(texto);
            const matchTipo = !tipo || emp.tipo === tipo;
            const matchSucursal = !sucursal || emp.sucursal === sucursal;
            return matchTexto && matchTipo && matchSucursal;
        });

        if(dataFiltrada.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-gray-500">No hay coincidencias con los filtros aplicados.</td></tr>`;
            return;
        }

        // Armamos el HTML con las nuevas columnas
        let filasHtml = '';
        dataFiltrada.forEach(emp => {
            filasHtml += `
                <tr class="border-b dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700/50">
                    <td class="px-4 py-2">
                        <input type="checkbox" class="form-checkbox chk-empleado rounded text-blue-600" value="${emp.codigo}" checked>
                    </td>
                    <td class="px-4 py-2">${emp.codigo}</td>
                    <td class="px-4 py-2 text-xs text-gray-500">${emp.tipo || '-'}</td>
                    <td class="px-4 py-2 font-medium">${emp.dni || '-'}</td>
                    <td class="px-4 py-2 font-medium">${emp.nombres} ${emp.apellidos}</td>
                    <td class="px-4 py-2 text-gray-500 text-xs">${emp.cargo || '-'}</td>
                    <td class="px-4 py-2 text-gray-500 text-xs">${emp.sucursal || '-'}</td>
                </tr>
            `;
        });
        tbody.innerHTML = filasHtml;
    },


    cargarTablaPrincipal: async function () {
        const tbody = document.querySelector('#tablaPeriodos tbody');
        if(!tbody) return;

        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4"><i class="bx bx-spin bx-loader text-2xl"></i> Cargando periodos...</td></tr>`;

        try {
            const response = await fetch(window.AppConfig.urls.listarPeriodos);
            const result = await response.json();

            if (result.success) {
                tbody.innerHTML = '';
                
                if (result.data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-gray-500">No hay periodos registrados.</td></tr>`;
                    return;
                }

                result.data.forEach(periodo => {
                    tbody.innerHTML += `
                        <tr class="border-b border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-3 font-medium">${periodo.nombre_periodo || 'Sin nombre'}</td>
                            <td class="px-4 py-3">${periodo.fecha_inicio}</td>
                            <td class="px-4 py-3">${periodo.fecha_fin}</td>
                            <td class="px-4 py-3">${periodo.fecha_corte}</td>
                            <td class="px-4 py-3 text-center flex justify-center gap-2">
                                <button title="Ir Proceso" class="p-1 text-emerald-600 hover:text-emerald-800 bg-emerald-100 rounded-md"><i class="bx bx-play-circle text-lg"></i></button>
                                <button title="Personal Relacionado" class="p-1 text-blue-600 hover:text-blue-800 bg-blue-100 rounded-md"><i class="bx bx-group text-lg"></i></button>
                                <button title="Editar" class="p-1 text-amber-600 hover:text-amber-800 bg-amber-100 rounded-md"><i class="bx bx-edit text-lg"></i></button>
                                <button title="Eliminar" class="p-1 text-red-600 hover:text-red-800 bg-red-100 rounded-md"><i class="bx bx-trash text-lg"></i></button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-red-500">Error: ${result.message}</td></tr>`;
            }
        } catch (error) {
            console.error("Error al cargar periodos:", error);
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-red-500">Error de conexión al cargar la tabla.</td></tr>`;
        }
    },

    // --- MANEJO DE MODALES ---

    abrirModalPeriodo: function() {
        document.getElementById('formPeriodo').reset();
        document.getElementById('modalPeriodo').classList.remove('hidden');
        document.body.classList.add('modal-abierto');
    },

    cerrarModal: function(idModal) {
        document.getElementById(idModal).classList.add('hidden');
        document.body.classList.remove('modal-abierto');
    },

    irPasoPersonal: async function() {
        const fechaCorte = document.getElementById('fecha_corte').value;
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;

        if(!fechaInicio || !fechaFin || !fechaCorte) {
            alert("Por favor, completa todas las fechas obligatorias (Inicio, Fin y Corte).");
            return;
        }

        if(fechaFin < fechaInicio) {
            alert("La fecha de fin no puede ser menor a la fecha de inicio.");
            return;
        }

        // Mostrar un estado de carga
        document.getElementById('txtFiltroCorte').innerText = "Cargando personal...";
        const tbody = document.getElementById('tbodyPersonalCorte');
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4"><i class="bx bx-spin bx-loader text-2xl"></i> Buscando...</td></tr>`;

        // 1. Llamamos al Backend para traer los empleados
        try {
            // Utilizamos la URL inyectada dinámicamente desde Blade
            const urlEndpoint = `${window.AppConfig.urls.personalCorte}?fecha_corte=${fechaCorte}`;
            const response = await fetch(urlEndpoint);
            const result = await response.json();

            if(result.success) {
                document.getElementById('txtFiltroCorte').innerText = "Filtrado por fecha de corte: " + fechaCorte;
                
                if(result.data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-red-500">No se encontró personal para esta fecha.</td></tr>`;
                    return;
                }

                // NUEVO: Guardamos la data en nuestra variable global, llenamos los selects y renderizamos la tabla
                this.personalData = result.data;
                this.llenarFiltrosDinamicos();
                this.renderizarPersonalBuscador();
            }
        } catch (error) {
            console.error("Error al traer personal:", error);
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-red-500">Error de conexión con el servidor.</td></tr>`;
        }

        // Transición visual
        this.cerrarModal('modalPeriodo');
        document.getElementById('modalPersonal').classList.remove('hidden');
        document.body.classList.add('modal-abierto');
    },

    volverPasoPeriodo: function() {
        this.cerrarModal('modalPersonal');
        document.getElementById('modalPeriodo').classList.remove('hidden');
        document.body.classList.add('modal-abierto');
    },

    renderizarPersonalSimulado: function() {
        const tbody = document.getElementById('tbodyPersonalCorte');
        tbody.innerHTML = `
            <tr class="border-b dark:border-slate-700">
                <td class="px-4 py-2"><input type="checkbox" class="form-checkbox rounded text-blue-600" checked></td>
                <td class="px-4 py-2">EMP-001</td>
                <td class="px-4 py-2 font-medium">Juan Perez</td>
                <td class="px-4 py-2 text-gray-500">Operario Especializado</td>
            </tr>
            <tr class="border-b dark:border-slate-700">
                <td class="px-4 py-2"><input type="checkbox" class="form-checkbox rounded text-blue-600"></td>
                <td class="px-4 py-2">EMP-002</td>
                <td class="px-4 py-2 font-medium">María Lopez</td>
                <td class="px-4 py-2 text-gray-500">Supervisora SSOMA</td>
            </tr>
        `;
    },

    guardarTodoElPeriodo: async function() {
        // 1. Recolectar datos del Formulario 1 (Periodo)
        const data = {
            nombre_periodo: document.getElementById('nombre_periodo').value,
            fecha_inicio: document.getElementById('fecha_inicio').value,
            fecha_fin: document.getElementById('fecha_fin').value,
            fecha_corte: document.getElementById('fecha_corte').value,
            anio_periodo: document.getElementById('anio_periodo').value,
            cod_empresa: document.getElementById('cod_empresa').value,
            personal: [] // Aquí meteremos los códigos de los seleccionados
        };

        // 2. Recolectar a los empleados que tengan el check marcado en el Formulario 2
        const checkboxes = document.querySelectorAll('.chk-empleado:checked');
        checkboxes.forEach(chk => {
            data.personal.push(chk.value);
        });

        if(data.personal.length === 0) {
            alert("Debes seleccionar al menos a un empleado.");
            return;
        }

        // 3. Enviar todo al Backend con FETCH (POST)
        try {
            // OJO: En Laravel necesitamos mandar el Token CSRF por seguridad
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const response = await fetch(window.AppConfig.urls.guardarPeriodo, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken 
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if(result.success) {
                alert("¡Periodo y personal guardados exitosamente!");
                this.cerrarModal('modalPersonal');
                this.cargarTablaPrincipal(); // Recargamos solo la tabla de forma fluida
            } else {
                alert("Error: " + result.message);
            }

        } catch (error) {
            console.error("Error al guardar:", error);
            alert("Hubo un error de red al intentar guardar.");
        }
    }
};