@extends('layouts.vertical', ['title' => 'Gestión de cursos'])
@section('css')
@endsection
@section('content')
@include("layouts.shared/page-title", ["subtitle" => "Capacitación", "title" => "Gestión de cursos"])

<script>
    // Inicialización síncrona para evitar Alpine/Vite race conditions
    window.alertasVencimientoCursos = function () {
        return {
            alertas: [],
            initAlertas() {
                const appUrl = '{{ env("APP_URL", "") }}';
                fetch(`${appUrl}/api/cursos/alertas-vencimiento`)
                    .then(res => res.json())
                    .then(data => {
                        console.log('⚡ Respuesta Alertas:', data);
                        if (data && data.success) {
                            this.alertas = data.alertas;
                            window.alertasCursosData = this.alertas.map(a => String(a.codigo_curso));
                            if (window.cursoTable && typeof window.renderTablaCursos === 'function') {
                                window.renderTablaCursos(window.cursosData || []);
                            }
                        }
                    })
                    .catch(e => console.error("Error cargando alertas de vencimiento:", e));
            }
        };
    };

    window.modalApertura = function() {
        return {
            isOpen: false,
            cargando: false,
            codigoCurso: null,
            cursoNombre: '',
            tipoCursoId: '',
            fechaInicio: '',
            clientesAsignados: [],
            empresasAsignadas: [],
            areasAsignadas: [],
            listaDNIPaste: '',
            incluirAutomatico: true,
            selectedSucursal: '',
            selectedCliente: '',
            selectedArea: '',
            combosApertura: { sucursales: [], clientes: [], areas: [] },
            
            async init() {
                await this.fetchCombos();
            },

            async fetchCombos() {
                const appUrl = '{{ env("APP_URL", "") }}';
                try {
                    const response = await fetch(`${appUrl}/api/capacitacion/combos-apertura`);
                    const data = await response.json();
                    if (data.success) {
                        this.combosApertura = data;
                    }
                } catch (e) {
                    console.error("Error cargando combos de apertura:", e);
                }
            },
            
            openModal(data) {
                this.codigoCurso = data.codigo;
                this.cursoNombre = data.nombre;
                this.tipoCursoId = data.tipo_curso || '';
                
                // Reset filtros
                this.selectedSucursal = '';
                this.selectedCliente = '';
                this.selectedArea = '';
                this.listaDNIPaste = '';
                
                const today = new Date();
                const yyyy = today.getFullYear();
                const mm = String(today.getMonth() + 1).padStart(2, '0');
                this.fechaInicio = `${yyyy}-${mm}`;
                
                window.dispatchEvent(new CustomEvent('cambiar-panel', {
                    detail: { panel: 'apertura_manual', titulo: this.cursoNombre }
                }));
                this.isOpen = true;
                this.cargando = false;
            },
            
            closeModal() {
                window.dispatchEvent(new CustomEvent('cambiar-panel', {
                    detail: { panel: 'registro' }
                }));
                this.isOpen = false;
                this.codigoCurso = null;
                this.cursoNombre = '';
                this.tipoCursoId = '';
                this.fechaInicio = '';
                this.selectedSucursal = '';
                this.selectedCliente = '';
                this.selectedArea = '';
            },
            
            async guardarApertura() {
                if (!this.fechaInicio) {
                    window.dispatchEvent(new CustomEvent('mostrar-alerta', {
                        detail: { titulo: "Atención", mensaje: "Debe seleccionar un mes de campaña.", tipo: "warning" }
                    }));
                    return;
                }
                
                const dnisLimpios = this.listaDNIPaste.trim() 
                    ? this.listaDNIPaste.split(/\n|,|;/).map(d => d.trim()).filter(d => d.length > 0)
                    : [];

                if (this.tipoCursoId == '6' && this.clientesAsignados.length === 0 && dnisLimpios.length === 0) {
                    window.dispatchEvent(new CustomEvent('mostrar-alerta', {
                         detail: { titulo: "Atención", mensaje: "Debe seleccionar al menos un cliente o pegar una lista de DNIs.", tipo: "warning" }
                    }));
                    return;
                }

                if (this.tipoCursoId == '7' && this.areasAsignadas.length === 0 && dnisLimpios.length === 0) {
                    window.dispatchEvent(new CustomEvent('mostrar-alerta', {
                         detail: { titulo: "Atención", mensaje: "Debe seleccionar al menos un área operativa o pegar una lista de DNIs.", tipo: "warning" }
                    }));
                    return;
                }

                this.cargando = true;
                const appUrl = '{{ env("APP_URL", "") }}';
                
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const headers = {
                        'Content-Type': 'application/json'
                    };
                    if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;

                    const payload = {
                        cod_cursos: this.codigoCurso,
                        fecha_inicio: this.fechaInicio,
                        incluir_automatico: this.incluirAutomatico,
                        sucursal_codigo: this.selectedSucursal,
                        cliente_id: this.selectedCliente,
                        area_codigo: this.selectedArea
                    };

                    if (dnisLimpios.length > 0) {
                        payload.dnis = dnisLimpios;
                    }

                    const response = await fetch(`${appUrl}/api/cursos/programacion-manual`, {
                        method: 'POST',
                        headers: headers,
                        body: JSON.stringify(payload)
                    });
                    
                    const result = await response.json();
                    
                    if(response.ok && result.success) {
                        this.closeModal();
                        // El mensaje viene del controlador indicando si fue masiva o solo apertura de ciclo
                        const mensajeFinal = result.message || "Operación exitosa";
                        const esBajoDemanda = result.message.includes('bajo demanda');

                        window.dispatchEvent(new CustomEvent('mostrar-alerta', {
                            detail: { 
                                titulo: esBajoDemanda ? "Ciclo Abierto" : "Proceso Iniciado", 
                                mensaje: mensajeFinal, 
                                tipo: "success", 
                                recargar: true 
                            }
                        }));
                    } else {
                        window.dispatchEvent(new CustomEvent('mostrar-alerta', {
                            detail: { titulo: "No se pudo aperturar", mensaje: result.message || "Error al procesar la solicitud.", tipo: "error" }
                        }));
                    }
                } catch(error) {
                    console.error("Error aperturando curso:", error);
                    window.dispatchEvent(new CustomEvent('mostrar-alerta', {
                        detail: { titulo: "Error de Servidor", mensaje: "Ocurrió un problema de conectividad con el servidor. Revisa los logs.", tipo: "error" }
                    }));
                } finally {
                    this.cargando = false;
                }
            }
        };
    };

    // Escuchador global en Vanilla JS para evadir el Proxy de AlpineJS
    window.addEventListener('mostrar-alerta', function(e) {
        if(typeof Swal !== 'undefined') {
            Swal.fire({
                title: e.detail.titulo,
                text: e.detail.mensaje,
                icon: e.detail.tipo,
                confirmButtonText: "Entendido",
                confirmButtonColor: "#1d4ed8" 
            }).then(() => {
                if (e.detail.recargar) {
                    window.location.reload();
                }
            });
        } else {
            alert(e.detail.titulo + ": " + e.detail.mensaje);
            if (e.detail.recargar) window.location.reload();
        }
    });
</script>

<div x-data="alertasVencimientoCursos()" x-init="initAlertas()" x-show="alertas.length > 0" style="display: none;" class="mb-6 bg-orange-50 border-l-4 border-orange-500 p-4 rounded shadow-sm">
    <div class="flex items-start">
        <div class="flex-shrink-0 mt-0.5">
            <i class="bx bxs-error-circle text-orange-500 text-xl"></i>
        </div>
        <div class="ml-3 w-full">
            <h3 class="text-sm font-bold text-orange-800">
                Atención: Renovación y Clonación de Cursos
            </h3>
            <div class="mt-2 text-sm text-orange-700">
                <p>Ocurrirá una clonación y matriculación automática pronto para los siguientes cursos periódicos. Verifique el material docente si es necesario:</p>
                <ul class="list-disc pl-5 mt-1 space-y-1">
                    <template x-for="alerta in alertas" :key="alerta.codigo_curso">
                        <li>
                            <strong x-text="alerta.nombre"></strong> (Próxima ejecución en <span x-text="alerta.dias_restantes"></span> días el <span x-text="alerta.fecha_proxima_clonacion"></span>)
                        </li>
                    </template>
                </ul>
            </div>
        </div>
        <div class="ml-auto pl-3">
            <div class="-mx-1.5 -my-1.5">
                <button type="button" @click="alertas = []" class="inline-flex rounded-md bg-orange-50 p-1.5 text-orange-500 hover:bg-orange-100 focus:outline-none focus:ring-2 focus:ring-orange-600 focus:ring-offset-2 focus:ring-offset-orange-50">
                    <span class="sr-only">Cerrar</span>
                    <i class="bx bx-x text-lg"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full items-start">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Lista de cursos</h4>
        </div>

        <div class="card-body">
            <div 
                x-data="{ 
                    soloEliminados: false, 
                    filtroArea: '', 
                    filtroTipoCurso: '',
                    tipos: window.opcionesTipoCurso || [] 
                }" 
                x-init="$watch('tipos', val => console.log('⚡ Alpine: Types loaded:', val)); console.log('⚡ Alpine: Init types:', tipos)"
                @tipo-curso-loaded.window="tipos = $event.detail; console.log('⚡ Alpine: Event caught, types updated')"
                @update-filtro-area="filtroArea = $event.detail; console.log('⚡ Alpine: Area Updated:', $event.detail); listarCursos(soloEliminados ? 0 : 1, filtroArea, filtroTipoCurso)"
                class="flex flex-wrap items-center justify-between gap-6"
            >
                <div class="flex items-center">
                <input 
                    class="form-switch" 
                    type="checkbox" 
                    role="switch" 
                    id="chkEliminados"
                    x-model="soloEliminados"
                >
                <label class="ms-1.5 font-medium text-sm text-gray-700" for="chkEliminados">
                    Solo eliminados
                </label>
                </div>

                <div class="flex flex-wrap items-end gap-4 ml-auto flex-1 justify-end">
                    <div class="flex flex-col min-w-[200px]">
                    <label for="slcFiltroTipoCurso" class="text-sm font-medium text-gray-700 mb-1">
                        Plan de Capacitación
                    </label>
                    <select 
                        id="slcFiltroTipoCurso" 
                        x-model="filtroTipoCurso"
                        @change="filtroTipoCurso = $el.value; console.log('⚡ Alpine: Select Changed. New Value:', $el.value); listarCursos(soloEliminados ? 0 : 1, filtroArea, $el.value)"
                        class="w-full rounded-lg border-gray-300 text-sm px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    >
                        <option value="">-- Todos --</option>
                        <template x-for="tipo in tipos" :key="tipo.codigo">
                            <option :value="tipo.codigo" x-text="tipo.descripcion"></option>
                        </template>
                    </select>
                    </div>

                    <div 
                        class="flex flex-col min-w-[200px]"
                        x-data="{
                        open: false,
                        search: '',
                        selected: null,
                        options: window.opcionesArea || [], 
                        get filteredOptions() {
                            if (this.search === '') return this.options;
                            return this.options.filter(opt => opt.descripcion.toLowerCase().includes(this.search.toLowerCase()))
                                .sort((a, b) => {
                                    const aStarts = a.descripcion.toLowerCase().startsWith(this.search.toLowerCase());
                                    const bStarts = b.descripcion.toLowerCase().startsWith(this.search.toLowerCase());
                                    if (aStarts && !bStarts) return -1;
                                    if (!aStarts && bStarts) return 1;
                                    return 0;
                                });
                        },
                        selectOption(option) {
                            this.selected = option;
                            this.open = false;
                            this.search = '';
                            this.$dispatch('update-filtro-area', option ? option.codigo : ''); // Send Code or empty
                        }
                    }"
                    @update-filtro-area="filtroArea = $event.detail"
                    @areas-loaded.window="options = $event.detail"
                >
                    <label class="text-sm font-medium text-gray-700 mb-1">
                        Sistema de Gestión
                    </label>
                    
                    <div class="relative">
                        <!-- Botón principal del Select -->
                        <button 
                            type="button" 
                            @click="open = !open" 
                            @click.away="open = false"
                            class="w-full bg-white border border-gray-300 rounded-lg shadow-sm px-3 py-2 text-left text-sm cursor-default focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 overflow-hidden"
                        >
                            <span class="block truncate" x-text="selected ? selected.descripcion : '-- Todas --'"></span>
                            <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </button>

                        <!-- Dropdown -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg flex flex-col overflow-hidden"
                             style="max-height: 320px; display: none;">
                            
                            <!-- Search Input inside Dropdown -->
                            <div class="p-2 border-b border-gray-100 bg-gray-50 flex-shrink-0">
                                <input 
                                    type="text" 
                                    x-model="search" 
                                    placeholder="Buscar..." 
                                    class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 p-1.5"
                                    @click.stop
                                >
                            </div>

                            <!-- Opcion "Todas" por defecto -->
                             <div 
                                @click="selectOption(null)"
                                class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-indigo-50 text-sm border-b border-gray-100 flex-shrink-0"
                            >
                                <span class="block truncate font-bold text-gray-500">-- Todas --</span>
                            </div>

                            <!-- Lista de Opciones Filtradas -->
                            <div class="overflow-y-auto custom-scrollbar flex-1">
                                <template x-for="option in filteredOptions" :key="option.codigo">
                                    <div 
                                        @click="selectOption(option)"
                                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-indigo-50 text-sm"
                                        :class="{ 'bg-indigo-50 font-semibold text-indigo-900': selected && selected.codigo === option.codigo }"
                                    >
                                        <span class="block truncate" x-text="option.descripcion"></span>
                                        
                                        <span x-show="selected && selected.codigo === option.codigo" class="absolute inset-y-0 right-0 flex items-center pr-4 text-indigo-600">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </div>
                                </template>
                            </div>
                             <div x-show="filteredOptions.length === 0" class="py-2 px-3 text-sm text-gray-500 text-center flex-shrink-0">
                                No se encontraron resultados
                            </div>
                        </div>
                        </div>
                    </div>
                </div>

                <div x-effect="listarCursos( soloEliminados ? 0 : 1, filtroArea, filtroTipoCurso )"></div>
            </div>

            <div class="mt-5 overflow-x-auto w-full">
                <table id="tblCursos" class="datatable responsive-table w-full">
                <thead>
                    <tr>
                    <th class="text-primary font-semibold">#</th>
                    <th class="text-primary font-semibold">CÓDIGO</th>
                    <th class="text-primary font-semibold">NOMBRE</th>
                    <th class="text-primary font-semibold">ACCIONES</th>
                    </tr>
                </thead>
                <tbody></tbody>
                </table>
            </div>
            </div>
    </div>

    <div class="card" x-data="{ panel: 'registro', tituloProgramacion: '', mostrarBotonRegistrarListado: true }" @cambiar-panel.window="panel = $event.detail.panel; tituloProgramacion = $event.detail.titulo || ''; mostrarBotonRegistrarListado = $event.detail.mostrarBtn === undefined ? true : $event.detail.mostrarBtn">
        <!-- Panel Registro de Curso -->
        <div x-show="panel === 'registro'" x-transition>
            <div class="card-header">
            <div class="flex items-center justify-between">
                <h4 class="card-title">Datos del curso</h4>
                <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-primary/25 text-primary-800" id="txtMensajeNuevo">Nuevo</span>
            </div>
            </div>
            <div class="flex items-center justify-center gap-2 mt-4 hidden" id="viewEditCreate">
                <span>¿Quieres registrar un curso?</span>
                <button type="button" id="btnCambiarEdit" onclick="restaurarFormCurso()"
                class="btn rounded-full bg-success/25 text-success hover:bg-success hover:text-white">
                    Crear curso
                </button>
            </div>
            <div class="card-body" x-data="formCursoGestion()" @submit.prevent x-init="$nextTick(() => { $watch('tipoCurso', value => { if(value != '5') targetGroup = 'TODOS'; }); })">
                <input type="hidden" name="targetGroupHidden" x-model="targetGroup">
                <input type="hidden" name="codGestionEditar" x-model="codigo" id="codGestionEditar">
                <input type="hidden" id="slcArea" x-model="area">
                <div class="w-full mt-4">
                    <h3 class="text-lg font-semibold text-primary text-center mb-1">Datos del curso</h3>
                    <hr>
                </div>
                
                <div class="w-full grid gap-4 mt-4 grid-cols-1 pb-6">
                    
                    <!-- Nombre Completo de Curso -->
                    <div>
                        <label for="txtNombreCurso" class="text-gray-800 text-sm font-medium inline-block mb-1">
                        Nombre del curso
                        </label>
                        <input type="text" id="txtNombreCurso"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                        x-model="nombre" />
                    </div>

                    <!-- Plan de Capacitación -->
                    <div>
                        <label class="text-gray-800 text-sm font-medium inline-block mb-2 text-primary">
                            Plan de Capacitación <span class="text-danger">*</span>
                        </label>
                        <div class="flex flex-wrap gap-3" 
                             x-data="{ tipos: window.opcionesTipoCurso || [] }"
                             @tipo-curso-loaded.window="tipos = $event.detail">
                            <template x-for="tipo in tipos" :key="tipo.codigo">
                                <label class="flex items-center space-x-2 cursor-pointer bg-white border border-gray-200 rounded-lg px-3 py-2 hover:bg-slate-50 transition-all shadow-sm"
                                       :class="{ 'border-primary ring-1 ring-primary/30 bg-primary/5': tipoCurso == tipo.codigo }">
                                    <input type="radio" :value="tipo.codigo" x-model="tipoCurso" 
                                        @change="checkEsPACByText(tipo.descripcion)"
                                        name="plan_capacitacion"
                                        class="w-4 h-4 text-primary focus:ring-primary border-gray-300">
                                    <span class="text-sm font-medium text-gray-700" x-text="tipo.descripcion"></span>
                                </label>
                            </template>
                        </div>
                        
                        <!-- NUEVO: Selector PCU (Clientes) -->
                        <div x-show="tipoCurso == '6'" x-transition class="mt-4 bg-blue-50/50 border border-blue-100 rounded-lg p-5">
                            <label class="text-blue-800 text-sm font-bold tracking-wide inline-block mb-2">
                                <i class="bx bx-buildings mr-1"></i> Seleccionar Clientes <span class="text-red-500">*</span>
                            </label>
                            <p class="text-xs text-blue-500 mb-3 font-medium">Se matriculará al personal asignado a estos clientes.</p>
                            <div class="mb-3">
                                <input type="text" x-model="busquedaCliente" placeholder="Buscar cliente..." 
                                    class="w-full border border-blue-200 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-blue-400 focus:border-blue-400 outline-none shadow-sm"
                                    @keydown.enter.prevent>
                            </div>
                            <div class="border border-blue-100 rounded-md p-3 overflow-y-auto bg-white custom-scrollbar shadow-inner" style="max-height: 160px;">
                                <div class="grid grid-cols-1 gap-2">
                                    <template x-for="clie in clientesFiltrados" :key="clie.codigo">
                                        <label class="flex items-start space-x-2 cursor-pointer hover:bg-slate-50 p-2 rounded-md border border-transparent hover:border-slate-200 transition-all">
                                            <input type="checkbox" :value="clie.codigo" x-model="clientesAsignados" 
                                                class="mt-0.5 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="text-sm font-medium text-gray-700">
                                                <span x-text="clie.codigo" class="text-xs text-gray-500 bg-gray-200 px-1.5 py-0.5 rounded mr-1"></span>
                                                <span x-text="clie.descripcion"></span>
                                            </span>
                                        </label>
                                    </template>
                                </div>
                                <div x-show="clientesFiltrados.length === 0" class="text-gray-400 text-sm text-center py-4">
                                    No se encontraron clientes asociados.
                                </div>
                            </div>
                        </div>

                        <!-- NUEVO: Selector PCI (Áreas Operativas) -->
                        <div x-show="tipoCurso == '7'" x-transition class="mt-4 bg-teal-50/50 border border-teal-100 rounded-lg p-5">
                            <label class="text-teal-800 text-sm font-bold tracking-wide inline-block mb-2">
                                <i class="bx bx-category mr-1"></i> Seleccionar Áreas Operativas <span class="text-red-500">*</span>
                            </label>
                            <p class="text-xs text-teal-500 mb-3 font-medium">Se matriculará al personal perteneciente a estas áreas.</p>
                            <div class="mb-3">
                                <input type="text" x-model="busquedaAreaPCI" placeholder="Buscar área..." 
                                    class="w-full border border-teal-200 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-teal-400 focus:border-teal-400 outline-none shadow-sm"
                                    @keydown.enter.prevent>
                            </div>
                            <div class="border border-teal-100 rounded-md p-3 overflow-y-auto bg-white custom-scrollbar shadow-inner" style="max-height: 160px;">
                                <div class="grid grid-cols-1 gap-2">
                                    <template x-for="ar in areasPCIFiltradas" :key="ar.codigo">
                                        <label class="flex items-start space-x-2 cursor-pointer hover:bg-slate-50 p-2 rounded-md border border-transparent hover:border-slate-200 transition-all">
                                            <input type="checkbox" :value="ar.codigo" x-model="areasAsignadas" 
                                                class="mt-0.5 w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                            <span class="text-sm font-medium text-gray-700">
                                                <span x-text="ar.codigo" class="text-xs text-gray-500 bg-gray-200 px-1.5 py-0.5 rounded mr-1"></span>
                                                <span x-text="ar.descripcion"></span>
                                            </span>
                                        </label>
                                    </template>
                                </div>
                                <div x-show="areasPCIFiltradas.length === 0" class="text-gray-400 text-sm text-center py-4">
                                    No se encontraron áreas asociadas.
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Área de Conocimiento -->
                    <div>
                        <label class="text-gray-800 text-sm font-medium inline-block mb-1 text-primary">
                        Sistema de Gestión <span class="text-danger">*</span>
                        </label>
                        
                        <div x-data="{
                            open: false,
                            searchTerm: '',
                            options: window.opcionesArea || [],
                            get filteredOptions() {
                                if (this.searchTerm === '') return this.options;
                                return this.options.filter(option => 
                                    option.descripcion.toLowerCase().includes(this.searchTerm.toLowerCase())
                                );
                            },
                            selectOption(option) {
                                this.$dispatch('update-area-conocimiento', option ? option.codigo : '');
                                this.searchTerm = '';
                                this.open = false;
                            },
                            init() {
                                if (window.opcionesArea && window.opcionesArea.length > 0) {
                                    this.options = window.opcionesArea;
                                }
                            },
                            get currentDescription() {
                               const found = this.options.find(opt => opt.codigo == this.areaConocimiento);
                               return found ? found.descripcion : '';
                            }
                        }" 
                        @areas-loaded.window="options = $event.detail"
                        @update-area-conocimiento="areaConocimiento = $event.detail; area = $event.detail"
                        class="relative w-full">
                            
                            <!-- Botón que simula el select -->
                            <button @click="open = !open" 
                                    type="button"
                                    class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-left text-sm flex justify-between items-center focus:outline-none focus:ring-1 focus:ring-primary h-[38px] transition-all">
                                <span :class="areaConocimiento ? 'text-gray-800' : 'text-gray-400'"
                                      x-text="currentDescription || 'Seleccione Sistema'"></span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <!-- Dropdown con búsqueda -->
                            <div x-show="open" 
                                 @click.away="open = false"
                                 class="absolute mt-1 w-full border border-gray-300 rounded-lg shadow-2xl overflow-hidden"
                                 style="display: none; background-color: white !important; opacity: 1 !important; z-index: 99999 !important;">
                                
                                <div class="p-2 border-b border-gray-100" style="background-color: white !important;">
                                    <input type="text" 
                                           x-model="searchTerm"
                                           class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-primary"
                                           placeholder="Buscar sistema...">
                                </div>

                                <ul class="max-h-60 overflow-y-auto py-1" style="background-color: white !important;">
                                    <template x-for="option in filteredOptions" :key="option.codigo">
                                        <li @click="selectOption(option)"
                                            class="px-3 py-2 text-sm hover:bg-primary/10 hover:text-primary cursor-pointer transition-colors"
                                            :class="{ 'bg-primary/5 text-primary font-medium': areaConocimiento == option.codigo }">
                                            <span x-text="option.descripcion"></span>
                                        </li>
                                    </template>
                                    <template x-if="filteredOptions.length === 0">
                                        <li class="px-3 py-2 text-sm text-gray-500 italic text-center">
                                            No se encontraron sistemas
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </div>


                    <!-- Frecuencia -->
                    <div x-show="!esDemanda" x-transition>
                        <label for="slcFrecuencia" class="text-gray-800 text-sm font-medium inline-block mb-1">Frecuencia</label>
                        <select id="slcFrecuencia" x-model="frecuencia" 
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white">
                            <option value="">-- Seleccione Frecuencia --</option>
                            <option value="MENSUAL">Mensual</option>
                            <option value="BIMESTRAL">Bimestral</option>
                            <option value="TRIMESTRAL">Trimestral</option>
                            <option value="CUATRIMESTRAL">Cuatrimestral</option>
                            <option value="SEMESTRAL">Semestral</option>
                            <option value="ANUAL">Anual</option>
                        </select>
                    </div>

                    <!-- SELECTOR SUCURSALES (SOLO PAC) -->
                    <div x-show="esPAC" x-transition class="mt-2 bg-indigo-50/50 border border-indigo-100 rounded-lg p-5">
                        <label class="text-indigo-800 text-sm font-bold tracking-wide inline-block mb-2">
                            <i class="bx bx-buildings mr-1"></i> Sucursales Asignadas <span class="text-red-500">*</span>
                        </label>
                        <div class="mb-3">
                            <input type="text" x-model="busquedaSucursal" placeholder="Buscar sucursal por nombre..." 
                                class="w-full border border-indigo-200 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400 outline-none shadow-sm"
                                @keydown.enter.prevent>
                        </div>
                        <div class="border border-indigo-100 rounded-md p-3 overflow-y-auto bg-white custom-scrollbar shadow-inner" style="max-height: 160px;">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <template x-for="suc in sucursalesFiltradas" :key="suc">
                                    <label class="flex items-center space-x-2 cursor-pointer hover:bg-slate-50 p-2 rounded-md border border-transparent hover:border-slate-200 transition-all">
                                        <input type="checkbox" :value="suc" x-model="sucursalesAsignadas" 
                                            class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-xs font-medium text-gray-700" x-text="suc"></span>
                                    </label>
                                </template>
                            </div>
                            <div x-show="sucursalesFiltradas.length === 0" class="text-gray-400 text-sm text-center py-4">
                                No se encontraron sucursales asociadas.
                            </div>
                        </div>
                        <p class="text-xs text-indigo-500 mt-2 font-medium">Seleccione explícitamente las sucursales donde este curso estará activo.</p>
                    </div>

                    <!-- NUEVO: Obligatorio al Alta -->
                    <div class="flex items-center gap-6 -mt-1 mb-1">
                        <div class="flex items-center">
                            <input class="form-switch" type="checkbox" role="switch" id="chkObligatorioAlta" x-model="obligatorioAlta" class="cursor-pointer">
                            <label for="chkObligatorioAlta" class="ml-2 text-gray-800 text-sm font-medium cursor-pointer">Obligatorio al Alta</label>
                        </div>
                        <div class="flex items-center">
                            <input class="form-switch" type="checkbox" role="switch" id="chkEsDemanda" x-model="esDemanda" class="cursor-pointer">
                            <label for="chkEsDemanda" class="ml-2 text-gray-800 text-sm font-medium cursor-pointer">Es por Demanda</label>
                        </div>
                    </div>

                    <!-- NUEVO: Responsable (Estilo Escritorio) -->
                    <div class="mt-4 bg-gray-50/50 border border-gray-100 rounded-lg p-3 shadow-sm">
                        <label class="text-indigo-800 text-[11px] font-bold uppercase tracking-wider mb-2 block">
                            <i class="bx bx-user-check mr-1"></i> Responsable (Administrativo 5)
                        </label>
                        
                        <div class="flex items-center gap-2">
                            <!-- Input Código -->
                            <div class="w-24">
                                <input type="text" x-model="codResponsable" readonly
                                    class="w-full bg-gray-100 border border-gray-300 rounded px-2 py-1.5 text-xs text-center font-mono text-gray-600 cursor-default"
                                    placeholder="Código">
                            </div>

                            <!-- Botón Búsqueda (Modal) -->
                            <div class="relative" x-data="searchablePersonnel()">
                                <button type="button" @click="toggle()"
                                    class="px-3 py-1.5 bg-white border border-gray-300 rounded text-gray-600 hover:bg-gray-50 active:bg-gray-100 transition-colors shadow-sm font-bold">
                                    ...
                                </button>

                                <!-- Lista Desplegable (Layout Amplio) -->
                                <div x-show="open" @click.away="open = false" x-transition
                                    class="absolute z-[100] mt-1 left-0 w-[90vw] md:w-[650px] max-w-4xl bg-white border border-gray-200 rounded-md shadow-2xl overflow-hidden flex flex-col"
                                    style="display: none;">
                                    
                                    <div class="p-3 border-b bg-indigo-50/50 flex items-center gap-2">
                                        <i class="bx bx-search text-indigo-500 text-lg"></i>
                                        <input type="text" x-model="query" @input.debounce.300ms="search()"
                                            class="w-full border-none bg-transparent p-1 text-sm focus:ring-0 outline-none text-gray-700 font-medium"
                                            placeholder="Buscar por nombre o DNI en Administrativos 5...">
                                    </div>

                                    <!-- Cabecera de "Tabla" - Usando Divs con Flex para evitar bug 'after' de Alpine -->
                                    <div class="bg-indigo-50 px-4 py-2 border-b flex gap-2 text-[10px] font-bold text-gray-500 uppercase flex-shrink-0">
                                        <div class="w-16 shrink-0 text-center">Código</div>
                                        <div class="flex-1 px-4 border-l border-indigo-100">Nombre Completo</div>
                                        <div class="w-24 shrink-0 text-center border-l border-indigo-100">DNI</div>
                                        <div class="w-28 shrink-0 text-center border-l border-indigo-100 italic">Sucursal</div>
                                    </div>

                                    <div class="overflow-y-auto custom-scrollbar bg-white" style="max-height: 280px !important;">
                                        <template x-for="(p, index) in results" :key="p.codigo + '-' + index">
                                            <div @click="select(p)" 
                                                class="px-4 py-2 text-[11px] hover:bg-indigo-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors group flex items-center gap-2 min-h-[36px]">
                                                <div class="w-16 shrink-0 font-mono text-gray-400 group-hover:text-indigo-600 font-bold text-center" x-text="p.codigo"></div>
                                                <div class="flex-1 px-4 font-bold text-gray-800 group-hover:text-indigo-700 truncate" x-text="p.nombre_completo"></div>
                                                <div class="w-24 shrink-0 text-center text-gray-600 font-medium" x-text="p.dni"></div>
                                                <div class="w-28 shrink-0 text-center text-gray-500 italic truncate text-[10px]" x-text="p.sucursal || 'N/A'"></div>
                                            </div>
                                        </template>
                                        
                                        <!-- Estados -->
                                        <div x-show="loading" class="p-8 text-center text-xs text-indigo-500 font-medium">
                                            <i class="bx bx-loader-alt bx-spin mr-2 text-lg align-middle"></i> Buscando responsables...
                                        </div>
                                        
                                        <div x-show="error" class="p-4 text-center text-[11px] text-red-500 bg-red-50" x-text="error"></div>
                                        
                                        <div x-show="!loading && !error && results.length === 0" class="p-10 text-center text-xs text-gray-400 flex flex-col items-center gap-2">
                                            <i class="bx bx-search-alt-2 text-3xl opacity-20"></i>
                                            <span>No se encontraron coincidencias</span>
                                        </div>
                                    </div>

                                    <!-- Footer Informativo -->
                                    <div class="bg-indigo-600 p-2 text-[10px] text-center text-white font-bold flex justify-between px-4 items-center">
                                        <span class="flex items-center gap-1"><i class="bx bx-check-shield text-sm"></i> ADMINISTRATIVOS ACTIVOS</span>
                                        <span class="bg-white/20 px-2 py-0.5 rounded text-[9px]" x-text="results.length + ' RESULTADOS'"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Input Nombre -->
                            <div class="flex-1">
                                <input type="text" x-model="nombreResponsable" readonly
                                    class="w-full bg-gray-100 border border-gray-300 rounded px-3 py-1.5 text-xs text-gray-600 cursor-default"
                                    placeholder="Nombre completo del responsable">
                            </div>
                        </div>
                    </div>

                    <!-- NUEVO: Observaciones -->
                    <div>
                        <label class="text-gray-800 text-sm font-medium inline-block mb-1">Observaciones</label>
                        <textarea rows="2" class="w-full border border-gray-300 rounded px-3 py-2 text-sm resize-none" placeholder="Ingrese observaciones o detalles adicionales..."></textarea>
                    </div>

                </div>
                <div class="w-full mt-4 flex flex-col items-center">
                    <div class="w-full flex items-center justify-between gap-4">
                        <div class="flex-1 border-t border-gray-200"></div>
                        <h3 class="text-lg font-semibold text-primary text-center">Datos del Examen</h3>
                        <div class="flex-1 border-t border-gray-200"></div>
                    </div>
                    
                    <!-- NUEVO: Aplica Evaluación -->
                    <div class="flex items-center mt-3 mb-2 bg-indigo-50 border border-indigo-100 px-4 py-2 rounded-lg shadow-sm w-fit">
                        <input class="form-switch" type="checkbox" role="switch" id="chkAplicaEvaluacion" x-model="aplicaEvaluacion" checked>
                        <label for="chkAplicaEvaluacion" class="ml-2 text-sm font-semibold text-indigo-900 cursor-pointer">Aplica Evaluación</label>
                    </div>
                </div>

                <!-- Contenedor condicional para Examen -->
                <div x-show="aplicaEvaluacion" x-transition.duration.300ms class="w-full border border-gray-100 bg-gray-50/30 p-4 rounded-xl shadow-inner mt-2">
                    <div class="w-full grid gap-4 mt-2 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 pb-2">
                        <div>
                            <label for="txtLimite" class="text-gray-800 text-sm font-medium inline-block mb-1">
                            Límite de tiempo (minutos)
                            </label>
                            <input type="number" id="txtLimite"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400 focus:outline-none"
                            x-model="limiteTiempo" placeholder=""
                            />
                        </div>
                        <div>
                            <label for="txtNota" class="text-gray-800 text-sm font-medium inline-block mb-1">
                            Nota mínima
                            </label>
                            <input type="number" id="txtNota"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400 focus:outline-none"
                            x-model="nota" placeholder=""
                            />
                        </div>
                        <div>
                            <label for="txtIntentos" class="text-gray-800 text-sm font-medium inline-block mb-1">
                            Número de intentos
                            </label>
                            <input type="number" id="txtIntentos"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400 focus:outline-none"
                            x-model="intentos" placeholder=""
                            />
                        </div>
                        <div>
                            <label for="txtCantidadPreguntas" class="text-gray-800 text-sm font-medium inline-block mb-1">
                            Cantidad De Preguntas
                            </label>
                            <input type="number" id="txtCantidadPreguntas"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400 focus:outline-none"
                            x-model="cantidadPreguntas" placeholder=""
                            />
                        </div>
                        <div>
                            <label for="txtPreguntasBalotario" class="text-gray-800 text-sm font-medium inline-block mb-1">
                            Preguntas en el balotario
                            </label>
                            <input type="number" id="txtPreguntasBalotario"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400 focus:outline-none"
                            x-model="preguntasBalotario" placeholder=""
                            />
                        </div>
                    </div>
                </div>

                <div class="flex flex-col py-5" x-show="aplicaEvaluacion" x-transition.opacity>
                    <!-- NUEVO: IMPORTADOR IA 2026 -->
                    <div class="border border-dashed border-blue-300 bg-blue-50/40 rounded-xl p-5 shadow-sm">
                        <label class="text-primary text-[11px] font-black uppercase tracking-widest mb-3 flex items-center">
                            <i class="bx bxs-zap mr-1.5 text-base"></i> Módulo Inteligente (Beta 2026)
                        </label>
                        <div class="flex flex-col sm:flex-row items-center gap-3">
                            <div class="flex-1 w-full bg-white border border-blue-200 rounded-lg px-4 py-2.5 flex items-center justify-between shadow-sm focus-within:ring-2 focus-within:ring-blue-100 transition-all">
                                <span class="text-xs text-blue-700 font-medium" :class="!archivoIANombre ? 'italic text-blue-400' : ''" x-text="archivoIANombre || 'Subir examen (.dot, .docx) para procesar con IA...'"></span>
                                <button x-show="archivoIANombre" type="button" @click="archivoIANombre = ''; archivoIA = null; preguntasExamenIA = []" class="text-red-400 hover:text-red-600 transition-colors ml-2">
                                    <i class="bx bx-trash text-lg"></i>
                                </button>
                            </div>
                            <div class="flex gap-2 w-full sm:w-auto">
                                <button type="button" @click="$refs.inputIA.click()" class="flex-1 sm:flex-none btn btn-sm bg-blue-600 text-white hover:bg-blue-700 transition-all rounded-lg px-5 shadow-sm font-bold h-[42px] flex items-center justify-center">
                                    <i class="bx bx-upload mr-2 text-lg"></i> Cargar Word
                                </button>
                                <input type="file" x-ref="inputIA" class="hidden" accept=".doc,.docx,.dot" 
                                       @change="archivoIA = $event.target.files[0]; archivoIANombre = $event.target.files[0].name"
                                       title="archivoIA">
                                
                                <button type="button" @click="analizarConIA()" :disabled="!archivoIA || cargandoIA"
                                        class="flex-1 sm:flex-none btn btn-sm bg-indigo-100 text-indigo-700 hover:bg-indigo-600 hover:text-white transition-all rounded-lg px-5 border border-indigo-200 font-bold disabled:opacity-50 h-[42px] flex items-center justify-center">
                                    <i x-show="!cargandoIA" class="bx bxs-magic-wand mr-2 text-lg"></i>
                                    <i x-show="cargandoIA" class="bx bx-loader-alt bx-spin mr-2 text-lg"></i>
                                    Analizar con IA
                                </button>
                                
                                <button type="button" @click="verVistaPrevia()" x-show="preguntasExamenIA.length > 0" x-transition
                                        class="flex-1 sm:flex-none btn btn-sm bg-emerald-100 text-emerald-700 hover:bg-emerald-600 hover:text-white transition-all rounded-lg px-5 border border-emerald-200 font-bold h-[42px] flex items-center justify-center shadow-sm">
                                    <i class="bx bxs-show mr-2 text-lg"></i> Previsualizar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NUEVO: Auditoría / Metadatos -->
                <div class="w-full mt-2 mb-4 bg-gray-50/70 border border-gray-200 rounded-lg p-5 opacity-60 pointer-events-none select-none">
                    <h5 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 border-b border-gray-200 pb-2">Información de Sistema <span class="text-[10px] ml-2 lowercase font-normal italic">(Solo lectura - Próximamente)</span></h5>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-y-4 gap-x-6">
                        <div class="flex flex-col">
                            <span class="text-[11px] text-gray-500 font-medium uppercase tracking-wide">Código Interno</span>
                            <span class="text-gray-400 font-bold bg-gray-100 border border-gray-200 px-3 py-1.5 rounded mt-1.5 w-fit min-w-[70px] text-center shadow-sm">-</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[11px] text-gray-500 font-medium uppercase tracking-wide">Registrado por</span>
                            <div class="text-xs text-gray-400 mt-1.5 flex flex-col gap-0.5">
                                <span class="font-medium text-gray-400"><i class="bx bx-user mr-1 text-gray-300"></i>(-) -</span>
                                <span class="text-gray-400 ml-5">-</span>
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[11px] text-gray-500 font-medium uppercase tracking-wide">Última modificación</span>
                            <div class="text-xs text-gray-400 mt-1.5 flex flex-col gap-0.5">
                                <span class="font-medium text-gray-400"><i class="bx bx-user-pin mr-1 text-gray-300"></i>(-) -</span>
                                <span class="text-gray-400 ml-5">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center w-full py-2">
                    <button type="submit" id="btnGestion" @click="registrar"
                    class="btn rounded-full bg-success/25 text-success hover:bg-success hover:text-white">
                        Registrar Curso&nbsp;<i class="fa-solid fa-floppy-disk"></i>
                    </button>
                    <button type="button" id="btnGestionEditar" onclick="editarFormGestionCurso()"
                    class="hidden btn rounded-full bg-warning/25 text-warning hover:bg-warning hover:text-white ">
                        Actualizar curso
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Panel Apertura de Ciclo (Primer Ciclo) -->
        <div x-show="panel === 'apertura_manual'" x-transition style="display: none;" x-data="modalApertura()" @open-apertura-modal.window="openModal($event.detail)">
            <div class="card-header">
                <div class="flex items-center justify-between">
                    <h4 class="card-title">Aperturar 1er Ciclo: <span x-text="cursoNombre" class="text-primary font-bold"></span></h4>
                    <button type="button" @click="closeModal()" title="Cerrar y volver a Registro" class="btn btn-sm rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200">
                        <i class="bx bx-x text-lg"></i>
                    </button>
                </div>
            </div>
            
            <div class="card-body">
                <div class="flex flex-col h-full min-h-[400px]">
                    <div class="flex-grow flex flex-col items-center pt-8">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary/20 mb-6">
                            <i class="bx bx-calendar-star text-primary text-3xl"></i>
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-100 rounded-lg p-5 mb-8 w-full max-w-lg shadow-sm">
                            <div class="flex">
                                <div class="flex-shrink-0 mt-0.5">
                                    <i class="bx bx-info-circle text-blue-500 text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">Sobre la Programación Manual</h3>
                                    <p class="text-sm text-blue-700 mt-2">
                                        Selecciona el <strong>mes de la campaña</strong>. 
                                        El curso se habilitará desde el primer hasta el último día de ese mes. 
                                        Se matriculará masivamente a todo el personal activo asignado.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="w-full max-w-sm mb-4">
                            <label for="fecha_inicio_modal" class="block text-sm font-semibold leading-6 text-gray-900 text-center mb-2">Mes de la Campaña (Año y Mes)</label>
                            <input type="month" x-model="fechaInicio" id="fecha_inicio_modal" class="block w-full rounded-md border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary text-center text-lg sm:leading-6">
                        </div>

                        <!-- NUEVO: Filtros Grupales (Punto 11) -->
                        <div x-show="!incluirAutomatico" x-transition class="w-full max-w-lg mt-4 bg-gray-50 border border-gray-200 rounded-lg p-4 shadow-sm">
                            <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-200">
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    <i class="bx bx-filter-alt mr-1"></i> Criterios de Selección
                                </h4>
                                <!-- Switch Matrícula Automática -->
                                <div class="flex items-center gap-2 bg-white px-2 py-1 rounded-md border border-gray-200">
                                    <span class="text-[10px] font-bold text-gray-500 uppercase">Automático</span>
                                    <input class="form-switch scale-75" type="checkbox" role="switch" x-model="incluirAutomatico" id="swIncluirAuto">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Cliente -->
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">Cliente</label>
                                    <select x-model="selectedCliente" class="w-full text-xs rounded border-gray-300 py-1.5 focus:ring-primary focus:border-primary">
                                        <option value="">-- Todos --</option>
                                        <template x-for="item in combosApertura.clientes" :key="item.codigo">
                                            <option :value="item.codigo" x-text="item.nombre"></option>
                                        </template>
                                    </select>
                                </div>
                                <!-- Sede -->
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">Sede / Sucursal</label>
                                    <select x-model="selectedSucursal" class="w-full text-xs rounded border-gray-300 py-1.5 focus:ring-primary focus:border-primary">
                                        <option value="">-- Todas --</option>
                                        <template x-for="item in combosApertura.sucursales" :key="item.codigo">
                                            <option :value="item.codigo" x-text="item.nombre"></option>
                                        </template>
                                    </select>
                                </div>
                                <!-- Área -->
                                <div class="sm:col-span-2">
                                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">Área / Sistema de Gestión</label>
                                    <select x-model="selectedArea" class="w-full text-xs rounded border-gray-300 py-1.5 focus:ring-primary focus:border-primary">
                                        <option value="">-- Todas --</option>
                                        <template x-for="item in combosApertura.areas" :key="item.codigo">
                                            <option :value="item.codigo" x-text="item.nombre"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div x-show="!incluirAutomatico" x-transition class="w-full max-w-lg mt-6">
                            <label class="block text-sm font-semibold leading-6 text-gray-700 mb-2">
                                <i class="bx bx-list-ol mr-1"></i> (Opcional) Pegar lista de DNIs
                            </label>
                            <p class="text-[11px] text-gray-500 mb-2 italic">Si pega DNIs aquí, el sistema los matriculará directamente junto con la segmentación automática.</p>
                            <textarea x-model="listaDNIPaste" rows="4" 
                                class="block w-full rounded-md border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary text-sm placeholder:text-gray-400"
                                placeholder="Pegue una columna de DNIs aquí (uno por línea)..."></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-center w-full gap-4 py-8 mt-6 border-t border-gray-100">
                    <button type="button" @click="guardarApertura()" :disabled="cargando" class="btn rounded-full bg-primary/25 text-primary hover:bg-primary hover:text-white transition-colors px-6 shadow-sm disabled:opacity-50">
                        <span x-show="!cargando" class="flex items-center"><i class="bx bx-calendar-star text-base mr-2"></i> Aperturar Ciclo</span>
                        <span x-show="cargando" class="flex items-center"><i class="bx bx-loader-alt bx-spin text-base mr-2"></i> Procesando...</span>
                    </button>
                    <button type="button" @click="closeModal()" :disabled="cargando" class="btn rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors px-6 disabled:opacity-50">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>

@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
@endsection

@vite(['resources/js/functions/capacitacion/gestion_cursos.js'])

{{-- ============================================================ --}}
{{-- MODAL IA 2026 - Independiente del layout principal           --}}
{{-- Debe estar FUERA del @section('content') para que            --}}
{{-- position:fixed funcione correctamente sobre toda la UI       --}}
{{-- ============================================================ --}}
<div id="modal-ia-2026"
     x-data="modalIA2026()"
     @abrir-modal-ia.window="abrirModalIA($event.detail.preguntas, $event.detail.cursoId, $event.detail.examenId, $event.detail.nombreArc)"
     style="display:contents">

    <div x-show="mostrarModalIA"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,0.85);backdrop-filter:blur(8px)">

        <div style="background:#f8fafc;border-radius:1.25rem;width:100%;max-width:1100px;max-height:88vh;display:flex;flex-direction:column;overflow:hidden;border:1px solid rgba(255,255,255,0.15);box-shadow:0 25px 60px -15px rgba(0,0,0,0.5)">

            {{-- Header --}}
            <div style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);padding:1.25rem 1.75rem;display:flex;justify-content:space-between;align-items:center;flex-shrink:0">
                <div>
                    <h3 style="color:#fff;font-size:1rem;font-weight:800;margin:0;display:flex;align-items:center;gap:0.5rem">
                        <i class="bx bxs-bot" style="color:#6366f1;font-size:1.35rem"></i>
                        Revisión Inteligente de Examen
                        <span style="background:rgba(255,255,255,0.1);color:#cbd5e1;font-size:0.6rem;padding:0.15rem 0.6rem;border-radius:100px;border:1px solid rgba(255,255,255,0.15);font-weight:900;letter-spacing:0.1em">IA 2026</span>
                    </h3>
                    <p style="color:#64748b;font-size:0.7rem;margin:0.15rem 0 0;display:flex;align-items:center;gap:0.25rem">
                        <i class="bx bx-file-blank"></i> Fuente: <span x-text="archivoIANombre" style="color:#94a3b8"></span>
                    </p>
                </div>
                <button @click="mostrarModalIA=false" style="width:2rem;height:2rem;border-radius:50%;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);color:#94a3b8;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='rgba(255,255,255,0.08)'">
                    <i class="bx bx-x" style="font-size:1.25rem"></i>
                </button>
            </div>

            {{-- Sub-header info --}}
            <div style="padding:0.75rem 1.75rem;background:#f1f5f9;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
                <p style="font-size:0.7rem;color:#64748b;margin:0">
                    <i class="bx bx-info-circle" style="color:#3b82f6"></i>
                    Valide las respuestas correctas antes de guardar. Use los selects para cambiar el tipo de pregunta.
                </p>
                <div style="display:flex;gap:0.5rem">
                    <span style="font-size:0.65rem;font-weight:700;padding:0.2rem 0.5rem;background:#fff;border:1px solid #e2e8f0;border-radius:0.375rem;color:#475569;display:flex;align-items:center;gap:0.3rem">
                        <span style="width:0.5rem;height:0.5rem;border-radius:50%;background:#3b82f6;display:inline-block"></span> Teoría
                    </span>
                    <span style="font-size:0.65rem;font-weight:700;padding:0.2rem 0.5rem;background:#fff;border:1px solid #e2e8f0;border-radius:0.375rem;color:#475569;display:flex;align-items:center;gap:0.3rem">
                        <span style="width:0.5rem;height:0.5rem;border-radius:50%;background:#f97316;display:inline-block"></span> Razonamiento
                    </span>
                </div>
            </div>

            {{-- Grid de preguntas (scroll interno) --}}
            <div style="flex:1;overflow-y:auto;padding:1.25rem 1.5rem;background:#f8fafc">
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.875rem">
                    <template x-for="(p, index) in preguntasIA" :key="index">
                        <div style="background:#fff;border-radius:0.75rem;border:1px solid #e2e8f0;border-left:4px solid;display:flex;flex-direction:column;overflow:hidden;transition:box-shadow 0.2s"
                             :style="p.tipo=='A' ? 'border-left-color:#3b82f6' : 'border-left-color:#f97316'">

                            {{-- Card Header --}}
                            <div style="padding:0.5rem 0.75rem;background:#f8fafc;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center">
                                <span style="font-size:0.6rem;font-weight:900;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;display:flex;align-items:center;gap:0.25rem">
                                    P. <span x-text="index+1" style="background:#e2e8f0;color:#475569;width:1.1rem;height:1.1rem;border-radius:0.25rem;display:inline-flex;align-items:center;justify-content:center;font-size:0.6rem"></span>
                                </span>
                                <select x-model="p.tipo" style="font-size:0.65rem;font-weight:700;border:1px solid #e2e8f0;border-radius:0.375rem;padding:0.15rem 0.4rem;background:transparent;color:#475569;cursor:pointer">
                                    <option value="A">Teoría</option>
                                    <option value="B">Razon. (B)</option>
                                </select>
                            </div>

                            {{-- Card Body --}}
                            <div style="padding:0.75rem;flex:1">
                                <p style="font-size:0.72rem;font-weight:700;color:#1e293b;margin:0 0 0.625rem;line-height:1.35" x-text="p.pregunta"></p>
                                <div style="display:flex;flex-direction:column;gap:0.35rem">
                                    <template x-for="(opt, optIndex) in p.opciones" :key="optIndex">
                                        <label style="display:flex;align-items:center;padding:0.375rem 0.5rem;border-radius:0.5rem;border:1px solid;cursor:pointer;transition:all 0.15s"
                                               :style="p.respuesta_correcta==optIndex ? 'border-color:#86efac;background:#f0fdf4' : 'border-color:#f1f5f9;background:#fafafa'">
                                            <input type="radio" :name="'resp_'+index" :value="optIndex" x-model="p.respuesta_correcta"
                                                   style="width:0.8rem;height:0.8rem;accent-color:#16a34a;flex-shrink:0">
                                            <span style="margin-left:0.5rem;font-size:0.68rem;font-weight:500;color:#475569;flex:1;line-height:1.3" x-text="opt"></span>
                                            <i x-show="p.respuesta_correcta==optIndex" class="bx bxs-check-circle" style="color:#16a34a;font-size:0.85rem;margin-left:auto;flex-shrink:0"></i>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Footer --}}
            <div style="padding:1rem 1.75rem;background:#fff;border-top:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;flex-shrink:0">
                <div style="display:flex;align-items:center;gap:0.5rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:0.625rem;padding:0.5rem 0.875rem">
                    <i class="bx bx-info-circle" style="color:#3b82f6;font-size:1rem"></i>
                    <span style="font-size:0.7rem;color:#1d4ed8;font-weight:600">Las preguntas se guardarán automáticamente al crear el curso</span>
                </div>
                <div style="display:flex;gap:0.75rem">
                    <button @click="mostrarModalIA=false" style="padding:0.6rem 1.25rem;border-radius:0.75rem;font-size:0.75rem;font-weight:700;color:#64748b;background:transparent;border:1px solid #e2e8f0;cursor:pointer;transition:all 0.2s" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                        Cancelar
                    </button>
                    <button @click="mostrarModalIA=false" style="padding:0.6rem 1.75rem;border-radius:0.75rem;font-size:0.75rem;font-weight:900;color:#fff;background:linear-gradient(135deg,#2563eb,#4f46e5);border:none;cursor:pointer;display:flex;align-items:center;gap:0.5rem;box-shadow:0 4px 15px -3px rgba(37,99,235,0.5);transition:all 0.2s;text-transform:uppercase;letter-spacing:0.05em">
                        <i class="bx bxs-check-circle"></i>
                        Confirmar Vista Previa
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function modalIA2026() {
    return {
        mostrarModalIA: false,
        cargandoIA: false,
        preguntasIA: [],
        tiempoExamenIA: 60,
        codExamenActual: null,
        codCursoActual: null,
        archivoIANombre: '',

        abrirModalIA(data, cursoId, examenId, nombreArc) {
            this.preguntasIA = Array.isArray(data) ? data : [];
            this.codCursoActual = cursoId;
            this.codExamenActual = examenId;
            this.archivoIANombre = nombreArc || '';
            this.mostrarModalIA = true;
        },

        async confirmarGuardadoIA() {
            if (this.tiempoExamenIA < 1) {
                Swal.fire('Validación', 'Debe ingresar un tiempo límite válido.', 'warning');
                return;
            }
            this.cargandoIA = true;
            try {
                const appUrl = '{{ env('APP_URL', '') }}';
                const response = await fetch(appUrl + '/api/capacitacion/guardar-examen-ia', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        cod_curso: this.codCursoActual,
                        cod_examen: this.codExamenActual,
                        preguntas: this.preguntasIA,
                        tiempo: this.tiempoExamenIA
                    })
                });
                const res = await response.json();
                if (res.success) {
                    Swal.fire('¡Éxito!', res.message, 'success');
                    this.mostrarModalIA = false;
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'No se pudo guardar el examen.', 'error');
            } finally {
                this.cargandoIA = false;
            }
        }
    };
}
</script>
