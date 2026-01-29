@extends('layouts.vertical', ['title' => 'Gestión de matrícula'])
@section('css')
@endsection
@section('content')
    @include("layouts.shared/page-title", ["subtitle" => "Capacitación", "title" => "Gestión de matrícula"])


    <div class="grid 2xl:grid-cols-2 grid-cols-1 gap-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Lista de cursos</h4>
            </div>

            <div class="card-body">
                <div x-data="{ soloEliminados: false, filtroArea: '', filtroTipoCurso: '' }"
                    class="flex flex-wrap items-end gap-6">
                    <div class="flex items-center">
                        <input class="form-switch" type="checkbox" role="switch" id="chkEliminados"
                            x-model="soloEliminados">
                        <label class="ms-1.5 font-medium text-sm text-gray-700" for="chkEliminados">
                            Solo eliminados
                        </label>
                    </div>

                    <div class="flex flex-col flex-1 min-w-[200px]">
                        <label for="slcFiltroTipoCurso" class="text-sm font-medium text-gray-700 mb-1">
                            Tipo de curso
                        </label>
                        <select id="slcFiltroTipoCurso" x-model="filtroTipoCurso"
                            class="w-full rounded-lg border-gray-300 text-sm px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">-- Todos --</option>
                        </select>
                    </div>

                    <div class="flex flex-col flex-1 min-w-[200px]">
                        <label for="slcFiltroArea" class="text-sm font-medium text-gray-700 mb-1">
                            Área
                        </label>
                        <select id="slcFiltroArea" x-model="filtroArea"
                            class="w-full rounded-lg border-gray-300 text-sm px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
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


        </div>

    </div>


    <div id="modal-registro"
        class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-y-auto hidden pointer-events-none">
        <div
            class="translate-y-10 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 w-11/12 md:w-2/3 max-w-5xl my-8 mx-auto flex flex-col bg-white shadow-sm rounded">
            <div class="flex flex-col border border-default-200 shadow-lg rounded-lg pointer-events-auto">

                <!-- Header -->
                <div
                    class="flex justify-between items-center py-4 px-6 border-b border-default-200 bg-gradient-to-r from-primary-50 to-primary-100">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800">Matricular Personal</h3>
                        <p class="text-sm text-gray-600 mt-1">Seleccione el personal que desea matricular en el curso</p>
                    </div>
                    <button type="button" class="text-gray-500 hover:text-gray-700 transition-colors"
                        data-hs-overlay="#modal-registro">
                        <i class="i-tabler-x text-2xl"></i>
                    </button>
                </div>

                <!-- Contenido -->
                <div class="p-6">
                    <!-- Información del curso -->
                    <div class="mb-4 p-3 bg-blue-50 border-l-4 border-blue-500 rounded">
                        <p class="text-sm text-gray-700">
                            <span class="font-semibold">Curso:</span>
                            <span id="nombreCurso">Cargando...</span>
                        </p>
                    </div>

                    <!-- Buscador mejorado -->
                    <div class="mb-4">
                        <div class="relative">
                            <i class="i-tabler-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" id="buscarPersonal" class="form-input w-full pl-10"
                                placeholder="Buscar por nombre, DNI o cargo..." />
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="flex gap-4 mb-4">
                        <div class="flex-1 p-3 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-xs text-gray-600">Ya matriculados</p>
                            <p class="text-2xl font-bold text-green-600" id="countMatriculados">0</p>
                        </div>
                        <div class="flex-1 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                            <p class="text-xs text-gray-600">Disponibles</p>
                            <p class="text-2xl font-bold text-orange-600" id="countDisponibles">0</p>
                        </div>
                        <div class="flex-1 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-xs text-gray-600">Seleccionados</p>
                            <p class="text-2xl font-bold text-blue-600" id="countSeleccionados">0</p>
                        </div>
                    </div>

                    <!-- Tabla con Tabulator -->
                    <div class="border border-default-200 rounded-lg overflow-hidden">
                        <div id="tblPersonalMatricula" style="height: 400px;"></div>
                    </div>
                </div>

                <!-- Footer mejorado -->
                <div class="flex justify-between items-center gap-3 px-6 py-4 bg-gray-50 border-t border-default-200">
                    <div class="text-sm text-gray-600">
                        <span id="mensajeSeleccion">Seleccione el personal a matricular</span>
                    </div>
                    <div class="flex gap-2">
                        <button class="btn bg-gray-200 hover:bg-gray-300 transition-colors"
                            data-hs-overlay="#modal-registro">
                            Cancelar
                        </button>
                        <button id="btnGuardarMatricula"
                            class="btn bg-success hover:bg-success/90 text-white transition-colors">
                            <i class="i-tabler-check mr-2"></i>
                            Matricular Seleccionados
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- <div id="modal-registro"
      class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-y-auto hidden pointer-events-none">
      <div
        class="translate-y-10 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 sm:max-w-lg sm:w-full my-8 sm:mx-auto flex flex-col bg-white shadow-sm rounded">
        <div class="flex flex-col border border-default-200 shadow-sm rounded-lg pointer-events-auto">

          <div class="flex justify-between items-center py-3 px-4 border-b border-default-200">
            <h3 class="text-lg font-semibold">Seleccionar Personal</h3>
            <button type="button" class="text-default-600 cursor-pointer" data-hs-overlay="#modal-registro">
              <i class="i-tabler-x text-lg"></i>
            </button>
          </div>

          <div class="p-4">
            <input type="text" id="buscarPersonal" class="form-input w-full"
              placeholder="Buscar personal..." />
          </div>

          <div class="overflow-y-auto max-h-[300px]">
            <table class="min-w-full text-sm text-left">
              <thead class="bg-gray-50 sticky top-0">
                <tr>
                  <th class="px-4 py-2">Nombre</th>
                  <th class="px-4 py-2">Matricular</th>
                </tr>
              </thead>
              <tbody id="tablaPersonal"></tbody>
            </table>
          </div>

          <div class="flex justify-end gap-2 p-4 border-t border-default-200">
            <button class="btn bg-gray-200" data-hs-overlay="#modal-registro">Cancelar</button>
            <button id="btnGuardarMatricula" class="btn bg-success text-white">Guardar</button>
          </div>

        </div>
      </div>
    </div> -->






@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
@endsection

@vite(['resources/js/functions/capacitacion/gestion_matricula.js'])
@section('script')
@endsection