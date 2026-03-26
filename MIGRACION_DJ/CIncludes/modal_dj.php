<!-- Tailwind CSS para el estilo moderno -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    important: true,
  }
</script>

<style>
    #modalDJ .modal-dialog {
        width: 95% !important;
        max-width: 1200px !important;
        margin: 30px auto !important;
    }
    .tagify {
        width: 100%;
    }
    /* Estilos para evitar conflictos con Bootstrap 3 */
    #modalDJ input, #modalDJ select, #modalDJ textarea {
        color: #333 !important;
    }
</style>

<!-- Modal DJ Formulario -->
<div class="modal fade" id="modalDJ" tabindex="-1" role="dialog" aria-labelledby="modalDJLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content !rounded-xl overflow-hidden border-none shadow-2xl">
            <div class="modal-header !bg-gradient-to-r !from-blue-800 !to-indigo-900 !py-4 !px-6 flex justify-between items-center">
                <h4 class="modal-title !text-white !font-bold !text-xl flex items-center gap-2" id="modalDJLabel">
                    <i class="fa fa-file-text"></i> Declaración Jurada de Datos - Gruposolmar
                </h4>
                <button type="button" class="close !text-white !opacity-80 hover:!opacity-100 !text-3xl !m-0" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body !p-0 bg-slate-50">
                <form id="formDJ" action="ajax_gestion_dj.php?action=save_dj_completo" method="POST" class="p-6">
                    <!-- Formulario dividido en secciones (similar al Blade original) -->
                    <!-- NOTA: He resumido la estructura para brevedad, conservando la lógica de secciones -->
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <!-- Foto y DNI -->
                        <div class="md:col-span-1 bg-white p-4 rounded-lg shadow-sm border border-slate-200 text-center">
                            <label class="block text-sm font-semibold text-slate-700 mb-3 border-b pb-2">Identidad</label>
                            <div class="relative group mx-auto mb-4 w-32 h-40 border-2 border-dashed border-slate-300 rounded flex items-center justify-center overflow-hidden">
                                <img id="photoPreview" src="assets/img/user.png" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer" onclick="document.getElementById('inputPhoto').click()">
                                    <i class="fa fa-camera text-white text-2xl"></i>
                                </div>
                            </div>
                            <input type="file" id="inputPhoto" name="photo" class="hidden" accept="image/*">
                            <p class="text-xs text-slate-500 mb-2">JPG o PNG (máx. 2MB)</p>
                            <button type="button" id="btnConsultarDNI" class="w-full py-2 bg-blue-100 text-blue-700 rounded font-medium hover:bg-blue-200 transition-colors text-sm">
                                <i class="fa fa-search"></i> Consultar DNI
                            </button>
                        </div>

                        <!-- Datos Personales -->
                        <div class="md:col-span-3 bg-white p-5 rounded-lg shadow-sm border border-slate-200">
                             <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase">Apellido Paterno</label>
                                    <input type="text" name="APEL_1" id="APEL_1" readonly class="w-full p-2 bg-slate-50 border border-slate-200 rounded text-slate-700">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase">Apellido Materno</label>
                                    <input type="text" name="APEL_2" id="APEL_2" readonly class="w-full p-2 bg-slate-50 border border-slate-200 rounded text-slate-700">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase">Nombres</label>
                                    <input type="text" name="NOMBRES" id="NOMBRES" readonly class="w-full p-2 bg-slate-50 border border-slate-200 rounded text-slate-700">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase">DNI / Documento</label>
                                    <input type="text" name="PERS_DNI" id="PERS_DNI" readonly class="w-full p-2 bg-slate-50 border border-slate-200 rounded text-slate-700">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase">Correo Electrónico</label>
                                    <input type="email" name="PERS_EMAIL" id="PERS_EMAIL" class="w-full p-2 border border-blue-200 rounded focus:ring-2 focus:ring-blue-400 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase">Celular / Teléfono</label>
                                    <input type="text" name="PERS_TELEFONO" id="PERS_TELEFONO" class="w-full p-2 border border-blue-200 rounded focus:ring-2 focus:ring-blue-400 outline-none">
                                </div>
                             </div>
                             
                             <div class="mt-6 border-t pt-4">
                                <label class="block text-sm font-semibold text-slate-700 mb-4 uppercase tracking-wider">Dirección Actual</label>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500">Departamento</label>
                                        <select id="selDept" name="DEPT" class="w-full p-2 border border-slate-300 rounded"></select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500">Provincia</label>
                                        <select id="selProv" name="PROV" class="w-full p-2 border border-slate-300 rounded"></select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500">Distrito</label>
                                        <select id="selDist" name="DIST" class="w-full p-2 border border-slate-300 rounded"></select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500">Dirección Domiciliaria</label>
                                    <input type="text" name="DIRECCION" id="DIRECCION" class="w-full p-2 border border-slate-300 rounded">
                                </div>
                             </div>
                        </div>
                    </div>

                    <!-- Secciones Adicionales (Resumidas para el MVP de migración) -->
                    <div class="bg-white p-5 rounded-lg shadow-sm border border-slate-200 mb-6">
                        <label class="block text-sm font-semibold text-slate-700 mb-4 uppercase tracking-wider border-b pb-2">Información Académica</label>
                         <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase">Grado de Instrucción</label>
                                <select id="selGrado" name="GRADO" class="w-full p-2 border border-slate-300 rounded focus:ring-2 focus:ring-blue-400 outline-none"></select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase">Institución</label>
                                <select id="selInstitucion" name="INSTITUCION" class="w-full p-2 border border-slate-300 rounded focus:ring-2 focus:ring-blue-400 outline-none"></select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase">Carrera / Especialidad</label>
                                <select id="selCarrera" name="CARRERA" class="w-full p-2 border border-slate-300 rounded focus:ring-2 focus:ring-blue-400 outline-none"></select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase">Año de Egreso</label>
                                <input type="number" id="PERS_ANIO_EGRESO" name="PERS_ANIO_EGRESO" placeholder="AAAA" class="w-full p-2 border border-slate-300 rounded focus:ring-2 focus:ring-blue-400 outline-none">
                            </div>
                         </div>
                    </div>

                    <!-- Contacto de Emergencia -->
                    <div class="bg-white p-5 rounded-lg shadow-sm border border-slate-200 mb-8">
                        <label class="block text-sm font-semibold text-slate-700 mb-4 uppercase tracking-wider border-b pb-2">Contacto de Emergencia</label>
                         <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500">Nombre del Contacto</label>
                                <input type="text" name="PERS_NOMCONTACTO" id="PERS_NOMCONTACTO" class="w-full p-2 border border-slate-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500">Teléfono de Emergencia</label>
                                <input type="text" name="PERS_NROEMERGENCIA" id="PERS_NROEMERGENCIA" class="w-full p-2 border border-slate-300 rounded">
                            </div>
                         </div>
                    </div>

                    <div class="flex justify-between items-center bg-slate-100 p-4 -mx-6 -mb-6 border-t">
                         <button type="button" id="btnPreviewPDF" class="px-6 py-3 bg-slate-600 text-white rounded-lg font-bold hover:bg-slate-700 transition-all flex items-center gap-2">
                            <i class="fa fa-file-pdf-o"></i> Previsualizar PDF (RH-02)
                         </button>
                         <div class="flex gap-3">
                            <button type="button" class="px-6 py-3 bg-slate-300 text-slate-700 rounded-lg font-bold hover:bg-slate-400 transition-all" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="px-8 py-3 bg-blue-700 text-white rounded-lg font-bold hover:bg-blue-800 transition-all shadow-lg hover:shadow-blue-500/30">
                                <i class="fa fa-save"></i> Guardar Todo
                            </button>
                         </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
