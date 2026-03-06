@extends('layouts.vertical', ['title' => 'Consulta de Matrículas'])
@section('css')
@endsection
@section('content')
    @include("layouts.shared/page-title", ["subtitle" => "Capacitación", "title" => "Consulta de Matrículas"])

    <div class="grid lg:grid-cols-3 grid-cols-1 gap-6">
        <!-- Panel de selección de curso -->
        <div class="card lg:col-span-1">
            <div class="card-header flex items-center justify-between">
                <h4 class="card-title">Seleccionar Curso</h4>
            </div>

            <div class="card-body">
                <div x-data="{ soloEliminados: false, filtroArea: '', filtroTipoCurso: '' }" class="space-y-4">
                    
                    <!-- Filtros -->
                    <div class="flex items-center">
                        <input class="form-switch" type="checkbox" role="switch" id="chkEliminados"
                            x-model="soloEliminados">
                        <label class="ms-1.5 font-medium text-sm text-gray-700" for="chkEliminados">
                            Solo eliminados
                        </label>
                    </div>

                    <div class="flex flex-col">
                        <label for="slcFiltroTipoCurso" class="text-sm font-medium text-gray-700 mb-1">
                            Tipo de curso
                        </label>
                        <select id="slcFiltroTipoCurso" x-model="filtroTipoCurso"
                            class="w-full rounded-lg border-gray-300 text-sm px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">-- Todos --</option>
                        </select>
                    </div>

                    <div class="flex flex-col"
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
                                this.filtroArea = option ? option.codigo : ''; 
                            }
                        }"
                        @areas-loaded.window="options = $event.detail">
                        <label class="text-sm font-medium text-gray-700 mb-1">
                            Área
                        </label>
                        
                        <div class="relative">
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

                            <div x-show="open" 
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg flex flex-col overflow-hidden"
                                style="max-height: 250px; display: none;"> <!-- Height original porque es un panel lateral pequeño -->
                                
                                <div class="p-2 border-b border-gray-100 bg-gray-50 flex-shrink-0">
                                    <input 
                                        type="text" 
                                        x-model="search" 
                                        placeholder="Buscar..." 
                                        class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 p-1.5"
                                        @click.stop
                                    >
                                </div>

                                <div 
                                    @click="selectOption(null)"
                                    class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-indigo-50 text-sm border-b border-gray-100 flex-shrink-0"
                                >
                                    <span class="block truncate font-bold text-gray-500">-- Todas --</span>
                                </div>

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

                    <div x-effect="listarCursosConsulta( soloEliminados ? 0 : 1, filtroArea, filtroTipoCurso )"></div>
                </div>

                <div class="mt-5 overflow-y-auto max-h-[400px]">
                    <table id="tblCursos" class="min-w-full text-sm">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left">Código</th>
                                <th class="px-3 py-2 text-left">Nombre</th>
                                <th class="px-3 py-2 text-center">Ver</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyCursos">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Panel Derecho: Tabs de Historial vs Matrícula -->
        <div class="card lg:col-span-2" x-data="{ tabActivo: 'personal' }" @cambiar-tab-curso.window="tabActivo = 'curso'">
            <!-- Tab Navigation -->
            <div class="border-b border-gray-200">
                <nav class="flex space-x-2 px-4 pt-2" aria-label="Tabs">
                    <button @click="tabActivo = 'personal'" 
                        :class="tabActivo === 'personal' ? 'bg-white border-gray-200 border-b-white text-primary' : 'bg-gray-50 border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
                        class="whitespace-nowrap py-3 px-4 border-l border-t border-r rounded-t-lg font-medium text-sm transition-colors mt-1 relative z-10"
                        style="margin-bottom: -1px;">
                        <i class="i-tabler-id-badge mr-1"></i> Directorio y Kardex
                    </button>
                    <button @click="tabActivo = 'curso'" 
                        :class="tabActivo === 'curso' ? 'bg-white border-gray-200 border-b-white text-primary' : 'bg-gray-50 border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
                        class="whitespace-nowrap py-3 px-4 border-l border-t border-r rounded-t-lg font-medium text-sm transition-colors mt-1 relative z-10"
                        style="margin-bottom: -1px;" id="btnTabCurso">
                        <i class="i-tabler-users mr-1"></i> Matrículas del Curso
                        <span id="badgeCurrentCourse" class="hidden ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-primary/10 text-primary">0</span>
                    </button>
                </nav>
            </div>

            <!-- Tab 1: Directorio y Kardex -->
            <div x-show="tabActivo === 'personal'" class="p-4" x-transition>
                <div class="flex flex-wrap items-center gap-4 mb-4">
                    <div class="w-full md:w-1/3 relative">
                        <i class="i-tabler-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" id="txtBusquedaPersonal" class="form-input w-full pl-9 py-2 text-sm" placeholder="Buscar personal por DNI o Nombres..." autofocus>
                    </div>
                    <div class="w-full md:w-1/4">
                        <select id="filtroSucursalPersonal" class="form-select w-full py-2 text-sm text-gray-700">
                            <option value="">Todas las sucursales</option>
                        </select>
                    </div>
                </div>
                
                <!-- Tabla Tabulator Personal -->
                <div class="border border-default-200 rounded-lg overflow-hidden">
                    <div id="tblPersonal" style="min-height: 400px; font-size: 13px;"></div>
                </div>
            </div>

            <!-- Tab 2: Matrículas del Curso -->
            <div x-show="tabActivo === 'curso'" class="p-4" x-transition style="display: none;">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="text-lg font-bold text-gray-800" id="infoCursoSeleccionadoTitulo">Seleccione un Curso</h4>
                        <p class="text-sm text-gray-500 mt-0.5" id="infoCursoSeleccionado">Haga clic en un curso del panel izquierdo para ver sus alumnos.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary/10 text-primary" id="badgeTotalMatriculas">
                            Total: 0
                        </span>
                        <button type="button" id="btnExportarExcel" 
                            class="btn bg-success/25 text-success hover:bg-success hover:text-white hidden transition-colors">
                            <i class="i-tabler-file-spreadsheet mr-1"></i> Exportar
                        </button>
                        <button type="button" id="btnAbrirModalMatricula" 
                            class="btn bg-primary text-white hover:bg-primary/90 hidden transition-colors"
                            data-hs-overlay="#modal-registro">
                            <i class="i-tabler-user-plus mr-1"></i> Matricular
                        </button>
                    </div>
                </div>

                <!-- Estadísticas rápidas -->
                <div class="grid grid-cols-4 gap-4 mb-4" id="estadisticasMatriculas" style="display: none;">
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-center">
                        <p class="text-xs text-gray-600">Matriculados</p>
                        <p class="text-xl font-bold text-blue-600" id="countMatriculados">0</p>
                    </div>
                    <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-center">
                        <p class="text-xs text-gray-600">En Progreso</p>
                        <p class="text-xl font-bold text-yellow-600" id="countEnProgreso">0</p>
                    </div>
                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-center">
                        <p class="text-xs text-gray-600">Aprobados</p>
                        <p class="text-xl font-bold text-green-600" id="countAprobados">0</p>
                    </div>
                    <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-center">
                        <p class="text-xs text-gray-600">Reprobados</p>
                        <p class="text-xl font-bold text-red-600" id="countReprobados">0</p>
                    </div>
                </div>

                <!-- Buscador y Filtros -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4" id="filtrosMatriculaContainer" style="display: none;">
                    <div class="relative">
                        <i class="i-tabler-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" id="buscarMatricula" class="form-input w-full pl-10"
                            placeholder="Buscar alumno por nombre, DNI..." />
                    </div>
                    <div>
                        <select id="slcFiltroProgramacion" class="form-select w-full">
                            <option value="">-- Todas las programaciones --</option>
                        </select>
                    </div>
                </div>

                <!-- Tabla de matrículas -->
                <div class="border border-default-200 rounded-lg overflow-hidden" id="contenedorTblMatriculas" style="display: none;">
                    <div id="tblMatriculas" style="min-height: 400px; font-size: 13px;"></div>
                </div>

                <!-- Estado vacío -->
                <div id="estadoVacio" class="text-center py-12 mt-4">
                    <i class="i-tabler-clipboard-list text-6xl text-gray-200 mb-4 block"></i>
                    <p class="text-gray-500 font-medium">Seleccione un curso de la lista izquierda</p>
                    <p class="text-sm text-gray-400">Podrá ver los alumnos matriculados, aprobarlos o exportar actas.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de registro (Copiado y optimizado de gestion_matricula) -->
    <div id="modal-registro"
        class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-y-auto hidden pointer-events-none">
        <div
            class="translate-y-10 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 my-6 mx-auto flex flex-col bg-white shadow-sm rounded"
            style="width: 520px; max-width: 95vw;">
            <div class="flex flex-col border border-default-200 shadow-lg rounded-lg pointer-events-auto">

                <!-- Header -->
                <div
                    class="flex justify-between items-center py-3 px-5 border-b border-default-200 bg-gradient-to-r from-primary-50 to-primary-100">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Matricular Personal</h3>
                        <p class="text-xs text-gray-600 mt-0.5">Seleccione el personal</p>
                    </div>
                    <button type="button" class="text-gray-500 hover:text-gray-700 transition-colors"
                        data-hs-overlay="#modal-registro">
                        <i class="i-tabler-x text-xl"></i>
                    </button>
                </div>

                <!-- Contenido -->
                <div class="p-4">
                    <!-- Información del curso (Simplificado para modal pequeño) -->
                    <div class="mb-3 p-2 bg-blue-50 border-l-4 border-blue-500 rounded text-[13px]">
                        <span class="font-semibold inline-block mr-1">Curso:</span>
                        <span id="nombreCursoModal" class="inline-block truncate align-bottom max-w-[80%]">Cargando...</span>
                    </div>

                    <!-- Selector de Programación -->
                    <div class="mb-3">
                        <label for="slcProgramacionMatriculaModal" class="block text-xs font-medium text-gray-700 mb-1">
                            Programación
                        </label>
                        <select id="slcProgramacionMatriculaModal" class="form-select w-full rounded-md border-gray-300 shadow-sm text-[13px] py-1.5 focus:ring-1 focus:ring-indigo-500">
                            <option value="">-- Seleccione --</option>
                        </select>
                    </div>

                    <!-- Buscador -->
                    <div class="mb-3">
                        <div class="relative">
                            <i class="i-tabler-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="text" id="buscarPersonalModal" class="form-input w-full pl-9 py-1.5 text-[13px] rounded-md border-gray-300 focus:ring-1 focus:ring-indigo-500"
                                placeholder="Buscar persona..." />
                        </div>
                    </div>

                    <!-- Estadísticas Compactas -->
                    <div class="flex gap-2 mb-3 text-center">
                        <div class="flex-1 p-1.5 bg-green-50 rounded border border-green-100">
                            <p class="text-[9px] font-semibold text-gray-600 uppercase tracking-wide">Matriculados</p>
                            <p class="text-base font-bold text-green-600 leading-tight" id="countMatriculadosModal">0</p>
                        </div>
                        <div class="flex-1 p-1.5 bg-orange-50 rounded border border-orange-100">
                            <p class="text-[9px] font-semibold text-gray-600 uppercase tracking-wide">Disponibles</p>
                            <p class="text-base font-bold text-orange-600 leading-tight" id="countDisponiblesModal">0</p>
                        </div>
                        <div class="flex-1 p-1.5 bg-blue-50 rounded border border-blue-100">
                            <p class="text-[9px] font-semibold text-gray-600 uppercase tracking-wide">Selección</p>
                            <p class="text-base font-bold text-blue-600 leading-tight" id="countSeleccionadosModal">0</p>
                        </div>
                    </div>

                    <!-- Tabla con Tabulator -->
                    <div class="border border-default-200 rounded-lg overflow-hidden text-xs">
                        <div id="tblPersonalMatriculaModal" style="height: 240px; font-size: 12px;"></div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-between items-center gap-2 px-4 py-2 bg-gray-50 border-t border-default-200">
                    <button class="btn bg-gray-200 hover:bg-gray-300 text-[13px] px-4 py-1.5"
                        data-hs-overlay="#modal-registro">
                        Cancelar
                    </button>
                    <button id="btnGuardarMatricula"
                        class="btn bg-success hover:bg-success/90 text-white text-[13px] px-6 py-1.5 shadow-sm">
                        Matricular
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Historial -->
    <div id="modal-historial"
        class="hs-overlay hidden w-full h-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto pointer-events-none">
        <div
            class="hs-overlay-open:opacity-100 hs-overlay-open:duration-500 opacity-0 transition-all sm:max-w-4xl sm:w-full m-3 sm:mx-auto min-h-[calc(100%-3.5rem)] flex items-center justify-center">
            <div
                class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:shadow-slate-700/[.7] w-full pointer-events-auto">
                <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                    <h3 class="font-bold text-gray-800 dark:text-gray-200" id="modal-title">
                        Historial de Capacitaciones
                    </h3>
                    <button type="button"
                        class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-lg border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                        data-hs-overlay="#modal-historial">
                        <span class="sr-only">Cerrar</span>
                        <i class="i-tabler-x text-xl"></i>
                    </button>
                </div>
                <div class="p-4 overflow-y-auto">
                     <div class="flex flex-col items-center justify-center mb-6">
                        <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mb-3 text-2xl font-bold text-gray-600 uppercase" id="avatarPersonal">
                            <!-- Initials here -->
                        </div>
                        <h4 class="text-xl font-bold text-gray-800" id="nombrePersonal"></h4>
                        <p class="text-sm text-gray-500" id="cargoPersonal"></p>
                        <p class="text-xs text-gray-400" id="areaPersonal"></p>
                     </div>

                     <!-- Timeline -->
                     <div id="historialContainer" class="relative border-l border-gray-200 dark:border-gray-700 ml-3 space-y-6">
                         <!-- Items injected via JS -->
                     </div>
                     
                     <div id="noDataMessage" class="hidden flex flex-col items-center justify-center py-8 text-gray-500">
                            <i class='bx bx-file-blank text-4xl mb-2'></i>
                            <p>No se encontraron registros de capacitación para este colaborador.</p>
                     </div>
                </div>
                <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t dark:border-gray-700">
                    <button type="button"
                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                        data-hs-overlay="#modal-historial">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
@endsection

@vite(['resources/js/functions/capacitacion/consulta_matriculas.js'])
