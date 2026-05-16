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

            <div class="w-full px-5 py-2 mt-3 flex justify-between items-center">
                <input type="text" id="buscarPer" placeholder="Buscar..."
                    class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm"
                    autocomplete="off" />
                <div class="flex items-center space-x-2 w-80 justify-end">
                    <label for="sucursales" class="text-default-800 text-sm font-medium">Sucursal</label>
                    <select id="sucursal" class="form-select max-w-xs">
                        <option disabled selected>-Seleccionar-</option>
                        @foreach($sucursales as $sucursal)
                            <option value="{{ $sucursal->abreviatura }}">{{ $sucursal->abreviatura }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="w-full px-5 py-1 flex items-center gap-4">
                <span class="text-default-800 text-sm font-medium">Tipo:</span>
                <div class="form-check">
                    <input type="radio" class="form-radio text-primary" name="tipoPerFiltro" id="radioPerTodos"
                        value="TODOS" checked>
                    <label class="ms-1.5 text-sm" for="radioPerTodos">Todos</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-radio text-primary" name="tipoPerFiltro" id="radioPerOper" value="OPER">
                    <label class="ms-1.5 text-sm" for="radioPerOper">Operativo</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-radio text-primary" name="tipoPerFiltro" id="radioPerAdmin"
                        value="ADMIN">
                    <label class="ms-1.5 text-sm" for="radioPerAdmin">Administrativo</label>
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

            <div class="w-full px-5 py-2 mt-3 flex justify-between items-center">
                <input type="text" id="buscarFol" placeholder="Buscar..."
                    class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm"
                    autocomplete="off" />

            </div>

            <div class="w-full px-5 py-2 mt-3">
                <div class="flex justify-end items-center space-x-2">
                    <label for="select-all">TODOS</label>
                    <input type="checkbox" id="select-all-fol" class="form-checkbox rounded text-dark">
                </div>
                <div id="tblFolios" class="w-full mt-5"></div>
            </div>
        </div>

        <div class="card overflow-hidden hidden lg:col-span-2" id="legajosDiv" style="height: 100%">
            <div class="card-header">
                <h4 class="card-title">Selección de LEGAJOS</h4>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-3">
                    <div>
                        <label for="clientes" class="text-default-800 text-sm font-medium inline-block mb-2">Cliente</label>
                        <select id="clientes" class="tom-select w-full">
                            <option disabled selected>-Seleccionar-</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->codigo }}">{{ $cliente->abreviatura }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="divCargos" class="hidden">
                        <label for="cargos" class="text-default-800 text-sm font-medium inline-block mb-2">Cargo</label>
                        <select id="cargos" class="tom-select w-full">
                            <option value="">Seleccionar...</option>
                            @foreach($cargos as $cargo)
                                <option value="{{ $cargo->codigo }}">{{ $cargo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="w-full px-5 py-2 mt-3">
                <div id="tblLegajos" class="w-full mt-5 hidden"></div>
            </div>
        </div>
    </div>

    <div class="fixed bottom-0 left-0 right-0 bg-gray-800 py-4 flex justify-center items-center gap-4">
        <div id="modoGenerarDiv" class="hidden flex items-center gap-3 border border-gray-600 rounded-lg px-4 py-1.5">
            <span class="text-gray-300 text-sm font-medium">Modo:</span>
            <label class="text-white text-sm flex items-center gap-1.5 cursor-pointer">
                <input type="radio" name="modoGenerar" value="separado" checked
                    class="form-radio text-cyan-400 h-3.5 w-3.5">
                Separados
            </label>
            <label class="text-white text-sm flex items-center gap-1.5 cursor-pointer">
                <input type="radio" name="modoGenerar" value="unico" class="form-radio text-cyan-400 h-3.5 w-3.5">
                Un solo PDF
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