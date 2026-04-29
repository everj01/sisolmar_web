@extends('layouts.vertical', ['title' => 'Carga de archivos'])

@section('content')

@include('layouts.shared/page-title', ['subtitle' => 'Recursos Humanos', 'title' => 'File Control'])

{{-- =====================================================================
     PANEL PRINCIPAL: Listado de personal + Folios / Legajos
     ===================================================================== --}}
<div class="grid lg:grid-cols-[3.1fr_2.05fr] gap-6 mt-8">

    {{-- ── CARD: Listado de Personal ────────────────────────────────────── --}}
    <div class="card overflow-hidden">
        <div class="card-header">
            <div class="flex flex-row items-center justify-between w-full">
                <h4 class="card-title">Listado de Personal</h4>
                @if ($tipoUsuario == 5 || $tipoUsuario == 2)
                    <button type="button"
                            class="btn bg-info text-white"
                            data-accion="abrir-reporte-avances">
                        Reporte de avances
                    </button>
                @endif
            </div>
            
        </div>

        <div class="px-5 pt-4 pb-2 space-y-3">

            {{-- Búsqueda + Sucursal --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <input
                    type="text"
                    id="buscarPersonal"
                    placeholder="Buscar..."
                    autocomplete="off"
                    class="w-40 px-3 py-1.5 text-sm uppercase border border-gray-300 rounded-full
                           focus:outline-none focus:border-blue-500 transition-colors"
                />
                <div class="flex items-center gap-2">
                    <label for="sucursal" class="text-sm font-medium text-gray-700 whitespace-nowrap">
                        Sucursal
                    </label>
                    <select id="sucursal" class="form-select text-sm">
                        <option disabled selected>— Seleccionar —</option>
                        @foreach ($sucursales as $sucursal)
                            <option value="{{ $sucursal->codigo }}">{{ $sucursal->abreviatura }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <label for="filtroDJ" class="text-sm font-medium text-gray-700 whitespace-nowrap">
                        DJ actualizada
                    </label>
                    <select id="filtroDJ" class="form-select text-sm" name="filtroDJ">
                        <option value="TODOS" selected>Todos</option>
                        <option value="SI">Sí</option>
                        <option value="NO">No</option>
                    </select>
                </div>
            </div>

            {{-- Filtros: Tipo de personal + Vigencia en una sola fila --}}
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 py-1">

                {{-- Tipo de personal --}}
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tipo</span>

                    @if ($tipoPerLimitar != 1 && $tipoPerLimitar != 2 && $tipoPerLimitar != 3 && $tipoPerLimitar == 0)
                          <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" class="form-radio text-primary" id="radioTodos" name="tipo_per" value="TODOS" checked>
                        <span class="text-sm">Todos</span>
                    </label>
                    @endif
                  

                    @if ($tipoPerLimitar == 0 || $tipoPerLimitar == 1)
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" class="form-radio text-primary" id="radioAdmin" name="tipo_per" value="ADMIN">
                            <span class="text-sm">Administrativo</span>
                        </label>
                    @endif

                    @if ($tipoPerLimitar == 0 || $tipoPerLimitar == 2)
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" class="form-radio text-primary" id="radioOper" name="tipo_per" value="OPER" checked>
                            <span class="text-sm">Operativo</span>
                        </label>
                    @endif

                    @if (/*$tipoPerLimitar == 0 ||*/ $tipoPerLimitar == 3 )
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" class="form-radio text-primary" id="radioEsp" name="tipo_per" value="ESPECIAL">
                            <span class="text-sm">Especial</span>
                        </label>
                    @endif
                </div>
                 <br>
                {{-- Separador vertical --}}
                <div class="hidden sm:block w-px h-5 bg-gray-200"></div>
                <br>
                {{-- Vigencia --}}
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Vigencia</span>

                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" class="form-radio text-primary" id="radioTodosV" name="vigencia" value="" checked>
                        <span class="text-sm">Todos</span>
                    </label>

                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" class="form-radio text-primary" id="radiooSi" name="vigencia" value="SI">
                        <span class="text-sm">Sí</span>
                    </label>

                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" class="form-radio text-primary" id="radioNo" name="vigencia" value="NO">
                        <span class="text-sm">No</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Tabla de personas --}}
        <div class="px-5 pb-5">
            <div id="tblPersonas" class="w-full"></div>

            {{-- Footer: selector de registros + info + paginación --}}
            <div class="flex flex-wrap items-center justify-between gap-3 mt-3">
                <div class="flex items-center gap-2">
                    <label for="page-size-personas" class="text-sm text-gray-600">Mostrar</label>
                    <select id="page-size-personas" class="form-select text-sm w-20">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                    <span class="text-sm text-gray-600">registros</span>
                    <div id="tablaInfo" class="text-sm text-gray-500 pl-2 border-l border-gray-200">
                        Cargando...
                    </div>
                </div>
                <div id="tablaPaginacion"></div>
            </div>
        </div>
    </div>

    {{-- ── CARD: Folios del personal ────────────────────────────────────── --}}
    <div id="dataDocs" class="card hidden">
        <div class="card-header flex items-center justify-between">
            <h4 class="card-title nombrePersDocs">Folios de</h4>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" class="form-radio text-primary" id="radioPrin" name="tipo_folio" value="PRINCIPAL" checked>
                    <span class="text-sm">Principal</span>
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" class="form-radio text-primary" id="radioAux" name="tipo_folio" value="ADICIONAL">
                    <span class="text-sm">Adicional</span>
                </label>
            </div>
        </div>

        <div class="px-5 py-4 space-y-3">
            <input
                type="text"
                id="buscarFolio"
                placeholder="Buscar..."
                autocomplete="off"
                class="w-40 px-3 py-1.5 text-sm uppercase border border-gray-300 rounded-full
                       focus:outline-none focus:border-blue-500 transition-colors"
            />
            <div id="tblDocs" class="w-full"></div>
        </div>
    </div>

    {{-- ── CARD: Legajos del personal ───────────────────────────────────── --}}
    <div id="dataDocsLeg" class="card hidden">
        <div class="card-header">
            <h4 class="card-title nombrePersLeg">Legajos para</h4>
        </div>

        <div class="px-5 py-4 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                    <select id="clientes" class="form-select w-full">
                        <option disabled selected>— Seleccionar —</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->codigo }}">{{ $cliente->abreviatura }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cargo</label>
                    <select id="cargos" class="form-select w-full">
                        <option disabled selected>— Seleccionar —</option>
                    </select>
                </div>
            </div>

            <input type="hidden" name="codPersonal" id="codPersonal">
            <div id="tblDocsLegajo" class="w-full hidden"></div>
        </div>
    </div>

</div>


{{-- =====================================================================
     MODALES
     ===================================================================== --}}

{{-- ── Trigger oculto: Visor de documentos ────────────────────────────── --}}
<button type="button" class="hidden" id="btn-modal-view-docs" data-hs-overlay="#modal-view-docs"></button>

{{-- Modal: Visor de documentos --}}
<div id="modal-view-docs"
     class="hs-overlay hidden fixed inset-0 z-70 overflow-y-auto transition-all duration-500 pointer-events-none">
    <div class="hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100
                translate-y-10 opacity-0 ease-in-out transition-all duration-500
                max-w-3xl w-full my-8 mx-auto flex flex-col bg-white shadow-sm rounded-lg pointer-events-auto
                border border-gray-200">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200">
            <h3 class="text-base font-medium text-gray-900 modal-title">Visor de documento</h3>
            <button type="button" id="btn-modal-view-docs-close"
                    class="text-gray-500 hover:text-gray-700 transition-colors"
                    data-hs-overlay="#modal-view-docs">
                <i class="i-tabler-x text-lg"></i>
            </button>
        </div>

        {{-- Nombre del doc seleccionado --}}
        <p id="txtDocSelec" class="text-center text-sm font-medium text-gray-700 pt-4 px-5"></p>

        {{-- Contenido del visor --}}
        <div id="visorDocs" class="flex flex-col gap-3 px-5 py-4"></div>
    </div>
</div>


{{-- ── Trigger oculto: Formulario de folios ────────────────────────────── --}}
<button type="button" class="hidden" id="btn-modal-docs" data-hs-overlay="#modal-file"></button>

{{-- Modal: Carga de folio PDF --}}
<div id="modal-file"
     class="hs-overlay hidden fixed inset-0 z-70 overflow-y-auto transition-all duration-500 pointer-events-none">
    <div class="hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100
                translate-y-10 opacity-0 ease-in-out transition-all duration-500
                sm:max-w-lg w-full my-8 sm:mx-auto flex flex-col bg-white shadow-sm rounded-lg pointer-events-auto
                border border-gray-200">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200">
            <h3 class="text-base font-medium text-gray-900 modal-title">Cargar documento</h3>
            <button type="button" id="btn-modal-docs-close"
                    class="text-gray-500 hover:text-gray-700 transition-colors"
                    data-hs-overlay="#modal-file">
                <i class="i-tabler-x text-lg"></i>
            </button>
        </div>

        {{-- Formulario --}}
        <form id="formFolioPersonal">
            @csrf
            <input type="hidden" id="codFolioActual" value="">

            <input type="hidden" id="codFolio" value="">
            <input type="hidden" id="meses" value="">
            <input type="hidden" id="cantArchivos" value="">
            <span id="txtPeriodo" class="hidden"></span>
            <span id="txtCantHojas" class="hidden"></span>

            {{-- Caducidad — solo visible para folios normales --}}
            

            <div class="px-5 py-4 space-y-5">

                
                
                <p class="text-sm text-center text-gray-600" id="aviso-tipo-archivo">
                    Solo se aceptan archivos <strong>PDF</strong> con un peso máximo de <strong>1 MB</strong>.
                </p>

                {{-- Fecha de emisión --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Emisión</label>
                    <input type="date" id="fecha_emision" required class="form-input w-full">
                </div>

                <div id="divCaducidad" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Caducidad</label>
                    <input type="date" id="fecha_caducidad" class="form-input w-full">
                </div>

                {{-- Selector de archivo --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Archivo PDF</label>

                    <div id="btnSeleccionar" role="button"
                         class="cursor-pointer border-2 border-dashed border-gray-300 rounded-xl p-8
                                flex flex-col items-center justify-center gap-3
                                hover:border-blue-400 hover:bg-blue-50 transition-colors">

                        <span class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-gray-100 text-gray-700">
                            <i class="i-tabler-file-type-pdf text-2xl"></i>
                        </span>

                        <div class="text-center text-sm text-gray-600">
                            <span class="font-medium text-gray-800">Haz click aqui </span>
                            <span class="font-semibold text-blue-600 hover:underline">SELECCIONAR</span>
                        </div>

                        <p class="text-xs text-gray-400">Máximo 1 MB</p>

                        <input type="file" id="archivoInput" accept=".pdf" class="hidden">
                    </div>

                    <ul id="listaArchivos" class="mt-3 space-y-2"></ul>
                </div>

            </div>

            {{-- Footer del modal --}}
            <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-gray-200">
                <button type="submit" class="btn bg-primary text-white"  id="btn-guardar-folio">
                    <i class="i-tabler-check me-1"></i> Guardar
                </button>
                <button type="button" class="btn bg-gray-100 text-gray-700 hover:bg-gray-200"
                        data-hs-overlay="#modal-file">
                    <i class="i-tabler-x me-1"></i> Cerrar
                </button>
            </div>
        </form>
    </div>
</div>


{{-- =====================================================================
     SECCIÓN DE COINCIDENCIAS (oculta por defecto)
     ===================================================================== --}}
<div id="divCoincidencias" class="grid lg:grid-cols-1 gap-6 mt-8 hidden">
    <div class="card overflow-hidden">
        <div class="card-header">
            <h4 class="card-title">Listado de Coincidencias</h4>
        </div>
        <div class="px-5 py-4 space-y-4">
            <input
                type="text"
                id="buscar"
                placeholder="Buscar..."
                class="w-40 px-3 py-1.5 text-sm border border-gray-300 rounded-full
                       focus:outline-none focus:border-blue-500 transition-colors"
            />
            <div id="tblPersonasCN" class="w-full"></div>
        </div>
    </div>
</div>

<button id="btn-modal-biometrico" data-hs-overlay="#modal-biometrico" class="hidden"></button>

@include('file_control.rrhh.partials_modal_comparacion_huellafirma_dni')

@endsection


@section('script')

<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://unpkg.com/exceljs@4.4.0/dist/exceljs.min.js"></script>

@endsection

@vite(['resources/js/functions/chargeFile.js'])

@vite(['resources/js/functions/chargefile/reporteAvances.js'])
