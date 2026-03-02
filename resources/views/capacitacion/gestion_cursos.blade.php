@extends('layouts.vertical', ['title' => 'Gestión de cursos'])
@section('css')
@endsection
@section('content')
@include("layouts.shared/page-title", ["subtitle" => "Capacitación", "title" => "Gestión de cursos"])


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
                class="flex flex-wrap items-end gap-6"
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

                <div class="flex flex-col flex-1 min-w-[200px]">
                <label for="slcFiltroTipoCurso" class="text-sm font-medium text-gray-700 mb-1">
                    Tipo de curso
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
                    class="flex flex-col flex-1 min-w-[200px] max-w-full"
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
                        Plan de capacitación
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

                <div x-effect="listarCursos( soloEliminados ? 0 : 1, filtroArea, filtroTipoCurso )"></div>
            </div>

            <div class="mt-5 overflow-x-auto w-full">
                <table id="tblCursos" class="datatable responsive-table w-full">
                <thead>
                    <tr>
                    <th>#</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Acciones</th>
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
            <div class="card-body" x-data="formCursoGestion()" @submit.prevent>
                <input type="hidden" name="codGestionEditar" x-model="codigo" id="codGestionEditar">
                <input type="hidden" id="slcArea" x-model="area">
                <div class="w-full mt-4">
                    <h3 class="text-lg font-semibold text-default-700 text-center mb-1">Datos del curso</h3>
                    <hr>
                </div>
                <div class="w-full grid gap-6 mt-4 lg:grid-cols-1 pb-8">
                    <div>
                        <label for="txtNombreCurso" class="text-gray-800 text-base font-medium inline-block mb-2">
                        Nombre del curso
                        </label>
                        <input type="text" id="txtNombreCurso"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm "
                        x-model="nombre" />
                    </div>
                    <div>
                        <label for="slcTipoCurso" class="text-gray-800 text-base font-medium inline-block mb-2">
                        Tipo de Curso
                        </label>
                        <select id="slcTipoCurso"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm "
                        x-model="tipoCurso"
                        x-data="{ tipos: window.opcionesTipoCurso || [] }"
                        @tipo-curso-loaded.window="tipos = $event.detail"
                        @change="checkEsPAC()">
                            <option value="">-- Seleccione --</option>
                            <template x-for="tipo in tipos" :key="tipo.codigo">
                                <option :value="tipo.codigo" x-text="tipo.descripcion"></option>
                            </template>
                        </select>
                    </div>

                    <!-- SELECTOR SUCURSALES (SOLO PAC) -->
                    <div x-show="esPAC" x-transition>
                        <label class="text-gray-800 text-base font-medium inline-block mb-2">
                            Sucursales Asignadas <span class="text-red-500">*</span>
                        </label>
                        <div class="mb-2">
                            <input type="text" x-model="busquedaSucursal" placeholder="Buscar sucursal..." 
                                class="w-full border border-gray-300 rounded px-3 py-1 text-xs focus:ring-primary focus:border-primary"
                                @keydown.enter.prevent>
                        </div>
                        <div class="border border-gray-300 rounded p-3 overflow-y-auto bg-white custom-scrollbar" style="max-height: 160px;">
                            <template x-for="suc in sucursalesFiltradas" :key="suc">
                                <label class="flex items-center space-x-2 mb-2 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                    <input type="checkbox" :value="suc" x-model="sucursalesAsignadas" 
                                        class="rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                                    <span class="text-sm text-gray-700" x-text="suc"></span>
                                </label>
                            </template>
                            <div x-show="sucursalesFiltradas.length === 0" class="text-gray-500 text-sm text-center py-2">
                                No se encontraron sucursales.
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Seleccione las sucursales donde este curso estará disponible.</p>
                    </div>

                    <div>
                        <label class="text-gray-800 text-base font-medium inline-block mb-2">
                        Plan de capacitación
                        </label>
                        
                        <div x-data="{
                            open: false,
                            searchTerm: '',
                            options: window.opcionesArea || [],
                            init() {
                                // Watch helper if needed
                            },
                            get filteredOptions() {
                                if (this.searchTerm === '') return this.options;
                                return this.options.filter(option => 
                                    option.descripcion.toLowerCase().includes(this.searchTerm.toLowerCase())
                                );
                            },
                            selectOption(option) {
                                this.$dispatch('update-area', option ? option.codigo : '');
                                this.searchTerm = '';
                                this.open = false;
                            },
                            init() {
                                // Fix race condition: check if data already exists
                                if (window.opcionesArea && window.opcionesArea.length > 0) {
                                    this.options = window.opcionesArea;
                                }
                            },
                            get currentDescription() {
                               const found = this.options.find(opt => opt.codigo == this.area);
                               return found ? found.descripcion : '';
                            }
                        }" 
                        @areas-loaded.window="options = $event.detail"
                        @update-area="area = $event.detail"
                        class="relative w-full">
                            
                            <!-- Trigger Input (Readonly) -->
                            <div @click="open = !open" @click.outside="open = false" class="relative">
                                <input type="text" 
                                    :value="currentDescription" 
                                    placeholder="-- Seleccione Plan --" 
                                    readonly 
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm cursor-pointer bg-white pr-8 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                />
                                <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>

                            <!-- Dropdown -->
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-40 overflow-y-auto flex flex-col"
                                 style="display: none;">
                                
                                <!-- Search Input inside Dropdown -->
                                <div class="p-2 border-b border-gray-100 bg-gray-50">
                                    <input type="text" 
                                        x-model="searchTerm" 
                                        class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:border-indigo-500" 
                                        placeholder="Buscar..." 
                                        @click.stop
                                    />
                                </div>

                                <!-- Options List -->
                                <div class="overflow-y-auto max-h-48 custom-scrollbar">
                                    <template x-for="option in filteredOptions" :key="option.codigo">
                                        <div @click="selectOption(option)" 
                                             class="px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 cursor-pointer transition-colors duration-150"
                                             :class="{ 'bg-indigo-100 text-indigo-800 font-medium': area == option.codigo }">
                                            <span x-text="option.descripcion"></span>
                                        </div>
                                    </template>
                                    <div x-show="filteredOptions.length === 0" class="px-4 py-3 text-sm text-gray-500 text-center">
                                        No se encontraron resultados
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 mb-2">
                             <input type="checkbox" id="chkPeriodicidad"
                                x-model="activarPeriodicidad"
                                @change="if(!activarPeriodicidad) { frecuencia = ''; mesInicio = ''; proyeccionAnios = 1; periodicidad = 0; }"
                                class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                            <label for="chkPeriodicidad" class="text-gray-800 text-base font-medium select-none cursor-pointer">
                            Activar Periodicidad
                            </label>
                        </div>

                        <!-- Bloque de configuración de Periodicidad -->
                        <div x-show="activarPeriodicidad" x-transition class="mt-3 p-4 border border-gray-200 rounded-lg bg-gray-50 flex flex-col gap-4">
                            
                            <!-- Select Frecuencia -->
                            <div class="w-full">
                                <label for="slcFrecuencia" class="text-gray-800 text-sm font-medium mb-1 block">Frecuencia</label>
                                <select id="slcFrecuencia" x-model="frecuencia" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                    <option value="">-- Seleccione Frecuencia --</option>
                                    <option value="MENSUAL">Mensual</option>
                                    <option value="BIMESTRAL">Bimestral</option>
                                    <option value="TRIMESTRAL">Trimestral</option>
                                    <option value="CUATRIMESTRAL">Cuatrimestral</option>
                                    <option value="SEMESTRAL">Semestral</option>
                                    <option value="ANUAL">Anual</option>
                                    <option value="PERSONALIZADO">Personalizado (Manual)</option>
                                </select>
                            </div>

                            <!-- Campos dinámicos para frecuencias estructuradas -->
                            <template x-if="frecuencia && frecuencia !== 'PERSONALIZADO'">
                                <div class="grid gap-4 lg:grid-cols-2 mt-2">
                                    <div>
                                        <label for="mesInicio" class="text-gray-800 text-sm font-medium mb-1 block">Mes de Inicio</label>
                                        <input type="month" id="mesInicio" x-model="mesInicio" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label for="proyeccionAnios" class="text-gray-800 text-sm font-medium mb-1 block">Proyectar por (Años)</label>
                                        <input type="number" id="proyeccionAnios" x-model="proyeccionAnios" min="1" max="10" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                    </div>
                                    <div class="col-span-1 lg:col-span-2">
                                        <p class="text-xs text-indigo-600 bg-indigo-50 p-2 rounded border border-indigo-100 italic">
                                            <i class="fa-solid fa-info-circle mr-1"></i> Las programaciones se generarán automáticamente al guardar el curso.
                                        </p>
                                    </div>
                                </div>
                            </template>
                            
                            <!-- Mensaje para personalizado -->
                            <template x-if="frecuencia === 'PERSONALIZADO'">
                                <div class="w-full mt-2">
                                     <p class="text-xs text-amber-700 bg-amber-50 p-2 rounded border border-amber-200 italic">
                                        <i class="fa-solid fa-hand-pointer mr-1"></i> Deberá agregar manualmente las programaciones desde el panel tras guardar el curso.
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="w-full mt-8">
                    <h3 class="text-lg font-semibold text-default-700 text-center mb-1">Datos del Examen</h3>
                    <hr>
                </div>
                    <!-- Campos Eliminados: Nombre del Examen y Descripción -->
                <div class="w-full grid gap-6 mt-4 lg:grid-cols-1 pb-8">
                    <div>
                        <label for="txtLimite" class="text-gray-800 text-base font-medium inline-block mb-2">
                        Límite de tiempo (minutos)
                        </label>
                        <input type="number" id="txtLimite"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                         x-model="limiteTiempo"
                        />
                    </div>
                    <div>
                        <label for="txtNota" class="text-gray-800 text-base font-medium inline-block mb-2">
                        Nota mínima
                        </label>
                        <input type="number" id="txtNota"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                         x-model="nota"
                        />
                    </div>
                    <div>
                        <label for="txtIntentos" class="text-gray-800 text-base font-medium inline-block mb-2">
                        Número de intentos
                        </label>
                        <input type="number" id="txtIntentos"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                        x-model="intentos"
                        />
                    </div>
                </div>

                <div class="flex flex-col py-5">
                    <div class="mt-5">

                        <div class="flex items-center justify-between mb-3">
                            <label for="txtNota" class="text-gray-800 text-base font-medium inline-block mb-2" id="txtTitleFile">
                                Subir Plantilla
                            </label>
                            <a class="btn rounded-full bg-info/25 text-info hover:bg-info hover:text-white cursor-pointer hidden"
                            id="btnDownloadPlantilla">
                                <i class='bx bxs-cloud-download'></i>&nbsp;Descargar plantilla
                            </a>
                        </div>


                        <div class="mt-4">
                            <!-- Botón para seleccionar archivo -->
                            <div id="btnSeleccionar"
                                class="cursor-pointer p-12 flex justify-center bg-white border border-dashed border-default-300 rounded-xl">

                                <div class="text-center">
                                    <span class="inline-flex justify-center items-center size-16 bg-default-100 text-default-800 rounded-full cursor-pointer">
                                        <i class="i-tabler-upload size-6 shrink-0"></i>
                                    </span>
                                    <div class="mt-4 flex flex-wrap justify-center text-sm leading-6 text-default-600">
                                        <span class="pe-1 font-medium text-default-800">
                                            Arrastra tu archivo <b class="font-bold">.mbz</b> aquí o
                                        </span>
                                        <span class="bg-white font-semibold text-primary hover:text-primary-700 rounded-lg decoration-2 hover:underline">
                                            SELECCIONAR
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-default-400">Peso menor a 1MB.</p>
                                </div>
                            </div>

                            <!-- Input oculto -->
                            <input type="file" id="archivoInput" accept=".mbz" class="hidden">

                            <!-- Lista de archivos -->
                            <div class="mt-1">
                                <ul id="listaArchivos" class="mt-4 space-y-2"></ul>
                            </div>

                            <!-- Botón analizar -->
                            <div class="mt-4">
                                <button id="btnAnalizar" type="button"
                                    class="px-4 py-2 bg-primary text-white rounded hover:bg-primary-700 disabled:opacity-50"
                                    disabled>
                                    Analizar Plantilla
                                </button>
                            </div>

                            <!-- Resumen de análisis -->
                            <div id="resumenPlantilla" class="mt-4"></div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center w-full py-8">
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

        <!-- Panel de Programación Integrado -->
        <div x-show="panel === 'programacion'" x-transition style="display: none;">
            <div class="card-header flex items-center justify-between">
                <h4 class="card-title">
                    Programaciones para <span x-text="tituloProgramacion" class="text-primary font-bold"></span>
                </h4>
                <button @click="panel = 'registro'" class="btn bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-full p-2">
                    <i class="i-tabler-x text-lg"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="flex items-center justify-center gap-2" x-show="mostrarBotonRegistrarListado">
                    <button type="button"
                    class="btn rounded-full bg-primary/25 text-primary hover:bg-primary hover:text-white"
                    onclick="window.abrirModalRegistro()">
                    <i class='bx bx-plus'></i>&nbsp;Registrar Programación
                    </button>
                </div>
                <div x-show="!mostrarBotonRegistrarListado" class="w-full text-center mb-4">
                    <p class="text-sm text-gray-500 italic bg-gray-50 py-2 px-4 rounded-lg border border-gray-200 inline-block">
                        <i class="fa-solid fa-lock text-gray-400 mr-1"></i> Programaciones autogeneradas. Registro manual deshabilitado.
                    </p>
                </div>
                <div class="mt-5 overflow-y overflow-x">
                    <table id="tblProgramacion" class="datatable responsive-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Código</th>
                                <th>Periodos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="mt-6 flex justify-center">
                    <button @click="panel = 'registro'" class="btn border border-gray-300 text-gray-600 hover:bg-gray-50 rounded-full">
                         Volver al registro de cursos
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
    <!-- Modal de Registro de Programación (Movido dentro de content para asegurar renderizado) -->
    <div id="modal-registro"
        class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-y-auto hidden pointer-events-none">
        <div class="translate-y-10 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 sm:max-w-lg sm:w-full my-8 sm:mx-auto flex flex-col bg-white shadow-sm rounded">
            <div class="flex flex-col border border-default-200 shadow-sm rounded-lg pointer-events-auto">
                <div class="flex justify-between items-center py-3 px-4 border-b border-default-200">
                    <h3 class="text-lg font-medium text-default-900">Programación de Curso</h3>
                    <button type="button" class="text-default-600 cursor-pointer" id="btn-modal-docs-close"
                        data-hs-overlay="#modal-registro">
                        <i class="i-tabler-x text-lg"></i>
                    </button>
                </div>
                
                <div class="card-body p-4" x-data="formProgramacionGestion()">
                    <input type="hidden" name="codGestionEditar" x-model="codigo" id="codGestionEditarProg">
                    <div class="w-full grid gap-4 lg:grid-cols-1 pb-4">
                        <input type="hidden" id="codigoCursoInput" name="codigoCursoInput" x-model="codigoCurso">
                        <div>
                            <label for="nombreCurso" class="text-gray-800 text-sm font-medium inline-block mb-1">
                                Curso Seleccionado
                            </label>
                            <input type="text" id="nombreCurso"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-gray-100"
                                x-model="nombreCurso" readonly />
                        </div>

                        <div>
                            <label for="tipoProgramacion" class="text-gray-800 text-sm font-medium inline-block mb-1">
                                Tipo de Programación
                            </label>
                            <select id="tipoProgramacion" x-model="tipo"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <option value="REGULAR">Regular (Mes completo)</option>
                                <option value="EXTEMPORANEO">Extemporáneo (Recuperación / Fuera de fecha)</option>
                            </select>
                        </div>

                        <!-- Input de Periodo (Mes) - Solo visible en REGULAR -->
                        <div x-show="tipo === 'REGULAR'">
                            <label for="periodoInput" class="text-gray-800 text-sm font-medium inline-block mb-1">
                                Mes del Periodo
                            </label>
                            <input type="month" id="periodoInput"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                                x-model="periodo" />
                        </div>

                        <!-- Fechas Inicio y Fin - Solo visibles en EXTEMPORANEO -->
                        <div class="grid grid-cols-2 gap-4" x-show="tipo === 'EXTEMPORANEO'" x-transition>
                            <div>
                                <label class="text-gray-800 text-sm font-medium inline-block mb-1">Fecha Inicio</label>
                                <input type="date" 
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-primary focus:border-primary" 
                                    x-model="fechaInicio" />
                            </div>
                            <div>
                                <label class="text-gray-800 text-sm font-medium inline-block mb-1">Fecha Fin</label>
                                <input type="date" 
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-primary focus:border-primary" 
                                    x-model="fechaFinal" />
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-center w-full pt-4">
                        <button type="button" @click="submit()" id="btnGestionProgramacion"
                        class="btn rounded-full bg-success/25 text-success hover:bg-success hover:text-white px-8 py-2 font-medium">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Guardar Programación
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
@endsection

@vite(['resources/js/functions/capacitacion/gestion_cursos.js'])
