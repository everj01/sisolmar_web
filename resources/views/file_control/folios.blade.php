@extends('layouts.vertical', ['title' => 'Gestión de Folios'])

@section('content')

@include('layouts.shared/page-title', ['subtitle' => 'File Control', 'title' => 'Gestíon de Folios'])

 <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mt-8">

    {{-- ── CARD PRINCIPAL: Listado de Folios ───────────────────────────── --}}
     <div class="card overflow-hidden lg:col-span-12">
        <div class="card-header flex flex-wrap justify-between items-center gap-4 border-b border-gray-100 pb-4">
            <h4 class="text-lg font-bold text-primary uppercase">Listado de Folios</h4>
            
            <div class="flex items-center gap-4">
                {{-- Tarjetas de Indicadores --}}
                <div class="flex gap-2 border-r border-gray-200 pr-4">
                    <div class="bg-blue-50 px-3 py-2 rounded-lg border border-blue-200 text-center min-w-[90px]">
                        <span class="block text-[9px] text-blue-600 font-bold uppercase">Total</span>
                        <span id="countTotal" class="text-lg font-bold text-blue-700 leading-none">0</span>
                    </div>
                    <div class="bg-green-50 px-3 py-2 rounded-lg border border-green-200 text-center min-w-[90px]">
                        <span class="block text-[9px] text-green-600 font-bold uppercase">Activos</span>
                        <span id="countActivos" class="text-lg font-bold text-green-700 leading-none">0</span>
                    </div>
                    <div class="bg-red-50 px-3 py-2 rounded-lg border border-red-200 text-center min-w-[90px]">
                        <span class="block text-[9px] text-red-600 font-bold uppercase">Inactivos</span>
                        <span id="countInactivos" class="text-lg font-bold text-red-700 leading-none">0</span>
                    </div>
                </div>

                <button type="button" id="btnNuevoFolio" class="btn bg-primary text-white rounded-full text-sm px-4 py-2">
                    <i class="fa-solid fa-plus me-1"></i> Nuevo Folio
                </button>
            </div>
        </div>

        <div class="px-5 py-4 space-y-4">

            {{-- Caja unificada de Filtros (Estilo Actualizar DJ) --}}
            <div class="flex flex-wrap items-center justify-between gap-4 bg-slate-50 p-4 rounded-lg border border-slate-200">
                <div class="flex flex-wrap items-center gap-4">
                    
                    {{-- Búsqueda --}}
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            id="buscar"
                            placeholder="Buscar folio..."
                            autocomplete="off"
                            class="w-48 px-4 py-1.5 border border-gray-300 rounded-full focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-sm uppercase" 
                            style="min-width: 200px;"
                        />
                    </div>

                    {{-- Prioridad --}}
                    <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                        <label for="filtroTipos" class="text-sm font-medium text-gray-700 whitespace-nowrap">Prioridad:</label>
                        <select id="filtroTipos" class="form-select text-sm w-36 pl-3 pr-8 py-1.5 border border-gray-300 rounded-lg focus:ring-primary">
                            <option value="TODOS" selected>Todos ({{ $todos }})</option>
                            <option value="PRINCIPAL">Principal ({{ $principal }})</option>
                            <option value="ADICIONAL">Adicional ({{ $adicional }})</option>
                        </select>
                    </div>

                    {{-- Clasificación --}}
                    <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                        <label for="filtroClasificacion" class="text-sm font-medium text-gray-700 whitespace-nowrap">Tipos:</label>
                        <select id="filtroClasificacion" class="form-select text-sm w-44 pl-3 pr-8 py-1.5 border border-gray-300 rounded-lg focus:ring-primary">
                            <option value="TODOS" selected>Todos</option>
                            <option value="DOCUMENTO">Documento ({{ $documento }})</option>
                            <option value="FORMATO">Formato ({{ $formato }})</option>
                            <option value="CERTIFICADO">Certificado ({{ $certificado }})</option>
                        </select>
                    </div>

                    {{-- Caduca --}}
                    <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                        <label for="vencimientoFiltro" class="text-sm font-medium text-gray-700 whitespace-nowrap">Caduca:</label>
                        <select id="vencimientoFiltro" class="form-select text-sm w-36 pl-3 pr-8 py-1.5 border border-gray-300 rounded-lg focus:ring-primary">
                            <option value="TODOS" selected>Todos</option>
                            <option value="SI">Sí</option>
                            <option value="NO">No</option>
                        </select>
                    </div>
                </div>

                {{-- Filtro de Estado --}}
                <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4 mt-2 lg:mt-0">
                    <label for="filtroEstado" class="text-sm font-medium text-gray-700 whitespace-nowrap">Estado:</label>
                    <select id="filtroEstado" class="form-select text-sm w-32 pl-3 pr-8 py-1.5 border border-gray-300 rounded-lg focus:ring-primary">
                        <option value="TODOS" selected>Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
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

    {{-- ── MODAL: Formulario de Gestión ────────────────────────── --}}
    <button type="button" class="hidden" id="btn-modal-gestion" data-hs-overlay="#modal-gestion-folio"></button>

    <div id="modal-gestion-folio"
         class="hs-overlay hidden fixed inset-0 z-[80] overflow-y-auto transition-all duration-500 pointer-events-none">
        <div class="hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100
                    translate-y-10 opacity-0 ease-in-out transition-all duration-500
                    sm:max-w-xl w-full my-8 sm:mx-auto flex flex-col bg-white shadow-sm rounded-lg pointer-events-auto
                    border border-gray-200">

            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 bg-slate-50 rounded-t-lg">
                <div class="flex items-center gap-3">
                    <h3 class="text-base font-bold text-gray-900">Gestión de Folios</h3>
                    <span id="txtMensajeNuevo"
                          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                        Nuevo registro
                    </span>
                </div>
                <button type="button" id="btn-modal-gestion-close"
                        class="text-gray-500 hover:text-gray-700 transition-colors"
                        data-hs-overlay="#modal-gestion-folio">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <div class="px-6 py-5">
                <form id="formSaveFolio">
                    <div x-data="{ nameFolio: '', tipoSeleccionado: '' }" class="space-y-4">

                        {{-- Nombre + Tipo --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                  <label for="nombre" class="block text-sm font-bold text-gray-700 mb-1">Nombre</label>
                                  <input type="text" id="nombre" class="form-input w-full px-3 py-2 border-gray-300 rounded-md focus:border-primary focus:ring-primary"
                                         x-model="nameFolio"
                                         @input="nameFolio = nameFolio.toUpperCase()"
                                         placeholder="Nombre del folio" required>
                                  <div id="avisoNombreRepetidoFolio" class="hidden mt-1 flex items-center gap-2 px-3 py-2 rounded-md bg-yellow-50 border border-yellow-300 text-yellow-800 text-xs">
                                      <i class="fa-solid fa-triangle-exclamation"></i>
                                      <span>Ya existe un folio con este nombre.</span>
                                  </div>
                              </div>
                            <div>
                                <label for="tipo" class="block text-sm font-bold text-gray-700 mb-1">Tipo</label>
                                <select id="tipo" class="form-select w-full px-3 py-2 border-gray-300 rounded-md focus:border-primary focus:ring-primary" x-model="tipoSeleccionado" required>
                                    <option value="" disabled selected>— Seleccionar —</option>
                                    <option value="1">DOCUMENTO</option>
                                    <option value="2">FORMATO</option>
                                    <option value="3">CERTIFICADO</option>
                                </select>
                            </div>
                        </div>

                        {{-- Responsable y Categoría --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="responsable" class="block text-sm font-bold text-gray-700 mb-1">Responsable</label>
                                <select id="responsable" class="form-select w-full px-3 py-2 border-gray-300 rounded-md focus:border-primary focus:ring-primary" required>
                                    <option value="" disabled selected>— Seleccionar —</option>
                                    @foreach ($roles as $rol)
                                        <option value="{{ $rol->codigo }}">{{ $rol->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="categoria" class="block text-sm font-bold text-gray-700 mb-1">Categoría</label>
                                <select id="categoria" class="form-select w-full px-3 py-2 border-gray-300 rounded-md focus:border-primary focus:ring-primary" required>
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
                                  class="block w-full px-3 py-2 rounded-md text-xs font-medium bg-yellow-100 text-yellow-800">
                                <strong>Formato:</strong> Folio emitido por la empresa.
                            </span>
                            <span x-show="tipoSeleccionado === '3'"
                                  class="block w-full px-3 py-2 rounded-md text-xs font-medium bg-blue-100 text-blue-800">
                                <strong>Certificado:</strong> Folio emitido por una entidad educativa.
                            </span>
                        </div>

                        {{-- Tipo de folio: Principal / Adicional --}}
                        <div class="flex items-center gap-4 mt-2">
                            <span class="text-sm font-bold text-gray-700 whitespace-nowrap">Prioridad:</span>
                            <div class="flex items-center gap-5">
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" class="form-radio text-primary" id="radioPrin" name="tipo_folio" value="PRINCIPAL" checked>
                                    <span class="text-sm font-medium">Principal</span>
                                </label>
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" class="form-radio text-primary" id="radioAdi" name="tipo_folio" value="ADICIONAL">
                                    <span class="text-sm font-medium">Adicional</span>
                                </label>
                            </div>
                        </div>
                        {{-- Vencimiento + Periodo --}}
                        <div class="flex items-center gap-4 mt-2">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="switchVencimiento" class="form-switch text-danger cursor-pointer">
                                <label for="switchVencimiento" class="text-sm font-bold cursor-pointer">Caducidad</label>
                            </div>
                            <div id="periodoDiv" class="hidden flex items-center gap-2">
                                <label for="periodo" class="text-sm font-medium text-gray-700 whitespace-nowrap">Periodo:</label>
                                <select id="periodo" class="form-select text-sm w-44 pl-3 pr-8 py-1.5 border-gray-300 rounded-md">
                                    <option disabled selected>— Seleccionar —</option>
                                    @foreach ($periodos as $periodo)
                                        <option value="{{ $periodo->codigo }}">{{ $periodo->descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Institución (oculto por defecto) --}}
                        <div id="institucionDiv" class="hidden flex items-center gap-5 mt-2">
                            <span class="text-sm font-bold text-gray-700">Plataforma:</span>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" class="form-radio text-danger" id="radioICMA" name="institucion" value="ICMA">
                                <span class="text-sm font-medium">ICMA</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" class="form-radio text-danger" id="radioAV" name="institucion" value="AV">
                                <span class="text-sm font-medium">AV</span>
                            </label>
                        </div>

                    </div>

                    <input type="hidden" name="codFolio" id="codFolio">

                    {{-- Botones --}}
                    <div class="flex justify-end gap-3 mt-8 border-t border-gray-200 pt-4">
                        <button type="button" class="btn bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg px-4 py-2" data-hs-overlay="#modal-gestion-folio">
                            Cancelar
                        </button>
                        <button type="submit" id="submitButton"
                                class="btn bg-success text-white hover:bg-green-600 rounded-lg px-4 py-2 flex items-center gap-2">
                            Guardar <i class="fa-solid fa-floppy-disk"></i>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

</div>

@endsection

@vite(['resources/js/functions/folios.js'])