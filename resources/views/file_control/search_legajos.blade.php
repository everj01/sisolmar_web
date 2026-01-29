@extends('layouts.vertical', ['title' => 'Gestión de Cargos'])

@section('css')

@endsection

@section('content')

@include("layouts.shared/page-title", ["subtitle" => "Recursos Humanos", "title" => "Búsqueda de Legajos"])

<style>
    .disabled-table {
    pointer-events: none; /* Bloquea clics y eventos */
    opacity: 0.3; /* Reduce la visibilidad */
}
</style>
<script src="https://kit.fontawesome.com/76256ea07c.js" crossorigin="anonymous"></script>
<div class="w-full"> 
    <div class=" flex flex-row items-center justify-center gap-2">
        <button type="button" class="btn bg-primary text-white" id="btnTodos">Mostrar Todos</button>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6 mt-8">

    <div class="card">
        <div class="card-header flex gap-1 justify-between items-center">
            <h3 class="text-center card-title">Listado de Clientes</h3>
            <input type="text" id="buscarCliente" placeholder="Buscar cliente..." class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm">
        </div>
        <div class="w-full px-5 py-2 ">
            <div id="tblCliente" class="w-full mt-5 overflow-y-auto"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header flex gap-1 justify-between items-center">
            <h3 class="text-center card-title">Listado de Cargos</h3>
            <input type="text" id="buscarCargo" placeholder="Buscar cargo..." class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm" />
        </div>
        <div class="w-full px-5 py-2">
            <div id="tblCargo" class="w-full mt-5 overflow-y-auto overflow-x-hidden"></div>
        </div>  
    </div>


</div>
<div class="grid lg:grid-cols-1 gap-6 mt-8">

    <div class="card overflow-hidden">
        <div class="card-header flex gap-6">
            <h4 class="card-title">Listado de Personal con LEGAJOS COMPLETOS</h4><span class="text-primary font-semibold text-lg" id="txtTextoilus"></span>
        </div>
        <div class="w-full px-5 py-2  mt-3">
            <input type="text" id="buscar" placeholder="Buscar..."
                class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm" />
            <div id="tblPersonas" class="w-full mt-5"></div>
        </div>
    </div>

    <div id="dataDocsLeg" class="card hidden">
        <div class="card-header">
            <h4 class="card-title nombrePersDocs">Folios de</h4>
        </div>
        <div class="w-full px-5 py-2 flex flex-col">
            <!-- <div class="flex justify-center items-center gap-4 mb-4 mt-4">
                <div class="form-check">
                    <input type="radio" class="form-radio text-primary" id="radioPrin" name="tipo_folio" value="PRINCIPAL" checked>
                    <label class="ms-1.5" for="radioPrin">Principal</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-radio text-primary" id="radioAux" name="tipo_folio" value="ADICIONAL">
                    <label class="ms-1.5" for="radioAux">Adicional</label>
                </div>
            </div> -->
            <div id="tblDocsLegajo" class="w-full flex-grow"></div>
        </div>
    </div>

    <div id="dataDocsLeg1" class="card hidden">
        <div class="card-header">
            <h4 class="card-title nombrePersLeg">Legajos para</h4>
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
                <div>
                    <label for="inputZip" class="text-default-800 text-sm font-medium inline-block mb-2">Cargo</label>
                    <select id="cargos" class="form-select">
                        <option disabled selected>-Seleccionar-</option>
                        @foreach($cargos as $cargo)
                        <option value="{{ $cargo->codigo }}">{{ $cargo->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="button" class="btn bg-primary text-white btnTraerFolios" id="btnTraerFolios">Traer folios</button>
        </div>
        <div class="w-full px-5 py-2">
            <input type="hidden" name="codPersonal" id="codPersonal">
            <div id="tblDocsLegajo1" class="w-full hidden"></div>
        </div>

    </div>
</div>

@endsection

@vite(['resources/js/functions/search_legajos.js'])
@section('script')

@endsection