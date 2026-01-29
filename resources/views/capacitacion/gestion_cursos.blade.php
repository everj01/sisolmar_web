@extends('layouts.vertical', ['title' => 'Gestión de cursos'])
@section('css')
@endsection
@section('content')
@include("layouts.shared/page-title", ["subtitle" => "Capacitación", "title" => "Gestión de cursos"])


<div class="grid 2xl:grid-cols-2 grid-cols-1 gap-6">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Registro de cursos</h4>
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
                <table id="tblCursos" class="datatable responsive-table w-full">
                <thead>
                    <tr>
                    <th>#</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
                </table>
            </div>
            </div>



        <!-- <div class="card-body">

            <div x-data="{ soloEliminados: false, filtroArea: '', filtroTipoCurso: '' }" class="flex flex-wrap gap-4">
                <div class="flex items-center">
                    <input class="form-switch" type="checkbox" role="switch" 
                        id="chkEliminados" 
                        x-model="soloEliminados">
                    <label class="ms-1.5" for="chkEliminados">Solo eliminados</label>
                </div>

                <select id="slcFiltroTipoCurso" x-model="filtroTipoCurso">
                    <option value="">-- Todos --</option>
                </select>

                <select id="slcFiltroArea" x-model="filtroArea">
                    <option value="">-- Todas --</option>
                </select>

                <div 
                x-effect="listarCursos(
                    soloEliminados ? 0 : 1, 
                    filtroArea, 
                    filtroTipoCurso
                )">
                </div>
            </div>

            <div class="mt-5 overflow-y overflow-x">
                <table id="tblCursos" class="datatable responsive-table" >
                    <thead>
                        <th >#</th>
                        <th >Codigo</th>
                        <th >Nombre</th>
                        <th >Acciones</th>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div> 

        </div> -->
    </div>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">
                Gestión de cursos
                <span
                class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-primary/25 text-primary-800"
                id="txtMensajeNuevo">Nuevo</span>
            </h4>
        </div>
        <div class="flex items-center justify-center gap-2 mt-4 hidden" id="viewEditCreate">
            <span>¿Quieres registrar un curso?</span>
            <button type="button" id="btnCambiarEdit" onclick="restaurarFormCurso()"
            class="btn rounded-full bg-success/25 text-success hover:bg-success hover:text-white">
                Crear curso
            </button>
        </div>
        <div class="card-body" x-data="formCursoGestion()" @submit.prevent>
            <input type="hidden" name="codGestionEditar" x-model="codigo" id="codGestionEditar">
            <div class="w-full mt-4">
                <h3 class="text-lg font-semibold text-default-700 text-center mb-1">Datos del curso</h3>
                <hr>
            </div>
            <div class="w-full grid gap-6 mt-4 lg:grid-cols-1 pb-8">
                <div>
                    <label for="txtNombreCurso" class="text-gray-800 text-base font-medium inline-block mb-2">
                    Nombre del curso
                    </label>
                    <input type="text" id="txtNombreCurso"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm "
                    x-model="nombre" />
                </div>
                <div>
                    <label for="slcTipoCurso" class="text-gray-800 text-base font-medium inline-block mb-2">
                    Tipo de Curso
                    </label>
                    <select id="slcTipoCurso"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm "
                    x-model="tipoCurso" >
                        <option value="">-- Seleccione --</option>
                    </select>
                </div>

                <div>
                    <label for="slcArea" class="text-gray-800 text-base font-medium inline-block mb-2">
                    Área
                    </label>
                    <select id="slcArea"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm "
                    x-model="area" >
                        <option value="">-- Seleccione --</option>
                    </select>
                </div>

                <div>
                    <label for="txtperiodicidad" class="text-gray-800 text-base font-medium inline-block mb-2">
                    Periodicidad (anual)
                    </label>
                    <input type="number" id="txtperiodicidad" min="1"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm "
                    x-model="periodicidad" />
                </div>
            </div>
            <div class="w-full mt-8">
                <h3 class="text-lg font-semibold text-default-700 text-center mb-1">Datos del Examen</h3>
                <hr>
            </div>
            <div class="w-full grid gap-6 mt-4 lg:grid-cols-1 pb-8"  >
                <div>
                    <label for="txtNombreExamen" class="text-gray-800 text-base font-medium inline-block mb-2">
                    Nombre del examen
                    </label>
                    <input
                        type="text"
                        id="txtNombreExamen"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                        x-model="nombreExa"
                        x-effect="nombreExa = nombre ? `Examen de ${nombre}` : ''"
                        readonly
                        />
                </div>
                <div>
                    <label for="txtDescripcion" class="text-gray-800 text-base font-medium inline-block mb-2">
                    Descripción
                    </label>
                    <textarea id="txtDescripcion"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                    x-model="descripcion"></textarea>
                </div>
                <div>
                    <label for="txtLimite" class="text-gray-800 text-base font-medium inline-block mb-2">
                    Límite de tiempo (minutos)
                    </label>
                    <input type="number" id="txtLimite"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                     x-model="limiteTiempo"
                    />
                </div>
                <div>
                    <label for="txtNota" class="text-gray-800 text-base font-medium inline-block mb-2">
                    Nota mínima
                    </label>
                    <input type="number" id="txtNota"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                     x-model="nota"
                    />
                </div>
                <div>
                    <label for="txtIntentos" class="text-gray-800 text-base font-medium inline-block mb-2">
                    Número de intentos
                    </label>
                    <input type="number" id="txtIntentos"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                    x-model="intentos"
                    />
                </div>
            </div>
            <hr>

            <div class="flex flex-col py-5">
                <div class="mt-5">

                    <div class="flex items-center justify-between mb-3">
                        <label for="txtNota" class="text-gray-800 text-base font-medium inline-block mb-2" id="txtTitleFile">
                            Subir Plantilla
                        </label>
                        <a class="btn rounded-full bg-info/25 text-info hover:bg-info hover:text-white cursor-pointer hidden"
                        id="btnDownloadPlantilla">
                            <i class='bx bxs-cloud-download'></i>&nbsp;Descargar plantilla
                        </a>





                    </div>


                    <div class="mt-4">
                        <!-- Botón para seleccionar archivo -->
                        <div id="btnSeleccionar"
                            class="cursor-pointer p-12 flex justify-center bg-white border border-dashed border-default-300 rounded-xl">

                            <div class="text-center">
                                <span class="inline-flex justify-center items-center size-16 bg-default-100 text-default-800 rounded-full cursor-pointer">
                                    <i class="i-tabler-upload size-6 shrink-0"></i>
                                </span>
                                <div class="mt-4 flex flex-wrap justify-center text-sm leading-6 text-default-600">
                                    <span class="pe-1 font-medium text-default-800">
                                        Arrastra tu archivo <b class="font-bold">.mbz</b> aquí o
                                    </span>
                                    <span class="bg-white font-semibold text-primary hover:text-primary-700 rounded-lg decoration-2 hover:underline">
                                        SELECCIONAR
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-default-400">Peso menor a 1MB.</p>
                            </div>
                        </div>

                        <!-- Input oculto -->
                        <input type="file" id="archivoInput" accept=".mbz" class="hidden">

                        <!-- Lista de archivos -->
                        <div class="mt-1">
                            <ul id="listaArchivos" class="mt-4 space-y-2"></ul>
                        </div>

                        <!-- Botón analizar -->
                        <div class="mt-4">
                            <button id="btnAnalizar" type="button"
                                class="px-4 py-2 bg-primary text-white rounded hover:bg-primary-700 disabled:opacity-50"
                                disabled>
                                Analizar Plantilla
                            </button>
                        </div>

                        <!-- Resumen de análisis -->
                        <div id="resumenPlantilla" class="mt-4"></div>
                    </div>




                    <!-- <div class="cursor-pointer p-12 flex justify-center bg-white border border-dashed border-default-300 rounded-xl"
                    id="btnSeleccionar" role="button">
                        <div class="text-center">
                            <span class="inline-flex justify-center items-center size-16 bg-default-100 text-default-800 rounded-full cursor-pointer" >
                                <i class="i-tabler-upload size-6 shrink-0"></i>
                            </span>
                            <div class="mt-4 flex flex-wrap justify-center text-sm leading-6 text-default-600">
                                <span class="pe-1 font-medium text-default-800">
                                    Arrastra tu archivo <b class="font-bold">.mbz</b> aquí o
                                </span>
                                <span class="bg-white font-semibold text-primary hover:text-primary-700 rounded-lg decoration-2 hover:underline focus-within:outline-none focus-within:ring-2 focus-within:ring-primary-600 focus-within:ring-offset-2" >SELECCIONAR</span>
                            </div>
                            <p class="mt-1 text-xs text-default-400">Peso menor a 1MB.</p>
                        </div>

                        <input type="file" id="archivoInput" accept=".mbz" class="hidden"
                            @change="analizarArchivo($event)">
                        
                        <input type="hidden" name="cantArchivos" id="cantArchivos">
                    </div>

                    <div class="mt-1">
                        <ul id="listaArchivos" class="mt-4 space-y-2"></ul>
                    </div>

                    <div id="resumenPlantilla" class="mt-4" x-html="resumen"></div> -->

                </div>
            </div>
            <div class="flex justify-center w-full py-8">
                <button type="submit" id="btnGestion" @click="registrar"
                class="btn rounded-full bg-success/25 text-success hover:bg-success hover:text-white">
                    Registrar Curso&nbsp;<i class="fa-solid fa-floppy-disk"></i>
                </button>
                <button type="button" id="btnGestionEditar" onclick="editarFormGestionCurso()"
                class="hidden btn rounded-full bg-warning/25 text-warning hover:bg-warning hover:text-white ">
                    Actualizar curso
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
@endsection

@vite(['resources/js/functions/capacitacion/gestion_cursos.js'])
@section('script')
@endsection
