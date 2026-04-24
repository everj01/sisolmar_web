<div id="modal-biometrico"
    class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-y-auto hidden pointer-events-none">
    <div class="translate-y-10 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 w-full my-8 mx-auto flex flex-col bg-white shadow-sm rounded pointer-events-auto"
         style="max-width: 1100px;">
        <div class="flex flex-col border border-default-200 shadow-sm rounded-lg overflow-hidden">

            <!-- HEADER -->
            <div class="flex justify-between items-center py-3 px-4 border-b border-default-200">
                <div class="flex items-center gap-2">
                    <div style="width:8px;height:8px;border-radius:50%;background:#6366f1;"></div>
                    <h3 class="text-sm font-medium text-default-900" id="modal-bio-title">Biométricos</h3>
                </div>
                <button type="button" class="text-default-400 hover:text-default-700 cursor-pointer" data-hs-overlay="#modal-biometrico">
                    <i class="i-tabler-x text-base"></i>
                </button>
            </div>

            <!-- PESTAÑAS -->
            <div class="flex border-b border-default-200 bg-gray-50">
                <button id="bio-tab-fh" onclick="bioSwitchTab('fh')"
                    class="flex items-center gap-2 px-5 py-3 text-sm font-medium border-b-2 border-indigo-500 text-indigo-600 transition-colors">
                    <i class="fa fa-fingerprint"></i> Firmas y Huellas
                </button>
                <button id="bio-tab-doc" onclick="bioSwitchTab('doc')"
                    class="flex items-center gap-2 px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                    <i class="fa fa-id-card"></i> DOC
                </button>
            </div>

            <!-- BODY -->
            <div class="px-4 py-4 overflow-y-auto" style="max-height:75vh;">

                <!-- PANEL: Firmas y Huellas -->
                <div id="bio-panel-fh" style="display:flex; flex-direction:column; gap:14px;">

                    <!-- HUELLA -->
                    <div style="background:#f8fafc; border-radius:10px; padding:12px;">
                        <div style="display:flex; align-items:center; gap:6px; margin-bottom:10px;">
                            <i class="fa fa-fingerprint" style="color:#6366f1; font-size:13px;"></i>
                            <span style="font-size:12px; font-weight:600; color:#374151; letter-spacing:0.3px;">HUELLA DIGITAL</span>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                            <div>
                                <p style="font-size:10px; color:#9ca3af; margin-bottom:5px; text-align:center; font-weight:500;">ANTIGUO</p>
                                <div id="bio-huella-antigua"></div>
                            </div>
                            <div>
                                <p style="font-size:10px; color:#9ca3af; margin-bottom:5px; text-align:center; font-weight:500;">NUEVO</p>
                                <div id="bio-huella-nueva"></div>
                            </div>
                        </div>
                    </div>

                    <!-- FIRMA -->
                    <div style="background:#f8fafc; border-radius:10px; padding:12px;">
                        <div style="display:flex; align-items:center; gap:6px; margin-bottom:10px;">
                            <i class="fa fa-pen" style="color:#6366f1; font-size:13px;"></i>
                            <span style="font-size:12px; font-weight:600; color:#374151; letter-spacing:0.3px;">FIRMA</span>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                            <div>
                                <p style="font-size:10px; color:#9ca3af; margin-bottom:5px; text-align:center; font-weight:500;">ANTIGUO</p>
                                <div id="bio-firma-antigua"></div>
                            </div>
                            <div>
                                <p style="font-size:10px; color:#9ca3af; margin-bottom:5px; text-align:center; font-weight:500;">NUEVO</p>
                                <div id="bio-firma-nueva"></div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- PANEL: DOC (oculto por defecto) -->
                {{-- <div id="bio-panel-doc" style="display:none;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; align-items:start;"> --}}
                <!-- PANEL: DOC -->
                <div id="bio-panel-doc" style="display:none;">
                    <div style="display:grid; grid-template-columns:3fr 2fr; gap:16px; align-items:start;">
                        <!-- COLUMNA IZQUIERDA: DNI antiguo -->
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px;">
                                <i class="fa fa-id-card" style="color:#6366f1; font-size:13px;"></i>
                                <span style="font-size:12px; font-weight:600; color:#374151;">DNI</span>
                                <span style="font-size:10px; color:#9ca3af; font-weight:500; margin-left:2px;">ANTIGUO</span>
                            </div>
                            <div id="bio-doc-dni-antiguo"></div>
                        </div>

                        <!-- COLUMNA DERECHA: Firma nueva + Huella nueva -->
                        <div style="display:flex; flex-direction:column; gap:12px;">

                            <div>
                                <div style="display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                                    <i class="fa fa-pen" style="color:#6366f1; font-size:12px;"></i>
                                    <span style="font-size:12px; font-weight:600; color:#374151;">FIRMA</span>
                                    <span style="font-size:10px; color:#9ca3af; font-weight:500; margin-left:2px;">NUEVA</span>
                                </div>
                                <div id="bio-doc-firma-nueva"></div>
                            </div>

                            <div>
                                <div style="display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                                    <i class="fa fa-fingerprint" style="color:#6366f1; font-size:12px;"></i>
                                    <span style="font-size:12px; font-weight:600; color:#374151;">HUELLA</span>
                                    <span style="font-size:10px; color:#9ca3af; font-weight:500; margin-left:2px;">NUEVA</span>
                                </div>
                                <div id="bio-doc-huella-nueva"></div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>