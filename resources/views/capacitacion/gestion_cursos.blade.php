@extends('layouts.vertical', ['title' => 'Gestión de cursos'])
@section('css')
<style>
    [x-cloak] {
        display: none !important;
    }

    /* ─────────────────────────────────────────────────────────────
   TABULATOR
───────────────────────────────────────────────────────────── */

    .tabulator {
        border: 1px solid rgba(226, 232, 240, .8) !important;
        border-radius: 18px !important;
        overflow: hidden !important;
        background: #ffffff !important;

        box-shadow:
            0 1px 2px rgba(15, 23, 42, .03),
            0 10px 30px rgba(15, 23, 42, .04) !important;

        font-size: 13px !important;
    }

    /* ───────────────── Header ───────────────── */

    .tabulator-header {
        background:
            linear-gradient(to bottom,
                #fcfcfd 0%,
                #f8fafc 100%) !important;

        border-top: none !important;
        border-left: none !important;
        border-right: none !important;
        border-bottom: 1px solid #eef2f7 !important;

        padding: 6px 0 4px 0 !important;
    }

    .tabulator-header .tabulator-col {
        background: transparent !important;
        border-right: none !important;
        min-height: 48px !important;
    }

    .tabulator-header .tabulator-col-content {
        padding: 12px 14px !important;
    }

    .tabulator-header .tabulator-col-title {
        width: 100% !important;

        text-align: left !important;

        font-size: 10px !important;
        font-weight: 800 !important;

        letter-spacing: .12em !important;
        text-transform: uppercase !important;

        color: #64748b !important;
    }

    /* Flechas sort */

    .tabulator-col-sorter {
        color: #94a3b8 !important;
    }

    /* ───────────────── Tabla ───────────────── */

    .tabulator-tableholder {
        overflow-x: auto !important;
        overflow-y: hidden !important;

        scrollbar-width: thin;
        scrollbar-color: rgba(100, 116, 139, .35) transparent;
    }

    .tabulator-tableholder::-webkit-scrollbar {
        height: 8px;
    }

    .tabulator-tableholder::-webkit-scrollbar-track {
        background: transparent;
    }

    .tabulator-tableholder::-webkit-scrollbar-thumb {
        background: rgba(100, 116, 139, .35);
        border-radius: 999px;
    }

    /* ───────────────── Rows ───────────────── */

    .tabulator-row {
        background: #ffffff !important;

        border-left: none !important;
        border-right: none !important;
        border-bottom: 1px solid #f8fafc !important;

        transition:
            background .18s ease,
            transform .18s ease,
            box-shadow .18s ease !important;
    }

    .tabulator-row:last-child {
        border-bottom: none !important;
    }

    .tabulator-row:hover {
        background:
            linear-gradient(to right,
                rgba(59, 130, 246, .04),
                rgba(59, 130, 246, .01)) !important;

        box-shadow:
            inset 3px 0 0 #2563eb !important;
    }

    /* ───────────────── Cells ───────────────── */

    .tabulator-row .tabulator-cell {
        border-right: none !important;

        padding-top: 14px !important;
        padding-bottom: 14px !important;
        padding-left: 14px !important;
        padding-right: 14px !important;

        vertical-align: middle !important;

        color: #1e293b !important;
    }

    /* ───────────────── Footer ───────────────── */

    .tabulator-footer {
        background: #ffffff !important;

        border-top: 1px solid #eef2f7 !important;
        border-left: none !important;
        border-right: none !important;
        border-bottom: none !important;

        padding: 14px 18px !important;
    }

    /* Contenido footer */

    .tabulator-footer-contents {
        display: flex !important;

        align-items: center !important;
        justify-content: space-between !important;

        flex-wrap: wrap !important;

        gap: 12px !important;
    }

    /* Counter */

    .tabulator-footer .tabulator-page-counter {
        color: #475569 !important;

        font-size: 12px !important;
        font-weight: 700 !important;
    }

    /* Paginador */

    .tabulator-footer .tabulator-paginator {
        display: flex !important;

        align-items: center !important;
        gap: 6px !important;
    }

    /* Select */

    .tabulator-footer select.tabulator-page-size {
        height: 38px !important;

        padding: 0 36px 0 14px !important;

        border-radius: 12px !important;
        border: 1px solid #e2e8f0 !important;

        background-color: #ffffff !important;

        font-size: 12px !important;
        font-weight: 700 !important;

        color: #334155 !important;

        appearance: none !important;

        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.7' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;

        background-position: right .7rem center !important;
        background-repeat: no-repeat !important;
        background-size: 1.1em 1.1em !important;

        transition:
            border-color .18s ease,
            box-shadow .18s ease !important;
    }

    .tabulator-footer select.tabulator-page-size:hover {
        border-color: #93c5fd !important;
    }

    .tabulator-footer select.tabulator-page-size:focus {
        outline: none !important;

        border-color: #3b82f6 !important;

        box-shadow:
            0 0 0 3px rgba(59, 130, 246, .12) !important;
    }

    /* Botones páginas */

    .tabulator-footer .tabulator-page {
        min-width: 36px !important;
        height: 36px !important;

        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;

        border-radius: 11px !important;

        border: 1px solid #e2e8f0 !important;

        background: #ffffff !important;

        color: #475569 !important;

        font-size: 12px !important;
        font-weight: 700 !important;

        transition:
            all .18s ease !important;
    }

    .tabulator-footer .tabulator-page:hover:not(.active) {
        background: #f8fafc !important;
        border-color: #cbd5e1 !important;

        transform: translateY(-1px);
    }

    .tabulator-footer .tabulator-page.active {
        background:
            linear-gradient(135deg,
                #2563eb 0%,
                #1d4ed8 100%) !important;

        border-color: #1d4ed8 !important;

        color: #ffffff !important;

        box-shadow:
            0 6px 16px rgba(37, 99, 235, .25) !important;
    }

    /* ───────────────── Placeholder ───────────────── */

    .tabulator-placeholder {
        min-height: 110px !important;

        display: flex !important;
        align-items: center !important;
        justify-content: center !important;

        background: #ffffff !important;
    }

    .tabulator-placeholder span {
        color: #94a3b8 !important;

        font-size: 13px !important;
        font-weight: 600 !important;
    }
</style>
@endsection

@include('layouts.shared/page-title', ['subtitle' => 'Capacitación', 'title' => 'Gestión de cursos'])

@section('content')
<div class="px-6 py-6">
    {{-- Header --}}
    <div
        class="relative overflow-hidden rounded-2xl border border-default-200/60 bg-gradient-to-br from-white via-default-50/50 to-primary/5 shadow-sm mb-6">
        <div class="absolute top-0 right-0 w-72 h-72 bg-primary/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-1/3 w-48 h-48 bg-amber-500/5 rounded-full blur-2xl"></div>
        <div class="absolute top-1/2 right-1/4 w-32 h-32 bg-green-500/5 rounded-full blur-xl"></div>
        <div class="absolute inset-0 opacity-[0.015]"
            style="background-image: radial-gradient(circle, currentColor 1px, transparent 1px); background-size: 24px 24px;">
        </div>

        <div class="relative p-8">
            <div class="flex items-start justify-between gap-6">
                <div class="flex-1">
                    <div
                        class="inline-flex items-center gap-2 w-fit px-3 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-semibold">
                        <div class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></div>
                        <i class="ti ti-book text-sm"></i>
                        Gestión de cursos
                    </div>

                    <h1 class="text-3xl font-bold tracking-tight text-default-900 mt-4">
                        Gestión de Cursos
                    </h1>

                    <p class="mt-3 text-sm leading-7 text-default-600 max-w-3xl">
                        Creación de cursos especificando sus características y plan de capacitación al que pertenecen.
                    </p>

                    <div class="flex items-center gap-6 mt-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                                <i class="ti ti-book-2 text-lg text-primary"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-default-800">Cursos</p>
                                <p class="text-[10px] text-default-500">disponibles</p>
                            </div>
                        </div>
                        <div class="w-px h-10 bg-default-200"></div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center">
                                <i class="ti ti-tag text-lg text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-default-800">Planes</p>
                                <p class="text-[10px] text-default-500">de capacitación</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hidden xl:flex flex-col items-center justify-center shrink-0">
                    <div
                        class="w-20 h-20 rounded-2xl bg-gradient-to-br from-primary/10 to-primary/5 flex items-center justify-center">
                        <i class="ti ti-book text-4xl text-primary/60"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-6 w-full items-start">
        <div class="card w-full">
            <div class="card-header flex flex-wrap items-center justify-between gap-4">
                <h4 class="card-title">Lista de cursos</h4>
                <button type="button" onclick="abrirModalRegistro()"
                    class="btn rounded-full bg-primary text-white hover:bg-primary-700 transition-all flex items-center gap-2 px-5 shadow-sm">
                    <i class="bx bx-plus-circle text-lg"></i> Registrar nuevo curso
                </button>
            </div>

            <div class="card-body">
                <div x-data="{
                    soloEliminados: false,
                    filtroArea: '',
                    filtroTipoCurso: '',
                    filtroCategoria: '',
                    filtroFechaDesde: '',
                    filtroFechaHasta: '',
                    tipos: [],
                    areas: []
                }" x-init="$nextTick(() => listarCursos(1, '', '', '', '', ''))" @tipo-curso-loaded.window="tipos = $event.detail"
                    @areas-loaded.window="areas = $event.detail"
                    class="flex flex-wrap items-center justify-between gap-6">
                    <div class="flex items-center">
                        {{-- <input 
                    class="form-switch" 
                    type="checkbox" 
                    role="switch" 
                    id="chkEliminados"
                    x-model="soloEliminados"
                > --}}
                        <input class="form-switch" type="checkbox" role="switch" id="chkEliminados" x-model="soloEliminados"
                            @change="listarCursos(soloEliminados ? 0 : 1, filtroArea, filtroTipoCurso, filtroCategoria, filtroFechaDesde, filtroFechaHasta)">
                        <label class="ms-1.5 font-medium text-sm text-gray-700" for="chkEliminados">
                            Solo eliminados
                        </label>
                    </div>

                    <div class="flex flex-wrap items-end gap-4 ml-auto flex-1 justify-end">
                        <div class="flex flex-col min-w-[200px]">
                            <label class="text-sm font-medium text-gray-700 mb-1">
                                Plan de Capacitación
                            </label>
                            <select x-model="filtroTipoCurso"
                                @change="listarCursos(soloEliminados ? 0 : 1, filtroArea, filtroTipoCurso, filtroCategoria, filtroFechaDesde, filtroFechaHasta)"
                                class="w-full bg-white border border-gray-300 rounded-lg shadow-sm px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">-- Todos --</option>
                                <template x-for="tipo in tipos" :key="tipo.codigo">
                                    <option :value="tipo.codigo" x-text="tipo.descripcion"></option>
                                </template>
                            </select>
                        </div>

                        <div class="flex flex-col min-w-[200px]">
                            <label class="text-sm font-medium text-gray-700 mb-1">
                                Sistema de Gestión
                            </label>
                            <select x-model="filtroArea"
                                @change="listarCursos(soloEliminados ? 0 : 1, filtroArea, filtroTipoCurso, filtroCategoria, filtroFechaDesde, filtroFechaHasta)"
                                class="w-full bg-white border border-gray-300 rounded-lg shadow-sm px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">-- Todas --</option>
                                <template x-for="area in areas" :key="area.codigo">
                                    <option :value="area.codigo" x-text="area.descripcion"></option>
                                </template>
                            </select>
                        </div>

                        <div class="flex flex-col">
                            <label class="text-sm font-medium text-gray-700 mb-1">Tipo de curso</label>
                            <select x-model="filtroCategoria"
                                @change="listarCursos(soloEliminados ? 0 : 1, filtroArea, filtroTipoCurso, filtroCategoria, filtroFechaDesde, filtroFechaHasta)"
                                class="w-full bg-white border border-gray-300 rounded-lg shadow-sm px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">-- Todas --</option>
                                <option value="INDUCCIÓN">Inducción</option>
                                <option value="CHARLA">Charla</option>
                                <option value="CAPACITACIÓN">Capacitación</option>
                                <option value="ENTRENAMIENTO">Entrenamiento</option>
                                <option value="SIMULACROS DE EMERGENCIA">Simulacros de emergencia</option>
                            </select>
                        </div>

                        <div class="flex flex-col">
                            <label class="text-sm font-medium text-gray-700 mb-1">Fecha creación</label>
                            <div class="flex items-center gap-2">
                                <input type="date" x-model="filtroFechaDesde"
                                    @change="listarCursos(soloEliminados ? 0 : 1, filtroArea, filtroTipoCurso, filtroCategoria, filtroFechaDesde, filtroFechaHasta)"
                                    class="w-full bg-white border border-gray-300 rounded-lg shadow-sm px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Desde">
                                <span class="text-gray-400 text-xs font-semibold">—</span>
                                <input type="date" x-model="filtroFechaHasta"
                                    @change="listarCursos(soloEliminados ? 0 : 1, filtroArea, filtroTipoCurso, filtroCategoria, filtroFechaDesde, filtroFechaHasta)"
                                    class="w-full bg-white border border-gray-300 rounded-lg shadow-sm px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Hasta">
                            </div>
                        </div>
                    </div>

                    {{-- <div x-effect="listarCursos( soloEliminados ? 0 : 1, filtroArea, filtroTipoCurso )"></div> --}}
                </div>

                <div class="mt-5 overflow-x-auto w-full relative" id="tblCursosContainer">
                    <div id="tblCursosLoader"
                        class="hidden absolute inset-0 flex items-center justify-center bg-white/90 z-10 rounded-xl transition-opacity duration-300">
                        <div class="flex flex-col items-center gap-3">
                            <div
                                class="w-10 h-10 border-[3px] border-primary/20 border-t-primary rounded-full animate-spin">
                            </div>
                            <span class="text-sm font-semibold text-gray-500">Cargando cursos...</span>
                        </div>
                    </div>
                    <div id="tblCursos"></div>
                </div>
            </div>
        </div>

        <div x-data="modalGestionCurso()"
            @cambiar-panel.window="panel = $event.detail.panel; tituloProgramacion = $event.detail.titulo || ''; mostrarBotonRegistrarListado = $event.detail.mostrarBtn === undefined ? true : $event.detail.mostrarBtn"
            class="w-full">

            <!-- ============================================================ -->
            <!-- MODAL 1: REGISTRAR CURSO (independiente)                     -->
            <!-- ============================================================ -->
            <div id="modalRegistroCurso" class="contents"
                x-data="{ showModal: false }"
                @open-modal-registro.window="showModal = true"
                @close-modal-registro.window="showModal = false">

                <div x-show="showModal" x-cloak
                    class="fixed inset-0 z-[120] flex items-center justify-center p-4"
                    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    style="background: rgba(36,39,70,0.45);">

                    <div
                        class="flex flex-col shadow-2xl shadow-primary/10 rounded-2xl overflow-hidden w-full max-w-7xl border border-default-200 bg-white transition-all duration-300 max-h-[90vh]"
                        x-data="formCursoGestion('reg')" @submit.prevent
                        x-init="$nextTick(() => { $watch('tipoCurso', value => { if (value != '5') targetGroup = 'TODOS'; }); })"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 translate-y-4">

                        <!-- Header -->
                        <div
                            class="flex justify-between items-start py-5 px-6 border-b border-default-100 shrink-0 bg-gradient-to-r from-white to-default-50/30">
                            <div>
                                <h3 class="text-xl font-bold text-default-900">Registrar curso</h3>
                                <p class="text-sm text-default-500 mt-1">Registro de cursos del plan de capacitacion</p>
                            </div>
                            <button type="button" @click="cerrarModalRegistro()"
                                class="flex-shrink-0 w-8 h-8 inline-flex items-center justify-center rounded-lg text-default-400 hover:text-default-700 hover:bg-default-100 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors cursor-pointer">
                                <i class="ti ti-x text-lg"></i>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-6 overflow-y-auto custom-scrollbar flex-1 bg-white">
                            @include('capacitacion.partials.form_curso_campos', ['prefijo' => 'reg'])
                        </div>

                        <!-- Footer -->
                        <div
                            class="flex justify-end items-center gap-3 px-6 py-4 border-t border-default-100 shrink-0 bg-default-50/50">
                            <button type="button" @click="cerrarModalRegistro()"
                                class="h-9 px-4 inline-flex items-center justify-center gap-1.5 bg-white border border-default-200 text-default-700 text-sm font-medium rounded-lg shadow-sm hover:bg-default-50 transition cursor-pointer">
                                Cancelar
                            </button>
                            <span :title="!formularioCompleto ? tituloCamposFaltantes : ''">
                                <button type="submit" @click="registrar" :disabled="!formularioCompleto"
                                    class="h-9 px-4 inline-flex items-center justify-center gap-1.5 bg-primary text-white text-sm font-medium rounded-lg shadow-sm hover:bg-primary/90 focus:ring-2 focus:ring-primary/30 transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                    :class="!formularioCompleto ? 'pointer-events-none' : ''">
                                    <i class="ti ti-device-floppy"></i> Registrar Curso
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- MODAL 2: ACTUALIZAR / EDITAR CURSO (independiente)           -->
            <!-- ============================================================ -->
            <div id="modalEditarCurso" class="contents"
                x-data="{ showModal: false }"
                @open-modal-editar.window="showModal = true"
                @close-modal-editar.window="showModal = false">

                <div x-show="showModal" x-cloak
                    class="fixed inset-0 z-[120] flex items-center justify-center p-4"
                    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    style="background: rgba(36,39,70,0.45);">

                    <div
                        class="flex flex-col shadow-2xl shadow-primary/10 rounded-2xl overflow-hidden w-full max-w-7xl border border-default-200 bg-white transition-all duration-300 max-h-[90vh]"
                        x-data="formCursoGestion('edi')" @submit.prevent
                        x-init="$nextTick(() => { $watch('tipoCurso', value => { if (value != '5') targetGroup = 'TODOS'; }); })"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 translate-y-4">

                        <!-- Header -->
                        <div
                            class="flex justify-between items-start py-5 px-6 border-b border-default-100 shrink-0 bg-gradient-to-r from-white to-default-50/30">
                            <div>
                                <h3 class="text-xl font-bold text-default-900">Actualizar curso</h3>
                                <p class="text-sm text-default-500 mt-1">Actualizacion de datos del curso seleccionado</p>
                            </div>
                            <button type="button" @click="cerrarModalEdicion()"
                                class="flex-shrink-0 w-8 h-8 inline-flex items-center justify-center rounded-lg text-default-400 hover:text-default-700 hover:bg-default-100 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors cursor-pointer">
                                <i class="ti ti-x text-lg"></i>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-6 overflow-y-auto custom-scrollbar flex-1 bg-white">
                            @include('capacitacion.partials.form_curso_campos', ['prefijo' => 'edi'])
                        </div>

                        <!-- Footer -->
                        <div
                            class="flex justify-end items-center gap-3 px-6 py-4 border-t border-default-100 shrink-0 bg-default-50/50">
                            <button type="button" @click="cerrarModalEdicion()"
                                class="h-9 px-4 inline-flex items-center justify-center gap-1.5 bg-white border border-default-200 text-default-700 text-sm font-medium rounded-lg shadow-sm hover:bg-default-50 transition cursor-pointer">
                                Cancelar
                            </button>
                            <button type="button" @click="editarFormGestionCurso()"
                                class="h-9 px-4 inline-flex items-center justify-center gap-1.5 bg-amber-500 text-white text-sm font-medium rounded-lg shadow-sm hover:bg-amber-600 transition cursor-pointer">
                                <i class="ti ti-pencil"></i> Actualizar curso
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel Apertura de Ciclo (Primer Ciclo) - Corregido x-if por x-show para que el listener sea persistente -->
            <div x-show="panel === 'apertura_manual'" x-data="modalApertura()" x-init="init()"
                @open-apertura-modal.window="openModal($event.detail)" style="display: none;"
                class="fixed inset-0 z-[1040] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100">

                <div class="card w-full max-w-lg shadow-2xl border border-slate-200 overflow-hidden"
                    @click.away="closeModal()">
                    <div class="card-header bg-white border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h4 class="card-title">Aperturar el curso: <span x-text="cursoNombre"
                                    class="text-primary font-bold"></span></h4>
                            <button type="button" @click="closeModal()" title="Cerrar y volver a Registro"
                                class="btn btn-sm rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200">
                                <i class="bx bx-x text-lg"></i>
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="flex flex-col h-full min-h-[300px]">
                            <div class="flex-grow flex flex-col items-center pt-6">
                                <div
                                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 mb-5">
                                    <i class="bx bx-calendar-event text-primary text-2xl"></i>
                                </div>

                                <div x-show="esPeriodico" class="w-full max-w-sm mb-4">
                                    <label for="fecha_inicio_modal"
                                        class="block text-sm font-semibold leading-6 text-gray-900 text-center mb-2">Fecha
                                        de inicio de capacitación</label>
                                    <input type="date" x-model="fechaInicio" id="fecha_inicio_modal"
                                        :min="fechaMinima"
                                        class="block w-full rounded-md border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary text-center text-base sm:leading-6">
                                </div>

                                <div x-show="!esPeriodico" class="w-full max-w-sm mb-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="fecha_inicio_no_periodico"
                                                class="block text-sm font-semibold leading-6 text-gray-900 text-center mb-2">Fecha
                                                de inicio</label>
                                            <input type="date" x-model="fechaInicio" id="fecha_inicio_no_periodico"
                                                :min="fechaMinima"
                                                class="block w-full rounded-md border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary text-center text-base sm:leading-6">
                                        </div>
                                        <div>
                                            <label for="fecha_fin_no_periodico"
                                                class="block text-sm font-semibold leading-6 text-gray-900 text-center mb-2">Fecha
                                                de fin</label>
                                            <input type="date" x-model="fechaFin" id="fecha_fin_no_periodico"
                                                :min="fechaMinima"
                                                class="block w-full rounded-md border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary text-center text-base sm:leading-6">
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-6 w-full max-w-lg shadow-sm">
                                    <div class="flex">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <i class="bx bx-info-circle text-blue-500 text-xl"></i>
                                        </div>
                                        <div class="ml-3">
                                            <template x-if="!esDirigidoOtros">
                                                <div>
                                                    <h3 class="text-sm font-medium text-blue-800">Matrícula Automática
                                                    </h3>
                                                    <p class="text-sm text-blue-700 mt-1">
                                                        Al aperturar el curso, se matriculará <strong>automáticamente</strong> a
                                                        <span x-text="dirigidoLabel"></span> según la configuración del curso.
                                                    </p>
                                                </div>
                                            </template>
                                            <template x-if="esDirigidoOtros">
                                                <div>
                                                    <h3 class="text-sm font-medium text-amber-800">Matrícula Manual
                                                    </h3>
                                                    <p class="text-sm text-amber-700 mt-1">
                                                        Deberá matricular <strong>manualmente</strong> al personal en la
                                                        pestaña de Matrículas.
                                                    </p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- NUEVO: Filtros Grupales (Punto 11) -->
                                <div x-show="!incluirAutomatico" x-transition
                                    class="w-full max-w-lg mt-2 bg-gray-50 border border-gray-200 rounded-lg p-4 shadow-sm">
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
                                                <template x-for="item in combosApertura.sucursales" :key="item.codigo">
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

                        <div class="flex flex-col items-center w-full gap-4 py-6 mt-4 border-t border-gray-100">
                            <div class="flex justify-center w-full gap-4">
                                <button type="button" @click="guardarApertura()" :disabled="cargando"
                                    class="btn rounded-full bg-primary text-white hover:bg-primary-600 transition-colors px-8 shadow-sm disabled:opacity-50">
                                    <span x-show="!cargando" class="flex items-center"><i
                                            class="bx bx-calendar-plus text-base mr-2"></i> Aperturar el curso</span>
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
            </div>

            <!-- Modal Aplazar Curso (Dar más plazo) -->
            <div x-show="panel === 'aplazar_curso'" x-data="modalAplazarCurso()" x-init="init()"
                @open-aplazar-modal.window="openModal($event.detail)" style="display: none;"
                class="fixed inset-0 z-[1040] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100">

                <div class="card w-full max-w-md shadow-2xl border border-slate-200 overflow-hidden"
                    @click.away="closeModal()">
                    <div class="card-header bg-white border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h4 class="card-title">Dar más plazo al curso: <span x-text="cursoNombre"
                                    class="text-primary font-bold"></span></h4>
                            <button type="button" @click="closeModal()" title="Cerrar"
                                class="btn btn-sm rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200">
                                <i class="bx bx-x text-lg"></i>
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div x-show="cargando" class="flex flex-col items-center justify-center py-8">
                            <div class="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin mb-3"></div>
                            <p class="text-gray-600">Cargando programación actual...</p>
                        </div>

                        <div x-show="!cargando && programacionActual" x-transition class="space-y-4">
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                                <h5 class="text-sm font-semibold text-gray-700 mb-3">Programación actual del curso</h5>
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <span class="text-gray-500">Código programación:</span>
                                        <span class="font-mono text-primary ml-2" x-text="programacionActual.codigo_programacion"></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Periodo:</span>
                                        <span class="font-medium ml-2" x-text="programacionActual.periodo"></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Fecha inicio:</span>
                                        <span class="font-medium ml-2" x-text="formatearFecha(programacionActual.fecha_inicio)"></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Fecha fin actual:</span>
                                        <span class="font-medium text-red-600 ml-2" x-text="formatearFecha(programacionActual.fecha_final)"></span>
                                    </div>
                                    <div class="col-span-2">
                                        <span class="text-gray-500">Estado:</span>
                                        <span class="ml-2 px-2 py-0.5 rounded text-xs font-medium"
                                            :class="programacionActual.estado_periodo === 'VIGENTE' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                                            x-text="programacionActual.estado_periodo"></span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="fechaNuevaFin" class="block text-sm font-semibold text-gray-900 mb-2">
                                    Nueva fecha de fin <span class="text-danger">*</span>
                                </label>
                                <input type="date" id="fechaNuevaFin" x-model="fechaNuevaFin"
                                    :min="fechaMinima"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-shadow"
                                    @change="validarFecha()">
                                <p x-show="errorFecha" class="mt-1 text-sm text-red-600" x-text="errorFecha"></p>
                                <p x-show="!errorFecha && fechaNuevaFin" class="mt-1 text-sm text-green-600">
                                    Se extenderá el curso por
                                    <strong x-text="extensionInfo.dias + ' día(s)' + (extensionInfo.horas ? ' y ' + extensionInfo.horas + ' hora(s)' : '') + (extensionInfo.minutos ? ' y ' + extensionInfo.minutos + ' minuto(s)' : '')"></strong>
                                </p>
                            </div>

                            <div x-show="errorAPI" class="bg-red-50 border border-red-100 rounded-lg p-3 text-sm text-red-700" x-text="errorAPI"></div>
                        </div>

                        <div x-show="!cargando && !programacionActual" class="text-center py-8 text-gray-500">
                            <i class="bx bx-error-circle text-4xl text-red-400 mb-2"></i>
                            <p>No se encontró programación actual para este curso</p>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-t border-gray-100 flex justify-end gap-3">
                        <button type="button" @click="closeModal()"
                            class="btn rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors px-5"
                            :disabled="cargando || guardando">
                            Cancelar
                        </button>
                        <button type="button" @click="guardarExtension()"
                            class="btn rounded-lg bg-success text-white hover:bg-green-600 transition-colors px-5 shadow-sm"
                            :disabled="cargando || guardando || !fechaValida || !fechaNuevaFin">
                            <span x-show="!guardando" class="flex items-center"><i
                                    class="bx bx-time-five text-base mr-2"></i> Aplicar extensión</span>
                            <span x-show="guardando" class="flex items-center"><i
                                    class="bx bx-loader-alt bx-spin text-base mr-2"></i> Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>

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
                                                        <input type="radio" :name="'resp_' + index" :value="chr(65 + optIndex)"
                                                            x-model="p.respuesta_correcta"
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
                                <span style="font-size:0.7rem;color:#1d4ed8;font-weight:600">Se guardarán las modificaciones al hacer click en "Confirmar cambios"</span>
                            </div>
                            <div style="display:flex;gap:0.75rem">
                                <button type="button" @click="mostrarModal=false"
                                    style="padding:0.6rem 1.25rem;border-radius:0.75rem;font-size:0.75rem;font-weight:700;color:#64748b;background:transparent;border:1px solid #e2e8f0;cursor:pointer;transition:all 0.2s"
                                    onmouseover="this.style.background='#f1f5f9'"
                                    onmouseout="this.style.background='transparent'">
                                    Cerrar
                                </button>
                                <button type="button" @click="undoChanges()"
                                    style="padding:0.6rem 1.25rem;border-radius:0.75rem;font-size:0.75rem;font-weight:700;color:#d97706;background:transparent;border:1px solid #fcd34d;cursor:pointer;transition:all 0.2s"
                                    onmouseover="this.style.background='#fffbeb'"
                                    onmouseout="this.style.background='transparent'">
                                    <i class="bx bx-undo" style="margin-right:0.35rem"></i>
                                    Deshacer cambios
                                </button>
                                <button type="button" @click="mostrarModal=false"
                                    style="padding:0.6rem 1.75rem;border-radius:0.75rem;font-size:0.75rem;font-weight:900;color:#fff;background:linear-gradient(135deg,#2563eb,#4f46e5);border:none;cursor:pointer;display:flex;align-items:center;gap:0.5rem;box-shadow:0 4px 15px -3px rgba(37,99,235,0.5);transition:all 0.2s;text-transform:uppercase;letter-spacing:0.05em">
                                    <i class="bx bxs-check-circle"></i>
                                    Confirmar cambios
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>

@vite(['resources/js/functions/capacitacion/gestion_cursos.js'])

<script>
    /**
     * Gestión de paneles (Apertura/Aplazar) de cursos.
     * La visibilidad de los modales de Registro y Edición la maneja cada modal
     * de forma independiente (modalRegistroCurso / modalEditarCurso).
     */
    window.modalGestionCurso = function() {
        return {
            panel: 'registro',
            tituloProgramacion: '',
            mostrarBotonRegistrarListado: true,
        };
    }

    window.modalExamenWord = function() {
        return {
            mostrarModal: false,
            preguntas: [],
            preguntasOriginales: [],
            codCursoActual: null,
            archivoNombre: '',

            abrirModalWord(preguntas, cursoId, examenId, nombreArc) {
                this.preguntas = Array.isArray(preguntas) ? preguntas : [];
                this.preguntasOriginales = Array.isArray(preguntas) ? JSON.parse(JSON.stringify(preguntas)) : [];
                this.codCursoActual = cursoId;
                this.archivoNombre = nombreArc || '';
                this.mostrarModal = true;
            },

            undoChanges() {
                this.preguntas = JSON.parse(JSON.stringify(this.preguntasOriginales));
            },

            chr(code) {
                return String.fromCharCode(code);
            }
        };
    }
</script>

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
                            if (window.tablaCursos) {
                                window.tablaCursos.redraw(true);
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
            dirigidoA: '',
            frecuencia: '',
            fechaInicio: '',
            fechaFin: '',
            clientesAsignados: [],
            empresasAsignadas: [],
            areasAsignadas: [],
            listaDNIPaste: '',
            incluirAutomatico: true,
            esPeriodico: true,
            selectedSucursal: '',
            selectedCliente: '',
            selectedArea: '',
            combosApertura: {
                sucursales: [],
                clientes: [],
                areas: []
            },
            _combosPromise: null,

            get fechaMinima() {
                const today = new Date();
                const yyyy = today.getFullYear();
                const mm = String(today.getMonth() + 1).padStart(2, '0');
                const dd = String(today.getDate()).padStart(2, '0');
                return `${yyyy}-${mm}-${dd}`;
            },

            get selectedClienteNombre() {
                const cliente = this.combosApertura.clientes?.find(c => String(c.codigo) === String(this.selectedCliente));
                return cliente ? cliente.nombre : '—';
            },

            get esDirigidoOtros() {
                return String(this.dirigidoA) === 'OTROS' || String(this.dirigidoA) === '0';
            },

            get dirigidoLabel() {
                const labels = {
                    '1': 'todo el personal',
                    '2': 'personal administrativo',
                    '3': 'personal operativo'
                };
                return labels[String(this.dirigidoA)] || '';
            },

            async init() {
                await this.fetchCombos();
            },

            async fetchCombos() {
                if (this._combosPromise) return this._combosPromise;
                this._combosPromise = (async () => {
                    try {
                        const response = await fetch(`${VITE_URL_APP}/api/capacitacion/combos-apertura`);
                        const data = await response.json();
                        if (data.success) {
                            this.combosApertura = data;
                        }
                    } catch (e) {
                        console.error("Error cargando combos de apertura:", e);
                    }
                })();
                return this._combosPromise;
            },

            openModal(data) {
                this.codigoCurso = data.codigo;
                this.cursoNombre = data.nombre;
                this.tipoCursoId = data.tipo_curso || '';
                this.dirigidoA = data.dirigido_a || '';
                this.frecuencia = data.frecuencia || '';
                this.esPeriodico = data.es_periodico != '0';

                // Reset filtros
                this.selectedSucursal = '';
                this.selectedCliente = '';
                this.selectedArea = '';
                this.listaDNIPaste = '';

                this.fechaInicio = this.fechaMinima;

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
                this.dirigidoA = '';
                this.fechaInicio = '';
                this.fechaFin = '';
                this.selectedSucursal = '';
                this.selectedCliente = '';
                this.selectedArea = '';
            },

            async guardarApertura() {
                if (!this.esPeriodico) {
                    if (!this.fechaInicio || !this.fechaFin) {
                        window.dispatchEvent(new CustomEvent('mostrar-alerta', {
                            detail: {
                                titulo: "Atención",
                                mensaje: "Debe seleccionar fecha de inicio y fecha de fin.",
                                tipo: "warning"
                            }
                        }));
                        return;
                    }
                    if (this.fechaInicio > this.fechaFin) {
                        window.dispatchEvent(new CustomEvent('mostrar-alerta', {
                            detail: {
                                titulo: "Atención",
                                mensaje: "La fecha de inicio no puede ser mayor a la fecha de fin.",
                                tipo: "warning"
                            }
                        }));
                        return;
                    }
                } else {
                    if (!this.fechaInicio) {
                        window.dispatchEvent(new CustomEvent('mostrar-alerta', {
                            detail: {
                                titulo: "Atención",
                                mensaje: "Debe seleccionar una fecha de inicio.",
                                tipo: "warning"
                            }
                        }));
                        return;
                    }
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
                        cod_curso: this.codigoCurso,
                        fecha_inicio: this.fechaInicio,
                        incluir_automatico: this.incluirAutomatico,
                        sucursal_codigo: this.selectedSucursal,
                        cliente_id: this.selectedCliente,
                        area_codigo: this.selectedArea
                    };

                    if (!this.esPeriodico) {
                        payload.fecha_final = this.fechaFin;
                    }

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
@endsection