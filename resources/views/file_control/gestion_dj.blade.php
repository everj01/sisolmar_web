@extends('layouts.vertical', ['title' => 'Gestión DJ'])

@section('css')
    <style>
        /* Placeholders más opacos globalmente en esta vista */
        ::placeholder {
            color: #9ca3af !important;
            opacity: 1 !important;
        }

        ::-webkit-input-placeholder {
            color: #9ca3af !important;
        }

        ::-moz-placeholder {
            color: #9ca3af !important;
        }
        .tab-btn.border-b-white {
            margin-bottom: -1px;
            border-bottom-color: white !important;
        }

        .hover-select-nativo:hover {
            background-color: #2563eb !important; /* Azul Tailwind */
            color: #ffffff !important;
        }
    </style>
@endsection

@section('content')

    @include("layouts.shared/page-title", ["subtitle" => "DJ", "title" => "Gestion DJ"])

    <div id="divListado" class="grid lg:grid-cols-1 gap-6 mt-8">
        <div class="card overflow-hidden">
            <div class="card-header border-b border-gray-100 py-4 px-5">
                <div class="flex flex-wrap justify-between items-center gap-4">
                    <div>
                        <h4 class="text-lg font-bold text-primary uppercase flex items-center">
                            <i class='bx bx-id-card text-2xl mr-2'></i> REGISTRO DE PERSONAL (DJ)
                        </h4>
                    </div>
                    <div class="flex gap-2">
                        <div class="bg-blue-50 px-3 py-2 rounded-lg border border-blue-200 text-center min-w-[90px] shadow-sm">
                            <span class="block text-[9px] text-blue-600 font-bold uppercase">Total</span>
                            <span id="countTotalPen" class="text-lg font-bold text-blue-700">0</span>
                        </div>
                        <div class="bg-green-50 px-3 py-2 rounded-lg border border-green-200 text-center min-w-[90px] shadow-sm">
                            <span class="block text-[9px] text-green-600 font-bold uppercase">Vigentes</span>
                            <span id="countVigentesPen" class="text-lg font-bold text-green-700">0</span>
                        </div>
                        <div class="bg-red-50 px-3 py-2 rounded-lg border border-red-200 text-center min-w-[90px] shadow-sm">
                            <span class="block text-[9px] text-red-600 font-bold uppercase">No Vigentes</span>
                            <span id="countNoVigentesPen" class="text-lg font-bold text-red-700">0</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CONTROLES COMUNES --}}
            <div class="w-full px-5 py-2 mt-2 flex justify-between items-center">
                <input type="text" id="buscarPersonal" placeholder="Buscar por nombre o DNI..."
                    class="w-48 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm uppercase"
                    style="width: 50%;  max-width: 450px; min-width: 200px;" autocomplete="off" />

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
                <div class="flex flex-col gap-2">
                    <!-- <button type="button" id="btnNuevaDJ"
                        class="btn rounded-full bg-primary/25 text-primary hover:bg-primary hover:text-white flex items-center gap-1 px-4 py-1"
                        data-hs-overlay="#modalDjGestion">
                        <i class='bx bx-plus text-base'></i>
                        <span>Nueva DJ</span>
                    </button> -->

                     @if($tipoUsuario != 9 && $tipoUsuario != 8) 
                     <div class="flex items-center justify-center gap-2">
                            <button type="button" id="btnNuevaDJ"
                            class="btn rounded-full bg-primary/25 text-primary hover:bg-primary hover:text-white flex items-center gap-1 px-4 py-1">
                            <i class='bx bx-plus text-base'></i>
                            <span>Nueva DJ</span>
                        </button>
                         <button type="button" id="btnAbrirReporte" class="btn rounded-full  bg-dark/25 text-dark hover:bg-dark hover:text-white flex items-center gap-1 px-4 py-1">
                            <i class='bx bx-file-find'></i> Reporte General
                        </button>
                     </div>
                        
                    @endif
                    
                    <button type="button" id="btnExtFirmaHuella" hidden disabled
                        class="btn rounded-full bg-warning/25 text-warning hover:bg-warning hover:text-white hidden items-center gap-1 px-4 py-1">
                                            <i class='bx bx-outline'></i>
                        <span>Extraer Firma y Huella</span>
                    </button>
                </div>
                
            </div>

            {{-- TABLA PESTAÑA 1: sin columna Migrado --}}
            <div id="panelPendiente" class="w-full px-5 py-2 mt-1">

                {{-- FILTROS EN CARD --}}
                <div class="flex flex-wrap items-center justify-between gap-4 mb-4 bg-slate-50 p-4 rounded-lg border border-slate-200">
                    <div class="flex flex-wrap items-center gap-5">

                        {{-- Sucursal --}}
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700">Sucursal:</label>
                            @php $sucursalesFiltradas = array_slice($sucursales, 1); @endphp
                            <select id="filtroSucursalPEN"
                                class="form-select text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
                                @if(count($sucursalesFiltradas) > 1)
                                    <option value="">Todas</option>
                                @endif
                                @foreach ($sucursalesFiltradas as $sucursal)
                                    <option value="{{ $sucursal->codigo }}">{{ $sucursal->abreviatura }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Tipo --}}
                        <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                            <label class="text-sm font-medium text-gray-700">Tipo:</label>
                            <select id="filtroTipoPerPEN" class="form-select text-sm w-44 px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
                                @if($tipoPerLimitar == 0)
                                    <option value="">Todos</option>
                                    <option value="OPERATIVO 4°">Operativo 4°</option>
                                    <option value="OPERATIVO 5°">Operativo 5°</option>
                                    <option value="ADMINISTRATIVO 4°">Administrativo 4°</option>
                                    <option value="ADMINISTRATIVO 5°">Administrativo 5°</option>
                                    <option value="ESPECIAL">Especial</option>
                                @elseif($tipoPerLimitar == 1)
                                    <option value="">Todos</option>
                                    <option value="ADMINISTRATIVO 4°">Administrativo 4°</option>
                                    <option value="ADMINISTRATIVO 5°">Administrativo 5°</option>
                                @elseif($tipoPerLimitar == 2)
                                    <option value="">Todos</option>
                                    <option value="OPERATIVO 4°">Operativo 4°</option>
                                    <option value="OPERATIVO 5°">Operativo 5°</option>
                                @endif
                            </select>
                        </div>

                        {{-- Cargo --}}
                        <div class="flex items-center gap-2 w-80"> <!-- Ampliado a w-80 -->
                            <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Cargo:</label>
                            <div class="flex-1 relative" id="custom-select-cargo">
                                <input type="text" id="filtroCargoPEN" placeholder="Todos" autocomplete="off"
                                    class="form-input text-sm w-full pl-3 pr-8 py-1.5 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white cursor-text" />
                                
                                <!-- Icono Flecha (Igual a un select nativo) -->
                                <div class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>

                                <!-- Lista Desplegable: min-w-full y w-max permiten que crezca sin limitarse al input -->
                                <div id="listaCargosPEN" class="absolute z-50 min-w-full w-max bg-white border border-gray-300 rounded-lg shadow-lg mt-1 hidden max-h-60 overflow-y-auto overflow-x-hidden py-1">
                                    <!-- Las opciones se inyectan con JS -->
                                </div>
                            </div>
                        </div>

                        {{-- Estado (Vigencia) --}}
                        <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                            <label class="text-sm font-medium text-gray-700">Estado:</label>
                            <select id="filtroVigenciaPEN" class="form-select text-sm w-36 pl-3 pr-8 py-1.5 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
                                <option value="" selected>Todos</option>
                                <option value="SI">Activos</option>
                                <option value="NO">Cesados</option>
                            </select>
                        </div>

                    </div>

                    {{-- Botón generar seleccionados --}}
                    @if($tipoUsuario != 9 && $tipoUsuario != 8)
                    <button type="button" id="btnGenerarSeleccionadosPEN" disabled
                        class="flex items-center gap-1.5 px-4 py-1.5 text-sm font-medium bg-indigo-400 text-white rounded-lg cursor-not-allowed opacity-50 transition-colors">
                        <i class='bx bxs-file-pdf text-base'></i>
                        Generar DJ (<span id="countSelPEN">0</span>)
                    </button>
                    @endif
                </div>

                <div id="tblPersonas" class="w-full mt-2"></div>

                <div class="flex items-center gap-2 mt-3">
                    <label for="page-size" class="text-sm text-gray-600">Mostrar</label>
                    <select id="page-size"
                        class="w-20 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="text-sm text-gray-600">registros</span>
                </div>
            </div>

            {{-- TABLA PESTAÑA 2: sin columna Estado, con columna Migrado --}}


<div id="panelMigrado" class="w-full px-5 py-2 mt-1 hidden">

    {{-- FILTROS + BOTONES --}}
    <div class="flex justify-between items-start gap-4 mb-4">

    <div class="flex flex-col items-start gap-2">

            {{-- Contador --}}
            <div>
                <div class="flex items-baseline gap-1 px-3 py-1 bg-gray-100 rounded-lg border border-gray-200">
                <span id="contadorFiltrado" class="text-base font-medium text-gray-800">0</span>
                <span class="text-sm text-gray-400">/</span>
                <span id="contadorTotal" class="text-sm text-gray-500">0</span>
            </div>
            </div>
            

            {{-- Reportes --}}
            <div class="flex items-center gap-2">
                <button type="button" id="btnReporteFaltantes"
                    class="flex items-center gap-1.5 px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                    <i class='bx bx-download text-base'></i>
                    Rep. faltantes
                </button>
                <button type="button" id="btnReporteActualizacion"
                    class="flex items-center gap-1.5 px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                    <i class='bx bx-download text-base'></i>
                    Rep. Actualización
                </button>
            </div>

            {{-- DJ Unificado --}}
            <div class="flex items-center gap-2">
                <button type="button" id="btnDJUnificado"
                    class="btn border-warning text-warning hover:bg-warning hover:text-white">
                    <i class='bx bx-file text-base'></i>
                    DJ Unificado
                </button>
                <button type="button" id="btnDJUnificadoMigrado"
                    class="btn border-primary text-primary hover:bg-primary hover:text-white">
                    <i class='bx bx-file text-base'></i>
                    DJ Unificado (Migrados)
                </button>
                <button type="button" id="btnResetearDJs"
                    class="btn bg-danger text-white">
                    <i class='bx bx-reset text-base'></i>
                    Resetear marcas
                </button>
            </div>

        </div>

        {{-- IZQUIERDA: Filtros --}}
        <div class="flex items-center gap-3 flex-wrap">
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Sucursal:</label>
                <!-- <select id="filtroSucursal"
                    class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
                    <option value="">Todas</option>
                </select> -->
                
                @php
                    $sucursalesFiltradas = array_slice($sucursales, 1);
                @endphp

                <select id="filtroSucursal" class="w-20 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
                    
                    @if(count($sucursalesFiltradas) > 1)
                        <option value="">Todas</option>
                    @endif

                    @foreach ($sucursalesFiltradas as $sucursal)
                        <option value="{{ $sucursal->codigo }}">
                            {{ $sucursal->abreviatura }}
                        </option>
                    @endforeach

 

                </select>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Tipo:</label>
                <!-- <select id="filtroTipoPer"
                    class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
                    <option value="">Todos</option>
                    <option value="OPERATIVO">Operativo</option>
                    <option value="ADMINISTRATIVO">Administrativo</option>
                    <option value="ESPECIAL">Especial</option>
                </select> -->
                <select id="filtroTipoPer" class="w-20 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
                    @if($tipoPerLimitar == 0)
                        <option value="">Todos</option>
                        <option value="OPERATIVO">Operativo</option>
                        <option value="ADMINISTRATIVO">Administrativo</option>
                        <!-- <option value="ESPECIAL">Especial</option> -->
                    @elseif($tipoPerLimitar == 1)
                        <option value="ADMINISTRATIVO" selected>Administrativo</option>
                    @elseif($tipoPerLimitar == 2)
                        <option value="OPERATIVO" selected>Operativo</option>
                    @endif
                </select>
            </div>
        </div>

        {{-- DERECHA: Contador + Botones --}}
        

    </div>

    {{-- DIVISOR ENTRE FILTROS Y TABLA --}}
    <div class="flex items-center gap-3 mb-4">
        <div class="flex-1 border-t border-gray-200"></div>
        <span class="text-xs text-gray-400 font-medium uppercase tracking-wider px-2">
            <i class='bx bx-table mr-1'></i>Resultados
        </span>
        <div class="flex-1 border-t border-gray-200"></div>
    </div>

    <div id="tblPersonasMigrado" class="w-full"></div>

    <div class="flex items-center gap-2 mt-3">
        <label for="page-size-migrado" class="text-sm text-gray-600">Mostrar</label>
        <select id="page-size-migrado" 
            class="w-20 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="20" selected>20</option>
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
    @include('file_control.rrhh.partials_modal_nueva_dj')
    @include('file_control.rrhh.partials_modal_ext_firmahuella')
    @include('file_control.rrhh.partials_modal_reporte')

    {{-- MODAL BIOMÉTRICO (Migrado desde Actualizar DJ) --}}
    <button id="btn-modal-biometrico" data-hs-overlay="#modal-biometrico" class="hidden"></button>
    @include('file_control.rrhh.partials_modal_comparacion_huellafirma_dni')

    {{-- MODAL DE CARGA DE DJ (Migrado desde Actualizar DJ) --}}
    <button type="button" class="hidden" id="btn-modal-dj_E4C" data-hs-overlay="#modal-carga-dj_E4C"></button>
    <div id="modal-carga-dj_E4C" class="hs-overlay hidden fixed inset-0 z-[80] overflow-y-auto transition-all duration-500 pointer-events-none">
        <div class="hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 translate-y-10 opacity-0 ease-in-out transition-all duration-500 sm:max-w-lg w-full my-8 sm:mx-auto flex flex-col bg-white shadow-sm rounded-lg pointer-events-auto border border-gray-200">
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Subir Declaración Jurada</h3>
                    <p class="text-sm text-gray-500 font-medium nombre-personal_E4C mt-0.5"></p>
                </div>
                <button type="button" id="btn-modal-dj-close_E4C" class="text-gray-500 hover:text-gray-700 transition-colors" data-hs-overlay="#modal-carga-dj_E4C">
                    <i class="bx bx-x text-2xl"></i>
                </button>
            </div>
            <form id="formSubirDJ_E4C">
                @csrf
                <input type="hidden" id="codPersonalDJ_E4C" value="">
                <div class="px-5 py-5 space-y-5">
                    <p class="text-sm text-center text-gray-500 bg-blue-50 p-2 rounded-lg border border-blue-100">
                        Solo se acepta archivo <strong>PDF</strong> con un peso máximo de <strong>1 MB</strong>.
                    </p>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Fecha de Emisión</label>
                        <input type="date" id="fecha_emision_dj_E4C" required class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Archivo PDF</label>
                        <div id="zonaDropDJ_E4C" role="button" class="cursor-pointer border-2 border-dashed border-gray-300 rounded-xl p-8 flex flex-col items-center justify-center gap-3 hover:border-primary hover:bg-blue-50 transition-colors">
                            <span class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-white shadow-sm border border-gray-200 text-gray-600">
                                <i class="bx bxs-file-pdf text-3xl"></i>
                            </span>
                            <div class="text-center text-sm text-gray-600">
                                <span class="font-medium">Haz click aquí para </span>
                                <span class="font-bold text-blue-600 hover:underline">SELECCIONAR</span>
                            </div>
                            <p class="text-xs text-gray-400">Máximo 1 MB</p>
                            <input type="file" id="archivoDJ_E4C" accept=".pdf" class="hidden">
                        </div>
                        <ul id="listaArchivosDJ_E4C" class="mt-3 space-y-2"></ul>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-gray-200 bg-slate-50 rounded-b-lg">
                    <button type="submit" class="btn bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-1" id="btn-guardar-dj_E4C">
                        <i class="bx bx-upload text-lg"></i> Subir DJ
                    </button>
                    <button type="button" id="btn-cancelar-dj_E4C" class="btn border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 px-4 py-2 rounded-lg text-sm font-medium" data-hs-overlay="#modal-carga-dj_E4C">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@section('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.1/dist/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        if (window.pdfjsLib) {
            pdfjsLib.GlobalWorkerOptions.workerSrc =
                'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }
    </script>

    <script>
        window.logoUrl = "{{ asset('images/logo_sol.png') }}";
    </script>
@endsection

 @vite([
        'resources/js/functions/gestion_dj.js',
       'resources/js/functions/nueva_dj.js', 
       'resources/js/functions/modal_reporte.js'
    ])


