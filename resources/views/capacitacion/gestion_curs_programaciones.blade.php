@extends('layouts.vertical', ['title' => 'Gestión de programación'])
@section('css')
@endsection
@section('content')
@include("layouts.shared/page-title", ["subtitle" => "Capacitación", "title" => "Gestión de programación"])
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.3.4/dist/css/datepicker-bulma.min.css">
<div class="grid 2xl:grid-cols-2 grid-cols-1 gap-6">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Lista de cursos</h4>
        </div>
        <div class="card-body">

            <div 
                x-data="{ soloEliminados: false, filtroArea: '', filtroTipoCurso: '' }" 
                class="flex flex-wrap items-end gap-6"
            >
                <div class="flex items-center">
                <input 
                    class="form-switch" 
                    type="checkbox" 
                    role="switch" 
                    id="chkEliminados"
                    x-model="soloEliminados"
                >
                <label class="ms-1.5 font-medium text-sm text-gray-700" for="chkEliminados">
                    Solo eliminados
                </label>
                </div>

                <div class="flex flex-col flex-1 min-w-[200px]">
                <label for="slcFiltroTipoCurso" class="text-sm font-medium text-gray-700 mb-1">
                    Tipo de curso
                </label>
                <select 
                    id="slcFiltroTipoCurso" 
                    x-model="filtroTipoCurso"
                    class="w-full rounded-lg border-gray-300 text-sm px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                >
                    <option value="">-- Todos --</option>
                </select>
                </div>

                <div class="flex flex-col flex-1 min-w-[200px]">
                <label for="slcFiltroArea" class="text-sm font-medium text-gray-700 mb-1">
                    Área
                </label>
                <select 
                    id="slcFiltroArea" 
                    x-model="filtroArea"
                    class="w-full rounded-lg border-gray-300 text-sm px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                >
                    <option value="">-- Todas --</option>
                </select>
                </div>

                <div x-effect="listarCursos( soloEliminados ? 0 : 1, filtroArea, filtroTipoCurso )"></div>
            </div>

            <div class="mt-5 overflow-y overflow-x">
                <table id="tblCursos" class="datatable responsive-table" >
                    <thead>
                        <th >#</th>
                        <th >Codigo</th>
                        <th >Nombre</th>
                        <th >Límite anual</th>
                        <th >Acciones</th>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

     <div class="card hidden" id="cardProgramacion">
        <div class="card-header">
            <h4 class="card-title">
                Programaciones para el curso <span id="txtTituloCurso"></span>
            </h4>
        </div>
        <div class="card-body">
            <div class="flex items-center justify-center gap-2">
                <button type="button"
                class="btn rounded-full bg-primary/25 text-primary hover:bg-primary hover:text-white"
                onclick="window.abrirModalRegistro()">
                <i class='bx bx-plus'></i>&nbsp;Registrar
                </button>


            </div>
            <div class="mt-5 overflow-y overflow-x">
                <table id="tblProgramacion" class="datatable responsive-table" >
                    <thead>
                        <th>#</th>
                        <th>Codigo</th>
                        <th>Periodo</th>
                        <th>Acciones</th>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<div id="modal-registro"
    class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-y-auto hidden pointer-events-none">
    <div class="translate-y-10 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 sm:max-w-lg sm:w-full my-8 sm:mx-auto flex flex-col bg-white shadow-sm rounded">
        <div class="flex flex-col border border-default-200 shadow-sm rounded-lg pointer-events-auto">
            <div class="flex justify-between items-center py-3 px-4 border-b border-default-200">
                
                <button type="button" class="text-default-600 cursor-pointer" id="btn-modal-docs-close"
                    data-hs-overlay="#modal-registro">
                    <i class="i-tabler-x text-lg"></i>
                </button>
            </div>
            
            <div class="card-body p-4" x-data="formProgramacionGestion()" @submit.prevent>
                <input type="hidden" name="codGestionEditar" x-model="codigo" id="codGestionEditar">
                <div class="w-full mt-4">
                    <h3 class="text-lg font-semibold text-default-700 text-center mb-1">Datos de la programación</h3>
                </div>
                <div class="w-full grid gap-6 mt-4 lg:grid-cols-1 pb-8"  >
                    <input type="hidden" id="codigoCursoInput" name="codigoCursoInput" x-model="codigoCurso">
                    <div>
                        <label for="nombreCurso" class="text-gray-800 text-base font-medium inline-block mb-2">
                            Nombre del curso
                        </label>
                        <input type="text" id="nombreCurso"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-gray-100"
                            x-model="nombreCurso" readonly />
                    </div>
                    <div>
                        <label for="fechaInicio" class="text-gray-800 text-base font-medium inline-block mb-2">
                            Fecha de inicio
                        </label>
                        <input type="date" id="fechaInicio"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                            x-model="fechaInicio" />
                    </div>
                    <div>
                        <label for="fechaFin" class="text-gray-800 text-base font-medium inline-block mb-2">
                            Fecha de fin
                        </label>
                        <input type="date" id="fechaFin"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                            x-model="fechaFinal" />
                    </div>
                </div>
                <div class="flex justify-center w-full py-8">
                    <button type="submit" id="btnGestionProgramacion"
                    class="btn rounded-full bg-success/25 text-success hover:bg-success hover:text-white">
                        Guradar Programación&nbsp;<i class="fa-solid fa-floppy-disk"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- <div id="modal-registro"
    class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-y-auto hidden pointer-events-none">
    <div class="translate-y-10 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 sm:max-w-lg sm:w-full my-8 sm:mx-auto flex flex-col bg-white shadow-sm rounded">
        <div class="flex flex-col border border-default-200 shadow-sm rounded-lg  pointer-events-auto">
            <div class="flex justify-between items-center py-3 px-4 border-b border-default-200">
                <h3 class="text-lg font-medium text-default-900 modal-title">Registrar Programación para <span id="txtTituloModalRegistro"></span></h3>
                <button type="button" class="text-default-600 cursor-pointer" id="btn-modal-docs-close"
                    data-hs-overlay="#modal-registro">
                    <i class="i-tabler-x text-lg"></i>
                </button>
            </div>



        </div>
    </div>
</div> -->








@endsection
@section('script')
<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
@endsection

@vite(['resources/js/functions/capacitacion/gestion_programacion.js'])
@section('script')
@endsection
