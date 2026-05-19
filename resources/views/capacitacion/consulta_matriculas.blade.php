@extends('layouts.vertical', ['title' => 'Gestión de Matrículas'])
@section('css')
<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    /* Ultra-thin Scrollbar Masterpiece */
    .custom-scrollbar,
    .tabulator-tableholder {
        scrollbar-width: thin;
        scrollbar-color: rgba(0, 0, 0, 0.1) transparent;
    }

    .custom-scrollbar::-webkit-scrollbar,
    .tabulator-tableholder::-webkit-scrollbar {
        width: 4px;
        height: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-track,
    .tabulator-tableholder::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb,
    .tabulator-tableholder::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.08);
        border-radius: 20px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover,
    .tabulator-tableholder::-webkit-scrollbar-thumb:hover {
        background: rgba(0, 0, 0, 0.15);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fadeIn 0.4s ease-out forwards;
    }

    /* Tabulator Masterpiece Tweaks */
    .tabulator {
        border: none !important;
        background: transparent !important;
    }

    .tabulator-header {
        background-color: rgba(249, 250, 251, 0.8) !important;
        border-bottom: 1px solid #f3f4f6 !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        font-size: 10px !important;
    }

    .tabulator-row {
        border-bottom: 1px solid #f9fafb !important;
        background: transparent !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .tabulator-row .tabulator-cell,
    #tblCursos td {
        padding-top: 10.5px !important;
        padding-bottom: 6px !important;
        vertical-align: middle !important;
    }


    .tabulator-row:hover {
        background-color: rgba(var(--tw-color-primary), 0.05) !important;
        transform: translateX(4px);
        box-shadow: inset 4px 0 0 0 rgb(var(--tw-color-primary)) !important;
    }

    .tabulator-tableholder {
        overflow-x: hidden !important;
    }

    /* Tabulator Footer Masterpiece Fixes */
    .tabulator-footer {
        border-top: 1px solid #f3f4f6 !important;
        padding: 12px 12px 20px 12px !important;
        background-color: #ffffff !important;
        text-align: center !important;
    }

    /* --- ESTILO TIPO TABULATOR PARA DATATABLES --- */
    #tblCursos .dataTable-sorter {
        padding-right: 20px !important;
        background: none !important;
        display: inline-flex !important;
        align-items: center;
    }

    #tblCursos .dataTable-sorter::before,
    #tblCursos .dataTable-sorter::after {
        display: none !important;
        content: none !important;
    }

    #tblCursos .dataTable-sorter::after {
        content: "" !important;
        display: inline-block !important;
        width: 0 !important;
        height: 0 !important;
        margin-left: 8px !important;
        border-left: 4px solid transparent !important;
        border-right: 4px solid transparent !important;
        border-bottom: 4px solid #94a3b8 !important;
        opacity: 0.4;
        position: static !important;
    }

    #tblCursos .asc .dataTable-sorter::after {
        opacity: 1 !important;
        border-bottom-color: #2563eb !important;
    }

    #tblCursos .desc .dataTable-sorter::after {
        opacity: 1 !important;
        border-bottom: none !important;
        border-top: 4px solid #2563eb !important;
    }

    th:last-child .dataTable-sorter {
        cursor: default;
    }

    th:last-child .dataTable-sorter::after {
        display: none !important;
    }

    .tabulator-footer .tabulator-paginator {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
        margin-bottom: 8px !important;
    }

    .tabulator-footer .tabulator-page-counter {
        display: block !important;
        width: 100% !important;
        color: #374151 !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        margin-top: 10px !important;
    }

    .tabulator-footer .tabulator-page.active {
        background-color: #4338ca !important;
        color: #ffffff !important;
    }


    .tabulator-footer-contents {
        flex-direction: column !important;
        align-items: left !important;
        justify-content: left !important;
        gap: 5px !important;
    }

    .tabulator-footer select.tabulator-page-size {
        padding: 5px 30px 5px 12px !important;
        border-radius: 10px !important;
        border: 1px solid #e5e7eb !important;
        background-color: #fff !important;
        font-weight: 600 !important;
        color: #374151 !important;
        appearance: none !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
        background-position: right 0.6rem center !important;
        background-repeat: no-repeat !important;
        background-size: 1.2em 1.2em !important;
        min-width: 80px !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
    }

    .tabulator-footer select.tabulator-page-size:hover {
        border-color: #3b82f6 !important;
    }

    .tabulator-footer .tabulator-page {
        border-radius: 8px !important;
        padding: 6px 12px !important;
        border: 1px solid #e5e7eb !important;
        background: #fff !important;
        color: #4b5563 !important;
        font-weight: 700 !important;
        transition: all 0.2s !important;
        margin: 0 2px !important;
    }

    .tabulator-footer .tabulator-page.active {
        background-color: #1e40af !important;
        /* Blue-800 for absolute contrast */
        border-color: #1e40af !important;
        color: #ffffff !important;
        box-shadow: 0 4px 6px -1px rgba(30, 64, 175, 0.3) !important;
    }

    .tabulator-footer .tabulator-page:hover:not(.active) {
        background-color: #f9fafb !important;
        border-color: #d1d5db !important;
    }

    /* --- ANIMACIONES PARA KARDEX --- */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(15px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.4s ease-out forwards;
    }

    /* Scrollbar personalizada para el modal */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e5e7eb;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #d1d5db;
    }
</style>
@endsection
@section('content')
@include('layouts.shared/page-title', [
'subtitle' => 'Capacitación',
'title' => 'Gestión de Matrículas',
])

<div class="grid lg:grid-cols-3 grid-cols-1 gap-6">
    <!-- Panel de selección de curso -->
    <div class="card lg:col-span-1 backdrop-blur-md bg-white/90 border border-white/20 shadow-xl rounded-2xl">
        <div
            class="card-header flex items-center justify-between bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 py-4 px-5">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <i class="i-tabler-book-2 text-xl"></i>
                </div>
                <h4 class="card-title font-bold text-gray-800">Cursos Disponibles</h4>
            </div>
        </div>

        <div class="card-body p-5">
            <div x-data="{ soloEliminados: false, filtroArea: '', filtroTipoCurso: '' }" class="space-y-4">

                <!-- Filtros Rápidos -->
                <div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-xl border border-gray-100">
                    <div class="flex items-center">
                        <input class="form-switch" type="checkbox" role="switch" id="chkEliminados"
                            x-model="soloEliminados">
                        <label class="ms-2 font-medium text-xs text-gray-600 uppercase tracking-wider"
                            for="chkEliminados">
                            Ver Eliminados
                        </label>
                    </div>
                    <i class="i-tabler-filter text-gray-400"></i>
                </div>

                <div class="flex flex-col" x-data="{
                        open: false,
                        search: '',
                        selected: null,
                        options: window.opcionesTipoCurso || [],
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
                            this.filtroTipoCurso = option ? option.codigo : '';
                        }
                    }"
                    @tipos-cursos-loaded.window="options = $event.detail">
                    <label class="text-sm font-medium text-gray-700 mb-1">
                        Tipo de curso
                    </label>

                    <div class="relative">
                        <button type="button" @click="open = !open" @click.away="open = false"
                            class="w-full bg-white border border-gray-300 rounded-lg shadow-sm px-3 py-2 text-left text-sm cursor-default focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
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
                            class="absolute z-[100] w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg flex flex-col overflow-hidden"
                            style="max-height: 250px; display: none;">

                            <div class="p-2 border-b border-gray-100 bg-gray-50 flex-shrink-0 relative">
                                <i
                                    class="i-tabler-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs z-10"></i>
                                <input type="text" x-model="search" placeholder="Buscar..."
                                    class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 py-1.5"
                                    style="padding-left: 1.5rem !important;" @click.stop>
                            </div>

                            <div @click="selectOption(null)"
                                class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-indigo-50 text-sm border-b border-gray-100 flex-shrink-0">
                                <span class="block truncate font-bold text-gray-500">-- Todos --</span>
                            </div>

                            <div class="overflow-y-auto custom-scrollbar flex-1">
                                <template x-for="option in filteredOptions" :key="option.codigo">
                                    <div @click="selectOption(option)"
                                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-indigo-50 text-sm"
                                        :class="{ 'bg-indigo-50 font-semibold text-indigo-900': selected && selected
                                                    .codigo === option.codigo }">
                                        <span class="block truncate" x-text="option.descripcion"></span>

                                        <span x-show="selected && selected.codigo === option.codigo"
                                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-indigo-600">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col" x-data="{
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
                    }" @areas-loaded.window="options = $event.detail">
                    <label class="text-sm font-medium text-gray-700 mb-1">
                        Área
                    </label>

                    <div class="relative">
                        <button type="button" @click="open = !open" @click.away="open = false"
                            class="w-full bg-white border border-gray-300 rounded-lg shadow-sm px-3 py-2 text-left text-sm cursor-default focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
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

                        <div x-show="open" x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute z-[100] w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg flex flex-col overflow-hidden"
                            style="max-height: 250px; display: none;">
                            <!-- Height original porque es un panel lateral pequeño -->

                            <div class="p-2 border-b border-gray-100 bg-gray-50 flex-shrink-0 relative">
                                <i
                                    class="i-tabler-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs z-10"></i>
                                <input type="text" x-model="search" placeholder="Buscar..."
                                    class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 py-1.5"
                                    style="padding-left: 
                                        1.5rem !important;"
                                    @click.stop>
                            </div>

                            <div @click="selectOption(null)"
                                class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-indigo-50 text-sm border-b border-gray-100 flex-shrink-0">
                                <span class="block truncate font-bold text-gray-500">-- Todas --</span>
                            </div>

                            <div class="overflow-y-auto custom-scrollbar flex-1">
                                <template x-for="option in filteredOptions" :key="option.codigo">
                                    <div @click="selectOption(option)"
                                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-indigo-50 text-sm"
                                        :class="{ 'bg-indigo-50 font-semibold text-indigo-900': selected && selected
                                                    .codigo === option.codigo }">
                                        <span class="block truncate" x-text="option.descripcion"></span>

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

                <div x-effect="listarCursosConsulta( soloEliminados ? 0 : 1, filtroArea, filtroTipoCurso )"></div>
            </div>

            <div class="mt-5 border border-gray-100 rounded-xl overflow-hidden bg-white/50">
                <table id="tblCursos" class="min-w-full text-sm">
                    <thead class="bg-gray-50/80 sticky top-0 backdrop-blur-sm">
                        <tr>
                            <th
                                class="px-3 py-3 text-left font-bold text-gray-700 uppercase text-[10px] tracking-widest">
                                Cód.</th>
                            <th
                                class="px-3 py-3 text-left font-bold text-gray-700 uppercase text-[10px] tracking-widest">
                                Curso</th>
                            <th
                                class="px-3 py-3 text-center font-bold text-gray-700 uppercase text-[10px] tracking-widest">
                                Ver</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyCursos" class="divide-y divide-gray-50">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Panel Derecho: Tabs de Historial vs Matrícula -->
    <div class="card lg:col-span-2 backdrop-blur-md bg-white/90 border border-white/20 shadow-xl rounded-2xl overflow-hidden"
        x-data="{ tabActivo: 'personal' }" @cambiar-tab-curso.window="tabActivo = 'curso'">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-100 bg-gray-50/30">
            <nav class="flex space-x-1 px-4 pt-2" aria-label="Tabs">
                <button @click="tabActivo = 'personal'"
                    :class="tabActivo === 'personal' ? 'bg-white border-gray-200 border-b-white text-primary shadow-sm' :
                            'bg-transparent border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100/50'"
                    class="whitespace-nowrap py-3 px-5 border-l border-t border-r rounded-t-xl font-bold text-sm transition-all mt-1 relative z-10 flex items-center gap-2"
                    style="margin-bottom: -1px;">
                    <i class="i-tabler-users-group text-lg"></i> Directorio
                </button>
                <button @click="tabActivo = 'curso'"
                    :class="tabActivo === 'curso' ? 'bg-white border-gray-200 border-b-white text-primary shadow-sm' :
                            'bg-transparent border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100/50'"
                    class="whitespace-nowrap py-3 px-5 border-l border-t border-r rounded-t-xl font-bold text-sm transition-all mt-1 relative z-10 flex items-center gap-2"
                    style="margin-bottom: -1px;" id="btnTabCurso">
                    <i class="i-tabler-chart-bar text-lg"></i> Matrículas
                    <span id="badgeCurrentCourse"
                        class="hidden ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary text-white shadow-sm">0</span>
                </button>
            </nav>
        </div>

        <!-- Tab 1: Directorio y Kardex -->
        <div x-show="tabActivo === 'personal'" class="p-4" x-transition>
            <div class="flex flex-wrap items-center gap-4 mb-4">
                <div class="w-full md:w-1/3 relative">
                    <i class="i-tabler-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 z-10"></i>
                    <input type="text" id="txtBusquedaPersonal"
                        class="form-input w-full py-2 text-sm rounded-xl border-gray-200 shadow-sm focus:ring-primary/20"
                        style="padding-left: 2.2rem !important;" placeholder="Buscar personal por DNI o Nombres..."
                        autofocus>
                </div>
                <div class="w-full md:w-1/4">
                    <div class="flex flex-col" x-data="{
                            open: false,
                            search: '',
                            selected: null,
                            options: window.opcionesSucursales || [],
                            get filteredOptions() {
                                if (this.search === '') return this.options;
                                return this.options.filter(opt => opt.descripcion.toLowerCase().includes(this.search.toLowerCase()));
                            },
                            selectOption(option) {
                                this.selected = option;
                                this.open = false;
                                this.search = '';
                                window.aplicarFiltroSucursalPersonal(option ? option.codigo : '');
                            }
                        }"
                        @sucursales-loaded.window="options = $event.detail">
                        <div class="relative">
                            <button type="button" @click="open = !open" @click.away="open = false"
                                class="w-full bg-white border border-gray-200 rounded-xl shadow-sm px-4 py-2 text-left text-sm cursor-default focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary flex items-center justify-between">
                                <span class="block truncate text-gray-700"
                                    x-text="selected ? selected.descripcion : 'Todas las sucursales'"></span>
                                <i class="i-tabler-chevron-down text-gray-400 text-xs"></i>
                            </button>

                            <div x-show="open" x-transition
                                class="absolute z-[100] w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl flex flex-col overflow-hidden"
                                style="max-height: 250px; display: none;">

                                <div class="p-2 border-b border-gray-100 bg-gray-50 flex-shrink-0 relative">
                                    <i
                                        class="i-tabler-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs z-10"></i>
                                    <input type="text" x-model="search" placeholder="Buscar sucursal..."
                                        style="padding-left: 
                                        1.5rem !important;"
                                        class="w-full text-sm border-gray-200 rounded-lg focus:ring-primary focus:border-primary py-1.5 pl-8 "
                                        @click.stop>
                                </div>

                                <div @click="selectOption(null)"
                                    class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-gray-50 text-sm border-b border-gray-50 flex-shrink-0">
                                    <span
                                        class="block truncate font-bold text-gray-400 uppercase text-[10px] tracking-widest">Todas
                                        las sucursales</span>
                                </div>

                                <div class="overflow-y-auto custom-scrollbar flex-1">
                                    <template x-for="option in filteredOptions" :key="option.codigo">
                                        <div @click="selectOption(option)"
                                            class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-primary/5 text-sm"
                                            :class="{ 'bg-primary/10 font-bold text-primary': selected && selected
                                                        .codigo === option.codigo }">
                                            <span class="block truncate" x-text="option.descripcion"></span>
                                            <span x-show="selected && selected.codigo === option.codigo"
                                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-primary">
                                                <i class="i-tabler-check text-sm"></i>
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <!-- Mantener el select oculto para compatibilidad con el JS si fuera necesario -->
                        <select id="filtroSucursalPersonal" class="hidden">
                            <option value="">Todas las sucursales</option>
                        </select>
                    </div>
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
                    <h4 class="text-lg font-bold text-gray-800" id="infoCursoSeleccionadoTitulo">Seleccione un Curso
                    </h4>
                    <p class="text-sm text-gray-500 mt-0.5" id="infoCursoSeleccionado">Haga clic en un curso del panel
                        izquierdo para ver sus alumnos.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary/10 text-primary"
                        id="badgeTotalMatriculas">
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
            <div class="grid grid-cols-4 gap-2 mb-4" id="estadisticasMatriculas" style="display: none;">
                <div
                    class="px-3 py-2 bg-primary rounded-xl shadow-sm text-white flex items-center justify-between overflow-hidden">
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-tighter opacity-80 leading-none">En Rol</p>
                        <p class="text-lg font-black mt-0.5 leading-none" id="countMatriculados">0</p>
                    </div>
                    <i class="i-tabler-users text-lg opacity-50"></i>
                </div>
                <div
                    class="px-3 py-2 bg-warning rounded-xl shadow-sm text-white flex items-center justify-between overflow-hidden">
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-tighter opacity-80 leading-none">Cursando</p>
                        <p class="text-lg font-black mt-0.5 leading-none" id="countEnProgreso">0</p>
                    </div>
                    <i class="i-tabler-hourglass text-lg opacity-50"></i>
                </div>
                <div
                    class="px-3 py-2 bg-success rounded-xl shadow-sm text-white flex items-center justify-between overflow-hidden">
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-tighter opacity-80 leading-none">Logrado</p>
                        <p class="text-lg font-black mt-0.5 leading-none" id="countAprobados">0</p>
                    </div>
                    <i class="i-tabler-award text-lg opacity-50"></i>
                </div>
                <div
                    class="px-3 py-2 bg-danger rounded-xl shadow-sm text-white flex items-center justify-between overflow-hidden">
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-tighter opacity-80 leading-none">Fallo</p>
                        <p class="text-lg font-black mt-0.5 leading-none" id="countReprobados">0</p>
                    </div>
                    <i class="i-tabler-square-x text-lg opacity-50"></i>
                </div>
            </div>

            <!-- Buscador y Filtros Avanzados -->
            <div class="space-y-4 mb-6" id="filtrosMatriculaContainer" style="display: none;">
                <div
                    class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                    <div class="relative">
                        <i
                            class="i-tabler-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg z-10"></i>
                        <input type="text" id="buscarMatricula"
                            class="form-input w-full py-2.5 rounded-xl border-gray-200 shadow-sm focus:ring-primary/20"
                            style="padding-left: 2.5rem !important;" placeholder="Buscar por Nombre o DNI..." />
                    </div>
                    <div class="relative">
                        <select id="slcFiltroProgramacion"
                            class="form-select w-full py-2.5 rounded-xl border-gray-200 shadow-sm focus:ring-primary/20">
                            <option value="">Todas las Fechas</option>
                        </select>
                    </div>
                    <div class="relative">
                        <div class="flex flex-col" x-data="{
                                open: false,
                                search: '',
                                selected: null,
                                options: [],
                                get filteredOptions() {
                                    if (this.search === '') return this.options;
                                    return this.options.filter(opt => opt.descripcion.toLowerCase().includes(this.search.toLowerCase()));
                                },
                                selectOption(option) {
                                    this.selected = option;
                                    this.open = false;
                                    this.search = '';
                                    window.aplicarFiltroSedeMatricula(option ? option.codigo : '');
                                }
                            }"
                            @sedes-matriculas-loaded.window="options = $event.detail">
                            <div class="relative">
                                <button type="button" @click="open = !open" @click.away="open = false"
                                    class="w-full bg-white border border-gray-200 rounded-xl shadow-sm px-4 py-2.5 text-left text-sm cursor-default focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary flex items-center justify-between">
                                    <span class="block truncate text-gray-700 font-medium"
                                        x-text="selected ? selected.descripcion : 'Todas las Sedes'"></span>
                                    <i class="i-tabler-chevron-down text-gray-400 text-xs"></i>
                                </button>

                                <div x-show="open" x-transition
                                    class="absolute z-[100] w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl flex flex-col overflow-hidden"
                                    style="max-height: 250px; display: none;">

                                    <div class="p-2 border-b border-gray-100 bg-gray-50 flex-shrink-0 relative">
                                        <i
                                            class="i-tabler-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs z-10"></i>
                                        <input type="text" x-model="search" placeholder="Buscar sede..."
                                            style="padding-left: 
                                        1.5rem !important;"
                                            class="w-full text-sm border-gray-200 rounded-lg focus:ring-primary focus:border-primary py-1.5 pl-8"
                                            @click.stop>
                                    </div>

                                    <div @click="selectOption(null)"
                                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-gray-50 text-sm border-b border-gray-50 flex-shrink-0">
                                        <span
                                            class="block truncate font-bold text-gray-400 uppercase text-[10px] tracking-widest">Todas
                                            las Sedes</span>
                                    </div>

                                    <div class="overflow-y-auto custom-scrollbar flex-1">
                                        <template x-for="option in filteredOptions" :key="option.codigo">
                                            <div @click="selectOption(option)"
                                                class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-primary/5 text-sm"
                                                :class="{ 'bg-primary/10 font-bold text-primary': selected && selected
                                                            .codigo === option.codigo }">
                                                <span class="block truncate" x-text="option.descripcion"></span>
                                                <span x-show="selected && selected.codigo === option.codigo"
                                                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-primary">
                                                    <i class="i-tabler-check text-sm"></i>
                                                </span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <select id="slcFiltroSede" class="hidden">
                                <option value="">Todas las Sedes</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold uppercase text-gray-400 tracking-widest ml-1">Filtros
                        Activos:</span>
                    <div id="activeFiltersContainer" class="flex flex-wrap gap-2">
                        <span class="text-[10px] text-gray-500 italic">Ninguno</span>
                    </div>
                </div>
            </div>

            <!-- Tabla de matrículas -->
            <div class="border border-default-200 rounded-lg overflow-hidden" id="contenedorTblMatriculas"
                style="display: none;">
                <div id="tblMatriculas" style="min-height: 400px; font-size: 13px;"></div>
            </div>

            <!-- Estado vacío -->
            <div id="estadoVacio" class="text-center py-12 mt-4">
                <i class="i-tabler-clipboard-list text-6xl text-gray-200 mb-4 block mx-auto"></i>
                <p class="text-gray-500 font-medium">Seleccione un curso de la lista izquierda</p>
                <p class="text-sm text-gray-400">Podrá ver los alumnos matriculados, aprobarlos o exportar actas.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de registro (Copiado y optimizado de gestion_matricula) -->
<div id="modal-registro"
    class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-y-auto hidden pointer-events-none">
    <div class="translate-y-10 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 my-6 mx-auto flex flex-col bg-white shadow-sm rounded"
        style="width: 720px; max-width: 95vw;">
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
                    <select id="slcProgramacionMatriculaModal"
                        class="form-select w-full rounded-md border-gray-300 shadow-sm text-[13px] py-1.5 focus:ring-1 focus:ring-indigo-500">
                        <option value="">-- Seleccione --</option>
                    </select>
                </div>

                <!-- Filtro por Sucursal -->
                <div class="mb-3">
                    <label for="slcProgramacionMatriculaModal" class="block text-xs font-medium text-gray-700 mb-1">
                        Filtrar por sucursal
                    </label>
                    <select id="slcFiltroSucursalModal"
                        class="form-select w-full rounded-md border-gray-300 shadow-sm text-[13px] py-1.5 focus:ring-1 focus:ring-indigo-500">
                        <option value="">Todas las sucursales</option>
                    </select>
                </div>

                <!-- Filtrar por tipo de personal -->
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Filtrar por tipo de personal
                    </label>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" class="form-radio text-primary" name="tipo_per_modal" value="TODOS" checked>
                            <span class="text-sm">Todos</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" class="form-radio text-primary" name="tipo_per_modal" value="ADMIN">
                            <span class="text-sm">Administrativo</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" class="form-radio text-primary" name="tipo_per_modal" value="OPER">
                            <span class="text-sm">Operativo</span>
                        </label>
                    </div>
                </div>

                <!-- Buscador -->
                <div class="mb-3">
                    <div class="relative">
                        <i
                            class="i-tabler-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm z-10"></i>
                        <input type="text" id="buscarPersonalModal"
                            class="form-input w-full py-1.5 text-[13px] rounded-md border-gray-300 focus:ring-1 focus:ring-indigo-500"
                            style="padding-left: 2.8rem !important;" placeholder="Buscar persona..." />
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
                    <div id="tblPersonalMatriculaModal" style="height: 320px; font-size: 12px;"></div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end items-center gap-2 px-4 py-2 bg-gray-50 border-t border-default-200">
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

<!-- Modal Historial (Kardex Masterpiece) -->
<div id="modal-historial"
    class="hs-overlay hidden w-full h-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto pointer-events-none">
    <div
        class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-2xl sm:w-full m-3 sm:mx-auto min-h-[calc(100%-3.5rem)] flex items-center">
        <div
            class="flex flex-col glass-card shadow-2xl rounded-3xl pointer-events-auto overflow-hidden w-full border border-white/40">
            <div
                class="flex justify-between items-center py-5 px-6 bg-gradient-to-r from-primary/10 to-transparent border-b border-gray-100">
                <div class="flex items-center gap-4">
                    <div id="avatarPersonal"
                        class="w-12 h-12 rounded-2xl bg-primary text-white flex items-center justify-center font-black text-lg shadow-lg shadow-primary/20">
                        --
                    </div>
                    <div>
                        <h3 id="nombrePersonal"
                            class="font-black text-gray-800 text-lg leading-tight uppercase tracking-tight">Cargando...
                        </h3>
                        <p class="text-[10px] font-bold text-primary uppercase tracking-widest mt-0.5">Kardex de
                            Capacitación</p>
                    </div>
                </div>
                <button type="button"
                    class="w-8 h-8 inline-flex justify-center items-center gap-2 rounded-full border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-primary transition-all text-sm"
                    data-hs-overlay="#modal-historial">
                    <i class="i-tabler-x text-lg"></i>
                </button>
            </div>
            <div class="p-8 custom-scrollbar max-h-[500px] overflow-y-auto bg-gray-50/30">
                <!-- Timeline Container -->
                <div id="historialContainer"
                    class="space-y-8 relative before:absolute before:inset-0 before:ml-3 before:-translate-x-px before:h-full before:w-0.5 before:bg-gradient-to-b before:from-primary/20 before:via-gray-100 before:to-transparent">
                    <!-- Items se insertan vía JS -->
                </div>

                <!-- Empty State -->
                <div id="noDataMessage" class="hidden text-center py-10 animate-fade-in">
                    <div
                        class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-white shadow-inner">
                        <i class="i-tabler-school-off text-3xl text-gray-300"></i>
                    </div>
                    <h4 class="text-gray-400 font-bold uppercase text-xs tracking-widest">Sin registros encontrados
                    </h4>
                    <p class="text-gray-400 text-[11px] mt-1">Este colaborador aún no cuenta con capacitaciones
                        registradas.</p>
                </div>
            </div>
            <div class="flex justify-end items-center gap-x-2 py-4 px-6 border-t border-gray-100 bg-white/50">
                <button type="button"
                    class="py-2.5 px-6 inline-flex justify-center items-center gap-2 rounded-xl border border-transparent font-black bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-all text-xs uppercase tracking-widest"
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
<script>window.cursoAutoSelectId = '{{ $cursoId ?? '' }}';</script>
@endsection

@vite(['resources/js/functions/capacitacion/consulta_matriculas.js'])