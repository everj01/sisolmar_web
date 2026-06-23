@extends('layouts.vertical', ['title' => 'Gestión de Folios'])

@section('content')

@include('layouts.shared/page-title', ['subtitle' => 'File Control', 'title' => 'Gestíon de Folios'])

 <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mt-8">

    {{-- ── CARD IZQUIERDA: Listado de Folios ───────────────────────────── --}}
     <div class="card overflow-hidden lg:col-span-8">
        <div class="card-header">
            <h4 class="card-title">Listado de Folios</h4>
        </div>

        <div class="px-5 py-4 space-y-4">

            {{-- Filtros de tipo --}}
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2">

                {{-- Grupo 1: tipos (principal / adicional) --}}
                  <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                      <span class="text font-semibold">Prioridad: </span>
                      <label class="flex items-center gap-1.5 cursor-pointer">
                          <input type="radio" class="form-radio text-primary" id="radioTod" name="filtroTipos" value="TODOS" checked>
                          <span class="text-sm">Todos ({{ $todos }})</span>
                      </label>
                      <label class="flex items-center gap-1.5 cursor-pointer">
                          <input type="radio" class="form-radio text-primary" id="radioPri" name="filtroTipos" value="PRINCIPAL">
                          <span class="text-sm">Principal ({{ $principal }})</span>
                      </label>
                      <label class="flex items-center gap-1.5 cursor-pointer">
                          <input type="radio" class="form-radio text-primary" id="radioAdic" name="filtroTipos" value="ADICIONAL">
                          <span class="text-sm">Adicional ({{ $adicional }})</span>
                      </label>
                  </div>

                  <div class="w-px h-5 bg-gray-200 hidden sm:block"></div>

                  {{-- Grupo 2: clasificación (documento / formato / certificado) --}}
                  <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                      <span class="text font-semibold">Tipos: </span>
                      <label class="flex items-center gap-1.5 cursor-pointer">
                          <input type="radio" class="form-radio text-danger" id="radioTodClasif" name="filtroClasificacion" value="TODOS" checked>
                          <span class="text-sm">Todos</span>
                      </label>
                      <label class="flex items-center gap-1.5 cursor-pointer">
                          <input type="radio" class="form-radio text-danger" id="radioDoc" name="filtroClasificacion" value="DOCUMENTO">
                          <span class="text-sm">Documento ({{ $documento }})</span>
                      </label>
                      <label class="flex items-center gap-1.5 cursor-pointer">
                          <input type="radio" class="form-radio text-danger" id="radioForm" name="filtroClasificacion" value="FORMATO">
                          <span class="text-sm">Formato ({{ $formato }})</span>
                      </label>
                      <label class="flex items-center gap-1.5 cursor-pointer">
                          <input type="radio" class="form-radio text-danger" id="radioCert" name="filtroClasificacion" value="CERTIFICADO">
                          <span class="text-sm">Certificado ({{ $certificado }})</span>
                      </label>
                  </div>
         

                  {{-- Grupo 3: vencimiento --}}
                  <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                      <span class="text font-semibold">Vencimiento: </span>
                      <label class="flex items-center gap-1.5 cursor-pointer">
                          <input type="radio" class="form-radio text-success" id="radioVenTod" name="vencimientoFiltro" value="TODOS" checked>
                          <span class="text-sm">Todos</span>
                      </label>
                      <label class="flex items-center gap-1.5 cursor-pointer">
                          <input type="radio" class="form-radio text-success" id="radioConVen" name="vencimientoFiltro" value="CON_VENCIMIENTO">
                          <span class="text-sm">Con vencimiento</span>
                      </label>
                      <label class="flex items-center gap-1.5 cursor-pointer">
                          <input type="radio" class="form-radio text-success" id="radioSinVen" name="vencimientoFiltro" value="SIN_VENCIMIENTO">
                          <span class="text-sm">Sin vencimiento</span>
                      </label>
                  </div>
             

            </div>

            {{-- Buscador + switch Solo activos --}}
            <div class="flex items-center justify-between gap-3">
                <input
                    type="text"
                    id="buscar"
                    placeholder="Buscar..."
                    class="w-40 px-3 py-1.5 text-sm border border-gray-300 rounded-full
                           focus:outline-none focus:border-blue-500 transition-colors"
                />
                <div x-data="{ soloActivos: true }" class="flex items-center gap-2">
                    <input type="checkbox" class="form-switch text-primary" role="switch"
                           id="chkEliminados" x-model="soloActivos">
                    {{-- AQUÍ LE METEMOS ALPINE.JS PARA QUE EL TEXTO CAMBIE DINÁMICAMENTE --}}
                    <label for="chkEliminados" class="text-sm cursor-pointer" x-text="soloActivos ? 'Solo activos' : 'Solo inactivos'"></label>
                    <div x-effect="soloActivos ? aplicarFiltroSoloActivos(1) : aplicarFiltroSoloActivos(0)"></div>
                </div>
            </div>

            {{-- Tabla --}}
            <div id="tblFolios" class="w-full"></div>

            {{-- Selector de registros --}}
            <div class="flex items-center gap-2">
                <label for="page-size" class="text-sm text-gray-600">Mostrar</label>
                 <select id="page-size" class="form-select text-sm w-20">
                      <option value="5">5</option>
                      <option value="10">10</option>
                      <option value="20" selected>20</option>
                      <option value="50">50</option>
                      <option value="100">100</option>
                  </select>
                <span class="text-sm text-gray-600">registros</span>
            </div>

        </div>
    </div>

    {{-- ── CARD DERECHA: Formulario de Gestión ────────────────────────── --}}
   <div class="card lg:col-span-4">
        <div class="card-header flex items-center gap-3">
            <h3 class="card-title">Gestión de Folios</h3>
            <span id="txtMensajeNuevo"
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-primary/25 text-primary-800">
                Nuevo registro
            </span>
        </div>

        {{-- Aviso solo en modo edición --}}
        <div id="soloEdicion" class="hidden items-center justify-center gap-2 px-5 pt-3">
            <span class="text-sm text-gray-600">¿Quieres crear un nuevo folio?</span>
            <button class="btn bg-info clean-btn text-white rounded-full">Nuevo folio</button>
        </div>

        <div class="px-6 py-4">
            <form id="formSaveFolio">
                <div x-data="{ nameFolio: '', tipoSeleccionado: '' }" class="space-y-4">

                    {{-- Nombre + Tipo --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                              <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                              <input type="text" id="nombre" class="form-input w-full"
                                     x-model="nameFolio"
                                     @input="nameFolio = nameFolio.toUpperCase()"
                                     placeholder="Nombre del folio" required>
                              <div id="avisoNombreRepetidoFolio" class="hidden mt-1 flex items-center gap-2 px-3 py-2 rounded-md bg-yellow-50 border border-yellow-300 text-yellow-800 text-xs">
                                  <i class="fa-solid fa-triangle-exclamation"></i>
                                  <span>Ya existe un folio con este nombre.</span>
                              </div>
                          </div>
                        <div>
                            <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                            <select id="tipo" class="form-select w-full" x-model="tipoSeleccionado" required>
                                <option value="" disabled selected>— Seleccionar —</option>
                                <option value="1">DOCUMENTO</option>
                                <option value="2">FORMATO</option>
                                <option value="3">CERTIFICADO</option>
                            </select>
                        </div>
                    </div>

                    {{-- Responsable y Categoría --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="responsable" class="block text-sm font-medium text-gray-700 mb-1">Responsable</label>
                            <select id="responsable" class="form-select w-full" required>
                                <option value="" disabled selected>— Seleccionar —</option>
                                @foreach ($roles as $rol)
                                    <option value="{{ $rol->codigo }}">{{ $rol->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                            <select id="categoria" class="form-select w-full" required>
                                <option value="" disabled selected>— Seleccionar —</option>
                                @foreach ($categorias as $cat)
                                    <option value="{{ $cat->codigo }}">{{ $cat->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Descripción contextual por tipo --}}
                    <div>
                        <span x-show="tipoSeleccionado === '1'"
                              class="block w-full px-3 py-2 rounded-md text-xs font-medium bg-red-100 text-red-800">
                            <strong>Documento:</strong> Folio que el personal trae a la empresa.
                        </span>
                        <span x-show="tipoSeleccionado === '2'"
                              class="block w-full px-3 py-2 rounded-md text-xs font-medium bg-red-100 text-red-800">
                            <strong>Formato:</strong> Folio emitido por la empresa.
                        </span>
                        <span x-show="tipoSeleccionado === '3'"
                              class="block w-full px-3 py-2 rounded-md text-xs font-medium bg-red-100 text-red-800">
                            <strong>Certificado:</strong> Folio emitido por una entidad educativa.
                        </span>
                    </div>

                    {{-- Tipo de folio: Principal / Adicional --}}
                    <div class="flex items-center gap-5">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" class="form-radio text-primary" id="radioPrin" name="tipo_folio" value="PRINCIPAL" checked>
                            <span class="text-sm">Principal</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" class="form-radio text-primary" id="radioAdi" name="tipo_folio" value="ADICIONAL">
                            <span class="text-sm">Adicional</span>
                        </label>
                    </div>

                    {{-- Vencimiento + Periodo --}}
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="switchVencimiento" class="form-switch text-danger">
                            <label for="switchVencimiento" class="text-sm cursor-pointer">Vencimiento</label>
                        </div>
                        <div id="periodoDiv" class="hidden flex items-center gap-2">
                            <label for="periodo" class="text-sm font-medium text-gray-700 whitespace-nowrap">Periodo</label>
                            <select id="periodo" class="form-select text-sm">
                                <option disabled selected>— Seleccionar —</option>
                                @foreach ($periodos as $periodo)
                                    <option value="{{ $periodo->codigo }}">{{ $periodo->descripcion }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Institución (oculto por defecto) --}}
                    <div id="institucionDiv" class="hidden flex items-center gap-5">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" class="form-radio text-danger" id="radioICMA" name="institucion" value="ICMA">
                            <span class="text-sm">ICMA</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" class="form-radio text-danger" id="radioAV" name="institucion" value="AV">
                            <span class="text-sm">AV</span>
                        </label>
                    </div>

                </div>

                <input type="hidden" name="codFolio" id="codFolio">

                {{-- Botones --}}
                <div class="flex justify-center gap-3 mt-6">
                    <button type="submit" id="submitButton"
                            class="btn rounded-full bg-success/25 text-success hover:bg-success hover:text-white">
                        Guardar <i class="fa-solid fa-floppy-disk"></i>
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>

@endsection

@vite(['resources/js/functions/folios.js'])