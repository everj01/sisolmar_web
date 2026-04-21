@extends('layouts.vertical', ['title' => 'Gestión de Folios'])

@section('content')

@include('layouts.shared/page-title', ['subtitle' => 'Recursos Humanos', 'title' => 'Folios'])

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">

    {{-- ── CARD IZQUIERDA: Listado de Folios ───────────────────────────── --}}
    <div class="card overflow-hidden">
        <div class="card-header">
            <h4 class="card-title">Listado de Folios</h4>
        </div>

        <div class="px-5 py-4 space-y-4">

            {{-- Filtros de tipo --}}
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2">

                {{-- Grupo 1: clasificación general --}}
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" class="form-radio text-primary" id="radioTod" name="folioFiltro" value="TODOS" checked>
                        <span class="text-sm">Todos ({{ $todos }})</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" class="form-radio text-primary" id="radioPri" name="folioFiltro" value="PRINCIPAL">
                        <span class="text-sm">Principal ({{ $principal }})</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" class="form-radio text-primary" id="radioAdic" name="folioFiltro" value="ADICIONAL">
                        <span class="text-sm">Adicional ({{ $adicional }})</span>
                    </label>
                </div>

                <div class="w-px h-5 bg-gray-200 hidden sm:block"></div>

                {{-- Grupo 2: subtipo --}}
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" class="form-radio text-danger" id="radioDoc" name="folioFiltro" value="DOCUMENTO">
                        <span class="text-sm">Documento ({{ $documento }})</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" class="form-radio text-danger" id="radioForm" name="folioFiltro" value="FORMATO">
                        <span class="text-sm">Formato ({{ $formato }})</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" class="form-radio text-danger" id="radioCert" name="folioFiltro" value="CERTIFICADO">
                        <span class="text-sm">Certificado ({{ $certificado }})</span>
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
                    <label for="chkEliminados" class="text-sm cursor-pointer">Solo activos</label>
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
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-sm text-gray-600">registros</span>
            </div>

        </div>
    </div>

    {{-- ── CARD DERECHA: Formulario de Gestión ────────────────────────── --}}
    <div class="card">
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

                    {{-- Responsable --}}
                    <div>
                        <label for="responsable" class="block text-sm font-medium text-gray-700 mb-1">Responsable</label>
                        <select id="responsable" class="form-select w-full" required>
                            <option value="" disabled selected>— Seleccionar —</option>
                            @foreach ($roles as $rol)
                                <option value="{{ $rol->codigo }}">{{ $rol->nombre }}</option>
                            @endforeach
                        </select>
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
                    <button type="button" id="cancelButton"
                            class="btn rounded-full bg-danger/25 text-danger hover:bg-danger hover:text-white">
                        Cancelar <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>

@endsection

@vite(['resources/js/functions/folios.js'])