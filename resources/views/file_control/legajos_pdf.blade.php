@extends('layouts.vertical', ['title' => 'Legajos PDF'])

@section('css')

@endsection

@section('content')

@include("layouts.shared/page-title", ["subtitle" => "Recursos Humanos", "title" => "Legajos PDF"])

<link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="grid 2xl:grid-cols-4 grid-cols-1 gap-6">
<!-- <div class="grid lg:grid-cols-1 gap-6 mt-8"> -->
    <div class="card custom-card">
        <div class="p-4 md:p-5">
            <h3 class="text-lg font-bold text-default-800">
                COMPLETO
            </h3>
            <p class="mt-2 text-default-500">
                Genera el legajo por cliente/cargo de una persona que está como DESTACADO.
            </p>
            <a id="legajo2" class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary hover:text-primary-800 disabled:opacity-50 disabled:pointer-events-none" href="#">
                Generar
                <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
            </a>
        </div>
    </div>

    <div class="card custom-card" >
        <div class="p-4 md:p-5">
            <h3 class="text-lg font-bold text-default-800">
                ESPECIAL
            </h3>
            <p class="mt-2 text-default-500">
                Genera legajos por documentos específicos de todo o algunas personas.
            </p>
            <a id="legajo1" class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary hover:text-primary-800 disabled:opacity-50 disabled:pointer-events-none" href="#">
                Generar
                <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
            </a>
        </div>
    </div>

    <div class="card custom-card">
        <div class="p-4 md:p-5">
            <h3 class="text-lg font-bold text-default-800">
                LEGAJO ESPECIAL 3
            </h3>
            <p class="mt-2 text-default-500">
                Genera legajos por documentos específicos de todo o algunas personas.
            </p>
            <a id="legajo3" class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary hover:text-primary-800 disabled:opacity-50 disabled:pointer-events-none" href="#">
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
            <a id="legajo4" class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary hover:text-primary-800 disabled:opacity-50 disabled:pointer-events-none" href="#">
                Generar
                <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
            </a>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6 mt-8">
    <div class="card overflow-hidden hidden" id="personasDiv">
        <div class="card-header">
            <h4 class="card-title">Listado de PERSONAS</h4>
        </div>
      
        <div class="w-full px-5 py-2 mt-3 flex justify-between items-center">
            <input type="text" id="buscarPer" placeholder="Buscar..." class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm" autocomplete="off"/>
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

        <div class="w-full px-5 py-2 mt-3">
            <div class="flex justify-end items-center space-x-2">
                <label for="select-all">TODOS</label>
                <input type="checkbox" id="select-all-per" class="form-checkbox rounded text-dark">
            </div>
            <div id="tblPersonas" class="w-full mt-5"></div>
        </div>
    </div>

    <div class="card overflow-hidden hidden" id="foliosDiv">
        <div class="card-header">
            <h4 class="card-title">Listado de FOLIOS</h4>
        </div>
      
        <div class="w-full px-5 py-2 mt-3 flex justify-between items-center">
            <input type="text" id="buscarFol" placeholder="Buscar..." class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm" autocomplete="off"/>
           
        </div>

        <div class="w-full px-5 py-2 mt-3">
            <div class="flex justify-end items-center space-x-2">
                <label for="select-all">TODOS</label>
                <input type="checkbox" id="select-all-fol" class="form-checkbox rounded text-dark">
            </div>
            <div id="tblFolios" class="w-full mt-5"></div>
        </div>
    </div>

    <div class="card overflow-hidden hidden" id="legajosDiv" style="height: 100%">
        <div class="card-header">
            <h4 class="card-title">Selección de LEGAJOS</h4>
        </div>
      
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-3">
                <div>
                    <label for="inputState"
                        class="text-default-800 text-sm font-medium inline-block mb-2">Cliente</label>
                    <select id="clientes" class="form-select">
                        <option disabled selected>-Seleccionar-</option>
                        @foreach($clientes as $cliente)
                        <option value="{{ $cliente->codigo }}">{{ $cliente->abreviatura }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="divCargos" class="hidden">
                    <label for="inputZip" class="text-default-800 text-sm font-medium inline-block mb-2">Cargo</label>
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


<div class="fixed bottom-0 left-0 right-0 bg-gray-800 py-4 flex justify-center">
    <button id="btnLeg1" class=" bg-cyan-500 text-white px-6 py-2 rounded-lg shadow-lg hover:bg-cyan-600 focus:outline-none" >
        Generar LEGAJO 1
    </button>&nbsp;
    <button id="btnLeg2" class=" bg-cyan-500 text-white px-6 py-2 rounded-lg shadow-lg hover:bg-cyan-600 focus:outline-none" >
    Generar LEGAJO 2
    </button>&nbsp;
    <button id="btnLeg3" class=" bg-cyan-500 text-white px-6 py-2 rounded-lg shadow-lg hover:bg-cyan-600 focus:outline-none" >
    Generar LEGAJO 3
    </button>
</div>



@endsection

@vite(['resources/js/functions/legajos_pdf.js'])
@section('script')

@endsection