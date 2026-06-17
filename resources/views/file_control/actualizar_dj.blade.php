@extends('layouts.vertical', ['title' => 'Actualizar DJ'])

@section('css')
    <style>
        ::placeholder {
            color: #9ca3af !important;
            opacity: 1 !important;
        }
    </style>
@endsection

@section('content')

    @include("layouts.shared/page-title", ["subtitle" => "DJ", "title" => "Actualizar DJ"])

    <div class="grid lg:grid-cols-1 gap-6 mt-8">
        <div class="card overflow-hidden">
            <div class="card-header border-b border-gray-200 pb-2">
                <h4 class="card-title text-lg font-medium text-primary">Migración (SIP)</h4>
            </div>

            <div class="w-full px-5 py-4">
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
                            <button type="button" id="btnDJUnificadoMigrado"
                                class="btn border-primary text-primary hover:bg-primary hover:text-white px-3 py-1 text-sm rounded-lg">
                                <i class='bx bx-file text-base'></i>
                                DJ Unificado (Migrados)
                            </button>
                            <button type="button" id="btnResetearDJs"
                                class="btn bg-danger text-white px-3 py-1 text-sm rounded-lg">
                                <i class='bx bx-reset text-base'></i>
                                Resetear marcas
                            </button>
                        </div>
                    </div>

                    {{-- IZQUIERDA: Búsqueda y Filtros --}}
                    <div class="flex flex-col items-end gap-3">
                        <div class="flex items-center gap-2">
                            <input type="text" id="buscarPersonal" placeholder="Buscar por nombre o DNI..."
                            class="w-48 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm uppercase"
                            style="min-width: 250px;" autocomplete="off" />
                        </div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-600">Sucursal:</label>
                                @php
                                    $sucursalesFiltradas = array_slice($sucursales, 1);
                                @endphp
                                <select id="filtroSucursal" class="w-24 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
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
                                <select id="filtroTipoPer" class="w-44 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
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
                        </div>
                    </div>
                </div>

                {{-- DIVISOR ENTRE FILTROS Y TABLA --}}
                <div class="flex items-center gap-3 mb-4 mt-6">
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

    {{-- Incluimos el modal general de DJ que sirve para editar/ver con los datos cargados --}}
    @include('file_control.rrhh.partials_modal_dj')

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
        window.logoUrl = "{{ asset('images/logo_sol.png') }}";
    </script>
@endsection

@vite([
    'resources/js/functions/actualizar_dj.js',
    'resources/js/functions/nueva_dj.js'
])