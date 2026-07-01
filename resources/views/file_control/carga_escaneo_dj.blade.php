@extends('layouts.vertical', ['title' => 'Carga de DJ'])

@section('content')

@include('layouts.shared/page-title', ['subtitle' => 'File Control', 'title' => 'Carga de DJ'])

<div class="grid grid-cols-1 gap-6 mt-8">

    <div class="card overflow-hidden">
        <div class="card-header">
            <h4 class="card-title">Listado de Personal</h4>
        </div>

        <div class="px-5 pt-4 pb-2 space-y-3">

            <div class="flex flex-wrap items-center justify-between gap-3">
                <input
                    type="text"
                    id="buscarPersonal"
                    placeholder="Buscar por nombre o DNI..."
                    autocomplete="off"
                    class="w-40 px-3 py-1.5 text-sm uppercase border border-gray-300 rounded-full
                           focus:outline-none focus:border-blue-500 transition-colors"
                    style="min-width: 220px;"
                />

                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Sucursal</label>
                    <select id="sucursal" class="form-select text-sm">
                        <option disabled selected>— Seleccionar —</option>
                        @foreach ($sucursales as $suc)
                            <option value="{{ $suc->codigo }}">{{ $suc->abreviatura }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Tipo</label>
                    <select id="tipo_per" class="form-select text-sm w-44">
                        @if ($tipoPerLimitar == 0)
                            <option value="TODOS" selected>Todos</option>
                            <option value="ADMIN_4">Administrativo 4°</option>
                            <option value="ADMIN_5">Administrativo 5°</option>
                            <option value="OPER_4">Operativo 4°</option>
                            <option value="OPER_5">Operativo 5°</option>
                            <option value="ESPECIAL">Especiales</option>
                        @elseif ($tipoPerLimitar == 1)
                            <option value="TODOS" selected>Todos</option>
                            <option value="ADMIN_4">Administrativo 4°</option>
                            <option value="ADMIN_5">Administrativo 5°</option>
                        @elseif ($tipoPerLimitar == 2)
                            <option value="TODOS" selected>Todos</option>
                            <option value="OPER_4">Operativo 4°</option>
                            <option value="OPER_5">Operativo 5°</option>
                        @endif
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700 whitespace-nowrap">DJ</label>
                    <select id="filtroDJ" class="form-select text-sm">
                        <option value="TODOS">Todos</option>
                        <option value="SI">Subida</option>
                        <option value="NO">Pendiente</option>
                    </select>
                </div>

                <div class="flex items-center gap-x-4">
                    <span class="text-sm font-medium text-gray-700">Vigencia</span>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="vigencia" value="" class="form-radio text-primary">
                        <span class="text-sm text-gray-700">Todos</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="vigencia" value="SI" checked class="form-radio text-primary">
                        <span class="text-sm text-gray-700">Sí</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="vigencia" value="NO" class="form-radio text-primary">
                        <span class="text-sm text-gray-700">No</span>
                    </label>
                </div>
            </div>

        </div>

        <div class="px-5 pb-5 pt-2">
            <div id="tblPersonas" class="w-full"></div>

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

</div>

{{-- Hidden: código de persona activo --}}
<input type="hidden" id="codPersonal" value="">

{{-- ── Modal: Carga de DJ ─────────────────────────────────────────────────── --}}
<button type="button" class="hidden" id="btn-modal-dj" data-hs-overlay="#modal-carga-dj"></button>

<div id="modal-carga-dj"
     class="hs-overlay hidden fixed inset-0 z-70 overflow-y-auto transition-all duration-500 pointer-events-none">
    <div class="hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100
                translate-y-10 opacity-0 ease-in-out transition-all duration-500
                sm:max-w-lg w-full my-8 sm:mx-auto flex flex-col bg-white shadow-sm rounded-lg pointer-events-auto
                border border-gray-200">

        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200">
            <div>
                <h3 class="text-base font-semibold text-gray-900">Subir Declaración Jurada</h3>
                <p class="text-sm text-gray-500 nombre-personal mt-0.5"></p>
            </div>
            <button type="button" id="btn-modal-dj-close"
                    class="text-gray-500 hover:text-gray-700 transition-colors"
                    data-hs-overlay="#modal-carga-dj">
                <i class="i-tabler-x text-xl"></i>
            </button>
        </div>

        <form id="formSubirDJ">
            @csrf
            <input type="hidden" id="codPersonalDJ" value="">

            <div class="px-5 py-5 space-y-5">

                <p class="text-sm text-center text-gray-500">
                    Solo se acepta archivo <strong>PDF</strong> con un peso máximo de <strong>1 MB</strong>.
                </p>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Emisión</label>
                    <input type="date" id="fecha_emision_dj" required class="form-input w-full">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Archivo PDF</label>

                    <div id="zonaDropDJ" role="button"
                         class="cursor-pointer border-2 border-dashed border-gray-300 rounded-xl p-8
                                flex flex-col items-center justify-center gap-3
                                hover:border-blue-400 hover:bg-blue-50 transition-colors">

                        <span class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-gray-100 text-gray-600">
                            <i class="i-tabler-file-type-pdf text-2xl"></i>
                        </span>

                        <div class="text-center text-sm text-gray-600">
                            <span class="font-medium text-gray-800">Haz click aquí para </span>
                            <span class="font-semibold text-blue-600 hover:underline">SELECCIONAR</span>
                        </div>

                        <p class="text-xs text-gray-400">Máximo 1 MB</p>

                        <input type="file" id="archivoDJ" accept=".pdf" class="hidden">
                    </div>

                    <ul id="listaArchivosDJ" class="mt-3 space-y-2"></ul>
                </div>

            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-gray-200">
                <button type="submit" class="btn bg-primary text-white" id="btn-guardar-dj">
                    <i class="i-tabler-upload me-1"></i> Subir DJ
                </button>
                <button type="button" class="btn bg-gray-100 text-gray-700 hover:bg-gray-200"
                        data-hs-overlay="#modal-carga-dj">
                    <i class="i-tabler-x me-1"></i> Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@vite(['resources/js/functions/carga_escaneo_dj.js'])
