{{-- ============================================================
     MODAL: Reporte — Datos Generales del Personal
     Uso: @include('file_control.rrhh.partials_modal_reporte')
     ============================================================ --}}

<div id="modalReporte"
     class="fixed inset-0 z-[999] hidden items-start justify-center bg-black/45 overflow-y-auto p-4"
     role="dialog" aria-modal="true" aria-labelledby="modalReporteTitulo">

    <div class="relative w-full max-w-[1400px] my-4 bg-white rounded-xl border border-gray-200 flex flex-col shadow-lg">

        {{-- HEADER --}}
        <div class="flex items-center justify-between px-5 py-2.5 border-b border-gray-200">
            {{-- Título centrado estilo original --}}
            <div class="flex-1 text-center">
                <h2 id="modalReporteTitulo"
                    class="text-sm font-bold text-primary underline underline-offset-2 tracking-wide uppercase">
                    Datos Básicos del Personal
                </h2>
                <p class="text-xs font-semibold text-gray-700 mt-0.5">Sol Security S.A.C.</p>
            </div>
            <button type="button" id="btnCerrarReporte"
                    class="text-gray-400 hover:text-gray-700 text-xl leading-none w-7 h-7 flex items-center justify-center rounded hover:bg-gray-100 transition-colors flex-shrink-0">
                &times;
            </button>
        </div>

        {{-- FILTROS --}}
        <div class="flex flex-wrap gap-x-3 gap-y-2 items-end px-5 py-2.5 bg-gray-50/70 border-b border-gray-200">

            {{-- Sucursal --}}
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Sucursal</label>
                @php $sucursalesFiltradas = array_slice($sucursales, 1); @endphp
                <select id="rptSucursal"
                        class="h-7 px-2 text-xs border border-gray-300 py-0 rounded-md bg-white text-gray-700 focus:ring-1 focus:ring-primary focus:border-primary w-32">
                    @if(count($sucursalesFiltradas) > 1)
                        <option value="">Todas</option>
                    @endif
                    @foreach ($sucursalesFiltradas as $sucursal)
                        <option value="{{ $sucursal->codigo }}">{{ $sucursal->abreviatura }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tipo --}}
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Tipo</label>
                <select id="rptTipoPer"
                        class="h-7 px-2 text-xs border border-gray-300 py-0 rounded-md bg-white text-gray-700 focus:ring-1 focus:ring-primary focus:border-primary w-44">
                    @if($tipoPerLimitar == 0)
                        <option value="">Todos</option>
                        <option value="01">Operativo 4°</option>
                        <option value="03">Operativo 5°</option>
                        <option value="02">Administrativo 4°</option>
                        <option value="05">Administrativo 5°</option>
                        <option value="06">Especial</option>
                    @elseif($tipoPerLimitar == 1)
                        <option value="02">Administrativo 4°</option>
                        <option value="05">Administrativo 5°</option>
                    @elseif($tipoPerLimitar == 2)
                        <option value="01">Operativo 4°</option>
                        <option value="03">Operativo 5°</option>
                    @endif
                </select>
            </div>

            {{-- Ap. Paterno --}}
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Ap. Paterno</label>
                <input type="text" id="rptApPaterno" placeholder="Buscar..." autocomplete="off"
                       class="h-7 px-2.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary w-40 uppercase placeholder:normal-case placeholder:text-gray-300">
            </div>

            {{-- Doc. Identidad --}}
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Doc. Identidad</label>
                <input type="text" id="rptDocIdentidad" placeholder="DNI..." autocomplete="off" maxlength="12"
                       class="h-7 px-2.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary w-28 placeholder:text-gray-300">
            </div>

            {{-- Estado --}}
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Estado</label>
                <div class="flex border border-gray-300 rounded-md overflow-hidden h-7 text-xs">
                    <label id="rptLblVig"
                           class="flex items-center gap-1.5 px-3 cursor-pointer bg-primary text-white font-medium transition-colors">
                        <input type="radio" name="rptVigente" value="1" checked class="w-3 h-3 accent-white"> Vigente
                    </label>
                    <label id="rptLblNoVig"
                           class="flex items-center gap-1.5 px-3 cursor-pointer bg-white text-gray-500 border-l border-gray-300 transition-colors">
                        <input type="radio" name="rptVigente" value="0" class="w-3 h-3 accent-primary"> No vigente
                    </label>
                </div>
            </div>

            {{-- Descargar con --}}
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Descargar con</label>
                <div class="flex border border-gray-300 rounded-md overflow-hidden h-7 text-xs">
                    <label class="flex items-center gap-1.5 px-3 cursor-pointer bg-white text-gray-600 border-r border-gray-300">
                        <input type="radio" name="rptDescCon" value="codigo" class="w-3 h-3 accent-primary"> Código
                    </label>
                    <label class="flex items-center gap-1.5 px-3 cursor-pointer bg-primary text-white font-medium" id="rptLblDescDoc">
                        <input type="radio" name="rptDescCon" value="doc" checked class="w-3 h-3 accent-white"> N° Doc.
                    </label>
                </div>
            </div>

            {{-- Buscar --}}
            <button type="button" id="btnBuscarReporte"
                    class="h-7 flex items-center gap-1.5 px-4 text-xs rounded-md bg-primary text-white hover:bg-primary/90 active:scale-95 transition-all font-medium self-end">
                <i class='bx bx-search text-sm'></i> Buscar
            </button>
        </div>

        {{-- BARRA DE ACCIONES --}}
        <div class="flex flex-wrap items-center gap-2 px-5 py-1.5 border-b border-gray-100 bg-white">
            <span id="rptSelInfo" class="text-xs text-gray-400 min-w-[90px]">0 seleccionados</span>
            <span class="text-gray-200">|</span>
            <span class="text-xs text-gray-500 font-medium">Descargar:</span>
            <button type="button" id="btnRptDescFoto"
                    class="flex items-center gap-1 px-2.5 py-1 text-xs border border-violet-300 text-violet-700 bg-violet-50 rounded-md hover:bg-violet-500 hover:text-white hover:border-violet-500 transition-colors">
                <i class='bx bx-image-alt text-sm'></i> Foto
            </button>
            <button type="button" id="btnRptDescDNI"
                    class="flex items-center gap-1 px-2.5 py-1 text-xs border border-sky-300 text-sky-700 bg-sky-50 rounded-md hover:bg-sky-500 hover:text-white hover:border-sky-500 transition-colors">
                <i class='bx bx-id-card text-sm'></i> DNI
            </button>
            <button type="button" id="btnRptDescCUL"
                    class="flex items-center gap-1 px-2.5 py-1 text-xs border border-emerald-300 text-emerald-700 bg-emerald-50 rounded-md hover:bg-emerald-500 hover:text-white hover:border-emerald-500 transition-colors">
                <i class='bx bx-check-shield text-sm'></i> CUL
            </button>
            <span class="text-gray-200">|</span>
            <span class="text-xs text-gray-500 font-medium">Exportar:</span>
            <button type="button" id="btnRptExportPDF"
                    class="flex items-center gap-1 px-2.5 py-1 text-xs border border-rose-300 text-rose-700 bg-rose-50 rounded-md hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-colors">
                <i class='bx bxs-file-pdf text-sm'></i> PDF
            </button>
            <button type="button" id="btnRptExportExcel"
                    class="flex items-center gap-1 px-2.5 py-1 text-xs border border-green-300 text-green-700 bg-green-50 rounded-md hover:bg-green-600 hover:text-white hover:border-green-600 transition-colors">
                <i class='bx bxs-file text-sm'></i> Excel
            </button>
        </div>

        {{-- TABLA --}}
        <div class="overflow-x-auto px-4 py-2 min-h-[300px]">
            <table class="w-full border-collapse text-[11px]" id="rptTabla" style="table-layout:auto">
                <thead>
                    <tr class="border-b-2 border-gray-300">
                        <th class="py-1.5 px-1 text-center whitespace-nowrap">
                            <input type="checkbox" id="rptCbAll" class="w-3.5 h-3.5 accent-primary cursor-pointer">
                        </th>
                        <th class="py-1.5 px-1 text-center text-[10px] font-bold text-gray-600 whitespace-nowrap">It.</th>
                        <th class="py-1.5 px-1 text-left   text-[10px] font-bold text-gray-600 whitespace-nowrap">Sucur.</th>
                        <th class="py-1.5 px-1 text-left   text-[10px] font-bold text-gray-600 whitespace-nowrap">Cód.</th>
                        <th class="py-1.5 px-1 text-left   text-[10px] font-bold text-gray-600 whitespace-nowrap min-w-[140px]">Apellidos y Nombres</th>
                        <th class="py-1.5 px-1 text-left   text-[10px] font-bold text-gray-600 whitespace-nowrap">País</th>
                        <th class="py-1.5 px-1 text-left   text-[10px] font-bold text-gray-600 whitespace-nowrap">Tipo Doc.</th>
                        <th class="py-1.5 px-1 text-left   text-[10px] font-bold text-gray-600 whitespace-nowrap">Doc. Iden.</th>
                        <th class="py-1.5 px-1 text-left   text-[10px] font-bold text-gray-600 whitespace-nowrap">Caduca Doc.</th>
                        <th class="py-1.5 px-1 text-center text-[10px] font-bold text-gray-600 whitespace-nowrap">Sexo</th>
                        <th class="py-1.5 px-1 text-center text-[10px] font-bold text-gray-600 whitespace-nowrap">Edad</th>
                        <th class="py-1.5 px-1 text-left   text-[10px] font-bold text-gray-600 whitespace-nowrap min-w-[130px]">Email</th>
                        <th class="py-1.5 px-1 text-left   text-[10px] font-bold text-gray-600 whitespace-nowrap">Teléfonos</th>
                        <th class="py-1.5 px-1 text-left   text-[10px] font-bold text-gray-600 whitespace-nowrap min-w-[150px]">Dirección Actual</th>
                        <th class="py-1.5 px-1 text-left   text-[10px] font-bold text-gray-600 whitespace-nowrap">Fecha Ingreso</th>
                        <th class="py-1.5 px-1 text-left   text-[10px] font-bold text-gray-600 whitespace-nowrap min-w-[100px]">Cargo</th>
                        <th class="py-1.5 px-1 text-left   text-[10px] font-bold text-gray-600 whitespace-nowrap">Tipo Pers.</th>
                        <th class="py-1.5 px-1 text-left   text-[10px] font-bold text-gray-600 whitespace-nowrap">Caduca EMO</th>
                        <th class="py-1.5 px-1 text-left   text-[10px] font-bold text-gray-600 whitespace-nowrap">Fin Contrato</th>
                    </tr>
                </thead>
                <tbody id="rptTbody">
                    <tr>
                        <td colspan="19" class="text-center py-12 text-gray-300 italic text-xs">
                            Usa el buscador para cargar registros
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- FOOTER --}}
        <div class="flex items-center justify-between px-5 py-2 border-t border-gray-100 bg-gray-50/60 rounded-b-xl">
            <span id="rptPagInfo" class="text-xs text-gray-400">—</span>
            <div class="flex items-center gap-1.5">
                <button id="rptBtnPrev"
                        class="w-7 h-7 flex items-center justify-center text-xs border border-gray-300 rounded-md bg-white hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed">
                    &#8592;
                </button>
                <span id="rptPageNum" class="text-xs font-medium text-gray-700 min-w-[20px] text-center">1</span>
                <button id="rptBtnNext"
                        class="w-7 h-7 flex items-center justify-center text-xs border border-gray-300 rounded-md bg-white hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed">
                    &#8594;
                </button>
                <span class="text-xs text-gray-400 ml-2">Mostrar</span>
                <select id="rptPageSize"
                        class="h-7 text-xs border border-gray-300 rounded-md px-1.5 bg-white focus:ring-1 focus:ring-primary focus:border-primary">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>

    </div>
</div>