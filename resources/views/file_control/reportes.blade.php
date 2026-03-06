@extends('layouts.vertical', ['title' => 'Legajos PDF'])

@section('css')
@endsection

@section('content')

@include("layouts.shared/page-title", ["subtitle" => "Recursos Humanos", "title" => "Reportes"])

<link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">

    <!-- Folios Vigentes -->
    <div class="card custom-card h-full">
        <div class="p-4 md:p-5 flex flex-col justify-between h-full">
            <h3 class="text-lg font-bold text-default-800">Folios vigentes</h3>
            <p class="mt-2 text-default-500">
                Genera el reporte de folios vigentes en el sistema con su clasificación.
            </p>
            <button type="button" id="btnReporteFoliosVigentes" class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary hover:text-primary-800">
                Generar <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
            </button>
        </div>
    </div>

    <!-- Folios Pendientes -->
    <div class="card custom-card h-full">
        <div class="p-4 md:p-5 flex flex-col justify-between h-full">
            <h3 class="text-lg font-bold text-default-800">Folios pendientes</h3>
            <p class="mt-2 text-default-500">Genera el reporte de folios pendientes por sucursal.</p>
            <button type="button" id="btnReporteFoliosPendientesSucursal" class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary hover:text-primary-800">
                Generar <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
            </button>
        </div>
    </div>

    <!-- Folios Por Vencer -->
    <div class="card custom-card h-full">
        <div class="p-4 md:p-5 flex flex-col justify-between h-full">
            <h3 class="text-lg font-bold text-default-800">Folios Por Vencer</h3>
            <p class="mt-2 text-default-500">Genera el reporte de folios Por Vencer.</p>
            <button type="button" id="btnReporteFoliosPorVencer" class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary hover:text-primary-800">
                Generar <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
            </button>
        </div>
    </div>

</div>

<div class="grid lg:grid-cols-1 gap-6 mt-8">

    <!-- Filtros Folios Vigentes -->
    <div class="card overflow-hidden hidden" id="filtrosFoliosVigentes">
        <div class="card-header">
            <h4 class="card-title">Filtros – Folios Vigentes</h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-default-800 text-sm font-medium inline-block mb-2">Tipo de folio</label>
                    <select id="filtroTipoFolio" class="form-select">
                        <option value="">Todos</option>
                        <option value="DOCUMENTO">Documento</option>
                        <option value="FORMATO">Formato</option>
                        <option value="CERTIFICADO">Certificado</option>
                    </select>
                </div>
                <div>
                    <label class="text-default-800 text-sm font-medium inline-block mb-2">Prioridad</label>
                    <select id="filtroPrioridad" class="form-select">
                        <option value="">Todas</option>
                        <option value="PRINCIPAL">Principal</option>
                        <option value="ADICIONAL">Adicional</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button id="btnGenerarPdfFoliosVigentes" class="bg-danger text-white px-6 py-2 rounded-lg shadow hover:bg-danger/90">
                    <i class="fa-solid fa-file-pdf"></i> Generar PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Filtros Folios Pendientes -->
    <div class="card overflow-hidden hidden" id="filtrosFoliosPendientesSucursal">
        <div class="card-header">
            <h4 class="card-title">Filtros – Folios Pendientes</h4>
        </div>
        <div class="p-6">
            <label for="sucursal" class="text-default-800 text-sm font-medium mb-2">Sucursal</label>
            <select id="sucursal" class="form-select">
                <option disabled selected>-Seleccionar-</option>
                @foreach($sucursales as $sucursal)
                    <option value="{{ $sucursal->codigo }}">{{ $sucursal->abreviatura }}</option>
                @endforeach
            </select>
            <div class="mt-6 flex justify-end">
                <button id="btnGenerarPdfFoliosPendientesSucursal" class="bg-danger text-white px-6 py-2 rounded-lg shadow hover:bg-danger/90">
                    <i class="fa-solid fa-file-pdf"></i> Generar PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Filtros Folios Por Vencer -->
<div class="card overflow-visible hidden" id="filtrosFoliosPorVencer">        <div class="card-header">
            <h4 class="card-title">Filtros – Folios Por Vencer</h4>
        </div>
        <div class="p-6">
            <label class="text-default-800 text-sm font-medium inline-block mb-3">Filtrar por</label>
            <div class="flex gap-6 mb-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="tipoFiltro" value="sucursal" class="form-radio"> <span>Sucursal</span>
                </label>
                 <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="tipoFiltro" value="cliente" class="form-radio"> <span>Cliente</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="tipoFiltro" value="servicio" class="form-radio"> <span>Código de servicio</span>
                </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div id="filtroSucursalDiv" class="hidden">
                    <label class="text-default-800 text-sm font-medium mb-2">Sucursal</label>
                    <select id="filtroSucursalSelect" class="form-select">
                        <option value="">-Seleccionar-</option>
                        @foreach($sucursales as $sucursal)
                            <option value="{{ $sucursal->codigo }}">{{ $sucursal->abreviatura }}</option>
                        @endforeach
                    </select>
                </div>

        <!-- CLIENTE -->
        <div id="filtroClienteDiv" class="hidden">
            <label class="text-default-800 text-sm font-medium mb-2">Cliente</label>
            <select id="filtroClienteSelect" class="form-select">
                <option value="">-Seleccionar-</option>
                @foreach($clientes as $cliente)
                    <option value="{{ $cliente->codigo }}">{{ $cliente->abreviatura }}</option>
                @endforeach
            </select>
        </div>

                  <!-- CODIGO -->
                <div id="filtroCodigoDiv" class="hidden">
                    <label class="text-default-800 text-sm font-medium mb-2">Codigo</label>
                    <select id="filtroCodigoSelect" class="form-select">
                        <option value="">-Seleccionar-</option>
                        <!-- Aquí luego vas a iterar $clientes si los tienes -->
                      
                    </select>
                </div>

            </div>
            <div class="flex justify-end">
                <button id="btnGenerarPdfFoliosPorVencer" class="bg-danger text-white px-6 py-2 rounded-lg shadow hover:bg-danger/90">
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
