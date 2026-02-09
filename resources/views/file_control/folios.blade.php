@extends('layouts.vertical', ['title' => 'Gestión de Folios'])

@section('css')

@endsection

@section('content')

@include("layouts.shared/page-title", ["subtitle" => "Recursos Humanos", "title" => "Folios"])

<div class="grid lg:grid-cols-2 gap-6 mt-8">
    <!-- Listado de Folios -->
    <div class="card overflow-hidden lg:col-span-8">
        <div class="card-header flex justify-between items-center">
            <h4 class="card-title">Listado de Folios</h4>
            <button type="button" id="btnGenerarPDF" 
                class="btn rounded-full bg-danger/25 text-danger hover:bg-danger hover:text-white">
                <i class="fa-solid fa-file-pdf"></i> Generar Reporte PDF
            </button>
        </div>
        <div class="w-full px-5 py-2 flex flex-col">
            <div class="flex justify-center items-center gap-4 mb-2 mt-4">
                <div class="form-check">
                    <input type="radio" class="form-radio text-primary" id="radioTod" name="folioFiltro" value="TODOS" checked>
                    <label class="ms-1.5" for="radioTod">TODOS ({{ $todos }})</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-radio text-primary" id="radioPri" name="folioFiltro" value="PRINCIPAL">
                    <label class="ms-1.5" for="radioPri">PRINCIPAL ({{ $principal }})</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-radio text-primary" id="radioAdic" name="folioFiltro" value="ADICIONAL">
                    <label class="ms-1.5" for="radioAdic">ADICIONAL ({{ $adicional }})</label>
                </div>
            </div>
            <div class="flex justify-center items-center gap-4 mb-4 mt-2">
                <div class="form-check">
                    <input type="radio" class="form-radio text-danger" id="radioDoc" name="folioFiltro" value="DOCUMENTO">
                    <label class="ms-1.5" for="radioDoc">DOCUMENTO ({{ $documento }})</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-radio text-danger" id="radioForm" name="folioFiltro" value="FORMATO">
                    <label class="ms-1.5" for="radioForm">FORMATO ({{ $formato }})</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-radio text-danger" id="radioCert" name="folioFiltro" value="CERTIFICADO">
                    <label class="ms-1.5" for="radioCert">CERTIFICADO ({{ $certificado }})</label>
                </div>
            </div>
            <!-- <div class="w-full px-5 py-2 mt-3">
                <div class="flex justify-between">
                    <input type="text" id="buscar" placeholder="Buscar..."
                    class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm" />
                    
                    <div x-data="{ soloActivos: true }" class="flex items-center">
                        <input class="form-switch" type="checkbox" role="switch" id="chkEliminados"
                            x-model="soloActivos">
                        <label class="ms-1.5" for="chkEliminados">Solo activos</label>

                        <div x-effect="
                            soloActivos 
                                ? aplicarFiltroSoloActivos(1)
                                : aplicarFiltroSoloActivos(0);
                        "></div>
                    </div>
                </div>
                
                <div id="tblFolios" class="w-full flex-grow mt-3"></div>
            </div> -->

            <div class="w-full px-5 py-2 mt-3">
                <div class="flex justify-between items-center mb-3">
                    <input type="text" id="buscar" placeholder="Buscar..."
                    class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm" />
                    
                    <div class="flex items-center gap-3">
                        <!-- <button type="button" id="btnGenerarPDF" 
                            class="btn rounded-full bg-danger/25 text-danger hover:bg-danger hover:text-white">
                            <i class="fa-solid fa-file-pdf"></i> Generar Reporte PDF
                        </button> -->
                        
                        <div x-data="{ soloActivos: true }" class="flex items-center">
                            <input class="form-switch" type="checkbox" role="switch" id="chkEliminados"
                                x-model="soloActivos">
                            <label class="ms-1.5" for="chkEliminados">Solo activos</label>

                            <div x-effect="
                                soloActivos 
                                    ? aplicarFiltroSoloActivos(1)
                                    : aplicarFiltroSoloActivos(0);
                            "></div>
                        </div>
                    </div>
                </div>
                
                <div id="tblFolios" class="w-full flex-grow mt-3"></div>
            </div>

        </div>
    </div>

    <!-- Formulario de registro -->
    <div class="card lg:col-span-4">
        <div class="card-header">
            <h3 class="card-title">Gestión de Folios&nbsp;&nbsp;&nbsp;<span 
            class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-primary/25 text-primary-800"
            id="txtMensajeNuevo">Nuevo registro</span></h3>
        </div>

        <div class="hidden gap-1 mt-1 items-center justify-center" id="soloEdicion">
            <span>¿Quieres crear un nuevo folio?</span>
            <button class="btn bg-info clean-btn text-white rounded-full mx-2">Nuevo folio</button>
        </div>

        <div class="p-6">
            <form id="formSaveFolio">
                <div x-data="{ nameFolio: '', tipoSeleccionado: '' }" class="space-y-4">
                    <div class="flex space-x-4">
                        <div class="flex-1">
                            <label for="nombre" class="text-default-800 text-sm font-medium inline-block mb-2">Nombre</label>
                            <input type="text" id="nombre" class="form-input w-full" x-model="nameFolio" @input="nameFolio = nameFolio.toUpperCase()" 
                            placeholder="Nombre del folio" required>
                        </div>

                        <div class="flex-1">
                            <label for="tipo" class="text-default-800 text-sm font-medium inline-block mb-2">Tipo</label>
                            <select class="form-select w-full" id="tipo" x-model="tipoSeleccionado" required>
                                <option value="" disabled selected>-Seleccionar-</option>
                                <option value="1">DOCUMENTO</option>
                                <option value="2">FORMATO</option>
                                <option value="3">CERTIFICADO</option>
                            </select>
                        </div>
                    </div>

                    <span x-show="tipoSeleccionado === '1'" class="w-full block py-1.5 px-3 rounded-md text-xs font-medium bg-red-100 text-red-800 mb-3">
                        <strong>Documento:</strong> Folio que el personal trae a la empresa
                    </span>
                    <span x-show="tipoSeleccionado === '2'" class="w-full block py-1.5 px-3 rounded-md text-xs font-medium bg-red-100 text-red-800 mb-3">
                        <strong>Formato:</strong> Folio emitido por la empresa
                    </span>
                    <span x-show="tipoSeleccionado === '3'" class="w-full block py-1.5 px-3 rounded-md text-xs font-medium bg-red-100 text-red-800 mb-3">
                        <strong>Certificado:</strong> Folio emitido por una entidad educativa
                    </span>

                    <div class="flex space-x-4">
                        <div class="form-check">
                            <input type="radio" class="form-radio text-primary" id="radioPrin" name="tipo_folio"
                                value="PRINCIPAL" checked>
                            <label class="ms-1.5" for="radioPrin">Principal</label>
                        </div>
                        <div class="form-check">
                            <input type="radio" class="form-radio text-primary" id="radioAdi" name="tipo_folio"
                                value="ADICIONAL">
                            <label class="ms-1.5" for="radioAdi">Adicional</label>
                        </div> 
                    </div>
                    <br>
                    <div class="flex space-x-4">
                        <div class="flex items-center w-full">
                            <input type="checkbox" id="switchVencimiento" class="form-switch text-danger">
                            <label for="switchVencimiento" class="ms-1.5">Vencimiento</label>
                        </div>

                        <div id="periodoDiv" class="flex items-center w-full hidden">
                            <label for="periodo"
                                class="text-default-800 text-sm font-medium inline-block mb-2">Periodo&nbsp;</label>
                            <select id="periodo" class="form-select">
                                <option disabled selected>-Seleccionar-</option>
                                @foreach($periodos as $periodo)
                                <option value="{{ $periodo->codigo }}">{{ $periodo->descripcion }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <br>
                    <div class="flex space-x-4 mt-5 hidden" id="institucionDiv">
                        <div class="form-check">
                            <input type="radio" class="form-radio text-danger" id="radioICMA" name="institucion"
                                value="ICMA">
                            <label class="ms-1.5" for="radioICMA">ICMA</label>
                        </div>
                        <div class="form-check">
                            <input type="radio" class="form-radio text-danger" id="radioAV" name="institucion"
                                value="AV">
                            <label class="ms-1.5" for="radioAV">AV</label>
                        </div> 
                        <!-- <div class="form-check">
                            <input type="radio" class="form-radio text-danger" id="radioOTROS" name="institucion"
                                value="OTROS">
                            <label class="ms-1.5" for="radioOTROS">OTROS</label>
                        </div>  -->
                    </div>

                </div>
                
                <input type="hidden" name="codFolio" id="codFolio">

                <div class="grid lg:grid-cols-1 justify-center items-center mt-5">
                    <div class="flex justify-center w-full gap-3">
                        <button type="submit" id="submitButton"
                            class="btn rounded-full bg-success/25 text-success hover:bg-success hover:text-white">
                            Guardar <i class="fa-solid fa-floppy-disk"></i>
                        </button>

                        <button type="button" id="cancelButton"
                            class="btn rounded-full bg-danger/25 text-danger hover:bg-danger hover:text-white">
                            Cancelar <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div> <!-- end card -->
</div>

@endsection

@section('script')

@endsection

@vite(['resources/js/functions/folios.js'])