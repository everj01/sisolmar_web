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
                            Ver historial de reportes generados
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
                <h3 class="text-base font-bold text-default-900 mb-2">Récord Histórico de Capacitación por personal</h3>
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

        {{-- Card 4: Reporte General de Capacitaciones --}}
        <div
            class="card-hover group relative overflow-hidden rounded-2xl border border-default-200/60 bg-white shadow-sm">
            <div class="relative p-6 flex flex-col h-full">
                <!-- Icon + Badge -->
                <div class="flex items-start justify-between mb-5">
                    <div
                        class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-400 flex items-center justify-center shadow-md shadow-violet-500/20">
                        <i class="ti ti-report-analytics text-xl text-white"></i>
                    </div>
                    <span
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-violet-50 text-violet-600 text-[10px] font-bold uppercase tracking-wider">
                        <i class="ti ti-chart-bar text-[9px]"></i>
                        General
                    </span>
                </div>

                <!-- Title + Description -->
                <h3 class="text-base font-bold text-default-900 mb-2">Reporte General de Capacitaciones</h3>
                <p class="text-sm text-default-500 leading-relaxed mb-4">
                    Visualice y exporte datos consolidados con filtros avanzados.
                </p>

                <!-- Features -->
                <div class="space-y-2 mb-5 flex-grow">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-violet-500 shrink-0"></i>
                        <span class="text-xs text-default-600">Filtre por empresa, sucursal y tipo de trabajo</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-violet-500 shrink-0"></i>
                        <span class="text-xs text-default-600">Personal, estado, capacitación y pool</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-violet-500 shrink-0"></i>
                        <span class="text-xs text-default-600">Exporte a Excel y PDF</span>
                    </div>
                </div>

                <!-- Button -->
                <button @click="abrirModalReporteGeneral()"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-violet-500 text-white text-sm font-semibold hover:bg-violet-600 transition-colors w-full">
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
                        <template x-for="option in cursos" :key="option.Id">
                            <option :value="option.Id" x-text="option.Nombre"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Estado de alumnos <span class="text-danger">*</span>
                    </label>
                    <select x-model="selectedEstado"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                        <option value="0">Todos los estados</option>
                        <option value="1">Aprobados</option>
                        <option value="2">Desaprobados</option>
                        <option value="3">Sin acceder</option>
                        <option value="4">En curso</option>
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
                                <div>
                                    <template x-if="hayAgrupacionPorSucursal">
                                        <div class="divide-y divide-default-200">
                                            <template x-for="(grupo, gi) in personalPorSucursal" :key="gi">
                                                <div>
                                                    <div class="bg-gradient-to-r from-primary/5 via-primary/[0.02] to-transparent px-5 py-1.5 border-b border-default-200">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-1 h-5 rounded-full bg-primary/60 shrink-0"></div>
                                                            <span class="text-sm font-bold text-default-800" x-text="grupo.SucursalNombre"></span>
                                                            <span class="text-[11px] text-default-500">·</span>
                                                            <span class="text-[11px] text-default-500"><span class="font-semibold text-default-700" x-text="grupo.Personales.length"></span> personal(es)</span>
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
                                                                <th class="px-5 py-3 text-center font-semibold text-default-700 text-xs uppercase tracking-wider">Nota final</th>
                                                                <th class="px-5 py-3 text-center font-semibold text-default-700 text-xs uppercase tracking-wider">Estado</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="bg-white">
                                                            <template x-for="(persona, pi) in grupo.Personales" :key="pi">
                                                                <tr class="table-row border-b border-default-100"
                                                                    :class="pi % 2 === 1 ? 'table-row-even' : ''">
                                                                    <td class="px-5 py-2.5 text-center text-default-400 text-xs font-mono" x-text="pi + 1"></td>
                                                                    <td class="px-5 py-2.5 text-default-800 font-semibold whitespace-nowrap text-sm" x-text="persona.CodigoPers"></td>
                                                                    <td class="px-5 py-2.5 text-default-800 whitespace-nowrap text-sm" x-text="persona.NombreCompleto"></td>
                                                                    <td class="px-5 py-2.5 text-default-500 font-mono text-sm" x-text="persona.DNI"></td>
                                                                    <td class="px-5 py-2.5 text-default-600 whitespace-nowrap text-sm" x-text="persona.TipoTrabajador"></td>
                                                                    <td class="px-5 py-2.5 text-default-600 whitespace-nowrap text-sm" x-text="persona.Cargo"></td>
                                                                    <td class="px-5 py-2.5 text-center">
                                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border"
                                                                            :class="parseFloat(persona.Nota_Final) >= 10 ? 'bg-green-50 text-green-700 border-green-200' : (persona.Nota_Final ? 'bg-red-50 text-red-700 border-red-200' : 'bg-gray-50 text-gray-600 border-gray-200')">
                                                                            <span class="badge-dot" :class="parseFloat(persona.Nota_Final) >= 10 ? 'bg-green-500' : (persona.Nota_Final ? 'bg-red-500' : 'bg-gray-400')"></span>
                                                                            <span x-text="persona.Nota_Final || '—'"></span>
                                                                        </span>
                                                                    </td>
                                                                    <td class="px-5 py-2.5 text-center">
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
                                    <template x-if="!hayAgrupacionPorSucursal">
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
                                                    <th @click="ordenar('Nota_Final')"
                                                        class="px-5 py-3.5 text-center font-semibold text-default-700 cursor-pointer hover:text-primary transition-colors text-xs uppercase tracking-wider table-sortable">
                                                        <div class="inline-flex items-center gap-1.5">
                                                            Nota final
                                                            <span :class="sortColumn === 'Nota_Final' ? 'text-primary' : 'text-default-300'"
                                                                x-text="sortColumn === 'Nota_Final' ? (sortDirection === 'asc' ? '▲' : '▼') : '⇅'"
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
                                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border"
                                                                :class="parseFloat(persona.Nota_Final) >= 10 ? 'bg-green-50 text-green-700 border-green-200' : (persona.Nota_Final ? 'bg-red-50 text-red-700 border-red-200' : 'bg-gray-50 text-gray-600 border-gray-200')">
                                                                <span class="badge-dot" :class="parseFloat(persona.Nota_Final) >= 10 ? 'bg-green-500' : (persona.Nota_Final ? 'bg-red-500' : 'bg-gray-400')"></span>
                                                                <span x-text="persona.Nota_Final || '—'"></span>
                                                            </span>
                                                        </td>
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
                                </div>
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
                                                        <th class="px-5 py-3 text-center font-semibold text-default-700 text-xs uppercase tracking-wider">Nota final</th>
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
                                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border"
                                                                    :class="parseFloat(persona.Nota_Final) >= 11 ? 'bg-green-50 text-green-700 border-green-200' : (persona.Nota_Final ? 'bg-red-50 text-red-700 border-red-200' : 'bg-gray-50 text-gray-600 border-gray-200')">
                                                                    <span class="badge-dot" :class="parseFloat(persona.Nota_Final) >= 11 ? 'bg-green-500' : (persona.Nota_Final ? 'bg-red-500' : 'bg-gray-400')"></span>
                                                                    <span x-text="persona.Nota_Final || '—'"></span>
                                                                </span>
                                                            </td>
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

                        <div x-show="!esReportePorCursos && personal.length > 0 && !hayAgrupacionPorSucursal"
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
                        <button type="button" @click="exportarExcelDesdeFiltros()"
                            :disabled="exportando || !selectedCurso"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-green-600 hover:bg-green-700 shadow-sm transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="ti ti-file-spreadsheet text-sm"></i>
                            <span x-text="exportando ? 'Exportando...' : 'Exportar Excel'"></span>
                        </button>
                        <button type="button" @click="exportarPDFDesdeFiltros()"
                            :disabled="exportando || !selectedCurso"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 shadow-sm transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="ti ti-file-type-pdf text-sm"></i>
                            <span x-text="exportando ? 'Exportando...' : 'Exportar PDF'"></span>
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

        <div :class="view === 'cursos' ? 'max-w-7xl' : 'max-w-xl'"
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
                        Sistema de gestión
                    </label>
                    <select x-model="selectedSistema"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                        <option value="">Todos los sistemas</option>
                        <template x-for="option in sistemas" :key="option.codigo">
                            <option :value="option.codigo" x-text="option.descripcion"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Área responsable
                    </label>
                    <select x-model="selectedArea"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                        <option value="">Todas las áreas</option>
                        <template x-for="option in areas" :key="option.codModdle">
                            <option :value="option.codModdle" x-text="option.Area"></option>
                        </template>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-default-700 mb-1.5 block">
                            Fecha de creación (desde) <span class="text-default-400 font-normal">(opcional)</span>
                        </label>
                        <input type="date" x-model="selectedFechaInicio"
                            class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-default-700 mb-1.5 block">
                            Fecha de creación (hasta) <span class="text-default-400 font-normal">(opcional)</span>
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
                                        <th @click="ordenar('Nombre')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors min-w-[14rem]">
                                            Nombre de curso
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'Nombre' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'Nombre' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left font-semibold text-default-700 min-w-[16rem]">
                                            Descripción
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left font-semibold text-default-700 min-w-[10rem]">
                                            Sistema
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left font-semibold text-default-700 min-w-[10rem]">
                                            Área
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left font-semibold text-default-700 whitespace-nowrap">
                                            Matriculados
                                        </th>
                                        <th @click="ordenar('Responsable')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors min-w-[10rem]">
                                            Responsable
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'Responsable' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'Responsable' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('Fecha_Inicio')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors whitespace-nowrap">
                                            Fecha de inicio
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'Fecha_Inicio' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'Fecha_Inicio' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('Fecha_Fin')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors whitespace-nowrap">
                                            Fecha de fin
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'Fecha_Fin' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'Fecha_Fin' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('Fecha_Creacion')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors whitespace-nowrap">
                                            Fecha de creación
                                            <span class="ml-1 text-xs"
                                                :class="sortColumn === 'Fecha_Creacion' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'Fecha_Creacion' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-default-100 bg-white">
                                    <template x-for="(fila, index) in cursosPaginado" :key="fila.id">
                                        <tr class="hover:bg-default-50 transition-colors">
                                            <td class="px-4 py-3 text-default-500"
                                                x-text="((currentPage - 1) * perPage) + index + 1"></td>
                                            <td class="px-4 py-3 text-default-800 font-medium"
                                                x-text="fila.Nombre"></td>
                                            <td class="px-4 py-3 text-default-600 max-w-[16rem]">
                                                <span class="line-clamp-2" x-text="fila.Descripcion"
                                                    :title="fila.Descripcion"></span>
                                            </td>
                                            <td class="px-4 py-3 text-default-600"
                                                x-text="fila.Sistema"></td>
                                            <td class="px-4 py-3 text-default-600"
                                                x-text="fila.Area"></td>
                                            <td class="px-4 py-3 text-default-600 text-center font-medium"
                                                x-text="fila.Total_Matriculados"></td>
                                            <td class="px-4 py-3 text-default-800 max-w-[14rem]">
                                                <span class="line-clamp-2" x-text="fila.Responsable"
                                                    :title="fila.Responsable"></span>
                                            </td>
                                            <td class="px-4 py-3 text-default-600 whitespace-nowrap"
                                                x-text="fila.Fecha_Inicio"></td>
                                            <td class="px-4 py-3 text-default-600 whitespace-nowrap"
                                                x-text="fila.Fecha_Fin"></td>
                                            <td class="px-4 py-3 text-default-600 whitespace-nowrap"
                                                x-text="fila.Fecha_Creacion"></td>
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
                        <button type="button" @click="exportarExcelHistorialCursosDesdeFiltros()"
                            :disabled="exportando"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-green-600 hover:bg-green-700 shadow-sm transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="ti ti-file-spreadsheet text-sm"></i>
                            <span x-text="exportando ? 'Exportando...' : 'Exportar Excel'"></span>
                        </button>
                        <button type="button" @click="exportarPDFHistorialCursosDesdeFiltros()"
                            :disabled="exportando"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 shadow-sm transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="ti ti-file-type-pdf text-sm"></i>
                            <span x-text="exportando ? 'Exportando...' : 'Exportar PDF'"></span>
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
                                Generar PDF
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
                                                            <div class="flex items-center gap-1">
                                                                <button @click="eliminarDefinitivamente(reporte.id)"
                                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-700 transition-colors cursor-pointer"
                                                                    title="Eliminar permanentemente">
                                                                    <i class="ti ti-trash-off text-sm"></i>
                                                                </button>
                                                                <button @click="cambiarEstado(reporte.id, true)"
                                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 text-green-500 hover:bg-green-100 hover:text-green-700 transition-colors cursor-pointer"
                                                                    title="Recuperar">
                                                                    <i class="ti ti-refresh text-sm"></i>
                                                                </button>
                                                            </div>
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

    {{-- Modal Record Histórico de Capacitaciones por Personal --}}
    <div id="modal-record-personal" x-data="modalRecordPersonal" x-show="open" x-cloak
        @keydown.escape.window="cerrar()" class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        style="background: rgba(36,39,70,0.45);">

        <div :class="view === 'filters' ? 'max-w-[1200px]' : 'max-w-[1300px]'"
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
                        class="w-10 h-10 rounded-xl bg-green-500 flex items-center justify-center text-white shadow-sm shrink-0">
                        <i class="ti ti-history text-lg"></i>
                    </div>
                    <div>
                        <div x-show="view === 'filters'">
                            <h3 class="text-[15px] font-semibold text-default-900 leading-tight">
                                Récord Histórico de Capacitación por personal
                            </h3>
                            <p class="text-xs text-default-500 mt-0.5">Seleccione cursos, personal y período para consultar</p>
                        </div>
                        <div x-show="view === 'results'">
                            <h3 class="text-[15px] font-semibold text-default-900 leading-tight">
                                Resultados del récord
                            </h3>
                            <p class="text-xs text-default-500 mt-0.5">
                                <span x-text="totalResultados"></span> personal(es) encontrados
                            </p>
                        </div>
                    </div>
                </div>
                <button type="button" @click="cerrar()"
                    class="flex-shrink-0 w-7 h-7 inline-flex items-center justify-center rounded-lg text-default-400 hover:text-default-700 hover:bg-default-100 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors cursor-pointer">
                    <i class="ti ti-x text-base"></i>
                </button>
            </div>

            {{-- Initial loader --}}
            <template x-if="loadingInicial">
                <div class="flex flex-col items-center justify-center py-20 text-default-400">
                    <i class="ti ti-loader animate-spin text-3xl mb-3"></i>
                    <p class="text-sm font-medium">Cargando personal y cursos...</p>
                </div>
            </template>

            {{-- Filters view --}}
            <div x-show="!loadingInicial && view === 'filters'" class="px-6 pt-4 pb-6 overflow-y-auto max-h-[72vh] custom-scrollbar">
                <div class="grid grid-cols-2 gap-6">
                    {{-- Left column: Personal --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 border-b border-default-200 pb-2">
                            <i class="ti ti-users text-green-500 text-base"></i>
                            <h4 class="text-sm font-semibold text-default-800">Personal</h4>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <select x-model="selectedCliente" @change="filtrarPersonales()"
                                    class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                    <option value="" x-text="loadingClientes ? 'Cargando...' : 'Todos los clientes'"></option>
                                    <template x-for="c in clientes" :key="c.codigo">
                                        <option :value="c.codigo" x-text="c.descripcion"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <select x-model="selectedSucursal" @change="filtrarPersonales()"
                                    class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                    <option value="" x-text="loadingSucursales ? 'Cargando...' : 'Todas las sucursales'"></option>
                                    <template x-for="s in sucursales" :key="s.codigo">
                                        <option :value="s.codigo" x-text="s.sucursal"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <select x-model="selectedTipoTrabajador" @change="filtrarPersonales()"
                                    class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                    <option value="">Todos los tipos</option>
                                    <template x-for="t in tiposTrabajador" :key="t">
                                        <option :value="t" x-text="t"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <select x-model="selectedCargo" @change="filtrarPersonales()"
                                    class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                    <option value="">Todos los cargos</option>
                                    <template x-for="c in cargos" :key="c">
                                        <option :value="c" x-text="c"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div>
                            <div class="relative">
                                <input type="text" x-model="searchPersonal" @input="filtrarPersonales()" placeholder="Buscar por nombre o DNI..."
                                    class="w-full h-9 pl-8 pr-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-default-500" x-text="selectedUsernames.length + ' personal(es) seleccionado(s)'"></span>
                                <button type="button" @click="toggleAllPersonal()"
                                    class="text-xs text-primary hover:text-primary/80 font-medium transition-colors cursor-pointer"
                                    x-text="selectAllPersonal ? 'Deseleccionar todos' : 'Seleccionar todos'">
                                </button>
                            </div>
                            <div class="border border-default-200 rounded-lg max-h-64 overflow-y-auto custom-scrollbar">
                                <template x-if="loadingPersonal">
                                    <div class="flex items-center justify-center py-6 text-default-400">
                                        <i class="ti ti-loader animate-spin text-lg mr-2"></i>
                                        <span class="text-sm">Cargando personal...</span>
                                    </div>
                                </template>
                                <template x-if="!loadingPersonal && personalesFiltrados.length === 0">
                                    <div class="text-center py-6 text-default-400 text-sm">
                                        No se encontró personal con los filtros seleccionados.
                                    </div>
                                </template>
                                <template x-for="p in personalesPaginados()" :key="p.dni">
                                    <label class="flex items-center gap-3 px-3 py-2 hover:bg-default-50 cursor-pointer border-b border-default-100 last:border-b-0 transition-colors">
                                        <input type="checkbox" :value="p.dni"
                                            @change="togglePersonal(p.dni)"
                                            :checked="selectedUsernames.includes(p.dni)"
                                            class="w-4 h-4 rounded border-default-300 text-primary focus:ring-primary cursor-pointer shrink-0">
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-sm font-medium text-default-800 truncate" x-text="p.nombre_completo"></span>
                                        </div>
                                    </label>
                                </template>
                            </div>

                            {{-- Paginación Personal --}}
                            <template x-if="personalesFiltrados.length > personalPerPage">
                                <div class="flex items-center justify-between pt-2.5">
                                    <span class="text-xs text-default-400">
                                        Pág. <span x-text="personalPage"></span> de <span x-text="personalTotalPages"></span>
                                    </span>
                                    <div class="flex items-center gap-1">
                                        {{-- Anterior --}}
                                        <button type="button"
                                            @click="personalPage = Math.max(1, personalPage - 1)"
                                            :disabled="personalPage <= 1"
                                            class="w-7 h-7 flex items-center justify-center rounded-md border border-default-200 text-default-500 hover:bg-default-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors cursor-pointer">
                                            <i class="ti ti-chevron-left text-xs"></i>
                                        </button>

                                        {{-- Página 1 siempre visible --}}
                                        <button type="button" @click="personalPage = 1"
                                            class="w-7 h-7 flex items-center justify-center rounded-md border text-xs font-medium transition-colors cursor-pointer"
                                            :class="personalPage === 1 ? 'bg-primary text-white border-primary shadow-sm' : 'border-default-200 text-default-600 hover:bg-default-100'">
                                            1
                                        </button>

                                        {{-- Elipsis izquierda --}}
                                        <template x-if="personalPage > 3">
                                            <span class="w-5 h-7 flex items-center justify-center text-default-400 text-xs select-none">…</span>
                                        </template>

                                        {{-- Página previa a la actual (si no es 1 ni última) --}}
                                        <template x-if="personalPage > 2 && personalPage < personalTotalPages">
                                            <button type="button" @click="personalPage = personalPage - 1"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border border-default-200 text-default-600 hover:bg-default-100 text-xs font-medium transition-colors cursor-pointer"
                                                x-text="personalPage - 1">
                                            </button>
                                        </template>

                                        {{-- Página actual (si no es 1 ni última) --}}
                                        <template x-if="personalPage !== 1 && personalPage !== personalTotalPages">
                                            <button type="button"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border bg-primary text-white border-primary shadow-sm text-xs font-medium cursor-default"
                                                x-text="personalPage">
                                            </button>
                                        </template>

                                        {{-- Página siguiente a la actual (si no es 1 ni última) --}}
                                        <template x-if="personalPage < personalTotalPages - 1 && personalPage !== 1">
                                            <button type="button" @click="personalPage = personalPage + 1"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border border-default-200 text-default-600 hover:bg-default-100 text-xs font-medium transition-colors cursor-pointer"
                                                x-text="personalPage + 1">
                                            </button>
                                        </template>

                                        {{-- Elipsis derecha --}}
                                        <template x-if="personalPage < personalTotalPages - 2">
                                            <span class="w-5 h-7 flex items-center justify-center text-default-400 text-xs select-none">…</span>
                                        </template>

                                        {{-- Última página siempre visible (si hay más de 1) --}}
                                        <template x-if="personalTotalPages > 1">
                                            <button type="button" @click="personalPage = personalTotalPages"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border text-xs font-medium transition-colors cursor-pointer"
                                                :class="personalPage === personalTotalPages ? 'bg-primary text-white border-primary shadow-sm' : 'border-default-200 text-default-600 hover:bg-default-100'"
                                                x-text="personalTotalPages">
                                            </button>
                                        </template>

                                        {{-- Siguiente --}}
                                        <button type="button"
                                            @click="personalPage = Math.min(personalTotalPages, personalPage + 1)"
                                            :disabled="personalPage >= personalTotalPages"
                                            class="w-7 h-7 flex items-center justify-center rounded-md border border-default-200 text-default-500 hover:bg-default-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors cursor-pointer">
                                            <i class="ti ti-chevron-right text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Right column: Cursos --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 border-b border-default-200 pb-2">
                            <i class="ti ti-book text-primary text-base"></i>
                            <h4 class="text-sm font-semibold text-default-800">Cursos</h4>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <select x-model="selectedSistema" @change="onSistemaChange()"
                                    class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                    <option value="" x-text="loadingSistemas ? 'Cargando sistemas...' : 'Todos los sistemas'"></option>
                                    <template x-for="option in sistemas" :key="option.codigo">
                                        <option :value="option.codigo" x-text="option.descripcion"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <select x-model="selectedArea" @change="onAreaChange()"
                                    class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                    <option value="" x-text="loadingAreas ? 'Cargando áreas...' : 'Todas las áreas'"></option>
                                    <template x-for="option in areas" :key="option.codModdle">
                                        <option :value="option.codModdle" x-text="option.Area"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-medium text-default-700 mb-1 block">
                                    Fecha de creación desde
                                </label>
                                <input type="date" x-model="selectedFechaDesde" @change="filtrarCursos()"
                                    class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-default-700 mb-1 block">
                                    Fecha de creación hasta
                                </label>
                                <input type="date" x-model="selectedFechaHasta" @change="filtrarCursos()"
                                    class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                            </div>
                        </div>

                        <div>
                            <select x-model="selectedEstadoId"
                                class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                <option value="0">Todos los estados</option>
                                <option value="1">Aprobados</option>
                                <option value="2">Desaprobados</option>
                                <option value="3">Sin acceder</option>
                                <option value="4">En curso</option>
                            </select>
                        </div>

                        <div>
                            <div class="relative">
                                <input type="text" x-model="searchCurso" @input="filtrarCursos()" placeholder="Buscar curso por nombre..."
                                    class="w-full h-9 pl-8 pr-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-default-500" x-text="selectedCourseIds.length + ' curso(s) seleccionado(s)'"></span>
                                <button type="button" @click="toggleAllCursos()"
                                    class="text-xs text-primary hover:text-primary/80 font-medium transition-colors cursor-pointer"
                                    x-text="selectAllCursos ? 'Deseleccionar todos' : 'Seleccionar todos'">
                                </button>
                            </div>
                            <div class="border border-default-200 rounded-lg max-h-64 overflow-y-auto custom-scrollbar">
                                <template x-if="loadingCursos">
                                    <div class="flex items-center justify-center py-6 text-default-400">
                                        <i class="ti ti-loader animate-spin text-lg mr-2"></i>
                                        <span class="text-sm">Cargando cursos...</span>
                                    </div>
                                </template>
                                <template x-if="!loadingCursos && cursos.length === 0">
                                    <div class="text-center py-6 text-default-400 text-sm">
                                        No hay cursos disponibles.
                                    </div>
                                </template>
                                <template x-for="curso in cursosPaginados()" :key="curso.Id">
                                    <label class="flex items-center gap-3 px-3 py-2 hover:bg-default-50 cursor-pointer border-b border-default-100 last:border-b-0 transition-colors">
                                        <input type="checkbox" :value="curso.Id"
                                            @change="toggleCurso(curso.Id)"
                                            :checked="selectedCourseIds.includes(curso.Id)"
                                            class="w-4 h-4 rounded border-default-300 text-primary focus:ring-primary cursor-pointer shrink-0">
                                        <span class="text-sm text-default-700 leading-tight" x-text="curso.Nombre"></span>
                                    </label>
                                </template>
                            </div>

                            {{-- Paginación Cursos --}}
                            <template x-if="cursos.length > cursosPerPage">
                                <div class="flex items-center justify-between pt-2.5">
                                    <span class="text-xs text-default-400">
                                        Pág. <span x-text="cursosPage"></span> de <span x-text="cursosTotalPages"></span>
                                    </span>
                                    <div class="flex items-center gap-1">
                                        {{-- Anterior --}}
                                        <button type="button"
                                            @click="cursosPage = Math.max(1, cursosPage - 1)"
                                            :disabled="cursosPage <= 1"
                                            class="w-7 h-7 flex items-center justify-center rounded-md border border-default-200 text-default-500 hover:bg-default-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors cursor-pointer">
                                            <i class="ti ti-chevron-left text-xs"></i>
                                        </button>

                                        {{-- Página 1 siempre visible --}}
                                        <button type="button" @click="cursosPage = 1"
                                            class="w-7 h-7 flex items-center justify-center rounded-md border text-xs font-medium transition-colors cursor-pointer"
                                            :class="cursosPage === 1 ? 'bg-primary text-white border-primary shadow-sm' : 'border-default-200 text-default-600 hover:bg-default-100'">
                                            1
                                        </button>

                                        {{-- Elipsis izquierda --}}
                                        <template x-if="cursosPage > 3">
                                            <span class="w-5 h-7 flex items-center justify-center text-default-400 text-xs select-none">…</span>
                                        </template>

                                        {{-- Página previa a la actual (si no es 1 ni última) --}}
                                        <template x-if="cursosPage > 2 && cursosPage < cursosTotalPages">
                                            <button type="button" @click="cursosPage = cursosPage - 1"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border border-default-200 text-default-600 hover:bg-default-100 text-xs font-medium transition-colors cursor-pointer"
                                                x-text="cursosPage - 1">
                                            </button>
                                        </template>

                                        {{-- Página actual (si no es 1 ni última) --}}
                                        <template x-if="cursosPage !== 1 && cursosPage !== cursosTotalPages">
                                            <button type="button"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border bg-primary text-white border-primary shadow-sm text-xs font-medium cursor-default"
                                                x-text="cursosPage">
                                            </button>
                                        </template>

                                        {{-- Página siguiente a la actual (si no es 1 ni última) --}}
                                        <template x-if="cursosPage < cursosTotalPages - 1 && cursosPage !== 1">
                                            <button type="button" @click="cursosPage = cursosPage + 1"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border border-default-200 text-default-600 hover:bg-default-100 text-xs font-medium transition-colors cursor-pointer"
                                                x-text="cursosPage + 1">
                                            </button>
                                        </template>

                                        {{-- Elipsis derecha --}}
                                        <template x-if="cursosPage < cursosTotalPages - 2">
                                            <span class="w-5 h-7 flex items-center justify-center text-default-400 text-xs select-none">…</span>
                                        </template>

                                        {{-- Última página siempre visible (si hay más de 1) --}}
                                        <template x-if="cursosTotalPages > 1">
                                            <button type="button" @click="cursosPage = cursosTotalPages"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border text-xs font-medium transition-colors cursor-pointer"
                                                :class="cursosPage === cursosTotalPages ? 'bg-primary text-white border-primary shadow-sm' : 'border-default-200 text-default-600 hover:bg-default-100'"
                                                x-text="cursosTotalPages">
                                            </button>
                                        </template>

                                        {{-- Siguiente --}}
                                        <button type="button"
                                            @click="cursosPage = Math.min(cursosTotalPages, cursosPage + 1)"
                                            :disabled="cursosPage >= cursosTotalPages"
                                            class="w-7 h-7 flex items-center justify-center rounded-md border border-default-200 text-default-500 hover:bg-default-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors cursor-pointer">
                                            <i class="ti ti-chevron-right text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Results view --}}
            <div x-show="view === 'results'" class="px-6 pb-6">

                <template x-if="buscando">
                    <div class="flex flex-col items-center justify-center py-10 text-default-400">
                        <i class="ti ti-loader animate-spin text-2xl mb-2"></i>
                        <p class="text-sm">Consultando récord de capacitaciones...</p>
                    </div>
                </template>

                <template x-if="!buscando && resultados.length === 0">
                    <div class="text-center py-10 text-default-500 text-sm">
                        No se encontraron resultados para los criterios seleccionados.
                    </div>
                </template>

                <template x-if="!buscando && resultados.length > 0">
                    <div class="flex flex-col max-h-[550px] border border-default-200 rounded-xl">
                        <div class="flex-1 overflow-auto custom-scrollbar">
                            <div class="divide-y divide-default-200">
                                <template x-for="(personal, pi) in resultados" :key="pi">
                                    <div>
                                        <div class="top-0 z-10 bg-gradient-to-r from-green-500/5 via-green-500/[0.02] to-transparent px-5 py-3 border-b border-default-200">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-1.5 h-8 rounded-full bg-green-500/60"></div>
                                                    <div>
                                                        <h4 class="text-sm font-bold text-default-800 leading-tight">
                                                            <span x-text="personal.NombreCompleto"></span>
                                                            <span class="font-normal text-default-500"> · </span>
                                                            <span class="font-normal text-default-500 text-xs" x-text="personal.NroDoc"></span>
                                                        </h4>
                                                        <p class="text-[11px] text-default-500 mt-0.5">
                                                            <template x-if="personal.Cargo">
                                                                <span><span class="font-semibold text-default-700" x-text="personal.Cargo"></span> · </span>
                                                            </template>
                                                            <span x-text="personal.Cursos ? personal.Cursos.length : 0"></span> curso(s) registrados
                                                            <template x-if="personal.Sucursal">
                                                                <span> · <span x-text="personal.Sucursal"></span></span>
                                                            </template>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr class="bg-default-50/80 border-b border-default-200">
                                                    <th class="px-5 py-3 text-center font-semibold text-default-700 text-xs uppercase tracking-wider w-14">#</th>
                                                    <th class="px-5 py-3 text-left font-semibold text-default-700 text-xs uppercase tracking-wider">Curso</th>
                                                    <th class="px-5 py-3 text-center font-semibold text-default-700 text-xs uppercase tracking-wider">Nota final</th>
                                                    <th class="px-5 py-3 text-center font-semibold text-default-700 text-xs uppercase tracking-wider">Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white">
                                                <template x-for="(curso, ci) in personal.Cursos" :key="ci">
                                                    <tr class="table-row border-b border-default-100"
                                                        :class="ci % 2 === 1 ? 'table-row-even' : ''">
                                                        <td class="px-5 py-3 text-center text-default-400 text-xs font-mono" x-text="ci + 1"></td>
                                                        <td class="px-5 py-3 text-default-800 font-medium text-sm" x-text="curso.Nombre"></td>
                                                        <td class="px-5 py-3 text-center">
                                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border"
                                                                :class="parseFloat(curso.Nota_Final) >= 11 ? 'bg-green-50 text-green-700 border-green-200' : (curso.Nota_Final && curso.Nota_Final !== 'Sin nota' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-gray-50 text-gray-600 border-gray-200')">
                                                                <span class="badge-dot" :class="parseFloat(curso.Nota_Final) >= 11 ? 'bg-green-500' : (curso.Nota_Final && curso.Nota_Final !== 'Sin nota' ? 'bg-red-500' : 'bg-gray-400')"></span>
                                                                <span x-text="curso.Nota_Final || '—'"></span>
                                                            </span>
                                                        </td>
                                                        <td class="px-5 py-3 text-center">
                                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border" :class="{
                                                                    'bg-amber-50 text-amber-700 border-amber-200': curso.Estado === 'PENDIENTE',
                                                                    'bg-green-50 text-green-700 border-green-200': curso.Estado === 'APROBADO',
                                                                    'bg-red-50 text-red-700 border-red-200': curso.Estado === 'DESAPROBADO',
                                                                    'bg-gray-50 text-gray-600 border-gray-200': curso.Estado === 'SIN ACCEDER' || curso.Estado === 'SIN NOTA',
                                                                    'bg-blue-50 text-blue-700 border-blue-200': curso.Estado === 'EN CURSO'
                                                                }">
                                                                <span class="badge-dot" :class="{
                                                                        'bg-amber-500': curso.Estado === 'PENDIENTE',
                                                                        'bg-green-500': curso.Estado === 'APROBADO',
                                                                        'bg-red-500': curso.Estado === 'DESAPROBADO',
                                                                        'bg-gray-400': curso.Estado === 'SIN ACCEDER' || curso.Estado === 'SIN NOTA',
                                                                        'bg-blue-500': curso.Estado === 'EN CURSO'
                                                                    }"></span>
                                                                <span x-text="curso.Estado"></span>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center justify-between px-5 py-3 border-t border-default-200 bg-default-50/80">
                            <div class="text-sm text-default-500">
                                Total: <span class="font-semibold text-default-700" x-text="totalResultados"></span> personal(es)
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
                        <button type="button" @click="exportarExcelRecord()"
                            :disabled="exportando || selectedCourseIds.length === 0 || selectedUsernames.length === 0"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-green-600 hover:bg-green-700 shadow-sm transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="ti ti-file-spreadsheet text-sm"></i>
                            <span x-text="exportando ? 'Exportando...' : 'Exportar Excel'"></span>
                        </button>
                        <button type="button" @click="exportarPDFRecord()"
                            :disabled="buscando || selectedCourseIds.length === 0 || selectedUsernames.length === 0"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 shadow-sm shadow-red-500/20 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="ti ti-file-type-pdf text-sm"></i>
                            <span x-text="buscando ? 'Generando...' : 'Exportar PDF'"></span>
                        </button>
                    </div>
                </template>

                <template x-if="view === 'results'">
                    <div class="flex items-center justify-between w-full">
                        <button type="button" @click="volverAFiltros()"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-default-600 bg-default-100 hover:bg-default-200 transition-all cursor-pointer">
                            <i class="ti ti-arrow-left text-sm"></i>
                            Atrás
                        </button>
                        <div x-show="resultados.length > 0 && !buscando" class="flex items-center gap-2">
                            <span class="text-sm text-default-500 mr-2">
                                <span x-text="resultados.length"></span> personal(es) · <span x-text="resultados.reduce((acc, p) => acc + (p.Cursos ? p.Cursos.length : 0), 0)"></span> registro(s) de cursos
                            </span>
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

    {{-- Modal Reporte General de Capacitaciones --}}
    <div id="modal-reporte-general" x-data="modalReporteGeneral" x-show="open" x-cloak
        @keydown.escape.window="cerrar()" class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        style="background: rgba(36,39,70,0.45);">

        <div class="flex flex-col w-full max-w-4xl bg-white rounded-2xl shadow-2xl shadow-primary/10 border border-default-200 overflow-hidden transition-all duration-300"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4">

            <div class="flex justify-between items-start py-5 px-6 border-b border-default-100">
                <div class="flex items-center gap-3.5">
                    <div
                        class="w-10 h-10 rounded-xl bg-violet-500 flex items-center justify-center text-white shadow-sm shrink-0">
                        <i class="ti ti-report-analytics text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-[15px] font-semibold text-default-900 leading-tight">
                            Reporte General de Capacitaciones
                        </h3>
                        <p class="text-xs text-default-500 mt-0.5">Filtros avanzados para generar reportes consolidados</p>
                    </div>
                </div>
                <button type="button" @click="cerrar()"
                    class="flex-shrink-0 w-7 h-7 inline-flex items-center justify-center rounded-lg text-default-400 hover:text-default-700 hover:bg-default-100 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors cursor-pointer">
                    <i class="ti ti-x text-base"></i>
                </button>
            </div>

            <template x-if="loadingInicial">
                <div class="flex flex-col items-center justify-center py-20 text-default-400">
                    <i class="ti ti-loader animate-spin text-3xl mb-3"></i>
                    <p class="text-sm font-medium">Cargando personal y cursos...</p>
                </div>
            </template>

            <div x-show="!loadingInicial" class="px-6 pt-4 pb-6 overflow-y-auto max-h-[72vh] custom-scrollbar">
                <div class="grid grid-cols-2 gap-6">
                    {{-- Left column: Personal --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 border-b border-default-200 pb-2">
                            <i class="ti ti-users text-violet-500 text-base"></i>
                            <h4 class="text-sm font-semibold text-default-800">Personal</h4>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <select x-model="selectedCliente" @change="filtrarPersonales()"
                                    class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                    <option value="" x-text="loadingClientes ? 'Cargando...' : 'Todos los clientes'"></option>
                                    <template x-for="c in clientes" :key="c.codigo">
                                        <option :value="c.codigo" x-text="c.descripcion"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <select x-model="selectedEmpresa" @change="filtrarPersonales()"
                                    class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                    <option value="">Todas las empresas</option>
                                    <option value="__sin_empresa__">Sin empresa</option>
                                    <template x-for="e in empresas" :key="e.codigo">
                                        <option :value="e.codigo" x-text="e.descripcion"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <select x-model="selectedSucursal" @change="filtrarPersonales()"
                                    class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                    <option value="">Todas las sucursales</option>
                                    <template x-for="s in sucursales" :key="s.codigo">
                                        <option :value="s.codigo" x-text="s.sucursal"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <select x-model="selectedTipoTrabajo" @change="filtrarPersonales()"
                                    class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                    <option value="">Todos los tipos</option>
                                    <template x-for="t in tiposTrabajo" :key="t">
                                        <option :value="t" x-text="t"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <select x-model="selectedCargo" @change="filtrarPersonales()"
                                    class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                    <option value="">Todos los cargos</option>
                                    <template x-for="c in cargos" :key="c">
                                        <option :value="c" x-text="c"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div>
                            <div class="relative">
                                <input type="text" x-model="searchPersonal" @input="filtrarPersonales()" placeholder="Buscar por nombre o DNI..."
                                    class="w-full h-9 pl-8 pr-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-default-500" x-text="selectedUsernames.length + ' personal(es) seleccionado(s)'"></span>
                                <button type="button" @click="toggleAllPersonal()"
                                    class="text-xs text-primary hover:text-primary/80 font-medium transition-colors cursor-pointer"
                                    x-text="selectAllPersonal ? 'Deseleccionar todos' : 'Seleccionar todos'">
                                </button>
                            </div>
                            <div class="border border-default-200 rounded-lg max-h-64 overflow-y-auto custom-scrollbar">
                                <template x-if="personalesFiltrados.length === 0">
                                    <div class="text-center py-6 text-default-400 text-sm">
                                        No se encontró personal con los filtros seleccionados.
                                    </div>
                                </template>
                                <template x-for="p in personalesPaginados()" :key="p.dni">
                                    <label class="flex items-center gap-3 px-3 py-2 hover:bg-default-50 cursor-pointer border-b border-default-100 last:border-b-0 transition-colors">
                                        <input type="checkbox" :value="p.dni"
                                            @change="togglePersonal(p.dni)"
                                            :checked="selectedUsernames.includes(p.dni)"
                                            class="w-4 h-4 rounded border-default-300 text-primary focus:ring-primary cursor-pointer shrink-0">
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-sm font-medium text-default-800 truncate" x-text="p.nombre_completo"></span>
                                            <span class="text-xs text-default-400 truncate" x-text="p.cargo ? p.cargo + (p.empresa ? ' - ' + p.empresa : '') : (p.empresa || '')"></span>
                                        </div>
                                    </label>
                                </template>
                            </div>

                            {{-- Paginación Personal --}}
                            <template x-if="personalesFiltrados.length > personalPerPage">
                                <div class="flex items-center justify-between pt-2.5">
                                    <span class="text-xs text-default-400">
                                        Pág. <span x-text="personalPage"></span> de <span x-text="personalTotalPages"></span>
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <button type="button"
                                            @click="personalPage = Math.max(1, personalPage - 1)"
                                            :disabled="personalPage <= 1"
                                            class="w-7 h-7 flex items-center justify-center rounded-md border border-default-200 text-default-500 hover:bg-default-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors cursor-pointer">
                                            <i class="ti ti-chevron-left text-xs"></i>
                                        </button>
                                        <button type="button" @click="personalPage = 1"
                                            class="w-7 h-7 flex items-center justify-center rounded-md border text-xs font-medium transition-colors cursor-pointer"
                                            :class="personalPage === 1 ? 'bg-primary text-white border-primary shadow-sm' : 'border-default-200 text-default-600 hover:bg-default-100'">
                                            1
                                        </button>
                                        <template x-if="personalPage > 3">
                                            <span class="w-5 h-7 flex items-center justify-center text-default-400 text-xs select-none">…</span>
                                        </template>
                                        <template x-if="personalPage > 2 && personalPage < personalTotalPages">
                                            <button type="button" @click="personalPage = personalPage - 1"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border border-default-200 text-default-600 hover:bg-default-100 text-xs font-medium transition-colors cursor-pointer"
                                                x-text="personalPage - 1">
                                            </button>
                                        </template>
                                        <template x-if="personalPage !== 1 && personalPage !== personalTotalPages">
                                            <button type="button"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border bg-primary text-white border-primary shadow-sm text-xs font-medium cursor-default"
                                                x-text="personalPage">
                                            </button>
                                        </template>
                                        <template x-if="personalPage < personalTotalPages - 1 && personalPage !== 1">
                                            <button type="button" @click="personalPage = personalPage + 1"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border border-default-200 text-default-600 hover:bg-default-100 text-xs font-medium transition-colors cursor-pointer"
                                                x-text="personalPage + 1">
                                            </button>
                                        </template>
                                        <template x-if="personalPage < personalTotalPages - 2">
                                            <span class="w-5 h-7 flex items-center justify-center text-default-400 text-xs select-none">…</span>
                                        </template>
                                        <template x-if="personalTotalPages > 1">
                                            <button type="button" @click="personalPage = personalTotalPages"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border text-xs font-medium transition-colors cursor-pointer"
                                                :class="personalPage === personalTotalPages ? 'bg-primary text-white border-primary shadow-sm' : 'border-default-200 text-default-600 hover:bg-default-100'"
                                                x-text="personalTotalPages">
                                            </button>
                                        </template>
                                        <button type="button"
                                            @click="personalPage = Math.min(personalTotalPages, personalPage + 1)"
                                            :disabled="personalPage >= personalTotalPages"
                                            class="w-7 h-7 flex items-center justify-center rounded-md border border-default-200 text-default-500 hover:bg-default-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors cursor-pointer">
                                            <i class="ti ti-chevron-right text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Right column: Cursos --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 border-b border-default-200 pb-2">
                            <i class="ti ti-book text-violet-500 text-base"></i>
                            <h4 class="text-sm font-semibold text-default-800">Cursos</h4>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-medium text-default-700 mb-1 block">
                                    Fecha de creación desde
                                </label>
                                <input type="date" x-model="selectedFechaDesde" @change="filtrarCursos()"
                                    class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-default-700 mb-1 block">
                                    Fecha de creación hasta
                                </label>
                                <input type="date" x-model="selectedFechaHasta" @change="filtrarCursos()"
                                    class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                            </div>
                        </div>

                        <div>
                            <select x-model="selectedEstado" @change="filtrarCursos()"
                                class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                <option value="">Todos los estados</option>
                                <option value="APROBADO">Aprobado</option>
                                <option value="DESAPROBADO">Desaprobado</option>
                                <option value="SIN ACCEDER">Sin acceder</option>
                                <option value="EN CURSO">En curso</option>
                            </select>
                        </div>

                        <div>
                            <div class="relative">
                                <input type="text" x-model="searchCurso" @input="filtrarCursos()" placeholder="Buscar curso por nombre..."
                                    class="w-full h-9 pl-8 pr-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-default-500" x-text="selectedCourseIds.length + ' curso(s) seleccionado(s)'"></span>
                                <button type="button" @click="toggleAllCursos()"
                                    class="text-xs text-primary hover:text-primary/80 font-medium transition-colors cursor-pointer"
                                    x-text="selectAllCursos ? 'Deseleccionar todos' : 'Seleccionar todos'">
                                </button>
                            </div>
                            <div class="border border-default-200 rounded-lg max-h-64 overflow-y-auto custom-scrollbar">
                                <template x-if="cursos.length === 0">
                                    <div class="text-center py-6 text-default-400 text-sm">
                                        No hay cursos disponibles.
                                    </div>
                                </template>
                                <template x-for="curso in cursosPaginados()" :key="curso.Id">
                                    <label class="flex items-center gap-3 px-3 py-2 hover:bg-default-50 cursor-pointer border-b border-default-100 last:border-b-0 transition-colors">
                                        <input type="checkbox" :value="curso.Id"
                                            @change="toggleCurso(curso.Id)"
                                            :checked="selectedCourseIds.includes(curso.Id)"
                                            class="w-4 h-4 rounded border-default-300 text-primary focus:ring-primary cursor-pointer shrink-0">
                                        <span class="text-sm text-default-700 leading-tight" x-text="curso.Nombre"></span>
                                    </label>
                                </template>
                            </div>

                            {{-- Paginación Cursos --}}
                            <template x-if="cursos.length > cursosPerPage">
                                <div class="flex items-center justify-between pt-2.5">
                                    <span class="text-xs text-default-400">
                                        Pág. <span x-text="cursosPage"></span> de <span x-text="cursosTotalPages"></span>
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <button type="button"
                                            @click="cursosPage = Math.max(1, cursosPage - 1)"
                                            :disabled="cursosPage <= 1"
                                            class="w-7 h-7 flex items-center justify-center rounded-md border border-default-200 text-default-500 hover:bg-default-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors cursor-pointer">
                                            <i class="ti ti-chevron-left text-xs"></i>
                                        </button>
                                        <button type="button" @click="cursosPage = 1"
                                            class="w-7 h-7 flex items-center justify-center rounded-md border text-xs font-medium transition-colors cursor-pointer"
                                            :class="cursosPage === 1 ? 'bg-primary text-white border-primary shadow-sm' : 'border-default-200 text-default-600 hover:bg-default-100'">
                                            1
                                        </button>
                                        <template x-if="cursosPage > 3">
                                            <span class="w-5 h-7 flex items-center justify-center text-default-400 text-xs select-none">…</span>
                                        </template>
                                        <template x-if="cursosPage > 2 && cursosPage < cursosTotalPages">
                                            <button type="button" @click="cursosPage = cursosPage - 1"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border border-default-200 text-default-600 hover:bg-default-100 text-xs font-medium transition-colors cursor-pointer"
                                                x-text="cursosPage - 1">
                                            </button>
                                        </template>
                                        <template x-if="cursosPage !== 1 && cursosPage !== cursosTotalPages">
                                            <button type="button"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border bg-primary text-white border-primary shadow-sm text-xs font-medium cursor-default"
                                                x-text="cursosPage">
                                            </button>
                                        </template>
                                        <template x-if="cursosPage < cursosTotalPages - 1 && cursosPage !== 1">
                                            <button type="button" @click="cursosPage = cursosPage + 1"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border border-default-200 text-default-600 hover:bg-default-100 text-xs font-medium transition-colors cursor-pointer"
                                                x-text="cursosPage + 1">
                                            </button>
                                        </template>
                                        <template x-if="cursosPage < cursosTotalPages - 2">
                                            <span class="w-5 h-7 flex items-center justify-center text-default-400 text-xs select-none">…</span>
                                        </template>
                                        <template x-if="cursosTotalPages > 1">
                                            <button type="button" @click="cursosPage = cursosTotalPages"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border text-xs font-medium transition-colors cursor-pointer"
                                                :class="cursosPage === cursosTotalPages ? 'bg-primary text-white border-primary shadow-sm' : 'border-default-200 text-default-600 hover:bg-default-100'"
                                                x-text="cursosTotalPages">
                                            </button>
                                        </template>
                                        <button type="button"
                                            @click="cursosPage = Math.min(cursosTotalPages, cursosPage + 1)"
                                            :disabled="cursosPage >= cursosTotalPages"
                                            class="w-7 h-7 flex items-center justify-center rounded-md border border-default-200 text-default-500 hover:bg-default-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors cursor-pointer">
                                            <i class="ti ti-chevron-right text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end items-center gap-2 py-4 px-6 border-t border-default-100">
                <button type="button" @click="cerrar()"
                    class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-default-600 bg-default-100 hover:bg-default-200 hover:text-default-800 transition-all cursor-pointer">
                    Cancelar
                </button>
                <button type="button" @click="exportarExcelReporteGeneral()"
                    :disabled="buscando || selectedUsernames.length === 0 || selectedCourseIds.length === 0"
                    class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all cursor-pointer">
                    <i class="ti ti-file-spreadsheet text-base"></i>
                    <span x-text="buscando ? 'Exportando...' : 'Exportar Excel'"></span>
                </button>
                <button type="button" @click="exportarPDFReporteGeneral()"
                    :disabled="buscando || selectedUsernames.length === 0 || selectedCourseIds.length === 0"
                    class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all cursor-pointer">
                    <i class="ti ti-file-type-pdf text-base"></i>
                    <span x-text="buscando ? 'Generando...' : 'Exportar PDF'"></span>
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