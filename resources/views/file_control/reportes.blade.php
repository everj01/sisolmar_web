@extends('layouts.vertical', ['title' => 'Legajos PDF'])

@section('css')
@endsection

@section('content')

    @include("layouts.shared/page-title", ["subtitle" => "Recursos Humanos", "title" => "Reportes"])

    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6 pt-8">

        <!-- Folios Vigentes -->
        <div class="card custom-card h-full">
            <div class="p-4 md:p-5 flex flex-col justify-between h-full">
                <h3 class="text-lg font-bold text-default-800 flex items-center gap-2">
                    <i class='bx bxs-file-find text-primary text-2xl'></i>
                    Folios vigentes
                </h3>
                <p class="mt-2 text-default-500">Genera el reporte de folios vigentes en el sistema con
                    su clasificación.</p>
                <button type="button" id="btnReporteFoliosVigentes" class="mt-3 inline-flex items-center
       gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary
      hover:text-primary-800">
                    Generar <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
                </button>
            </div>
        </div>

        <!-- Folios Pendientes -->
        <div class="card custom-card h-full">
            <div class="p-4 md:p-5 flex flex-col justify-between h-full">
                <h3 class="text-lg font-bold text-default-800 flex items-center gap-2">
                    <i class='bx bx-hourglass text-warning text-2xl'></i>
                    Folios pendientes
                </h3>
                <p class="mt-2 text-default-500">Genera el reporte de folios pendientes por
                    sucursal.</p>
                <button type="button" id="btnReporteFoliosPendientesSucursal" class="mt-3 inline-flex
      items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary
      hover:text-primary-800">
                    Generar <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
                </button>
            </div>
        </div>

        <!-- Folios Por Vencer -->
        <div class="card custom-card h-full">
            <div class="p-4 md:p-5 flex flex-col justify-between h-full">
                <h3 class="text-lg font-bold text-default-800 flex items-center gap-2">
                    <i class='bx bxs-bell-ring text-danger text-2xl'></i>
                    Folios Por Vencer
                </h3>
                <p class="mt-2 text-default-500">Genera el reporte de folios Por Vencer.</p>
                <button type="button" id="btnReporteFoliosPorVencer" class="mt-3 inline-flex
      items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary
      hover:text-primary-800">
                    Generar <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
                </button>
            </div>
        </div>

        <!-- Folios Pendientes de Escaneo -->
        <div class="card custom-card h-full">
            <div class="p-4 md:p-5 flex flex-col justify-between h-full">
                <h3 class="text-lg font-bold text-default-800 flex items-center gap-2">
                    <i class='bx bx-scan text-info text-2xl'></i>
                    Folios Pendientes de Escaneo
                </h3>
                <p class="mt-2 text-default-500">Genera el reporte de folios que aún no han sido
                    escaneados.</p>
                <button type="button" id="btnReporteFoliosPendientesEscaneo" class="mt-3 inline-flex
      items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary
      hover:text-primary-800">
                    Generar <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
                </button>
            </div>
        </div>

        <!-- Folios Pendientes de Registro -->
        <div class="card custom-card h-full">
            <div class="p-4 md:p-5 flex flex-col justify-between h-full">
                <h3 class="text-lg font-bold text-default-800 flex items-center gap-2">
                    <i class='bx bx-list-ul text-secondary text-2xl'></i>
                    Folios Pendientes de Registro
                </h3>
                <p class="mt-2 text-default-500">Genera el reporte de folios que aún no han sido
                    registrados en el sistema.</p>
                <button type="button" id="btnReporteFoliosPendientesRegistro" class="mt-3 inline-flex
      items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary
      hover:text-primary-800">
                    Generar <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
                </button>
            </div>
        </div>

        <!-- Vigencia de Documentos -->
        <div class="card custom-card h-full">
            <div class="p-4 md:p-5 flex flex-col justify-between h-full">
                <h3 class="text-lg font-bold text-default-800 flex items-center gap-2">
                    <i class='bx bx-time-five text-success text-2xl'></i>
                    Vigencia de Documentos
                </h3>
                <p class="mt-2 text-default-500">Genera el reporte de documentos según su estado de
                    vigencia.</p>
                <button type="button" id="btnReporteVigenciaDocumentos" class="mt-3 inline-flex
      items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary
      hover:text-primary-800">
                    Generar <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
                </button>
            </div>
        </div>

        <!-- Carnet -->
        <div class="card custom-card h-full">
            <div class="p-4 md:p-5 flex flex-col justify-between h-full">
                <h3 class="text-lg font-bold text-default-800 flex items-center gap-2">
                    <i class='bx bx-id-card text-primary text-2xl'></i>
                    Carnet
                </h3>
                <p class="mt-2 text-default-500">Genera el reporte de carnets del personal.</p>
                <button type="button" id="btnReporteCarnet" class="mt-3 inline-flex items-center gap-x-1
       text-sm font-semibold rounded-lg border border-transparent text-primary hover:text-primary-800">
                    Generar <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
                </button>
            </div>
        </div>

        <!-- Certificados -->
        <div class="card custom-card h-full">
            <div class="p-4 md:p-5 flex flex-col justify-between h-full">
                <h3 class="text-lg font-bold text-default-800 flex items-center gap-2">
                    <i class='bx bxs-award text-warning text-2xl'></i>
                    Certificados
                </h3>
                <p class="mt-2 text-default-500">Genera el reporte de certificados registrados en el
                    sistema.</p>
                <button type="button" id="btnReporteCertificados" class="mt-3 inline-flex items-center
      gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary
      hover:text-primary-800">
                    Generar <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
                </button>
            </div>
        </div>

        <!-- Constancias de Entrega -->
        <div class="card custom-card h-full">
            <div class="p-4 md:p-5 flex flex-col justify-between h-full">
                <h3 class="text-lg font-bold text-default-800 flex items-center gap-2">
                    <i class='bx bx-clipboard text-info text-2xl'></i>
                    Constancias de Entrega
                </h3>
                <p class="mt-2 text-default-500">Genera el reporte de constancias de entrega de
                    documentos al personal.</p>
                <button type="button" id="btnReporteConstanciasEntrega" class="mt-3 inline-flex
      items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary
      hover:text-primary-800">
                    Generar <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
                </button>
            </div>
        </div>

    </div>


    <!-- ====== MODAL: Folios Vigentes ====== -->
    <div id="modalFoliosVigentes" class="fixed inset-0 z-50 hidden items-center justify-center
      bg-black/50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4">
            <div class="flex items-center justify-between p-5 border-b border-default-200">
                <h5 class="text-base font-semibold text-default-800 flex items-center gap-2">
                    <i class='bx bxs-file-find text-primary text-xl'></i>
                    Filtros – Folios Vigentes
                </h5>
                <button class="btnCerrarModal text-default-400 hover:text-default-600">
                    <i class='bx bx-x text-2xl'></i>
                </button>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-default-800 text-sm font-medium inline-block mb-2">Tipo de
                            folio</label>
                        <select id="filtroTipoFolio" class="form-select">
                            <option value="">Todos</option>
                            <option value="DOCUMENTO">Documento</option>
                            <option value="FORMATO">Formato</option>
                            <option value="CERTIFICADO">Certificado</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-default-800 text-sm font-medium inline-block
      mb-2">Prioridad</label>
                        <select id="filtroPrioridad" class="form-select">
                            <option value="">Todas</option>
                            <option value="PRINCIPAL">Principal</option>
                            <option value="ADICIONAL">Adicional</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button class="btnCerrarModal px-4 py-2 rounded-lg border border-default-300
      text-default-600 hover:bg-default-100 text-sm">Cancelar</button>
                    <button id="btnGenerarPdfFoliosVigentes" class="bg-danger text-white px-6 py-2
      rounded-lg shadow hover:bg-danger/90 text-sm">
                        <i class="fa-solid fa-file-pdf"></i> Generar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== MODAL: Folios Pendientes ====== -->
    <div id="modalFoliosPendientesSucursal" class="fixed inset-0 z-50 hidden items-center justify-center
       bg-black/50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4">
            <div class="flex items-center justify-between p-5 border-b border-default-200">
                <h5 class="text-base font-semibold text-default-800 flex items-center gap-2">
                    <i class='bx bx-hourglass text-warning text-xl'></i>
                    Filtros – Folios Pendientes
                </h5>
                <button class="btnCerrarModal text-default-400 hover:text-default-600">
                    <i class='bx bx-x text-2xl'></i>
                </button>
            </div>
            <div class="p-6">
                <label for="sucursal" class="text-default-800 text-sm font-medium mb-2
      block">Sucursal</label>
                <select id="sucursal" class="form-select">
                    <option disabled selected>-Seleccionar-</option>
                    @foreach($sucursales as $sucursal)
                        <option value="{{ $sucursal->codigo }}">{{ $sucursal->abreviatura }}</option>
                    @endforeach
                </select>
                <div class="mt-6 flex justify-end gap-3">
                    <button class="btnCerrarModal px-4 py-2 rounded-lg border border-default-300
      text-default-600 hover:bg-default-100 text-sm">Cancelar</button>
                    <button id="btnGenerarPdfFoliosPendientesSucursal" class="bg-danger text-white px-6
      py-2 rounded-lg shadow hover:bg-danger/90 text-sm">
                        <i class="fa-solid fa-file-pdf"></i> Generar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== MODAL: Folios Por Vencer ====== -->
    <div id="modalFoliosPorVencer" class="fixed inset-0 z-50 hidden items-center justify-center
      bg-black/50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4">
            <div class="flex items-center justify-between p-5 border-b border-default-200">
                <h5 class="text-base font-semibold text-default-800 flex items-center gap-2">
                    <i class='bx bxs-bell-ring text-danger text-xl'></i>
                    Filtros – Folios Por Vencer
                </h5>
                <button class="btnCerrarModal text-default-400 hover:text-default-600">
                    <i class='bx bx-x text-2xl'></i>
                </button>
            </div>
            <div class="p-6">
                <label class="text-default-800 text-sm font-medium inline-block mb-3">Filtrar
                    por</label>
                <div class="flex gap-6 mb-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="tipoFiltro" value="sucursal" class="form-radio">
                        <span>Sucursal</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="tipoFiltro" value="cliente" class="form-radio">
                        <span>Cliente</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="tipoFiltro" value="servicio" class="form-radio">
                        <span>Código de servicio</span>
                    </label>
                </div>
                <div class="grid grid-cols-1 gap-4 mb-6">
                    <div id="filtroSucursalDiv" class="hidden">
                        <label class="text-default-800 text-sm font-medium mb-2 block">Sucursal</label>
                        <select id="filtroSucursalSelect" class="form-select">
                            <option value="">-Seleccionar-</option>
                            @foreach($sucursales as $sucursal)
                                                        <option value="{{ $sucursal->codigo }}">{{ $sucursal->abreviatura
                                  }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="filtroClienteDiv" class="hidden">
                        <label class="text-default-800 text-sm font-medium mb-2 block">Cliente</label>
                        <select id="filtroClienteSelect" class="form-select">
                            <option value="">-Seleccionar-</option>
                            @foreach($clientes as $cliente)
                                                        <option value="{{ $cliente->codigo }}">{{ $cliente->abreviatura
                                  }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="filtroCodigoDiv" class="hidden">
                        <label class="text-default-800 text-sm font-medium mb-2 block">Codigo</label>
                        <select id="filtroCodigoSelect" class="form-select">
                            <option value="">-Seleccionar-</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <button class="btnCerrarModal px-4 py-2 rounded-lg border border-default-300
      text-default-600 hover:bg-default-100 text-sm">Cancelar</button>
                    <button id="btnGenerarPdfFoliosPorVencer" class="bg-danger text-white px-6 py-2
      rounded-lg shadow hover:bg-danger/90 text-sm">
                        <i class="fa-solid fa-file-pdf"></i> Generar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@vite(['resources/js/functions/reportes.js'])
@section('script')
@endsection