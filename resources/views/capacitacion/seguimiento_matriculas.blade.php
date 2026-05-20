@php
use Carbon\Traits\Date;
@endphp
@extends('layouts.vertical', ['title' => 'Seguimiento de Matrículas'])
@section('css')
<!-- Estilos -->
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
            class="relative overflow-hidden rounded-2xl border border-default-200/60 bg-gradient-to-br from-white via-default-50/50 to-primary/5 shadow-sm">
            <!-- Decorative elements -->
            <div class="absolute top-0 right-0 w-72 h-72 bg-primary/5 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-1/3 w-48 h-48 bg-amber-500/5 rounded-full blur-2xl"></div>
            <div class="absolute top-1/2 right-1/4 w-32 h-32 bg-green-500/5 rounded-full blur-xl"></div>

            <!-- Grid pattern overlay -->
            <div class="absolute inset-0 opacity-[0.015]"
                style="background-image: radial-gradient(circle, currentColor 1px, transparent 1px); background-size: 24px 24px;">
            </div>

            <div class="relative p-8 flex flex-col xl:flex-row gap-6">
                <!-- Columna informativa -->
                <div class="flex flex-col gap-3 xl:w-[70%]">

                    <div
                        class="inline-flex items-center gap-2 w-fit px-3 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-semibold">
                        <div class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></div>
                        <i class="ti ti-chart-bar text-sm"></i>
                        Panel de seguimiento de matrículas
                    </div>

                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-default-900 mt-4">
                            Seguimiento de Matriculados
                        </h1>

                        <p class="mt-3 text-sm leading-7 text-default-600 max-w-3xl">
                            Supervise el progreso de los participantes matriculados en los cursos de capacitación
                            mediante indicadores informativos. Acceda rápidamente a usuarios que aún no inician,
                            participantes en progreso, aprobados y desaprobados.
                        </p>
                    </div>

                    <!-- Quick stats -->
                    <div class="flex items-center gap-6 mt-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                                <i class="ti ti-users text-lg text-primary"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-default-800">Matriculados</p>
                                <p class="text-[10px] text-default-500">Total inscritos</p>
                            </div>
                        </div>
                        <div class="w-px h-10 bg-default-200"></div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center">
                                <i class="ti ti-player-play text-lg text-amber-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-default-800">En progreso</p>
                                <p class="text-[10px] text-default-500">Activos ahora</p>
                            </div>
                        </div>
                        <div class="w-px h-10 bg-default-200"></div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-sky-500/10 flex items-center justify-center">
                                <i class="ti ti-mail text-lg text-sky-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-default-800">Notificaciones</p>
                                <p class="text-[10px] text-default-500">por correo</p>
                            </div>
                        </div>
                    </div>

                    <!-- Info badges -->
                    <div x-data="infoBadges()" class="flex flex-wrap gap-2 pt-2">
                        <div @click="abrirInfo('Participantes matriculados', 'Son todos los colaboradores que han sido registrados en un curso. Este indicador muestra el total de personas inscritas, independientemente de si han comenzado o finalizado el curso.')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-default-200 text-xs text-default-600 cursor-pointer hover:bg-primary/5 transition-colors">
                            <i class="ti ti-users text-xs text-primary"></i>
                            <span>Participantes matriculados</span>
                        </div>

                        <div @click="abrirInfo('Usuarios en progreso', 'Son aquellos colaboradores que ya ingresaron al curso y han avanzado en al menos una actividad o módulo, pero aún no lo completan. Refleja el grupo activo que está actualmente capacitándose.')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-default-200 text-xs text-default-600 cursor-pointer hover:bg-amber-50 transition-colors">
                            <i class="ti ti-player-play text-xs text-amber-600"></i>
                            <span>Usuarios en progreso</span>
                        </div>

                        <div @click="abrirInfo('Notificaciones por correo', mensajes.notificaciones)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-default-200 text-xs text-default-600 cursor-pointer hover:bg-sky-50 transition-colors">
                            <i class="ti ti-mail text-xs text-sky-600"></i>
                            <span>Notificaciones por correo</span>
                        </div>
                    </div>
                </div>

                <!-- Columna de filtros y búsquedas -->
                <div
                    class="flex flex-col gap-3 xl:w-[30%] bg-white rounded-xl p-4 border border-default-200/60 shadow-sm">
                    <div class="flex items-center gap-2 pb-2 border-b border-default-200">
                        <div class="w-1 h-4 bg-primary rounded-full"></div>
                        <span class="text-xs font-semibold text-default-700 uppercase tracking-wide">Filtros de
                            personal</span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-default-700">Sucursal</label>
                        <div class="relative">
                            <i
                                class="ti ti-building-store absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-default-400 pointer-events-none"></i>
                            <select id="filtroSucursalPersonal" :disabled="tabActivo !== 'personal'"
                                style="background-image: none !important;" class="w-full pl-8 pr-8 py-1.5 text-sm border border-default-200 rounded-lg
                           !bg-white !text-default-700 !appearance-none cursor-pointer
                           focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary/50"
                                :class="{ '!cursor-not-allowed opacity-60': tabActivo !== 'personal' }">
                                <option value="">Todas las sucursales</option>
                            </select>
                            <i
                                class="ti ti-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-default-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-default-700">Tipo de trabajador</label>
                        <div class="relative">
                            <i
                                class="ti ti-briefcase absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-default-400 pointer-events-none"></i>
                            <select id="filtroTipoPersonal" :disabled="tabActivo !== 'personal'"
                                style="background-image: none !important;" class="w-full pl-8 pr-8 py-1.5 text-sm border border-default-200 rounded-lg
                           !bg-white !text-default-700 !appearance-none cursor-pointer
                           focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary/50"
                                :class="{ '!cursor-not-allowed opacity-60': tabActivo !== 'personal' }">
                                <option value="">Todos los tipos</option>
                                <option value="Administrativo">Administrativo</option>
                                <option value="Operativo">Operativo</option>
                            </select>
                            <i
                                class="ti ti-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-default-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5" x-data="searchPersonalSeguimiento()">
                        <label class="text-xs font-medium text-default-700">Buscar personal</label>
                        <div class="relative">
                            <i
                                class="ti ti-search absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-default-400 pointer-events-none"></i>
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
                                        class="text-[10px] font-semibold tracking-widest uppercase text-default-400">Resultados</span>
                                </div>

                                <div class="max-h-56 overflow-y-auto custom-scrollbar">
                                    <template x-for="(p, idx) in results" :key="idx">
                                        <div @click="seleccionarPersonal(p)" class="flex items-center gap-3 px-3 py-2.5 cursor-pointer
                            hover:bg-primary/5 border-b border-default-100 last:border-b-0
                            transition-colors duration-100 group">

                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-default-800 truncate"
                                                    x-text="p.nombre_completo"></p>
                                                <p class="text-xs mt-0.5">
                                                    <span class="text-default-400">DNI</span>
                                                    <span
                                                        class="ml-1 bg-default-100 text-default-600 rounded px-1 py-0.5 font-mono text-[11px]"
                                                        x-text="p.dni || '—'"></span>
                                                </p>
                                            </div>

                                            <i class="ti ti-chevron-right text-xs text-default-300
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
        <div class="rounded-2xl border border-default-200/60 bg-white shadow-sm p-6">
            <!-- Tab Navigation -->
            <div class="flex items-center gap-1 mb-4">
                <button
                    @click="tabActivo = 'cursos'; setTimeout(() => { if (window.tabulatorCursos) window.tabulatorCursos.redraw(true) }, 100)"
                    :class="tabActivo === 'cursos'
                    ? 'inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-primary bg-primary/10 rounded-xl transition-all'
                    : 'inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium text-default-500 hover:text-default-700 hover:bg-default-100 rounded-xl transition-all'">
                    <i class="ti ti-book text-sm"></i>
                    Cursos registrados
                </button>
                <button
                    @click="tabActivo = 'personal'; setTimeout(() => { if (window.tabulatorPersonal) window.tabulatorPersonal.redraw(true) }, 100)"
                    :class="tabActivo === 'personal'
                    ? 'inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-primary bg-primary/10 rounded-xl transition-all'
                    : 'inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium text-default-500 hover:text-default-700 hover:bg-default-100 rounded-xl transition-all'">
                    <i class="ti ti-users text-sm"></i>
                    Lista de personal
                </button>
            </div>

            <!-- Tab 1: Cursos registrados -->
            <div x-show="tabActivo === 'cursos'" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-default-900">Cursos registrados</h2>
                    <div class="relative">
                        <i
                            class="ti ti-search absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-default-400 pointer-events-none"></i>
                        <input id="buscarCursoSeguimiento" placeholder="Buscar por nombre o código..."
                            class="w-64 pl-8 pr-3 py-2 text-sm border border-default-200 rounded-lg !bg-white !text-default-700 placeholder:text-default-300 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary/50">
                    </div>
                </div>
                <div id="tblCursosSeguimiento" class="w-full"></div>
            </div>

            <!-- Tab 2: Lista de personal -->
            <div x-show="tabActivo === 'personal'" class="min-h-[400px]"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-default-900">Lista de personal</h2>
                </div>
                <div id="tblPersonalSeguimiento" class="w-full"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal detalle de curso -->
<div id="modal-detalle-curso" x-data="modalCurso()" x-show="open" x-cloak
    class="fixed inset-0 z-[80] flex items-center justify-center p-4"
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="background: rgba(36,39,70,0.45);">

    <div class="flex flex-col shadow-2xl shadow-primary/10 rounded-2xl overflow-hidden w-full max-w-2xl border border-default-200 bg-white transition-all duration-300"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4">

        <!-- Header -->
        <div class="flex justify-between items-start py-5 px-6 border-b border-default-100">
            <div class="flex items-start gap-4 flex-1 min-w-0">
                <div
                    class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white shadow-sm shrink-0">
                    <i class="ti ti-book text-lg"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-[15px] font-semibold text-default-900 leading-tight truncate" x-text="curso.nombre">
                        Cargando...</h3>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5">
                        <p class="text-[10px] font-bold text-primary uppercase tracking-widest"
                            x-text="'Local: ' + curso.codigo">Local: -</p>
                        <span class="text-default-300">|</span>
                        <p class="text-[10px] font-bold text-primary/70 uppercase tracking-widest"
                            x-text="'Moodle: ' + curso.codigo_moodle">Moodle: -</p>
                        <template x-if="curso.responsable">
                            <div class="flex items-center gap-x-3">
                                <span class="text-default-300">|</span>
                                <p class="text-[10px] font-bold text-default-400 uppercase tracking-widest truncate"
                                    x-text="'Responsable: ' + curso.responsable">Responsable: -</p>
                            </div>
                        </template>
                    </div>
                    <template x-if="curso.fechaCreacion">
                        <p class="text-[10px] text-default-400 mt-1">
                            <i class="ti ti-calendar text-[9px] mr-0.5"></i>
                            <span x-text="'Creado: ' + curso.fechaCreacion"></span>
                        </p>
                    </template>
                </div>
            </div>
            <button type="button" @click="cerrar()"
                class="flex-shrink-0 w-7 h-7 inline-flex items-center justify-center rounded-lg text-default-400 hover:text-default-700 hover:bg-default-100 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors cursor-pointer">
                <i class="ti ti-x text-base"></i>
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="px-6 pt-5 pb-2">
            <div class="grid grid-cols-2 gap-3">
                <!-- Total Matriculados -->
                <div class="text-center p-3 rounded-xl bg-default-50 border border-default-200/60">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center mx-auto mb-2">
                        <i class="ti ti-users text-xl text-primary"></i>
                    </div>
                    <p class="text-[9px] text-default-500 uppercase tracking-widest font-bold mb-0.5">Matriculados</p>
                    <p class="text-2xl font-bold text-default-900" x-text="curso.total">0</p>
                </div>

                <!-- Sin Iniciar -->
                <div class="text-center p-3 rounded-xl bg-amber-50 border border-amber-200/50 cursor-pointer hover:shadow-md transition-shadow"
                    @click="abrirModalUsuarios('sin iniciar')">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center mx-auto mb-2">
                        <i class="ti ti-hourglass-empty text-xl text-amber-600"></i>
                    </div>
                    <p class="text-[9px] text-default-500 uppercase tracking-widest font-bold mb-0.5">Sin iniciar</p>
                    <p class="text-2xl font-bold text-amber-600" x-text="curso.totalSinIniciar">0</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="px-6 pb-2">
            <div class="p-3 rounded-xl bg-default-50 border border-default-200/60">
                <p class="text-[10px] font-bold text-default-400 uppercase tracking-widest mb-2">Acciones rápidas</p>
                <div class="flex items-center gap-2">
                    <a :href="'/capacitacion/consulta-matriculas?curso_id=' + curso.codigoInterno"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-primary/10 text-primary text-xs font-semibold hover:bg-primary/20 transition-colors">
                        <i class="ti ti-list-details text-sm"></i>
                        Ver matriculados
                    </a>
                    <button type="button" @click="abrirModalUsuarios('sin iniciar')"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-amber-50 text-amber-700 text-xs font-semibold hover:bg-amber-100 transition-colors">
                        <i class="ti ti-mail text-sm"></i>
                        Notificar pendientes
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end items-center gap-2 py-4 px-6 border-t border-default-100">
            <button type="button" @click="cerrar()"
                class="px-4 h-9 inline-flex justify-center items-center rounded-lg text-sm font-medium text-default-600 bg-default-100 hover:bg-default-200 hover:text-default-800 transition-all cursor-pointer">
                Cerrar
            </button>
            <button type="button" @click="cerrar()"
                class="px-4 h-9 inline-flex justify-center items-center rounded-lg text-sm font-semibold text-white bg-primary hover:bg-primary/90 shadow-sm shadow-primary/20 transition-all cursor-pointer">
                Entendido
            </button>
        </div>
    </div>
</div>

<!-- Modal con lista de usuarios -->
<div id="modal-lista-usuarios" x-data="modalListaUsuarios()" x-show="open" x-cloak
    class="fixed inset-0 z-[90] flex items-center justify-center p-4"
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="background: rgba(36,39,70,0.45);">

    <div class="flex flex-col shadow-2xl shadow-primary/10 rounded-2xl overflow-hidden w-full max-w-3xl border border-default-200 bg-white transition-all duration-300"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4">

        <div class="flex justify-between items-center py-5 px-6 border-b border-default-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                    <i class="ti ti-users text-amber-600"></i>
                </div>
                <h3 class="text-[15px] font-semibold text-default-900" x-text="titulo">Usuarios</h3>
            </div>
            <button type="button" @click="cerrar()"
                class="flex-shrink-0 w-7 h-7 inline-flex items-center justify-center rounded-lg text-default-400 hover:text-default-700 hover:bg-default-100 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors cursor-pointer">
                <i class="ti ti-x text-base"></i>
            </button>
        </div>

        <!-- Buscador -->
        <div class="px-6 pt-4 pb-2">
            <div class="relative">
                <i
                    class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-sm text-default-400 pointer-events-none"></i>
                <input x-model="busqueda" placeholder="Buscar por nombre o DNI..."
                    class="w-full pl-9 pr-3 py-2 text-sm border border-default-200 rounded-lg !bg-white !text-default-700 placeholder:text-default-300 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary/50" />
            </div>
        </div>

        <div class="p-6 custom-scrollbar max-h-[500px] overflow-y-auto">
            <div x-show="cargado && usuariosFiltrados.length > 0">
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-default-200 text-xs font-semibold text-default-500 uppercase tracking-widest">
                            <th class="text-left py-3 px-2 w-10">#</th>
                            <th class="text-left py-3 px-2">Nombre</th>
                            <th class="text-left py-3 px-2">Correo</th>
                            <th class="text-center py-3 px-2 w-40">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default-100">
                        <template x-for="(user, index) in usuariosFiltrados" :key="index">
                            <tr class="hover:bg-default-50 transition-colors">
                                <td class="py-3 px-2 text-default-400 text-xs font-mono" x-text="index + 1"></td>
                                <td class="py-3 px-2 font-medium text-default-800 cursor-pointer hover:text-primary transition-colors"
                                    @click="abrirCursoPersonal(user)">
                                    <span x-text="user.full_name"></span>
                                </td>
                                <td class="py-3 px-2 text-default-500 text-xs" x-text="user.email"></td>
                                <td class="py-3 px-2 text-center">
                                    <button type="button" @click="!estaEnCooldown(user) && notificarUsuario(user)"
                                        :disabled="estaEnCooldown(user)"
                                        :class="estaEnCooldown(user)
        ? 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-default-100 text-default-400 cursor-not-allowed text-xs font-semibold'
        : 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-sky-500 text-white hover:bg-sky-600 transition-all text-xs font-semibold shadow-sm'">
                                        <i :class="estaEnCooldown(user) ? 'ti ti-clock' : 'ti ti-mail'"></i>
                                        <span
                                            x-text="estaEnCooldown(user) ? 'Espera ' + tiempoRestante(user) : 'Notificar'"></span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div x-show="!cargado" class="text-center py-10 text-default-400 text-sm">Cargando usuarios...</div>
            <div x-show="cargado && usuariosFiltrados.length === 0" class="text-center py-10 text-default-400 text-sm">
                No se
                encontraron usuarios</div>
        </div>

        <div class="flex justify-end items-center gap-2 py-4 px-6 border-t border-default-100">
            <button type="button" @click="cerrar()"
                class="px-4 h-9 inline-flex justify-center items-center rounded-lg text-sm font-medium text-default-600 bg-default-100 hover:bg-default-200 transition-all cursor-pointer">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Modal informativo -->
<div id="modal-info" x-data="modalInfo()" x-show="open" x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="background: rgba(36,39,70,0.45);">

    <div class="flex flex-col shadow-2xl shadow-primary/10 rounded-2xl overflow-hidden w-full max-w-lg border border-default-200 bg-white transition-all duration-300"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4">

        <div class="flex justify-between items-center py-5 px-6 border-b border-default-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                    <i class="ti ti-info-circle text-primary"></i>
                </div>
                <h3 class="text-[15px] font-semibold text-default-900" x-text="titulo"></h3>
            </div>
            <button type="button" @click="cerrar()"
                class="flex-shrink-0 w-7 h-7 inline-flex items-center justify-center rounded-lg text-default-400 hover:text-default-700 hover:bg-default-100 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors cursor-pointer">
                <i class="ti ti-x text-base"></i>
            </button>
        </div>

        <div class="p-6 text-sm text-default-600 leading-relaxed" x-html="mensaje"></div>

        <div class="flex justify-end items-center gap-2 py-4 px-6 border-t border-default-100">
            <button type="button" @click="cerrar()"
                class="px-4 h-9 inline-flex justify-center items-center rounded-lg text-sm font-semibold text-white bg-primary hover:bg-primary/90 shadow-sm shadow-primary/20 transition-all cursor-pointer">
                Entendido
            </button>
        </div>
    </div>
</div>

<!-- Modal de usuario encontrado -->
<div id="modal-usuario" x-data="modalUsuario()" x-show="open" x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="background: rgba(36,39,70,0.45);">

    <div class="flex flex-col shadow-2xl shadow-primary/10 rounded-2xl overflow-hidden w-full max-w-5xl border border-default-200 bg-white transition-all duration-300 max-h-[90vh]"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4">

        <!-- Header con perfil -->
        <div class="flex justify-between items-start py-5 px-6 border-b border-default-100 shrink-0">
            <div class="flex items-start gap-4 flex-1 min-w-0">
                <!-- Avatar con iniciales -->
                <div
                    class="w-12 h-12 rounded-xl bg-primary text-white flex items-center justify-center font-bold text-lg shadow-sm shrink-0">
                    <span x-text="getIniciales()"></span>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-[15px] font-semibold text-default-900 leading-tight truncate"
                        x-text="personal.nombre_completo">
                        Cargando...</h3>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5">
                        <p class="text-[10px] font-bold text-default-500 uppercase tracking-widest"
                            x-text="'DNI: ' + personal.dni">DNI: -</p>
                        <span class="text-default-300">|</span>
                        <p class="text-[10px] font-bold text-primary uppercase tracking-widest"
                            x-text="'Código: ' + personal.codigo">Código: -</p>
                        <template x-if="personal.email">
                            <div class="flex items-center gap-x-2">
                                <span class="text-default-300">|</span>
                                <p class="text-[10px] font-bold text-default-400 uppercase tracking-widest truncate"
                                    x-text="personal.email"></p>
                                <button type="button" @click="copiarCorreo(personal.email, $event)"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded text-default-400 hover:text-primary hover:bg-primary/10 transition-colors cursor-pointer shrink-0"
                                    title="Copiar correo">
                                    <i class="ti ti-copy text-[10px]"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <button type="button" @click="cerrar()"
                class="flex-shrink-0 w-7 h-7 inline-flex items-center justify-center rounded-lg text-default-400 hover:text-default-700 hover:bg-default-100 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors cursor-pointer">
                <i class="ti ti-x text-base"></i>
            </button>
        </div>

        <!-- Info Personal + Stats Resumen -->
        <div class="px-6 pt-4 pb-2 shrink-0">
            <div class="flex items-start gap-4">
                <!-- Datos del personal -->
                <div class="flex-1 grid grid-cols-3 gap-2">
                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-default-50 border border-default-200/60">
                        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                            <i class="ti ti-building-store text-sm text-primary"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] text-default-400 uppercase tracking-wider font-bold">Sucursal</p>
                            <p class="text-xs font-semibold text-default-700 truncate"
                                x-text="personal.sucursal || 'No registrada'"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-default-50 border border-default-200/60">
                        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                            <i class="ti ti-briefcase text-sm text-primary"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] text-default-400 uppercase tracking-wider font-bold">Tipo de trabajador
                            </p>
                            <p class="text-xs font-semibold text-default-700 truncate"
                                x-text="personal.cargo || 'No registrado'"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-default-50 border border-default-200/60">
                        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                            <i class="ti ti-mail text-sm text-primary"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] text-default-400 uppercase tracking-wider font-bold">Email</p>
                            <p class="text-xs font-semibold text-default-700 truncate"
                                x-text="personal.email || 'No registrado'"></p>
                        </div>
                    </div>
                </div>

                <!-- Divider -->
                <div class="w-px h-16 bg-default-200 self-center"></div>

                <!-- Stats de cursos -->
                <div class="flex items-center gap-3">
                    <div class="text-center px-2">
                        <p class="text-lg font-bold text-primary" x-text="cursos.length">0</p>
                        <p class="text-[8px] text-default-400 uppercase tracking-wider font-bold">Total</p>
                    </div>
                    <div class="text-center px-2">
                        <p class="text-lg font-bold text-amber-500" x-text="countByEstado('sin_iniciar')">0</p>
                        <p class="text-[8px] text-default-400 uppercase tracking-wider font-bold">Sin iniciar</p>
                    </div>
                    <div class="text-center px-2">
                        <p class="text-lg font-bold text-green-500" x-text="countByEstado('en_curso')">0</p>
                        <p class="text-[8px] text-default-400 uppercase tracking-wider font-bold">En curso</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cursos del alumno -->
        <div class="flex flex-col flex-1 min-h-0">
            <div class="shrink-0 px-6 pt-4 pb-2">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-bold text-default-600 uppercase tracking-wider flex items-center gap-2">
                        <i class="ti ti-book text-primary text-sm"></i>
                        Cursos matriculados
                    </h4>
                    <span class="text-[10px] text-default-400 font-medium"
                        x-text="cursosFiltrados.length + ' de ' + cursos.length + ' curso(s)'"></span>
                </div>

                <div x-show="cursosCargado" class="flex flex-wrap items-center gap-2 pb-2 border-b border-default-100">
                    <!-- Filtros tipo pills -->
                    <div class="flex items-center gap-1">
                        <button type="button" @click="filtroEstado = 'todos'"
                            :class="filtroEstado === 'todos'
                                ? 'px-3 py-1.5 rounded-lg bg-primary text-white text-[10px] font-semibold transition-all'
                                : 'px-3 py-1.5 rounded-lg bg-default-100 text-default-500 text-[10px] font-semibold hover:bg-default-200 transition-all'">
                            Todos
                        </button>
                        <button type="button" @click="filtroEstado = 'sin_iniciar'"
                            :class="filtroEstado === 'sin_iniciar'
                                ? 'px-3 py-1.5 rounded-lg bg-amber-500 text-white text-[10px] font-semibold transition-all'
                                : 'px-3 py-1.5 rounded-lg bg-amber-50 text-amber-600 text-[10px] font-semibold hover:bg-amber-100 transition-all'">
                            <i class="ti ti-clock mr-0.5"></i> Sin iniciar
                        </button>
                        <button type="button" @click="filtroEstado = 'en_curso'"
                            :class="filtroEstado === 'en_curso'
                                ? 'px-3 py-1.5 rounded-lg bg-green-500 text-white text-[10px] font-semibold transition-all'
                                : 'px-3 py-1.5 rounded-lg bg-green-50 text-green-600 text-[10px] font-semibold hover:bg-green-100 transition-all'">
                            <i class="ti ti-player-play mr-0.5"></i> En curso
                        </button>
                    </div>
                    <div class="flex-1"></div>
                    <div class="relative">
                        <i
                            class="ti ti-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-default-400 pointer-events-none"></i>
                        <input x-model="busquedaCurso" placeholder="Buscar curso..."
                            class="w-48 pl-7 pr-3 py-1.5 text-xs border border-default-200 rounded-lg !bg-white !text-default-700 placeholder:text-default-300 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary/50">
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar px-6 pb-4">
                <div x-show="!cursosCargado" class="flex flex-col items-center justify-center py-10 text-default-400">
                    <i class="ti ti-loader animate-spin text-2xl mb-2"></i>
                    <p class="text-sm">Cargando cursos...</p>
                </div>

                <div x-show="cursosCargado && cursos.length === 0"
                    class="flex flex-col items-center justify-center py-10 text-default-400">
                    <i class="ti ti-book-off text-3xl opacity-30 mb-2"></i>
                    <p class="text-sm">No se encontraron cursos para este alumno</p>
                </div>

                <div x-show="cursosCargado && cursosFiltrados.length === 0 && cursos.length > 0"
                    class="flex flex-col items-center justify-center py-10 text-default-400">
                    <i class="ti ti-filter-off text-3xl opacity-30 mb-2"></i>
                    <p class="text-sm">Ningún curso coincide con los filtros</p>
                </div>

                <div x-show="cursosCargado && cursosFiltrados.length > 0" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-default-50 sticky top-0 z-10">
                            <tr
                                class="border-b border-default-200 text-[10px] font-semibold text-default-500 uppercase tracking-widest">
                                <th class="text-left py-2.5 px-3 w-10">#</th>
                                <th class="text-left py-2.5 px-3">Nombre del curso</th>
                                <th class="text-center py-2.5 px-3 w-32">Estado</th>
                                <th class="text-center py-2.5 px-3 w-32">Último acceso</th>
                                <th class="text-center py-2.5 px-3 w-28">Matrícula</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-default-100">
                            <template x-for="(c, index) in cursosFiltrados" :key="c.course_id">
                                <tr class="hover:bg-default-50 transition-colors">
                                    <td class="py-2.5 px-3 text-default-400 text-xs font-mono" x-text="index + 1"></td>
                                    <td class="py-2.5 px-3">
                                        <p class="font-medium text-default-800 text-xs" x-text="c.course_nombre"></p>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span x-show="c.estado === 'en_curso'"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[9px] font-semibold bg-green-50 text-green-700 border border-green-200">
                                            <i class="ti ti-player-play text-[8px]"></i>
                                            En curso
                                        </span>
                                        <span x-show="c.estado === 'sin_iniciar'"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[9px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            <i class="ti ti-clock text-[8px]"></i>
                                            Sin iniciar
                                        </span>
                                        <span x-show="c.estado === 'finalizado'"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[9px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
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
                                            class="text-[10px] text-default-300">—</span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <template x-if="formatearFecha(c.fecha_inicio_matricula)">
                                            <span style="font-size: 10px; color: #6b7280;"
                                                x-text="formatearFecha(c.fecha_inicio_matricula).fecha"></span>
                                        </template>
                                        <span x-show="!formatearFecha(c.fecha_inicio_matricula)"
                                            class="text-[10px] text-default-300">—</span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-between items-center gap-2 py-4 px-6 border-t border-default-100 bg-white shrink-0">
            <div class="text-xs text-default-400">
                <i class="ti ti-info-circle mr-1"></i>
                <span x-show="countByEstado('sin_iniciar') > 0"
                    x-text="countByEstado('sin_iniciar') + ' curso(s) pendiente(s)'"></span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="!enCooldown() && notificarCursosPendientes()" :disabled="enCooldown()"
                    :class="enCooldown()
                        ? 'px-4 h-9 inline-flex justify-center items-center rounded-lg font-semibold bg-default-100 text-default-400 cursor-not-allowed text-xs'
                        : 'px-4 h-9 inline-flex justify-center items-center rounded-lg font-semibold bg-sky-500 text-white hover:bg-sky-600 transition-all text-xs shadow-sm'">
                    <i :class="enCooldown() ? 'ti ti-clock mr-1.5' : 'ti ti-mail mr-1.5'"></i>
                    <span
                        x-text="enCooldown() ? 'Espera ' + tiempoRestanteCooldown() : 'Notificar sobre cursos pendientes'"></span>
                </button>
                <button type="button" @click="cerrar()"
                    class="px-4 h-9 inline-flex justify-center items-center rounded-lg font-semibold bg-default-100 text-default-600 hover:bg-default-200 transition-all text-xs cursor-pointer">
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
                    this.curso.totalCompletados = Math.max(0, this.curso.total - res.data.total_sin_iniciar -
                        res.data.total_en_progreso);
                    this.curso.porcentajeProgreso = this.curso.total > 0 ?
                        Math.round(((this.curso.totalEnProgreso + this.curso.totalCompletados) / this.curso
                            .total) * 100) :
                        0;
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
            const modalUsuarios = document.getElementById('modal-lista-usuarios')._x_dataStack?. [0];
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
        busqueda: '',
        cargado: false,
        notificados: {},
        _cooldownMs: 5 * 60 * 60 * 1000,

        get usuariosFiltrados() {
            if (!this.busqueda) return this.usuarios;
            const q = this.busqueda.toLowerCase().trim();
            return this.usuarios.filter(u =>
                (u.full_name || '').toLowerCase().includes(q) ||
                (u.username || '').toLowerCase().includes(q) ||
                (u.idnumber || '').toLowerCase().includes(q)
            );
        },

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

        copiarCorreo(email, event) {
            navigator.clipboard.writeText(email).then(() => {
                const btn = event.currentTarget;
                const icon = btn.querySelector('i');
                icon.className = 'ti ti-check text-[10px]';
                btn.classList.add('text-green-500', 'bg-green-50');
                setTimeout(() => {
                    icon.className = 'ti ti-copy text-[10px]';
                    btn.classList.remove('text-green-500', 'bg-green-50');
                }, 1500);
            });
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