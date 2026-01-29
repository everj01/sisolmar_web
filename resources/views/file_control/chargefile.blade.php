@extends('layouts.vertical', ['title' => 'Carga de archivos'])

@section('css')

@endsection

@section('content')

@include("layouts.shared/page-title", ["subtitle" => "Recursos Humanos", "title" => "File Control"])

<style>
    /* Contenedor de la paginación */
.tabulator-paginator {
    display: flex;
    gap: 0.5rem; /* separación entre botones */
    justify-content: center; /* centra horizontalmente */
    margin-top: 1rem;
}

/* Cada botón de página */
.tabulator-page {
    display: inline-block;
    padding: 0.3rem 0.6rem;
    border: 1px solid #ccc;
    border-radius: 0.25rem;
    cursor: pointer;
    background-color: #f9f9f9;
    transition: all 0.2s;
}

/* Hover */
.tabulator-page:hover {
    background-color: #e2e8f0; /* un gris claro */
}

/* Página activa */
.tabulator-page.active {
    background-color: #3b82f6; /* azul */
    color: white;
    border-color: #3b82f6;
}

</style>
<div class="grid lg:grid-cols-2 gap-6 mt-8">
    <div class="card overflow-hidden">
        <div class="card-header">
            <h4 class="card-title">Listado de Personal</h4>
        </div>

        <div class="w-full px-5 py-2 mt-3 flex flex-wrap justify-between items-center gap-4">
            <input type="text" id="buscarPersonal" placeholder="Buscar..." class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm uppercase" autocomplete="off" />
            <div class="flex items-center space-x-2 w-80 justify-end">
                <label for="sucursales" class="text-default-800 text-sm font-medium">Sucursal</label>
                <select id="sucursal" class="form-select max-w-xs">
                    <option disabled selected>-Seleccionar-</option>
                    @foreach($sucursales as $sucursal)
                    <option value="{{ $sucursal->codigo }}">{{ $sucursal->abreviatura }}</option>
                    @endforeach
                </select>
            </div>
        </div>


        <div class="w-full px-5 py-2 flex flex-col md:flex-row justify-center items-center gap-4">
            <!-- Tipo de personal -->
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center space-x-2">
                    <input type="radio" class="form-radio text-primary" id="radioTodos" name="tipo_per" value="TODOS" checked>
                    <label for="radioTodos">Todos</label>
                </div>
                <div class="flex items-center space-x-2">
                    <input type="radio" class="form-radio text-primary" id="radioOper" name="tipo_per" value="ADMIN">
                    <label for="radioOper">Administrativo</label>
                </div>
                <div class="flex items-center space-x-2">
                    <input type="radio" class="form-radio text-primary" id="radioAdmin" name="tipo_per" value="OPER">
                    <label for="radioAdmin">Operativo</label>
                </div>
            </div>

            <!-- Vigencia del personal -->
            <div class="flex flex-wrap items-center gap-4">
                <label for="vigencia" class="text-default-800 text-sm font-medium">Vigencia: </label>
                <div class="flex items-center space-x-2">
                    <input type="radio" class="form-radio text-primary" id="radioTodosV" name="vigencia" value="" checked>
                    <label for="radioTodosV">Todos</label>
                </div>
                <div class="flex items-center space-x-2">
                    <input type="radio" class="form-radio text-primary" id="radiooSi" name="vigencia" value="SI">
                    <label for="radiooSi">Si</label>
                </div>
                <div class="flex items-center space-x-2">
                    <input type="radio" class="form-radio text-primary" id="radioNo" name="vigencia" value="NO">
                    <label for="radioNo">No</label>
                </div>
            </div>
        </div>


        <!-- <div class="w-full px-5 py-2 mt-3">
            <div id="tblPersonas" class="w-full mt-5"></div>
        </div> -->

        <div class="w-full px-5 py-2 mt-3">
            <div id="tblPersonas" class="w-full mb-5"></div>
            <div class="flex justify-between items-center mb-2">
                <div id="tablaInfo" class="text-sm text-gray-700">Cargando...</div>
                <div id="tablaPaginacion"></div>
            </div>
        </div>

    </div>

    <div id="dataDocs" class="card hidden">
        <div class="card-header">
            <h4 class="card-title nombrePersDocs">Folios de</h4>
        </div>
        <div class="w-full px-5 py-2 flex flex-col">
            <div class="flex justify-center items-center gap-4 mb-4 mt-4">
                <div class="form-check">
                    <input type="radio" class="form-radio text-primary" id="radioPrin" name="tipo_folio" value="PRINCIPAL" checked>
                    <label class="ms-1.5" for="radioPrin">Principal</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-radio text-primary" id="radioAux" name="tipo_folio" value="ADICIONAL">
                    <label class="ms-1.5" for="radioAux">Adicional</label>
                </div>
            </div>
            <div class="w-full px-5 py-2 mt-3">
                <input type="text" id="buscarFolio" placeholder="Buscar..."
                    class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm uppercase" autocomplete="off"/>
                <div id="tblDocs" class="w-full flex-grow mt-3"></div>
            </div>
        </div>
    </div>

    <div id="dataDocsLeg" class="card hidden">
        <div class="card-header">
            <h4 class="card-title nombrePersLeg">Legajos para</h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-3">
                <div>
                    <label for="inputState"
                        class="text-default-800 text-sm font-medium inline-block mb-2">Cliente</label>
                    <select id="clientes" class="form-select">
                        <option disabled selected>-Seleccionar-</option>
                        @foreach($clientes as $cliente)
                        <option value="{{ $cliente->codigo }}">{{ $cliente->abreviatura }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="inputZip" class="text-default-800 text-sm font-medium inline-block mb-2">Cargo</label>
                    <select id="cargos" class="form-select">
                        <option disabled selected>-Seleccionar-</option>
                    </select>
                </div>
            </div>

        </div>
        <div class="w-full px-5 py-2">
            <input type="hidden" name="codPersonal" id="codPersonal">
            <div id="tblDocsLegajo" class="w-full hidden"></div>
        </div>

    </div>
</div>

<div class="card-body">
    <!--VISOR DE DOCUMENTOS-->
    <button type="button" class="hidden" id="btn-modal-view-docs" data-hs-overlay="#modal-view-docs">
    </button>

    <div id="modal-view-docs"
        class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-y-auto hidden pointer-events-none">
        <div
            class="translate-y-10 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 max-w-3xl w-full my-8 mx-auto flex flex-col bg-white shadow-sm rounded">
            <div class="flex flex-col border border-default-200 shadow-sm rounded-lg  pointer-events-auto">
                <div class="flex justify-between items-center py-3 px-4 border-b border-default-200">
                    <h3 class="text-lg font-medium text-default-900 modal-title">Modal title</h3>
                    <button type="button" class="text-default-600 cursor-pointer" id="btn-modal-view-docs-close"
                        data-hs-overlay="#modal-view-docs">
                        <i class="i-tabler-x text-lg"></i>
                    </button>
                </div>
                <div class="text-center">
                    <h4 id="txtDocSelec" class="mt-4 mb-5 ms-2"></h4>
                </div>

                <div class="flex gap-2 flex-col mx-4" id="visorDocs">

                </div>
            </div>
        </div>
    </div>

    <!-- Formulario FOLIOS -->
    <button type="button" class="hidden" id="btn-modal-docs" data-hs-overlay="#modal-file">
    </button>

    <div id="modal-file"
        class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-y-auto hidden pointer-events-none">
        <div
            class="translate-y-10 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 sm:max-w-lg sm:w-full my-8 sm:mx-auto flex flex-col bg-white shadow-sm rounded">
            <div class="flex flex-col border border-default-200 shadow-sm rounded-lg  pointer-events-auto">
                <div class="flex justify-between items-center py-3 px-4 border-b border-default-200">
                    <h3 class="text-lg font-medium text-default-900 modal-title">Modal title</h3>
                    <button type="button" class="text-default-600 cursor-pointer" id="btn-modal-docs-close"
                        data-hs-overlay="#modal-file">
                        <i class="i-tabler-x text-lg"></i>
                    </button>
                </div>
                <form id="formFolioPersonal">
                    @csrf
                    <div class="p-4 overflow-y-auto">
                        <p class="mt-1 text-default-600">
                            <center>Archivo de tipo <strong>IMAGEN</strong> y con peso máximo
                                <strong>1MB</strong>.
                            </center>
                        </p>
                        <span class="mt-1 text-default-600 text-red-800">
                            <center> <strong id="txtPeriodo"></strong></center>
                        </span>
                        <input type="hidden" name="meses" id="meses">
                        <input type="hidden" name="codFolio" id="codFolio">

                        <div class="mt-5" id="divEmision">
                            <label for="example-time"
                                class="text-default-800 text-sm font-medium inline-block mb-2">Fecha
                                de Emision</label>
                            <input class="form-input" type="date" id="fecha_emision" required>
                        </div>
                        <div class="mt-5" id="divCaducidad">
                            <label for="example-time"
                                class="text-default-800 text-sm font-medium inline-block mb-2">Fecha
                                de Caducidad</label>
                            <input class="form-input" type="date" id="fecha_caducidad" required>
                        </div>
                        <br>
                        <hr>
                        <center class="text-primary text-base">
                            Este folio tiene como máximo, <strong id="txtCantHojas"></strong> hojas
                        </center>
                        <hr>
                        <div class="mt-5">
                            <label for="example-time" class="text-default-800 text-sm font-medium inline-block mb-2">Archivo</label>
                            <div class="cursor-pointer p-12 flex justify-center bg-white border border-dashed border-default-300 rounded-xl" id="btnSeleccionar" role="button">
                                <div class="text-center">
                                    <span class="inline-flex justify-center items-center size-16 bg-default-100 text-default-800 rounded-full cursor-pointer" >
                                        <i class="i-tabler-upload size-6 shrink-0"></i>
                                    </span>

                                    <div class="mt-4 flex flex-wrap justify-center text-sm leading-6 text-default-600">
                                        <span class="pe-1 font-medium text-default-800">
                                            Arrastra tu archivo aquí o d
                                        </span>
                                        <span class="bg-white font-semibold text-primary hover:text-primary-700 rounded-lg decoration-2 hover:underline focus-within:outline-none focus-within:ring-2 focus-within:ring-primary-600 focus-within:ring-offset-2" >SELECCIONAR</span>
                                    </div>

                                    <p class="mt-1 text-xs text-default-400">
                                        Peso menor a 1MB.
                                    </p>
                                </div>
                                <input type="file" id="archivoInput" name="imagenes[]" multiple accept="image/*" class="hidden">
                                <input type="hidden" name="cantArchivos" id="cantArchivos">
                            </div>

                            <div class="mt-1">
                                <ul id="listaArchivos" class="mt-4 space-y-2"></ul>
                            </div>

                        </div>


                        <!-- <div class="mt-5">
                            <label for="example-time"
                                class="text-default-800 text-sm font-medium inline-block mb-2">Archivo</label>
                            <div class="mt-1" data-hs-file-upload='{
                                        "url": "/upload",
                                        "extensions": {
                                        "default": {
                                            "class": "shrink-0 size-5"
                                        },
                                        "xls": {
                                            "class": "shrink-0 size-5"
                                        },
                                        "zip": {
                                            "class": "shrink-0 size-5"
                                        },
                                        "csv": {
                                            "icon": "<i class=\"i-tabler-file-code\"></i>",
                                            "class": "shrink-0 size-5"
                                        }
                                        }
                                    }'>
                                <template data-hs-file-upload-preview="">
                                    <div class="p-3 bg-white border border-solid border-default-300 rounded-xl">
                                        <div class="mb-1 flex justify-between items-center">
                                            <div class="flex items-center gap-x-3">
                                                <span
                                                    class="size-10 flex justify-center items-center border border-default-200 text-default-500 rounded-lg"
                                                    data-hs-file-upload-file-icon="">
                                                    <img class="rounded-lg hidden" data-dz-thumbnail="">
                                                </span>
                                                <div>
                                                    <p class="text-sm font-medium text-default-800">
                                                        <span class="truncate inline-block max-w-[300px] align-bottom"
                                                            data-hs-file-upload-file-name=""></span>.<span
                                                            data-hs-file-upload-file-ext=""></span>
                                                    </p>
                                                    <p class="text-xs text-default-500"
                                                        data-hs-file-upload-file-size="">
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-x-2">
                                                <button type="button"
                                                    class="text-default-500 hover:text-default-800 focus:outline-none focus:text-default-800"
                                                    data-hs-file-upload-remove="">
                                                    <i class="i-tabler-trash size-4 shrink-0"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-x-3 whitespace-nowrap">
                                            <div class="flex w-full h-2 bg-default-200 rounded-full overflow-hidden"
                                                role="progressbar" aria-valuenow="0" aria-valuemin="0"
                                                aria-valuemax="100" data-hs-file-upload-progress-bar="">
                                                <div class="flex flex-col justify-center rounded-full overflow-hidden bg-primary-600 text-xs text-white text-center whitespace-nowrap transition-all duration-500 hs-file-upload-complete:bg-green-500"
                                                    style="width: 0" data-hs-file-upload-progress-bar-pane=""></div>
                                            </div>
                                            <div class="w-10 text-end">
                                                <span class="text-sm text-default-800">
                                                    <span data-hs-file-upload-progress-bar-value="">0</span>%
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <div class="cursor-pointer p-12 flex justify-center bg-white border border-dashed border-default-300 rounded-xl"
                                    data-hs-file-upload-trigger="">
                                    <div class="text-center">
                                        <span
                                            class="inline-flex justify-center items-center size-16 bg-default-100 text-default-800 rounded-full">
                                            <i class="i-tabler-upload size-6 shrink-0"></i>
                                        </span>

                                        <div class="mt-4 flex flex-wrap justify-center text-sm leading-6 text-default-600">
                                            <span class="pe-1 font-medium text-default-800">
                                                Arrastra tu archivo aquí o
                                            </span>
                                            <span
                                                class="bg-white font-semibold text-primary hover:text-primary-700 rounded-lg decoration-2 hover:underline focus-within:outline-none focus-within:ring-2 focus-within:ring-primary-600 focus-within:ring-offset-2">SELECCIONAR</span>
                                        </div>

                                        <p class="mt-1 text-xs text-default-400">
                                            Peso menor a 1MB.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div> -->

                        <!-- <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Gallery</h4>
                            </div>
                            <div class="p-6">
                                <div data-hs-file-upload="{
                                        &quot;url&quot;: &quot;/upload&quot;,
                                        &quot;acceptedFiles&quot;: &quot;image/*&quot;,
                                        &quot;autoHideTrigger&quot;: false,
                                        &quot;extensions&quot;: {
                                        &quot;default&quot;: {
                                            &quot;class&quot;: &quot;shrink-0 size-5&quot;
                                        },
                                        &quot;xls&quot;: {
                                            &quot;class&quot;: &quot;shrink-0 size-5&quot;
                                        },
                                        &quot;zip&quot;: {
                                            &quot;class&quot;: &quot;shrink-0 size-5&quot;
                                        },
                                        &quot;csv&quot;: {
                                            &quot;icon&quot;: &quot;<i class=\&quot;i-tabler-file-code\&quot;></i>&quot;,
                                            &quot;class&quot;: &quot;shrink-0 size-5&quot;
                                        }
                                        }
                                    }">
                                    <template data-hs-file-upload-preview="">
                                        <div class="relative mt-2 p-2 bg-white border border-default-200 rounded-xl">
                                            <img class="mb-2 w-full object-cover rounded-lg" data-dz-thumbnail="">

                                            <div class="mb-1 flex justify-between items-center gap-x-3 whitespace-nowrap">
                                                <div class="w-10">
                                                    <span class="text-sm text-default-800">
                                                        <span data-hs-file-upload-progress-bar-value="">0</span>%
                                                    </span>
                                                </div>

                                                <div class="flex items-center gap-x-2">
                                                    <button type="button" class="text-default-500 hover:text-default-800 focus:outline-none focus:text-default-800" data-hs-file-upload-remove="">
                                                        <i class="i-tabler-trash shrink-0 size-3.5"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="flex w-full h-2 bg-default-200 rounded-full overflow-hidden" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" data-hs-file-upload-progress-bar="">
                                                <div class="flex flex-col justify-center rounded-full overflow-hidden bg-primary-600 text-xs text-white text-center whitespace-nowrap transition-all duration-500 hs-file-upload-complete:bg-green-500" style="width: 0" data-hs-file-upload-progress-bar-pane=""></div>
                                            </div>
                                        </div>
                                    </template>

                                    <div class="cursor-pointer p-12 flex justify-center bg-white border border-dashed border-default-300 rounded-xl dz-clickable" data-hs-file-upload-trigger="">
                                        <div class="text-center">
                                            <span class="inline-flex justify-center items-center size-16 bg-default-100 text-default-800 rounded-full">
                                                <i class="i-tabler-upload size-6 shrink-0"></i>
                                            </span>

                                            <div class="mt-4 flex flex-wrap justify-center text-sm leading-6 text-default-600">
                                                <span class="pe-1 font-medium text-default-800">
                                                    Drop your file here or
                                                </span>
                                                <span class="bg-white font-semibold text-primary hover:text-primary-700 rounded-lg decoration-2 hover:underline focus-within:outline-none focus-within:ring-2 focus-within:ring-primary-600 focus-within:ring-offset-2">browse</span>
                                            </div>

                                            <p class="mt-1 text-xs text-default-400">
                                                Pick a file up to 2MB.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-4 gap-x-2 empty:gap-0" data-hs-file-upload-previews=""></div>
                                </div>
                            </div>
                        </div> -->

                        <div class="mt-4 space-y-2 empty:mt-0" data-hs-file-upload-previews=""></div>
                    </div>
                    <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-default-200">
                        <button type="submit" class="btn bg-primary text-white" href="#">
                            <i class="i-tabler-check me-1"></i>
                            Guardar
                        </button>
                        <button type="button" class="btn bg-primary text-white" data-hs-overlay="#modal-file">
                            <i class="i-tabler-x me-1"></i>
                            Cerrar
                        </button>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="divCoincidencias" class="grid lg:grid-cols-1 gap-6 mt-8 hidden">
    <div class="card overflow-hidden">
        <div class="card-header">
            <h4 class="card-title">Listado de COINCIDENCIAS</h4>
        </div>
        <div class="w-full px-5 py-2 mt-3">
            <input type="text" id="buscar" placeholder="Buscar..."
                class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm" />
            <div id="tblPersonasCN" class="w-full mt-8"></div>
        </div>
    </div>
</div>


@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
@endsection

@vite(['resources/js/functions/chargeFile.js'])
@vite(['resources/js/functions/changeFilePers.js'])
@section('script')

@endsection
