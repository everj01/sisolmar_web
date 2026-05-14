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
<!-- Perdón si el código está en este estado. Lo separaría mediante componentes pero no hay tiempo y paciencia :P -->

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
            class="card-hover relative overflow-hidden rounded-2xl border border-default-200/60 bg-white shadow-sm group">
            <div
                class="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
            </div>
            <div class="relative p-6 flex flex-col h-full">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                        <i class="ti ti-file-report text-2xl text-primary"></i>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-default-900 mb-2">Reporte por capacitación</h3>
                <p class="text-sm text-default-500 leading-relaxed flex-grow mb-4">
                    Filtre por sistema de gestión, área, sucursal, período y curso para obtener el listado del personal
                    con su estado en la capacitación. Visualice, ordene por columnas y exporte los resultados a Excel o PDF.
                </p>
                <button @click="abrirModalReporte()"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-600 transition-colors shadow-sm w-full">
                    Generar reporte
                </button>
            </div>
        </div>
    </div>

    {{-- Modal --}}
    <div id="modal-reporte-capacitacion" x-data="modalReporte" x-show="open" x-cloak
        class="fixed inset-0 z-[80] flex items-center justify-center p-4" style="background: rgba(36,39,70,0.45);">

        <div :class="view === 'personal' ? 'max-w-6xl' : 'max-w-xl'"
            class="flex flex-col w-full bg-white rounded-2xl shadow-2xl shadow-primary/10 border border-default-200 animate-fade-in transition-all duration-300">

            {{-- Header --}}
            <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-4">

                <div class="flex items-center gap-3.5">

                    <div
                        class="relative flex-shrink-0 w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white shadow-sm">
                        <i class="ti ti-file-report text-lg"></i>
                    </div>

                    <div>

                        <div x-show="view === 'filters'">
                            <h3 class="text-[15px] font-semibold text-default-900 leading-tight">
                                Generar Reporte
                            </h3>

                            <p class="text-xs text-default-500 mt-0.5">
                                Seleccione los filtros para generar el reporte
                            </p>
                        </div>

                        <div x-show="view === 'personal'">
                            <h3 class="text-[15px] font-semibold text-default-900 leading-tight">
                                Personal encontrado
                                <span x-show="selectedEstado === 'PENDIENTE'">
                                    sin iniciar la capacitación de <span x-text="nombreCurso"></span>
                                </span>
                            </h3>

                            <p class="text-xs text-default-500 mt-0.5">
                                Resultado del reporte generado
                            </p>
                        </div>

                    </div>
                </div>

                <button type="button" @click="cerrar()"
                    class="flex-shrink-0 w-7 h-7 inline-flex items-center justify-center rounded-lg text-default-400 hover:text-default-700 hover:bg-default-100 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors cursor-pointer">

                    <i class="ti ti-x text-base"></i>
                </button>
            </div>

            {{-- Filters --}}
            <div x-show="view === 'filters'" class="px-6 pb-1 space-y-4">

                {{-- Sistema --}}
                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Sistema de gestión <span class="text-danger">*</span>
                    </label>

                    <select x-model="selectedSistema" @change="cargarAreas($event.target.value)"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">

                        <option value="" x-text="loadingSistemas ? 'Cargando sistemas...' : (sistemas.length === 0 ? 'Sin sistemas disponibles' : 'Seleccione')"></option>

                        <template x-for="option in sistemas" :key="option.codigo">
                            <option :value="option.codigo" x-text="option.descripcion"></option>
                        </template>
                    </select>
                </div>

                {{-- Área --}}
                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Área responsable <span class="text-danger">*</span>
                    </label>

                    <select x-model="selectedArea" @change="cargarCursos($event.target.value)"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">

                        <option value="" x-text="loadingAreas ? 'Cargando áreas...' : (selectedSistema && areas.length === 0 ? 'Sin áreas disponibles' : 'Seleccione')"></option>

                        <template x-for="option in areas" :key="option.codModdle">
                            <option :value="option.codModdle" x-text="option.Area"></option>
                        </template>
                    </select>
                </div>

                {{-- Sucursal --}}
                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Sucursal <span class="text-danger">*</span>
                    </label>

                    <select x-model="selectedSucursal"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">

                        <option value="" x-text="loadingSucursales ? 'Cargando sucursales...' : (sucursales.length === 0 ? 'Sin sucursales disponibles' : 'Seleccione Sucursal')"></option>

                        <template x-for="option in sucursales" :key="option.codigo">
                            <option :value="option.codigo" x-text="option.sucursal"></option>
                        </template>
                    </select>
                </div>

                {{-- Periodo --}}
                <!--<div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Periodo <span class="text-danger">*</span>
                    </label>

                    <select x-model="selectedPeriodo" @change="filtrarCursosPorPeriodo()"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">

                        <option value="" x-text="periodos.length === 0 ? 'Sin periodos disponibles' : 'Seleccione Periodo'"></option>

                        <template x-for="option in periodos" :key="option.id">
                            <option :value="option.periodo" x-text="option.periodo"></option>
                        </template>
                    </select>
                </div>-->

                {{-- Curso --}}
                <div>
                    <label class="text-xs font-medium text-default-700 mb-1.5 block">
                        Curso de capacitación <span class="text-danger">*</span>
                    </label>

                    <select x-model="selectedCurso"
                        class="w-full h-9 px-3 text-sm bg-white border border-default-200 rounded-lg text-default-900 placeholder-default-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">

                        <option value="" x-text="!selectedArea ? 'Seleccione Curso' : (loadingCursos ? 'Cargando cursos...' : (cursos.length > 0 ? cursos.length + ' resultado(s)' : 'Sin cursos disponibles'))"></option>

                        <template x-for="option in cursos" :key="option.id">
                            <option :value="option.id" x-text="option.fullname"></option>
                        </template>
                    </select>
                </div>

                {{-- Estado --}}
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

            {{-- Body personal --}}
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

                    <!-- CONTENEDOR FLEX -->
                    <div class="flex flex-col max-h-[500px] border border-default-200 rounded-xl">

                        <!-- TABLA SCROLL -->
                        <div class="flex-1 overflow-auto">

                            <table class="min-w-full text-sm">

                                <thead class="bg-default-50 border-b border-default-200 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-default-700 w-14">#</th>
                                        <th @click="ordenar('CodigoPers')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors">
                                            Código Pers.
                                            <span class="ml-1 text-xs" :class="sortColumn === 'CodigoPers' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'CodigoPers' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('NombreCompleto')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors">
                                            Nombre completo
                                            <span class="ml-1 text-xs" :class="sortColumn === 'NombreCompleto' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'NombreCompleto' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('DNI')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors">
                                            DNI
                                            <span class="ml-1 text-xs" :class="sortColumn === 'DNI' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'DNI' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('TipoTrabajador')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors">
                                            Tipo trabajador
                                            <span class="ml-1 text-xs" :class="sortColumn === 'TipoTrabajador' ? 'text-primary' : 'text-default-300'"
                                                x-text="sortColumn === 'TipoTrabajador' ? (sortDirection === 'asc' ? '↑' : '↓') : '↕'"></span>
                                        </th>
                                        <th @click="ordenar('Estado')"
                                            class="px-4 py-3 text-left font-semibold text-default-700 cursor-pointer select-none hover:text-primary transition-colors">
                                            Estado
                                            <span class="ml-1 text-xs" :class="sortColumn === 'Estado' ? 'text-primary' : 'text-default-300'"
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

                        <!-- FOOTER STICKY REAL (SIN CAMBIAR TU LÓGICA) -->
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

                                <button @click="currentPage--" :disabled="currentPage === 1"
                                    class="px-3 h-8 rounded-lg border border-default-200 bg-white text-sm hover:bg-default-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Anterior
                                </button>

                                <div class="px-3 text-sm text-default-600">
                                    Página
                                    <span x-text="currentPage"></span>
                                    de
                                    <span x-text="totalPages"></span>
                                </div>

                                <button @click="currentPage++" :disabled="currentPage >= totalPages"
                                    class="px-3 h-8 rounded-lg border border-default-200 bg-white text-sm hover:bg-default-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Siguiente
                                </button>

                            </div>
                        </div>

                    </div>

                </template>

            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-2 px-6 py-4 mt-2 border-t border-default-100">

                <button type="button" @click="cerrar()"
                    class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-default-600 bg-default-100 hover:bg-default-200 hover:text-default-800 transition-all cursor-pointer">

                    Cancelar
                </button>

                <template x-if="view === 'filters'">

                    <button type="button" @click="obtenerPersonal()" :disabled="loadingPersonal"
                        class="px-4 h-9 inline-flex items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-white bg-primary hover:bg-primary/90 shadow-sm shadow-primary/20 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">

                        <i class="ti ti-arrow-right text-sm"></i>

                        <span x-text="loadingPersonal ? 'Cargando...' : 'Obtener personal'"></span>
                    </button>
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
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
@vite(['resources/js/app.js'])
@endsection