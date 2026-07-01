<style>
#fhDropZone.fh-drag-over {
    background: #eff6ff;
    border-color: #3b82f6;
}
</style>

<div id="modalExtFirmaHuella"
    class="hidden fixed inset-0  overflow-x-hidden overflow-y-auto" 
    style="background:rgba(0,0,0,0.5); z-index: 999999999 !important;">
    <div class="relative w-full my-8 mx-auto flex flex-col"
        style="max-width:920px;">
        <div class="flex flex-col border border-gray-200 shadow-xl bg-white rounded-lg">

            {{-- Header --}}
            <div class="flex justify-between items-center py-3 px-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                <div class="flex items-center gap-2">
                    <i class='bx bx-fingerprint text-xl' style="color:#f59e0b;"></i>
                    <h3 class="font-semibold text-gray-800">Extraer Firma y Huella desde PDF DJ</h3>
                </div>
                <button type="button" id="btnCerrarFHModal"
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class='bx bx-x text-2xl'></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-5 space-y-4">

                {{-- PASO 1: Seleccionar archivo --}}
                <div id="fhStep1">
                    <p class="text-sm text-gray-500 mb-3">
                        <i class='bx bx-info-circle mr-1'></i>
                        Adjunta el PDF de la Declaración Jurada (máximo 2 páginas).
                        Se extraerá la firma y huella del área inferior del documento.
                    </p>

                    {{-- Drop zone --}}
                    <div id="fhDropZone"
                        class="border-2 border-dashed border-gray-300 rounded-xl p-10 text-center cursor-pointer hover:border-primary hover:bg-primary/5 transition-all select-none"
                        onclick="document.getElementById('fhInputPdf').click()">
                        <input type="file" id="fhInputPdf" accept=".pdf" class="hidden">
                        <i class='bx bxs-file-pdf text-5xl' style="color:#d1d5db;"></i>
                        <p class="text-base font-medium text-gray-500 mt-2">Haz clic aquí o arrastra el PDF</p>
                        <p class="text-xs text-gray-400 mt-1">Solo archivos PDF &middot; Máximo 2 páginas</p>
                    </div>

                    {{-- Info del archivo --}}
                    <div id="fhFileInfo" class="hidden mt-3">
                        <div class="flex items-center justify-between bg-blue-50 border border-blue-200 rounded-lg px-4 py-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <i class='bx bxs-file-pdf text-red-500 text-lg flex-shrink-0'></i>
                                <div class="min-w-0">
                                    <p id="fhFileName" class="text-sm font-medium text-blue-700 truncate"></p>
                                    <p id="fhPageCount" class="text-xs text-blue-400"></p>
                                </div>
                            </div>
                            <div class="flex gap-2 flex-shrink-0 ml-3">
                                <button type="button" id="fhBtnCambiar"
                                    class="text-xs px-3 py-1.5 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">
                                    Cambiar
                                </button>
                                <button type="button" id="fhBtnPreview"
                                    class="flex items-center gap-1.5 px-4 py-1.5 text-sm bg-primary text-white rounded-lg hover:bg-primary/80 transition-colors">
                                    <i class='bx bx-show'></i>
                                    Previsualizar PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PASO 2: Vista previa --}}
                <div id="fhStep2" class="hidden">
                    <div class="flex justify-between items-center mb-3 flex-wrap gap-2">
                        <p class="text-sm font-medium text-gray-700">
                            <i class='bx bx-show mr-1 text-primary'></i>
                            Vista previa &mdash; el recuadro indica el área de extracción
                        </p>
                        <button type="button" id="fhBtnExtract"
                            class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors"
                            style="background:#f59e0b;">
                            <i class='bx bx-cut'></i>
                            Extraer Firma y Huella
                        </button>
                    </div>
                    <div id="fhPagesContainer"
                        class="overflow-y-auto space-y-4 rounded-lg p-3"
                        style="max-height:64vh;border:1px solid #e5e7eb;background:#f3f4f6;">
                    </div>
                </div>

                {{-- PASO 3: Imágenes extraídas --}}
                <div id="fhStep3" class="hidden">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                        <p class="text-sm font-medium text-gray-700">
                            <i class='bx bx-check-circle mr-1' style="color:#16a34a;"></i>
                            Imágenes extraídas — descárgalas como PNG
                        </p>
                        <button type="button" id="fhBtnReintentar"
                            class="flex items-center gap-1 text-xs px-3 py-1.5 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">
                            <i class='bx bx-arrow-back'></i> Volver a vista previa
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        {{-- Firma --}}
                        <div class="border border-gray-200 rounded-xl overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-2 bg-gray-50 border-b border-gray-200">
                                <span class="text-sm font-medium text-gray-700">
                                    <i class='bx bx-pen mr-1'></i>Firma Registrada
                                </span>
                                <button type="button" id="fhBtnDownloadFirma"
                                    class="flex items-center gap-1 px-3 py-1 text-xs text-white rounded-lg transition-colors"
                                    style="background:#6366f1;">
                                    <i class='bx bx-download'></i> Descargar PNG
                                </button>
                            </div>
                            <div class="p-3 bg-white flex items-center justify-center" style="min-height:130px;">
                                <canvas id="fhCanvasFirma" style="max-width:100%;border:1px solid #f3f4f6;border-radius:4px;"></canvas>
                            </div>
                        </div>

                        {{-- Huella --}}
                        <div class="border border-gray-200 rounded-xl overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-2 bg-gray-50 border-b border-gray-200">
                                <span class="text-sm font-medium text-gray-700">
                                    <i class='bx bx-fingerprint mr-1'></i>Huella Registrada
                                </span>
                                <button type="button" id="fhBtnDownloadHuella"
                                    class="flex items-center gap-1 px-3 py-1 text-xs text-white rounded-lg transition-colors"
                                    style="background:#6366f1;">
                                    <i class='bx bx-download'></i> Descargar PNG
                                </button>
                            </div>
                            <div class="p-3 bg-white flex items-center justify-center" style="min-height:130px;">
                                <canvas id="fhCanvasHuella" style="max-width:100%;border:1px solid #f3f4f6;border-radius:4px;"></canvas>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>