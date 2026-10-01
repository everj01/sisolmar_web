@extends('layouts.vertical', ['title' => 'Cursos externos'])
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

@include('layouts.shared.page-title', ['subtitle' => 'Capacitación', 'title' => 'Cursos externos'])

@section('content')
<div class="px-6 py-6" x-data="cursosExternosApp">
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
                        <i class="ti ti-shield text-sm"></i>
                        Cursos Externos
                    </div>

                    <h1 class="text-3xl font-bold tracking-tight text-default-900 mt-4">
                        Cursos Externos
                    </h1>

                    <p class="mt-3 text-sm leading-7 text-default-600 max-w-3xl">
                        Sin descripción.
                    </p>

                    <div class="flex items-center gap-6 mt-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                                <i class="ti ti-shield text-lg text-primary"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-default-800">Cursos</p>
                                <p class="text-[10px] text-default-500">ICMA</p>
                            </div>
                        </div>
                        <div class="w-px h-10 bg-default-200"></div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center">
                                <i class="ti ti-book text-lg text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-default-800">Otros</p>
                                <p class="text-[10px] text-default-500">Cursos</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hidden xl:flex flex-col items-center justify-center shrink-0">
                    <div
                        class="w-20 h-20 rounded-2xl bg-gradient-to-br from-primary/10 to-primary/5 flex items-center justify-center">
                        <i class="ti ti-shield text-4xl text-primary/60"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        {{-- Card: Cursos portuarios --}}
        <div class="card-hover group relative overflow-hidden rounded-2xl border border-default-200/60 bg-white shadow-sm">
            <div class="relative p-6 flex flex-col h-full">
                {{-- Icon + Badge --}}
                <div class="flex items-start justify-between mb-5">
                    <div
                        class="w-12 h-12 rounded-xl bg-teal-500 flex items-center justify-center shadow-md">
                        <i class="ti ti-anchor text-xl text-white"></i>
                    </div>
                    <span
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-teal-50 text-teal-500 text-[10px] font-bold uppercase tracking-wider">
                        <i class="ti ti-anchor text-[9px]"></i>
                        Portuarios
                    </span>
                </div>

                {{-- Title + Description --}}
                <h3 class="text-base font-bold text-default-900 mb-2">Cursos portuarios</h3>
                <p class="text-sm text-default-500 leading-relaxed mb-4">
                    Consulte el estado de los cursos portuarios del personal, incluyendo vigencia,
                    estado y fecha de vencimiento.
                </p>

                {{-- Features --}}
                <div class="space-y-2 mb-5 flex-grow">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-teal-500 shrink-0"></i>
                        <span class="text-xs text-default-600">Filtre por sucursal y tipo de personal</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-teal-500 shrink-0"></i>
                        <span class="text-xs text-default-600">Vigencia y estado del curso portuario</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-teal-500 shrink-0"></i>
                        <span class="text-xs text-default-600">Control por fecha de vencimiento</span>
                    </div>
                </div>

                {{-- Button --}}
                <button type="button" @click="abrirModalCursosPortuarios()"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-teal-500 text-white text-sm font-semibold hover:bg-teal-600 transition-colors w-full cursor-pointer">
                    <i class="ti ti-arrow-right text-sm"></i>
                    Generar un reporte
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Reporte de Cursos Portuarios --}}
    <div id="modal-cursos-portuarios" x-data="modalCursosPortuarios" x-show="open" x-cloak
        @keydown.escape.window="cerrar()" class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        style="background: rgba(36,39,70,0.45);">

        <div class="flex flex-col w-full max-w-40 bg-white rounded-2xl shadow-2xl shadow-primary/10 border border-default-200 overflow-hidden transition-all duration-300"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4">

            {{-- Encabezado del modal --}}
            <div class="flex justify-between items-start py-5 px-6 border-b border-default-100">
                <div class="flex items-center gap-3.5">
                    <div
                        class="w-10 h-10 rounded-xl bg-teal-500 flex items-center justify-center text-white shadow-sm shrink-0">
                        <i class="ti ti-anchor text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-[15px] font-semibold text-default-900 leading-tight">
                            Reporte de cursos portuarios
                        </h3>
                        <p class="text-xs text-default-500 mt-0.5">Seleccione los filtros para generar el reporte</p>
                    </div>
                </div>
                {{-- Botón cerrar --}}
                <button type="button" @click="cerrar()"
                    class="flex-shrink-0 w-7 h-7 inline-flex items-center justify-center rounded-lg text-default-400 hover:text-default-700 hover:bg-default-100 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors cursor-pointer">
                    <i class="ti ti-x text-base"></i>
                </button>
            </div>

            {{-- Filtros --}}
            <div class="px-6 pt-4 pb-6 space-y-4">
                {{-- Sucursal --}}
                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Sucursal <span class="text-default-400 font-normal">(opcional, "Todas" para general)</span>
                    </label>
                    <select x-model="selectedSucursal"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                        <option value="" x-text="loadingSucursales ? 'Cargando sucursales...' : 'Todas las sucursales'">
                        </option>
                        <template x-for="option in sucursales" :key="option.Codigo">
                            <option :value="option.Codigo" x-text="option.Sucursal"></option>
                        </template>
                    </select>
                </div>

                {{-- Tipo de personal --}}
                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Tipo de personal <span class="text-default-400 font-normal">(opcional, "Todos" para general)</span>
                    </label>
                    <select x-model="selectedTipoPersonal"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                        <option value="" x-text="loadingTiposPers ? 'Cargando tipos...' : 'Todos los tipos de personal'">
                        </option>
                        <template x-for="option in tiposPers" :key="option.CODIGO">
                            <option :value="option.CODIGO" x-text="option.DESCRIPCION"></option>
                        </template>
                    </select>
                </div>

                {{-- Vigencia y Estado --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-default-700 mb-1.5 block">
                            Vigencia
                        </label>
                        <select x-model="selectedVigencia"
                            class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                            <option value="">Todos</option>
                            <option value="1">SI</option>
                            <option value="2">NO</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-default-700 mb-1.5 block">
                            Estado
                        </label>
                        <select x-model="selectedEstado"
                            class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                            <option value="">Todos</option>
                            <option value="1">SI</option>
                            <option value="2">NO</option>
                        </select>
                    </div>
                </div>

                {{-- Vencimiento --}}
                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Vencimiento <span class="text-default-400 font-normal">(opcional)</span>
                    </label>
                    <input type="date" x-model="selectedVencimiento"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                </div>

                {{-- Certificados (selección múltiple) --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-medium text-default-700">
                            Certificados
                            <span class="text-default-400 font-normal">(opcional, "Todos" para general)</span>
                        </label>
                        <button type="button" x-show="selectedCertificados.length > 0"
                            @click="selectedCertificados = []; searchCertificado = ''"
                            class="text-xs text-teal-500 hover:text-teal-800 font-medium transition-all cursor-pointer">
                            Limpiar
                        </button>
                    </div>

                    <div class="border border-default-200 rounded-lg overflow-hidden">

                        {{-- Buscador + seleccionar todos --}}
                        <div class="flex items-center gap-2 border-b border-default-200 bg-white px-3">
                            <i class="ti ti-search text-default-400 text-sm shrink-0"></i>
                            <input type="text" x-model="searchCertificado"
                                placeholder="Buscar certificado..."
                                class="flex-1 h-9 text-sm bg-transparent text-default-900 placeholder-default-400 border-0 ring-0 focus:ring-0 focus:outline-none shadow-none">
                        </div>

                        {{-- Lista de certificados --}}
                        <div class="max-h-40 overflow-y-auto divide-y divide-default-100">
                            <template x-if="loadingCertificados">
                                <div class="flex items-center justify-center py-5 text-default-400 gap-2">
                                    <i class="ti ti-loader animate-spin text-lg"></i>
                                    <span class="text-sm">Cargando certificados...</span>
                                </div>
                            </template>

                            <template x-if="!loadingCertificados">
                                <div>
                                    <template x-for="option in certificadosFiltrados" :key="option.CODIGO">
                                        <label
                                            class="flex items-center gap-2 px-3 py-2 cursor-pointer transition-all"
                                            :class="selectedCertificados.includes(String(option.CODIGO)) ? 'bg-teal-50' : 'hover:bg-default-100'">

                                            <input type="checkbox" class="sr-only" :value="option.CODIGO"
                                                :checked="selectedCertificados.includes(String(option.CODIGO))"
                                                @change="toggleCertificado(option.CODIGO)">

                                            {{-- Checkbox custom --}}
                                            <div
                                                class="w-4 h-4 shrink-0 rounded border flex items-center justify-center transition-all"
                                                :class="selectedCertificados.includes(String(option.CODIGO))
                                                    ? 'bg-teal-500 border-teal-500'
                                                    : 'bg-white border-default-300'">
                                                <i class="ti ti-check text-white text-[11px]"
                                                    x-show="selectedCertificados.includes(String(option.CODIGO))"></i>
                                            </div>

                                            <span class="text-sm transition-all truncate"
                                                :class="selectedCertificados.includes(String(option.CODIGO)) ? 'text-teal-500 font-medium' : 'text-default-700'"
                                                x-text="option.DESCRIPCION">
                                            </span>
                                        </label>
                                    </template>

                                    <div x-show="certificadosFiltrados.length === 0"
                                        class="flex flex-col items-center justify-center py-5 text-default-400 gap-1.5">
                                        <i class="ti ti-search-off text-lg"></i>
                                        <span class="text-xs">Sin resultados para "<span
                                                class="font-medium"
                                                x-text="searchCertificado"></span>"</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Chips de seleccionados --}}
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <template x-for="codigo in selectedCertificados" :key="codigo">
                            <span
                                class="inline-flex items-center gap-1 px-2 h-6 rounded-full bg-teal-50 border border-teal-200 text-teal-500 text-[11px] font-medium max-w-[300px]">
                                <span class="truncate" x-text="descripcionCertificado(codigo)"></span>
                                <button type="button" @click="toggleCertificado(codigo)"
                                    class="shrink-0 w-4 h-4 rounded-full hover:bg-default-200 flex items-center justify-center transition-all cursor-pointer"
                                    title="Quitar">
                                    <i class="ti ti-x text-[11px]"></i>
                                </button>
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Footer / Botones de acción --}}
            <div class="flex justify-end items-center gap-2 py-4 px-6 border-t border-default-100">
                <button type="button" @click="cerrar()"
                    class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-default-600 bg-default-100 hover:bg-default-200 hover:text-default-800 transition-all cursor-pointer">
                    Cancelar
                </button>
                <button type="button" @click="generarReporte()"
                    class="px-5 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-semibold text-white bg-teal-500 hover:bg-teal-600 shadow-sm transition-all cursor-pointer">
                    <i class="ti ti-report text-sm"></i>
                    Generar reporte
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
@endsection