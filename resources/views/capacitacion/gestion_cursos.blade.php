@extends('layouts.vertical', ['title' => 'Gestión de cursos'])
@section('css')
@endsection
@section('content')
@include("layouts.shared/page-title", ["subtitle" => "Capacitación", "title" => "Gestión de cursos"])

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
                        Área del conocimiento
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
            <div class="card-body" x-data="formCursoGestion()" @submit.prevent>
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
                        <label for="slcTipoCurso" class="text-gray-800 text-sm font-medium inline-block mb-1">
                        Plan de Capacitación
                        </label>
                        <select id="slcTipoCurso"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
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

                    <!-- Área de Conocimiento -->
                    <div>
                        <label class="text-gray-800 text-sm font-medium inline-block mb-1">
                        Área del conocimiento
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
                                this.$dispatch('update-area', option ? option.codigo : '');
                                this.searchTerm = '';
                                this.open = false;
                            },
                            init() {
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
                            
                            <!-- Trigger Input -->
                            <div @click="open = !open" @click.outside="open = false" class="relative">
                                <input type="text" 
                                    :value="currentDescription" 
                                    placeholder="-- Seleccione Plan --" 
                                    readonly 
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm cursor-pointer bg-white pr-8"
                                />
                                <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>

                            <!-- Dropdown -->
                            <div x-show="open" style="display: none;"
                                 x-transition.opacity
                                 class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded shadow-lg max-h-48 flex flex-col overflow-hidden">
                                
                                <div class="p-2 border-b border-gray-100 bg-gray-50/50">
                                    <input type="text" x-model="searchTerm" 
                                        class="w-full border border-gray-200 rounded shadow-sm px-3 py-1.5 text-sm outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-400" 
                                        placeholder="Buscar..." @click.stop />
                                </div>

                                <div class="overflow-y-auto max-h-40 custom-scrollbar">
                                    <template x-for="option in filteredOptions" :key="option.codigo">
                                        <div @click="selectOption(option)" 
                                             class="px-4 py-2 text-sm text-gray-600 hover:bg-sky-50 hover:text-sky-700 cursor-pointer transition-colors"
                                             :class="{ 'bg-sky-100/50 text-sky-800 font-semibold': area == option.codigo }">
                                            <span x-text="option.descripcion"></span>
                                        </div>
                                    </template>
                                    <div x-show="filteredOptions.length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">
                                        <i class="bx bx-search mb-1 text-lg"></i><br>Sin resultados
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Frecuencia -->
                    <div>
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

                </div>
                <div class="w-full mt-4">
                    <h3 class="text-lg font-semibold text-primary text-center mb-1">Datos del Examen</h3>
                    <hr>
                </div>
                    <!-- Campos Eliminados: Nombre del Examen y Descripción -->
                <div class="w-full grid gap-4 mt-4 grid-cols-1 pb-2">
                    <div>
                        <label for="txtLimite" class="text-gray-800 text-sm font-medium inline-block mb-1">
                        Límite de tiempo (minutos)
                        </label>
                        <input type="number" id="txtLimite"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                         x-model="limiteTiempo" placeholder=""
                        />
                    </div>
                    <div>
                        <label for="txtNota" class="text-gray-800 text-sm font-medium inline-block mb-1">
                        Nota mínima
                        </label>
                        <input type="number" id="txtNota"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                         x-model="nota" placeholder=""
                        />
                    </div>
                    <div>
                        <label for="txtIntentos" class="text-gray-800 text-sm font-medium inline-block mb-1">
                        Número de intentos
                        </label>
                        <input type="number" id="txtIntentos"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                        x-model="intentos" placeholder=""
                        />
                    </div>
                    <div>
                        <label for="txtCantidadPreguntas" class="text-gray-800 text-sm font-medium inline-block mb-1">
                        Cantidad De Preguntas
                        </label>
                        <input type="number" id="txtCantidadPreguntas"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                        x-model="cantidadPreguntas" placeholder=""
                        />
                    </div>
                    <div>
                        <label for="txtPreguntasBalotario" class="text-gray-800 text-sm font-medium inline-block mb-1">
                        Preguntas en el balotario
                        </label>
                        <input type="number" id="txtPreguntasBalotario"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                        x-model="preguntasBalotario" placeholder=""
                        />
                    </div>
                </div>

                <div class="flex flex-col py-5">
                    <div class="mt-2">

                        <div class="flex items-center justify-between mb-4">
                            <label for="txtNota" class="text-gray-800 text-sm font-medium inline-block" id="txtTitleFile">
                                Subir Plantilla
                            </label>
                            <a class="btn rounded-full bg-info/25 text-info hover:bg-info hover:text-white cursor-pointer hidden text-xs px-3 py-1"
                            id="btnDownloadPlantilla">
                                <i class='bx bxs-cloud-download'></i>&nbsp;Descargar plantilla
                            </a>
                        </div>


                        <div class="mt-2">
                            <!-- Botón para seleccionar archivo -->
                            <div id="btnSeleccionar"
                                class="cursor-pointer py-8 px-4 flex justify-center bg-white border-2 border-dashed border-gray-200 hover:border-indigo-300 rounded-lg transition-colors">

                                <div class="text-center">
                                    <span class="mx-auto flex justify-center items-center w-12 h-12 bg-indigo-50 text-indigo-500 rounded-full cursor-pointer mb-3">
                                        <i class="i-tabler-upload size-5 shrink-0"></i>
                                    </span>
                                    <div class="flex flex-wrap justify-center text-sm leading-6 text-gray-500">
                                        Arrastra tu archivo .mbz aquí o&nbsp;
                                        <span class="font-semibold text-indigo-600 hover:text-indigo-500 cursor-pointer">
                                            SELECCIONAR
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-400">Peso menor a 1MB.</p>
                                </div>
                            </div>

                            <!-- Input oculto -->
                            <input type="file" id="archivoInput" accept=".mbz" class="hidden">

                            <!-- Lista de archivos -->
                            <div class="mt-1">
                                <ul id="listaArchivos" class="mt-4 space-y-2"></ul>
                            </div>

                            <!-- Botón analizar -->
                            <div class="mt-4 flex">
                                <button id="btnAnalizar" type="button"
                                    class="px-5 py-2 btn rounded-full bg-info/25 text-info hover:bg-info hover:text-white disabled:opacity-50 transition-colors cursor-pointer font-medium"
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



</div>

@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
@endsection

@vite(['resources/js/functions/capacitacion/gestion_cursos.js'])
