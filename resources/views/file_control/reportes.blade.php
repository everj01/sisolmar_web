@extends('layouts.vertical', ['title' => 'Legajos PDF'])

@section('css')

@endsection

@section('content')

@include("layouts.shared/page-title", ["subtitle" => "Recursos Humanos", "title" => "Reportes"])

<link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="grid 2xl:grid-cols-4 grid-cols-1 gap-6">
<!-- <div class="grid lg:grid-cols-1 gap-6 mt-8"> -->
    <div class="card custom-card">
        <div class="p-4 md:p-5">
            <h3 class="text-lg font-bold text-default-800">
                Folios vigentes
            </h3>
            <p class="mt-2 text-default-500">
                Genera el reporte de folios vigentes en el sistema con su clasificación.
            </p>
            <button
                type="button"
                id="btnReporteFoliosVigentes"
                class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg
                    border border-transparent text-primary hover:text-primary-800">
                Generar
                <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
            </button>

        </div>
    </div>

    <div class="card custom-card" >
        <div class="p-4 md:p-5">
            <h3 class="text-lg font-bold text-default-800">
                Folios pendientes
            </h3>
            <p class="mt-2 text-default-500">
                Genera el reporte de folios pendientes por sucursal.
            </p>
            <button
                type="button"
                id="btnReporteFoliosPendientesSucursal"
                class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg
                    border border-transparent text-primary hover:text-primary-800">
                Generar
                <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
            </button>
        </div>
    </div>

    <div class="card custom-card">
        <div class="p-4 md:p-5">
            <h3 class="text-lg font-bold text-default-800">
                Legajos por cliente - cargo
            </h3>
            <p class="mt-2 text-default-500">
                Genera el reporte de legajos por cliente y cargo.
            </p>
            <a id="legajo3" class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-primary hover:text-primary-800 disabled:opacity-50 disabled:pointer-events-none" href="#">
                Generar
                <i class="material-symbols-rounded text-lg flex-shrink-0">chevron_right</i>
            </a>
        </div>
    </div>

</div>


<div class="grid lg:grid-cols-1 gap-6 mt-8">

    <!-- FILTROS: FOLIOS VIGENTES -->
    <div class="card overflow-hidden hidden" id="filtrosFoliosVigentes">
        <div class="card-header">
            <h4 class="card-title">Filtros – Folios vigentes</h4>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>
                    <label class="text-default-800 text-sm font-medium inline-block mb-2">
                        Tipo de folio
                    </label>
                    <select id="filtroTipoFolio" class="form-select">
                        <option value="">Todos</option>
                        <option value="DOCUMENTO">Documento</option>
                        <option value="FORMATO">Formato</option>
                        <option value="CERTIFICADO">Certificado</option>
                    </select>
                </div>

                <div>
                    <label class="text-default-800 text-sm font-medium inline-block mb-2">
                        Prioridad
                    </label>
                    <select id="filtroPrioridad" class="form-select">
                        <option value="">Todas</option>
                        <option value="PRINCIPAL">Principal</option>
                        <option value="ADICIONAL">Adicional</option>
                    </select>
                </div>

            </div>

            <div class="mt-6 flex justify-end">
                <button
                    id="btnGenerarPdfFoliosVigentes"
                    class="bg-danger text-white px-6 py-2 rounded-lg shadow
                           hover:bg-danger/90">
                    <i class="fa-solid fa-file-pdf"></i>
                    Generar PDF
                </button>
            </div>
        </div>
    </div>


    <!-- FILTROS: FOLIOS PENDIENTES POR SUCURSAL -->
    <div class="card overflow-hidden hidden" id="filtrosFoliosPendientesSucursal">
        <div class="card-header">
            <h4 class="card-title">Filtros – Folios pendientes por sucursal</h4>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>
                    <label for="sucursal" class="text-default-800 text-sm font-medium inline-block mb-2">Sucursal</label>
                    <select id="sucursal" class="form-select">
                        <option disabled selected>-Seleccionar-</option>
                        @foreach($sucursales as $sucursal)
                        <option value="{{ $sucursal->codigo }}">{{ $sucursal->abreviatura }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    id="btnGenerarPdfFoliosPendientesSucursal"
                    class="bg-danger text-white px-6 py-2 rounded-lg shadow
                           hover:bg-danger/90">
                    <i class="fa-solid fa-file-pdf"></i>
                    Generar PDF
                </button>
            </div>
        </div>
    </div>

</div>





@endsection

@vite(['resources/js/functions/reportes.js'])
@section('script')

@endsection