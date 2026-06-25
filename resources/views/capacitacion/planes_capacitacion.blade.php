@extends('layouts.vertical', ['title' => 'Planes de capacitación'])

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
</style>
@endsection

@include('layouts.shared/page-title', ['subtitle' => 'Capacitación', 'title' => 'Planes de capacitación'])

@section('content')
<div x-data="planesCapacApp" class="px-6 py-6">
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
                        <i class="ti ti-books text-sm"></i>
                        Planes de capacitación
                    </div>

                    <!-- Title + Description -->
                    <h1 class="text-3xl font-bold tracking-tight text-default-900 mt-4">
                        Planes de Capacitación
                    </h1>
                    <p class="mt-3 text-sm leading-7 text-default-600 max-w-3xl">
                        Visualice los planes de capacitación disponibles en el sistema y descárguelos en formato PDF
                        para su distribución o archivo.
                    </p>

                    <!-- Quick stats -->
                    <div class="flex items-center gap-6 mt-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                                <i class="ti ti-books text-lg text-primary"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-default-800">Planes</p>
                                <p class="text-[10px] text-default-500">disponibles</p>
                            </div>
                        </div>
                        <div class="w-px h-10 bg-default-200"></div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center">
                                <i class="ti ti-file-text text-lg text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-default-800">PDF</p>
                                <p class="text-[10px] text-default-500">descargable</p>
                            </div>
                        </div>
                        <div class="w-px h-10 bg-default-200"></div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center">
                                <i class="ti ti-search text-lg text-amber-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-default-800">Consulta</p>
                                <p class="text-[10px] text-default-500">rápida</p>
                            </div>
                        </div>
                    </div>


                </div>

                <!-- Right side: decorative icon -->
                <div class="hidden xl:flex flex-col items-center justify-center shrink-0">
                    <div
                        class="w-20 h-20 rounded-2xl bg-gradient-to-br from-primary/10 to-primary/5 flex items-center justify-center">
                        <i class="ti ti-books text-4xl text-primary/60"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        {{-- Card: Plan de Capacitación Estandar (PCE) --}}
        <div
            class="card-hover group relative overflow-hidden rounded-2xl border border-default-200/60 bg-white shadow-sm">
            <div class="relative p-6 flex flex-col h-full">
                <!-- Icon + Badge -->
                <div class="flex items-start justify-between mb-5">
                    <div
                        class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary to-blue-400 flex items-center justify-center shadow-md shadow-primary/20">
                        <i class="ti ti-file-text text-xl text-white"></i>
                    </div>
                    <span
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-default-100 text-default-600 text-[10px] font-bold uppercase tracking-wider">
                        <i class="ti ti-bookmark text-[9px]"></i>
                        PCE
                    </span>
                </div>

                <!-- Title + Description -->
                <h3 class="text-base font-bold text-default-900 mb-2">Plan de Capacitación Estandar (PCE)</h3>
                <p class="text-sm text-default-500 leading-relaxed mb-4">
                    Plan general de capacitaciones estandarizadas para el personal de la organización.
                </p>

                <!-- Features -->
                <div class="space-y-2 mb-5 flex-grow">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-primary shrink-0"></i>
                        <span class="text-xs text-default-600">Capacitaciones estandarizadas por área</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-primary shrink-0"></i>
                        <span class="text-xs text-default-600">Cobertura para todo el personal</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-primary shrink-0"></i>
                        <span class="text-xs text-default-600">Formato PDF descargable e imprimible</span>
                    </div>
                </div>

                <!-- Button -->
                <button @click="abrirPDF()"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 transition-colors w-full cursor-pointer">
                    <i class="ti ti-eye text-sm"></i>
                    Ver plan PCE
                </button>
            </div>
        </div>
    </div>

    {{-- Modal previsualización PDF --}}
    <div x-show="open" x-cloak
        @keydown.escape.window="cerrar()" class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        style="background: rgba(36,39,70,0.45);">

        <div class="flex flex-col w-full max-w-7xl h-[93vh] bg-white rounded-2xl shadow-2xl shadow-primary/10 border border-default-200 overflow-hidden transition-all duration-300"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4">

            <div class="flex justify-between items-center py-4 px-6 border-b border-default-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-primary flex items-center justify-center text-white shadow-sm shrink-0">
                        <i class="ti ti-file-text text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-[15px] font-semibold text-default-900 leading-tight">
                            Plan de Capacitación Estándar (PCE)
                        </h3>
                        <p class="text-xs text-default-500">Previsualización del documento</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="window.open(pdfUrl)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary text-white text-xs font-semibold hover:bg-primary/90 transition-colors cursor-pointer">
                        <i class="ti ti-external-link text-sm"></i>
                        Abrir en ventana
                    </button>
                    <a :href="pdfUrl" :download="'Plan_Capacitacion_Estandar_PCE_' + new Date().getFullYear() + '.pdf'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green-500 text-white text-xs font-semibold hover:bg-green-600 transition-colors">
                        <i class="ti ti-download text-sm"></i>
                        Descargar
                    </a>
                    <button type="button" @click="cerrar()"
                        class="flex-shrink-0 w-7 h-7 inline-flex items-center justify-center rounded-lg text-default-400 hover:text-default-700 hover:bg-default-100 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors cursor-pointer">
                        <i class="ti ti-x text-base"></i>
                    </button>
                </div>
            </div>

            <div class="flex-1 bg-default-50 flex items-center justify-center">
                <template x-if="pdfUrl">
                    <iframe :src="pdfUrl" class="w-full h-full border-0" title="Plan de Capacitación Estándar (PCE)"></iframe>
                </template>
                <template x-if="!pdfUrl">
                    <div class="text-default-500 text-sm">Generando PDF...</div>
                </template>
            </div>
        </div>
    </div>
</div>
@vite(['resources/js/app.js', 'resources/js/functions/capacitacion/planes_capacitaciones.js'])
@endsection