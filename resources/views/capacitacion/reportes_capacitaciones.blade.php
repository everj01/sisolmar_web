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
<div x-data="reportesApp" class="px-6 py-6">
    {{-- Header --}}
    <div
        class="relative overflow-hidden rounded-2xl border border-default-200/60 bg-gradient-to-br from-white to-default-50 shadow-sm mb-6">
        <div class="absolute top-0 right-0 w-72 h-72 bg-primary/5 rounded-full blur-3xl"></div>
        <div class="relative p-8">
            <div
                class="inline-flex items-center gap-2 w-fit px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-medium">
                <i class="ti ti-report text-sm"></i>
                Reportes de capacitaciones
            </div>
            <div class="mt-4">
                <h1 class="text-3xl font-bold tracking-tight text-default-900">Reportes</h1>
                <p class="mt-3 text-sm leading-7 text-default-600 max-w-4xl">
                    Genere reportes detallados sobre el estado de las capacitaciones del personal.
                </p>
            </div>
        </div>
    </div>

    {{-- Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <div
            class="card-hover relative overflow-hidden rounded-2xl border border-default-200/60 bg-white shadow-sm cursor-pointer group">
            <div
                class="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
            </div>
            <div class="relative p-6 flex flex-col h-full">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                        <i class="ti ti-file-report text-2xl text-primary"></i>
                    </div>
                    <i
                        class="ti ti-chevron-right text-default-300 group-hover:text-primary transition-colors text-lg"></i>
                </div>
                <h3 class="text-lg font-semibold text-default-900 mb-2">Reporte por capacitación</h3>
                <p class="text-sm text-default-500 leading-relaxed flex-grow mb-4">
                    Genera un reporte por capacitación, visualizando rápidamente qué personal está pendiente, aprobado
                    y/o desaprobado.
                </p>
                <button @click="abrirModalReporte()"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-600 transition-colors shadow-sm w-fit">
                    <i class="ti ti-arrow-right"></i>
                    Generar
                </button>
            </div>
        </div>
    </div>

    {{-- Modal --}}
    <div id="modal-reporte-capacitacion" x-data="modalReporte" x-show="open" x-cloak
        class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        style="background: rgba(0,0,0,0.35); backdrop-filter: blur(2px);">

        <div
            class="flex flex-col shadow-2xl rounded-3xl overflow-hidden w-full max-w-2xl border border-white/40 bg-white animate-fade-in">

            {{-- Modal header --}}
            <div
                class="flex justify-between items-center py-5 px-6 bg-gradient-to-r from-primary/10 to-transparent border-b border-gray-100">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 rounded-2xl bg-primary text-white flex items-center justify-center font-black text-lg shadow-lg shadow-primary/20">
                        <i class="ti ti-file-report text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-gray-800 text-lg leading-tight tracking-tight">Generar Reporte</h3>
                        <p class="text-xs font-medium text-gray-500">Seleccione los filtros para generar el reporte</p>
                    </div>
                </div>
                <button type="button" @click="cerrar()"
                    class="w-8 h-8 inline-flex justify-center items-center rounded-full border bg-white text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary transition-all">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            {{-- Modal body --}}
            <div class="p-6 space-y-5">

                {{-- Sistema de gestión --}}
                <div>
                    <label class="text-gray-800 text-sm font-medium inline-block mb-1 text-primary">
                        Sistema de gestión <span class="text-danger">*</span>
                    </label>
                    <select x-model="selectedSistema" @change="cargarAreas($event.target.value)"
                        class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Seleccione</option>

                        <template x-for="option in sistemas" :key="option.codigo">
                            <option :value="option.codigo" x-text="option.descripcion"></option>
                        </template>
                    </select>
                </div>

                {{-- Área responsable --}}
                <div>
                    <label class="text-gray-800 text-sm font-medium inline-block mb-1 text-primary">
                        Área responsable <span class="text-danger">*</span>
                    </label>
                    <select x-model="selectedArea" @change="cargarCursos($event.target.value)"
                        class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Seleccione</option>

                        <template x-for="option in areas" :key="option.codModdle">
                            <option :value="option.codModdle" x-text="option.Area"></option>
                        </template>
                    </select>
                </div>

                {{-- Periodo --}}
                <div>
                    <label class="text-gray-800 text-sm font-medium inline-block mb-1 text-primary">
                        Periodo <span class="text-danger">*</span>
                    </label>
                    <select x-model="selectedPeriodo" @change="filtrarCursosPorPeriodo()"
                        class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary h-[38px] shadow-sm">

                        <option value="">Seleccione Periodo</option>

                        <template x-for="option in periodos" :key="option.id">
                            <option :value="option.periodo" x-text="option.periodo"></option>
                        </template>
                    </select>
                </div>

                {{-- Curso de capacitación --}}
                <div>
                    <label class="text-gray-800 text-sm font-medium inline-block mb-1 text-primary">
                        Curso de capacitación <span class="text-danger">*</span>
                    </label>
                    <select x-model="selectedCurso"
                        class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary h-[38px] shadow-sm">
                        <option value="">Seleccione Curso</option>

                        <template x-for="option in cursos" :key="option.id">
                            <option :value="option.id" x-text="option.fullname"></option>
                        </template>
                    </select>
                </div>

                {{-- Estado de alumnos --}}
                <div>
                    <label class="text-gray-800 text-sm font-medium inline-block mb-1 text-primary">
                        Estado de alumnos <span class="text-danger">*</span>
                    </label>
                    <select x-model="selectedEstado"
                        class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary h-[38px] shadow-sm">
                        <option value="PENDIENTE">PENDIENTE</option>
                        <option value="APROBADO">APROBADO</option>
                        <option value="DESAPROBADO">DESAPROBADO</option>
                    </select>
                </div>

            </div>

            {{-- Modal footer --}}
            <div class="flex justify-end items-center gap-x-2 py-4 px-6 border-t border-gray-100 bg-white/50">
                <button type="button" @click="cerrar()"
                    class="py-2.5 px-6 inline-flex justify-center items-center rounded-xl font-medium bg-gray-100 text-gray-800 hover:bg-gray-200 transition-all text-sm">
                    Cancelar
                </button>
                <button type="button" @click="generarReporte()"
                    class="py-2.5 px-6 inline-flex justify-center items-center rounded-xl font-medium bg-primary text-white hover:bg-primary/90 transition-all text-sm">
                    <i class="ti ti-arrow-right"></i>
                    Generar reporte
                </button>
            </div>
        </div>
    </div>
</div>

@vite(['resources/js/app.js'])
@endsection