@extends('layouts.vertical', ['title' => 'Gestion de cargo'])

@section('css')

@endsection

@section('content')

@include("layouts.shared/page-title", [
    "subtitle" => "Gestion de Legajos",
    "title" => "Gestión de legajos"
])

<script src="https://kit.fontawesome.com/76256ea07c.js" crossorigin="anonymous"></script>

<style>
    .disabled-table {
        pointer-events: none;
        opacity: 0.3;
    }

    /* Cards con altura fija */
    .card-altura-fija {
        max-height: 400px; /* Ajusta esta altura según necesites */
    }

    /* Contenedor de tabla con scroll */
    .tabla-container {
        max-height: 280px; /* Ajusta según el espacio que necesites para la tabla */
        overflow-y: auto;
        overflow-x: auto;
    }

    /* Para el card de folios que es más alto */
    .card-folios {
        max-height: 820px; /* Aproximadamente el doble para que coincida con los dos de la izquierda */
    }

    .tabla-folios-container {
        max-height: 500px; /* Más espacio para la tabla de folios */
        overflow-y: auto;
        overflow-x: auto;
    }
</style>

<div class="grid lg:grid-cols-2 gap-6 mt-8">

    <!-- ===================== -->
    <!-- COLUMNA IZQUIERDA -->
    <!-- ===================== -->
    <div class="flex flex-col gap-6">

        <!-- SELECCIÓN CLIENTE -->
        <div class="card card-altura-fija">
            <div class="card-header flex justify-between items-center">
                <h3 class="card-title">Selección del CLIENTE</h3>
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
                <h3 class="card-title">Selección del CARGO</h3>
                <input type="text" id="buscarCargo" placeholder="Buscar cargo..."
                    class="w-40 px-3 py-1 border border-gray-300 rounded-full text-sm">
            </div>

            <div class="flex justify-center items-center gap-4 mt-4">
                <div class="form-check">
                    <input type="radio" class="form-radio text-primary" name="cargoFiltro"
                        id="radioTodos" value="TODOS" checked>
                    <label class="ms-1.5">Todos</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-radio text-primary" name="cargoFiltro"
                        id="radioOper" value="OPERATIVO">
                    <label class="ms-1.5">Operativo</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-radio text-primary" name="cargoFiltro"
                        id="radioAdmin" value="ADMINISTRATIVO">
                    <label class="ms-1.5">Administrativo</label>
                </div>
            </div>

            <div class="w-full px-5 py-2">
                <div id="tblCargo" class="w-full mt-5 tabla-container"></div>
            </div>
        </div>

    </div>

    <!-- ===================== -->
    <!-- COLUMNA DERECHA -->
    <!-- ===================== -->
    <div class="card card-folios">

        <div class="card-header flex justify-between items-center">
            <h4 class="card-title">Asignar los FOLIOS</h4>
            <input type="text" id="buscarFolio" placeholder="Buscar folios..."
                class="w-40 px-3 py-1 border border-gray-300 rounded-full text-sm">
        </div>

        <div class="w-full px-5 py-2">

            <div class="flex justify-between items-center py-5 mt-5">
                <div class="flex items-center gap-5">
                    <div class="flex gap-1 items-center">
                        <label class="text-sm font-medium">Nombre</label>
                        <input type="text" id="txtNombre" class="form-input w-[550px]" disabled>
                    </div>

                    <button type="button" id="btnRegistrar"
                        onclick="guardarLegajo()" disabled
                        class="btn rounded-full bg-success/25 text-success hover:bg-success hover:text-white">
                        Guardar Legajo
                    </button>
                </div>

                <!-- NOTIFICACIONES -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="relative">
                        <i class="fa-solid fa-envelope text-2xl text-gray-700"></i>

                        @if(isset($notify) && count($notify) > 0)
                            <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full px-1">
                                {{ count($notify) }}
                            </span>
                        @endif
                    </button>

                    <div x-show="open" @click.outside="open = false" x-transition
                        class="absolute top-full mt-2 right-0 w-[36rem] max-h-[36rem] overflow-y-auto
                        bg-white border border-gray-300 rounded-xl shadow-2xl z-50 p-5">

                        @if(isset($notify) && count($notify) > 0)
                            @foreach ($notify as $nf)
                                <div class="mb-4 p-4 bg-blue-50 border-l-4 border-blue-400 rounded-xl">
                                    <div class="flex space-x-3">
                                        <i class="fa-solid {{ $nf->tipo ? 'fa-trash-can text-red-600' : 'fa-plus text-green-600' }}"></i>
                                        <div>
                                            <p class="font-semibold">
                                                Solicitud de {{ $nf->tipo ? 'Desactivación' : 'Activación' }}
                                            </p>
                                            <p class="text-sm">
                                                Se solicita {{ $nf->tipo ? 'quitar' : 'activar' }} el folio
                                                <b>{{ $nf->folio }}</b><br>
                                                Cliente: <b>{{ $nf->cliente }}</b><br>
                                                Cargo: <b>{{ $nf->cargo }}</b>
                                            </p>
                                            <p class="text-xs text-right text-gray-500 mt-2">
                                                {{ date('d/m/Y', strtotime($nf->fecha)) }} - {{ $nf->hora }}
                                                <button type="button"
                                                    class="btn btn-sm rounded-full bg-warning/25 text-warning hover:bg-warning hover:text-white"
                                                    onclick="quitarNotificacion('{{ $nf->codigo }}')">
                                                    Quitar
                                                </button>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-center text-gray-500 text-sm">No hay notificaciones.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div id="notifiSoli" class="flex flex-col items-center gap-1"></div>

            <div id="tblFolio" class="w-full mt-5 tabla-folios-container"></div>

            <input type="hidden" id="hidLegajo">
        </div>
    </div>

</div>

@endsection

@vite(['resources/js/functions/legajo.js'])
@section('script')

@endsection
