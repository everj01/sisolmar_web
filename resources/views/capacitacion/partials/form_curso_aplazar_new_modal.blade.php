{{-- Modal Extender plazo (vista nueva): misma lógica que el modal aplazar original,
      estilo moderno. IDs con prefijo napl. --}}
<div id="modalAplazarCursoNew" class="contents">
    <div x-data="formAplazarNew()" id="formAplazarNewRoot" x-init="init()"
        @close-modal-aplazar-new.window="showModal = false">

        <div x-show="showModal" x-cloak class="fixed inset-0 z-[120] flex items-center justify-center p-4"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            style="background: rgba(36,39,70,0.45);">

            <div class="relative flex flex-col shadow-2xl rounded-2xl overflow-hidden w-full max-w-md border border-default-200 bg-white max-h-[90vh]"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0">

                {{-- Loader total --}}
                <div x-show="cargando" x-transition.opacity
                    class="absolute inset-0 z-30 bg-white flex flex-col items-center justify-center gap-4 p-8 text-center">
                    <span class="w-12 h-12 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></span>
                    <p class="text-base font-bold text-default-900">Cargando programación vigente…</p>
                </div>

                {{-- Header --}}
                <div class="px-6 pt-5 pb-4 border-b border-default-100 bg-gradient-to-r from-white to-default-50/40 shrink-0">
                    <div class="flex justify-between items-start gap-4">
                        <div class="flex items-start gap-3">
                            <div class="w-11 h-11 rounded-xl bg-emerald-500/10 flex items-center justify-center shrink-0">
                                <i class="ti ti-clock-plus text-xl text-emerald-600"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-default-900">Extender plazo</h3>
                                <p class="text-sm text-default-500 mt-0.5 font-semibold" x-text="cursoNombre"></p>
                            </div>
                        </div>
                        <button type="button" @click="cerrar()"
                            class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-default-400 hover:text-default-700 hover:bg-default-100 transition-colors">
                            <i class="ti ti-x text-lg"></i>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 bg-white">
                    <div x-show="error" class="mb-4 text-sm font-semibold text-red-600 bg-red-50 border border-red-100 rounded-lg px-4 py-3" x-text="error"></div>

                    <div x-show="programacion" class="flex flex-col gap-4">
                        <div class="bg-default-50/60 border border-default-100 rounded-xl p-4">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-default-500 mb-2">Programación vigente</p>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <p class="text-[11px] text-default-400 font-medium">Periodo</p>
                                    <p class="font-bold text-default-800" x-text="programacion.periodo || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] text-default-400 font-medium">Código</p>
                                    <p class="font-mono font-bold text-primary" x-text="programacion.codigo_programacion"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] text-default-400 font-medium">Inicio</p>
                                    <p class="font-bold text-default-800" x-text="fmtFecha(programacion.fecha_inicio)"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] text-default-400 font-medium">Fin actual</p>
                                    <p class="font-bold text-red-600" x-text="fmtFecha(programacion.fecha_final)"></p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="naplNuevaFin" class="text-sm font-medium text-gray-800 inline-block mb-1">Nueva fecha de fin <span class="text-red-500">*</span></label>
                            <input id="naplNuevaFin" type="date" x-model="fechaNuevaFin" :min="fechaMinima"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary/50">
                            <p x-show="errorFecha" class="mt-1 text-sm text-red-600" x-text="errorFecha"></p>
                        </div>

                        {{-- Feedback de extensión --}}
                        <div x-show="fechaValida && extensionDias > 0" x-transition class="rounded-xl border border-emerald-200 bg-emerald-50/60 px-4 py-3 flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg bg-emerald-500/10 flex items-center justify-center shrink-0">
                                <i class="ti ti-calendar-plus text-lg text-emerald-600"></i>
                            </div>
                            <p class="text-sm text-emerald-800">
                                El curso estará disponible hasta el <strong x-text="fmtFecha(fechaNuevaFin)"></strong>
                                (<strong x-text="extensionDias"></strong> día(s) más).
                            </p>
                        </div>

                        <div class="rounded-xl border border-blue-100 bg-blue-50/50 px-4 py-3 flex items-start gap-3">
                            <i class="ti ti-info-circle text-lg text-blue-500 shrink-0 mt-0.5"></i>
                            <p class="text-xs text-blue-700 leading-5">Al aplicar se actualiza la <strong>fecha fin del curso y de las inscripciones de los matriculados</strong> (incluido Moodle).</p>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end items-center gap-2 px-6 py-4 border-t border-default-100 bg-default-50/50 shrink-0">
                    <button type="button" @click="cerrar()" :disabled="cargando || guardando"
                        class="h-9 px-4 inline-flex items-center bg-white border border-default-200 text-default-700 text-sm font-medium rounded-lg shadow-sm hover:bg-default-50 transition disabled:opacity-50">
                        Cancelar
                    </button>
                    <button type="button" @click="guardarExtension()" :disabled="cargando || guardando || !fechaValida || !fechaNuevaFin"
                        class="h-9 px-5 inline-flex items-center gap-1.5 bg-emerald-600 text-white text-sm font-medium rounded-lg shadow-sm hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="ti ti-clock-plus" x-show="!guardando"></i>
                        <span x-text="guardando ? 'Guardando…' : 'Aplicar extensión'"></span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
