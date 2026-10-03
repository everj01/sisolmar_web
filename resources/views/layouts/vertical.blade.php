@include('layouts.shared/main')

<head>
    @include('layouts.shared/title-meta', ['title' => $title])
    @yield('css')
    @include('layouts.shared/head-css')
</head>

<body class="bg-gray-50 dark:bg-slate-900 transition-colors duration-300">

    <div class="wrapper">

        @include('layouts.shared/sidenav')

        <div id="page-content" class="page-content">

            @include('layouts.shared/topbar')

            <main>
                <!-- Start Content-->
                @yield('content')
            </main>

            @include('layouts.shared/footer')

        </div>

    </div>

<!-- Contenedor Popups Superior Derecho (SIP Demandas) SIN BLOQUEO TEMPORAL -->
    <div id="demandas-toast-container" style="position: fixed; top: 80px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;">
    </div>

    <!-- Popup notificación folios por vencer -->
    <div id="folio-toast"
        style="
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            border-left: 5px solid #f59e0b;
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            min-width: 320px;
            display: none;
            opacity: 1;
            transition: opacity 0.5s ease;
        ">
    </div>


    @include('layouts.shared.footer-scripts')

    @php
        // Pop-ups de notificaciones: solo se muestran UNA VEZ por login.
        // La primera renderización consume el flag; al navegar entre vistas ya no aparecen.
        $notifPopupsAutoshow = !session('notif_popups_ya_mostrados');
        if ($notifPopupsAutoshow) {
            session(['notif_popups_ya_mostrados' => true]);
        }
    @endphp

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            // 🔹 Solo una vez por login (y sin repetir en el mismo navegador)
            if (!@json($notifPopupsAutoshow) || sessionStorage.getItem("folioToastShown")) {
                return;
            }

            fetch("{{ route('notificaciones.foliosPorVencer') }}")
                .then(response => response.json())
                .then(data => {

                    if (data.length > 0) {

                        sessionStorage.setItem("folioToastShown", "true");

                        const total = data.length;
                        const toast = document.getElementById("folio-toast");

                        toast.innerHTML = `
                            <div style="font-weight:600; margin-bottom:5px;">
                                🔔 Notificación
                            </div>
                            <div style="font-size:14px;">
                                Hay <b>${total}</b> personas con folios a vencer en los próximos 10 días.
                            </div>
                            <div style="font-size:13px; margin-top:6px; opacity:0.8;">
                                Para más detalles puede revisarlo en la barra de notificaciones.
                            </div>
                        `;


                        toast.style.display = "block";

                        setTimeout(() => {
                            toast.style.opacity = "0";
                            setTimeout(() => {
                                toast.style.display = "none";
                            }, 500);
                        }, 6000);
                    }
                })
                .catch(error => console.error(error));
        });
    </script>

<script>
        document.addEventListener("DOMContentLoaded", function() {
            let notificacionesMostradas = new Set();
            // Pop-ups solo una vez por login: el primer check los muestra;
            // los checks siguientes (cada 5s) solo actualizan la campanita.
            let popupsActivos = @json($notifPopupsAutoshow);

            function checkDemandasAdmin() {
                fetch("{{ url('/api/notificaciones/demandas-admin') }}")
                    .then(response => response.json())
                    .then(result => {
                        if (result.success && result.data.length > 0) {
                            const container = document.getElementById("demandas-toast-container");
                            const notifList = document.getElementById("notif-list");
                            const badge = document.getElementById("notif-count");
                            
                            if (notifList && notifList.innerHTML.includes('Â¡Todo al día!')) {
                                notifList.innerHTML = '';
                            }

                            result.data.forEach(demanda => {
                                // --- 1. POP-UP SUPERIOR DERECHA ---
                                if (popupsActivos && !notificacionesMostradas.has(demanda.id)) {
                                    notificacionesMostradas.add(demanda.id);

                                    const toast = document.createElement("div");
                                    toast.id = `demanda-toast-${demanda.id}`;
                                    toast.style.cssText = "background: white; border-left: 5px solid #3b82f6; padding: 16px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 320px; position: relative; opacity: 0; transform: translateX(100%); transition: all 0.4s ease;";
                                    
                                    toast.innerHTML = `
                                        <button onclick="cerrarToastSip('${demanda.id}')" style="position: absolute; top: 10px; right: 10px; background: transparent; border: none; font-size: 20px; cursor: pointer; color: #9ca3af; transition: color 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#9ca3af'">
                                            <i class="bx bx-x"></i>
                                        </button>
                                        <div style="font-weight:600; margin-bottom:5px; color: #1e3a8a; padding-right: 15px;">
                                            <i class="bx bx-info-circle"></i> DJ Registrada por SIP
                                        </div>
                                        <div style="font-size:13px; color: #4b5563; line-height: 1.4;">
                                            El personal <b>${demanda.personal}</b> ha registrado su DJ por demanda.
                                        </div>
                                    `;

                                    if(container) {
                                        container.appendChild(toast);
                                        setTimeout(() => {
                                            toast.style.opacity = "1";
                                            toast.style.transform = "translateX(0)";
                                        }, 100);
                                    }
                                }

                                // --- 2. CAMPANITA INTERNA ---
                                if (notifList && !document.getElementById(`campana-demanda-${demanda.id}`)) {
                                    const itemCampana = document.createElement("div");
                                    itemCampana.id = `campana-demanda-${demanda.id}`;
                                    itemCampana.className = "flex items-center justify-between px-4 py-3 hover:bg-slate-50 transition-colors border-b border-gray-100";
                                    itemCampana.innerHTML = `
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                                                <i class="bx bx-info-circle text-lg"></i>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-gray-700">DJ por SIP</span>
                                                <span class="text-[11px] text-gray-500"><b>${demanda.personal}</b> Actualizó DJ (Demanda)</span>
                                            </div>
                                        </div>
                                        <button onclick="event.stopPropagation(); borrarDemandaSip('${demanda.id}')" class="text-gray-400 hover:text-red-500 px-2" title="Descartar">
                                            <i class="bx bx-x text-lg"></i>
                                        </button>
                                    `;
                                    notifList.prepend(itemCampana); 
                                    
                                    if(badge) {
                                        let currentCount = parseInt(badge.textContent) || 0;
                                        badge.textContent = currentCount + 1;
                                        badge.classList.remove('hidden');
                                    }
                                }
                            });
                        }

                        // Después del primer check ya no se muestran más pop-ups
                        popupsActivos = false;
                    })
                    .catch(error => console.error('Error revisando demandas del SIP:', error));
            }

            // Función para ELIMINAR definitivamente de la base de datos (Desde la Campanita)
            window.borrarDemandaSip = function(id) {
                fetch("{{ url('/api/notificaciones/demandas-admin/borrar') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const toast = document.getElementById(`demanda-toast-${id}`);
                        if (toast) {
                            toast.style.opacity = "0";
                            toast.style.transform = "translateX(100%)";
                            setTimeout(() => toast.remove(), 400);
                        }
                        
                        const itemCampana = document.getElementById(`campana-demanda-${id}`);
                        if (itemCampana) {
                            itemCampana.remove();
                            const badge = document.getElementById("notif-count");
                            if(badge) {
                                let currentCount = parseInt(badge.textContent) || 0;
                                if (currentCount > 0) badge.textContent = currentCount - 1;
                            }
                        }
                        notificacionesMostradas.delete(id);
                    }
                })
                .catch(err => console.error('Error al borrar la notificación', err));
            };

            // Función para CERRAR el pop-up: descarta la notificación definitivamente
            // (se elimina de la BD y ya no vuelve a aparecer en ningún login)
            window.cerrarToastSip = function(id) {
                window.borrarDemandaSip(id);
            };

            setInterval(checkDemandasAdmin, 5000); 
            checkDemandasAdmin();
        });
    </script>


    @yield('script')

    @vite(['resources/js/app.js'])

</body>

</html>
