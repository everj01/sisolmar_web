@extends('layouts.vertical', ['title' => 'Actualizar DJ'])

@section('css')
    <style>
        ::placeholder { color: #9ca3af !important; opacity: 1 !important; }
        .tab-content { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
@endsection

@section('content')

    @include("layouts.shared/page-title", ["subtitle" => "DJ", "title" => "Actualizar DJ"])

    <div class="mt-6 mb-6 overflow-x-auto">
        <nav class="flex items-center justify-center space-x-3 border-b border-gray-200 pb-3 min-w-max" aria-label="Tabs" id="dj-timeline-tabs">
            <button data-target="etapa1" data-active="bg-blue-900 text-white shadow-lg" data-inactive="bg-white text-gray-500 border border-gray-200"
                class="tab-btn {{ $esRrhhMigracion ? 'hidden' : 'active bg-blue-900 text-white shadow-lg' }} rounded-full px-5 py-2.5 text-sm font-medium transition-all whitespace-nowrap">
                1° ETAPA: Actualización por SIP
            </button>
            <button data-target="etapa2" data-active="bg-blue-900 text-white shadow-lg" data-inactive="bg-white text-gray-500 border border-gray-200"
                class="tab-btn {{ $esRrhhMigracion ? 'hidden' : 'bg-white text-gray-500 border border-gray-200' }} rounded-full px-5 py-2.5 text-sm font-medium transition-all whitespace-nowrap hover:border-blue-300 hover:text-blue-700">
                2° ETAPA: Verificación
            </button>
            <button data-target="etapa3" data-active="bg-blue-900 text-white shadow-lg" data-inactive="bg-white text-gray-500 border border-gray-200"
                class="tab-btn {{ $esRrhhMigracion ? 'hidden' : 'bg-white text-gray-500 border border-gray-200' }} rounded-full px-5 py-2.5 text-sm font-medium transition-all whitespace-nowrap hover:border-blue-300 hover:text-blue-700">
                3° ETAPA: Generación DJ en PDF
            </button>

            <button data-target="etapa_carga" data-active="bg-blue-900 text-white shadow-lg" data-inactive="bg-white text-gray-500 border border-gray-200"
                class="tab-btn {{ $esRrhhMigracion ? 'active bg-blue-900 text-white shadow-lg' : 'bg-white text-gray-500 border border-gray-200' }} rounded-full px-5 py-2.5 text-sm font-medium transition-all whitespace-nowrap hover:border-blue-300 hover:text-blue-700">
                4° ETAPA: Carga de DJ
            </button>

            <button data-target="etapa4" data-active="bg-blue-900 text-white shadow-lg" data-inactive="bg-white text-gray-500 border border-gray-200"
                class="tab-btn {{ $esRrhhMigracion ? 'hidden' : 'bg-white text-gray-500 border border-gray-200' }} rounded-full px-5 py-2.5 text-sm font-medium transition-all whitespace-nowrap hover:border-blue-300 hover:text-blue-700">
                5° ETAPA: Escaneo DJ
            </button>
        </nav>
    </div>

    <div id="tabs-container">

        <div id="etapa1" class="tab-content {{ $esRrhhMigracion ? 'hidden' : 'active' }}">
            <div class="grid lg:grid-cols-1 gap-6">
                <div class="card overflow-hidden border border-gray-100 shadow-sm">
                    <div class="w-full px-5 py-4">

                        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
                            <div>
                                <h4 class="text-lg font-bold text-primary uppercase">Actualización por SIP</h4>
                                <p class="text-sm text-gray-500">Generador de reporte</p>
                            </div>

                            <div class="flex gap-2">
                                <div class="bg-blue-50 px-3 py-2 rounded-lg border border-blue-200 text-center min-w-[90px]">
                                    <span class="block text-[9px] text-blue-600 font-bold uppercase">Total</span>
                                    <span id="countTotalE1" class="text-lg font-bold text-blue-700">0</span>
                                </div>
                                <div class="bg-green-50 px-3 py-2 rounded-lg border border-green-200 text-center min-w-[90px]">
                                    <span class="block text-[9px] text-green-600 font-bold uppercase">Actualiz.</span>
                                    <span id="countActualizadosE1" class="text-lg font-bold text-green-700">0</span>
                                </div>
                                <div class="bg-red-50 px-3 py-2 rounded-lg border border-red-200 text-center min-w-[90px]">
                                    <span class="block text-[9px] text-red-600 font-bold uppercase">Sin Actual.</span>
                                    <span id="countSinActualizarE1" class="text-lg font-bold text-red-700">0</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-4 mb-6 bg-slate-50 p-4 rounded-lg border border-slate-200">
                            <div class="flex flex-wrap items-center gap-5">
                                <div class="flex items-center gap-2">
                                    <input type="text" id="buscarPersonalE1" placeholder="Buscar por nombre o DNI..." class="w-48 px-4 py-1.5 border border-gray-300 rounded-full focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-sm uppercase" style="min-width: 250px;" autocomplete="off" />
                                </div>

                                <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                                    <label class="text-sm font-medium text-gray-700">Sucursal:</label>
                                    @php $sucursalesFiltradasE1 = array_slice($sucursales, 1); @endphp
                                    <select id="filtroSucursalE1" class="form-select text-sm w-36 px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-primary">
                                        @if(count($sucursalesFiltradasE1) > 1) <option value="00">Todas</option> @endif
                                        @foreach ($sucursalesFiltradasE1 as $sucursal) <option value="{{ $sucursal->codigo }}">{{ $sucursal->abreviatura }}</option> @endforeach
                                    </select>
                                </div>

                                <div class="flex items-center gap-2">
    <label class="text-sm font-medium text-gray-700">Tipo:</label>
    <select id="filtroTipoE1" class="form-select text-sm w-44 px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-primary">
        @if($tipoPerLimitar == 0)
            <option value="00">Todos</option>
            <option value="01">Operativo 4°</option>
            <option value="03">Operativo 5°</option>
            <option value="02">Administrativo 4°</option>
            <option value="05">Administrativo 5°</option>
            {{-- <option value="06">Especial</option> --}}
        @elseif($tipoPerLimitar == 1)
                                            <option value="00">Todos</option>
                                            <option value="02">Administrativo 4°</option>
                                            <option value="05">Administrativo 5°</option>
                                        @elseif($tipoPerLimitar == 2)
                                            <option value="00">Todos</option>
                                            <option value="01">Operativo 4°</option>
                                            <option value="03">Operativo 5°</option>
                                        @endif
                                    </select>
                                </div>

                                <div class="flex items-center gap-4 border-l-2 border-gray-300 pl-5">
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="filtroEstadoE1" value="null" checked class="w-4 h-4 text-primary focus:ring-primary">
                                        <span class="text-sm font-medium text-gray-700">Todos</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="filtroEstadoE1" value="0" class="w-4 h-4 text-green-600 focus:ring-green-500">
                                        <span class="text-sm font-medium text-gray-700">Actualizados</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="filtroEstadoE1" value="1" class="w-4 h-4 text-red-600 focus:ring-red-500">
                                        <span class="text-sm font-medium text-gray-700">Faltan</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button id="btnExportExcelE1" class="flex items-center gap-1.5 px-4 py-1.5 text-sm font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                    <i class='bx bx-spreadsheet text-lg'></i> Excel
                                </button>
                                <button id="btnExportPdfE1" class="flex items-center gap-1.5 px-4 py-1.5 text-sm font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                    <i class='bx bxs-file-pdf text-lg'></i> PDF
                                </button>
                            </div>
                        </div>

                        <div id="tblEtapa1" class="w-full"></div>

                        <div class="flex items-center gap-2 mt-3">
                            <label for="page-size-etapa1" class="text-sm text-gray-600">Mostrar</label>
                            <select id="page-size-etapa1" class="w-20 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
                                <option value="5">5</option><option value="10">10</option><option value="20" selected>20</option><option value="50">50</option><option value="100">100</option>
                            </select>
                            <span class="text-sm text-gray-600">registros</span>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div id="etapa2" class="tab-content hidden">
            <div class="grid lg:grid-cols-1 gap-6">
                <div class="card overflow-hidden border border-gray-100 shadow-sm">
                    <div class="w-full px-5 py-4">

                        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
                            <div>
                                <h4 class="text-lg font-bold text-primary uppercase">Tabla de Comparación</h4>
                                <p class="text-sm text-gray-500">Verificación de datos</p>
                            </div>

                            <div class="flex gap-2">
                                <div class="bg-blue-50 px-3 py-2 rounded-lg border border-blue-200 text-center min-w-[90px]">
                                    <span class="block text-[9px] text-blue-600 font-bold uppercase">Total</span>
                                    <span id="contadorTotalE2" class="text-lg font-bold text-blue-700">0</span>
                                </div>
                                <div class="bg-green-50 px-3 py-2 rounded-lg border border-green-200 text-center min-w-[90px]">
                                    <span class="block text-[9px] text-green-600 font-bold uppercase">Verificados</span>
                                    <span id="contadorFiltradoE2" class="text-lg font-bold text-green-700">0</span>
                                </div>
                                <div class="bg-yellow-50 px-3 py-2 rounded-lg border border-yellow-200 text-center min-w-[90px]">
                                    <span class="block text-[9px] text-yellow-600 font-bold uppercase">Sin Verificar</span>
                                    <span id="contadorSinVerificarE2" class="text-lg font-bold text-yellow-700">0</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-4 mb-6 bg-slate-50 p-4 rounded-lg border border-slate-200">
                            <div class="flex flex-wrap items-center gap-5">
                                <div class="flex items-center gap-2">
                                    <input type="text" id="buscarPersonalE2" placeholder="Buscar por nombre o DNI..." class="w-48 px-4 py-1.5 border border-gray-300 rounded-full focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-sm uppercase" style="min-width: 250px;" autocomplete="off" />
                                </div>

                                <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                                    <label class="text-sm font-medium text-gray-700">Sucursal:</label>
                                    @php $sucursalesFiltradasE2 = array_slice($sucursales, 1); @endphp
                                    <select id="filtroSucursalE2" class="form-select text-sm w-36 px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-primary">
                                        @if(count($sucursalesFiltradasE2) > 1) <option value="00">Todas</option> @endif
                                        @foreach ($sucursalesFiltradasE2 as $sucursal) <option value="{{ $sucursal->codigo }}">{{ $sucursal->abreviatura }}</option> @endforeach
                                    </select>
                                </div>

                                <div class="flex items-center gap-2">
                                    <label class="text-sm font-medium text-gray-700">Tipo:</label>
                                    <select id="filtroTipoPerE2" class="form-select text-sm w-44 px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-primary">
                                        @if($tipoPerLimitar == 0)
                                            <option value="00">Todos</option><option value="01">Operativo 4°</option><option value="03">Operativo 5°</option><option value="02">Administrativo 4°</option><option value="05">Administrativo 5°</option>
                                        @elseif($tipoPerLimitar == 1)
                                            <option value="00">Todos</option><option value="02">Administrativo 4°</option><option value="05">Administrativo 5°</option>
                                        @elseif($tipoPerLimitar == 2)
                                            <option value="00">Todos</option><option value="01">Operativo 4°</option><option value="03">Operativo 5°</option>
                                        @endif
                                    </select>
                                </div>

                                <div class="flex items-center gap-4 border-l-2 border-gray-300 pl-5">
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="filtroEstadoE2" value="null" checked class="w-4 h-4 text-primary focus:ring-primary">
                                        <span class="text-sm font-medium text-gray-700">Todos</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="filtroEstadoE2" value="0" class="w-4 h-4 text-green-600 focus:ring-green-500">
                                        <span class="text-sm font-medium text-gray-700">Verificados</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="filtroEstadoE2" value="1" class="w-4 h-4 text-yellow-600 focus:ring-yellow-500">
                                        <span class="text-sm font-medium text-gray-700">Sin Verificar</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button type="button" id="btnReporteVerificados" class="flex items-center gap-1.5 px-4 py-1.5 text-sm font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                    <i class='bx bxs-file-pdf text-lg'></i> Reporte PDF
                                </button>
                                
                                {{-- NUEVO BOTÓN EXCEL (Generación nativa) --}}
                                <button type="button" id="btnExportExcelE2" class="flex items-center gap-1.5 px-4 py-1.5 text-sm font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                    <i class='bx bx-spreadsheet text-lg'></i> Excel
                                </button>
                                
                                <div class="hidden items-center gap-2">
                                    <button type="button" id="btnDJUnificadoVerificado" class="btn border-primary text-primary hover:bg-primary hover:text-white px-3 py-1 text-sm rounded-lg"><i class='bx bx-file text-base'></i> DJ Unificado</button>
                                    <button type="button" id="btnResetearDJsE2" class="btn bg-danger text-white px-3 py-1 text-sm rounded-lg"><i class='bx bx-reset text-base'></i> Resetear marcas</button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 mb-4 mt-6">
                            <div class="flex-1 border-t border-gray-200"></div>
                            <span class="text-xs text-gray-400 font-medium uppercase tracking-wider px-2"><i class='bx bx-table mr-1'></i>Resultados Verificados</span>
                            <div class="flex-1 border-t border-gray-200"></div>
                        </div>

                        <div id="tblPersonasVerificado" class="w-full"></div>

                        <div class="flex items-center gap-2 mt-3">
                            <label for="page-size-verificado" class="text-sm text-gray-600">Mostrar</label>
                            <select id="page-size-verificado" class="w-20 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
                                <option value="5">5</option><option value="10" selected>10</option><option value="20">20</option><option value="50">50</option><option value="100">100</option>
                            </select>
                            <span class="text-sm text-gray-600">registros</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="etapa3" class="tab-content hidden">
            <div class="grid lg:grid-cols-1 gap-6">
                <div class="card overflow-hidden border border-gray-100 shadow-sm">
                    <div class="w-full px-5 py-4">

                        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
                            <div>
                                <h4 class="text-lg font-bold text-primary uppercase">Generación de DJ Masivo</h4>
                                <p class="text-sm text-gray-500">Control de impresiones PDF</p>
                            </div>

                            <div class="flex gap-2">
                                <div class="bg-blue-50 px-3 py-2 rounded-lg border border-blue-200 text-center min-w-[90px]">
                                    <span class="block text-[9px] text-blue-600 font-bold uppercase">Total</span>
                                    <span id="contadorTotalE3" class="text-lg font-bold text-blue-700">0</span>
                                </div>
                                <div class="bg-green-50 px-3 py-2 rounded-lg border border-green-200 text-center min-w-[90px]">
                                    <span class="block text-[9px] text-green-600 font-bold uppercase">Generados</span>
                                    <span id="contadorGeneradosE3" class="text-lg font-bold text-green-700">0</span>
                                </div>
                                <div class="bg-yellow-50 px-3 py-2 rounded-lg border border-yellow-200 text-center min-w-[90px]">
                                    <span class="block text-[9px] text-yellow-600 font-bold uppercase">Pendientes</span>
                                    <span id="contadorPendientesE3" class="text-lg font-bold text-yellow-700">0</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-4 mb-6 bg-slate-50 p-4 rounded-lg border border-slate-200">
                            <div class="flex flex-wrap items-center gap-5">
                                <div class="flex items-center gap-2">
                                    <input type="text" id="buscarPersonalE3" placeholder="Buscar por nombre o DNI..." class="w-48 px-4 py-1.5 border border-gray-300 rounded-full focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-sm uppercase" style="min-width: 250px;" autocomplete="off" />
                                </div>

                                <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                                    <label class="text-sm font-medium text-gray-700">Sucursal:</label>
                                    @php $sucursalesFiltradasE3 = array_slice($sucursales, 1); @endphp
                                    <select id="filtroSucursalE3" class="form-select text-sm w-36 px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-primary">
                                        @if(count($sucursalesFiltradasE3) > 1) <option value="00">Todas</option> @endif
                                        @foreach ($sucursalesFiltradasE3 as $sucursal) <option value="{{ $sucursal->codigo }}">{{ $sucursal->abreviatura }}</option> @endforeach
                                    </select>
                                </div>

                                <div class="flex items-center gap-2">
                                    <label class="text-sm font-medium text-gray-700">Tipo:</label>
                                    <select id="filtroTipoPerE3" class="form-select text-sm w-44 px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-primary">
                                        @if($tipoPerLimitar == 0)
                                            <option value="00">Todos</option><option value="01">Operativo 4°</option><option value="03">Operativo 5°</option><option value="02">Administrativo 4°</option><option value="05">Administrativo 5°</option>
                                        @elseif($tipoPerLimitar == 1)
                                            <option value="00">Todos</option><option value="02">Administrativo 4°</option><option value="05">Administrativo 5°</option>
                                        @elseif($tipoPerLimitar == 2)
                                            <option value="00">Todos</option><option value="01">Operativo 4°</option><option value="03">Operativo 5°</option>
                                        @endif
                                    </select>
                                </div>

                                <div class="flex items-center gap-4 border-l-2 border-gray-300 pl-5">
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="filtroEstadoE3" value="null" checked class="w-4 h-4 text-primary focus:ring-primary">
                                        <span class="text-sm font-medium text-gray-700">Todos</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="filtroEstadoE3" value="0" class="w-4 h-4 text-green-600 focus:ring-green-500">
                                        <span class="text-sm font-medium text-gray-700">Generados</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="filtroEstadoE3" value="1" class="w-4 h-4 text-yellow-600 focus:ring-yellow-500">
                                        <span class="text-sm font-medium text-gray-700">Pendientes</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button type="button" id="btnReporteAvanceE3" class="hidden btn border-gray-300 text-gray-700 hover:bg-gray-50 px-3 py-1.5 text-sm rounded-lg items-center gap-1.5 font-medium transition-colors">
                                    <i class='bx bx-bar-chart-alt-2 text-base'></i> Rep. Avances
                                </button>
                                <button type="button" id="btnDJUnificadoE3" class="flex items-center gap-1.5 px-4 py-1.5 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class='bx bx-file text-lg'></i> DJ Masivo
                                </button>
                                <button type="button" id="btnGenerarSeleccionadosE3" disabled class="flex items-center gap-1.5 px-4 py-1.5 text-sm font-medium bg-indigo-400 text-white rounded-lg cursor-not-allowed opacity-50 transition-colors">
                                    <i class='bx bx-check-square text-lg'></i> Generar Seleccionados
                                </button>
                                <button type="button" id="btnQuitarMarcaE3" disabled class="flex items-center gap-1.5 px-4 py-1.5 text-sm font-medium bg-orange-400 text-white rounded-lg cursor-not-allowed opacity-50 transition-colors">
                                    <i class='bx bx-x-circle text-lg'></i> Quitar marca
                                </button>
                                <button type="button" id="btnResetearDJsE3" class="hidden flex items-center gap-1.5 px-4 py-1.5 text-sm font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                    <i class='bx bx-reset text-lg'></i> Resetear marcas
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 mb-4 mt-6">
                            <div class="flex-1 border-t border-gray-200"></div>
                            <span class="text-xs text-gray-400 font-medium uppercase tracking-wider px-2"><i class='bx bx-table mr-1'></i>Lista Control de Impresiones</span>
                            <div class="flex-1 border-t border-gray-200"></div>
                        </div>

                        <div id="tblPersonasEtapa3" class="w-full"></div>

                        <div class="flex items-center gap-2 mt-3">
                            <label for="page-size-etapa3" class="text-sm text-gray-600">Mostrar</label>
                            <select id="page-size-etapa3" class="w-20 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
                                <option value="5">5</option><option value="10" selected>10</option><option value="20">20</option><option value="50">50</option><option value="100">100</option>
                            </select>
                            <span class="text-sm text-gray-600">registros</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- ============================================================ --}}
        {{-- NUEVA 4° ETAPA: CARGA DE DJ                                  --}}
        {{-- ============================================================ --}}
        <div id="etapa_carga" class="tab-content {{ $esRrhhMigracion ? 'active' : 'hidden' }}">
            <div class="grid lg:grid-cols-1 gap-6">
                <div class="card overflow-hidden border border-gray-100 shadow-sm">
                    <div class="w-full px-5 py-4">
                        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
                            <div>
                                <h4 class="text-lg font-bold text-primary uppercase">Carga de DJ</h4>
                                <p class="text-sm text-gray-500">Listado de Personal y Subida de PDFs</p>
                            </div>

                            <div class="flex gap-2">
                                <div class="bg-blue-50 px-3 py-2 rounded-lg border border-blue-200 text-center min-w-[90px]">
                                    <span class="block text-[9px] text-blue-600 font-bold uppercase">Total</span>
                                    <span id="countTotalE4C" class="text-lg font-bold text-blue-700">0</span>
                                </div>
                                <div class="bg-green-50 px-3 py-2 rounded-lg border border-green-200 text-center min-w-[90px]">
                                    <span class="block text-[9px] text-green-600 font-bold uppercase">Escaneados</span>
                                    <span id="countEscaneadosE4C" class="text-lg font-bold text-green-700">0</span>
                                </div>
                                <div class="bg-yellow-50 px-3 py-2 rounded-lg border border-yellow-200 text-center min-w-[90px]">
                                    <span class="block text-[9px] text-yellow-600 font-bold uppercase">Pendientes</span>
                                    <span id="countPendientesE4C" class="text-lg font-bold text-yellow-700">0</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-4 mb-6 bg-slate-50 p-4 rounded-lg border border-slate-200">
                            <div class="flex flex-wrap items-center gap-4 w-full">
                                <input type="text" id="buscarPersonal_E4C" placeholder="Buscar por nombre o DNI..." autocomplete="off"
                                    class="w-48 px-4 py-1.5 text-sm uppercase border border-gray-300 rounded-full focus:outline-none focus:border-primary transition-colors" style="min-width: 220px;" />

                                <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                                    <label class="text-sm font-medium text-gray-700">Sucursal:</label>
                                    <select id="sucursal_E4C" class="form-select text-sm w-36 px-3 py-1.5 border border-gray-300 rounded-lg">
                                        <option disabled selected>— Seleccionar —</option>
                                        @foreach ($sucursales as $suc)
                                            <option value="{{ $suc->codigo }}">{{ $suc->abreviatura }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                                    <label class="text-sm font-medium text-gray-700">Tipo:</label>
                                    <select id="tipo_per_E4C" class="form-select text-sm w-44 px-3 py-1.5 border border-gray-300 rounded-lg">
                                        @if ($tipoPerLimitar == 0)
                                            <option value="TODOS" selected>Todos</option><option value="ADMIN_4">Administrativo 4°</option><option value="ADMIN_5">Administrativo 5°</option><option value="OPER_4">Operativo 4°</option><option value="OPER_5">Operativo 5°</option><option value="ESPECIAL">Especiales</option>
                                        @elseif ($tipoPerLimitar == 1)
                                            <option value="TODOS" selected>Todos</option><option value="ADMIN_4">Administrativo 4°</option><option value="ADMIN_5">Administrativo 5°</option>
                                        @elseif ($tipoPerLimitar == 2)
                                            <option value="TODOS" selected>Todos</option><option value="OPER_4">Operativo 4°</option><option value="OPER_5">Operativo 5°</option>
                                        @endif
                                    </select>
                                </div>

                                <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                                    <label class="text-sm font-medium text-gray-700">DJ:</label>
                                    <select id="filtroDJ_E4C" class="form-select text-sm px-3 py-1.5 border border-gray-300 rounded-lg">
                                        <option value="TODOS">Todos</option>
                                        <option value="SI">Subida</option>
                                        <option value="NO">Pendiente</option>
                                    </select>
                                </div>

                                <div class="flex items-center gap-4 border-l-2 border-gray-200 pl-4">
                                    <span class="text-sm font-medium text-gray-700">Vigencia:</span>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="vigencia_E4C" value="" class="w-4 h-4 text-primary focus:ring-primary">
                                        <span class="text-sm text-gray-700">Todos</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="vigencia_E4C" value="SI" checked class="w-4 h-4 text-primary focus:ring-primary">
                                        <span class="text-sm text-gray-700">Sí</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="vigencia_E4C" value="NO" class="w-4 h-4 text-primary focus:ring-primary">
                                        <span class="text-sm text-gray-700">No</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="w-full">
                            <div id="tblPersonas_E4C" class="w-full"></div>
                            <div class="flex flex-wrap items-center justify-between gap-3 mt-4">
                                <div class="flex items-center gap-2">
                                    <label for="page-size-personas_E4C" class="text-sm font-medium text-gray-700">Mostrar</label>
                                    <select id="page-size-personas_E4C" class="form-select text-sm w-20 px-3 py-1 border border-gray-300 rounded-lg">
                                        <option value="5">5</option><option value="10" selected>10</option><option value="20">20</option><option value="50">50</option>
                                    </select>
                                    <span class="text-sm text-gray-600">registros</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL DE CARGA DE DJ (Asociado a Etapa 4) --}}
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
                        <button type="button" class="btn border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 px-4 py-2 rounded-lg text-sm font-medium" data-hs-overlay="#modal-carga-dj_E4C">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="etapa4" class="tab-content hidden">
            <div class="grid lg:grid-cols-1 gap-6">
                <div class="card overflow-hidden border border-gray-100 shadow-sm">
                    <div class="w-full px-5 py-4">

                        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
                            <div>
                                <h4 class="text-lg font-bold text-primary uppercase">Escaneo de DJ (Biométrico)</h4>
                                <p class="text-sm text-gray-500">Validación de Huella y Firma</p>
                            </div>

                            {{-- NUEVOS INDICADORES ETAPA 4 --}}
                            <div class="flex gap-2">
                                <div class="bg-blue-50 px-3 py-2 rounded-lg border border-blue-200 text-center min-w-[90px]">
                                    <span class="block text-[9px] text-blue-600 font-bold uppercase">Total</span>
                                    <span id="contadorTotalE4" class="text-lg font-bold text-blue-700">0</span>
                                </div>
                                <div class="bg-green-50 px-3 py-2 rounded-lg border border-green-200 text-center min-w-[90px]">
                                    <span class="block text-[9px] text-green-600 font-bold uppercase">Escaneados</span>
                                    <span id="contadorEscaneadosE4" class="text-lg font-bold text-green-700">0</span>
                                </div>
                                <div class="bg-yellow-50 px-3 py-2 rounded-lg border border-yellow-200 text-center min-w-[90px]">
                                    <span class="block text-[9px] text-yellow-600 font-bold uppercase">Pendientes</span>
                                    <span id="contadorPendientesE4" class="text-lg font-bold text-yellow-700">0</span>
                                </div>
                            </div>
                        </div>

                        {{-- CAJA GRIS DE FILTROS ESTANDARIZADA --}}
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-6 bg-slate-50 p-4 rounded-lg border border-slate-200">
                            <div class="flex flex-wrap items-center gap-5">
                                
                                {{-- BUSCADOR --}}
                                <div class="flex items-center gap-2">
                                    <input type="text" id="buscarPersonalE4" placeholder="Buscar por nombre o DNI..." class="w-48 px-4 py-1.5 border border-gray-300 rounded-full focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-sm uppercase" style="min-width: 250px;" autocomplete="off" />
                                </div>

                                {{-- SELECT SUCURSAL --}}
                                <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                                    <label class="text-sm font-medium text-gray-700">Sucursal:</label>
                                    @php $sucursalesFiltradasE4 = array_slice($sucursales, 1); @endphp
                                    <select id="filtroSucursalE4" class="form-select text-sm w-36 px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-primary">
                                        @if(count($sucursalesFiltradasE4) > 1) <option value="00">Todas</option> @endif
                                        @foreach ($sucursalesFiltradasE4 as $sucursal) <option value="{{ $sucursal->codigo }}">{{ $sucursal->abreviatura }}</option> @endforeach
                                    </select>
                                </div>

                                {{-- SELECT TIPO --}}
                                <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                                    <label class="text-sm font-medium text-gray-700">Tipo:</label>
                                    <select id="filtroTipoPerE4" class="form-select text-sm w-44 px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-primary">
                                        @if($tipoPerLimitar == 0)
                                            <option value="00">Todos</option><option value="01">Operativo 4°</option><option value="03">Operativo 5°</option><option value="02">Administrativo 4°</option><option value="05">Administrativo 5°</option>
                                        @elseif($tipoPerLimitar == 1)
                                            <option value="00">Todos</option><option value="02">Administrativo 4°</option><option value="05">Administrativo 5°</option>
                                        @elseif($tipoPerLimitar == 2)
                                            <option value="00">Todos</option><option value="01">Operativo 4°</option><option value="03">Operativo 5°</option>
                                        @endif
                                    </select>
                                </div>

                                {{-- NUEVO: RADIO BUTTONS ESTADO --}}
                                <div class="flex items-center gap-4 border-l-2 border-gray-300 pl-5">
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="filtroEstadoE4" value="null" checked class="w-4 h-4 text-primary focus:ring-primary">
                                        <span class="text-sm font-medium text-gray-700">Todos</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="filtroEstadoE4" value="0" class="w-4 h-4 text-green-600 focus:ring-green-500">
                                        <span class="text-sm font-medium text-gray-700">Escaneados</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="filtroEstadoE4" value="1" class="w-4 h-4 text-yellow-600 focus:ring-yellow-500">
                                        <span class="text-sm font-medium text-gray-700">Pendientes</span>
                                    </label>
                                </div>

                            </div>

                            {{-- BOTÓN REPORTE AVANCES --}}
                            <div class="flex gap-2">
                                <button type="button" class="flex items-center gap-1.5 px-4 py-1.5 text-sm font-medium border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 transition-colors shadow-sm" data-hs-overlay="#modal-reporte-avances">
                                    <i class='bx bx-bar-chart-alt-2 text-lg'></i> Reporte Avances
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 mb-4 mt-6">
                            <div class="flex-1 border-t border-gray-200"></div>
                            <span class="text-xs text-gray-400 font-medium uppercase tracking-wider px-2"><i class='bx bx-table mr-1'></i>Lista Control Biométrico</span>
                            <div class="flex-1 border-t border-gray-200"></div>
                        </div>

                        <div id="tblPersonasEtapa4" class="w-full"></div>
                </div>
            </div>
        </div>

        <div id="etapa5" class="tab-content hidden">
            <div class="grid lg:grid-cols-1 gap-6">
                <div class="card overflow-hidden">
                    <div class="card-header border-b border-gray-200 pb-2">
                        <h4 class="card-title text-lg font-medium text-primary">Migración (SIP)</h4>
                    </div>

                    <div class="w-full px-5 py-4">
                        {{-- FILTROS + BOTONES --}}
                        <div class="flex justify-between items-start gap-4 mb-4">
                            <div class="flex flex-col items-start gap-2">
                                <div>
                                    <div class="flex items-baseline gap-1 px-3 py-1 bg-gray-100 rounded-lg border border-gray-200">
                                        <span id="contadorFiltrado" class="text-base font-medium text-gray-800">0</span>
                                        <span class="text-sm text-gray-400">/</span>
                                        <span id="contadorTotal" class="text-sm text-gray-500">0</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" id="btnReporteFaltantes" class="flex items-center gap-1.5 px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-white text-gray-700 hover:bg-gray-50 transition-colors"><i class='bx bx-download text-base'></i> Rep. faltantes</button>
                                    <button type="button" id="btnReporteActualizacion" class="flex items-center gap-1.5 px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-white text-gray-700 hover:bg-gray-50 transition-colors"><i class='bx bx-download text-base'></i> Rep. Actualización</button>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" id="btnDJUnificadoMigrado" class="btn border-primary text-primary hover:bg-primary hover:text-white px-3 py-1 text-sm rounded-lg"><i class='bx bx-file text-base'></i> DJ Unificado (Migrados)</button>
                                    <button type="button" id="btnResetearDJs" class="btn bg-danger text-white px-3 py-1 text-sm rounded-lg"><i class='bx bx-reset text-base'></i> Resetear marcas</button>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-3">
                                <div class="flex items-center gap-2">
                                    <input type="text" id="buscarPersonal" placeholder="Buscar por nombre o DNI..." class="w-48 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm uppercase" style="min-width: 250px;" autocomplete="off" />
                                </div>
                                <div class="flex items-center gap-3 flex-wrap">
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm text-gray-600">Sucursal:</label>
                                        @php $sucursalesFiltradasLegacy = array_slice($sucursales, 1); @endphp
                                        <select id="filtroSucursal" class="w-24 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
                                            @if(count($sucursalesFiltradasLegacy) > 1) <option value="">Todas</option> @endif
                                            @foreach ($sucursalesFiltradasLegacy as $sucursal) <option value="{{ $sucursal->codigo }}">{{ $sucursal->abreviatura }}</option> @endforeach
                                        </select>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm text-gray-600">Tipo:</label>
                                        <select id="filtroTipoPer" class="w-44 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
                                            @if($tipoPerLimitar == 0)
                                                <option value="">Todos</option><option value="OPERATIVO 4°">Operativo 4°</option><option value="OPERATIVO 5°">Operativo 5°</option><option value="ADMINISTRATIVO 4°">Administrativo 4°</option><option value="ADMINISTRATIVO 5°">Administrativo 5°</option><option value="ESPECIAL">Especial</option>
                                            @elseif($tipoPerLimitar == 1)
                                                <option value="">Todos</option><option value="ADMINISTRATIVO 4°">Administrativo 4°</option><option value="ADMINISTRATIVO 5°">Administrativo 5°</option>
                                            @elseif($tipoPerLimitar == 2)
                                                <option value="">Todos</option><option value="OPERATIVO 4°">Operativo 4°</option><option value="OPERATIVO 5°">Operativo 5°</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 mb-4 mt-6">
                            <div class="flex-1 border-t border-gray-200"></div>
                            <span class="text-xs text-gray-400 font-medium uppercase tracking-wider px-2"><i class='bx bx-table mr-1'></i>Resultados</span>
                            <div class="flex-1 border-t border-gray-200"></div>
                        </div>

                        <div id="tblPersonasMigrado" class="w-full"></div>

                        <div class="flex items-center gap-2 mt-3">
                            <label for="page-size-migrado" class="text-sm text-gray-600">Mostrar</label>
                            <select id="page-size-migrado" class="w-20 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-white">
                                <option value="5">5</option><option value="10" selected>10</option><option value="20">20</option><option value="50">50</option><option value="100">100</option>
                            </select>
                            <span class="text-sm text-gray-600">registros</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div> @include('file_control.rrhh.partials_modal_dj')

    <button id="btn-modal-biometrico" data-hs-overlay="#modal-biometrico" class="hidden"></button>
    @include('file_control.rrhh.partials_modal_comparacion_huellafirma_dni')

{{-- MODAL: REPORTE DE AVANCES (ETAPA 4) --}}
    <div id="modal-reporte-avances" class="hs-overlay hidden fixed inset-0 z-[80] overflow-y-auto pointer-events-none transition-all duration-500">
        <div class="hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 translate-y-10 opacity-0 ease-in-out transition-all duration-500 sm:max-w-lg sm:w-full m-3 sm:mx-auto">
            <div class="flex flex-col bg-white border border-gray-200 shadow-sm rounded-xl pointer-events-auto">
                <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
                    <h3 class="font-bold text-gray-800">Reporte de Avances</h3>
                    <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#modal-reporte-avances">
                        <span class="sr-only">Cerrar</span>
                        <i class='bx bx-x text-xl'></i>
                    </button>
                </div>
                <div class="p-5 overflow-y-auto">
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sucursal</label>
                        @php $sucursalesModal = array_slice($sucursales, 1); @endphp
                        <select id="modalSucursalE4" class="py-2.5 px-4 block w-full border border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none">
                            <option value="00">TODOS</option>
                            @foreach ($sucursalesModal as $sucursal)
                                <option value="{{ $sucursal->codigo }}">{{ $sucursal->abreviatura }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Tipo de Personal</label>
                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="radio" name="modalTipoPerE4" value="00" checked class="form-radio text-blue-500 focus:ring-blue-500 border-gray-300">
                                <span class="font-medium">Todos</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="radio" name="modalTipoPerE4" value="OPER" class="form-radio text-blue-500 focus:ring-blue-500 border-gray-300">
                                <span>Operativo</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="radio" name="modalTipoPerE4" value="ADMIN" class="form-radio text-blue-500 focus:ring-blue-500 border-gray-300">
                                <span>Administrativo</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200">
                    <button type="button" id="btnModalPdfE4" class="py-2 px-4 inline-flex justify-center items-center gap-1.5 rounded-lg border border-transparent font-medium bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all text-sm">
                        <i class='bx bxs-file-pdf text-lg'></i> PDF
                    </button>
                    <button type="button" id="btnModalExcelE4" class="py-2 px-4 inline-flex justify-center items-center gap-1.5 rounded-lg border border-transparent font-medium bg-green-600 text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all text-sm">
                        <i class='bx bx-spreadsheet text-lg'></i> Excel
                    </button>
                    <button type="button" class="py-2 px-4 inline-flex justify-center items-center gap-1.5 rounded-lg border border-gray-200 font-medium bg-white text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all text-sm" data-hs-overlay="#modal-reporte-avances">
                        <i class='bx bx-x text-lg'></i> Cerrar
                    </button>
                </div>
            </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
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