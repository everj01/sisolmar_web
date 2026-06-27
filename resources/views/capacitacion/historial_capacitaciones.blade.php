@extends('layouts.vertical', ['title' => 'Historial de capacitaciones'])

@section('content')
@include("layouts.shared/page-title", ["subtitle" => "Capacitación", "title" => "Historial de capacitaciones"])

<div class="card">
    <div class="card-header">
        <h4 class="card-title">Historial de capacitaciones del personal</h4>
    </div>
    <div class="card-body">
        <div class="mb-5 flex items-center gap-4">
             <div class="w-full max-w-sm relative">
                <label for="txtBusqueda" class="sr-only">Buscar</label>
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <i class='bx bx-search text-gray-400'></i>
                </div>
                <input type="text" id="txtBusqueda"
                    class="block w-full p-2.5 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Buscar por DNI o Nombres..." autofocus>
            </div>
            
             <div class="flex items-center gap-2">
                 <input type="checkbox" id="chkInactivos" class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary">
                 <label for="chkInactivos" class="text-sm font-medium text-gray-700 select-none cursor-pointer">
                     Mostrar cesados
                 </label>
             </div>
        </div>

        <div class="overflow-x-auto">
             <div id="tblPersonal"></div>
        </div>
    </div>
</div>

<!-- Modal Historial -->
<div id="modal-historial"
    class="hs-overlay hidden w-full h-full fixed top-0 start-0 z-[60] overflow-x-hidden overflow-y-auto pointer-events-none">
    <div
        class="hs-overlay-open:opacity-100 hs-overlay-open:duration-500 opacity-0 transition-all sm:max-w-4xl sm:w-full m-3 sm:mx-auto min-h-[calc(100%-3.5rem)] flex items-center justify-center">
        <div
            class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:shadow-slate-700/[.7] w-full pointer-events-auto">
            <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                <h3 class="font-bold text-gray-800 dark:text-gray-200" id="modal-title">
                    Historial de Capacitaciones
                </h3>
                <button type="button"
                    class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-lg border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                    data-hs-overlay="#modal-historial">
                    <span class="sr-only">Cerrar</span>
                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24"
                        height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-4 overflow-y-auto">
                 <div class="flex flex-col items-center justify-center mb-6">
                    <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mb-3 text-2xl font-bold text-gray-600 uppercase" id="avatarPersonal">
                        <!-- Initials here -->
                    </div>
                    <h4 class="text-xl font-bold text-gray-800" id="nombrePersonal"></h4>
                    <p class="text-sm text-gray-500" id="cargoPersonal"></p>
                    <p class="text-xs text-gray-400" id="areaPersonal"></p>
                 </div>

                 <!-- Timeline -->
                 <div id="historialContainer" class="relative border-l border-gray-200 dark:border-gray-700 ml-3 space-y-6">
                     <!-- Items injected via JS -->
                 </div>
                 
                 <div id="noDataMessage" class="hidden flex flex-col items-center justify-center py-8 text-gray-500">
                        <i class='bx bx-file-blank text-4xl mb-2'></i>
                        <p>No se encontraron registros de capacitación para este colaborador.</p>
                 </div>
            </div>
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t dark:border-gray-700">
                <button type="button"
                    class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                    data-hs-overlay="#modal-historial">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
@endsection

@vite(['resources/js/functions/capacitacion/historial_capacitaciones.js'])
