@extends('layouts.vertical', ['title' => 'Gestion de cargo'])

@section('css')

@endsection

@section('content')

@include("layouts.shared/page-title", ["subtitle" => "Gestion de Legajos", "title" => "Gestión de legajos"])
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
            <h3 class="text-center card-title">Selección del CLIENTE</h3>
            <input type="text" placeholder="Buscar cliente..." id="buscarCliente" class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm">
        </div>
        <div class="w-full px-5 py-2">
            <div id="tblCliente" class="w-full mt-5  overflow-y-auto overflow-x-hidden"></div>
        </div>

    </div> <!-- end card -->

    <div class="card">
        <div class="card-header flex gap-1 justify-between items-center">
            <h3 class="text-center card-title">Selección del CARGO</h3>
            <input type="text" id="buscarCargo" placeholder="Buscar cargo..." class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm" />
        </div>
        <div class="flex justify-center items-center gap-4 mt-4">
            <div class="form-check">
                <input type="radio" class="form-radio text-primary" 
                name="cargoFiltro" id="radioTodos" value="TODOS" checked>
                <label class="ms-1.5" for="radioTodos">Todos</label>
            </div>
            <div class="form-check">
                <input type="radio" class="form-radio text-primary" 
                name="cargoFiltro" id="radioOper" value="OPERATIVO">
                <label class="ms-1.5" for="radioOper">Operativo</label>
            </div>
            <div class="form-check">
                <input type="radio" class="form-radio text-primary" 
                name="cargoFiltro" id="radioAdmin" value="ADMINISTRATIVO">
                <label class="ms-1.5" for="radioAdmin">Administrativo</label>
            </div>
        </div>
        <div class="w-full px-5 py-2">
            <div id="tblCargo" class="w-full mt-5 overflow-y-auto overflow-x-hidden"></div>
        </div>
    </div> <!-- end card -->

    <div class="card col-span-2">
        <div class="card-header flex gap-1 justify-between items-center">
            <h4 class="card-title">Asignar los FOLIOS</h4>
            <input type="text" id="buscarFolio" placeholder="Buscar folios..." class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm" />
        </div>

        <!-- <div class="bg-primary text-sm text-white rounded-md p-4" role="alert">
            Se pide activar el folio ESCANEO DNI para el cliente CORPORACION HAYDUK SAC con cargo AGENTE DE SEGURIDAD FLOTA
        </div> -->

        <div class="w-full px-5 py-2">
            <div class="flex justify-between items-center py-5 mt-5">
                <div class="flex items-center gap-5">
                    
                    <div class="flex gap-1 justify-center items-center">
                        <label for="txtNombre"
                            class="text-default-800 text-sm font-medium inline-block mb-2">Nombre</label>
                        <input type="text" id="txtNombre" class="form-input" style="width: 550px" disabled>
                    </div>

                    <div x-data>
                        <button type="button" id="btnRegistrar" @click="guardarLegajo()" disabled
                            class="btn rounded-full bg-success/25 text-success hover:bg-success hover:text-white">
                            Guardar Legajo
                        </button>
                    </div>
                </div>

                <div class="relative flex justify-end px-6 w-auto" x-data="{ open: false }">
                    <!-- Notificaciones -->
                    <button @click="open = !open" class="relative focus:outline-none">

                        <i class="fa-solid fa-envelope text-2xl text-gray-700"></i>
                        @if(isset($notify) && count($notify) > 0)
                            <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full px-1">
                                {{ count($notify) }}
                            </span>
                        @endif
                    </button>

                    <!-- Modal de notificaciones -->
                    <div
                    x-show="open"
                    @click.outside="open = false"
                    x-transition
                    class="absolute top-full mt-2 right-0 w-[36rem] max-h-[36rem] overflow-y-auto bg-white border border-gray-300 rounded-xl shadow-2xl z-50 p-5"
                    >
                        @if(isset($notify) && count($notify) > 0)
                            @foreach ($notify as $nf)
                                <div class="mb-4 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-800 rounded-xl shadow-sm">
                                    <div class="flex space-x-3">
                                        <i class="fa-solid {{ $nf->tipo ? 'fa-trash-can text-red-600' : 'fa-plus text-green-600' }}"></i>
                                        <div class="">
                                            <p class="font-semibold text-base">
                                                Solicitud de {{ $nf->tipo ? 'Desactivación' : 'Activación' }}
                                            </p>
                                            <p class="text-sm leading-relaxed">
                                                Se solicita <span class="font-medium">{{ $nf->tipo ? 'quitar' : 'activar' }}</span> el folio
                                                <span class="font-medium">{{ $nf->folio }}</span><br>
                                                Cliente: <span class="font-bold">{{ $nf->cliente }}</span><br>
                                                Cargo: <span class="font-bold">{{ $nf->cargo }}</span>.
                                            </p>
                                            <p class="text-xs text-gray-500 mt-2 text-right">
                                                <i class="fa-solid fa-calendar"></i> {{  date("d/m/Y", strtotime($nf->fecha))}} &nbsp;
                                                <i class="fa-solid fa-clock"></i> {{ $nf->hora }}&nbsp;&nbsp;&nbsp;&nbsp;
                                                <button type="button" class="btn btn-sm rounded-full bg-warning/25 text-warning hover:bg-warning hover:text-white" onclick="quitarNotificacion('{{ $nf->codigo }}')">Quitar</button>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-gray-500 text-sm text-center">No hay notificaciones.</p>
                        @endif
                    </div>
                </div>

            </div>

        </div>















        <div class="flex flex-col items-center justify-center gap-1" id="notifiSoli">
        </div>

        <div class="w-full px-5 py-2 " >
            <div id="tblFolio" class="w-full mt-5 overflow-y-auto overflow-x-hidden">
            </div>
        </div>
        <input type="hidden" name="hola" id="hidLegajo">

    </div>
</div>

@endsection

@vite(['resources/js/functions/legajo.js'])
@section('script')

@endsection
