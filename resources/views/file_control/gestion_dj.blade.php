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
    </style>
@endsection

@section('content')

    @include("layouts.shared/page-title", ["subtitle" => "DJ", "title" => "Gestion DJ"])

    <div id="divListado" class="grid lg:grid-cols-1 gap-6 mt-8">
        <div class="card overflow-hidden">
            <div class="card-header">
                <h4 class="card-title">Registro de personal (DJ)</h4>
            </div>
       

            {{-- TÍTULO ESTÁTICO (Sin pestañas) --}}
            <div class="px-5 pt-4 border-b border-gray-200 pb-2">
                <h5 class="text-lg font-medium text-primary">DJ Listos</h5>
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
                <div class="flex gap-3 mb-3 flex-wrap">
                    <div class="flex gap-3 mb-3 flex-wrap items-center">
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">Sucursal:</label>
                            
                            @php
                                $sucursalesFiltradas = array_slice($sucursales, 1);
                            @endphp

                            <select id="filtroSucursalPEN" 
                            class=" px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">

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
                          
                            <select id="filtroTipoPerPEN" class="w-44 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
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
                         {{-- <button type="button" id="btnDescargarDJs_PEN"
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-sm border border-primary rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors">
                                    <i class='bx bx-archive-in text-base'></i>
                                    Descargar DJ's
                                </button> --}}
                                <button type="button" id="btnDJUnificado_PEN"
                                    class="btn border-warning text-warning hover:bg-warning hover:text-white">
                                    <i class='bx bx-file text-base'></i>
                                    DJ Unificado

                                </button>

                        {{-- Card contador + Botón reporte --}}
                        
                    </div>

                </div>
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
    <button id="btn-modal-biometrico" data-hs-overlay="#modal-biometrico" class="hidden"></button>

    @include('file_control.rrhh.partials_modal_dj')
    @include('file_control.rrhh.partials_modal_nueva_dj')
    @include('file_control.rrhh.partials_modal_ext_firmahuella')
    @include('file_control.rrhh.partials_modal_reporte')

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
