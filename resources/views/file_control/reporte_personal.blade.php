@extends('layouts.vertical', ['title' => 'Reporte Personal'])

@section('css')
<style>
    #tblReportePersonal .tabulator-cell { font-size: 0.8125rem; }
</style>
@endsection

@section('content')
@include("layouts.shared/page-title", ["subtitle" => "DJ", "title" => "Reporte Personal"])

<div class="grid lg:grid-cols-1 gap-6 mt-8">
    <div class="card overflow-hidden">

        <div class="card-header flex items-center justify-between">
            <h4 class="card-title">Listado de Personal</h4>
            <div class="flex items-center gap-1.5 text-sm text-default-500">
                <span id="cntTotal" class="font-semibold text-default-700">0</span>
                <span>registros</span>
            </div>
        </div>

        {{-- Búsqueda --}}
        <div class="w-full px-5 py-2 mt-2">
            <input type="text" id="buscarRep"
                placeholder="Buscar por nombre, apellido, código o DNI..."
                class="w-full px-3 py-1.5 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm"
                style="max-width: 500px;" autocomplete="off" />
        </div>

        {{-- Filtros --}}
        <div class="w-full px-5 py-2">
            <div class="flex flex-wrap items-center gap-5 bg-slate-50 p-4 rounded-lg border border-slate-200">

                {{-- Sucursal --}}
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700">Sucursal:</label>
                    <select id="filtroSucursal" class="form-select text-sm px-3 py-1.5 border border-gray-300 rounded-lg bg-white">
                        <option value="">Todas</option>
                        @foreach(array_slice($sucursales, 1) as $suc)
                            <option value="{{ $suc->abreviatura }}">{{ $suc->abreviatura }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tipo --}}
                <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                    <label class="text-sm font-medium text-gray-700">Tipo:</label>
                    <select id="filtroTipo" class="form-select text-sm w-48 px-3 py-1.5 border border-gray-300 rounded-lg bg-white">
                        @if($tipoPerLimitar == 0)
                            <option value="">Todos</option>
                            <option value="OPER 4°">Operativo 4°</option>
                            <option value="OPER 5°">Operativo 5°</option>
                            <option value="ADM 4°">Administrativo 4°</option>
                            <option value="ADM 5°">Administrativo 5°</option>
                            <option value="ESP">Especiales</option>
                        @elseif($tipoPerLimitar == 1)
                            <option value="">Todos</option>
                            <option value="ADM 4°">Administrativo 4°</option>
                            <option value="ADM 5°">Administrativo 5°</option>
                        @elseif($tipoPerLimitar == 2)
                            <option value="">Todos</option>
                            <option value="OPER 4°">Operativo 4°</option>
                            <option value="OPER 5°">Operativo 5°</option>
                        @endif
                    </select>
                </div>

                {{-- Vigencia --}}
                <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                    <label class="text-sm font-medium text-gray-700">Vigencia:</label>
                    <select id="filtroVigencia" class="form-select text-sm px-3 py-1.5 border border-gray-300 rounded-lg bg-white">
                        <option value="">TODOS</option>
                        <option value="SI" selected>SI</option>
                        <option value="NO">NO</option>
                    </select>
                </div>

            </div>
        </div>

        {{-- Tabla --}}
        <div class="w-full px-5 py-2 mt-2">
            <div id="tblReportePersonal" class="w-full"></div>

            <div class="flex items-center gap-2 mt-3">
                <label for="pageSizeRep" class="text-sm text-gray-600">Mostrar</label>
                <select id="pageSizeRep" class="w-20 px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-white">
                    <option value="10">10</option>
                    <option value="20" selected>20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-sm text-gray-600">por página</span>
            </div>
        </div>

    </div>
</div>

{{-- Modal: Ver detalles --}}
<div id="modalDetallePersonal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl mx-4 flex flex-col max-h-[85vh]">
        <div class="flex items-center justify-between p-5 border-b border-default-200 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center">
                    <i class='bx bx-user text-primary text-xl'></i>
                </div>
                <div>
                    <h5 class="text-base font-semibold text-default-800" id="modalDetalleTitulo">Detalle de Personal</h5>
                    <p class="text-xs text-default-400" id="modalDetalleCodigo"></p>
                </div>
            </div>
            <button id="btnCerrarModalDetalle" class="text-default-400 hover:text-default-600">
                <i class='bx bx-x text-2xl'></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <div class="flex flex-col items-center justify-center gap-2 py-10 text-default-400">
                <i class='bx bx-time-five text-4xl'></i>
                <p class="text-sm">Detalle en construcción</p>
            </div>
        </div>
    </div>
</div>

@endsection

@vite(['resources/js/functions/reporte_personal.js'])
@section('script')
@endsection
