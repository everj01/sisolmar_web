@extends('layouts.vertical', ['title' => 'Gestión de cursos'])
@section('css')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endsection
@section('content')
    @include('layouts.shared/page-title', ['subtitle' => 'Capacitación', 'title' => 'Gestión de cursos'])


    <div x-data="alertasVencimientoCursos()" x-init="initAlertas()" x-show="alertas.length > 0" x-cloak class="mb-6 bg-orange-50 ...">
        {{-- <div x-data="alertasVencimientoCursos()" x-init="initAlertas()" x-show="alertas.length > 0" style="display: none;" class="mb-6 bg-orange-50 border-l-4 border-orange-500 p-4 rounded shadow-sm"> --}}
        <div class="flex items-start">
            <div class="flex-shrink-0 mt-0.5">
                <i class="bx bxs-error-circle text-orange-500 text-xl"></i>
            </div>
            <div class="ml-3 w-full">
                <h3 class="text-sm font-bold text-orange-800">
                    Atención: Renovación y Clonación de Cursos
                </h3>
                <div class="mt-2 text-sm text-orange-700">
                    <p>Ocurrirá una clonación y matriculación automática pronto para los siguientes cursos periódicos.
                        Verifique el material docente si es necesario:</p>
                    <ul class="list-disc pl-5 mt-1 space-y-1">
                        <template x-for="alerta in alertas" :key="alerta.codigo_curso">
                            <li>
                                <strong x-text="alerta.nombre"></strong> (Próxima ejecución en <span
                                    x-text="alerta.dias_restantes"></span> días el <span
                                    x-text="alerta.fecha_proxima_clonacion"></span>)
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button type="button" @click="alertas = []"
                        class="inline-flex rounded-md bg-orange-50 p-1.5 text-orange-500 hover:bg-orange-100 focus:outline-none focus:ring-2 focus:ring-orange-600 focus:ring-offset-2 focus:ring-offset-orange-50">
                        <span class="sr-only">Cerrar</span>
                        <i class="bx bx-x text-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-6 w-full items-start">
        <div class="card w-full">
            <div class="card-header flex flex-wrap items-center justify-between gap-4">
                <h4 class="card-title">Lista de cursos</h4>
                <button type="button" onclick="restaurarFormCurso()"
                    class="btn rounded-full bg-primary text-white hover:bg-primary-700 transition-all flex items-center gap-2 px-5 shadow-sm">
                    <i class="bx bx-plus-circle text-lg"></i> Registrar nuevo curso
                </button>
            </div>

            <div class="card-body">
                {{-- <div 
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
            > --}}
                <div x-data="{
                    soloEliminados: false,
                    filtroArea: '',
                    filtroTipoCurso: '',
                    tipos: []
                }" x-init="$nextTick(() => listarCursos(1, '', ''))" @tipo-curso-loaded.window="tipos = $event.detail"
                    @update-filtro-area="filtroArea = $event.detail; listarCursos(soloEliminados ? 0 : 1, filtroArea, filtroTipoCurso)"
                    @update-filtro-tipo-curso="filtroTipoCurso = $event.detail; listarCursos(soloEliminados ? 0 : 1, filtroArea, filtroTipoCurso)"
                    class="flex flex-wrap items-center justify-between gap-6">
                    <div class="flex items-center">
                        {{-- <input 
                    class="form-switch" 
                    type="checkbox" 
                    role="switch" 
                    id="chkEliminados"
                    x-model="soloEliminados"
                > --}}
                        <input class="form-switch" type="checkbox" role="switch" id="chkEliminados"
                            x-model="soloEliminados"
                            @change="listarCursos(soloEliminados ? 0 : 1, filtroArea, filtroTipoCurso)">
                        <label class="ms-1.5 font-medium text-sm text-gray-700" for="chkEliminados">
                            Solo eliminados
                        </label>
                    </div>

                    <div class="flex flex-wrap items-end gap-4 ml-auto flex-1 justify-end">
                        <div class="flex flex-col min-w-[200px]" x-data="{
                            open: false,
                            search: '',
                            selected: null,
                            options: [],
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
                                this.$dispatch('update-filtro-tipo-curso', option ? option.codigo : '');
                            }
                        }" x-init="$watch('tipos', val => options = val)"
                            @tipo-curso-loaded.window="options = $event.detail">
                            <label class="text-sm font-medium text-gray-700 mb-1">
                                Plan de Capacitación
                            </label>

                            <div class="relative">
                                <button type="button" @click="open = !open" @click.away="open = false"
                                    class="w-full bg-white border border-gray-300 rounded-lg shadow-sm px-3 py-2 text-left text-sm cursor-default focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 overflow-hidden">
                                    <span class="block truncate"
                                        x-text="selected ? selected.descripcion : '-- Todos --'"></span>
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd"
                                                d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </button>

                                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="absolute z-50 w-full min-w-[280px] mt-1 bg-white border border-gray-300 rounded-md shadow-lg flex flex-col overflow-hidden"
                                    style="max-height: 320px; display: none;">

                                    <div class="p-2 border-b border-gray-100 bg-gray-50 flex-shrink-0">
                                        <input type="text" x-model="search" placeholder="Buscar..."
                                            class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 p-1.5"
                                            @click.stop>
                                    </div>

                                    <div @click="selectOption(null)"
                                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-indigo-50 text-sm border-b border-gray-100 flex-shrink-0">
                                        <span class="block font-bold text-gray-500">-- Todos --</span>
                                    </div>

                                    <div class="overflow-y-auto custom-scrollbar flex-1">
                                        <template x-for="option in filteredOptions" :key="option.codigo">
                                            <div @click="selectOption(option)"
                                                class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-indigo-50 text-sm"
                                                :class="{
                                                    'bg-indigo-50 font-semibold text-indigo-900': selected && selected
                                                        .codigo === option.codigo
                                                }">
                                                <span class="block" x-text="option.descripcion"></span>

                                                <span x-show="selected && selected.codigo === option.codigo"
                                                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-indigo-600">
                                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            </div>
                                        </template>
                                    </div>
                                    <div x-show="filteredOptions.length === 0"
                                        class="py-2 px-3 text-sm text-gray-500 text-center flex-shrink-0">
                                        No se encontraron resultados
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col min-w-[200px]" x-data="{
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
                            @areas-loaded.window="options = $event.detail">
                            <label class="text-sm font-medium text-gray-700 mb-1">
                                Sistema de Gestión
                            </label>

                            <div class="relative">
                                <!-- Botón principal del Select -->
                                <button type="button" @click="open = !open" @click.away="open = false"
                                    class="w-full bg-white border border-gray-300 rounded-lg shadow-sm px-3 py-2 text-left text-sm cursor-default focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 overflow-hidden">
                                    <span class="block truncate"
                                        x-text="selected ? selected.descripcion : '-- Todas --'"></span>
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd"
                                                d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </button>

                                <!-- Dropdown -->
                                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="absolute z-50 w-full min-w-[320px] mt-1 bg-white border border-gray-300 rounded-md shadow-lg flex flex-col overflow-hidden"
                                    style="max-height: 320px; display: none;">

                                    <!-- Search Input inside Dropdown -->
                                    <div class="p-2 border-b border-gray-100 bg-gray-50 flex-shrink-0">
                                        <input type="text" x-model="search" placeholder="Buscar..."
                                            class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 p-1.5"
                                            @click.stop>
                                    </div>

                                    <!-- Opcion "Todas" por defecto -->
                                    <div @click="selectOption(null)"
                                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-indigo-50 text-sm border-b border-gray-100 flex-shrink-0">
                                        <span class="block truncate font-bold text-gray-500">-- Todas --</span>
                                    </div>

                                    <!-- Lista de Opciones Filtradas -->
                                    <div class="overflow-y-auto custom-scrollbar flex-1">
                                        <template x-for="option in filteredOptions" :key="option.codigo">
                                            <div @click="selectOption(option)"
                                                class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-indigo-50 text-sm"
                                                :class="{
                                                    'bg-indigo-50 font-semibold text-indigo-900': selected && selected
                                                        .codigo === option.codigo
                                                }">
                                                <span class="block" x-text="option.descripcion"></span>

                                                <span x-show="selected && selected.codigo === option.codigo"
                                                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-indigo-600">
                                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            </div>
                                        </template>
                                    </div>
                                    <div x-show="filteredOptions.length === 0"
                                        class="py-2 px-3 text-sm text-gray-500 text-center flex-shrink-0">
                                        No se encontraron resultados
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- <div x-effect="listarCursos( soloEliminados ? 0 : 1, filtroArea, filtroTipoCurso )"></div> --}}
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

        <div x-data="{ panel: 'registro', tituloProgramacion: '', mostrarBotonRegistrarListado: true, showModal: false }"
            @cambiar-panel.window="panel = $event.detail.panel; tituloProgramacion = $event.detail.titulo || ''; mostrarBotonRegistrarListado = $event.detail.mostrarBtn === undefined ? true : $event.detail.mostrarBtn"
            @open-modal-gestion.window="showModal = true" @close-modal-gestion.window="showModal = false" class="w-full">

            <!-- Modal para Registro/Actualización -->
            <div x-show="showModal" x-cloak
                class="fixed inset-0 z-[1040] flex items-center justify-center p-2 sm:p-4 bg-slate-900/60 backdrop-blur-sm"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

                <div
                    class="card w-full max-w-5xl max-h-[95dvh] sm:max-h-[95vh] overflow-hidden flex flex-col shadow-2xl border border-slate-200">

                    <div class="overflow-y-auto custom-scrollbar flex-1 bg-white" x-data="formCursoGestion()" @submit.prevent
                        x-init="$nextTick(() => { $watch('tipoCurso', value => { if (value != '5') targetGroup = 'TODOS'; }); })">
                        <!-- Panel Registro de Curso -->
                        <div x-show="panel === 'registro'" x-transition>
                            <div class="card-header border-b border-gray-100 bg-white sticky top-0 z-10">
                                <div class="flex items-center justify-between">
                                    <h4 class="card-title" x-text="codigo ? 'Actualizar Curso' : 'Datos del curso'"></h4>
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium"
                                            :class="codigo ? 'bg-warning/25 text-warning-800' : 'bg-primary/25 text-primary-800'"
                                            x-text="codigo ? 'Editando' : 'Nuevo'">Nuevo</span>
                                        <button type="button" @click="showModal = false"
                                            class="text-gray-400 hover:text-gray-600 transition-colors">
                                            <i class="bx bx-x text-2xl"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <input type="hidden" name="targetGroupHidden" x-model="targetGroup">
                                <input type="hidden" name="codGestionEditar" x-model="codigo" id="codGestionEditar">
                                <input type="hidden" id="slcArea" x-model="area">

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 w-full mt-4">
                                    <!-- Columna 1: Datos del curso -->
                                    <div>
                                        <div class="w-full flex flex-col items-center mb-4">
                                            <div class="w-full flex items-center justify-between gap-4">
                                                <div class="flex-1 border-t border-gray-200"></div>
                                                <h3 class="text-lg font-semibold text-primary text-center">
                                                    <i class="bx bx-book-open mr-1"></i> Información General
                                                </h3>
                                                <div class="flex-1 border-t border-gray-200"></div>
                                            </div>
                                        </div>

                                        <div class="w-full grid gap-4 grid-cols-1 pb-6">

                                            <!-- Nombre Completo de Curso -->
                                            <div>
                                                <label for="txtNombreCurso"
                                                    class="text-gray-800 text-sm font-medium inline-block mb-1">
                                                    Nombre del curso
                                                </label>
                                                <input type="text" id="txtNombreCurso"
                                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                                                    x-model="nombre" />
                                            </div>

                                            <!-- Plan de Capacitación -->
                                            <div>
                                                <label
                                                    class="text-gray-800 text-sm font-medium inline-block mb-2 text-primary">
                                                    Plan de Capacitación <span class="text-danger">*</span>
                                                </label>
                                                <div class="flex flex-wrap gap-3" x-data="{ tipos: window.opcionesTipoCurso || [] }"
                                                    @tipo-curso-loaded.window="tipos = $event.detail">
                                                    <template x-for="tipo in tipos" :key="tipo.codigo">
                                                        <label
                                                            class="flex items-center space-x-2 cursor-pointer bg-white border border-gray-200 rounded-lg px-3 py-2 hover:bg-slate-50 transition-all shadow-sm"
                                                            :class="{
                                                                'border-primary ring-1 ring-primary/30 bg-primary/5': tipoCurso ==
                                                                    tipo
                                                                    .codigo
                                                            }">
                                                            <input type="radio" :value="tipo.codigo"
                                                                x-model="tipoCurso"
                                                                @change="checkEsPACByText(tipo.descripcion)"
                                                                name="plan_capacitacion"
                                                                class="w-4 h-4 text-primary focus:ring-primary border-gray-300">
                                                            <span class="text-sm font-medium text-gray-700"
                                                                x-text="tipo.descripcion"></span>
                                                        </label>
                                                    </template>
                                                </div>

                                                <!-- NUEVO: Selector PCU (Clientes) -->
                                                <div x-show="tipoCurso == '6'" x-transition
                                                    class="mt-4 bg-blue-50/50 border border-blue-100 rounded-lg p-5">
                                                    <label
                                                        class="text-blue-800 text-sm font-bold tracking-wide inline-block mb-2">
                                                        <i class="bx bx-buildings mr-1"></i> Seleccionar Clientes <span
                                                            class="text-red-500">*</span>
                                                    </label>
                                                    <p class="text-xs text-blue-500 mb-3 font-medium">Se matriculará al
                                                        personal asignado a
                                                        estos clientes.</p>
                                                    <div class="mb-3">
                                                        <input type="text" x-model="busquedaCliente"
                                                            placeholder="Buscar cliente..."
                                                            class="w-full border border-blue-200 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-blue-400 focus:border-blue-400 outline-none shadow-sm"
                                                            @keydown.enter.prevent>
                                                    </div>
                                                    <div class="border border-blue-100 rounded-md p-3 overflow-y-auto bg-white custom-scrollbar shadow-inner"
                                                        style="max-height: 160px;">
                                                        <div class="grid grid-cols-1 gap-2">
                                                            <template x-for="clie in clientesFiltrados"
                                                                :key="clie.codigo">
                                                                <label
                                                                    class="flex items-start space-x-2 cursor-pointer hover:bg-slate-50 p-2 rounded-md border border-transparent hover:border-slate-200 transition-all">
                                                                    <input type="checkbox" :value="clie.codigo"
                                                                        x-model="clientesAsignados"
                                                                        class="mt-0.5 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                    <span class="text-sm font-medium text-gray-700">
                                                                        <span x-text="clie.codigo"
                                                                            class="text-xs text-gray-500 bg-gray-200 px-1.5 py-0.5 rounded mr-1"></span>
                                                                        <span x-text="clie.descripcion"></span>
                                                                    </span>
                                                                </label>
                                                            </template>
                                                        </div>
                                                        <div x-show="clientesFiltrados.length === 0"
                                                            class="text-gray-400 text-sm text-center py-4">
                                                            No se encontraron clientes asociados.
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- NUEVO: Selector PCI (Áreas Operativas) -->
                                                <div x-show="tipoCurso == '7'" x-transition
                                                    class="mt-4 bg-teal-50/50 border border-teal-100 rounded-lg p-5">
                                                    <label
                                                        class="text-teal-800 text-sm font-bold tracking-wide inline-block mb-2">
                                                        <i class="bx bx-category mr-1"></i> Seleccionar Áreas Operativas
                                                        <span class="text-red-500">*</span>
                                                    </label>
                                                    <p class="text-xs text-teal-500 mb-3 font-medium">Se matriculará al
                                                        personal perteneciente
                                                        a estas áreas.</p>
                                                    <div class="mb-3">
                                                        <input type="text" x-model="busquedaAreaPCI"
                                                            placeholder="Buscar área..."
                                                            class="w-full border border-teal-200 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-teal-400 focus:border-teal-400 outline-none shadow-sm"
                                                            @keydown.enter.prevent>
                                                    </div>
                                                    <div class="border border-teal-100 rounded-md p-3 overflow-y-auto bg-white custom-scrollbar shadow-inner"
                                                        style="max-height: 160px;">
                                                        <div class="grid grid-cols-1 gap-2">
                                                            <template x-for="ar in areasPCIFiltradas"
                                                                :key="ar.codigo">
                                                                <label
                                                                    class="flex items-start space-x-2 cursor-pointer hover:bg-slate-50 p-2 rounded-md border border-transparent hover:border-slate-200 transition-all">
                                                                    <input type="checkbox" :value="ar.codigo"
                                                                        x-model="areasAsignadas"
                                                                        class="mt-0.5 w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                                                    <span class="text-sm font-medium text-gray-700">
                                                                        <span x-text="ar.codigo"
                                                                            class="text-xs text-gray-500 bg-gray-200 px-1.5 py-0.5 rounded mr-1"></span>
                                                                        <span x-text="ar.descripcion"></span>
                                                                    </span>
                                                                </label>
                                                            </template>
                                                        </div>
                                                        <div x-show="areasPCIFiltradas.length === 0"
                                                            class="text-gray-400 text-sm text-center py-4">
                                                            No se encontraron áreas asociadas.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>


                                            <!-- Área de Conocimiento -->
                                            <div>
                                                <label
                                                    class="text-gray-800 text-sm font-medium inline-block mb-1 text-primary">
                                                    Sistema de Gestión <span class="text-danger">*</span>
                                                </label>

                                                <div x-data="{
                                                    open: false,
                                                    searchTerm: '',
                                                    options: window.opcionesArea || [],
                                                    get filteredOptions() {
                                                        if (this.searchTerm === '') return this.options;
                                                        return this.options.filter(option =>
                                                            (option.descripcion || '').toLowerCase().includes(this.searchTerm.toLowerCase())
                                                        );
                                                    },
                                                    selectOption(option) {
                                                        areaConocimiento = option ? option.codigo : '';
                                                        area = areaConocimiento; // Sincronizar campo alternativo
                                                        cargarAreasResponsables(areaConocimiento);
                                                        this.searchTerm = '';
                                                        this.open = false;
                                                    },
                                                    init() {
                                                        if (window.opcionesArea && window.opcionesArea.length > 0) {
                                                            this.options = window.opcionesArea;
                                                        }
                                                    },
                                                    get currentDescription() {
                                                        // Buscamos en las opciones basándonos en areaConocimiento (del padre)
                                                        const found = this.options.find(opt => opt.codigo == areaConocimiento);
                                                        return found ? found.descripcion : '';
                                                    }
                                                }"
                                                    @areas-loaded.window="options = $event.detail"
                                                    class="relative w-full">

                                                    <!-- Botón que simula el select -->
                                                    <button @click="open = !open" type="button"
                                                        class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-left text-sm flex justify-between items-center focus:outline-none focus:ring-1 focus:ring-primary h-[38px] transition-all shadow-sm">
                                                        <span
                                                            :class="areaConocimiento ? 'text-gray-800 font-semibold' :
                                                                'text-gray-400'"
                                                            x-text="currentDescription || 'Seleccione Sistema'"></span>
                                                        <i class="bx bx-chevron-down text-gray-400 text-lg"
                                                            :class="open ? 'rotate-180' : ''"></i>
                                                    </button>

                                                    <!-- Dropdown con búsqueda -->
                                                    <div x-show="open" x-cloak @click.away="open = false"
                                                        x-transition:enter="transition ease-out duration-100"
                                                        x-transition:enter-start="opacity-0 scale-95"
                                                        x-transition:enter-end="opacity-100 scale-100"
                                                        class="absolute mt-1 w-full border border-gray-300 rounded-lg shadow-2xl overflow-hidden"
                                                        style="display: none; background-color: white !important; opacity: 1 !important; z-index: 99999 !important;">

                                                        <div class="p-2 border-b border-gray-100"
                                                            style="background-color: white !important;">
                                                            <input type="text" x-model="searchTerm"
                                                                class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-primary"
                                                                placeholder="Buscar sistema...">
                                                        </div>

                                                        <ul class="max-h-60 overflow-y-auto py-1"
                                                            style="background-color: white !important;">
                                                            <template x-for="option in filteredOptions"
                                                                :key="option.codigo">
                                                                <li @click="selectOption(option)"
                                                                    class="px-3 py-2 text-sm hover:bg-primary/10 hover:text-primary cursor-pointer transition-colors"
                                                                    :class="{
                                                                        'bg-primary/5 text-primary font-medium': areaConocimiento ==
                                                                            option
                                                                            .codigo
                                                                    }">
                                                                    <span x-text="option.descripcion"></span>
                                                                </li>
                                                            </template>
                                                            <template x-if="filteredOptions.length === 0">
                                                                <li
                                                                    class="px-3 py-2 text-sm text-gray-500 italic text-center">
                                                                    No se encontraron sistemas
                                                                </li>
                                                            </template>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Área Responsable -->
                                            <div>
                                                <label
                                                    class="text-gray-800 text-sm font-medium inline-block mb-1 text-primary">
                                                    Área responsable <span class="text-danger">*</span>
                                                </label>

                                                <div x-data="{
                                                    open: false,
                                                    searchTerm: '',
                                                    get options() { return areasResponsables; },
                                                    selectOption(option) {
                                                        areaResponsable = option ? option.codArea : '';
                                                        codMoodleArea = option ? option.codModdle : '';
                                                        this.searchTerm = '';
                                                        this.open = false;
                                                    },
                                                    get filteredOptions() {
                                                        if (this.searchTerm === '') return this.options;
                                                        return this.options.filter(opt =>
                                                            (opt.Area || opt.nombre || opt.descripcion || '').toLowerCase().includes(this
                                                                .searchTerm.toLowerCase())
                                                        );
                                                    },
                                                    get currentDescription() {
                                                        const found = this.options.find(opt => opt.codArea == areaResponsable);
                                                        return found ? (found.Area || found.nombre || found.descripcion) : '';
                                                    }
                                                }" class="relative w-full">

                                                    <!-- Botón que simula el select -->
                                                    <button @click="open = !open" type="button"
                                                        class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-left text-sm flex justify-between items-center focus:outline-none focus:ring-1 focus:ring-primary h-[38px] transition-all shadow-sm">
                                                        <span
                                                            :class="areaResponsable ? 'text-gray-800 font-semibold' :
                                                                'text-gray-400'"
                                                            x-text="currentDescription || 'Seleccione Área'"></span>
                                                        <i class="bx bx-chevron-down text-gray-400 text-lg"
                                                            :class="open ? 'rotate-180' : ''"></i>
                                                    </button>

                                                    <!-- Dropdown con búsqueda -->
                                                    <div x-show="open" x-cloak @click.away="open = false"
                                                        x-transition:enter="transition ease-out duration-100"
                                                        x-transition:enter-start="opacity-0 scale-95"
                                                        x-transition:enter-end="opacity-100 scale-100"
                                                        class="absolute mt-1 w-full border border-gray-300 rounded-lg shadow-2xl overflow-hidden"
                                                        style="display: none; background-color: white !important; opacity: 1 !important; z-index: 99999 !important;">

                                                        <div class="p-2 border-b border-gray-100"
                                                            style="background-color: white !important;">
                                                            <input type="text" x-model="searchTerm"
                                                                class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-primary"
                                                                placeholder="Buscar área...">
                                                        </div>

                                                        <ul class="max-h-60 overflow-y-auto py-1"
                                                            style="background-color: white !important;">
                                                            <template x-for="option in filteredOptions"
                                                                :key="option.codArea">
                                                                <li @click="selectOption(option)"
                                                                    class="px-3 py-2 text-sm hover:bg-primary/10 hover:text-primary cursor-pointer transition-colors"
                                                                    :class="{
                                                                        'bg-primary/5 text-primary font-medium': areaResponsable ==
                                                                            option.codArea
                                                                    }">
                                                                    <span
                                                                        x-text="option.Area || option.nombre || option.descripcion"></span>
                                                                </li>
                                                            </template>
                                                            <template x-if="filteredOptions.length === 0">
                                                                <li
                                                                    class="px-3 py-2 text-sm text-gray-500 italic text-center">
                                                                    No se encontraron áreas
                                                                </li>
                                                            </template>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>


                                            <!-- Frecuencia -->
                                            <div x-show="!esDemanda" x-transition>
                                                <label for="slcFrecuencia"
                                                    class="text-gray-800 text-sm font-medium inline-block mb-1">Frecuencia</label>
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

                                            <!-- Dirigido a -->
                                            <div x-show="!esDemanda" x-transition>
                                                <label for="slcDirigido"
                                                    class="text-gray-800 text-sm font-medium inline-block mb-1">Dirigido a</label>
                                                <select id="slcDirigido" x-model="dirigido"
                                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white">
                                                    <option value="">-- Seleccione Dirigido --</option>
                                                    @foreach($dirigidos as $item)
                                                        <option value="{{ $item->codigo }}">{{ $item->texto }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <!-- SELECTOR SUCURSALES (SOLO PAC) -->
                                            <div x-show="esPAC" x-transition
                                                class="mt-2 bg-indigo-50/50 border border-indigo-100 rounded-lg p-5">
                                                <label
                                                    class="text-indigo-800 text-sm font-bold tracking-wide inline-block mb-2">
                                                    <i class="bx bx-buildings mr-1"></i> Sucursales Asignadas <span
                                                        class="text-red-500">*</span>
                                                </label>
                                                <div class="mb-3">
                                                    <input type="text" x-model="busquedaSucursal"
                                                        placeholder="Buscar sucursal por nombre..."
                                                        class="w-full border border-indigo-200 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400 outline-none shadow-sm"
                                                        @keydown.enter.prevent>
                                                </div>
                                                <div class="border border-indigo-100 rounded-md p-3 overflow-y-auto bg-white custom-scrollbar shadow-inner"
                                                    style="max-height: 160px;">
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                        <template x-for="suc in sucursalesFiltradas"
                                                            :key="suc">
                                                            <label
                                                                class="flex items-center space-x-2 cursor-pointer hover:bg-slate-50 p-2 rounded-md border border-transparent hover:border-slate-200 transition-all">
                                                                <input type="checkbox" :value="suc"
                                                                    x-model="sucursalesAsignadas"
                                                                    class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                                <span class="text-xs font-medium text-gray-700"
                                                                    x-text="suc"></span>
                                                            </label>
                                                        </template>
                                                    </div>
                                                    <div x-show="sucursalesFiltradas.length === 0"
                                                        class="text-gray-400 text-sm text-center py-4">
                                                        No se encontraron sucursales asociadas.
                                                    </div>
                                                </div>
                                                <p class="text-xs text-indigo-500 mt-2 font-medium">Seleccione
                                                    explícitamente las sucursales
                                                    donde este curso estará activo.</p>
                                            </div>

                                            <!-- NUEVO: Responsable (Estilo Escritorio) -->
                                            <div
                                                class="mt-4 bg-gray-50/50 border border-gray-100 rounded-lg p-3 shadow-sm">
                                                <label
                                                    class="text-indigo-800 text-[11px] font-bold uppercase tracking-wider mb-2 block">
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

                                                            <div
                                                                class="p-3 border-b bg-indigo-50/50 flex items-center gap-2">
                                                                <i class="bx bx-search text-indigo-500 text-lg"></i>
                                                                <input type="text" x-model="query"
                                                                    @input.debounce.300ms="search()"
                                                                    class="w-full border-none bg-transparent p-1 text-sm focus:ring-0 outline-none text-gray-700 font-medium"
                                                                    placeholder="Buscar por nombre o DNI en Administrativos 5...">
                                                            </div>

                                                            <!-- Cabecera de "Tabla" - Usando Divs con Flex para evitar bug 'after' de Alpine -->
                                                            <div
                                                                class="bg-indigo-50 px-4 py-2 border-b flex gap-2 text-[10px] font-bold text-gray-500 uppercase flex-shrink-0">
                                                                <div class="w-16 shrink-0 text-center">Código</div>
                                                                <div class="flex-1 px-4 border-l border-indigo-100">Nombre
                                                                    Completo</div>
                                                                <div
                                                                    class="w-24 shrink-0 text-center border-l border-indigo-100">
                                                                    DNI</div>
                                                                <div
                                                                    class="w-28 shrink-0 text-center border-l border-indigo-100 italic">
                                                                    Sucursal</div>
                                                            </div>

                                                            <div class="overflow-y-auto custom-scrollbar bg-white"
                                                                style="max-height: 280px !important;">
                                                                <template x-for="(p, index) in results"
                                                                    :key="p.codigo + '-' + index">
                                                                    <div @click="select(p)"
                                                                        class="px-4 py-2 text-[11px] hover:bg-indigo-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors group flex items-center gap-2 min-h-[36px]">
                                                                        <div class="w-16 shrink-0 font-mono text-gray-400 group-hover:text-indigo-600 font-bold text-center"
                                                                            x-text="p.codigo"></div>
                                                                        <div class="flex-1 px-4 font-bold text-gray-800 group-hover:text-indigo-700 truncate"
                                                                            x-text="p.nombre_completo"></div>
                                                                        <div class="w-24 shrink-0 text-center text-gray-600 font-medium"
                                                                            x-text="p.dni"></div>
                                                                        <div class="w-28 shrink-0 text-center text-gray-500 italic truncate text-[10px]"
                                                                            x-text="p.sucursal || 'N/A'"></div>
                                                                    </div>
                                                                </template>

                                                                <!-- Estados -->
                                                                <div x-show="loading"
                                                                    class="p-8 text-center text-xs text-indigo-500 font-medium">
                                                                    <i
                                                                        class="bx bx-loader-alt bx-spin mr-2 text-lg align-middle"></i>
                                                                    Buscando
                                                                    responsables...
                                                                </div>

                                                                <div x-show="error"
                                                                    class="p-4 text-center text-[11px] text-red-500 bg-red-50"
                                                                    x-text="error"></div>

                                                                <div x-show="!loading && !error && results.length === 0"
                                                                    class="p-10 text-center text-xs text-gray-400 flex flex-col items-center gap-2">
                                                                    <i class="bx bx-search-alt-2 text-3xl opacity-20"></i>
                                                                    <span>No se encontraron coincidencias</span>
                                                                </div>
                                                            </div>

                                                            <!-- Footer Informativo -->
                                                            <div
                                                                class="bg-indigo-600 p-2 text-[10px] text-center text-white font-bold flex justify-between px-4 items-center">
                                                                <span class="flex items-center gap-1"><i
                                                                        class="bx bx-check-shield text-sm"></i>
                                                                    ADMINISTRATIVOS ACTIVOS</span>
                                                                <span class="bg-white/20 px-2 py-0.5 rounded text-[9px]"
                                                                    x-text="results.length + ' RESULTADOS'"></span>
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
                                                <label
                                                    class="text-gray-800 text-sm font-medium inline-block mb-1">Observaciones</label>
                                                <textarea rows="2" x-model="observaciones"
                                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm resize-none focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-shadow"
                                                    placeholder="Ingrese observaciones o detalles adicionales..."></textarea>
                                            </div>

                                            <!-- NUEVO: Recursos Visuales del Curso (2026) -->
                                            <div class="mt-2 bg-slate-50 border border-slate-200 rounded-xl p-5 shadow-sm">
                                                <label
                                                    class="text-primary text-[11px] font-black uppercase tracking-widest mb-3 flex items-center">
                                                    <i class="bx bx-images mr-1.5 text-base"></i> Recursos Visuales
                                                    (Opcionales)
                                                </label>

                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    <!-- Slot 1: Portada del Curso -->
                                                    <div class="flex flex-col gap-2">
                                                        <span
                                                            class="text-[10px] font-bold text-slate-500 uppercase tracking-tight ml-1">Portada
                                                            del Curso</span>
                                                        <div class="relative w-full aspect-[3/2] bg-white border-2 border-dashed border-slate-200 rounded-lg overflow-hidden flex items-center justify-center group transition-all"
                                                            :class="imagePreviewPortada ? 'border-solid border-primary/30' :
                                                                'hover:border-slate-300'">

                                                            <template x-if="imagePreviewPortada">
                                                                <div class="w-full h-full relative">
                                                                    <img :src="imagePreviewPortada"
                                                                        class="w-full h-full object-cover shadow-inner">
                                                                    <div
                                                                        class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                                        <button type="button"
                                                                            @click="imagePreviewPortada = null; imageFilePortada = null; $refs.inputImagePortada.value = ''"
                                                                            class="btn btn-sm bg-red-500 text-white rounded-full p-2 hover:bg-red-600 shadow-lg transform hover:scale-110 transition-all">
                                                                            <i class="bx bx-trash text-lg"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </template>

                                                            <template x-if="!imagePreviewPortada">
                                                                <div class="flex flex-col items-center py-4 cursor-pointer group/upload"
                                                                    @click="$refs.inputImagePortada.click()">
                                                                    <div
                                                                        class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mb-2 border border-slate-100 group-hover/upload:bg-primary/10 group-hover/upload:text-primary group-hover/upload:border-primary/20 transition-all duration-300">
                                                                        <i
                                                                            class="bx bx-image text-2xl text-slate-400 group-hover/upload:text-primary"></i>
                                                                    </div>
                                                                    <span
                                                                        class="text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">Subir
                                                                        Portada</span>
                                                                    <div
                                                                        class="flex items-center gap-1.5 px-2.5 py-1 bg-primary/5 text-primary border border-primary/10 rounded-md">
                                                                        <i class="bx bx-expand-alt text-[10px]"></i>
                                                                        <span
                                                                            class="text-[9px] font-black uppercase tracking-wider">1200x300
                                                                            px • JPG</span>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                        <input type="file" id="inputImagePortada"
                                                            x-ref="inputImagePortada" class="hidden"
                                                            accept=".jpg,.jpeg,.png"
                                                            @change="handleImageUpload($event, 'portada')">
                                                    </div>

                                                    <!-- Slot 2: Afiche Informativo -->
                                                    <div class="flex flex-col gap-2">
                                                        <span
                                                            class="text-[10px] font-bold text-slate-500 uppercase tracking-tight ml-1">Afiche
                                                            Informativo</span>
                                                        <div class="relative w-full aspect-[3/2] bg-white border-2 border-dashed border-slate-200 rounded-lg overflow-hidden flex items-center justify-center group transition-all"
                                                            :class="imagePreviewAfiche ? 'border-solid border-indigo-200' :
                                                                'hover:border-slate-300'">

                                                            <template x-if="imagePreviewAfiche">
                                                                <div class="w-full h-full relative">
                                                                    <img :src="imagePreviewAfiche"
                                                                        class="w-full h-full object-cover shadow-inner">
                                                                    <div
                                                                        class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                                        <button type="button"
                                                                            @click="imagePreviewAfiche = null; imageFileAfiche = null; $refs.inputImageAfiche.value = ''"
                                                                            class="btn btn-sm bg-red-500 text-white rounded-full p-2 hover:bg-red-600 shadow-lg transform hover:scale-110 transition-all">
                                                                            <i class="bx bx-trash text-lg"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </template>

                                                            <template x-if="!imagePreviewAfiche">
                                                                <div class="flex flex-col items-center text-slate-400 py-4 cursor-pointer"
                                                                    @click="$refs.inputImageAfiche.click()">
                                                                    <div
                                                                        class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center mb-1 group-hover:bg-indigo-50 group-hover:text-indigo-500 transition-colors">
                                                                        <i class="bx bx-info-circle text-xl"></i>
                                                                    </div>
                                                                    <span
                                                                        class="text-[9px] font-bold uppercase tracking-tight">Subir
                                                                        Afiche Informativo</span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                        <input type="file" id="inputImageAfiche"
                                                            x-ref="inputImageAfiche" class="hidden"
                                                            accept=".jpg,.jpeg,.png"
                                                            @change="handleImageUpload($event, 'afiche')">
                                                    </div>
                                                </div>

                                                <!-- Información simple de formatos -->
                                                <div
                                                    class="mt-4 flex items-center justify-center bg-slate-100/50 border border-slate-200 rounded-lg px-3 py-2">
                                                    <span
                                                        class="text-[10px] text-slate-500 font-bold uppercase tracking-tight">
                                                        <i class="bx bx-info-circle align-middle mr-1"></i> Formatos
                                                        Permitidos: .jpg, .jpeg, .png
                                                    </span>
                                                </div>
                                            </div>

                                        </div>

                                    </div>

                                    <!-- Columna 2: Datos del Examen & Metadatos -->
                                    <div class="flex flex-col h-full mt-8 lg:mt-0">
                                        <!-- NUEVO: Aplica Evaluación -->
                                        <div class="flex items-center justify-between mb-2 bg-indigo-50/80 border border-indigo-100 px-5 py-3 rounded-xl shadow-sm w-full transition-all hover:bg-indigo-50 cursor-pointer select-none"
                                            @click="aplicaEvaluacion = !aplicaEvaluacion"
                                            title="Activar para requerir una evaluación obligatoria en este curso">
                                            <div class="flex flex-col pointer-events-none">
                                                <span class="text-sm font-bold text-indigo-900">Evaluación de Curso</span>
                                                <span class="text-[11px] text-indigo-600/80 font-medium mt-0.5">Requerir
                                                    examen obligatorio para aprobar</span>
                                            </div>
                                            <div class="relative" @click.stop>
                                                <input class="form-switch cursor-pointer scale-110" type="checkbox"
                                                    role="switch" id="chkAplicaEvaluacion" x-model="aplicaEvaluacion">
                                            </div>
                                        </div>

                                        <!-- Placeholder cuando no aplica evaluación -->
                                        <div x-show="!aplicaEvaluacion" x-transition
                                            class="flex flex-col items-center justify-center h-full min-h-[300px] border-2 border-dashed border-gray-200 rounded-xl bg-gray-50/50 text-gray-400 p-6">
                                            <i class="bx bx-file-blank mb-3 text-gray-300" style="font-size: 3.5rem;"></i>
                                            <h4 class="text-base font-bold text-gray-500 mb-1">Sin evaluación requerida
                                            </h4>
                                            <p class="text-sm text-gray-400 text-center">
                                                Activa el interruptor de <strong
                                                    class="text-indigo-400 font-semibold">Evaluación de Curso</strong> si
                                                deseas configurar un examen obligatorio para aprobar este curso.
                                            </p>
                                        </div>

                                        <div x-show="aplicaEvaluacion" class="w-full flex flex-col items-center mb-4">
                                            <div class="w-full flex items-center justify-between gap-4">
                                                <div class="flex-1 border-t border-gray-200"></div>
                                                <h3 class="text-lg font-semibold text-primary text-center">
                                                    <i class="bx bx-task mr-1"></i> Datos del Examen
                                                </h3>
                                                <div class="flex-1 border-t border-gray-200"></div>
                                            </div>
                                        </div>

                                        <!-- Contenedor condicional para Examen -->
                                        <div x-show="aplicaEvaluacion" x-transition.duration.300ms
                                            class="w-full border border-gray-100 bg-gray-50/30 p-5 rounded-xl shadow-sm mb-4">
                                            <div class="w-full grid gap-4 grid-cols-1 sm:grid-cols-2">
                                                <div>
                                                    <label for="txtLimite"
                                                        class="text-gray-800 text-sm font-medium inline-block mb-1">
                                                        Límite de tiempo (minutos)
                                                    </label>
                                                    <input type="number" id="txtLimite"
                                                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400 focus:outline-none"
                                                        x-model="limiteTiempo" placeholder="" />
                                                </div>
                                                <div>
                                                    <label for="txtNota"
                                                        class="text-gray-800 text-sm font-medium inline-block mb-1">
                                                        Nota mínima
                                                    </label>
                                                    <input type="number" id="txtNota"
                                                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400 focus:outline-none"
                                                        x-model="nota" placeholder="" />
                                                </div>
                                                <div>
                                                    <label for="txtIntentos"
                                                        class="text-gray-800 text-sm font-medium inline-block mb-1">
                                                        Número de intentos
                                                    </label>
                                                    <input type="number" id="txtIntentos"
                                                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400 focus:outline-none"
                                                        x-model="intentos" placeholder="" />
                                                </div>
                                                <div>
                                                    <label for="txtCantidadPreguntas"
                                                        class="text-gray-800 text-sm font-medium inline-block mb-1">
                                                        Cantidad De Preguntas
                                                    </label>
                                                    <input type="number" id="txtCantidadPreguntas"
                                                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400 focus:outline-none"
                                                        x-model="cantidadPreguntas" placeholder="" />
                                                </div>
                                                <div>
                                                    <label for="txtPreguntasBalotario"
                                                        class="text-gray-800 text-sm font-medium inline-block mb-1">
                                                        Preguntas en el balotario
                                                    </label>
                                                    <input type="number" id="txtPreguntasBalotario"
                                                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400 focus:outline-none"
                                                        :class="preguntasExamen.length > 0 ?
                                                            'bg-gray-100 cursor-not-allowed' : 'bg-white'"
                                                        :readonly="preguntasExamen.length > 0"
                                                        x-model="preguntasBalotario" placeholder="" />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex flex-col py-5" x-show="aplicaEvaluacion" x-transition.opacity>
                                            <div
                                                class="border border-dashed border-blue-300 bg-blue-50/40 rounded-xl p-5 shadow-sm">

                                                <label
                                                    class="text-primary text-[11px] font-black uppercase tracking-widest mb-3 flex items-center">
                                                    <i class="bx bxs-file-doc mr-1.5 text-base"></i> Banco de preguntas
                                                </label>

                                                <div class="flex flex-col gap-3">

                                                    <!-- Nombre del archivo -->
                                                    <div
                                                        class="w-full bg-white border border-blue-200 rounded-lg px-4 py-2.5 flex items-center justify-between shadow-sm focus-within:ring-2 focus-within:ring-blue-100 transition-all">

                                                        <span class="text-xs text-blue-700 font-medium"
                                                            :class="!archivoWordNombre ? 'italic text-blue-400' : ''"
                                                            x-text="archivoWordNombre || 'Selecciona un archivo Word (.docx) con las preguntas & respuestas del examen'">
                                                        </span>

                                                        <button x-show="archivoWordNombre" type="button"
                                                            @click="archivoWordNombre = ''; archivoWord = null; preguntasExamen = []"
                                                            class="text-red-400 hover:text-red-600 transition-colors ml-2"
                                                            title="Quitar archivo">

                                                            <i class="bx bx-trash text-lg"></i>
                                                        </button>
                                                    </div>

                                                    <!-- Botones -->
                                                    <div class="flex flex-wrap gap-2 w-full">
                                                        <!-- Botón de Selección -->
                                                        <button type="button" @click="$refs.inputWord.click()"
                                                            class="flex-1 sm:flex-none btn btn-sm bg-white text-blue-600 border border-blue-200 hover:bg-blue-50 transition-all rounded-lg px-5 shadow-sm font-bold h-[42px] flex items-center justify-center">

                                                            <i class="bx bx-file-find mr-2 text-lg"></i>
                                                            <span
                                                                x-text="archivoWordNombre ? 'Cambiar archivo' : 'Seleccionar archivo'"></span>
                                                        </button>

                                                        <!-- Botón de Procesamiento Word -->
                                                        <button x-show="archivoWordNombre && !preguntasExamen.length"
                                                            type="button" @click="analizarExamenWord()"
                                                            :disabled="cargandoWord"
                                                            class="flex-1 sm:flex-none btn btn-sm bg-blue-600 text-white hover:bg-blue-700 transition-all rounded-lg px-5 shadow-sm font-bold h-[42px] flex items-center justify-center disabled:opacity-70">

                                                            <template x-if="!cargandoWord">
                                                                <div class="flex items-center">
                                                                    <i class="bx bx-file mr-2 text-lg"></i>
                                                                    Analizar Word
                                                                </div>
                                                            </template>
                                                            <template x-if="cargandoWord">
                                                                <div class="flex items-center">
                                                                    <i class="bx bx-loader-alt bx-spin mr-2 text-lg"></i>
                                                                    Analizando...
                                                                </div>
                                                            </template>
                                                        </button>

                                                        <!-- Botón Ver Vista Previa (Si ya fue procesado) -->
                                                        <button x-show="preguntasExamen.length > 0" type="button"
                                                            @click="verVistaPrevia()"
                                                            class="flex-1 sm:flex-none btn btn-sm bg-emerald-500 text-white hover:bg-emerald-600 transition-all rounded-lg px-5 shadow-sm font-bold h-[42px] flex items-center justify-center">
                                                            <i class="bx bx-show mr-2 text-lg"></i>
                                                            Ver Preguntas
                                                        </button>

                                                        <input type="file" id="inputWordExamen" x-ref="inputWord"
                                                            class="hidden" accept=".docx"
                                                            @change="archivoWord = $event.target.files[0]; archivoWordNombre = $event.target.files[0].name; preguntasExamen = []; $event.target.value = '';"
                                                            title="archivoWord">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- NUEVO: Auditoría / Metadatos -->
                                        <div x-show="codigo" x-cloak
                                            style="width:100%; margin-top:0.5rem; margin-bottom:1rem; padding:1.25rem; background:rgba(249,250,251,0.7); border:1px solid #e5e7eb; border-radius:0.5rem; opacity:0.7; pointer-events:none; user-select:none;">
                                            <h5
                                                style="font-size:0.75rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid #e5e7eb;">
                                                Información de Sistema
                                            </h5>

                                            <div style="display:flex; flex-wrap:wrap; gap:2.5rem;">
                                                <div style="display:flex; flex-direction:column; min-width:120px;">
                                                    <span
                                                        style="font-size:0.68rem; color:#6b7280; font-weight:600; text-transform:uppercase; letter-spacing:0.025em; margin-bottom:0.5rem;">Código
                                                        Interno</span>
                                                    <div x-text="sys_codigo"
                                                        style="background:#f3f4f6; border:1px solid #e5e7eb; padding:0.375rem 0.75rem; border-radius:0.25rem; color:#9ca3af; font-weight:700; font-size:0.8rem; display:inline-block; text-align:center; box-shadow:0 1px 2px 0 rgba(0,0,0,0.05); width:max-content; min-width:70px;">
                                                        -
                                                    </div>
                                                </div>

                                                <div style="display:flex; flex-direction:column; min-width:180px;">
                                                    <span
                                                        style="font-size:0.68rem; color:#6b7280; font-weight:600; text-transform:uppercase; letter-spacing:0.025em; margin-bottom:0.5rem;">Registrado
                                                        por</span>
                                                    <div style="display:flex; flex-direction:column; gap:0.25rem;">
                                                        <div
                                                            style="display:flex; align-items:center; gap:0.35rem; color:#9ca3af; font-size:0.75rem; font-weight:600;">
                                                            <i class="bx bx-user"
                                                                style="color:#d1d5db; font-size:0.9rem;"></i>
                                                            <span x-text="sys_creado_por || '-'">(-) -</span>
                                                        </div>
                                                        <span x-text="sys_fecha_creacion || '-'"
                                                            style="color:#9ca3af; font-size:0.75rem; padding-left:1.35rem;">-</span>
                                                    </div>
                                                </div>

                                                <div style="display:flex; flex-direction:column; min-width:180px;">
                                                    <span
                                                        style="font-size:0.68rem; color:#6b7280; font-weight:600; text-transform:uppercase; letter-spacing:0.025em; margin-bottom:0.5rem;">Última
                                                        modificación</span>
                                                    <div style="display:flex; flex-direction:column; gap:0.25rem;">
                                                        <div
                                                            style="display:flex; align-items:center; gap:0.35rem; color:#9ca3af; font-size:0.75rem; font-weight:600;">
                                                            <i class="bx bx-user-pin"
                                                                style="color:#d1d5db; font-size:0.9rem;"></i>
                                                            <span x-text="sys_modificado_por || '-'">(-) -</span>
                                                        </div>
                                                        <span x-text="sys_fecha_modificacion || '-'"
                                                            style="color:#9ca3af; font-size:0.75rem; padding-left:1.35rem;">-</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div> <!-- End Columna 2 -->
                                </div> <!-- End Grid 2-col -->

                                <div class="flex flex-col items-center w-full py-4 border-t border-gray-100 mt-6 gap-3">
                                    <div class="flex flex-wrap items-center justify-center gap-3">
                                        <button type="submit" id="btnGestion" @click="registrar"
                                            :disabled="!formularioCompleto"
                                            class="btn rounded-full bg-success/25 text-success hover:bg-success hover:text-white disabled:opacity-50 disabled:cursor-not-allowed">
                                            Registrar Curso&nbsp;<i class="fa-solid fa-floppy-disk"></i>
                                        </button>
                                        <button type="button" id="btnGestionEditar" onclick="editarFormGestionCurso()"
                                            class="hidden btn rounded-full bg-warning/25 text-warning hover:bg-warning hover:text-white ">
                                            Actualizar curso
                                        </button>
                                        <button type="button" @click="showModal = false"
                                            class="btn rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- End Modal Wrapper -->

            <!-- Panel Apertura de Ciclo (Primer Ciclo) - Corregido x-if por x-show para que el listener sea persistente -->
            <div x-show="panel === 'apertura_manual'" x-data="modalApertura()" x-init="init()"
                @open-apertura-modal.window="openModal($event.detail)" style="display: none;"
                class="fixed inset-0 z-[1040] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100">

                <div class="card w-full max-w-2xl shadow-2xl border border-slate-200 overflow-hidden"
                    @click.away="closeModal()">
                    <div class="card-header bg-white border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h4 class="card-title">Aperturar 1er Ciclo: <span x-text="cursoNombre"
                                    class="text-primary font-bold"></span></h4>
                            <button type="button" @click="closeModal()" title="Cerrar y volver a Registro"
                                class="btn btn-sm rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200">
                                <i class="bx bx-x text-lg"></i>
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="flex flex-col h-full min-h-[400px]">
                            <div class="flex-grow flex flex-col items-center pt-8">
                                <div
                                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary/20 mb-6">
                                    <i class="bx bx-calendar-star text-primary text-3xl"></i>
                                </div>

                                <div
                                    class="bg-blue-50 border border-blue-100 rounded-lg p-5 mb-8 w-full max-w-lg shadow-sm">
                                    <div class="flex">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <i class="bx bx-info-circle text-blue-500 text-xl"></i>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-blue-800">Sobre la Programación Manual
                                            </h3>
                                            <p class="text-sm text-blue-700 mt-2">
                                                Selecciona el <strong>mes de la campaña</strong>.
                                                El curso se habilitará desde el primer hasta el último día de ese mes.
                                                Se matriculará masivamente a todo el personal activo asignado.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="w-full max-w-sm mb-4">
                                    <label for="fecha_inicio_modal"
                                        class="block text-sm font-semibold leading-6 text-gray-900 text-center mb-2">Mes
                                        de
                                        la Campaña (Año y Mes)</label>
                                    <input type="month" x-model="fechaInicio" id="fecha_inicio_modal"
                                        class="block w-full rounded-md border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary text-center text-lg sm:leading-6">
                                </div>

                                <!-- NUEVO: Filtros Grupales (Punto 11) -->
                                <div x-show="!incluirAutomatico" x-transition
                                    class="w-full max-w-lg mt-4 bg-gray-50 border border-gray-200 rounded-lg p-4 shadow-sm">
                                    <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-200">
                                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            <i class="bx bx-filter-alt mr-1"></i> Criterios de Selección
                                        </h4>
                                        <!-- Switch Matrícula Automática -->
                                        <div
                                            class="flex items-center gap-2 bg-white px-2 py-1 rounded-md border border-gray-200">
                                            <span class="text-[10px] font-bold text-gray-500 uppercase">Automático</span>
                                            <input class="form-switch scale-75" type="checkbox" role="switch"
                                                x-model="incluirAutomatico" id="swIncluirAuto">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <!-- Cliente -->
                                        <div>
                                            <label
                                                class="block text-[11px] font-semibold text-gray-600 mb-1">Cliente</label>
                                            <select x-model="selectedCliente"
                                                class="w-full text-xs rounded border-gray-300 py-1.5 focus:ring-primary focus:border-primary">
                                                <option value="">-- Todos --</option>
                                                <template x-for="item in combosApertura.clientes" :key="item.codigo">
                                                    <option :value="item.codigo" x-text="item.nombre"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <!-- Sede -->
                                        <div>
                                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">Sede /
                                                Sucursal</label>
                                            <select x-model="selectedSucursal"
                                                class="w-full text-xs rounded border-gray-300 py-1.5 focus:ring-primary focus:border-primary">
                                                <option value="">-- Todas --</option>
                                                <template x-for="item in combosApertura.sucursales"
                                                    :key="item.codigo">
                                                    <option :value="item.codigo" x-text="item.nombre"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <!-- Área -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">Área /
                                                Sistema de Gestión</label>
                                            <select x-model="selectedArea"
                                                class="w-full text-xs rounded border-gray-300 py-1.5 focus:ring-primary focus:border-primary">
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
                                    <p class="text-[11px] text-gray-500 mb-2 italic">Si pega DNIs aquí, el sistema los
                                        matriculará directamente junto con la segmentación automática.</p>
                                    <textarea x-model="listaDNIPaste" rows="4"
                                        class="block w-full rounded-md border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary text-sm placeholder:text-gray-400"
                                        placeholder="Pegue una columna de DNIs aquí (uno por línea)..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-center w-full gap-4 py-8 mt-6 border-t border-gray-100">
                            <button type="button" @click="guardarApertura()" :disabled="cargando"
                                class="btn rounded-full bg-primary/25 text-primary hover:bg-primary hover:text-white transition-colors px-6 shadow-sm disabled:opacity-50">
                                <span x-show="!cargando" class="flex items-center"><i
                                        class="bx bx-calendar-star text-base mr-2"></i> Aperturar Ciclo</span>
                                <span x-show="cargando" class="flex items-center"><i
                                        class="bx bx-loader-alt bx-spin text-base mr-2"></i> Procesando...</span>
                            </button>
                            <button type="button" @click="closeModal()" :disabled="cargando"
                                class="btn rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors px-6 disabled:opacity-50">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- End Outer x-data for panel management -->
    @endsection

    @section('script')
        <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>

        @vite(['resources/js/functions/capacitacion/gestion_cursos.js'])

        {{-- ============================================================ --}}
        {{-- MODAL EXAMEN WORD 2026 - Independiente                       --}}
        {{-- ============================================================ --}}
        <div id="modal-word-2026" x-data="modalExamenWord()"
            @abrir-modal-word.window="abrirModalWord($event.detail.preguntas, $event.detail.cursoId, $event.detail.examenId, $event.detail.nombreArc, $event.detail.metrics)"
            style="display:contents">

            <div x-show="mostrarModal" x-cloak x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                style="position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,0.85);backdrop-filter:blur(8px)">
                <div
                    style="background:#f8fafc;border-radius:1.25rem;width:100%;max-width:1250px;max-height:88vh;margin:auto;display:flex;flex-direction:column;overflow:hidden;border:1px solid rgba(255,255,255,0.15);box-shadow:0 25px 60px -15px rgba(0,0,0,0.5)">

                    {{-- Header --}}
                    <div
                        style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);padding:1.25rem 1.75rem;display:flex;justify-content:space-between;align-items:center;flex-shrink:0">
                        <div>
                            <h3
                                style="color:#fff;font-size:1rem;font-weight:800;margin:0;display:flex;align-items:center;gap:0.5rem">
                                <i class="bx bx-file" style="color:#6366f1;font-size:1.35rem"></i>
                                Revisión de Examen Extraído
                                <span
                                    style="background:rgba(255,255,255,0.1);color:#cbd5e1;font-size:0.6rem;padding:0.15rem 0.6rem;border-radius:100px;border:1px solid rgba(255,255,255,0.15);font-weight:900;letter-spacing:0.1em">LOCAL
                                    2026</span>
                            </h3>
                            <p
                                style="color:#64748b;font-size:0.7rem;margin:0.15rem 0 0;display:flex;align-items:center;gap:0.25rem">
                                <i class="bx bx-file-blank"></i> Fuente: <span x-text="archivoNombre"
                                    style="color:#94a3b8"></span>
                            </p>
                        </div>
                        <button type="button" @click="mostrarModal=false"
                            style="width:2rem;height:2rem;border-radius:50%;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);color:#94a3b8;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s"
                            onmouseover="this.style.background='rgba(255,255,255,0.15)'"
                            onmouseout="this.style.background='rgba(255,255,255,0.08)'">
                            <i class="bx bx-x" style="font-size:1.25rem"></i>
                        </button>
                    </div>

                    {{-- Sub-header info --}}
                    <div
                        style="padding:0.75rem 1.75rem;background:#f1f5f9;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;flex-wrap:wrap;gap:1rem">
                        <div style="display:flex;flex-direction:column;gap:0.25rem">
                            <p style="font-size:0.7rem;color:#64748b;margin:0">
                                <i class="bx bx-info-circle" style="color:#3b82f6"></i>
                                Valide las respuestas correctas. Use los selects para cambiar el tipo de pregunta.
                            </p>
                        </div>
                        <div style="display:flex;gap:0.5rem">
                            <span
                                style="font-size:0.65rem;font-weight:700;padding:0.2rem 0.5rem;background:#fff;border:1px solid #e2e8f0;border-radius:0.375rem;color:#475569;display:flex;align-items:center;gap:0.3rem">
                                <span
                                    style="width:0.5rem;height:0.5rem;border-radius:50%;background:#3b82f6;display:inline-block"></span>
                                Básica
                            </span>
                            <span
                                style="font-size:0.65rem;font-weight:700;padding:0.2rem 0.5rem;background:#fff;border:1px solid #e2e8f0;border-radius:0.375rem;color:#475569;display:flex;align-items:center;gap:0.3rem">
                                <span
                                    style="width:0.5rem;height:0.5rem;border-radius:50%;background:#f97316;display:inline-block"></span>
                                Complementaria
                            </span>
                        </div>
                    </div>

                    {{-- Grid de preguntas (scroll interno) --}}
                    <div style="flex:1;overflow-y:auto;padding:1.25rem 1.5rem;background:#f8fafc">
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.875rem">
                            <template x-for="(p, index) in preguntas" :key="index">
                                <div style="background:#fff;border-radius:0.75rem;border:1px solid #e2e8f0;border-left:4px solid;display:flex;flex-direction:column;overflow:hidden;transition:box-shadow 0.2s"
                                    :style="p.tipo == 'A' ? 'border-left-color:#3b82f6' : 'border-left-color:#f97316'">

                                    {{-- Card Header --}}
                                    <div
                                        style="padding:0.5rem 0.75rem;background:#f8fafc;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center">
                                        <span
                                            style="font-size:0.75rem;font-weight:900;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;display:flex;align-items:center;gap:0.35rem">
                                            P. <span x-text="index+1"
                                                style="background:#e2e8f0;color:#475569;width:1.4rem;height:1.4rem;border-radius:0.25rem;display:inline-flex;align-items:center;justify-content:center;font-size:0.8rem"></span>
                                        </span>
                                        <select x-model="p.tipo"
                                            style="font-size:0.75rem;font-weight:700;border:1px solid #e2e8f0;border-radius:0.375rem;padding:0.25rem 0.5rem;background:transparent;color:#475569;cursor:pointer">
                                            <option value="A">Básica</option>
                                            <option value="B">Complementaria</option>
                                        </select>
                                    </div>

                                    {{-- Card Body --}}
                                    <div style="padding:0.75rem;flex:1">
                                        <p style="font-size:0.85rem;font-weight:700;color:#1e293b;margin:0 0 0.625rem;line-height:1.4"
                                            x-text="p.texto"></p>
                                        <div style="display:flex;flex-direction:column;gap:0.4rem">
                                            <template x-for="(opt, optIndex) in p.opciones" :key="optIndex">
                                                <label
                                                    style="display:flex;align-items:center;padding:0.375rem 0.5rem;border-radius:0.5rem;border:1px solid;cursor:pointer;transition:all 0.15s"
                                                    :style="p.respuesta_correcta == chr(65 + optIndex) ?
                                                        'border-color:#86efac;background:#f0fdf4' :
                                                        'border-color:#f1f5f9;background:#fafafa'">
                                                    <input type="radio" :name="'resp_' + index"
                                                        :value="chr(65 + optIndex)" x-model="p.respuesta_correcta"
                                                        style="width:0.85rem;height:0.85rem;accent-color:#16a34a;flex-shrink:0">
                                                    <span
                                                        style="margin-left:0.5rem;font-size:0.80rem;font-weight:500;color:#475569;flex:1;line-height:1.35"
                                                        x-text="opt"></span>
                                                    <i x-show="p.respuesta_correcta == chr(65 + optIndex)"
                                                        class="bx bxs-check-circle"
                                                        style="color:#16a34a;font-size:0.95rem;margin-left:auto;flex-shrink:0"></i>
                                                </label>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div
                        style="padding:1rem 1.75rem;background:#fff;border-top:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;flex-shrink:0">
                        <div
                            style="display:flex;align-items:center;gap:0.5rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:0.625rem;padding:0.5rem 0.875rem">
                            <i class="bx bx-info-circle" style="color:#3b82f6;font-size:1rem"></i>
                            <span style="font-size:0.7rem;color:#1d4ed8;font-weight:600">Las preguntas se guardarán
                                automáticamente al crear o actualizar el curso</span>
                        </div>
                        <div style="display:flex;gap:0.75rem">
                            <button type="button" @click="mostrarModal=false"
                                style="padding:0.6rem 1.25rem;border-radius:0.75rem;font-size:0.75rem;font-weight:700;color:#64748b;background:transparent;border:1px solid #e2e8f0;cursor:pointer;transition:all 0.2s"
                                onmouseover="this.style.background='#f1f5f9'"
                                onmouseout="this.style.background='transparent'">
                                Cerrar
                            </button>
                            <button type="button" @click="mostrarModal=false"
                                style="padding:0.6rem 1.75rem;border-radius:0.75rem;font-size:0.75rem;font-weight:900;color:#fff;background:linear-gradient(135deg,#2563eb,#4f46e5);border:none;cursor:pointer;display:flex;align-items:center;gap:0.5rem;box-shadow:0 4px 15px -3px rgba(37,99,235,0.5);transition:all 0.2s;text-transform:uppercase;letter-spacing:0.05em">
                                <i class="bx bxs-check-circle"></i>
                                Confirmar Vista Previa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            window.modalExamenWord = function() {
                return {
                    mostrarModal: false,
                    preguntas: [],
                    codCursoActual: null,
                    archivoNombre: '',

                    abrirModalWord(preguntas, cursoId, examenId, nombreArc) {
                        this.preguntas = Array.isArray(preguntas) ? preguntas : [];
                        this.codCursoActual = cursoId;
                        this.archivoNombre = nombreArc || '';
                        this.mostrarModal = true;
                    },

                    chr(code) {
                        return String.fromCharCode(code);
                    }
                };
            }

            /**
             * Componente para Matrícula Masiva vía Excel (2026)
             */
            window.modalImportacionExcel = function() {
                return {
                    mostrarModal: false,
                    cargando: false,
                    procesandoMatricula: false,
                    preguntasIA: [], // No se usa aquí pero para consistencia si hay conflictos
                    codCursoActual: null,
                    nombreCursoActual: '',
                    personalEncontrado: [],
                    resumen: {
                        total: 0,
                        encontrados: 0,
                        errores: 0,
                        advertencias: 0
                    },
                    filtros: {
                        soloErrores: false
                    },

                    abrirModalExcel(curso) {
                        this.codCursoActual = curso.codigo;
                        this.nombreCursoActual = curso.nombre;
                        this.personalEncontrado = [];
                        this.mostrarModal = true;
                        this.resetResumen();
                        // Limpiar input file si existe
                        const input = document.getElementById('inputExcelMatricula');
                        if (input) input.value = '';
                    },

                    resetResumen() {
                        this.resumen = {
                            total: 0,
                            encontrados: 0,
                            errores: 0,
                            advertencias: 0
                        };
                    },

                    async procesarArchivo(event) {
                        const file = event.target.files[0];
                        if (!file) return;

                        this.cargando = true;
                        const formData = new FormData();
                        formData.append('archivo', file);

                        try {
                            const response = await fetch('/api/capacitacion/validar-excel-matricula', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: formData
                            });
                            const res = await response.json();

                            if (res.success) {
                                this.personalEncontrado = res.data;
                                this.actualizarResumen();
                            } else {
                                Swal.fire('Error', res.message, 'error');
                            }
                        } catch (e) {
                            console.error(e);
                            Swal.fire('Error', 'No se pudo procesar el archivo Excel.', 'error');
                        } finally {
                            this.cargando = false;
                        }
                    },

                    actualizarResumen() {
                        this.resumen.total = this.personalEncontrado.length;
                        this.resumen.encontrados = this.personalEncontrado.filter(p => p.status !== 'RED').length;
                        this.resumen.errores = this.personalEncontrado.filter(p => p.status === 'RED').length;
                        this.resumen.advertencias = this.personalEncontrado.filter(p => p.status === 'AMBER').length;
                    },

                    get listaFiltrada() {
                        if (this.filtros.soloErrores) {
                            return this.personalEncontrado.filter(p => p.status === 'RED');
                        }
                        return this.personalEncontrado;
                    },

                    async confirmarMatricula() {
                        const swal = window.Swal;
                        const validos = this.personalEncontrado.filter(p => p.status !== 'RED');
                        if (validos.length === 0) {
                            swal ? swal.fire('Atención', 'No hay personal válido para matricular.', 'warning') : alert(
                                'No hay personal válido para matricular.');
                            return;
                        }

                        const confirmResult = swal ? await swal.fire({
                            title: '¿Confirmar Matrícula Masiva?',
                            text: `Se matricularán ${validos.length} personas al curso "${this.nombreCursoActual}".`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, matricular',
                            cancelButtonText: 'Cancelar'
                        }) : {
                            isConfirmed: confirm(`¿Matricular ${validos.length} personas?`)
                        };

                        if (!confirmResult.isConfirmed) return;

                        this.procesandoMatricula = true;
                        try {
                            const response = await fetch('/api/capacitacion/confirmar-matricula-masiva', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    cod_curso: this.codCursoActual,
                                    personal: validos
                                })
                            });
                            const res = await response.json();
                            if (res.success) {
                                swal ? swal.fire('¡Éxito!', res.message, 'success') : alert(res.message);
                                this.mostrarModal = false;
                            } else {
                                swal ? swal.fire('Error', res.message, 'error') : alert('Error: ' + res.message);
                            }
                        } catch (e) {
                            console.error(e);
                            swal ? swal.fire('Error', 'Ocurrió un problema al procesar la matrícula masiva.', 'error') :
                                alert('Error al procesar la matrícula.');
                        } finally {
                            this.procesandoMatricula = false;
                        }
                    }
                };
            }
        </script>

        <!-- MODAL: MATRÍCULA MASIVA EXCEL (2026) -->
        <div x-data="modalImportacionExcel()" @abrir-modal-excel.window="abrirModalExcel($event.detail)"
            style="display:contents">

            <div x-show="mostrarModal" x-cloak x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:1.5rem;background:rgba(0,0,0,0.55);backdrop-filter:blur(4px)">
                {{-- Contenedor: tamaño automático según contenido, con máximo --}}
                <div
                    style="background:#fff;border-radius:1rem;width:100%;max-width:780px;margin:auto;display:flex;flex-direction:column;border:1px solid #e2e8f0;box-shadow:0 20px 60px -10px rgba(0,0,0,0.25);overflow:hidden">

                    {{-- Header --}}
                    <div
                        style="padding:0.875rem 1.25rem;background:linear-gradient(135deg,#1d4ed8,#4338ca);color:#fff;display:flex;justify-content:space-between;align-items:center;flex-shrink:0">
                        <div>
                            <h3
                                style="margin:0;font-size:0.95rem;font-weight:800;display:flex;align-items:center;gap:0.5rem">
                                <i class="bx bxs-file-import"></i> Matrícula Masiva vía Excel
                            </h3>
                            <p style="margin:0;font-size:0.7rem;opacity:0.75;margin-top:0.15rem"
                                x-text="'Curso: ' + nombreCursoActual"></p>
                        </div>
                        <button @click="mostrarModal = false"
                            style="background:rgba(255,255,255,0.15);border:none;color:#fff;border-radius:50%;width:2rem;height:2rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.2s"
                            onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                            onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                            <i class="bx bx-x" style="font-size:1.1rem"></i>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div style="padding:1rem;background:#f8fafc;overflow-y:auto;max-height:calc(90vh - 130px)">

                        {{-- Zona de Carga (compacta) --}}
                        <div x-show="personalEncontrado.length === 0 && !cargando"
                            style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem 1.5rem;border:2px dashed #cbd5e1;border-radius:0.875rem;background:#fff;cursor:pointer;transition:all 0.2s"
                            onmouseover="this.style.borderColor='#3b82f6';this.style.background='#eff6ff'"
                            onmouseout="this.style.borderColor='#cbd5e1';this.style.background='#fff'"
                            @click="$refs.fileInput.click()">
                            <input type="file" x-ref="fileInput" id="inputExcelMatricula" class="hidden"
                                accept=".xlsx,.xls,.csv" @change="procesarArchivo">
                            <div
                                style="width:3.5rem;height:3.5rem;background:#dbeafe;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:0.75rem">
                                <i class="bx bx-cloud-upload" style="font-size:1.75rem;color:#2563eb"></i>
                            </div>
                            <h4 style="margin:0 0 0.35rem;font-size:0.875rem;font-weight:700;color:#1e293b">Subir
                                listado de agentes</h4>
                            <p
                                style="margin:0 0 0.875rem;font-size:0.72rem;color:#94a3b8;text-align:center;max-width:320px;line-height:1.5">
                                Archivo Excel (.xlsx/.xls) con columnas: <strong>DNI, Nombre, Cargo y Cliente</strong>
                            </p>
                            <button type="button"
                                style="padding:0.45rem 1.25rem;background:#2563eb;color:#fff;border:none;border-radius:0.5rem;font-size:0.75rem;font-weight:700;cursor:pointer;box-shadow:0 4px 12px rgba(37,99,235,0.35);transition:all 0.2s"
                                onmouseover="this.style.background='#1d4ed8'"
                                onmouseout="this.style.background='#2563eb'">
                                Seleccionar archivo
                            </button>
                        </div>

                        {{-- Loader --}}
                        <div x-show="cargando"
                            style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2.5rem;gap:0.75rem">
                            <div
                                style="width:2.5rem;height:2.5rem;border:4px solid #dbeafe;border-top-color:#2563eb;border-radius:50%;animation:spin 0.8s linear infinite">
                            </div>
                            <span style="font-size:0.8rem;color:#64748b;font-weight:600">Analizando documento...</span>
                        </div>

                        {{-- Resultados (Resumen + Tabla) --}}
                        <div x-show="personalEncontrado.length > 0 && !cargando" x-transition
                            style="display:flex;flex-direction:column;gap:0.75rem">

                            {{-- Resumen en una sola fila horizontal --}}
                            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.5rem">
                                <div
                                    style="background:#fff;border:1px solid #e2e8f0;border-radius:0.625rem;padding:0.6rem 0.75rem;display:flex;align-items:center;gap:0.6rem">
                                    <div style="background:#eff6ff;border-radius:0.375rem;padding:0.375rem;color:#2563eb">
                                        <i class="bx bx-group" style="font-size:1rem;display:block"></i>
                                    </div>
                                    <div>
                                        <p
                                            style="margin:0;font-size:0.6rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.05em">
                                            Total</p><span style="font-size:1rem;font-weight:900;color:#1e293b"
                                            x-text="resumen.total"></span>
                                    </div>
                                </div>
                                <div
                                    style="background:#fff;border:1px solid #e2e8f0;border-radius:0.625rem;padding:0.6rem 0.75rem;display:flex;align-items:center;gap:0.6rem">
                                    <div style="background:#f0fdf4;border-radius:0.375rem;padding:0.375rem;color:#16a34a">
                                        <i class="bx bx-check-circle" style="font-size:1rem;display:block"></i>
                                    </div>
                                    <div>
                                        <p
                                            style="margin:0;font-size:0.6rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.05em">
                                            Listos</p><span style="font-size:1rem;font-weight:900;color:#1e293b"
                                            x-text="resumen.encontrados"></span>
                                    </div>
                                </div>
                                <div
                                    style="background:#fff;border:1px solid #e2e8f0;border-radius:0.625rem;padding:0.6rem 0.75rem;display:flex;align-items:center;gap:0.6rem">
                                    <div style="background:#fff7ed;border-radius:0.375rem;padding:0.375rem;color:#ea580c">
                                        <i class="bx bx-error" style="font-size:1rem;display:block"></i>
                                    </div>
                                    <div>
                                        <p
                                            style="margin:0;font-size:0.6rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.05em">
                                            Alerta</p><span style="font-size:1rem;font-weight:900;color:#1e293b"
                                            x-text="resumen.advertencias"></span>
                                    </div>
                                </div>
                                <div
                                    style="background:#fff;border:1px solid #e2e8f0;border-radius:0.625rem;padding:0.6rem 0.75rem;display:flex;align-items:center;gap:0.6rem">
                                    <div style="background:#fef2f2;border-radius:0.375rem;padding:0.375rem;color:#dc2626">
                                        <i class="bx bx-x-circle" style="font-size:1rem;display:block"></i>
                                    </div>
                                    <div>
                                        <p
                                            style="margin:0;font-size:0.6rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.05em">
                                            Error</p><span style="font-size:1rem;font-weight:900;color:#1e293b"
                                            x-text="resumen.errores"></span>
                                    </div>
                                </div>
                            </div>

                            {{-- Tabla con scroll interno --}}
                            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.75rem;overflow:hidden">
                                <div
                                    style="padding:0.6rem 0.875rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;background:#f8fafc">
                                    <span style="font-size:0.75rem;font-weight:700;color:#475569">Previsualización de
                                        Personal</span>
                                    <div style="display:flex;align-items:center;gap:0.875rem">
                                        <label style="display:flex;align-items:center;gap:0.35rem;cursor:pointer">
                                            <input type="checkbox" x-model="filtros.soloErrores"
                                                style="accent-color:#dc2626;width:0.85rem;height:0.85rem">
                                            <span style="font-size:0.68rem;font-weight:600;color:#64748b">Solo
                                                errores</span>
                                        </label>
                                        <button @click="personalEncontrado = []"
                                            style="font-size:0.68rem;font-weight:700;color:#94a3b8;background:none;border:none;cursor:pointer;transition:color 0.15s"
                                            onmouseover="this.style.color='#ef4444'"
                                            onmouseout="this.style.color='#94a3b8'">Cambiar archivo</button>
                                    </div>
                                </div>
                                <div style="overflow-y:auto;max-height:300px">
                                    <table style="width:100%;border-collapse:collapse;font-size:0.72rem;text-align:left">
                                        <thead style="position:sticky;top:0;z-index:1">
                                            <tr
                                                style="background:#f8fafc;color:#94a3b8;font-size:0.62rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em">
                                                <th style="padding:0.5rem 0.75rem;border-bottom:1px solid #f1f5f9">
                                                    Estado</th>
                                                <th style="padding:0.5rem 0.75rem;border-bottom:1px solid #f1f5f9">DNI
                                                </th>
                                                <th style="padding:0.5rem 0.75rem;border-bottom:1px solid #f1f5f9">
                                                    Personal (Sistema)</th>
                                                <th style="padding:0.5rem 0.75rem;border-bottom:1px solid #f1f5f9">
                                                    Nombre Excel</th>
                                                <th style="padding:0.5rem 0.75rem;border-bottom:1px solid #f1f5f9">
                                                    Cargo / Cliente</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(p, index) in listaFiltrada" :key="p.dni || index">
                                                <tr style="border-bottom:1px solid #f8fafc;transition:background 0.1s"
                                                    onmouseover="this.style.background='#f8fafc'"
                                                    onmouseout="this.style.background='transparent'">
                                                    <td style="padding:0.45rem 0.75rem">
                                                        <template x-if="p.status === 'GREEN'">
                                                            <span
                                                                style="display:inline-flex;align-items:center;gap:0.3rem;padding:0.2rem 0.6rem;border-radius:9999px;background:#dcfce7;color:#15803d;font-size:0.6rem;font-weight:800">
                                                                <span
                                                                    style="width:0.35rem;height:0.35rem;border-radius:50%;background:#16a34a;display:inline-block"></span>
                                                                LISTO
                                                            </span>
                                                        </template>
                                                        <template x-if="p.status === 'AMBER'">
                                                            <span
                                                                @click="window.Swal ? window.Swal.fire('Motivo de Alerta', p.warnings.join('<br>'), 'warning') : alert(p.warnings.join('\n'))"
                                                                style="display:inline-flex;align-items:center;gap:0.3rem;padding:0.2rem 0.6rem;border-radius:9999px;background:#ffedd5;color:#c2410c;font-size:0.6rem;font-weight:800;cursor:pointer;transition:transform 0.1s"
                                                                onmouseover="this.style.transform='scale(1.05)'"
                                                                onmouseout="this.style.transform='scale(1)'"
                                                                :title="'Clic para ver detalle'">
                                                                <span
                                                                    style="width:0.35rem;height:0.35rem;border-radius:50%;background:#ea580c;display:inline-block"></span>
                                                                ALERTA
                                                            </span>
                                                        </template>
                                                        <template x-if="p.status === 'RED'">
                                                            <span
                                                                style="display:inline-flex;align-items:center;gap:0.3rem;padding:0.2rem 0.6rem;border-radius:9999px;background:#fee2e2;color:#b91c1c;font-size:0.6rem;font-weight:800">
                                                                <span
                                                                    style="width:0.35rem;height:0.35rem;border-radius:50%;background:#dc2626;display:inline-block"></span>
                                                                NO EXISTE
                                                            </span>
                                                        </template>
                                                    </td>
                                                    <td style="padding:0.45rem 0.75rem;font-family:monospace;font-weight:700;color:#475569"
                                                        x-text="p.dni"></td>
                                                    <td style="padding:0.45rem 0.75rem;font-weight:600;color:#1e293b"
                                                        x-text="p.nombre_db"></td>
                                                    <td style="padding:0.45rem 0.75rem;color:#94a3b8;font-style:italic"
                                                        x-text="p.nombre_excel"></td>
                                                    <td style="padding:0.45rem 0.75rem">
                                                        <div style="font-weight:700;color:#334155" x-text="p.cargo">
                                                        </div>
                                                        <div style="font-size:0.62rem;color:#94a3b8" x-text="p.cliente">
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                    <div x-show="listaFiltrada.length === 0"
                                        style="padding:2rem;text-align:center;color:#94a3b8;font-size:0.8rem">No hay
                                        datos con los filtros actuales.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div
                        style="padding:0.75rem 1.25rem;background:#fff;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-shrink:0">
                        <button @click="mostrarModal = false"
                            style="padding:0.45rem 1rem;font-size:0.78rem;font-weight:700;color:#64748b;background:transparent;border:1px solid #e2e8f0;border-radius:0.5rem;cursor:pointer;transition:all 0.15s"
                            onmouseover="this.style.background='#f1f5f9'"
                            onmouseout="this.style.background='transparent'">Cerrar</button>
                        <button x-show="personalEncontrado.length > 0" @click="confirmarMatricula"
                            :disabled="procesandoMatricula || resumen.encontrados === 0"
                            style="padding:0.45rem 1.25rem;background:linear-gradient(135deg,#2563eb,#4f46e5);color:#fff;border:none;border-radius:0.5rem;font-size:0.78rem;font-weight:800;cursor:pointer;display:flex;align-items:center;gap:0.4rem;box-shadow:0 4px 15px -3px rgba(37,99,235,0.4);transition:all 0.2s"
                            :style="(procesandoMatricula || resumen.encontrados === 0) ? 'opacity:0.5;cursor:not-allowed' : ''">
                            <template x-if="!procesandoMatricula">
                                <i class="bx bxs-paper-plane"></i>
                            </template>
                            <template x-if="procesandoMatricula">
                                <div
                                    style="width:0.85rem;height:0.85rem;border:2px solid rgba(255,255,255,0.4);border-top-color:#fff;border-radius:50%;animation:spin 0.8s linear infinite">
                                </div>
                            </template>
                            Procesar Matrícula Masiva
                        </button>
                    </div>
                </div>
            </div>{{-- cierre x-show --}}
        </div>{{-- cierre x-data --}}

        <script>
            // Inicialización síncrona para evitar Alpine/Vite race conditions
            window.alertasVencimientoCursos = function() {
                return {
                    alertas: [],
                    initAlertas() {

                        fetch(`${VITE_URL_APP}/api/cursos/alertas-vencimiento`)
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
                    combosApertura: {
                        sucursales: [],
                        clientes: [],
                        areas: []
                    },

                    async init() {
                        await this.fetchCombos();
                    },

                    async fetchCombos() {

                        try {
                            const response = await fetch(`${VITE_URL_APP}/api/capacitacion/combos-apertura`);
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
                            detail: {
                                panel: 'apertura_manual',
                                titulo: this.cursoNombre
                            }
                        }));
                        this.isOpen = true;
                        this.cargando = false;
                    },

                    closeModal() {
                        window.dispatchEvent(new CustomEvent('cambiar-panel', {
                            detail: {
                                panel: 'registro'
                            }
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
                                detail: {
                                    titulo: "Atención",
                                    mensaje: "Debe seleccionar un mes de campaña.",
                                    tipo: "warning"
                                }
                            }));
                            return;
                        }

                        const dnisLimpios = this.listaDNIPaste.trim() ?
                            this.listaDNIPaste.split(/\n|,|;/).map(d => d.trim()).filter(d => d.length > 0) : [];

                        if (this.tipoCursoId == '6' && this.clientesAsignados.length === 0 && dnisLimpios.length ===
                            0) {
                            window.dispatchEvent(new CustomEvent('mostrar-alerta', {
                                detail: {
                                    titulo: "Atención",
                                    mensaje: "Debe seleccionar al menos un cliente o pegar una lista de DNIs.",
                                    tipo: "warning"
                                }
                            }));
                            return;
                        }

                        if (this.tipoCursoId == '7' && this.areasAsignadas.length === 0 && dnisLimpios.length === 0) {
                            window.dispatchEvent(new CustomEvent('mostrar-alerta', {
                                detail: {
                                    titulo: "Atención",
                                    mensaje: "Debe seleccionar al menos un área operativa o pegar una lista de DNIs.",
                                    tipo: "warning"
                                }
                            }));
                            return;
                        }

                        this.cargando = true;


                        try {
                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                                'content');
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

                            const response = await fetch(`${VITE_URL_APP}/api/cursos/programacion-manual`, {
                                method: 'POST',
                                headers: headers,
                                body: JSON.stringify(payload)
                            });

                            const result = await response.json();

                            if (response.ok && result.success) {
                                this.closeModal();
                                // El mensaje viene del controlador indicando si fue masiva o solo apertura de ciclo
                                const mensajeFinal = result.message || "Operación exitosa";

                                window.dispatchEvent(new CustomEvent('mostrar-alerta', {
                                    detail: {
                                        mensaje: mensajeFinal,
                                        tipo: "success",
                                        toast: true,
                                        recargar: true
                                    }
                                }));
                            } else {
                                window.dispatchEvent(new CustomEvent('mostrar-alerta', {
                                    detail: {
                                        titulo: "No se pudo aperturar",
                                        mensaje: result.message || "Error al procesar la solicitud.",
                                        tipo: "error"
                                    }
                                }));
                            }
                        } catch (error) {
                            console.error("Error aperturando curso:", error);
                            window.dispatchEvent(new CustomEvent('mostrar-alerta', {
                                detail: {
                                    titulo: "Error de Servidor",
                                    mensaje: "Ocurrió un problema de conectividad con el servidor. Revisa los logs.",
                                    tipo: "error"
                                }
                            }));
                        } finally {
                            this.cargando = false;
                        }
                    }
                };
            };

            // Escuchador global en Vanilla JS para evadir el Proxy de AlpineJS
            window.addEventListener('mostrar-alerta', function(e) {
                if (typeof Swal !== 'undefined') {
                    if (e.detail.toast) {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        });
                        Toast.fire({
                            icon: e.detail.tipo,
                            title: e.detail.mensaje
                        }).then(() => {
                            if (e.detail.recargar) {
                                window.location.reload();
                            }
                        });
                    } else {
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
                    }
                } else {
                    const title = e.detail.titulo ? e.detail.titulo + ": " : "";
                    alert(title + e.detail.mensaje);
                    if (e.detail.recargar) window.location.reload();
                }
            });
        </script>
