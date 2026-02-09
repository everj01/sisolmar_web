@extends('layouts.vertical', ['title' => 'Gestion de cargo'])

@section('css')

@endsection

@section('content')

@include("layouts.shared/page-title", ["subtitle" => "Requisitos de Legajos", "title" => "Requisitos de Legajos"])

<script src="https://kit.fontawesome.com/76256ea07c.js" crossorigin="anonymous"></script>

<style>
    .disabled-table {
        pointer-events: none;
        opacity: 0.3;
    }

    /* Cards con altura fija */
    .card-altura-fija {
        max-height: 400px;
    }

    /* Contenedor de tabla con scroll */
    .tabla-container {
        max-height: 280px;
        overflow-y: auto;
        overflow-x: auto;
    }

    /* Para el card de folios */
    .card-folios {
        max-height: 824px; /* Altura total: 400px + 400px + 24px gap */
        display: flex;
        flex-direction: column;
    }

    .card-folios-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        min-height: 0;
    }

    .tabla-folios-container {
        flex: 1;
        overflow-y: auto;
        overflow-x: auto;
        min-height: 0;
    }
</style>

<div class="grid gap-6 mt-8" style="grid-template-columns: 40% 60%;">

    <!-- ===================== -->
    <!-- COLUMNA IZQUIERDA (40%) -->
    <!-- ===================== -->
    <div class="flex flex-col gap-6">

        <!-- SELECCIÓN CLIENTE -->
        <div class="card card-altura-fija">
            <div class="card-header flex justify-between items-center">
                <h3 class="card-title">SELECCIONAR CLIENTE</h3>
                <input type="text" id="buscarCliente" placeholder="Buscar cliente..."
                    class="w-40 px-3 py-1 border border-gray-300 rounded-full text-sm">
            </div>
            <div class="w-full px-5 py-2">
                <div id="tblCliente" class="w-full mt-5 tabla-container"></div>
            </div>
        </div>

        <!-- SELECCIÓN CARGO -->
        <div class="card card-altura-fija">
            <div class="card-header flex justify-between items-center">
                <h3 class="card-title">SELECCIONAR CARGO</h3>
                <input type="text" id="buscarCargo" placeholder="Buscar cargo..."
                    class="w-40 px-3 py-1 border border-gray-300 rounded-full text-sm">
            </div>
            <div class="w-full px-5 py-2">
                <div id="tblCargo" class="w-full mt-5 tabla-container"></div>
            </div>
        </div>

    </div>

    <!-- ===================== -->
    <!-- COLUMNA DERECHA (60%) -->
    <!-- ===================== -->
    <div class="card card-folios">

        <div class="card-header flex justify-between items-center">
            <h4 class="card-title">BUSCAR FOLIOS</h4>
            <input type="text" id="buscarFolio" placeholder="Buscar folios..."
                class="w-40 px-3 py-1 border border-gray-300 rounded-full text-sm">
        </div>

        <div class="card-folios-content px-5 py-2">

            <div id="dismiss-alert" class="hs-removing:translate-x-5 hs-removing:opacity-0 transition duration-300 bg-teal-50 border border-teal-200 rounded-md p-4 mb-3" role="alert">
                <div class="flex items-center gap-3">
                    <i class="i-tabler-circle-check text-xl"></i>
                    <div class="flex-grow">
                        <div class="text-sm text-teal-800 font-medium">
                            Las notificaciones están vigentes 24 horas.
                        </div>
                    </div>
                    <button data-hs-remove-element="#dismiss-alert" type="button" id="dismiss-test" class="ms-auto h-8 w-8 rounded-full bg-default-200 flex justify-center items-center">
                        <i class="i-tabler-x text-xl"></i>
                    </button>
                </div>
            </div>

            <div id="tblFolio" class="tabla-folios-container"></div>

            <input type="hidden" id="hidLegajo">

            <div class="w-full" hidden>
                <div class="flex justify-center items-center py-5 gap-5">
                    <div class="flex gap-1 justify-center items-center">
                        <label for="txtNombre" class="text-default-800 text-sm font-medium inline-block">Nombre</label>
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

</div>

@endsection

<!-- @section('content')

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

@endsection -->

@vite(['resources/js/functions/legajo_comercial.js'])
@section('script')

@endsection
