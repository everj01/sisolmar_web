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

    /* Scrollbar discreta para los paneles del modal de resultados */
    .thin-scroll::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .thin-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .thin-scroll::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, .35);
        border-radius: 8px;
    }

    .thin-scroll::-webkit-scrollbar-thumb:hover {
        background: rgba(148, 163, 184, .6);
    }

    /* Elimina cualquier borde/outline/anillo del buscador de certificados */
    #modal-reporte-certificados input[x-model="searchCertificado"] {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        background: transparent !important;
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
        <div class="card-hover group relative overflow-hidden rounded-2xl border border-default-200/60 bg-white shadow-sm">
            <div class="relative p-6 flex flex-col h-full">
                <div class="flex items-start justify-between mb-5">
                    <div class="w-12 h-12 rounded-xl bg-teal-500 flex items-center justify-center shadow-md">
                        <i class="ti ti-certificate text-xl text-white"></i>
                    </div>
                    <span
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-teal-50 text-teal-500 text-[10px] font-bold uppercase tracking-wider">
                        <i class="ti ti-certificate text-[9px]"></i>
                        Certificados
                    </span>
                </div>

                <h3 class="text-base font-bold text-default-900 mb-2">Reporte de Certificaciones</h3>
                <p class="text-sm text-default-500 leading-relaxed mb-4">
                    Consulte el estado de los certificados del personal, incluyendo vigencia,
                    estado y fecha de vencimiento.
                </p>

                <div class="space-y-2 mb-5 flex-grow">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-teal-500 shrink-0"></i>
                        <span class="text-xs text-default-600">Filtre por sucursal y tipo de personal</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-teal-500 shrink-0"></i>
                        <span class="text-xs text-default-600">Vigencia y estado del certificado</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ti ti-check text-xs text-teal-500 shrink-0"></i>
                        <span class="text-xs text-default-600">Control por fecha de vencimiento</span>
                    </div>
                </div>

                <button type="button" @click="abrirModalReporteCertificados()"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-teal-500 text-white text-sm font-semibold hover:bg-teal-600 transition-colors w-full cursor-pointer">
                    <i class="ti ti-arrow-right text-sm"></i>
                    Generar un reporte
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Reporte de Certificaciones --}}
    <div id="modal-reporte-certificados" x-data="modalReporteCertificados" x-show="open" x-cloak
        @keydown.escape.window="cerrar()" class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        style="background: rgba(36,39,70,0.45);">

        <div class="flex flex-col w-full bg-white rounded-2xl shadow-2xl shadow-primary/10 border border-default-200 overflow-hidden transition-all duration-300"
            :class="view === 'results' ? 'max-w-[1600px] max-h-[94vh]' : 'max-w-4xl max-h-[92vh]'"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4">

            {{-- Encabezado del modal --}}
            <div class="flex justify-between items-start py-5 px-6 border-b border-default-100 shrink-0">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-teal-500 flex items-center justify-center text-white shadow-sm shrink-0">
                        <i class="ti text-lg" :class="view === 'results' ? 'ti-users' : 'ti-certificate'"></i>
                    </div>
                    <div>
                        <h3 class="text-[15px] font-semibold text-default-900 leading-tight"
                            x-text="view === 'results' ? 'Personal encontrado' : 'Reporte de Certificaciones'"></h3>
                        <p class="text-xs text-default-500 mt-0.5"
                            x-text="view === 'results'
                                ? 'Resultado del reporte generado'
                                : 'Seleccione los filtros para generar el reporte'"></p>
                    </div>
                </div>
                <button type="button" @click="cerrar()"
                    class="flex-shrink-0 w-7 h-7 inline-flex items-center justify-center rounded-lg text-default-400 hover:text-default-700 hover:bg-default-100 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors cursor-pointer">
                    <i class="ti ti-x text-base"></i>
                </button>
            </div>

            {{-- ==================== VISTA: FILTROS ==================== --}}
            <div x-show="view === 'filters'" class="px-6 pt-4 pb-6 space-y-4 overflow-y-auto">
                {{-- Sucursal --}}
                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Sucursal <span class="text-default-400 font-normal">(opcional, "Todas" para general)</span>
                    </label>
                    <select x-model="selectedSucursal"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                        <option value="" x-text="loadingSucursales ? 'Cargando sucursales...' : 'Todas las sucursales'"></option>
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
                        <option value="" x-text="loadingTiposPers ? 'Cargando tipos...' : 'Todos los tipos de personal'"></option>
                        <template x-for="option in tiposPers" :key="option.CODIGO">
                            <option :value="option.CODIGO" x-text="option.DESCRIPCION"></option>
                        </template>
                    </select>
                </div>

                {{-- Vigencia y Estado --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-default-700 mb-1.5 block">Vigencia</label>
                        <select x-model="selectedVigencia"
                            class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                            <option value="">Todos</option>
                            <option value="1">SI</option>
                            <option value="2">NO</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-default-700 mb-1.5 block">Estado</label>
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

                {{-- Certificados --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-medium text-default-700">
                            Certificados
                            <span class="text-default-400 font-normal">(máximo <span x-text="MAX_CERTIFICADOS"></span>)</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <span class="text-[11px] font-medium"
                                :class="limiteAlcanzado ? 'text-amber-600' : 'text-default-400'">
                                <span x-text="selectedCertificados.length"></span>
                                <span class="text-default-300">/</span>
                                <span x-text="MAX_CERTIFICADOS"></span>
                            </span>
                            <button type="button" x-show="selectedCertificados.length > 0"
                                @click="selectedCertificados = []; searchCertificado = ''"
                                class="text-xs text-teal-500 hover:text-teal-800 font-medium transition-all cursor-pointer">
                                Limpiar
                            </button>
                        </div>
                    </div>

                    {{-- Toggle: solo certificados portuarios --}}
                    <label class="flex items-center justify-between gap-3 mb-2 px-3 py-2 rounded-lg border border-default-200 bg-default-50 cursor-pointer hover:bg-default-100 transition-colors">
                        <div class="flex items-center gap-2 min-w-0">
                            <i class="ti ti-anchor text-teal-600 text-sm shrink-0"></i>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-default-800 leading-tight">
                                    Solo certificados portuarios
                                </p>
                                <p class="text-[10.5px] text-default-500 leading-tight mt-0.5">
                                    Por defecto se muestran los certificados generales
                                </p>
                            </div>
                        </div>
                        <div class="relative shrink-0">
                            <input type="checkbox" class="sr-only"
                                :checked="soloPortuarios"
                                @change="toggleSoloPortuarios()">
                            <div class="w-9 h-5 rounded-full transition-colors"
                                :class="soloPortuarios ? 'bg-teal-500' : 'bg-default-300'">
                            </div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform"
                                :class="soloPortuarios ? 'translate-x-4' : 'translate-x-0'">
                            </div>
                        </div>
                    </label>

                    <div class="border border-default-200 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-2 bg-white px-3">
                            <i class="ti ti-search text-default-400 text-sm shrink-0"></i>
                            <input type="text" x-model="searchCertificado"
                                placeholder="Buscar certificado..."
                                class="flex-1 h-9 text-sm bg-transparent text-default-900 placeholder-default-400 border-0 ring-0 focus:ring-0 focus:outline-none shadow-none">
                        </div>

                        <div x-show="limiteAlcanzado"
                            class="flex items-center gap-2 px-3 py-2 bg-amber-50 border-b border-amber-100 text-amber-700 text-xs">
                            <i class="ti ti-alert-triangle text-sm shrink-0"></i>
                            <span>Límite alcanzado. Solo puedes seleccionar hasta <strong x-text="MAX_CERTIFICADOS"></strong> certificados. Desmarca uno para cambiarlo.</span>
                        </div>

                        <div class="max-h-56 overflow-y-auto divide-y divide-default-100 thin-scroll">
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
                                            class="flex items-center gap-2 px-3 py-2 transition-all"
                                            :class="[
                                                selectedCertificados.includes(String(option.CODIGO))
                                                    ? 'bg-teal-50 cursor-pointer'
                                                    : (limiteAlcanzado
                                                        ? 'opacity-50 cursor-not-allowed'
                                                        : 'hover:bg-default-100 cursor-pointer')
                                            ]">
                                            <input type="checkbox" class="sr-only"
                                                :disabled="!selectedCertificados.includes(String(option.CODIGO)) && limiteAlcanzado"
                                                :value="option.CODIGO"
                                                :checked="selectedCertificados.includes(String(option.CODIGO))"
                                                @change="toggleCertificado(option.CODIGO)">

                                            <div class="w-4 h-4 shrink-0 rounded border flex items-center justify-center transition-all"
                                                :class="selectedCertificados.includes(String(option.CODIGO))
                                                    ? 'bg-teal-500 border-teal-500'
                                                    : 'bg-white border-default-300'">
                                                <i class="ti ti-check text-white text-[11px]"
                                                    x-show="selectedCertificados.includes(String(option.CODIGO))"></i>
                                            </div>

                                            <span class="text-sm transition-all truncate"
                                                :class="selectedCertificados.includes(String(option.CODIGO)) ? 'text-teal-500 font-medium' : 'text-default-700'"
                                                x-text="option.DESCRIPCION"></span>
                                        </label>
                                    </template>

                                    <div x-show="certificadosFiltrados.length === 0"
                                        class="flex flex-col items-center justify-center py-5 text-default-400 gap-1.5">
                                        <i class="ti ti-search-off text-lg"></i>
                                        <span class="text-xs">Sin resultados para "<span class="font-medium"
                                                x-text="searchCertificado"></span>"</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

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

            {{-- ==================== VISTA: RESULTADOS ==================== --}}
            <div x-show="view === 'results'" class="flex-1 min-h-0 flex flex-col">

                {{-- Loading --}}
                <template x-if="loadingReporte">
                    <div class="flex flex-col items-center justify-center py-24 gap-3 text-default-500">
                        <i class="ti ti-loader-2 animate-spin text-3xl text-teal-500"></i>
                        <p class="text-sm">Generando reporte...</p>
                    </div>
                </template>

                {{-- Empty --}}
                <template x-if="!loadingReporte && reporteData.length === 0">
                    <div class="flex flex-col items-center justify-center py-24 gap-2 text-default-400">
                        <i class="ti ti-clipboard-x text-4xl"></i>
                        <p class="text-sm font-medium text-default-500">No se encontraron registros.</p>
                        <p class="text-xs">Prueba ajustando los filtros del reporte.</p>
                    </div>
                </template>

                {{-- Content: sidebar + panel --}}
                <template x-if="!loadingReporte && reporteData.length > 0">
                    <div class="flex-1 min-h-0 flex overflow-hidden">

                        {{-- ============ SIDEBAR IZQUIERDO ============ --}}
                        <div class="w-80 shrink-0 border-r border-default-100 bg-default-50 flex flex-col min-h-0">
                            <div class="px-4 py-3 border-b border-default-100 shrink-0">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-default-500">Certificados seleccionados</p>
                            </div>

                            <div class="flex-1 overflow-y-auto thin-scroll py-1">
                                <template x-for="cert in resultadosAgrupados" :key="cert.certificado">
                                    <div>
                                        {{-- Certificado --}}
                                        <button type="button"
                                            @click="selectCert(cert.certificado)"
                                            class="w-full flex items-start gap-2 px-4 py-3 text-left transition-colors cursor-pointer"
                                            :class="activeCertificado === cert.certificado
                                                ? 'bg-default-100 border-l-2 border-l-default-500'
                                                : 'border-l-2 border-l-transparent hover:bg-default-50'">
                                            <i class="ti text-xs shrink-0 mt-1 transition-colors"
                                                :class="activeCertificado === cert.certificado
                                                    ? 'ti-chevron-down text-default-600'
                                                    : 'ti-chevron-right text-default-400'"></i>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[12.5px] leading-tight truncate"
                                                    :class="activeCertificado === cert.certificado
                                                        ? 'font-bold text-default-900'
                                                        : 'font-medium text-default-700'"
                                                    :title="cert.certificado"
                                                    x-text="cert.certificado"></p>
                                                <p class="text-[11px] text-default-500 mt-0.5"
                                                    x-text="cert.total + ' personal(es)'"></p>
                                            </div>
                                        </button>

                                        {{-- Sucursales --}}
                                        <div x-show="activeCertificado === cert.certificado"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0 -translate-y-1"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            x-transition:leave="transition ease-in duration-150"
                                            x-transition:leave-start="opacity-100 translate-y-0"
                                            x-transition:leave-end="opacity-0 -translate-y-1">
                                            {{-- Todas --}}
                                            <button type="button"
                                                @click="selectSucursal(cert.certificado, null)"
                                                class="w-full flex items-center gap-2 pl-10 pr-4 py-2 text-left transition-colors cursor-pointer"
                                                :class="activeCertificado === cert.certificado && activeSucursal === null
                                                    ? 'bg-default-100/70 border-l-2 border-l-default-400'
                                                    : 'border-l-2 border-l-transparent hover:bg-default-50'">
                                                <div class="w-1 h-4 rounded-full shrink-0 transition-colors"
                                                    :class="activeCertificado === cert.certificado && activeSucursal === null
                                                        ? 'bg-default-500'
                                                        : 'bg-default-200'"></div>
                                                <span class="text-[11px] font-bold uppercase tracking-wide truncate"
                                                    :class="activeCertificado === cert.certificado && activeSucursal === null
                                                        ? 'text-default-900'
                                                        : 'text-default-600'"
                                                    x-text="'Todas las sucursales'"></span>
                                                <span class="text-[10.5px] text-default-500 shrink-0 ml-auto"
                                                    x-text="cert.total + ' personal(es)'"></span>
                                            </button>

                                            {{-- Cada sucursal --}}
                                            <template x-for="suc in cert.sucursales" :key="suc.sucursal">
                                                <div>
                                                    <button type="button"
                                                        @click="selectSucursal(cert.certificado, suc.sucursal)"
                                                        class="w-full flex items-center gap-2 pl-10 pr-4 py-2 text-left transition-colors cursor-pointer"
                                                        :class="activeCertificado === cert.certificado && activeSucursal === suc.sucursal
                                                            ? 'bg-default-100/70 border-l-2 border-l-default-400'
                                                            : 'border-l-2 border-l-transparent hover:bg-default-50'">
                                                        <div class="w-1 h-4 rounded-full shrink-0 transition-colors"
                                                            :class="activeCertificado === cert.certificado && activeSucursal === suc.sucursal
                                                                ? 'bg-default-500'
                                                                : 'bg-default-200'"></div>
                                                        <span class="text-[12px] truncate"
                                                            :class="activeCertificado === cert.certificado && activeSucursal === suc.sucursal
                                                                ? 'font-semibold text-default-900'
                                                                : 'font-medium text-default-600'"
                                                            :title="suc.sucursal"
                                                            x-text="suc.sucursal"></span>
                                                        <span class="text-[10.5px] text-default-500 shrink-0 ml-auto"
                                                            x-text="suc.total + ' personal(es)'"></span>
                                                        <i class="ti text-[10px] text-default-400 shrink-0 transition-transform"
                                                            :class="activeCertificado === cert.certificado && activeSucursal === suc.sucursal
                                                                ? 'ti-chevron-down'
                                                                : 'ti-chevron-right'"></i>
                                                    </button>

                                                    {{-- Tipos de trabajador --}}
                                                    <div x-show="activeCertificado === cert.certificado && activeSucursal === suc.sucursal"
                                                        x-transition:enter="transition ease-out duration-200"
                                                        x-transition:enter-start="opacity-0 -translate-y-1"
                                                        x-transition:enter-end="opacity-100 translate-y-0"
                                                        x-transition:leave="transition ease-in duration-150"
                                                        x-transition:leave-start="opacity-100 translate-y-0"
                                                        x-transition:leave-end="opacity-0 -translate-y-1">
                                                        {{-- Todos los tipos --}}
                                                        <button type="button"
                                                            @click="selectTipo(cert.certificado, suc.sucursal, null)"
                                                            class="w-full flex items-center gap-2 pl-14 pr-4 py-1.5 text-left transition-colors cursor-pointer"
                                                            :class="activeTipo === null
                                                                ? 'bg-default-100/70 border-l-2 border-l-default-300'
                                                                : 'border-l-2 border-l-transparent hover:bg-default-50'">
                                                            <span class="text-[10.5px] font-bold uppercase tracking-wide truncate"
                                                                :class="activeTipo === null ? 'text-default-800' : 'text-default-500'"
                                                                x-text="'Todos los tipos'"></span>
                                                            <span class="text-[10px] text-default-400 shrink-0 ml-auto"
                                                                x-text="suc.total"></span>
                                                        </button>

                                                        {{-- Cada tipo --}}
                                                        <template x-for="tipo in suc.tipos" :key="tipo.tipo">
                                                            <button type="button"
                                                                @click="selectTipo(cert.certificado, suc.sucursal, tipo.tipo)"
                                                                class="w-full flex items-center gap-2 pl-14 pr-4 py-1.5 text-left transition-colors cursor-pointer"
                                                                :class="activeTipo === tipo.tipo
                                                                    ? 'bg-default-100/70 border-l-2 border-l-default-300'
                                                                    : 'border-l-2 border-l-transparent hover:bg-default-50'">
                                                                <i class="ti ti-user text-[11px] shrink-0"
                                                                    :class="activeTipo === tipo.tipo ? 'text-default-600' : 'text-default-300'"></i>
                                                                <span class="text-[11.5px] truncate"
                                                                    :class="activeTipo === tipo.tipo
                                                                        ? 'font-semibold text-default-900'
                                                                        : 'font-medium text-default-600'"
                                                                    :title="tipo.tipo"
                                                                    x-text="tipo.tipo"></span>
                                                                <span class="text-[10px] text-default-400 shrink-0 ml-auto"
                                                                    x-text="tipo.total"></span>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- ============ PANEL DERECHO ============ --}}
                        <section class="flex-1 min-w-0 flex flex-col bg-white">

                            {{-- Encabezado del panel --}}
                            <div class="px-6 py-3.5 border-b border-default-100 shrink-0 flex items-center gap-3 min-w-0">
                                <template x-if="activeCertificado">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-1 h-5 rounded-full bg-default-400 shrink-0"></div>
                                        <p class="text-[13px] font-bold uppercase tracking-wide text-default-900 truncate"
                                            x-text="activeCertificado"></p>
                                        <span class="text-default-300 shrink-0">·</span>
                                        <p class="text-[12px] font-bold uppercase tracking-wide text-default-600 shrink-0"
                                            x-text="activeSucursal || 'Todas las sucursales'"></p>
                                        <template x-if="activeTipo !== null">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <span class="text-default-300 shrink-0">·</span>
                                                <p class="text-[12px] font-bold uppercase tracking-wide text-default-600 shrink-0"
                                                    x-text="activeTipo"></p>
                                            </div>
                                        </template>
                                        <span class="text-[11.5px] text-default-400 font-medium shrink-0"
                                            x-text="'(' + personalActual.length + ' personal(es))'"></span>
                                    </div>
                                </template>

                                <template x-if="!activeCertificado">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-1 h-5 rounded-full bg-default-200 shrink-0"></div>
                                        <span class="text-[12.5px] font-medium text-default-400 italic">
                                            Seleccione un certificado para ver el personal
                                        </span>
                                    </div>
                                </template>
                            </div>

                            {{-- Tabla --}}
                            <div class="flex-1 overflow-auto thin-scroll">
                                <table class="w-full text-[12.5px]">
                                    <thead class="sticky top-0 z-10 bg-default-50/95 backdrop-blur-sm">
                                        <tr class="border-b border-default-200">
                                            <th class="px-3 py-2.5 text-left font-bold text-[10.5px] uppercase tracking-wider text-default-500 whitespace-nowrap w-12">#</th>
                                            <th class="px-3 py-2.5 text-left font-bold text-[10.5px] uppercase tracking-wider text-default-500 whitespace-nowrap">Código</th>
                                            <th class="px-3 py-2.5 text-left font-bold text-[10.5px] uppercase tracking-wider text-default-500 whitespace-nowrap">Nombre completo</th>
                                            <th class="px-3 py-2.5 text-left font-bold text-[10.5px] uppercase tracking-wider text-default-500 whitespace-nowrap">DNI</th>
                                            <th class="px-3 py-2.5 text-left font-bold text-[10.5px] uppercase tracking-wider text-default-500 whitespace-nowrap">Cargo</th>
                                            <th class="px-3 py-2.5 text-left font-bold text-[10.5px] uppercase tracking-wider text-default-500 whitespace-nowrap">Ingreso</th>
                                            <th class="px-3 py-2.5 text-center font-bold text-[10.5px] uppercase tracking-wider text-default-500 whitespace-nowrap">Vigencia</th>
                                            <th class="px-3 py-2.5 text-left font-bold text-[10.5px] uppercase tracking-wider text-default-500 whitespace-nowrap">Resultado</th>
                                            <th class="px-3 py-2.5 text-left font-bold text-[10.5px] uppercase tracking-wider text-default-500 whitespace-nowrap">Emisión</th>
                                            <th class="px-3 py-2.5 text-left font-bold text-[10.5px] uppercase tracking-wider text-default-500 whitespace-nowrap">Caduca</th>
                                            <th class="px-3 py-2.5 text-center font-bold text-[10.5px] uppercase tracking-wider text-default-500 whitespace-nowrap">Estado</th>
                                            <th class="px-3 py-2.5 text-left font-bold text-[10.5px] uppercase tracking-wider text-default-500 whitespace-nowrap">Observación</th>
                                            <th class="px-3 py-2.5 text-center font-bold text-[10.5px] uppercase tracking-wider text-default-500 whitespace-nowrap">Escaneo</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-default-100">
                                        <template x-for="(p, idx) in personalPaginado" :key="(p.CODIGO || 'x') + '-' + idx">
                                            <tr class="hover:bg-teal-50/30 transition-colors">
                                                <td class="px-3 py-2 text-default-400 font-mono text-[11px] whitespace-nowrap"
                                                    x-text="((paginaActual - 1) * porPagina) + idx + 1"></td>
                                                <td class="px-3 py-2 font-mono text-[11.5px] font-semibold text-default-700 whitespace-nowrap"
                                                    x-text="p.CODIGO || '—'"></td>
                                                <td class="px-3 py-2 font-medium text-default-900 whitespace-nowrap"
                                                    x-text="p.PERSONAL || '—'"></td>
                                                <td class="px-3 py-2 text-default-600 font-mono text-[11.5px] whitespace-nowrap"
                                                    x-text="p.DNI || '—'"></td>
                                                <td class="px-3 py-2 text-default-600 whitespace-nowrap max-w-[200px] truncate"
                                                    :title="p.CARGO || ''"
                                                    x-text="p.CARGO || '—'"></td>
                                                <td class="px-3 py-2 text-default-600 whitespace-nowrap"
                                                    x-text="_formatFecha(p.INGRESO)"></td>
                                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                        :class="_badgeVigencia(p.VIGENCIA)"
                                                        x-text="_labelVigencia(p.VIGENCIA)"></span>
                                                </td>
                                                <td class="px-3 py-2 text-default-600 whitespace-nowrap"
                                                    x-text="p.RESULTADO || '—'"></td>
                                                <td class="px-3 py-2 text-default-600 whitespace-nowrap"
                                                    x-text="_formatFecha(p.FEC_EMISION)"></td>
                                                <td class="px-3 py-2 text-default-600 whitespace-nowrap"
                                                    x-text="_formatFecha(p.FEC_CADUCA)"></td>
                                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                        :class="_badgeEstado(p.ESTADO)"
                                                        x-text="_labelEstado(p.ESTADO)"></span>
                                                </td>
                                                <td class="px-3 py-2 text-default-500 whitespace-nowrap max-w-[180px] truncate"
                                                    :title="p.OBSERVACION || ''"
                                                    x-text="p.OBSERVACION || '—'"></td>
                                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full"
                                                        :class="_badgeEscaneo(p.ESCANEO)"
                                                        :title="_labelEscaneo(p.ESCANEO)">
                                                        <i class="ti text-[12px]"
                                                            :class="_iconoEscaneo(p.ESCANEO)"></i>
                                                    </span>
                                                </td>
                                            </tr>
                                        </template>

                                        <template x-if="!activeCertificado">
                                            <tr>
                                                <td colspan="13" class="px-3 py-12 text-center text-default-400">
                                                    <p class="text-sm">Seleccione un certificado y una sucursal en el panel izquierdo.</p>
                                                </td>
                                            </tr>
                                        </template>

                                        <template x-if="activeCertificado && personalActual.length === 0">
                                            <tr>
                                                <td colspan="13" class="px-3 py-12 text-center text-default-400">
                                                    <i class="ti ti-inbox text-3xl block mb-2"></i>
                                                    <p class="text-sm">Sin personal registrado.</p>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Paginación --}}
                            <div class="flex items-center justify-between gap-3 px-6 py-3 border-t border-default-100 shrink-0 bg-white">
                                <p class="text-[11.5px] text-default-500">
                                    <strong x-text="personalActual.length"></strong> registros
                                    <span class="text-default-300 mx-1">·</span>
                                    Pág. <strong x-text="paginaActual"></strong> de <strong x-text="totalPaginas"></strong>
                                </p>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="paginaAnterior()" :disabled="paginaActual === 1"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg border border-default-200 bg-white text-default-600 hover:bg-default-100 hover:text-default-800 disabled:opacity-40 disabled:cursor-not-allowed transition-all cursor-pointer"
                                        title="Anterior">
                                        <i class="ti ti-chevron-left text-xs"></i>
                                    </button>

                                    <template x-for="(pagina, idxPag) in paginasVisibles" :key="idxPag">
                                        <span class="contents">
                                            <span x-show="pagina === '…'"
                                                class="w-8 h-8 inline-flex items-center justify-center text-[12px] text-default-400 select-none">…</span>
                                            <button type="button" x-show="pagina !== '…'"
                                                @click="irPagina(pagina)"
                                                class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-[12px] font-medium transition-all cursor-pointer"
                                                :class="paginaActual === pagina
                                                    ? 'bg-default-800 text-white shadow-sm'
                                                    : 'border border-default-200 bg-white text-default-600 hover:bg-default-100 hover:text-default-800'"
                                                :title="'Página ' + pagina"
                                                x-text="pagina"></button>
                                        </span>
                                    </template>

                                    <button type="button" @click="paginaSiguiente()"
                                        :disabled="paginaActual >= totalPaginas"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg border border-default-200 bg-white text-default-600 hover:bg-default-100 hover:text-default-800 disabled:opacity-40 disabled:cursor-not-allowed transition-all cursor-pointer"
                                        title="Siguiente">
                                        <i class="ti ti-chevron-right text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </section>
                    </div>
                </template>
            </div>

            {{-- Footer / Botones de acción --}}
            <div class="flex justify-between items-center gap-2 py-4 px-6 border-t border-default-100 shrink-0 bg-white">
                {{-- Footer: filtros --}}
                <template x-if="view === 'filters'">
                    <div class="flex items-center justify-end gap-2 w-full">
                        <button type="button" @click="cerrar()" :disabled="loadingReporte"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-default-600 bg-default-100 hover:bg-default-200 hover:text-default-800 transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                            Cancelar
                        </button>
                        <button type="button" @click="generarReporte()" :disabled="loadingReporte"
                            class="px-5 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-semibold text-white bg-teal-500 hover:bg-teal-600 shadow-sm transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                            <template x-if="loadingReporte">
                                <i class="ti ti-loader-2 animate-spin text-sm"></i>
                            </template>
                            <template x-if="!loadingReporte">
                                <i class="ti ti-report text-sm"></i>
                            </template>
                            <span x-text="loadingReporte ? 'Generando...' : 'Generar reporte'"></span>
                        </button>
                    </div>
                </template>

                {{-- Footer: resultados --}}
                <template x-if="view === 'results'">
                    <div class="flex items-center justify-between gap-2 w-full">
                        <button type="button" @click="volver()"
                            class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-default-700 bg-default-100 hover:bg-default-200 hover:text-default-900 transition-all cursor-pointer">
                            <i class="ti ti-arrow-left text-sm"></i>
                            Atrás
                        </button>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="exportar('excel')" :disabled="exportandoExcel"
                                class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-semibold text-white bg-green-600 hover:bg-green-700 shadow-sm transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                                <i class="ti text-sm" :class="exportandoExcel ? 'ti-loader-2 animate-spin' : 'ti-file-spreadsheet'"></i>
                                <span x-text="exportandoExcel ? 'Generando...' : 'Exportar Excel'"></span>
                            </button>
                            <button type="button" @click="exportar('pdf')" :disabled="exportandoPdf"
                                class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-semibold text-white bg-red-500 hover:bg-red-600 shadow-sm transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                                <i class="ti text-sm" :class="exportandoPdf ? 'ti-loader-2 animate-spin' : 'ti-file-type-pdf'"></i>
                                <span x-text="exportandoPdf ? 'Generando...' : 'Exportar PDF'"></span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
@endsection