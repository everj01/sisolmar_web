@extends('layouts.vertical', ['title' => 'Gestion de cargo'])

@section('css')

@endsection

@section('content')

@include("layouts.shared/page-title", ["subtitle" => "Requisitos de Legajos", "title" => "Requisitos de Legajos"])

<script src="https://kit.fontawesome.com/76256ea07c.js" crossorigin="anonymous"></script>

<style>
.disabled-table {
    pointer-events: none; /* Bloquea clics y eventos */
    opacity: 0.3; /* Reduce la visibilidad */
}
</style>

<div class="grid lg:grid-cols-2 gap-6 mt-8">

    <div class="card">
        <div class="card-header flex gap-1 justify-between items-center">
            <h3 class="text-center card-title">SELECCIONAR CLIENTE</h3>
            <input type="text" placeholder="Buscar cliente..." id="buscarCliente" class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm">
        </div>
        <div class="w-full px-5 py-2 ">
            <div id="tblCliente" class="w-full mt-5  overflow-y-auto overflow-x-hidden"></div>
        </div>
    </div>
    <!-- end card -->

    <div class="card">
        <div class="card-header flex gap-1 justify-between items-center">
            <h3 class="text-center card-title">SELECCIONAR CARGO</h3>
            <input type="text" id="buscarCargo" placeholder="Buscar cargo..." class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm" />
        </div>
        <div class="w-full px-5 py-2">
            <div id="tblCargo" class="w-full mt-5 overflow-y-auto overflow-x-hidden"></div>
        </div>
    </div> <!-- end card -->

    <div class="card col-span-2">
        <div class="card-header flex gap-1 justify-between items-center">
            <h4 class="card-title">BUSCAR FOLIOS</h4>
            <input type="text" id="buscarFolio"
            placeholder="Buscar folios..."
            class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm" />
        </div>
        <div id="dismiss-alert" class="hs-removing:translate-x-5 hs-removing:opacity-0 transition duration-300 bg-teal-50 border border-teal-200 rounded-md p-4" role="alert">
            <div class="flex items-center gap-3">
                <i class="i-tabler-circle-check text-xl"></i>
                <div class="flex-grow">
                    <div class="text-sm text-teal-800 font-medium">
                        Las notificaciones esta vigentes 24 horas.
                    </div>
                </div>
                <button data-hs-remove-element="#dismiss-alert" type="button" id="dismiss-test" class="ms-auto h-8 w-8 rounded-full bg-default-200 flex justify-center items-center">
                    <i class="i-tabler-x text-xl"></i>
                </button>
            </div>
        </div>
        <div class="w-full px-5 py-2 ">

            <div id="tblFolio" class="w-full mt-5 overflow-y-auto overflow-x-hidden"></div>
        </div>
        <input type="hidden" name="hola" id="hidLegajo">
        <div class="w-full px-5 py-2" hidden>
            <div class="flex justify-center items-center py-5 mt-5 gap-5">
                <div class="flex gap-1 justify-center items-center">
                    <label for="txtNombre"
                    class="text-default-800 text-sm font-medium inline-block mb-2">Nombre</label>
                    <input type="text" id="txtNombre" class="form-input" style="width: 350px">
                </div>
                <button type="button" id="btnRegistrar" disabled
                class="btn rounded-full bg-success/25 text-success hover:bg-success hover:text-white">
                    Registrar Legajo <i class="fa-solid fa-floppy-disk"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@vite(['resources/js/functions/legajo_comercial.js'])
@section('script')

@endsection
