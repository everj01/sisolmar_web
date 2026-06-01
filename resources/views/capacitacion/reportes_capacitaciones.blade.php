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

.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.1) transparent;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
    height: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.15);
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.25);
}

.table-sortable th {
    user-select: none;
}

.table-row {
    transition: all 0.15s ease;
}

.table-row-even {
    background-color: rgba(0, 0, 0, 0.02);
}

.table-row:hover {
    background-color: rgba(59, 130, 246, 0.04) !important;
}

.badge-dot {
    display: inline-block;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    margin-right: 5px;
    flex-shrink: 0;
}
</style>
@endsection

@section('content')
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

                    <!-- Historial button -->
                    <div class="mt-5">
                        <button @click="abrirModalHistorial()"
                            class="inline-flex items-center gap-2.5 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 text-white text-sm font-semibold shadow-md shadow-indigo-500/20 hover:shadow-lg hover:shadow-indigo-500/30 hover:from-indigo-600 hover:to-purple-600 transition-all cursor-pointer">
                            <i class="ti ti-clock text-base"></i>
                            Ver historial de reportes
                        </button>
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
        @keydown.escape.window="cerrar()" class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        style="background: rgba(36,39,70,0.45);">

        <div :class="view === 'personal' ? 'max-w-7xl' : 'max-w-xl'"
            class="flex flex-col w-full bg-white rounded-2xl shadow-2xl shadow-primary/10 border border-default-200 overflow-hidden transition-all duration-300"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4">

            <div class="flex justify-between items-start py-5 px-6 border-b border-default-100">
                <div class="flex items-center gap-3.5">
                    <div
                        class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white shadow-sm shrink-0">
                        <i class="ti ti-file-report text-lg"></i>
                    </div>
                    <div>
                        <div x-show="view === 'filters'">
                            <h3 class="text-[15px] font-semibold text-default-900 leading-tight">
                                Reporte por capacitación
                            </h3>
                            <p class="text-xs text-default-500 mt-0.5">Seleccione curso, sucursal (opcional) y estado del personal</p>
                        </div>
                        <div x-show="view === 'personal'">
                            <h3 class="text-[15px] font-semibold text-default-900 leading-tight">
                                Personal encontrado
                                <span x-show="selectedCurso && selectedEstado === 'PENDIENTE'">
                                    sin iniciar la capacitación de <span x-text="nombreCurso"></span>
                                </span>
                                <span x-show="!selectedCurso && selectedEstado === 'PENDIENTE'">
                                    de <span x-text="nombreCurso"></span> (sin iniciar)
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

            <div x-show="view === 'filters'" class="px-6 pt-4 pb-6 space-y-4">
                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Sucursal <span class="text-default-400 font-normal">(opcional, "Todas" para general)</span>
                    </label>
                    <select x-model="selectedSucursal"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                        <option value="" disabled
                            x-text="loadingSucursales ? 'Cargando sucursales...' : (sucursales.length === 0 ? 'Sin sucursales disponibles' : 'Seleccione sucursal')">
                        </option>
                        <option value="">Todas las sucursales</option>
                        <template x-for="option in sucursales" :key="option.codigo">
                            <option :value="option.codigo" x-text="option.sucursal"></option>
                        </template>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-default-700 mb-1.5 block">
                            Fecha de creación (desde) <span class="text-default-400 font-normal">(opcional)</span>
                        </label>
                        <input type="date" x-model="selectedFechaInicio" @input="filtrarCursosPorFecha()"
                            class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-default-700 mb-1.5 block">
                            Fecha de creación (hasta) <span class="text-default-400 font-normal">(opcional)</span>
                        </label>
                        <input type="date" x-model="selectedFechaFin" @input="filtrarCursosPorFecha()"
                            class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" />
                    </div>
                </div>

                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Curso de capacitación <span class="text-danger">*</span>
                    </label>
                    <select x-model="selectedCurso"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                        <option value="" disabled
                            x-text="loadingCursos ? 'Cargando cursos...' : (cursos.length === 0 ? 'Sin cursos disponibles' : 'Seleccione un curso')">
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
                    <select x-model="selectedEstado"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                        <option value="">Todos los estados</option>
                        <option value="Aprobado">Aprobados</option>
                        <option value="Desaprobado">Desaprobados</option>
                        <option value="Sin acceder">Sin acceder</option>
                        <option value="En curso">En curso</option>
                    </select>
                </div>
            </div>

            <div x-show="view === 'personal'" class="px-6 pb-6">

                <template x-if="loadingPersonal">
                    <div class="flex flex-col items-center justify-center py-10 text-default-400">
                        <i class="ti ti-loader animate-spin text-2xl mb-2"></i>
                        <p class="text-sm">Cargando personal...</p>
                    </div>
                </template>

                <template x-if="!loadingPersonal && personal.length === 0">
                    <div class="text-center py-10 text-default-500 text-sm">
                        No se encontró personal disponible.
                    </div>
                </template>

                <template x-if="!loadingPersonal && personal.length > 0">
                    <div class="flex flex-col max-h-[500px] border border-default-200 rounded-xl">
                        <div class="flex-1 overflow-auto custom-scrollbar">

                            <template x-if="!esReportePorCursos">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-default-50 border-b border-default-200 sticky top-0 z-10">
                                        <tr>
                                            <th class="px-5 py-3.5 text-center font-semibold text-default-700 w-14 text-xs uppercase tracking-wider">#</th>
                                            <th @click="ordenar('CodigoPers')"
                                                class="px-5 py-3.5 text-left font-semibold text-default-700 cursor-pointer hover:text-primary transition-colors text-xs uppercase tracking-wider table-sortable">
                                                <div class="inline-flex items-center gap-1.5">
                                                    Código
                                                    <span :class="sortColumn === 'CodigoPers' ? 'text-primary' : 'text-default-300'"
                                                        x-text="sortColumn === 'CodigoPers' ? (sortDirection === 'asc' ? '▲' : '▼') : '⇅'"
                                                        class="text-[10px] transition-colors"></span>
                                                </div>
                                            </th>
                                            <th @click="ordenar('NombreCompleto')"
                                                class="px-5 py-3.5 text-left font-semibold text-default-700 cursor-pointer hover:text-primary transition-colors text-xs uppercase tracking-wider table-sortable">
                                                <div class="inline-flex items-center gap-1.5">
                                                    Nombre completo
                                                    <span :class="sortColumn === 'NombreCompleto' ? 'text-primary' : 'text-default-300'"
                                                        x-text="sortColumn === 'NombreCompleto' ? (sortDirection === 'asc' ? '▲' : '▼') : '⇅'"
                                                        class="text-[10px] transition-colors"></span>
                                                </div>
                                            </th>
                                            <th @click="ordenar('DNI')"
                                                class="px-5 py-3.5 text-left font-semibold text-default-700 cursor-pointer hover:text-primary transition-colors text-xs uppercase tracking-wider table-sortable">
                                                <div class="inline-flex items-center gap-1.5">
                                                    DNI
                                                    <span :class="sortColumn === 'DNI' ? 'text-primary' : 'text-default-300'"
                                                        x-text="sortColumn === 'DNI' ? (sortDirection === 'asc' ? '▲' : '▼') : '⇅'"
                                                        class="text-[10px] transition-colors"></span>
                                                </div>
                                            </th>
                                            <th @click="ordenar('TipoTrabajador')"
                                                class="px-5 py-3.5 text-left font-semibold text-default-700 cursor-pointer hover:text-primary transition-colors text-xs uppercase tracking-wider table-sortable">
                                                <div class="inline-flex items-center gap-1.5">
                                                    Tipo
                                                    <span :class="sortColumn === 'TipoTrabajador' ? 'text-primary' : 'text-default-300'"
                                                        x-text="sortColumn === 'TipoTrabajador' ? (sortDirection === 'asc' ? '▲' : '▼') : '⇅'"
                                                        class="text-[10px] transition-colors"></span>
                                                </div>
                                            </th>
                                            <th @click="ordenar('Cargo')"
                                                class="px-5 py-3.5 text-left font-semibold text-default-700 cursor-pointer hover:text-primary transition-colors text-xs uppercase tracking-wider table-sortable">
                                                <div class="inline-flex items-center gap-1.5">
                                                    Cargo
                                                    <span :class="sortColumn === 'Cargo' ? 'text-primary' : 'text-default-300'"
                                                        x-text="sortColumn === 'Cargo' ? (sortDirection === 'asc' ? '▲' : '▼') : '⇅'"
                                                        class="text-[10px] transition-colors"></span>
                                                </div>
                                            </th>
                                            <th @click="ordenar('Estado')"
                                                class="px-5 py-3.5 text-center font-semibold text-default-700 cursor-pointer hover:text-primary transition-colors text-xs uppercase tracking-wider table-sortable">
                                                <div class="inline-flex items-center gap-1.5">
                                                    Estado
                                                    <span :class="sortColumn === 'Estado' ? 'text-primary' : 'text-default-300'"
                                                        x-text="sortColumn === 'Estado' ? (sortDirection === 'asc' ? '▲' : '▼') : '⇅'"
                                                        class="text-[10px] transition-colors"></span>
                                                </div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white">
                                        <template x-for="(persona, index) in personalPaginado" :key="index">
                                            <tr class="table-row border-b border-default-100"
                                                :class="index % 2 === 1 ? 'table-row-even' : ''">
                                                <td class="px-5 py-3 text-center text-default-400 text-xs font-mono"
                                                    x-text="((currentPage - 1) * perPage) + index + 1"></td>
                                                <td class="px-5 py-3 text-default-800 font-semibold whitespace-nowrap text-sm"
                                                    x-text="persona.CodigoPers"></td>
                                                <td class="px-5 py-3 text-default-800 whitespace-nowrap text-sm"
                                                    x-text="persona.NombreCompleto"></td>
                                                <td class="px-5 py-3 text-default-500 font-mono text-sm"
                                                    x-text="persona.DNI"></td>
                                                <td class="px-5 py-3 text-default-600 whitespace-nowrap text-sm"
                                                    x-text="persona.TipoTrabajador"></td>
                                                <td class="px-5 py-3 text-default-600 whitespace-nowrap text-sm"
                                                    x-text="persona.Cargo"></td>
                                                <td class="px-5 py-3 text-center">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border" :class="{
                                                            'bg-amber-50 text-amber-700 border-amber-200': persona.Estado === 'PENDIENTE',
                                                            'bg-green-50 text-green-700 border-green-200': persona.Estado === 'APROBADO',
                                                            'bg-red-50 text-red-700 border-red-200': persona.Estado === 'DESAPROBADO',
                                                            'bg-gray-50 text-gray-600 border-gray-200': persona.Estado === 'SIN ACCEDER',
                                                            'bg-blue-50 text-blue-700 border-blue-200': persona.Estado === 'EN CURSO'
                                                        }">
                                                        <span class="badge-dot" :class="{
                                                                'bg-amber-500': persona.Estado === 'PENDIENTE',
                                                                'bg-green-500': persona.Estado === 'APROBADO',
                                                                'bg-red-500': persona.Estado === 'DESAPROBADO',
                                                                'bg-gray-400': persona.Estado === 'SIN ACCEDER',
                                                                'bg-blue-500': persona.Estado === 'EN CURSO'
                                                            }"></span>
                                                        <span x-text="persona.Estado"></span>
                                                    </span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </template>

                            <template x-if="esReportePorCursos">
                                <div class="divide-y divide-default-200">
                                    <template x-for="(grupo, gi) in personal" :key="gi">
                                        <div>
                                            <div class="sticky top-0 z-10 bg-gradient-to-r from-primary/5 via-primary/[0.02] to-transparent px-5 py-3 border-b border-default-200">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-2.5">
                                                        <div class="w-1.5 h-8 rounded-full bg-primary/60"></div>
                                                        <div>
                                                            <h4 class="text-sm font-bold text-default-800 leading-tight" x-text="grupo.Curso"></h4>
                                                            <p class="text-[11px] text-default-500 mt-0.5">
                                                                <span class="font-semibold text-default-700" x-text="grupo.Total"></span> personal(es) registrados
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <table class="min-w-full text-sm">
                                                <thead>
                                                    <tr class="bg-default-50/80 border-b border-default-200">
                                                        <th class="px-5 py-3 text-center font-semibold text-default-700 text-xs uppercase tracking-wider w-14">#</th>
                                                        <th class="px-5 py-3 text-left font-semibold text-default-700 text-xs uppercase tracking-wider">Código</th>
                                                        <th class="px-5 py-3 text-left font-semibold text-default-700 text-xs uppercase tracking-wider">Nombre completo</th>
                                                        <th class="px-5 py-3 text-left font-semibold text-default-700 text-xs uppercase tracking-wider">DNI</th>
                                                        <th class="px-5 py-3 text-left font-semibold text-default-700 text-xs uppercase tracking-wider">Tipo</th>
                                                        <th class="px-5 py-3 text-left font-semibold text-default-700 text-xs uppercase tracking-wider">Cargo</th>
                                                        <th class="px-5 py-3 text-center font-semibold text-default-700 text-xs uppercase tracking-wider">Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white">
                                                    <template x-for="(persona, pi) in grupo.Personales" :key="pi">
                                                        <tr class="table-row border-b border-default-100"
                                                            :class="pi % 2 === 1 ? 'table-row-even' : ''">
                                                            <td class="px-5 py-3 text-center text-default-400 text-xs font-mono" x-text="pi + 1"></td>
                                                            <td class="px-5 py-3 text-default-800 font-semibold whitespace-nowrap text-sm" x-text="persona.CodigoPers"></td>
                                                            <td class="px-5 py-3 text-default-800 whitespace-nowrap text-sm" x-text="persona.NombreCompleto"></td>
                                                            <td class="px-5 py-3 text-default-500 font-mono text-sm" x-text="persona.DNI"></td>
                                                            <td class="px-5 py-3 text-default-600 whitespace-nowrap text-sm" x-text="persona.TipoTrabajador"></td>
                                                            <td class="px-5 py-3 text-default-600 whitespace-nowrap text-sm" x-text="persona.Cargo"></td>
                                                            <td class="px-5 py-3 text-center">
                                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border" :class="{
                                                                        'bg-amber-50 text-amber-700 border-amber-200': persona.Estado === 'PENDIENTE',
                                                                        'bg-green-50 text-green-700 border-green-200': persona.Estado === 'APROBADO',
                                                                        'bg-red-50 text-red-700 border-red-200': persona.Estado === 'DESAPROBADO',
                                                                        'bg-gray-50 text-gray-600 border-gray-200': persona.Estado === 'SIN ACCEDER',
                                                                        'bg-blue-50 text-blue-700 border-blue-200': persona.Estado === 'EN CURSO'
                                                                    }">
                                                                    <span class="badge-dot" :class="{
                                                                            'bg-amber-500': persona.Estado === 'PENDIENTE',
                                                                            'bg-green-500': persona.Estado === 'APROBADO',
                                                                            'bg-red-500': persona.Estado === 'DESAPROBADO',
                                                                            'bg-gray-400': persona.Estado === 'SIN ACCEDER',
                                                                            'bg-blue-500': persona.Estado === 'EN CURSO'
                                                                        }"></span>
                                                                    <span x-text="persona.Estado"></span>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </template>
                                </div>
                            </template>

                        </div>

                        <div x-show="!esReportePorCursos && personal.length > 0"
                            class="flex items-center justify-between px-5 py-3 border-t border-default-200 bg-default-50/80">
                            <div class="text-sm text-default-500">
                                <span class="font-medium text-default-700" x-text="personal.length"></span> registros
                                <span class="mx-1.5 text-default-300">·</span>
                                Pág. <span class="font-medium text-default-700" x-text="currentPage"></span>
                                de <span class="font-medium text-default-700" x-text="totalPages"></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="currentPage--" :disabled="currentPage === 1"
                                    class="inline-flex items-center gap-1.5 px-3 h-8 rounded-lg border border-default-200 bg-white text-sm font-medium text-default-600 hover:bg-default-100 hover:text-default-800 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                                    <i class="ti ti-chevron-left text-xs"></i>
                                    Anterior
                                </button>
                                <button type="button" @click="currentPage++" :disabled="currentPage >= totalPages"
                                    class="inline-flex items-center gap-1.5 px-3 h-8 rounded-lg border border-default-200 bg-white text-sm font-medium text-default-600 hover:bg-default-100 hover:text-default-800 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                                    Siguiente
                                    <i class="ti ti-chevron-right text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <div x-show="esReportePorCursos && personal.length > 0"
                            class="flex items-center justify-between px-5 py-3 border-t border-default-200 bg-default-50/80">
                            <div class="text-sm text-default-500">
                                Total general: <span class="font-semibold text-default-700" x-text="totalPersonal"></span> personal(es)
                                en <span class="font-semibold text-default-700" x-text="personal.length"></span> curso(s)
                            </div>
                        </div>

                    </div>
                </template>
            </div>

            <div class="flex justify-end items-center gap-2 py-4 px-6 border-t border-default-100">
                <template x-if="view === 'filters'">
                    <div class="flex items-center justify-end gap-2 w-full">
                        <button type="button" @click="cerrar()"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-default-600 bg-default-100 hover:bg-default-200 hover:text-default-800 transition-all cursor-pointer">
                            Cancelar
                        </button>
                        <button type="button" @click="obtenerPersonal()"
                            :disabled="loadingPersonal || !selectedCurso"
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
                                Generar PDF
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Modal Reporte de Capacitaciones --}}
    <div id="modal-reporte-de-capacitaciones" x-data="modalReporteDeCapacitaciones" x-show="open" x-cloak
        @keydown.escape.window="cerrar()" class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        style="background: rgba(36,39,70,0.45);">

        <div :class="view === 'cursos' ? 'max-w-6xl' : 'max-w-xl'"
            class="flex flex-col w-full bg-white rounded-2xl shadow-2xl shadow-primary/10 border border-default-200 overflow-hidden transition-all duration-300"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4">

            <div class="flex justify-between items-start py-5 px-6 border-b border-default-100">
                <div class="flex items-center gap-3.5">
                    <div
                        class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white shadow-sm shrink-0">
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

            <div x-show="view === 'filters'" class="px-6 pt-4 pb-6 space-y-4">
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

            <div x-show="view === 'cursos'" class="px-6 pb-6">

                <template x-if="loadingCursos">
                    <div class="flex flex-col items-center justify-center py-10 text-default-400">
                        <i class="ti ti-loader animate-spin text-2xl mb-2"></i>
                        <p class="text-sm">Cargando cursos...</p>
                    </div>
                </template>

                <template x-if="!loadingCursos && cursosFilas.length === 0">
                    <div class="text-center py-10 text-default-500 text-sm">
                        No se encontraron cursos con los criterios indicados.
                    </div>
                </template>

                <template x-if="!loadingCursos && cursosFilas.length > 0">
                    <div class="flex flex-col max-h-[500px] border border-default-200 rounded-xl">
                        <div class="flex-1 overflow-auto custom-scrollbar">
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

            <div class="flex justify-end items-center gap-2 py-4 px-6 border-t border-default-100">
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
        x-cloak @keydown.escape.window="cerrar()" class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        style="background: rgba(36,39,70,0.45);">

        <div :class="view === 'resultados' ? 'max-w-6xl' : 'max-w-4xl'"
            class="flex flex-col w-full bg-white rounded-2xl shadow-2xl shadow-primary/10 border border-default-200 overflow-hidden transition-all duration-300"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4">

            <div class="flex justify-between items-start py-5 px-6 border-b border-default-100">
                <div class="flex items-center gap-3.5">
                    <div
                        class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white shadow-sm shrink-0">
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

            <div x-show="view === 'filters'" class="px-6 pt-4 pb-6">
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

            <div x-show="view === 'resultados'" class="px-6 pb-6">
                <template x-if="loadingRecord">
                    <div class="flex flex-col items-center justify-center py-10 text-default-400">
                        <i class="ti ti-loader animate-spin text-2xl mb-2"></i>
                        <p class="text-sm">Cargando récord...</p>
                    </div>
                </template>

                <template x-if="!loadingRecord && personalRecord.length === 0">
                    <div class="text-center py-10 text-default-500 text-sm">
                        No se encontraron cursos para el personal con los filtros indicados.
                    </div>
                </template>

                <template x-if="!loadingRecord && personalRecord.length > 0">
                    <div class="flex flex-col max-h-[500px] border border-default-200 rounded-xl">
                        <div class="flex-1 overflow-auto custom-scrollbar">
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

            <div class="flex justify-end items-center gap-2 py-4 px-6 border-t border-default-100">
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

    {{-- Modal Historial de Reportes --}}
    <div id="modal-historial-reportes" x-data="modalHistorialReportes" x-show="open" x-cloak
        @keydown.escape.window="cerrar()" class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        style="background: rgba(36,39,70,0.45);">

        <div class="flex flex-col w-full max-w-5xl bg-white rounded-2xl shadow-2xl shadow-primary/10 border border-default-200 overflow-hidden transition-all duration-300"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4">

            <div class="flex justify-between items-start py-5 px-6 border-b border-default-100">
                <div class="flex items-center gap-3.5">
                    <div
                        class="w-10 h-10 rounded-xl bg-indigo-500 flex items-center justify-center text-white shadow-sm shrink-0">
                        <i class="ti ti-clock text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-[15px] font-semibold text-default-900 leading-tight">
                            Historial de reportes
                        </h3>
                        <p class="text-xs text-default-500 mt-0.5">Reportes de capacitaciones generados</p>
                    </div>
                </div>
                <button type="button" @click="cerrar()"
                    class="flex-shrink-0 w-7 h-7 inline-flex items-center justify-center rounded-lg text-default-400 hover:text-default-700 hover:bg-default-100 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors cursor-pointer">
                    <i class="ti ti-x text-base"></i>
                </button>
            </div>

            <div class="px-6 pt-4 pb-6">
                <div class="mb-4 flex items-center gap-3">
                    <div class="relative flex-1">
                        <i
                            class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-default-400 text-sm w-4 h-4 flex items-center justify-center"></i>
                        <input type="text" x-model="searchQuery" @input="currentPage = 1"
                            placeholder="Buscar por nombre, fecha o ID..."
                            class="w-full h-9 pl-10 pr-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer shrink-0">
                        <input type="checkbox" x-model="showDeletedOnly" @change="currentPage = 1" class="sr-only peer">
                        <div
                            class="w-9 h-5 bg-default-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-default-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-500 relative">
                        </div>
                        <span class="text-xs font-medium text-default-600">Eliminados</span>
                    </label>
                </div>

                <template x-if="loading">
                    <div class="flex flex-col items-center justify-center py-16 text-default-400">
                        <i class="ti ti-loader animate-spin text-2xl mb-2"></i>
                        <p class="text-sm">Cargando reportes...</p>
                    </div>
                </template>

                <template x-if="!loading && reportes.length === 0">
                    <div class="text-center py-16">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-default-100 flex items-center justify-center mb-4">
                            <i class="ti ti-file-off text-2xl text-default-400"></i>
                        </div>
                        <p class="text-sm text-default-500">No se encontraron reportes generados.</p>
                        <p class="text-xs text-default-400 mt-1">Los reportes aparecerán aquí al exportar a Excel o PDF.
                        </p>
                    </div>
                </template>

                <template x-if="!loading && reportes.length > 0">
                    <div class="flex flex-col max-h-[550px] border border-default-200 rounded-xl">
                        <template x-if="reportesFiltrados.length === 0">
                            <div class="text-center py-10 text-default-500 text-sm">
                                <template x-if="showDeletedOnly">
                                    <span>No se encontraron reportes eliminados.</span>
                                </template>
                                <template x-if="!showDeletedOnly">
                                    <span>No se encontraron reportes que coincidan con "<span x-text="searchQuery"
                                            class="font-medium"></span>".</span>
                                </template>
                            </div>
                        </template>
                        <template x-if="reportesFiltrados.length > 0">
                            <div class="flex-1 overflow-auto custom-scrollbar">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-default-50 border-b border-default-200 sticky top-0 z-10">
                                        <tr>
                                            <th class="px-4 py-3 text-center font-semibold text-default-700 w-12">
                                                <input type="checkbox"
                                                    @change="toggleSeleccionTodos($event.target.checked)"
                                                    :checked="todosSeleccionados"
                                                    class="w-4 h-4 rounded border-default-300 text-primary focus:ring-primary cursor-pointer">
                                            </th>
                                            <th class="px-4 py-3 text-left font-semibold text-default-700 w-14">#</th>
                                            <th @click="ordenar('nombre_archivo')"
                                                class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors">
                                                Nombre del archivo
                                                <span class="ml-1 text-xs"
                                                    :class="sortColumn === 'nombre_archivo' ? 'text-primary' : 'text-default-300'"
                                                    x-text="sortColumn === 'nombre_archivo' ? (sortDirection === 'asc' ? '↑' : sortDirection === 'desc' ? '↓' : '↕') : '↕'"></span>
                                            </th>
                                            <th @click="ordenar('descripcion')"
                                                class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors">
                                                Descripción
                                                <span class="ml-1 text-xs"
                                                    :class="sortColumn === 'descripcion' ? 'text-primary' : 'text-default-300'"
                                                    x-text="sortColumn === 'descripcion' ? (sortDirection === 'asc' ? '↑' : sortDirection === 'desc' ? '↓' : '↕') : '↕'"></span>
                                            </th>
                                            <th class="px-4 py-3 text-center font-semibold text-default-700 w-32">
                                                Descargar</th>
                                            <th @click="ordenar('fecha_creacion')"
                                                class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors whitespace-nowrap">
                                                Fecha
                                                <span class="ml-1 text-xs"
                                                    :class="sortColumn === 'fecha_creacion' ? 'text-primary' : 'text-default-300'"
                                                    x-text="sortColumn === 'fecha_creacion' ? (sortDirection === 'asc' ? '↑' : sortDirection === 'desc' ? '↓' : '↕') : '↕'"></span>
                                            </th>
                                            <th class="px-4 py-3 text-center font-semibold text-default-700 w-20"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-default-100 bg-white">
                                        <template x-for="(reporte, index) in reportesFiltrados" :key="reporte.id">
                                            <tr class="hover:bg-default-50 transition-colors"
                                                :class="!reporte.habilitado ? 'bg-default-100/50 opacity-60' : ''">
                                                <td class="px-4 py-3 text-center">
                                                    <template x-if="reporte.habilitado">
                                                        <input type="checkbox" :value="reporte.id"
                                                            @change="toggleSeleccion(reporte.id, $event.target.checked)"
                                                            :checked="selectedReportes.includes(reporte.id)"
                                                            class="w-4 h-4 rounded border-default-300 text-primary focus:ring-primary cursor-pointer">
                                                    </template>
                                                </td>
                                                <td class="px-4 py-3 text-default-500" x-text="index + 1"></td>

                                                <template x-if="editingId !== reporte.id">
                                                    <td class="px-4 py-3 font-medium max-w-[18rem] truncate"
                                                        :class="reporte.habilitado ? 'text-default-800' : 'text-default-500 line-through'"
                                                        x-text="reporte.nombre_archivo" :title="reporte.nombre_archivo">
                                                    </td>
                                                </template>
                                                <template x-if="editingId === reporte.id">
                                                    <td class="px-4 py-3">
                                                        <input type="text" x-model="editForm.nombre_archivo"
                                                            class="w-full h-8 px-2.5 text-sm bg-white border border-primary/30 rounded-lg text-default-900 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                                            placeholder="Nombre del archivo">
                                                    </td>
                                                </template>

                                                <template x-if="editingId !== reporte.id">
                                                    <td class="px-4 py-3 max-w-[14rem] truncate"
                                                        :class="reporte.habilitado ? 'text-default-600' : 'text-default-400'"
                                                        x-text="reporte.descripcion || '—'"
                                                        :title="reporte.descripcion"></td>
                                                </template>
                                                <template x-if="editingId === reporte.id">
                                                    <td class="px-4 py-3">
                                                        <input type="text" x-model="editForm.descripcion"
                                                            class="w-full h-8 px-2.5 text-sm bg-white border border-primary/30 rounded-lg text-default-900 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                                            placeholder="Descripción">
                                                    </td>
                                                </template>

                                                <td class="px-4 py-3">
                                                    <template x-if="reporte.habilitado">
                                                        <div class="flex items-center justify-center gap-2">
                                                            <button x-show="reporte.tiene_pdf"
                                                                @click="descargarArchivo(reporte.id, 'pdf')"
                                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 transition-colors cursor-pointer"
                                                                title="Descargar PDF">
                                                                <i class="ti ti-download text-sm"></i>
                                                            </button>
                                                            <button x-show="reporte.tiene_excel"
                                                                @click="descargarArchivo(reporte.id, 'excel')"
                                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 hover:text-green-700 transition-colors cursor-pointer"
                                                                title="Descargar Excel">
                                                                <i class="ti ti-download text-sm"></i>
                                                            </button>
                                                        </div>
                                                    </template>
                                                    <template x-if="!reporte.habilitado">
                                                        <span class="text-xs text-default-400 italic">Eliminado</span>
                                                    </template>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-xs"
                                                    :class="reporte.habilitado ? 'text-default-600' : 'text-default-400'"
                                                    x-text="formatearFecha(reporte.fecha_creacion)"></td>

                                                <td class="px-4 py-3">
                                                    <div class="flex items-center justify-center gap-1.5">
                                                        <template x-if="reporte.habilitado && editingId !== reporte.id">
                                                            <button @click="iniciarEdicion(reporte)"
                                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-default-100 text-default-500 hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer"
                                                                title="Editar">
                                                                <i class="ti ti-pencil text-sm"></i>
                                                            </button>
                                                        </template>
                                                        <template x-if="reporte.habilitado && editingId === reporte.id">
                                                            <div class="flex items-center gap-1">
                                                                <button @click="guardarEdicion()" :disabled="savingEdit"
                                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 hover:text-green-700 transition-colors cursor-pointer disabled:opacity-50"
                                                                    title="Guardar">
                                                                    <i class="ti ti-check text-sm"></i>
                                                                </button>
                                                                <button @click="cancelarEdicion()"
                                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 transition-colors cursor-pointer"
                                                                    title="Cancelar">
                                                                    <i class="ti ti-x text-sm"></i>
                                                                </button>
                                                            </div>
                                                        </template>

                                                        <template x-if="reporte.habilitado">
                                                            <button @click="cambiarEstado(reporte.id, false)"
                                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-700 transition-colors cursor-pointer"
                                                                title="Eliminar">
                                                                <i class="ti ti-trash text-sm"></i>
                                                            </button>
                                                        </template>
                                                        <template x-if="!reporte.habilitado">
                                                            <button @click="cambiarEstado(reporte.id, true)"
                                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 text-green-500 hover:bg-green-100 hover:text-green-700 transition-colors cursor-pointer"
                                                                title="Recuperar">
                                                                <i class="ti ti-refresh text-sm"></i>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <div
                            class="flex items-center justify-between px-4 py-3 border-t border-default-200 bg-default-50">
                            <div class="text-sm text-default-500">
                                <template x-if="searchQuery">
                                    <span>Mostrando <span x-text="reportesFiltrados.length"></span> de <span
                                            x-text="reportes.length"></span> reporte(s)</span>
                                </template>
                                <template x-if="!searchQuery">
                                    <span>Total: <span x-text="reportes.length"></span> reporte(s)</span>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex justify-end items-center gap-2 py-4 px-6 border-t border-default-100">
                <div class="flex items-center gap-3 flex-1">
                    <span x-show="selectedReportes.length > 0" class="text-sm text-default-600">
                        <span x-text="selectedReportes.length"></span>
                        seleccionado(s)
                    </span>
                    <button x-show="selectedReportes.length > 0" @click="descargarSeleccionadosZip()"
                        :disabled="downloadingZip"
                        class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="ti ti-file-zip text-sm"></i>
                        <span x-text="downloadingZip ? 'Generando ZIP...' : 'Descargar ZIP'"></span>
                    </button>
                </div>
                <button type="button" @click="cerrar()"
                    class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-default-600 bg-default-100 hover:bg-default-200 hover:text-default-800 transition-all cursor-pointer">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
@vite(['resources/js/app.js'])
@endsection