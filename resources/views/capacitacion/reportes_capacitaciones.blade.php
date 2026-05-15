@extends('layouts.vertical', ['title' => 'Reportes de Capacitaciones'])

@section('css')
<style>
[x-cloak] {
    display: none !important;
}

.card-hover {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-hover:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.15);
}
</style>
@endsection

@section('content')
<!-- Perdón si el código está en este estado. Lo separaría utilizando componentes pero no hay tiempo y tampoco paciencia :P -->

<div x-data="reportesApp" class="px-6 py-6">
    {{-- Header --}}
    <div
        class="relative overflow-hidden rounded-2xl border border-default-200/60 bg-gradient-to-br from-white via-default-50/50 to-primary/5 shadow-sm mb-6">
        <!-- Decorative elements -->
        <div class="absolute top-0 right-0 w-72 h-72 bg-primary/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-1/3 w-48 h-48 bg-amber-500/5 rounded-full blur-2xl"></div>
        <div class="absolute top-1/2 right-1/4 w-32 h-32 bg-green-500/5 rounded-full blur-xl"></div>

        <!-- Grid pattern overlay -->
        <div class="absolute inset-0 opacity-[0.015]"
            style="background-image: radial-gradient(circle, currentColor 1px, transparent 1px); background-size: 24px 24px;">
        </div>

        <div class="relative p-8">
            <div class="flex items-start justify-between gap-6">
                <div class="flex-1">
                    <!-- Badge -->
                    <div
                        class="inline-flex items-center gap-2 w-fit px-3 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-semibold">
                        <div class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></div>
                        <i class="ti ti-report text-sm"></i>
                        Módulo de reportes de capacitaciones
                    </div>

                    <!-- Title + Description -->
                    <h1 class="text-3xl font-bold tracking-tight text-default-900 mt-4">
                        Centro de Reportes
                    </h1>
                    <p class="mt-3 text-sm leading-7 text-default-600 max-w-3xl">
                        Genere reportes detallados sobre el estado de las capacitaciones del personal.
                        Filtre, ordene y exporte la información a Excel o PDF según sus necesidades.
                    </p>

                    <!-- Quick stats -->
                    <div class="flex items-center gap-6 mt-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                                <i class="ti ti-file-report text-lg text-primary"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-default-800">3 tipos</p>
                                <p class="text-[10px] text-default-500">de reporte</p>
                            </div>
                        </div>
                        <div class="w-px h-10 bg-default-200"></div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center">
                                <i class="ti ti-file-spreadsheet text-lg text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-default-800">Excel</p>
                                <p class="text-[10px] text-default-500">& PDF</p>
                            </div>
                        </div>
                        <div class="w-px h-10 bg-default-200"></div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center">
                                <i class="ti ti-filter text-lg text-amber-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-default-800">Filtros</p>
                                <p class="text-[10px] text-default-500">avanzados</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right side: decorative icon -->
                <div class="hidden xl:flex flex-col items-center justify-center shrink-0">
                    <div
                        class="w-20 h-20 rounded-2xl bg-gradient-to-br from-primary/10 to-primary/5 flex items-center justify-center">
                        <i class="ti ti-chart-bar text-4xl text-primary/60"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        {{-- Card 1: Reporte por capacitación --}}
        <div
            class="card-hover group relative overflow-hidden rounded-2xl border border-default-200/60 bg-white shadow-sm">
            <div class="relative p-6 flex flex-col h-full">
                <!-- Icon + Badge -->
                <div class="flex items-start justify-between mb-5">
                    <div
                        class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary to-blue-400 flex items-center justify-center shadow-md shadow-primary/20">
                        <i class="ti ti-file-report text-xl text-white"></i>
                    </div>
                    <span
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-default-100 text-default-600 text-[10px] font-bold uppercase tracking-wider">
                        <i class="ti ti-users text-[9px]"></i>
                        Personal
                    </span>
                </div>

                <!-- Title + Description -->
                <h3 class="text-base font-bold text-default-900 mb-2">Reporte por capacitación</h3>
                <p class="text-sm text-default-500 leading-relaxed mb-4">
                    Identifique al personal según su estado en una capacitación específica.
                </p>

                <!-- Features -->
                <div class="space-y-2 mb-5 flex-grow">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-primary shrink-0"></i>
                        <span class="text-xs text-default-600">Filtre por sistema, área, sucursal y curso</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-primary shrink-0"></i>
                        <span class="text-xs text-default-600">Estado: Pendiente, Aprobado o Desaprobado</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-primary shrink-0"></i>
                        <span class="text-xs text-default-600">Exporte a Excel y PDF</span>
                    </div>
                </div>

                <!-- Button -->
                <button @click="abrirModalReporte()"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 transition-colors w-full">
                    <i class="ti ti-arrow-right text-sm"></i>
                    Generar reporte
                </button>
            </div>
        </div>

        {{-- Card 2: Reporte de capacitaciones --}}
        <div
            class="card-hover group relative overflow-hidden rounded-2xl border border-default-200/60 bg-white shadow-sm">
            <div class="relative p-6 flex flex-col h-full">
                <!-- Icon + Badge -->
                <div class="flex items-start justify-between mb-5">
                    <div
                        class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-amber-400 flex items-center justify-center shadow-md shadow-amber-500/20">
                        <i class="ti ti-books text-xl text-white"></i>
                    </div>
                    <span
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 text-amber-600 text-[10px] font-bold uppercase tracking-wider">
                        <i class="ti ti-calendar text-[9px]"></i>
                        Cursos
                    </span>
                </div>

                <!-- Title + Description -->
                <h3 class="text-base font-bold text-default-900 mb-2">Reporte de capacitaciones</h3>
                <p class="text-sm text-default-500 leading-relaxed mb-4">
                    Obtenga el listado completo de cursos con fechas, responsables y descripciones.
                </p>

                <!-- Features -->
                <div class="space-y-2 mb-5 flex-grow">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-amber-500 shrink-0"></i>
                        <span class="text-xs text-default-600">Filtre por sistema, área y período</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-amber-500 shrink-0"></i>
                        <span class="text-xs text-default-600">Detalle de responsable y descripción</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-amber-500 shrink-0"></i>
                        <span class="text-xs text-default-600">Exporte a Excel y PDF</span>
                    </div>
                </div>

                <!-- Button -->
                <button @click="abrirModalCursosArea()"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-amber-500 text-white text-sm font-semibold hover:bg-amber-600 transition-colors w-full">
                    <i class="ti ti-arrow-right text-sm"></i>
                    Generar reporte
                </button>
            </div>
        </div>

        {{-- Card 3: Récord por personal --}}
        <div
            class="card-hover group relative overflow-hidden rounded-2xl border border-default-200/60 bg-white shadow-sm">
            <div class="relative p-6 flex flex-col h-full">
                <!-- Icon + Badge -->
                <div class="flex items-start justify-between mb-5">
                    <div
                        class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-400 flex items-center justify-center shadow-md shadow-green-500/20">
                        <i class="ti ti-history text-xl text-white"></i>
                    </div>
                    <span
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-green-50 text-green-600 text-[10px] font-bold uppercase tracking-wider">
                        <i class="ti ti-user text-[9px]"></i>
                        Historial
                    </span>
                </div>

                <!-- Title + Description -->
                <h3 class="text-base font-bold text-default-900 mb-2">Récord de capacitaciones por personal</h3>
                <p class="text-sm text-default-500 leading-relaxed mb-4">
                    Consulte el historial completo de cursos de un colaborador específico.
                </p>

                <!-- Features -->
                <div class="space-y-2 mb-5 flex-grow">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-green-500 shrink-0"></i>
                        <span class="text-xs text-default-600">Búsqueda por nombre o DNI</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-green-500 shrink-0"></i>
                        <span class="text-xs text-default-600">Filtre por sistema, área y estado</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-green-500 shrink-0"></i>
                        <span class="text-xs text-default-600">Fechas de matrícula y último acceso</span>
                    </div>
                </div>

                <!-- Button -->
                <button @click="abrirModalRecordPersonal()"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-green-500 text-white text-sm font-semibold hover:bg-green-600 transition-colors w-full">
                    <i class="ti ti-arrow-right text-sm"></i>
                    Generar reporte
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Reporte Por Capacitacion --}}
    <div id="modal-reporte-por-capacitacion" x-data="modalReportePorCapacitacion" x-show="open" x-cloak
        @keydown.escape.window="cerrar()"
        class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="background: rgba(36,39,70,0.45);">

        <div :class="view === 'personal' ? 'max-w-6xl' : 'max-w-xl'"
            class="flex flex-col w-full bg-white rounded-2xl shadow-2xl shadow-primary/10 border border-default-200 transition-all duration-300"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4">

            <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-4">
                <div class="flex items-center gap-3.5">
                    <div
                        class="relative flex-shrink-0 w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white shadow-sm">
                        <i class="ti ti-file-report text-lg"></i>
                    </div>
                    <div>
                        <div x-show="view === 'filters'">
                            <h3 class="text-[15px] font-semibold text-default-900 leading-tight">
                                Reporte por capacitación
                            </h3>
                            <p class="text-xs text-default-500 mt-0.5">Filtre por sistema, área, sucursal y curso</p>
                        </div>
                        <div x-show="view === 'personal'">
                            <h3 class="text-[15px] font-semibold text-default-900 leading-tight">
                                Personal encontrado
                                <span x-show="selectedEstado === 'PENDIENTE'">
                                    sin iniciar la capacitación de <span x-text="nombreCurso"></span>
                                </span>
                            </h3>
                            <p class="text-xs text-default-500 mt-0.5">Resultado del reporte generado</p>
                        </div>
                    </div>
                </div>
                <button type="button" @click="cerrar()"
                    class="flex-shrink-0 w-7 h-7 inline-flex items-center justify-center rounded-lg text-default-400 hover:text-default-700 hover:bg-default-100 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors cursor-pointer">
                    <i class="ti ti-x text-base"></i>
                </button>
            </div>

            <div x-show="view === 'filters'" class="px-6 pb-1 space-y-4">
                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Sistema de gestión <span class="text-danger">*</span>
                    </label>
                    <select x-model="selectedSistema" @change="cargarAreas($event.target.value)"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                        <option value=""
                            x-text="loadingSistemas ? 'Cargando sistemas...' : (sistemas.length === 0 ? 'Sin sistemas disponibles' : 'Seleccione')">
                        </option>
                        <template x-for="option in sistemas" :key="option.codigo">
                            <option :value="option.codigo" x-text="option.descripcion"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Área responsable <span class="text-danger">*</span>
                    </label>
                    <select x-model="selectedArea" @change="cargarCursos($event.target.value)"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                        <option value=""
                            x-text="loadingAreas ? 'Cargando áreas...' : (selectedSistema && areas.length === 0 ? 'Sin áreas disponibles' : 'Seleccione')">
                        </option>
                        <template x-for="option in areas" :key="option.codModdle">
                            <option :value="option.codModdle" x-text="option.Area"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Sucursal <span class="text-danger">*</span>
                    </label>
                    <select x-model="selectedSucursal"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                        <option value=""
                            x-text="loadingSucursales ? 'Cargando sucursales...' : (sucursales.length === 0 ? 'Sin sucursales disponibles' : 'Seleccione Sucursal')">
                        </option>
                        <template x-for="option in sucursales" :key="option.codigo">
                            <option :value="option.codigo" x-text="option.sucursal"></option>
                        </template>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-default-700 mb-1.5 block">
                            Fecha de inicio <span class="text-default-400 font-normal">(opcional)</span>
                        </label>
                        <select x-model="selectedFechaInicio" @change="filtrarCursosPorFecha()"
                            class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                            <option value=""
                                x-text="!selectedArea ? 'Seleccione' : (fechasInicio.length === 0 ? 'Sin fechas disponibles' : 'Todas las fechas')">
                            </option>
                            <template x-for="option in fechasInicio" :key="option.fecha">
                                <option :value="option.fecha" x-text="option.fecha"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-default-700 mb-1.5 block">
                            Fecha de fin <span class="text-default-400 font-normal">(opcional)</span>
                        </label>
                        <select x-model="selectedFechaFin" @change="filtrarCursosPorFecha()"
                            class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                            <option value=""
                                x-text="!selectedArea ? 'Seleccione' : (fechasFin.length === 0 ? 'Sin fechas disponibles' : 'Todas las fechas')">
                            </option>
                            <template x-for="option in fechasFin" :key="option.fecha">
                                <option :value="option.fecha" x-text="option.fecha"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Curso de capacitación <span class="text-danger">*</span>
                    </label>
                    <select x-model="selectedCurso"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                        <option value=""
                            x-text="!selectedArea ? 'Seleccione Curso' : (loadingCursos ? 'Cargando cursos...' : (cursos.length > 0 ? cursos.length + ' resultado(s)' : 'Sin cursos disponibles'))">
                        </option>
                        <template x-for="option in cursos" :key="option.id">
                            <option :value="option.id" x-text="option.fullname"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Estado de alumnos <span class="text-danger">*</span>
                    </label>
                    <select x-model="selectedEstado" disabled
                        class="w-full h-9 px-3 text-sm cursor-not-allowed bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                        <option value="PENDIENTE">PENDIENTE</option>
                        <option value="APROBADO">APROBADO</option>
                        <option value="DESAPROBADO">DESAPROBADO</option>
                    </select>
                </div>
            </div>

            <div x-show="view === 'personal'" class="px-6 pb-2">

                <template x-if="loadingPersonal">
                    <div class="flex items-center justify-center py-10">
                        <svg class="animate-spin h-6 w-6 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                            </path>
                        </svg>
                    </div>
                </template>

                <template x-if="!loadingPersonal && personal.length === 0">
                    <div class="text-center py-10 text-default-500 text-sm">
                        No se encontró personal disponible.
                    </div>
                </template>

                <template x-if="!loadingPersonal && personal.length > 0">
                    <div class="flex flex-col max-h-[500px] border border-default-200 rounded-xl">
                        <div class="flex-1 overflow-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-default-50 border-b border-default-200 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-default-700 w-14">#</th>
                                        <th @click="ordenar('CodigoPers')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors">
                                            Código Pers.
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'CodigoPers' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'CodigoPers' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('NombreCompleto')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors">
                                            Nombre completo
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'NombreCompleto' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'NombreCompleto' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('DNI')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors">
                                            DNI
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'DNI' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'DNI' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('TipoTrabajador')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors">
                                            Tipo trabajador
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'TipoTrabajador' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'TipoTrabajador' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('Cargo')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors">
                                            Cargo
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'Cargo' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'Cargo' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('Estado')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors">
                                            Estado
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'Estado' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'Estado' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-default-100 bg-white">
                                    <template x-for="(persona, index) in personalPaginado" :key="index">
                                        <tr class="hover:bg-default-50 transition-colors">
                                            <td class="px-4 py-3 text-default-500"
                                                x-text="((currentPage - 1) * perPage) + index + 1"></td>
                                            <td class="px-4 py-3 text-default-800 font-medium whitespace-nowrap"
                                                x-text="persona.CodigoPers"></td>
                                            <td class="px-4 py-3 text-default-800 font-medium whitespace-nowrap"
                                                x-text="persona.NombreCompleto"></td>
                                            <td class="px-4 py-3 text-default-600 font-mono" x-text="persona.DNI"></td>
                                            <td class="px-4 py-3 text-default-600 whitespace-nowrap"
                                                x-text="persona.TipoTrabajador"></td>
                                            <td class="px-4 py-3 text-default-600 whitespace-nowrap"
                                                x-text="persona.Cargo"></td>
                                            <td class="px-4 py-3">
                                                <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="{
                                                        'bg-amber-50 text-amber-700 border border-amber-200': persona.Estado === 'PENDIENTE',
                                                        'bg-green-50 text-green-700 border border-green-200': persona.Estado === 'APROBADO',
                                                        'bg-red-50 text-red-700 border border-red-200': persona.Estado === 'DESAPROBADO'
                                                    }" x-text="persona.Estado">
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div x-show="personal.length > 0"
                            class="flex items-center justify-between px-4 py-3 border-t border-default-200 bg-default-50">
                            <div class="text-sm text-default-500">
                                Mostrando
                                <span x-text="((currentPage - 1) * perPage) + 1"></span>
                                -
                                <span x-text="Math.min(currentPage * perPage, personal.length)"></span>
                                de
                                <span x-text="personal.length"></span>
                                registros
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="currentPage--" :disabled="currentPage === 1"
                                    class="px-3 h-8 rounded-lg border border-default-200 bg-white text-sm hover:bg-default-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Anterior
                                </button>
                                <div class="px-3 text-sm text-default-600">
                                    Página
                                    <span x-text="currentPage"></span>
                                    de
                                    <span x-text="totalPages"></span>
                                </div>
                                <button type="button" @click="currentPage++" :disabled="currentPage >= totalPages"
                                    class="px-3 h-8 rounded-lg border border-default-200 bg-white text-sm hover:bg-default-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Siguiente
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 mt-2 border-t border-default-100">
                <template x-if="view === 'filters'">
                    <div class="flex items-center justify-end gap-2 w-full">
                        <button type="button" @click="cerrar()"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-default-600 bg-default-100 hover:bg-default-200 hover:text-default-800 transition-all cursor-pointer">
                            Cancelar
                        </button>
                        <button type="button" @click="obtenerPersonal()"
                            :disabled="loadingPersonal || !selectedCurso || !selectedSucursal"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-primary hover:bg-primary/90 shadow-sm shadow-primary/20 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="ti ti-arrow-right text-sm"></i>
                            <span x-text="loadingPersonal ? 'Cargando...' : 'Obtener personal'"></span>
                        </button>
                    </div>
                </template>

                <template x-if="view === 'personal'">
                    <div class="flex items-center justify-between w-full">
                        <button type="button" @click="volverAFiltros()"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-default-600 bg-default-100 hover:bg-default-200 transition-all cursor-pointer">
                            <i class="ti ti-arrow-left text-sm"></i>
                            Atrás
                        </button>
                        <div x-show="personal.length > 0" class="flex items-center gap-2">
                            <button type="button" @click="exportarExcel()"
                                class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-green-600 hover:bg-green-700 shadow-sm transition-all cursor-pointer">
                                <i class="ti ti-file-spreadsheet text-sm"></i>
                                Exportar Excel
                            </button>
                            <button type="button" @click="exportarPDF()"
                                class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 shadow-sm transition-all cursor-pointer">
                                <i class="ti ti-file-type-pdf text-sm"></i>
                                Exportar PDF
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Modal Reporte de Capacitaciones --}}
    <div id="modal-reporte-de-capacitaciones" x-data="modalReporteDeCapacitaciones" x-show="open" x-cloak
        @keydown.escape.window="cerrar()"
        class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="background: rgba(36,39,70,0.45);">

        <div :class="view === 'cursos' ? 'max-w-6xl' : 'max-w-xl'"
            class="flex flex-col w-full bg-white rounded-2xl shadow-2xl shadow-primary/10 border border-default-200 transition-all duration-300"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4">

            <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-4">
                <div class="flex items-center gap-3.5">
                    <div
                        class="relative flex-shrink-0 w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white shadow-sm">
                        <i class="ti ti-books text-lg"></i>
                    </div>
                    <div>
                        <div x-show="view === 'filters'">
                            <h3 class="text-[15px] font-semibold text-default-900 leading-tight">
                                Reporte de capacitaciones
                            </h3>
                            <p class="text-xs text-default-500 mt-0.5">Filtre por sistema, área y período</p>
                        </div>
                        <div x-show="view === 'cursos'">
                            <h3 class="text-[15px] font-semibold text-default-900 leading-tight">
                                Cursos encontrados
                            </h3>
                            <p class="text-xs text-default-500 mt-0.5">Listado según los filtros aplicados</p>
                        </div>
                    </div>
                </div>
                <button type="button" @click="cerrar()"
                    class="flex-shrink-0 w-7 h-7 inline-flex items-center justify-center rounded-lg text-default-400 hover:text-default-700 hover:bg-default-100 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors cursor-pointer">
                    <i class="ti ti-x text-base"></i>
                </button>
            </div>

            <div x-show="view === 'filters'" class="px-6 pb-1 space-y-4">
                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Sistema de gestión <span class="text-danger">*</span>
                    </label>
                    <select x-model="selectedSistema" @change="cargarAreas($event.target.value)"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                        <option value=""
                            x-text="loadingSistemas ? 'Cargando sistemas...' : (sistemas.length === 0 ? 'Sin sistemas disponibles' : 'Seleccione')">
                        </option>
                        <template x-for="option in sistemas" :key="option.codigo">
                            <option :value="option.codigo" x-text="option.descripcion"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Área responsable <span class="text-danger">*</span>
                    </label>
                    <select x-model="selectedArea"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                        <option value=""
                            x-text="loadingAreas ? 'Cargando áreas...' : (selectedSistema && areas.length === 0 ? 'Sin áreas disponibles' : 'Seleccione')">
                        </option>
                        <template x-for="option in areas" :key="option.codModdle">
                            <option :value="option.codModdle" x-text="option.Area"></option>
                        </template>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-default-700 mb-1.5 block">
                            Fecha de inicio <span class="text-default-400 font-normal">(opcional)</span>
                        </label>
                        <input type="date" x-model="selectedFechaInicio"
                            class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-default-700 mb-1.5 block">
                            Fecha de fin <span class="text-default-400 font-normal">(opcional)</span>
                        </label>
                        <input type="date" x-model="selectedFechaFin"
                            class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>
                </div>
            </div>

            <div x-show="view === 'cursos'" class="px-6 pb-2">

                <template x-if="loadingCursos">
                    <div class="flex items-center justify-center py-10">
                        <svg class="animate-spin h-6 w-6 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                            </path>
                        </svg>
                    </div>
                </template>

                <template x-if="!loadingCursos && cursosFilas.length === 0">
                    <div class="text-center py-10 text-default-500 text-sm">
                        No se encontraron cursos con los criterios indicados.
                    </div>
                </template>

                <template x-if="!loadingCursos && cursosFilas.length > 0">
                    <div class="flex flex-col max-h-[500px] border border-default-200 rounded-xl">
                        <div class="flex-1 overflow-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-default-50 border-b border-default-200 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-default-700 w-14">#</th>
                                        <th @click="ordenar('SistemaGestion')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors">
                                            Sistema de gestión
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'SistemaGestion' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'SistemaGestion' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('Area')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors">
                                            Área
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'Area' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'Area' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('NombreCurso')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors min-w-[12rem]">
                                            Nombre de curso
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'NombreCurso' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'NombreCurso' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('FechaInicio')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors whitespace-nowrap">
                                            Fecha de inicio
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'FechaInicio' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'FechaInicio' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('FechaFin')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors whitespace-nowrap">
                                            Fecha de fin
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'FechaFin' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'FechaFin' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('Responsable')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors min-w-[10rem]">
                                            Responsable
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'Responsable' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'Responsable' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('Descripcion')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors min-w-[10rem]">
                                            Descripción
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'Descripcion' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'Descripcion' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-default-100 bg-white">
                                    <template x-for="(fila, index) in cursosPaginado" :key="fila.id">
                                        <tr class="hover:bg-default-50 transition-colors">
                                            <td class="px-4 py-3 text-default-500"
                                                x-text="((currentPage - 1) * perPage) + index + 1"></td>
                                            <td class="px-4 py-3 text-default-800" x-text="fila.SistemaGestion"></td>
                                            <td class="px-4 py-3 text-default-800" x-text="fila.Area"></td>
                                            <td class="px-4 py-3 text-default-800 font-medium"
                                                x-text="fila.NombreCurso"></td>
                                            <td class="px-4 py-3 text-default-600 whitespace-nowrap"
                                                x-text="fila.FechaInicio"></td>
                                            <td class="px-4 py-3 text-default-600 whitespace-nowrap"
                                                x-text="fila.FechaFin"></td>
                                            <td class="px-4 py-3 text-default-800 max-w-[14rem]">
                                                <span class="line-clamp-2" x-text="fila.Responsable"
                                                    :title="fila.Responsable"></span>
                                            </td>
                                            <td class="px-4 py-3 text-default-600 max-w-xs">
                                                <span class="line-clamp-2" x-text="fila.Descripcion"
                                                    :title="fila.Descripcion"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div x-show="cursosFilas.length > 0"
                            class="flex items-center justify-between px-4 py-3 border-t border-default-200 bg-default-50">
                            <div class="text-sm text-default-500">
                                Mostrando
                                <span x-text="((currentPage - 1) * perPage) + 1"></span>
                                -
                                <span x-text="Math.min(currentPage * perPage, cursosFilas.length)"></span>
                                de
                                <span x-text="cursosFilas.length"></span>
                                registros
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="currentPage--" :disabled="currentPage === 1"
                                    class="px-3 h-8 rounded-lg border border-default-200 bg-white text-sm hover:bg-default-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Anterior
                                </button>
                                <div class="px-3 text-sm text-default-600">
                                    Página
                                    <span x-text="currentPage"></span>
                                    de
                                    <span x-text="totalPagesCursos"></span>
                                </div>
                                <button type="button" @click="currentPage++" :disabled="currentPage >= totalPagesCursos"
                                    class="px-3 h-8 rounded-lg border border-default-200 bg-white text-sm hover:bg-default-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Siguiente
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 mt-2 border-t border-default-100">
                <template x-if="view === 'filters'">
                    <div class="flex items-center justify-end gap-2 w-full">
                        <button type="button" @click="cerrar()"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-default-600 bg-default-100 hover:bg-default-200 hover:text-default-800 transition-all cursor-pointer">
                            Cancelar
                        </button>
                        <button type="button" @click="obtenerCursos()" :disabled="loadingCursos || !selectedArea"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-primary hover:bg-primary/90 shadow-sm shadow-primary/20 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="ti ti-arrow-right text-sm"></i>
                            <span x-text="loadingCursos ? 'Cargando...' : 'Obtener cursos'"></span>
                        </button>
                    </div>
                </template>

                <template x-if="view === 'cursos'">
                    <div class="flex items-center justify-between w-full flex-wrap gap-2">
                        <button type="button" @click="volverAFiltros()"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-default-600 bg-default-100 hover:bg-default-200 transition-all cursor-pointer">
                            <i class="ti ti-arrow-left text-sm"></i>
                            Atrás
                        </button>
                        <div x-show="cursosFilas.length > 0 && !loadingCursos" class="flex items-center gap-2">
                            <button type="button" @click="exportarExcelHistorialCursos()"
                                class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-green-600 hover:bg-green-700 shadow-sm transition-all cursor-pointer">
                                <i class="ti ti-file-spreadsheet text-sm"></i>
                                Exportar Excel
                            </button>
                            <button type="button" @click="exportarPDFHistorialCursos()"
                                class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 shadow-sm transition-all cursor-pointer">
                                <i class="ti ti-file-type-pdf text-sm"></i>
                                Exportar PDF
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Modal Récord de Capacitaciones por Personal --}}
    <div id="modal-reporte-record-de-capac-por-personal" x-data="modalReporteRecordDeCapacPorPersonal" x-show="open"
        x-cloak @keydown.escape.window="cerrar()"
        class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="background: rgba(36,39,70,0.45);">

        <div :class="view === 'resultados' ? 'max-w-6xl' : 'max-w-4xl'"
            class="flex flex-col w-full bg-white rounded-2xl shadow-2xl shadow-primary/10 border border-default-200 transition-all duration-300"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4">

            <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-4">
                <div class="flex items-center gap-3.5">
                    <div
                        class="relative flex-shrink-0 w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white shadow-sm">
                        <i class="ti ti-history text-lg"></i>
                    </div>
                    <div>
                        <div x-show="view === 'filters'">
                            <h3 class="text-[15px] font-semibold text-default-900 leading-tight">
                                Récord de capacitaciones por personal
                            </h3>
                            <p class="text-xs text-default-500 mt-0.5">Filtre por sistema, área, sucursal y personal</p>
                        </div>
                        <div x-show="view === 'resultados'">
                            <h3 class="text-[15px] font-semibold text-default-900 leading-tight">
                                Récord encontrado
                                <span x-text="'de ' + nombrePersonal"></span>
                            </h3>
                            <p class="text-xs text-default-500 mt-0.5">Cursos del personal según filtros aplicados</p>
                        </div>
                    </div>
                </div>
                <button type="button" @click="cerrar()"
                    class="flex-shrink-0 w-7 h-7 inline-flex items-center justify-center rounded-lg text-default-400 hover:text-default-700 hover:bg-default-100 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors cursor-pointer">
                    <i class="ti ti-x text-base"></i>
                </button>
            </div>

            <div x-show="view === 'filters'" class="px-6 pb-1">
                <div class="grid grid-cols-2 gap-6">
                    <!-- Columna izquierda: Filtros de cursos -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="flex-shrink-0 w-1 h-4 bg-primary rounded-full"></div>
                            <h4 class="text-xs font-semibold text-default-700 uppercase tracking-wide">Filtros de
                                capacitaciones por área</h4>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-default-700 mb-1.5 block">
                                Sistema de gestión <span class="text-danger">*</span>
                            </label>
                            <select x-model="selectedSistema" @change="cargarAreas($event.target.value)"
                                class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                <option value=""
                                    x-text="loadingSistemas ? 'Cargando sistemas...' : (sistemas.length === 0 ? 'Sin sistemas disponibles' : 'Seleccione')">
                                </option>
                                <template x-for="option in sistemas" :key="option.codigo">
                                    <option :value="option.codigo" x-text="option.descripcion"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-default-700 mb-1.5 block">
                                Área responsable <span class="text-danger">*</span>
                            </label>
                            <select x-model="selectedArea" @change="cargarCursos($event.target.value)"
                                class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                <option value=""
                                    x-text="loadingAreas ? 'Cargando áreas...' : (selectedSistema && areas.length === 0 ? 'Sin áreas disponibles' : 'Seleccione')">
                                </option>
                                <template x-for="option in areas" :key="option.codModdle">
                                    <option :value="option.codModdle" x-text="option.Area"></option>
                                </template>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-medium text-default-700 mb-1.5 block">
                                    Fecha de inicio <span class="text-default-400 font-normal">(opcional)</span>
                                </label>
                                <select x-model="selectedFechaInicio" @change="filtrarCursosPorFecha()"
                                    class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                    <option value=""
                                        x-text="!selectedArea ? 'Seleccione' : (fechasInicio.length === 0 ? 'Sin fechas' : 'Todas')">
                                    </option>
                                    <template x-for="option in fechasInicio" :key="option.fecha">
                                        <option :value="option.fecha" x-text="option.fecha"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-default-700 mb-1.5 block">
                                    Fecha de fin <span class="text-default-400 font-normal">(opcional)</span>
                                </label>
                                <select x-model="selectedFechaFin" @change="filtrarCursosPorFecha()"
                                    class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                    <option value=""
                                        x-text="!selectedArea ? 'Seleccione' : (fechasFin.length === 0 ? 'Sin fechas' : 'Todas')">
                                    </option>
                                    <template x-for="option in fechasFin" :key="option.fecha">
                                        <option :value="option.fecha" x-text="option.fecha"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Columna derecha: Filtros de personal -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="flex-shrink-0 w-1 h-4 bg-primary rounded-full"></div>
                            <h4 class="text-xs font-semibold text-default-700 uppercase tracking-wide">Filtros de
                                personal por sucursal</h4>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-default-700 mb-1.5 block">
                                Sucursal <span class="text-danger">*</span>
                            </label>
                            <select x-model="selectedSucursal" @change="cargarPersonalPorSucursal()"
                                class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                <option value=""
                                    x-text="loadingSucursales ? 'Cargando sucursales...' : (sucursales.length === 0 ? 'Sin sucursales disponibles' : 'Seleccione Sucursal')">
                                </option>
                                <template x-for="option in sucursales" :key="option.codigo">
                                    <option :value="option.codigo" x-text="option.sucursal"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-default-700 mb-1.5 block">
                                Personal <span class="text-danger">*</span>
                            </label>
                            <input type="text" x-model="searchPersonal" placeholder="Buscar por nombre o DNI..."
                                @input="selectedPersonal = ''"
                                class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all mb-2"
                                x-show="selectedSucursal && !loadingPersonal && personalOptions.length > 0">
                            <select x-model="selectedPersonal"
                                class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                <option value=""
                                    x-text="!selectedSucursal ? 'Seleccione sucursal primero' : (loadingPersonal ? 'Cargando personal...' : (filteredPersonalOptions.length === 0 ? 'Sin personal disponible' : filteredPersonalOptions.length + ' resultado(s)'))">
                                </option>
                                <template x-for="option in filteredPersonalOptions" :key="option.codigo">
                                    <option :value="option.codigo"
                                        x-text="option.nombre_completo + ' (' + option.dni + ')'"
                                        x-show="!searchPersonal || filteredPersonalOptions.length > 0">
                                    </option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-default-700 mb-1.5 block">
                                Estado <span class="text-danger">*</span>
                            </label>
                            <select x-model="selectedEstado" disabled
                                class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-not-allowed">
                                <option value="PENDIENTE">PENDIENTE</option>
                                <option value="APROBADO">APROBADO</option>
                                <option value="DESAPROBADO">DESAPROBADO</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="view === 'resultados'" class="px-6 pb-2">
                <template x-if="loadingRecord">
                    <div class="flex items-center justify-center py-10">
                        <svg class="animate-spin h-6 w-6 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                </template>

                <template x-if="!loadingRecord && personalRecord.length === 0">
                    <div class="text-center py-10 text-default-500 text-sm">
                        No se encontraron cursos para el personal con los filtros indicados.
                    </div>
                </template>

                <template x-if="!loadingRecord && personalRecord.length > 0">
                    <div class="flex flex-col max-h-[500px] border border-default-200 rounded-xl">
                        <div class="flex-1 overflow-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-default-50 border-b border-default-200 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-default-700 w-14">#</th>
                                        <th @click="ordenar('area')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors">
                                            Area
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'area' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'area' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('nombre_curso')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors min-w-[14rem]">
                                            Nombre de capacitacion
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'nombre_curso' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'nombre_curso' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('estado')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors">
                                            Estado
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'estado' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'estado' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-default-100 bg-white">
                                    <template x-for="(item, index) in recordPaginado" :key="index">
                                        <tr class="hover:bg-default-50 transition-colors">
                                            <td class="px-4 py-3 text-default-500"
                                                x-text="((currentPage - 1) * perPage) + index + 1"></td>
                                            <td class="px-4 py-3 text-default-800" x-text="item.area"></td>
                                            <td class="px-4 py-3 text-default-800 font-medium"
                                                x-text="item.nombre_curso"></td>
                                            <td class="px-4 py-3">
                                                <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="{
                                                        'bg-amber-50 text-amber-700 border border-amber-200': item.estado === 'PENDIENTE',
                                                        'bg-green-50 text-green-700 border border-green-200': item.estado === 'APROBADO',
                                                        'bg-red-50 text-red-700 border border-red-200': item.estado === 'DESAPROBADO',
                                                        'bg-blue-50 text-blue-700 border border-blue-200': item.estado === 'EN_CURSO'
                                                    }" x-text="item.estado">
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div x-show="personalRecord.length > 0"
                            class="flex items-center justify-between px-4 py-3 border-t border-default-200 bg-default-50">
                            <div class="text-sm text-default-500">
                                Mostrando
                                <span x-text="((currentPage - 1) * perPage) + 1"></span>
                                -
                                <span x-text="Math.min(currentPage * perPage, personalRecord.length)"></span>
                                de
                                <span x-text="personalRecord.length"></span>
                                registros
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="currentPage--" :disabled="currentPage === 1"
                                    class="px-3 h-8 rounded-lg border border-default-200 bg-white text-sm hover:bg-default-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Anterior
                                </button>
                                <div class="px-3 text-sm text-default-600">
                                    Página <span x-text="currentPage"></span> de <span x-text="totalPages"></span>
                                </div>
                                <button type="button" @click="currentPage++" :disabled="currentPage >= totalPages"
                                    class="px-3 h-8 rounded-lg border border-default-200 bg-white text-sm hover:bg-default-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Siguiente
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 mt-2 border-t border-default-100">
                <template x-if="view === 'filters'">
                    <div class="flex items-center justify-end gap-2 w-full">
                        <button type="button" @click="cerrar()"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-default-600 bg-default-100 hover:bg-default-200 hover:text-default-800 transition-all cursor-pointer">
                            Cancelar
                        </button>
                        <button type="button" @click="obtenerRecord()" :disabled="loadingRecord || !selectedPersonal"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-primary hover:bg-primary/90 shadow-sm shadow-primary/20 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="ti ti-arrow-right text-sm"></i>
                            <span x-text="loadingRecord ? 'Cargando...' : 'Obtener récord'"></span>
                        </button>
                    </div>
                </template>

                <template x-if="view === 'resultados'">
                    <div class="flex items-center justify-between w-full flex-wrap gap-2">
                        <button type="button" @click="volverAFiltros()"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-default-600 bg-default-100 hover:bg-default-200 transition-all cursor-pointer">
                            <i class="ti ti-arrow-left text-sm"></i>
                            Atrás
                        </button>
                        <div x-show="personalRecord.length > 0 && !loadingRecord" class="flex items-center gap-2">
                            <button type="button" @click="exportarExcelRecord()"
                                class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-green-600 hover:bg-green-700 shadow-sm transition-all cursor-pointer">
                                <i class="ti ti-file-spreadsheet text-sm"></i>
                                Exportar Excel
                            </button>
                            <button type="button" @click="exportarPDFRecord()"
                                class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 shadow-sm transition-all cursor-pointer">
                                <i class="ti ti-file-type-pdf text-sm"></i>
                                Exportar PDF
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
@vite(['resources/js/app.js'])
@endsection