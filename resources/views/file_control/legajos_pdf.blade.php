@extends('layouts.vertical', ['title' => 'Legajos PDF'])

@section('css')

@endsection

@section('content')

    @include("layouts.shared/page-title", ["subtitle" => "File Control", "title" => "Legajos PDF"])

    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="grid 2xl:grid-cols-4 grid-cols-1 gap-6 pt-8">
        <!-- <div class="grid lg:grid-cols-1 gap-6 mt-8"> -->
        <div class="card custom-card">
            <div class="p-4 md:p-5">
                <h3 class="text-lg font-bold text-default-800">
                    COMPLETO
                </h3>
                <p class="mt-2 text-default-500">
                    Legajos completo por cliente - cargo
                </p>
                <a id="legajo2"
                    class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary hover:text-primary-800 disabled:opacity-50 disabled:pointer-events-none"
                    href="#">
                    Generar
                    <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
                </a>
            </div>
        </div>

        <div class="card custom-card">
            <div class="p-4 md:p-5">
                <h3 class="text-lg font-bold text-default-800">
                    ESPECIAL
                </h3>
                <p class="mt-2 text-default-500">
                    Legajos de folios con libre elección.
                </p>
                <a id="legajo1"
                    class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary hover:text-primary-800 disabled:opacity-50 disabled:pointer-events-none"
                    href="#">
                    Generar
                    <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
                </a>
            </div>
        </div>

        {{-- <div class="card custom-card">
            <div class="p-4 md:p-5">
                <h3 class="text-lg font-bold text-default-800">
                    LEGAJO ESPECIAL 3
                </h3>
                <p class="mt-2 text-default-500">
                    Genera legajos por documentos específicos de todo o algunas personas.
                </p>
                <a id="legajo3"
                    class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary hover:text-primary-800 disabled:opacity-50 disabled:pointer-events-none"
                    href="#">
                    Generar
                    <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
                </a>
            </div>
        </div>

        <div class="card custom-card">
            <div class="p-4 md:p-5">
                <h3 class="text-lg font-bold text-default-800">
                    LEGAJO ESPECIAL 5
                </h3>
                <p class="mt-2 text-default-500">
                    Genera legajos por documentos específicos de todo o algunas personas.
                </p>
                <a id="legajo4"
                    class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary hover:text-primary-800 disabled:opacity-50 disabled:pointer-events-none"
                    href="#">
                    Generar
                    <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
                </a>
            </div>
        </div> --}}
    </div>

    <div class="grid lg:grid-cols-5 gap-6 mt-8 pb-10">
        <div class="card overflow-hidden hidden lg:col-span-3" id="personasDiv">
            <div class="card-header flex justify-between items-center">
                <h4 class="card-title">Listado de PERSONAS</h4>
                <button id="btnVerSeleccionados"
                    class="hidden text-xs bg-primary text-white px-3 py-1 rounded-full font-semibold hover:bg-primary/80">
                    <span id="cntSeleccionados">0</span> seleccionado(s)
                </button>
            </div>

            <div class="w-full px-5 py-2 mt-2">
                <input type="text" id="buscarPer" placeholder="Buscar por nombre, documento o código..."
                    class="w-full px-3 py-1.5 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm"
                    autocomplete="off" />
            </div>

            <div class="w-full px-5 pb-3">
                <div class="flex flex-wrap items-center gap-5 bg-slate-50 p-4 rounded-lg border border-slate-200">

                    {{-- Sucursal --}}
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700">Sucursal:</label>
                        <select id="sucursal" class="form-select text-sm px-3 py-1.5 border border-gray-300 rounded-lg bg-white">
                            <option value="">Todas</option>
                            @foreach(array_slice($sucursales, 1) as $sucursal)
                                <option value="{{ $sucursal->abreviatura }}">{{ $sucursal->abreviatura }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tipo --}}
                    <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                        <label class="text-sm font-medium text-gray-700">Tipo:</label>
                        <select id="tipoPerFiltro" class="form-select text-sm w-44 px-3 py-1.5 border border-gray-300 rounded-lg bg-white">
                            <option value="TODOS" selected>Todos</option>
                            <option value="OPERATIVO 4°">Operativo 4°</option>
                            <option value="OPERATIVO 5°">Operativo 5°</option>
                            <option value="ADMINISTRATIVO 4°">Administrativo 4°</option>
                            <option value="ADMINISTRATIVO 5°">Administrativo 5°</option>
                            <option value="ESPECIALES" hidden>Especiales</option>
                        </select>
                    </div>

                    {{-- Vigencia --}}
                    <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                        <label class="text-sm font-medium text-gray-700">Vigencia:</label>
                        <select id="filtroVigenciaLeg" class="form-select text-sm px-3 py-1.5 border border-gray-300 rounded-lg bg-white">
                            <option value="00">TODOS</option>
                            <option value="SI" selected>SI</option>
                            <option value="NO">NO</option>
                        </select>
                    </div>

                </div>
            </div>

            <div class="w-full px-5 py-2 mt-3">
                <div class="flex justify-end items-center space-x-2">
                    <label for="select-all">TODOS</label>
                    <input type="checkbox" id="select-all-per" class="form-checkbox rounded text-dark">
                </div>
                <div id="tblPersonas" class="w-full mt-5"></div>
            </div>
        </div>

        <div class="card overflow-hidden hidden lg:col-span-2" id="foliosDiv">
            <div class="card-header">
                <h4 class="card-title">Listado de FOLIOS</h4>
            </div>

            <div class="w-full px-5 py-3 mt-2">
                <div class="flex flex-wrap items-center justify-between gap-3 bg-slate-50 p-4 rounded-lg border border-slate-200">
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Buscar:</label>
                        <input type="text" id="buscarFol" placeholder="Buscar folio..."
                            class="px-3 py-1.5 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition-all text-sm bg-white"
                            autocomplete="off" style="min-width: 160px;" />
                    </div>
                    <div class="flex items-center gap-2">
                        <label for="select-all-fol" class="text-sm font-medium text-gray-700">Todos</label>
                        <input type="checkbox" id="select-all-fol" class="form-checkbox rounded text-dark">
                    </div>
                </div>
            </div>

            <div class="w-full px-5 py-2 mt-1">
                <div id="tblFolios" class="w-full mt-5"></div>
            </div>
        </div>

        <div class="card overflow-hidden hidden lg:col-span-2" id="legajosDiv" style="height: 100%">
            <div class="card-header">
                <h4 class="card-title">Selección de LEGAJOS</h4>
            </div>

            <div class="w-full px-5 py-3 mt-2">
                <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="clientes" class="text-sm font-medium text-gray-700 block mb-1.5">Cliente</label>
                            <select id="clientes" class="tom-select w-full">
                                <option disabled selected>-Seleccionar-</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->codigo }}">{{ $cliente->abreviatura }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="divCargos" class="hidden">
                            <label for="cargos" class="text-sm font-medium text-gray-700 block mb-1.5">Cargo</label>
                            <select id="cargos" class="tom-select w-full">
                                <option value="">Seleccionar...</option>
                                @foreach($cargos as $cargo)
                                    <option value="{{ $cargo->codigo }}">{{ $cargo->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full px-5 py-2 mt-3">
                <div id="legajosSelectAllDiv" class="hidden flex justify-end items-center space-x-2 mb-1">
                    <label for="select-all-leg" class="text-sm">TODOS</label>
                    <input type="checkbox" id="select-all-leg" class="form-checkbox rounded text-dark" checked>
                </div>
                <div id="tblLegajos" class="w-full mt-2 hidden"></div>
            </div>
        </div>
    </div>

    <div class="fixed bottom-0 left-0 right-0 bg-gray-800 py-4 flex justify-center items-center gap-4">
        {{-- Checkbox Sin carátula --}}
        <div id="chkSinCaratulaDiv" class="hidden flex items-center gap-2 border border-gray-600 rounded-lg px-4 py-1.5">
            <label class="text-white text-sm flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" id="chkSinCaratula" class="form-checkbox rounded text-cyan-400 h-3.5 w-3.5">
                <span>Sin carátula</span>
            </label>
        </div>

        <div id="modoGenerarDiv" class="hidden flex items-center gap-3 border border-gray-600 rounded-lg px-4 py-1.5">
            <span class="text-gray-300 text-sm font-medium">Modo:</span>
            <label class="text-white text-sm flex items-center gap-1.5 cursor-pointer">
                <input type="radio" name="modoGenerar" value="unico" checked
                    class="form-radio text-cyan-400 h-3.5 w-3.5">
                Un solo PDF
            </label>
            <label class="text-sm flex items-center gap-1.5 cursor-pointer" id="labelSeparado" style="opacity:0.4">
                <input type="radio" name="modoGenerar" value="separado" id="radioSeparado" disabled class="form-radio text-cyan-400 h-3.5 w-3.5">
                <span class="text-white">Separados</span>
            </label>
        </div>
        <button id="btnLeg1"
            class="hidden bg-cyan-500 text-white px-6 py-2 rounded-lg shadow-lg hover:bg-cyan-600 focus:outline-none">
            Generar LEGAJO
        </button>
        <button id="btnLeg2"
            class="hidden bg-cyan-500 text-white px-6 py-2 rounded-lg shadow-lg hover:bg-cyan-600 focus:outline-none">
            Generar LEGAJO
        </button>
        <button id="btnLeg3"
            class="hidden bg-cyan-500 text-white px-6 py-2 rounded-lg shadow-lg hover:bg-cyan-600 focus:outline-none">
            Generar LEGAJO
        </button>
    </div>

    <!-- Modal: Personal seleccionado -->
    <div id="modalSeleccionados" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 flex flex-col max-h-[80vh]">
            <div class="flex items-center justify-between p-5 border-b border-default-200 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center">
                        <i class='bx bx-group text-primary text-xl'></i>
                    </div>
                    <div>
                        <h5 class="text-base font-semibold text-default-800">Personal seleccionado</h5>
                        <p class="text-xs text-default-400"><span id="cntModalSel">0</span> persona(s)</p>
                    </div>
                </div>
                <button id="btnCerrarModalSel" class="text-default-400 hover:text-default-600">
                    <i class='bx bx-x text-2xl'></i>
                </button>
            </div>
            <div class="px-5 pt-4 pb-2 flex-shrink-0">
                <div class="relative">
             
                    <input type="text" id="buscarModalSel" placeholder="Buscar por nombre o código..."
                        class="w-full pl-9 pr-3 pl-4 py-2 border border-default-200 rounded-lg text-sm focus:outline-none focus:border-primary transition-colors">
                </div>
            </div>
            <ul id="listaModalSeleccionados" class="divide-y divide-default-100 text-sm overflow-y-auto flex-1 px-5 pb-4">
            </ul>
        </div>
    </div>

@endsection
@vite(['resources/js/functions/legajos_pdf.js'])
@section('script')
@endsection