@extends('layouts.vertical', ['title' => 'Gestión DJ'])

@section('css')
<style>
    /* Placeholders más opacos globalmente en esta vista */
    ::placeholder { color: #9ca3af !important; opacity: 1 !important; }
    ::-webkit-input-placeholder { color: #9ca3af !important; }
    ::-moz-placeholder { color: #9ca3af !important; }
</style>
@endsection

@section('content')

    @include("layouts.shared/page-title", ["subtitle" => "Recursos Humanos", "title" => "File Control"])

    <div id="divListado" class="grid lg:grid-cols-1 gap-6 mt-8">
        <div class="card overflow-hidden">
            <div class="card-header">
                <h4 class="card-title">Listado de Personal</h4>
            </div>

            {{-- PESTAÑAS --}}
            <div class="px-5 pt-4">
                <div class="flex gap-1 border-b border-gray-200 justify-center items-center">
                    <button
                        id="tabBtnPendiente"
                        type="button"
                        class="tab-btn px-5 py-2 text-sm font-medium rounded-t-lg border border-b-0 transition-colors
                               bg-white text-primary border-gray-200"
                        data-tab="pendiente">
                        Pendientes / Listos
                    </button>
                    <button
                        id="tabBtnMigrado"
                        type="button"
                        class="tab-btn px-5 py-2 text-sm font-medium rounded-t-lg border border-b-0 transition-colors
                               bg-gray-50 text-gray-500 border-transparent hover:text-gray-700"
                        data-tab="migrado">
                        Migración
                    </button>
                </div>
            </div>

            {{-- CONTROLES COMUNES --}}
            <div class="w-full px-5 py-2 mt-2 flex justify-between items-center">
                <input
                    type="text"
                    id="buscarPersonal"
                    placeholder="Buscar por nombre o DNI..."
                    class="w-48 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm uppercase"
                    style="width: 50%;  max-width: 450px; min-width: 200px;"
                    autocomplete="off"
                />

                <!-- <button type="button" id="btnDescargarDJs"
                    class="flex items-center gap-1.5 px-3 py-1.5 text-sm border border-primary rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors">
                    <i class='bx bx-archive-in text-base'></i>
                    Descargar DJ's
                </button>
                <button type="button" id="btnDJUnificado"
                    class="flex items-center gap-1.5 px-3 py-1.5 text-sm border border-amber-500 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-500 hover:text-white transition-colors">
                    <i class='bx bx-file text-base'></i>
                    DJ Unificado

                </button> -->

                <button type="button"
                    id="btnNuevaDJ"
                    class="btn rounded-full bg-primary/25 text-primary hover:bg-primary hover:text-white flex items-center gap-1 px-4 py-1"
                    data-hs-overlay="#modalDjGestion">
                    <i class='bx bx-plus text-base'></i>
                    <span>Nueva DJ</span>
                </button>
            </div>

            {{-- TABLA PESTAÑA 1: sin columna Migrado --}}
            <div id="panelPendiente" class="w-full px-5 py-2 mt-1">
                <div id="tblPersonas" class="w-full mt-5"></div>
                <div class="flex items-center gap-2 mt-3">
                    <label for="page-size" class="text-sm text-gray-600">Mostrar</label>
                    <select id="page-size"
                        class="w-20 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="text-sm text-gray-600">registros</span>
                </div>
            </div>

            {{-- TABLA PESTAÑA 2: sin columna Estado, con columna Migrado --}}
            <div id="panelMigrado" class="w-full px-5 py-2 mt-1 hidden">
                {{-- FILTROS ADICIONALES --}}
                <div class="flex gap-3 mb-3 flex-wrap">
                    <div class="flex gap-3 mb-3 flex-wrap items-center">
    <div class="flex items-center gap-2">
        <label class="text-sm text-gray-600">Sucursal:</label>
        <select id="filtroSucursal"
            class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
            <option value="">Todas</option>
        </select>
    </div>
    <div class="flex items-center gap-2">
        <label class="text-sm text-gray-600">Tipo:</label>
        <select id="filtroTipoPer"
            class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
            <option value="">Todos</option>
            <option value="OPERATIVO">Operativo</option>
            <option value="ADMINISTRATIVO">Administrativo</option>
        </select>
    </div>

    {{-- Card contador + Botón reporte --}}
    <div class="flex items-center gap-2 ml-auto">
        <div class="flex items-baseline gap-1 px-3 py-1 bg-gray-100 rounded-lg border border-gray-200">
            <span id="contadorFiltrado" class="text-base font-medium text-gray-800">0</span>
            <span class="text-sm text-gray-400">/</span>
            <span id="contadorTotal" class="text-sm text-gray-500">0</span>
        </div>
        <button type="button" id="btnReporteFaltantes"
            class="flex items-center gap-1.5 px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-white text-gray-700 hover:bg-gray-50 transition-colors">
            <i class='bx bx-download text-base'></i>
            Rep. faltantes
        </button>

        <button type="button" id="btnReporteActualizacion"
            class="flex items-center gap-1.5 px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-white text-gray-700 hover:bg-gray-50 transition-colors">
            <i class='bx bx-download text-base'></i>
            Rep. Actualización

        <button type="button" id="btnDescargarDJs"
            class="flex items-center gap-1.5 px-3 py-1.5 text-sm border border-primary rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors">
            <i class='bx bx-archive-in text-base'></i>
            Descargar DJ's
        </button>
        <button type="button" id="btnDJUnificado"
            class="flex items-center gap-1.5 px-3 py-1.5 text-sm border border-amber-500 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-500 hover:text-white transition-colors">
            <i class='bx bx-file text-base'></i>
            DJ Unificado

        </button>
    </div>
</div>
                    
                </div>
                <div id="tblPersonasMigrado" class="w-full mt-5"></div>
                <div class="flex items-center gap-2 mt-3">
                    <label for="page-size-migrado" class="text-sm text-gray-600">Mostrar</label>
                    <select id="page-size-migrado"
                        class="w-20 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="text-sm text-gray-600">registros</span>
                </div>
            </div>

        </div>
    </div>

    <div id="divCoincidencias" class="grid lg:grid-cols-1 gap-6 mt-8 hidden">
        <div class="card overflow-hidden">
            <div class="card-header">
                <h4 class="card-title">Listado de COINCIDENCIAS</h4>
            </div>
            <div class="w-full px-5 py-2 mt-3">
                <input type="text" id="buscar" placeholder="Buscar..."
                    class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm" />
                <div id="tblPersonasCN" class="w-full mt-8"></div>
            </div>
        </div>
    </div>

    @include('file_control.rrhh.partials_modal_dj')

@endsection

@section('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.1/dist/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
    <script>
        window.logoUrl = "{{ asset('images/logo_sol.png') }}";
    </script>
@endsection

@vite(['resources/js/functions/gestion_dj.js'])