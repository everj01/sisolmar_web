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

        <div class="card-header border-b border-gray-100 py-4 px-5">
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div class="flex items-center gap-3">
                    <h4 class="text-lg font-bold text-primary uppercase flex items-center">
                        <i class='bx bx-group text-2xl mr-2'></i> LISTADO DE PERSONAL
                    </h4>
                    <div id="repLoadingIndicator" class="hidden items-center gap-1.5 text-xs text-gray-400">
                        <svg class="animate-spin w-3.5 h-3.5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        Actualizando...
                    </div>
                </div>
                <div class="flex gap-2">
                    <div class="bg-blue-50 px-3 py-2 rounded-lg border border-blue-200 text-center min-w-[90px] shadow-sm">
                        <span class="block text-[9px] text-blue-600 font-bold uppercase">Total</span>
                        <span id="cntTotal" class="text-lg font-bold text-blue-700">0</span>
                    </div>
                    <div class="bg-green-50 px-3 py-2 rounded-lg border border-green-200 text-center min-w-[90px] shadow-sm">
                        <span class="block text-[9px] text-green-600 font-bold uppercase">Vigentes</span>
                        <span id="cntVigentes" class="text-lg font-bold text-green-700">0</span>
                    </div>
                    <div class="bg-red-50 px-3 py-2 rounded-lg border border-red-200 text-center min-w-[90px] shadow-sm">
                        <span class="block text-[9px] text-red-600 font-bold uppercase">Cesados</span>
                        <span id="cntCesados" class="text-lg font-bold text-red-700">0</span>
                    </div>
                    <div class="bg-neutral-100 px-3 py-2 rounded-lg border border-neutral-300 text-center min-w-[90px] shadow-sm">
                        <span class="block text-[9px] text-neutral-500 font-bold uppercase">Lista Negra</span>
                        <span id="cntListaNegra" class="text-lg font-bold text-neutral-800">0</span>
                    </div>
                </div>
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
                            <option value="{{ $suc->codigo }}">{{ $suc->abreviatura }}</option>
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

                {{-- Botones exportar --}}
                <div class="ml-auto flex items-center gap-2">
                    <button id="btnExportExcelRep" title="Exportar Excel"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-green-300 bg-green-50 text-green-700 text-xs font-semibold hover:bg-green-600 hover:text-white hover:border-green-600 transition-colors">
                        <i class='bx bx-file text-base'></i> Excel
                    </button>
                    <button id="btnExportPdfRep" title="Exportar PDF"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-300 bg-red-50 text-red-700 text-xs font-semibold hover:bg-red-600 hover:text-white hover:border-red-600 transition-colors">
                        <i class='bx bxs-file-pdf text-base'></i> PDF
                    </button>
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
    <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl mx-4 flex flex-col max-h-[90vh]">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                    <i class='bx bx-user text-primary text-xl'></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h5 class="text-base font-bold text-gray-900 tracking-tight" id="modalDetalleTitulo">Detalle de Personal</h5>
                        <span id="modalVigenciaBadge" class="hidden"></span>
                    </div>
                    <p class="text-xs text-gray-500 font-semibold" id="modalDetalleCodigo"></p>
                </div>
            </div>
            <button id="btnCerrarModalDetalle" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class='bx bx-x text-2xl'></i>
            </button>
        </div>

        {{-- Body --}}
        <div class="overflow-y-auto flex-1 px-6 py-5 space-y-6">

            {{-- Sección 1: Información del personal --}}
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Información del Personal</p>
                <div class="flex gap-4">
                    {{-- Foto --}}
                    <div class="flex-shrink-0">
                        <div class="w-24 h-28 rounded-lg border border-gray-200 overflow-hidden bg-gray-100 flex items-center justify-center">
                            <img id="modalFotoPersonal" src="" alt="Foto"
                                class="w-full h-full object-cover hidden"
                                onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');" />
                            <div id="modalFotoPlaceholder" class="flex flex-col items-center justify-center text-gray-300 gap-1">
                                <i class='bx bx-user text-4xl'></i>
                                <span class="text-[10px]">Sin foto</span>
                            </div>
                        </div>
                    </div>
                    {{-- Campos --}}
                    <div id="modalInfoPersonal" class="grid grid-cols-2 md:grid-cols-3 gap-3 flex-1"></div>
                </div>
            </div>

            {{-- Sección 2: Historial de ceses --}}
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Historial de Ingresos / Ceses</p>
                <div id="modalHistorialCeses">
                    <div class="flex items-center justify-center gap-2 py-6 text-gray-400 text-sm">
                        <i class='bx bx-loader-alt bx-spin'></i> Cargando...
                    </div>
                </div>
            </div>

            {{-- Sección 3: Historial de tareajes --}}
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Historial de Tareajes</p>
                <div id="modalHistorialTareajes">
                    <div class="flex items-center justify-center gap-2 py-6 text-gray-400 text-sm">
                        <i class='bx bx-loader-alt bx-spin'></i> Cargando...
                    </div>
                </div>
            </div>

            {{-- Sección 4: Lista negra --}}
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Lista Negra</p>
                <div id="modalHistorialListaNegra">
                    <div class="flex items-center justify-center gap-2 py-6 text-gray-400 text-sm">
                        <i class='bx bx-loader-alt bx-spin'></i> Cargando...
                    </div>
                </div>
            </div>

        </div>

        {{-- Footer modal --}}
        <div class="flex items-center justify-end gap-2 px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50 rounded-b-xl">
            <span class="text-xs text-gray-400 mr-auto">Exportar ficha individual</span>
            <button id="btnExportDetalleExcel"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-green-300 bg-green-50 text-green-700 text-xs font-semibold hover:bg-green-600 hover:text-white hover:border-green-600 transition-colors">
                <i class='bx bx-file text-base'></i> Excel
            </button>
            <button id="btnExportDetallePdf"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-300 bg-red-50 text-red-700 text-xs font-semibold hover:bg-red-600 hover:text-white hover:border-red-600 transition-colors">
                <i class='bx bxs-file-pdf text-base'></i> PDF
            </button>
        </div>

    </div>
</div>

@endsection

@vite(['resources/js/functions/reporte_personal.js'])
@section('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.1/dist/jspdf.plugin.autotable.min.js"></script>
    <script>
        window.logoUrl = "{{ asset('images/logo_sol.png') }}";
    </script>
@endsection
