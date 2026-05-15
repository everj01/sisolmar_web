@php
use Carbon\Traits\Date;
@endphp
@extends('layouts.vertical', ['title' => 'Seguimiento de Matrículas'])
@section('css')
<!-- Estilos -->
<style>
.glass-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.3);
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

/* ── Tabulator base ─────────────────────────────────────────── */
.tabulator {
    border: none !important;
    border-radius: 12px !important;
    overflow: hidden !important;
    background: transparent !important;
}

/* ── Header ─────────────────────────────────────────────────── */
.tabulator-header {
    background-color: #f8fafc !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    border-bottom: 2px solid #e2e8f0 !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    font-size: 10px !important;
}

.tabulator-header .tabulator-col {
    background: transparent !important;
    border-right: none !important;
}

/* ── Filas ───────────────────────────────────────────────────── */
.tabulator-row {
    border-bottom: 1px solid #f1f5f9 !important;
    border-left: none !important;
    border-right: none !important;
    background: transparent !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.tabulator-row:last-child {
    border-bottom: none !important;
}

.tabulator-row .tabulator-cell {
    padding-top: 10.5px !important;
    padding-bottom: 6px !important;
    vertical-align: middle !important;
    border-right: none !important;
}

#tblCursos td {
    padding-top: 10.5px !important;
    padding-bottom: 6px !important;
    vertical-align: middle !important;
}

.tabulator-row:hover {
    background-color: rgba(var(--tw-color-primary), 0.05) !important;
    box-shadow: inset 4px 0 0 0 rgb(var(--tw-color-primary)) !important;
}

.tabulator-tableholder {
    overflow-x: hidden !important;
}

/* ── Footer ──────────────────────────────────────────────────── */
.tabulator-footer {
    border-top: 2px solid #e2e8f0 !important;
    border-left: none !important;
    border-right: none !important;
    border-bottom: none !important;
    padding: 12px 12px 20px 12px !important;
    background-color: #ffffff !important;
    text-align: center !important;
}

.tabulator-footer-contents {
    flex-direction: column !important;
    align-items: left !important;
    justify-content: left !important;
    gap: 5px !important;
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
    border-color: #1e40af !important;
    color: #ffffff !important;
    box-shadow: 0 4px 6px -1px rgba(30, 64, 175, 0.3) !important;
}

.tabulator-footer .tabulator-page:hover:not(.active) {
    background-color: #f9fafb !important;
    border-color: #d1d5db !important;
}

[x-cloak] {
    display: none !important;
}

/* Fix para doble scrollbar */
body {
    height: 100vh !important;
    overflow: hidden !important;
}

.page-content {
    height: 100vh !important;
    overflow-y: auto !important;
}

/* Ajuste para tabla sin registros */
.tabulator-placeholder {
    min-height: 80px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.tabulator-placeholder span {
    color: #94a3b8 !important;
    font-size: 0.875rem !important;
    font-weight: 500 !important;
}
</style>
@endsection
@section('content')

<div x-data="{ tabActivo: 'cursos' }">
    <!-- Header de página -->
    <div class="px-6 py-6">
        <div
            class="relative overflow-hidden rounded-2xl border border-default-200/60 bg-gradient-to-br from-white to-default-50 shadow-sm">

            <div class="absolute top-0 right-0 w-72 h-72 bg-primary/5 rounded-full blur-3xl"></div>

            <div class="relative p-8 flex felx-row gap-3">
                <!-- Columna informativa -->
                <div class="flex flex-col gap-3 w-[70%]">

                    <div
                        class="inline-flex items-center gap-2 w-fit px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-medium">
                        <i class="ti ti-chart-bar text-sm"></i>
                        Panel de seguimiento
                    </div>

                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-default-900">
                            Seguimiento de Matriculados
                        </h1>

                        <p class="mt-3 text-sm leading-7 text-default-600 max-w-4xl">
                            Supervise el progreso de los participantes matriculados en los cursos de capacitación
                            mediante indicadores informativos. Acceda rápidamente a usuarios que aún no inician,
                            participantes en progreso, aprobados y desaprobados, además de herramientas de seguimiento
                            y notificación por correo electrónico.
                        </p>
                    </div>

                    <div x-data="infoBadges()" class="flex flex-wrap gap-3 pt-2">
                        <div @click="abrirInfo('Participantes matriculados', 'Son todos los colaboradores que han sido registrados en un curso. Este indicador muestra el total de personas inscritas, independientemente de si han comenzado o finalizado el curso.')"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-default-200 shadow-sm cursor-pointer hover:bg-primary/5 transition-colors">
                            <i class="ti ti-users text-primary"></i>
                            <span class="text-sm text-default-700">Participantes matriculados</span>
                        </div>

                        <div @click="abrirInfo('Usuarios en progreso', 'Son aquellos colaboradores que ya ingresaron al curso y han avanzado en al menos una actividad o módulo, pero aún no lo completan. Refleja el grupo activo que está actualmente capacitándose.')"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-default-200 shadow-sm cursor-pointer hover:bg-amber-50 transition-colors">
                            <i class="ti ti-player-play text-amber-600"></i>
                            <span class="text-sm text-default-700">Usuarios en progreso</span>
                        </div>

                        <div @click="abrirInfo('Aprobados y desaprobados', 'Muestra el resultado final de los participantes que completaron el curso. Los aprobados son quienes cumplieron con los requisitos mínimos; los desaprobados no alcanzaron la nota o estándar requerido.')"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-default-200 shadow-sm cursor-pointer hover:bg-green-50 transition-colors">
                            <i class="ti ti-circle-check text-green-600"></i>
                            <span class="text-sm text-default-700">Aprobados y desaprobados</span>
                        </div>

                        <div @click="abrirInfo('Notificaciones por correo', mensajes.notificaciones)"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-default-200 shadow-sm cursor-pointer hover:bg-sky-50 transition-colors">
                            <i class="ti ti-mail text-sky-600"></i>
                            <span class="text-sm text-default-700">Notificaciones por correo</span>
                        </div>
                    </div>
                </div>

                <!-- Columna de filtrados y búsquedas -->
                <div class="flex flex-col gap-3 w-[30%] bg-white rounded-xl p-4 border border-default-200">
                    <div class="flex items-center gap-2 pb-1 border-b border-default-200">
                        <i class="ti ti-adjustments-horizontal text-sm text-default-500"></i>
                        <span class="text-xs font-medium text-default-500 tracking-wide">Filtros de personal</span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Sucursal</label>
                        <div class="relative">
                            <i
                                class="ti ti-building-store absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-gray-400 pointer-events-none"></i>
                            <select id="filtroSucursalPersonal" :disabled="tabActivo !== 'personal'"
                                style="background-image: none !important;" class="w-full pl-8 pr-8 py-1.5 text-sm border border-default-200 rounded-lg 
           !bg-white !text-default-700 !appearance-none cursor-pointer 
           focus:outline-none focus:ring-1 focus:ring-primary/30"
                                :class="{ '!cursor-not-allowed opacity-60': tabActivo !== 'personal' }">
                                <option value="">Todas las sucursales</option>
                            </select>
                            <i
                                class="ti ti-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Tipo de cargo</label>
                        <div class="relative">
                            <i
                                class="ti ti-briefcase absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-gray-400 pointer-events-none"></i>
                            <select id="filtroTipoPersonal" :disabled="tabActivo !== 'personal'"
                                style="background-image: none !important;" class="w-full pl-8 pr-8 py-1.5 text-sm border border-default-200 rounded-lg 
           !bg-white !text-default-700 !appearance-none cursor-pointer 
           focus:outline-none focus:ring-1 focus:ring-primary/30"
                                :class="{ '!cursor-not-allowed opacity-60': tabActivo !== 'personal' }">
                                <option value="">Todos los tipos</option>
                                <option value="Administrativo">Administrativo</option>
                                <option value="Operativo">Operativo</option>
                            </select>
                            <i
                                class="ti ti-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5" x-data="searchPersonalSeguimiento()">
                        <label class="text-xs font-medium text-gray-500 tracking-wide uppercase">Buscar personal</label>
                        <div class="relative">
                            <i
                                class="ti ti-search absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-gray-400 pointer-events-none"></i>
                            <input x-ref="inputBuscar" x-model="query" @input="search(); posicionarDropdown()"
                                @focus="posicionarDropdown()" @keydown.enter.stop="seleccionarPrimerResultado()"
                                @click.away="open = false" placeholder="Buscar por DNI o nombres..." class="w-full pl-8 pr-3 py-2 text-sm border border-default-200 rounded-lg
                !bg-white !text-default-700 placeholder:text-default-300
                focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary/50
                transition-shadow duration-150" />

                            <div x-ref="dropdown" x-show="open && results.length > 0" x-cloak
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="fixed z-[999] bg-white border border-default-200 rounded-xl shadow-xl overflow-hidden">

                                <div class="px-3 py-2 border-b border-default-100">
                                    <span
                                        class="text-[10px] font-semibold tracking-widest uppercase text-gray-400">Resultados</span>
                                </div>

                                <div class="max-h-56 overflow-y-auto custom-scrollbar">
                                    <template x-for="(p, idx) in results" :key="idx">
                                        <div @click="seleccionarPersonal(p)" class="flex items-center gap-3 px-3 py-2.5 cursor-pointer
                            hover:bg-primary/5 border-b border-default-100 last:border-b-0
                            transition-colors duration-100 group">

                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-800 truncate"
                                                    x-text="p.nombre_completo"></p>
                                                <p class="text-xs mt-0.5">
                                                    <span class="text-gray-400">DNI</span>
                                                    <span
                                                        class="ml-1 bg-gray-100 text-gray-600 rounded px-1 py-0.5 font-mono text-[11px]"
                                                        x-text="p.dni || '—'"></span>
                                                </p>
                                            </div>

                                            <i class="ti ti-chevron-right text-xs text-gray-300
                            group-hover:text-primary group-hover:translate-x-0.5
                            transition-all duration-150 shrink-0"></i>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Listado de cursos / Personal -->
    <div class="px-6 pb-6">
        <div class="glass-card rounded-xl p-6 shadow-sm">
            <!-- Tab Navigation -->
            <div class="flex items-center gap-1 border-b border-gray-200 mb-4">
                <button
                    @click="tabActivo = 'cursos'; setTimeout(() => { if (window.tabulatorCursos) window.tabulatorCursos.redraw(true) }, 100)"
                    :class="tabActivo === 'cursos'
                    ? 'px-4 py-2.5 text-sm font-bold text-primary border-b-2 border-primary -mb-[1px] transition-all'
                    : 'px-4 py-2.5 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent -mb-[1px] transition-all'">
                    <i class="ti ti-book text-sm mr-1.5"></i>
                    Cursos registrados
                </button>
                <button
                    @click="tabActivo = 'personal'; setTimeout(() => { if (window.tabulatorPersonal) window.tabulatorPersonal.redraw(true) }, 100)"
                    :class="tabActivo === 'personal'
                    ? 'px-4 py-2.5 text-sm font-bold text-primary border-b-2 border-primary -mb-[1px] transition-all'
                    : 'px-4 py-2.5 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent -mb-[1px] transition-all'">
                    <i class="ti ti-users text-sm mr-1.5"></i>
                    Lista de personal
                </button>
            </div>

            <!-- Tab 1: Cursos registrados -->
            <div x-show="tabActivo === 'cursos'">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-default-800">Cursos registrados</h2>
                    <div class="relative">
                        <i
                            class="ti ti-search absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-gray-400 pointer-events-none"></i>
                        <input id="buscarCursoSeguimiento" placeholder="Buscar por nombre o código..."
                            class="w-64 pl-8 pr-3 py-1.5 text-sm border border-default-200 rounded-lg !bg-white !text-default-700 placeholder:text-default-300 focus:outline-none focus:ring-1 focus:ring-primary/30">
                    </div>
                </div>
                <div id="tblCursosSeguimiento" class="w-full"></div>
            </div>

            <!-- Tab 2: Lista de personal -->
            <div x-show="tabActivo === 'personal'" class="min-h-[400px]">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-default-800">Lista de personal</h2>
                </div>
                <div id="tblPersonalSeguimiento" class="w-full"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal detalle de curso -->
<div id="modal-detalle-curso" x-data="modalCurso()" x-show="open" x-cloak
    class="fixed inset-0 z-[80] flex items-center justify-center p-4"
    style="background: rgba(0,0,0,0.35); backdrop-filter: blur(2px);">

    <div
        class="flex flex-col glass-card shadow-2xl rounded-3xl overflow-hidden w-full max-w-2xl border border-white/40 bg-white animate-fade-in">

        <!-- Header -->
        <div
            class="flex justify-between items-start py-5 px-6 bg-gradient-to-r from-primary/10 via-primary/5 to-transparent border-b border-gray-100">
            <div class="flex items-start gap-4 flex-1 min-w-0">
                <div
                    class="w-12 h-12 rounded-2xl bg-primary text-white flex items-center justify-center font-black text-lg shadow-lg shadow-primary/20 shrink-0">
                    <i class="ti ti-book text-xl"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="font-black text-gray-800 text-lg leading-tight tracking-tight truncate"
                        x-text="curso.nombre">
                        Cargando...</h3>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5">
                        <p class="text-[10px] font-bold text-primary uppercase tracking-widest"
                            x-text="'Local: ' + curso.codigo">Local: -</p>
                        <span class="text-gray-300">|</span>
                        <p class="text-[10px] font-bold text-primary/70 uppercase tracking-widest"
                            x-text="'Moodle: ' + curso.codigo_moodle">Moodle: -</p>
                        <template x-if="curso.responsable">
                            <template x-if="true">
                                <div class="flex items-center gap-x-3">
                                    <span class="text-gray-300">|</span>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest truncate"
                                        x-text="'Responsable: ' + curso.responsable">Responsable: -</p>
                                </div>
                            </template>
                        </template>
                    </div>
                    <template x-if="curso.fechaCreacion">
                        <p class="text-[10px] text-gray-400 mt-1">
                            <i class="ti ti-calendar text-[9px] mr-0.5"></i>
                            <span x-text="'Creado: ' + curso.fechaCreacion"></span>
                        </p>
                    </template>
                </div>
            </div>
            <button type="button" @click="cerrar()"
                class="w-8 h-8 inline-flex justify-center items-center rounded-full border bg-white text-gray-700 shadow-sm hover:bg-gray-50 hover:text-danger focus:outline-none focus:ring-2 focus:ring-primary transition-all shrink-0 ml-2">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="px-6 pt-5 pb-2">
            <div class="grid grid-cols-4 gap-3">
                <!-- Total Matriculados -->
                <div class="text-center p-3 rounded-2xl bg-gradient-to-br from-primary/5 to-primary/10 border border-primary/10">
                    <div class="w-10 h-10 rounded-xl bg-primary/20 flex items-center justify-center mx-auto mb-2">
                        <i class="ti ti-users text-xl text-primary"></i>
                    </div>
                    <p class="text-[9px] text-gray-500 uppercase tracking-widest font-bold mb-0.5">Matriculados</p>
                    <p class="text-2xl font-black text-gray-800" x-text="curso.total">0</p>
                </div>

                <!-- Sin Iniciar -->
                <div class="text-center p-3 rounded-2xl bg-gradient-to-br from-amber-50 to-amber-100/50 border border-amber-200/50 cursor-pointer hover:shadow-md transition-shadow"
                    @click="abrirModalUsuarios('sin iniciar')">
                    <div class="w-10 h-10 rounded-xl bg-amber-200/60 flex items-center justify-center mx-auto mb-2">
                        <i class="ti ti-hourglass-empty text-xl text-amber-600"></i>
                    </div>
                    <p class="text-[9px] text-gray-500 uppercase tracking-widest font-bold mb-0.5">Sin iniciar</p>
                    <p class="text-2xl font-black text-amber-600" x-text="curso.totalSinIniciar">0</p>
                </div>

                <!-- En Curso -->
                <div class="text-center p-3 rounded-2xl bg-gradient-to-br from-green-50 to-green-100/50 border border-green-200/50 cursor-pointer hover:shadow-md transition-shadow"
                    @click="abrirModalUsuarios('en curso')">
                    <div class="w-10 h-10 rounded-xl bg-green-200/60 flex items-center justify-center mx-auto mb-2">
                        <i class="ti ti-player-play text-xl text-green-600"></i>
                    </div>
                    <p class="text-[9px] text-gray-500 uppercase tracking-widest font-bold mb-0.5">En curso</p>
                    <p class="text-2xl font-black text-green-600" x-text="curso.totalEnProgreso">0</p>
                </div>

                <!-- Completados -->
                <div class="text-center p-3 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100/50 border border-blue-200/50">
                    <div class="w-10 h-10 rounded-xl bg-blue-200/60 flex items-center justify-center mx-auto mb-2">
                        <i class="ti ti-circle-check text-xl text-blue-600"></i>
                    </div>
                    <p class="text-[9px] text-gray-500 uppercase tracking-widest font-bold mb-0.5">Completados</p>
                    <p class="text-2xl font-black text-blue-600" x-text="curso.totalCompletados">0</p>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="px-6 py-3">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Progreso general del curso</span>
                <span class="text-xs font-black text-primary" x-text="curso.porcentajeProgreso + '%'">0%</span>
            </div>
            <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700 ease-out flex"
                    :style="'width: ' + curso.porcentajeProgreso + '%'">
                    <template x-if="curso.totalEnProgreso > 0">
                        <div class="h-full bg-gradient-to-r from-green-400 to-green-500"
                            :style="'width: ' + (curso.total > 0 ? (curso.totalEnProgreso / curso.total * 100) : 0) + '%'"></div>
                    </template>
                    <template x-if="curso.totalCompletados > 0">
                        <div class="h-full bg-gradient-to-r from-blue-400 to-blue-500"
                            :style="'width: ' + (curso.total > 0 ? (curso.totalCompletados / curso.total * 100) : 0) + '%'"></div>
                    </template>
                </div>
            </div>
            <div class="flex items-center gap-4 mt-2">
                <div class="flex items-center gap-1.5">
                    <div class="w-2.5 h-2.5 rounded-full bg-amber-400"></div>
                    <span class="text-[9px] text-gray-500 font-medium">Sin iniciar</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-2.5 h-2.5 rounded-full bg-green-400"></div>
                    <span class="text-[9px] text-gray-500 font-medium">En curso</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-2.5 h-2.5 rounded-full bg-blue-400"></div>
                    <span class="text-[9px] text-gray-500 font-medium">Completados</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="px-6 pb-2">
            <div class="p-3 rounded-xl bg-gray-50/80 border border-gray-100">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Acciones rápidas</p>
                <div class="flex items-center gap-2">
                    <a :href="'/capacitacion/consulta-matriculas?curso_id=' + curso.codigoInterno"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-primary/10 text-primary text-xs font-bold hover:bg-primary/20 transition-colors">
                        <i class="ti ti-list-details text-sm"></i>
                        Ver matriculados
                    </a>
                    <button type="button" @click="abrirModalUsuarios('sin iniciar')"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-amber-50 text-amber-700 text-xs font-bold hover:bg-amber-100 transition-colors">
                        <i class="ti ti-mail text-sm"></i>
                        Notificar pendientes
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end items-center gap-x-2 py-4 px-6 border-t border-gray-100 bg-white/50">
            <button type="button" @click="cerrar()"
                class="py-2.5 px-6 inline-flex justify-center items-center rounded-xl font-black bg-gray-100 text-gray-800 hover:bg-gray-200 transition-all text-xs uppercase tracking-widest">
                Cerrar
            </button>
            <button type="button" @click="cerrar()"
                class="py-2.5 px-6 inline-flex justify-center items-center rounded-xl font-black bg-primary text-white hover:bg-primary/90 transition-all text-xs uppercase tracking-widest">
                Entendido
            </button>
        </div>
    </div>
</div>

<!-- Modal con lista de usuarios -->
<div id="modal-lista-usuarios" x-data="modalListaUsuarios()" x-show="open" x-cloak
    class="fixed inset-0 z-[90] flex items-center justify-center p-4"
    style="background: rgba(0,0,0,0.35); backdrop-filter: blur(2px);">

    <div
        class="flex flex-col glass-card shadow-2xl rounded-3xl overflow-hidden w-full max-w-3xl border border-white/40 bg-white animate-fade-in">

        <div
            class="flex justify-between items-center py-5 px-6 bg-gradient-to-r from-primary/10 to-transparent border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                    <i class="ti ti-users text-amber-600"></i>
                </div>
                <h3 class="font-black text-gray-800 text-base" x-text="titulo">Usuarios</h3>
            </div>
            <button type="button" @click="cerrar()"
                class="w-8 h-8 inline-flex justify-center items-center rounded-full border bg-white text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary transition-all">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <div class="p-6 custom-scrollbar max-h-[500px] overflow-y-auto">
            <div x-show="cargado && usuarios.length > 0">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs font-bold text-gray-500 uppercase tracking-widest">
                            <th class="text-left py-3 px-2">#</th>
                            <th class="text-left py-3 px-2">Nombre</th>
                            <th class="text-left py-3 px-2">Correo</th>
                            <th class="text-center py-3 px-2">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(user, index) in usuarios" :key="index">
                            <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                                <td class="py-3 px-2 text-gray-400 text-xs font-mono" x-text="index + 1"></td>
                                <td class="py-3 px-2 font-medium text-gray-800 cursor-pointer hover:text-primary transition-colors"
                                    @click="abrirCursoPersonal(user)">
                                    <span x-text="user.full_name"></span>
                                </td>
                                <td class="py-3 px-2 text-gray-500 text-xs" x-text="user.email"></td>
                                <td class="py-3 px-2 text-center">
                                    <button type="button" @click="!estaEnCooldown(user) && notificarUsuario(user)"
                                        :disabled="estaEnCooldown(user)"
                                        :class="estaEnCooldown(user)
        ? 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-200 text-gray-400 cursor-not-allowed text-[10px] font-bold uppercase tracking-wider'
        : 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-sky-500 text-white hover:bg-sky-600 transition-all text-[10px] font-bold uppercase tracking-wider shadow-sm'">
                                        <i :class="estaEnCooldown(user) ? 'ti ti-clock' : 'ti ti-mail'"></i>
                                        <span
                                            x-text="estaEnCooldown(user) ? 'Espera ' + tiempoRestante(user) : 'Notificar por correo'"></span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div x-show="!cargado" class="text-center py-10 text-gray-400 text-sm">Cargando usuarios...</div>
            <div x-show="cargado && usuarios.length === 0" class="text-center py-10 text-gray-400 text-sm">No se
                encontraron usuarios</div>
        </div>

        <div class="flex justify-end items-center gap-x-2 py-4 px-6 border-t border-gray-100 bg-white/50">
            <button type="button" @click="cerrar()"
                class="py-2.5 px-6 inline-flex justify-center items-center rounded-xl font-black bg-gray-100 text-gray-800 hover:bg-gray-200 transition-all text-xs uppercase tracking-widest">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Modal informativo -->
<div id="modal-info" x-data="modalInfo()" x-show="open" x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    style="background: rgba(0,0,0,0.35); backdrop-filter: blur(2px);">

    <div
        class="flex flex-col glass-card shadow-2xl rounded-3xl overflow-hidden w-full max-w-lg border border-white/40 bg-white animate-fade-in">

        <div
            class="flex justify-between items-center py-5 px-6 bg-gradient-to-r from-primary/10 to-transparent border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                    <i class="ti ti-info-circle text-primary"></i>
                </div>
                <h3 class="font-black text-gray-800 text-base" x-text="titulo"></h3>
            </div>
            <button type="button" @click="cerrar()"
                class="w-8 h-8 inline-flex justify-center items-center rounded-full border bg-white text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary transition-all">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <div class="p-6 text-sm text-gray-600 leading-relaxed" x-html="mensaje"></div>

        <div class="flex justify-end items-center gap-x-2 py-4 px-6 border-t border-gray-100 bg-white/50">
            <button type="button" @click="cerrar()"
                class="py-2.5 px-6 inline-flex justify-center items-center rounded-xl font-black bg-primary text-white hover:bg-primary/90 transition-all text-xs uppercase tracking-widest">
                Entendido
            </button>
        </div>
    </div>
</div>

<!-- Modal de usuario encontrado -->
<div id="modal-usuario" x-data="modalUsuario()" x-show="open" x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    style="background: rgba(0,0,0,0.35); backdrop-filter: blur(2px);">

    <div
        class="flex flex-col glass-card shadow-2xl rounded-3xl overflow-hidden w-full max-w-5xl border border-white/40 bg-white animate-fade-in max-h-[90vh]">

        <!-- Header con perfil -->
        <div
            class="flex justify-between items-start py-5 px-6 bg-gradient-to-r from-primary/10 via-primary/5 to-transparent border-b border-gray-100 shrink-0">
            <div class="flex items-start gap-4 flex-1 min-w-0">
                <!-- Avatar con iniciales -->
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary to-primary/80 text-white flex items-center justify-center font-black text-xl shadow-lg shadow-primary/25 shrink-0">
                    <span x-text="getIniciales()"></span>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="font-black text-gray-800 text-lg leading-tight truncate"
                        x-text="personal.nombre_completo">
                        Cargando...</h3>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5">
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest"
                            x-text="'DNI: ' + personal.dni">DNI: -</p>
                        <span class="text-gray-300">|</span>
                        <p class="text-[10px] font-bold text-primary uppercase tracking-widest"
                            x-text="'Código: ' + personal.codigo">Código: -</p>
                        <template x-if="personal.email">
                            <div class="flex items-center gap-x-3">
                                <span class="text-gray-300">|</span>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest truncate"
                                    x-text="personal.email"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <button type="button" @click="cerrar()"
                class="w-8 h-8 inline-flex justify-center items-center rounded-full border bg-white text-gray-700 shadow-sm hover:bg-gray-50 hover:text-danger focus:outline-none focus:ring-2 focus:ring-primary transition-all shrink-0 ml-2">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <!-- Info Personal + Stats Resumen -->
        <div class="px-6 pt-4 pb-2 shrink-0">
            <div class="flex items-start gap-4">
                <!-- Datos del personal -->
                <div class="flex-1 grid grid-cols-3 gap-2">
                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-gray-50/80 border border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                            <i class="ti ti-building-store text-sm text-primary"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] text-gray-400 uppercase tracking-wider font-bold">Sucursal</p>
                            <p class="text-xs font-semibold text-gray-700 truncate"
                                x-text="personal.sucursal || 'No registrada'"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-gray-50/80 border border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                            <i class="ti ti-briefcase text-sm text-primary"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] text-gray-400 uppercase tracking-wider font-bold">Cargo</p>
                            <p class="text-xs font-semibold text-gray-700 truncate"
                                x-text="personal.cargo || 'No registrado'"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-gray-50/80 border border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                            <i class="ti ti-mail text-sm text-primary"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] text-gray-400 uppercase tracking-wider font-bold">Email</p>
                            <p class="text-xs font-semibold text-gray-700 truncate"
                                x-text="personal.email || 'No registrado'"></p>
                        </div>
                    </div>
                </div>

                <!-- Divider -->
                <div class="w-px h-16 bg-gray-200 self-center"></div>

                <!-- Stats de cursos -->
                <div class="flex items-center gap-3">
                    <div class="text-center px-2">
                        <p class="text-lg font-black text-primary" x-text="cursos.length">0</p>
                        <p class="text-[8px] text-gray-400 uppercase tracking-wider font-bold">Total</p>
                    </div>
                    <div class="text-center px-2">
                        <p class="text-lg font-black text-amber-500" x-text="countByEstado('sin_iniciar')">0</p>
                        <p class="text-[8px] text-gray-400 uppercase tracking-wider font-bold">Sin iniciar</p>
                    </div>
                    <div class="text-center px-2">
                        <p class="text-lg font-black text-green-500" x-text="countByEstado('en_curso')">0</p>
                        <p class="text-[8px] text-gray-400 uppercase tracking-wider font-bold">En curso</p>
                    </div>
                    <div class="text-center px-2">
                        <p class="text-lg font-black text-blue-500" x-text="countByEstado('finalizado')">0</p>
                        <p class="text-[8px] text-gray-400 uppercase tracking-wider font-bold">Finalizados</p>
                    </div>
                </div>
            </div>

            <!-- Barra de progreso -->
            <div class="mt-3" x-show="cursosCargado && cursos.length > 0">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Avance general</span>
                    <span class="text-xs font-black text-primary" x-text="porcentajeAvance + '%'">0%</span>
                </div>
                <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700 ease-out flex">
                        <div class="h-full bg-gradient-to-r from-green-400 to-green-500"
                            :style="'width: ' + (cursos.length > 0 ? (countByEstado('en_curso') / cursos.length * 100) : 0) + '%'"></div>
                        <div class="h-full bg-gradient-to-r from-blue-400 to-blue-500"
                            :style="'width: ' + (cursos.length > 0 ? (countByEstado('finalizado') / cursos.length * 100) : 0) + '%'"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cursos del alumno -->
        <div class="flex flex-col flex-1 min-h-0">
            <div class="shrink-0 px-6 pt-4 pb-2">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-bold text-gray-600 uppercase tracking-wider flex items-center gap-2">
                        <i class="ti ti-book text-primary text-sm"></i>
                        Cursos matriculados
                    </h4>
                    <span class="text-[10px] text-gray-400 font-medium"
                        x-text="cursosFiltrados.length + ' de ' + cursos.length + ' curso(s)'"></span>
                </div>

                <div x-show="cursosCargado" class="flex flex-wrap items-center gap-2 pb-2 border-b border-gray-100">
                    <!-- Filtros tipo pills -->
                    <div class="flex items-center gap-1">
                        <button type="button" @click="filtroEstado = 'todos'"
                            :class="filtroEstado === 'todos'
                                ? 'px-3 py-1 rounded-lg bg-primary text-white text-[10px] font-bold transition-all'
                                : 'px-3 py-1 rounded-lg bg-gray-100 text-gray-500 text-[10px] font-bold hover:bg-gray-200 transition-all'">
                            Todos
                        </button>
                        <button type="button" @click="filtroEstado = 'sin_iniciar'"
                            :class="filtroEstado === 'sin_iniciar'
                                ? 'px-3 py-1 rounded-lg bg-amber-500 text-white text-[10px] font-bold transition-all'
                                : 'px-3 py-1 rounded-lg bg-amber-50 text-amber-600 text-[10px] font-bold hover:bg-amber-100 transition-all'">
                            <i class="ti ti-clock mr-0.5"></i> Sin iniciar
                        </button>
                        <button type="button" @click="filtroEstado = 'en_curso'"
                            :class="filtroEstado === 'en_curso'
                                ? 'px-3 py-1 rounded-lg bg-green-500 text-white text-[10px] font-bold transition-all'
                                : 'px-3 py-1 rounded-lg bg-green-50 text-green-600 text-[10px] font-bold hover:bg-green-100 transition-all'">
                            <i class="ti ti-player-play mr-0.5"></i> En curso
                        </button>
                        <button type="button" @click="filtroEstado = 'finalizado'"
                            :class="filtroEstado === 'finalizado'
                                ? 'px-3 py-1 rounded-lg bg-blue-500 text-white text-[10px] font-bold transition-all'
                                : 'px-3 py-1 rounded-lg bg-blue-50 text-blue-600 text-[10px] font-bold hover:bg-blue-100 transition-all'">
                            <i class="ti ti-circle-check mr-0.5"></i> Finalizados
                        </button>
                    </div>
                    <div class="flex-1"></div>
                    <div class="relative">
                        <i
                            class="ti ti-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none"></i>
                        <input x-model="busquedaCurso" placeholder="Buscar curso..."
                            class="w-48 pl-7 pr-3 py-1.5 text-xs border border-default-200 rounded-lg !bg-white !text-default-700 placeholder:text-default-300 focus:outline-none focus:ring-1 focus:ring-primary/30">
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar px-6 pb-4">
                <div x-show="!cursosCargado" class="flex flex-col items-center justify-center py-10 text-gray-400">
                    <i class="ti ti-loader animate-spin text-2xl mb-2"></i>
                    <p class="text-sm">Cargando cursos...</p>
                </div>

                <div x-show="cursosCargado && cursos.length === 0"
                    class="flex flex-col items-center justify-center py-10 text-gray-400">
                    <i class="ti ti-book-off text-3xl opacity-30 mb-2"></i>
                    <p class="text-sm">No se encontraron cursos para este alumno</p>
                </div>

                <div x-show="cursosCargado && cursosFiltrados.length === 0 && cursos.length > 0"
                    class="flex flex-col items-center justify-center py-10 text-gray-400">
                    <i class="ti ti-filter-off text-3xl opacity-30 mb-2"></i>
                    <p class="text-sm">Ningún curso coincide con los filtros</p>
                </div>

                <div x-show="cursosCargado && cursosFiltrados.length > 0" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50/80 sticky top-0 z-10">
                            <tr
                                class="border-b border-gray-200 text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                                <th class="text-left py-2.5 px-3 w-10">#</th>
                                <th class="text-left py-2.5 px-3 w-24">Código</th>
                                <th class="text-left py-2.5 px-3">Nombre del curso</th>
                                <th class="text-center py-2.5 px-3 w-32">Estado</th>
                                <th class="text-center py-2.5 px-3 w-32">Último acceso</th>
                                <th class="text-center py-2.5 px-3 w-28">Matrícula</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(c, index) in cursosFiltrados" :key="c.course_id">
                                <tr class="border-b border-gray-50 hover:bg-primary/[0.03] transition-colors">
                                    <td class="py-2.5 px-3 text-gray-400 text-xs font-mono" x-text="index + 1"></td>
                                    <td class="py-2.5 px-3">
                                        <span class="inline-block px-2 py-0.5 rounded-md bg-primary/5 text-primary text-[10px] font-bold"
                                            x-text="c.course_codigo"></span>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <p class="font-medium text-gray-800 text-xs" x-text="c.course_nombre"></p>
                                        <template x-if="c.area">
                                            <p class="text-[9px] text-gray-400 mt-0.5" x-text="c.area"></p>
                                        </template>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span x-show="c.estado === 'en_curso'"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[9px] font-bold bg-green-100 text-green-700">
                                            <i class="ti ti-player-play text-[8px]"></i>
                                            En curso
                                        </span>
                                        <span x-show="c.estado === 'sin_iniciar'"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[9px] font-bold bg-amber-100 text-amber-700">
                                            <i class="ti ti-clock text-[8px]"></i>
                                            Sin iniciar
                                        </span>
                                        <span x-show="c.estado === 'finalizado'"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[9px] font-bold bg-blue-100 text-blue-700">
                                            <i class="ti ti-circle-check text-[8px]"></i>
                                            Finalizado
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <template x-if="formatearFecha(c.ultimo_acceso)">
                                            <div style="line-height: 1.4;">
                                                <span style="font-size: 11px; font-weight: 600; color: #374151;"
                                                    x-text="formatearFecha(c.ultimo_acceso).fecha"></span>
                                                <br>
                                                <span style="font-size: 10px; color: #9ca3af;"
                                                    x-text="formatearFecha(c.ultimo_acceso).hora"></span>
                                            </div>
                                        </template>
                                        <span x-show="!formatearFecha(c.ultimo_acceso)"
                                            class="text-[10px] text-gray-300">—</span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <template x-if="formatearFecha(c.fecha_inicio_matricula)">
                                            <span style="font-size: 10px; color: #6b7280;"
                                                x-text="formatearFecha(c.fecha_inicio_matricula).fecha"></span>
                                        </template>
                                        <span x-show="!formatearFecha(c.fecha_inicio_matricula)"
                                            class="text-[10px] text-gray-300">—</span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-between items-center gap-x-2 py-4 px-6 border-t border-gray-100 bg-white/50 shrink-0">
            <div class="text-xs text-gray-400">
                <i class="ti ti-info-circle mr-1"></i>
                <span x-show="countByEstado('sin_iniciar') > 0"
                    x-text="countByEstado('sin_iniciar') + ' curso(s) pendiente(s)'"></span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="!enCooldown() && notificarCursosPendientes()" :disabled="enCooldown()"
                    :class="enCooldown()
                        ? 'py-2.5 px-5 inline-flex justify-center items-center rounded-xl font-bold bg-gray-200 text-gray-400 cursor-not-allowed text-xs'
                        : 'py-2.5 px-5 inline-flex justify-center items-center rounded-xl font-bold bg-sky-500 text-white hover:bg-sky-600 transition-all text-xs shadow-sm'">
                    <i :class="enCooldown() ? 'ti ti-clock mr-1.5' : 'ti ti-mail mr-1.5'"></i>
                    <span
                        x-text="enCooldown() ? 'Espera ' + tiempoRestanteCooldown() : 'Notificar pendientes'"></span>
                </button>
                <button type="button" @click="cerrar()"
                    class="py-2.5 px-5 inline-flex justify-center items-center rounded-xl font-bold bg-gray-100 text-gray-700 hover:bg-gray-200 transition-all text-xs">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Blade <script> -->
<script>
window.modalCurso = function() {
    return {
        open: false,
        curso: {
            nombre: 'Cargando...',
            codigo: '-',
            codigoInterno: 0,
            codigo_moodle: 0,
            responsable: '',
            fechaCreacion: '',
            total: 0,
            totalSinIniciar: 0,
            totalEnProgreso: 0,
            totalCompletados: 0,
            porcentajeProgreso: 0
        },

        mostrar(data, fetchUsuarios, sendMail) {
            this._fetchUsuarios = fetchUsuarios;
            this._sendMail = sendMail;
            this.curso.nombre = data.nombre;
            this.curso.codigo = data.codigo_curso;
            this.curso.codigo_moodle = data.codigo_moodle;
            this.curso.codigoInterno = data.codigo;
            this.curso.responsable = data.responsable || '';
            this.curso.fechaCreacion = this.formatearFechaCreacion(data.fecha_creacion);
            this.curso.total = data.total_matriculados;
            this.curso.totalSinIniciar = '...';
            this.curso.totalEnProgreso = '...';
            this.curso.totalCompletados = '...';
            this.curso.porcentajeProgreso = 0;
            this.open = true;

            fetchUsuarios(data.codigo_moodle)
                .then(res => {
                    this.curso.totalSinIniciar = res.data.total_sin_iniciar;
                    this.curso.totalEnProgreso = res.data.total_en_progreso;
                    this.curso.totalCompletados = Math.max(0, this.curso.total - res.data.total_sin_iniciar - res.data.total_en_progreso);
                    this.curso.porcentajeProgreso = this.curso.total > 0
                        ? Math.round(((this.curso.totalEnProgreso + this.curso.totalCompletados) / this.curso.total) * 100)
                        : 0;
                })
                .catch(() => {
                    this.curso.totalSinIniciar = 'Error';
                    this.curso.totalEnProgreso = 'Error';
                    this.curso.totalCompletados = 'Error';
                    this.curso.porcentajeProgreso = 0;
                });
        },

        formatearFechaCreacion(fecha) {
            if (!fecha) return '';
            try {
                const d = new Date(fecha);
                return d.toLocaleDateString('es-PE', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            } catch {
                return '';
            }
        },

        abrirModalUsuarios(tipo = '') {
            const modalUsuarios = document.getElementById('modal-lista-usuarios')._x_dataStack?.[0];
            if (modalUsuarios) {
                modalUsuarios.mostrar(this.curso.nombre, this.curso.codigo_moodle, this._fetchUsuarios, this
                    ._sendMail, tipo);
            }
        },

        cerrar() {
            this.open = false;
        },
    };
};

window.modalListaUsuarios = function() {
    return {
        open: false,
        titulo: '',
        cursoId: null,
        usuarios: [],
        cargado: false,
        notificados: {},
        _cooldownMs: 5 * 60 * 60 * 1000,

        _guardarNotificados() {
            localStorage.setItem('notificados_correo', JSON.stringify(this.notificados));
        },

        mostrar(titulo, moodleCourseId, fetchUsuarios, sendMail, tipo = '') {
            this._sendMail = sendMail;
            this.titulo = tipo ? `${titulo} | Usuarios ${tipo}` : titulo;
            this.cursoId = moodleCourseId;
            this.usuarios = [];
            this.cargado = false;
            this.open = true;

            const guardado = localStorage.getItem('notificados_correo');
            this.notificados = guardado ? JSON.parse(guardado) : {};

            fetchUsuarios(moodleCourseId)
                .then(res => {
                    this.usuarios = res.data.usuarios || [];
                    this.cargado = true;
                })
                .catch(() => {
                    this.cargado = true;
                });
        },

        estaEnCooldown(user) {
            if (!this.notificados) return false;
            const key = user.email + '_' + this.cursoId;
            const ultimaVez = this.notificados[key];
            if (!ultimaVez) return false;
            return (Date.now() - ultimaVez) < this._cooldownMs;
        },

        tiempoRestante(user) {
            if (!this.notificados) return '';
            const key = user.email + '_' + this.cursoId;
            const ultimaVez = this.notificados[key];
            if (!ultimaVez) return '';

            const restanteMs = this._cooldownMs - (Date.now() - ultimaVez);
            if (restanteMs <= 0) return '';

            const horas = Math.floor(restanteMs / 3600000);
            const minutos = Math.floor((restanteMs % 3600000) / 60000);

            if (horas > 0) return `${horas}h ${minutos}m`;
            return `${minutos}m`;
        },

        notificarUsuario(user) {
            const ahora = Date.now();
            const key = user.email + '_' + this.cursoId;
            const ultimaVez = this.notificados[key];

            if (ultimaVez && (ahora - ultimaVez) < this._cooldownMs) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Ya fue notificado',
                    text: `Este usuario ya recibió un recordatorio. Podrás volver a notificarlo en ${this.tiempoRestante(user)}.`,
                });
                return;
            }

            Swal.fire({
                title: 'Enviando correo de recordatorio',
                text: 'Por favor espere...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            this._sendMail({
                    to: user.email,
                    subject: 'Recordatorio: ' + this.titulo,
                    template: 'recordatorio-curso',
                    data: {
                        full_name: user.full_name,
                        course_name: this.titulo,
                        enrolment_start_date: user.enrolment_start_date,
                    },
                })
                .then(() => {
                    this.notificados[key] = Date.now();
                    this._guardarNotificados();

                    Swal.fire({
                        icon: 'success',
                        title: 'Correo enviado',
                        text: 'El recordatorio ha sido enviado a ' + user.email,
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                    });
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo enviar el correo a ' + user.email,
                    });
                });
        },

        abrirCursoPersonal(user) {
            const term = encodeURIComponent((user.username || user.full_name || '').trim());
            if (!term) return;

            fetch(`${VITE_URL_APP}/api/buscar-personal-capacitacion?q=${term}&limite=20`)
                .then(r => r.json())
                .then(res => {
                    const personal = res.personal || [];
                    if (personal.length > 0) {
                        this.open = false;
                        const modalEl = document.getElementById('modal-usuario');
                        const alpineComponent = modalEl?._x_dataStack?. [0];
                        if (alpineComponent) {
                            alpineComponent.mostrar({
                                ...personal[0],
                                email: user.email
                            });
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'No encontrado',
                            text: 'No se encontraron datos de este usuario en el sistema'
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al buscar el usuario'
                    });
                });
        },

        cerrar() {
            this.open = false;
        },
    };
};

window.infoBadges = function() {
    return {
        mensajes: {
            notificaciones: '<p>Las notificaciones por correo funcionan de dos formas:</p><br><p><b>• Automatizado:</b> Los días 1 y 15 de cada mes, el sistema analiza automáticamente todos los cursos y envía un correo recordatorio a los participantes que aún no han iniciado el curso.</p><br><p><b>• Manual:</b> Ingresando al listado de usuarios que no han iniciado un curso (haciendo clic en la tarjeta "Sin iniciar" del detalle del curso), puedes enviar un recordatorio individual haciendo clic en el botón "Notificar por correo" de cada usuario.</p>',
        },
        abrirInfo(titulo, mensaje) {
            const el = document.getElementById('modal-info')._x_dataStack?. [0];
            if (el) {
                el.mostrar(titulo, mensaje);
            }
        },
    };
};

window.modalInfo = function() {
    return {
        open: false,
        titulo: '',
        mensaje: '',
        mostrar(titulo, mensaje) {
            this.titulo = titulo;
            this.mensaje = mensaje;
            this.open = true;
        },
        cerrar() {
            this.open = false;
        },
    };
};

window.modalUsuario = function() {
    return {
        open: false,
        personal: {
            nombre_completo: '',
            dni: '',
            codigo: '',
            cargo: '',
            sucursal: '',
            email: ''
        },
        cursos: [],
        cursosCargado: false,
        filtroEstado: 'todos',
        busquedaCurso: '',
        _cooldownMs: 5 * 60 * 60 * 1000,

        _cooldownKey() {
            return 'notif_pendientes_' + this.personal.dni;
        },

        getIniciales() {
            if (!this.personal.nombre_completo) return '?';
            const partes = this.personal.nombre_completo.trim().split(/\s+/).filter(Boolean);
            if (partes.length >= 2) {
                return (partes[0][0] + partes[partes.length - 1][0]).toUpperCase();
            }
            return partes[0][0].toUpperCase();
        },

        countByEstado(estado) {
            return this.cursos.filter(c => c.estado === estado).length;
        },

        get porcentajeAvance() {
            if (this.cursos.length === 0) return 0;
            const completados = this.countByEstado('finalizado');
            const enCurso = this.countByEstado('en_curso');
            return Math.round(((completados + enCurso) / this.cursos.length) * 100);
        },

        enCooldown() {
            const ultimaVez = localStorage.getItem(this._cooldownKey());
            return ultimaVez && (Date.now() - parseInt(ultimaVez)) < this._cooldownMs;
        },

        tiempoRestanteCooldown() {
            const ultimaVez = localStorage.getItem(this._cooldownKey());
            if (!ultimaVez) return '';
            const restante = this._cooldownMs - (Date.now() - parseInt(ultimaVez));
            if (restante <= 0) return '';
            const h = Math.floor(restante / 3600000);
            const m = Math.floor((restante % 3600000) / 60000);
            return h > 0 ? `${h}h ${m}m` : `${m}m`;
        },

        get cursosFiltrados() {
            return this.cursos.filter(c => {
                if (this.filtroEstado !== 'todos' && c.estado !== this.filtroEstado) return false;
                const q = this.busquedaCurso.toLowerCase().trim();
                if (q && !c.course_nombre.toLowerCase().includes(q) && !c.course_codigo.toLowerCase()
                    .includes(q)) return false;
                return true;
            });
        },

        formatearFecha(val) {
            if (!val || val === '0' || val === 0) return null;
            const d = new Date(val);
            if (isNaN(d.getTime())) return null;
            const fecha = d.toLocaleDateString('es-PE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
            const hora = d.toLocaleTimeString('es-PE', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
            return {
                fecha,
                hora
            };
        },

        mostrar(data) {
            this.personal = data;
            this.cursos = [];
            this.cursosCargado = false;
            this.filtroEstado = 'todos';
            this.busquedaCurso = '';
            this.open = true;

            fetch(`${VITE_URL_APP}/api/get-cursos-alumno/${data.dni}`)
                .then(r => r.json())
                .then(res => {
                    this.cursos = res.cursos || [];
                    this.cursosCargado = true;
                })
                .catch(() => {
                    this.cursos = [];
                    this.cursosCargado = true;
                });
        },

        notificarCursosPendientes() {
            if (this.enCooldown()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Ya fue notificado',
                    text: `Podrás volver a notificar en ${this.tiempoRestanteCooldown()}`
                });
                return;
            }
            const pendientes = this.cursos.filter(c => c.estado === 'sin_iniciar');
            if (pendientes.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Sin pendientes',
                    text: 'Este usuario inició todos sus cursos'
                });
                return;
            }
            if (!this.personal.email) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin correo',
                    text: 'Este colaborador no tiene correo registrado en el sistema'
                });
                return;
            }
            Swal.fire({
                title: 'Enviando recordatorio',
                text: `Recordatorio de ${pendientes.length} curso(s) pendiente(s)`,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            fetch(`${VITE_URL_APP}/api/mail/send`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                            'content')
                    },
                    body: JSON.stringify({
                        to: this.personal.email,
                        subject: 'Tienes cursos pendientes por iniciar',
                        template: 'recordatorio-pendientes',
                        data: {
                            full_name: this.personal.nombre_completo,
                            cursos_pendientes: pendientes.map(c => ({
                                nombre: c.course_nombre,
                                codigo: c.course_codigo
                            })),
                        },
                    }),
                })
                .then(r => r.json())
                .then(() => {
                    localStorage.setItem(this._cooldownKey(), Date.now());
                    Swal.fire({
                        icon: 'success',
                        title: 'Recordatorio enviado',
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo enviar el recordatorio'
                    });
                });
        },

        cerrar() {
            this.open = false;
        },
    };
};

window.searchPersonalSeguimiento = function() {
    return {
        open: false,
        query: "",
        results: [],

        search() {
            const q = this.query.toLowerCase().trim();
            if (q.length < 3) {
                this.results = [];
                this.open = false;
                return;
            }
            const personalData = window.tabulatorPersonal?.getData() || [];
            this.results = personalData.filter(p =>
                (p.nombre_completo || '').toLowerCase().includes(q) ||
                (p.dni || '').includes(q)
            );
            this.open = this.results.length > 0;
            if (this.open) this.posicionarDropdown();
        },

        seleccionarPrimerResultado() {
            if (this.results.length > 0) {
                this.seleccionarPersonal(this.results[0]);
            }
        },

        seleccionarPersonal(p) {
            this.open = false;
            this.query = p.nombre_completo;
            const modalEl = document.getElementById("modal-usuario");
            const alpineComponent = modalEl?._x_dataStack?. [0];
            if (alpineComponent) {
                alpineComponent.mostrar(p);
            }
        },

        posicionarDropdown() {
            this.$nextTick(() => {
                const input = this.$refs.inputBuscar;
                const dropdown = this.$refs.dropdown;
                if (!input || !dropdown) return;

                const rect = input.getBoundingClientRect();
                dropdown.style.top = (rect.bottom + window.scrollY + 4) + 'px';
                dropdown.style.left = (rect.left + window.scrollX) + 'px';
                dropdown.style.width = rect.width + 'px';
            });
        },
    };
};
</script>

@endsection
@section('script')
@vite(['resources/js/functions/capacitacion/seguimiento_matriculas.js'])
@endsection