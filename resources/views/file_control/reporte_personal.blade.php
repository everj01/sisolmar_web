@extends('layouts.vertical', ['title' => 'Reporte Personal'])

@section('css')
<style>
    /* ── Tabla ──────────────────────────────────────────────────────── */
    #tblReportePersonal .tabulator-cell { font-size: 0.8125rem; }
    #tblReportePersonal .tabulator-row {
        transition: background-color .15s ease, box-shadow .15s ease;
        cursor: pointer;
    }
    /* Base: todas las filas blancas (sin zebra) */
    #tblReportePersonal .tabulator-row { background-color: #ffffff; }
    /* Cesados: rojo. Lista negra: gris (tiene prioridad sobre cesado). */
    #tblReportePersonal .tabulator-row.rep-cesado { background-color: #fee2e2; }
    #tblReportePersonal .tabulator-row.rep-ln     { background-color: #e5e7eb; }

    #tblReportePersonal .tabulator-row:hover {
        background-color: #eef4ff !important;
        box-shadow: inset 3px 0 0 0 #2563eb;
    }
    #tblReportePersonal .tabulator-row.rep-cesado:hover {
        background-color: #fecaca !important;
        box-shadow: inset 3px 0 0 0 #dc2626;
    }
    #tblReportePersonal .tabulator-row.rep-ln:hover {
        background-color: #d5dae0 !important;
        box-shadow: inset 3px 0 0 0 #475569;
    }
    #tblReportePersonal .tabulator-header {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 2px solid #e2e8f0;
    }
    #tblReportePersonal .tabulator-header .tabulator-col {
        background: transparent;
        border-right-color: #e9eef5;
    }
    #tblReportePersonal .tabulator-header .tabulator-col .tabulator-header-content {
        font-weight: 700;
        color: #475569;
        font-size: .7rem;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    #tblReportePersonal .tabulator-placeholder-wrapper {
        display: flex; align-items: center; justify-content: center;
        gap: .5rem; color: #94a3b8; font-size: .875rem;
    }

    /* ── Cards de totales ───────────────────────────────────────────── */
    .rep-card {
        position: relative;
        overflow: hidden;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: .75rem;
        padding: .65rem .85rem;
        min-width: 118px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
        transition: box-shadow .2s ease, transform .2s ease, border-color .2s ease;
    }
    .rep-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, .10);
    }
    .rep-card .rep-label {
        display: flex; align-items: center; gap: .35rem;
        font-size: 9px; font-weight: 800; letter-spacing: .07em;
        text-transform: uppercase;
    }
    .rep-card .rep-value {
        display: block;
        font-size: 1.35rem; line-height: 1.15; font-weight: 800;
        font-variant-numeric: tabular-nums;
    }
    .rep-bar {
        margin-top: .35rem;
        height: 5px; width: 100%;
        background: #e5e7eb;
        border-radius: 999px;
        overflow: hidden;
    }
    .rep-bar > i {
        display: block; height: 100%; width: 0;
        border-radius: 999px;
        transition: width .8s cubic-bezier(.4, 0, .2, 1);
    }

    /* ── Barra de filtros ───────────────────────────────────────────── */
    .rep-toolbar {
        background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
        border: 1px solid #e2e8f0;
        border-radius: .75rem;
        padding: .75rem .85rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }
    .rep-toolbar .form-select,
    .rep-toolbar input[type="text"] {
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: .5rem;
        font-size: .8125rem;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    /* Mayor especificidad: el plugin @tailwindcss/forms pisa pl-9/pr-9 */
    .rep-toolbar input[type="text"] {
        padding: .5rem 2.25rem;
    }
    .rep-toolbar .form-select:focus,
    .rep-toolbar input[type="text"]:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
        outline: none;
    }

    /* ── Fila de resumen bajo la tabla ──────────────────────────────── */
    .rep-footline {
        border-top: 1px dashed #e5e7eb;
        margin-top: .75rem;
        padding-top: .65rem;
    }
    .rep-footline .rep-info {
        font-size: .75rem; color: #64748b;
        font-variant-numeric: tabular-nums;
    }
    .rep-footline .rep-info b { color: #0f172a; font-weight: 700; }
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
                <div class="flex flex-wrap gap-2.5">
                    {{-- Total --}}
                    <div class="rep-card">
                        <div class="rep-label text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Total
                        </div>
                        <span id="cntTotal" class="rep-value text-slate-800">0</span>
                        <div class="rep-bar bg-slate-100"><i id="barTotal" class="bg-slate-500"></i></div>
                    </div>

                    {{-- Vigentes --}}
                    <div class="rep-card" style="border-color:#bbf7d0;">
                        <div class="rep-label text-emerald-600">
                            <i class='bx bx-check-circle'></i> Vigentes
                        </div>
                        <span id="cntVigentes" class="rep-value text-emerald-700">0</span>
                        <div class="rep-bar bg-emerald-100"><i id="barVigentes" class="bg-emerald-500"></i></div>
                    </div>

                    {{-- Cesados --}}
                    <div class="rep-card" style="border-color:#fecaca;">
                        <div class="rep-label text-red-500">
                            <i class='bx bx-x-circle'></i> Cesados
                        </div>
                        <span id="cntCesados" class="rep-value text-red-600">0</span>
                        <div class="rep-bar bg-red-100"><i id="barCesados" class="bg-red-400"></i></div>
                    </div>

                    {{-- Lista Negra --}}
                    <div class="rep-card" style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);border-color:#0f172a;">
                        <div class="rep-label text-slate-400">
                            <i class='bx bx-block'></i> Lista Negra
                        </div>
                        <span id="cntListaNegra" class="rep-value text-white">0</span>
                        <div class="rep-bar bg-white/15"><i id="barLN" class="bg-amber-400"></i></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Búsqueda + Filtros (barra unificada) --}}
        <div class="w-full px-5 pt-4">
            <div class="rep-toolbar">
                {{-- Fila 1: búsqueda + acciones --}}
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative flex-1 min-w-[230px] max-w-[420px]">
                        <i class='bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg pointer-events-none'></i>
                        <input type="text" id="buscarRep"
                            placeholder="Buscar por nombre, apellido, código o DNI..."
                            class="w-full text-sm" autocomplete="off" />
                        <button type="button" id="btnLimpiarBusqueda" title="Limpiar búsqueda"
                            class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-600 transition-colors">
                            <i class='bx bx-x-circle text-lg'></i>
                        </button>
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <button type="button" id="btnLimpiarFiltros" title="Restablecer todos los filtros"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-500 text-xs font-semibold hover:bg-gray-100 hover:text-gray-700 hover:border-gray-400 transition-colors">
                            <i class='bx bx-reset text-base'></i> Limpiar
                        </button>
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

                {{-- Fila 2: filtros del servidor --}}
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 mt-3 pt-3 border-t border-slate-200">

                    {{-- Sucursal --}}
                    <div class="flex items-center gap-2">
                        <i class='bx bx-building text-gray-400 text-lg'></i>
                        <label for="filtroSucursal" class="text-sm font-medium text-gray-600">Sucursal</label>
                        <select id="filtroSucursal" class="form-select px-3 py-1.5">
                            <option value="">Todas</option>
                            @foreach(array_slice($sucursales, 1) as $suc)
                                <option value="{{ $suc->codigo }}">{{ $suc->abreviatura }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tipo --}}
                    <div class="flex items-center gap-2">
                        <i class='bx bx-category text-gray-400 text-lg'></i>
                        <label for="filtroTipo" class="text-sm font-medium text-gray-600">Tipo</label>
                        <select id="filtroTipo" class="form-select px-3 py-1.5 min-w-[150px]">
                            <option value="">Todos</option>
                            <option value="OPER 4°">Operativo 4°</option>
                            <option value="OPER 5°" selected>Operativo 5°</option>
                            <option value="ESP">Especiales</option>
                        </select>
                    </div>

                    {{-- Vigencia --}}
                    <div class="flex items-center gap-2">
                        <i class='bx bx-time text-gray-400 text-lg'></i>
                        <label for="filtroVigencia" class="text-sm font-medium text-gray-600">Vigencia</label>
                        <select id="filtroVigencia" class="form-select px-3 py-1.5">
                            <option value="">TODOS</option>
                            <option value="SI" selected>SI</option>
                            <option value="NO">NO</option>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="w-full px-5 pb-4 pt-4">
            <div id="tblReportePersonal" class="w-full rounded-lg overflow-hidden border border-gray-200"></div>

            <div class="rep-footline flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <label for="pageSizeRep" class="text-sm text-gray-600">Mostrar</label>
                    <select id="pageSizeRep" class="w-20 px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-white">
                        <option value="10">10</option>
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="text-sm text-gray-600">por página</span>
                </div>
                <div class="rep-info" id="tblInfo"></div>
            </div>
        </div>

    </div>
</div>

{{-- Modal: Ver detalles --}}
<div id="modalDetallePersonal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl mx-4 flex flex-col max-h-[90vh]">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0 bg-gradient-to-r from-slate-50 to-white">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center flex-shrink-0">
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
            <button id="btnCerrarModalDetalle" title="Cerrar (Esc)"
                class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                <i class='bx bx-x text-2xl'></i>
            </button>
        </div>

        {{-- Body --}}
        <div class="overflow-y-auto flex-1 px-6 py-5 space-y-6">

            {{-- Sección 1: Información del personal --}}
            <div>
                <p class="text-[10px] font-bold text-primary uppercase tracking-widest mb-3 flex items-center gap-2">
                    <span class="w-4 h-0.5 bg-primary rounded-full"></span> Información del Personal
                </p>
                <div class="flex gap-4">
                    {{-- Foto --}}
                    <div class="flex-shrink-0">
                        <div class="w-24 h-28 rounded-lg border-2 border-gray-200 overflow-hidden bg-gradient-to-b from-gray-50 to-gray-100 flex items-center justify-center shadow-inner">
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
                <p class="text-[10px] font-bold text-primary uppercase tracking-widest mb-3 flex items-center gap-2">
                    <span class="w-4 h-0.5 bg-primary rounded-full"></span> Historial de Ingresos / Ceses
                </p>
                <div id="modalHistorialCeses">
                    <div class="flex items-center justify-center gap-2 py-6 text-gray-400 text-sm">
                        <i class='bx bx-loader-alt bx-spin'></i> Cargando...
                    </div>
                </div>
            </div>

            {{-- Sección 3: Historial de tareajes --}}
            <div>
                <p class="text-[10px] font-bold text-primary uppercase tracking-widest mb-3 flex items-center gap-2">
                    <span class="w-4 h-0.5 bg-primary rounded-full"></span> Historial de Tareajes
                </p>
                <div id="modalHistorialTareajes">
                    <div class="flex items-center justify-center gap-2 py-6 text-gray-400 text-sm">
                        <i class='bx bx-loader-alt bx-spin'></i> Cargando...
                    </div>
                </div>
            </div>

            {{-- Sección 4: Lista negra --}}
            <div>
                <p class="text-[10px] font-bold text-slate-600 uppercase tracking-widest mb-3 flex items-center gap-2">
                    <span class="w-4 h-0.5 bg-slate-500 rounded-full"></span>
                    <i class='bx bx-block'></i> Lista Negra
                </p>
                <div id="modalHistorialListaNegra">
                    <div class="flex items-center justify-center gap-2 py-6 text-gray-400 text-sm">
                        <i class='bx bx-loader-alt bx-spin'></i> Cargando...
                    </div>
                </div>
            </div>

        </div>

        {{-- Footer modal --}}
        <div class="flex items-center justify-end gap-2 px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-slate-50 rounded-b-xl">
            <span class="text-xs text-gray-400 mr-auto flex items-center gap-1.5">
                <i class='bx bx-export'></i> Exportar ficha individual
            </span>
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
