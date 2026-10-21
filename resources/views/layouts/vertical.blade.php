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

    <div id="modal-detalle-solicitud-sip" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="titulo-detalle-solicitud-sip">
        <section class="w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-slate-800">
            <header class="flex items-start justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                <div>
                    <h2 id="titulo-detalle-solicitud-sip" class="text-lg font-semibold text-slate-900 dark:text-white">Solicitud de actualización desde el SIP</h2>
                    <p id="subtitulo-detalle-solicitud-sip" class="mt-1 text-sm text-slate-500">Datos del personal que envió solicitudes desde el SIP.</p>
                </div>
                <button type="button" onclick="cerrarDetalleDemandaSip()" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700" aria-label="Cerrar"><i class="bx bx-x text-xl"></i></button>
            </header>
            <div id="detalle-solicitudes-sip-lista" class="max-h-[65vh] space-y-3 overflow-y-auto px-5 py-5"></div>
            <footer class="flex justify-end border-t border-slate-200 px-5 py-3 dark:border-slate-700"><button type="button" onclick="cerrarDetalleDemandaSip()" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cerrar</button></footer>
        </section>
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

    @auth
    <div x-data="matriculaNotificacion({{ auth()->id() }})">
        <template x-for="(n, index) in notificaciones" :key="n._id">
            <div class="fixed right-4 w-80 bg-white dark:bg-slate-800 border border-default-200 dark:border-slate-700 shadow-2xl rounded-2xl overflow-hidden animate-slide-up"
                :style="'z-index: ' + (50 + index) + '; bottom: ' + (16 + index * 210) + 'px'">

                {{-- Franja superior de color --}}
                <div class="h-1.5 w-full"
                    :class="!n.finalizado
                    ? 'bg-gradient-to-r from-blue-400 to-blue-500'
                    : n.fallidos > 0
                        ? 'bg-gradient-to-r from-amber-400 to-orange-500'
                        : 'bg-gradient-to-r from-emerald-400 to-green-500'">
                </div>

                <div class="p-5">
                    {{-- Cabecera --}}
                    <div class="flex items-start gap-3 mb-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-lg shadow-sm"
                            :class="!n.finalizado
                            ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 ring-1 ring-blue-200 dark:ring-blue-700/50'
                            : n.fallidos > 0
                                ? 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 ring-1 ring-amber-200 dark:ring-amber-700/50'
                                : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 ring-1 ring-emerald-200 dark:ring-emerald-700/50'">
                            <template x-if="!n.finalizado">
                                <i class="i-ph-spinner-gap text-xl animate-spin"></i>
                            </template>
                            <template x-if="n.finalizado && n.fallidos > 0">
                                <i class="i-ph-warning-circle text-xl"></i>
                            </template>
                            <template x-if="n.finalizado && !n.fallidos">
                                <i class="i-ph-check-circle text-xl"></i>
                            </template>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-gray-100 text-sm truncate" x-text="n.curso"></p>
                            <p class="text-xs mt-0.5 font-medium"
                                :class="!n.finalizado
                                ? 'text-blue-600 dark:text-blue-400'
                                : n.fallidos > 0
                                    ? 'text-amber-600 dark:text-amber-400'
                                    : 'text-emerald-600 dark:text-emerald-400'">
                                <template x-if="!n.finalizado">
                                    <span>Matriculando personas...</span>
                                </template>
                                <template x-if="n.finalizado && n.fallidos > 0">
                                    <span>Procesado con errores</span>
                                </template>
                                <template x-if="n.finalizado && !n.fallidos">
                                    <span>Completado exitosamente</span>
                                </template>
                            </p>
                        </div>
                        <template x-if="n.finalizado">
                            <button @click="cerrar(n._id)" class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                                <i class="i-ph-x text-sm"></i>
                            </button>
                        </template>
                    </div>

                    {{-- Barra de progreso --}}
                    <div class="mb-4">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                            <span class="font-medium" x-text="n.finalizado ? 'Efectividad' : 'Progreso'"></span>
                            <span class="font-semibold" x-text="n.finalizado
                            ? Math.round((n.enviados / n.total) * 100) + '%'
                            : n.porcentaje + '%'">
                            </span>
                        </div>
                        <div class="w-full h-2 bg-gray-100 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 ease-out"
                                :style="'width: ' + (n.finalizado ? (n.enviados / n.total) * 100 : n.porcentaje) + '%'"
                                :class="!n.finalizado
                                ? 'bg-gradient-to-r from-blue-400 to-blue-500'
                                : n.fallidos > 0
                                    ? 'bg-gradient-to-r from-amber-400 to-amber-500'
                                    : 'bg-gradient-to-r from-emerald-400 to-emerald-500'">
                            </div>
                        </div>
                        {{-- Contador de personas --}}
                        <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 text-right">
                            <span x-text="n.procesados"></span> / <span x-text="n.total"></span> personas
                        </p>
                    </div>

                    {{-- Stats (solo al finalizar) --}}
                    <template x-if="n.finalizado">
                        <div>
                            <div class="grid grid-cols-3 gap-2 mb-4">
                                <div class="bg-gray-50 dark:bg-slate-700/50 rounded-xl p-3 text-center">
                                    <p class="text-lg font-bold text-gray-900 dark:text-gray-100" x-text="n.enviados"></p>
                                    <p class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mt-0.5">Enviados</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-slate-700/50 rounded-xl p-3 text-center">
                                    <p class="text-lg font-bold text-gray-900 dark:text-gray-100" x-text="n.total"></p>
                                    <p class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mt-0.5">Total</p>
                                </div>
                                <div class="rounded-xl p-3 text-center"
                                    :class="n.fallidos > 0 ? 'bg-red-50 dark:bg-red-900/20' : 'bg-gray-50 dark:bg-slate-700/50'">
                                    <p class="text-lg font-bold"
                                        :class="n.fallidos > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100'"
                                        x-text="n.fallidos || '0'">
                                    </p>
                                    <p class="text-[10px] font-semibold uppercase tracking-wider mt-0.5"
                                        :class="n.fallidos > 0 ? 'text-red-500 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'">
                                        Fallidos
                                    </p>
                                </div>
                            </div>

                            <button @click="cerrar(n._id)"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold text-white transition-all duration-200 shadow-sm"
                                :class="n.fallidos > 0
                                ? 'bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700'
                                : 'bg-gradient-to-r from-primary to-primary-600 hover:from-primary-600 hover:to-primary-700'">
                                <i class="i-ph-check text-sm"></i>
                                ENTENDIDO
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
    @endauth

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

            // Mostrar Etapa 2 como aviso flotante al iniciar sesión, igual que
            // las solicitudes SIP. El detalle sigue disponible en la campana.
            if (@json($notifPopupsAutoshow)) {
                fetch("{{ url('/api/notificaciones/pendientes-etapa2') }}", {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin'
                })
                    .then(response => {
                        if (!response.ok) throw new Error(`HTTP ${response.status}`);
                        return response.json();
                    })
                    .then(result => {
                        const total = Number(result.total) || 0;
                        const container = document.getElementById('demandas-toast-container');
                        if (!result.success || total === 0 || !container) return;

                        const modalList = document.getElementById('modal-etapa2-list');
                        const modalTotal = document.getElementById('modal-etapa2-total');
                        if (modalTotal) modalTotal.textContent = total;
                        if (modalList) {
                            modalList.replaceChildren();
                            Object.entries(result.data || {}).forEach(([sucursal, cantidad]) => {
                                const row = document.createElement('div');
                                row.className = 'flex items-center justify-between px-4 py-3 hover:bg-slate-50 transition-colors';
                                const name = document.createElement('span');
                                name.className = 'text-sm font-bold text-gray-700 uppercase';
                                name.textContent = sucursal;
                                const count = document.createElement('span');
                                count.className = 'text-xs font-bold text-red-600 bg-red-50 px-2.5 py-1 rounded-full border border-red-100 shadow-sm';
                                count.textContent = `${cantidad} pend.`;
                                row.append(name, count);
                                modalList.appendChild(row);
                            });
                        }

                        const toast = document.createElement('div');
                        toast.id = 'etapa2-toast-resumen';
                        toast.style.cssText = 'order:2;background:white;border-left:5px solid #f97316;padding:16px 20px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.15);min-width:320px;max-width:460px;position:relative;opacity:0;transform:translateX(100%);transition:all .4s ease;cursor:pointer;';
                        toast.setAttribute('role', 'button');
                        toast.setAttribute('tabindex', '0');
                        toast.setAttribute('aria-label', `Ver ${total} pendientes de Etapa 2`);

                        const close = document.createElement('button');
                        close.type = 'button';
                        close.setAttribute('aria-label', 'Cerrar aviso');
                        close.style.cssText = 'position:absolute;top:10px;right:10px;background:transparent;border:0;font-size:20px;cursor:pointer;color:#9ca3af;';
                        close.innerHTML = '<i class="bx bx-x"></i>';
                        close.addEventListener('click', event => {
                            event.stopPropagation();
                            window.cerrarToastEtapa2();
                        });

                        const title = document.createElement('div');
                        title.style.cssText = 'font-weight:600;margin-bottom:5px;color:#9a3412;padding-right:15px;';
                        title.innerHTML = '<i class="bx bx-task"></i> Pendientes Etapa 2';
                        const message = document.createElement('div');
                        message.style.cssText = 'font-size:13px;color:#4b5563;line-height:1.4;';
                        message.textContent = `Hay ${total} personas pendientes de verificación. Haz clic para ver el detalle.`;
                        toast.append(close, title, message);
                        toast.addEventListener('click', () => {
                            document.getElementById('btn-open-modal-etapa2')?.click();
                            window.cerrarToastEtapa2();
                        });
                        toast.addEventListener('keydown', event => {
                            if (event.key === 'Enter' || event.key === ' ') {
                                event.preventDefault();
                                toast.click();
                            }
                        });
                        container.appendChild(toast);
                        setTimeout(() => { toast.style.opacity = '1'; toast.style.transform = 'translateX(0)'; }, 100);
                        setTimeout(() => window.cerrarToastEtapa2(), 10000);
                    })
                    .catch(error => console.error('Error revisando pendientes de Etapa 2:', error));
            }

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
            const demandasPorId = new Map();
            const listaNotificacionesSip = document.getElementById('notif-list');
            // Pop-ups solo una vez por login: el primer check los muestra;
            // los checks siguientes (cada 5s) solo actualizan la campanita.
            let popupsActivos = @json($notifPopupsAutoshow);
            let popupResumenMostrado = false;

            window.abrirDetalleDemandaSip = function(id) {
                const demandas = [...demandasPorId.values()];
                if (!demandas.length) return;
                const lista = document.getElementById('detalle-solicitudes-sip-lista');
                document.getElementById('subtitulo-detalle-solicitud-sip').textContent =
                    `${demandas.length} ${demandas.length === 1 ? 'solicitud pendiente' : 'solicitudes pendientes'} de actualización desde el SIP.`;
                lista.replaceChildren();
                demandas.forEach(demanda => {
                    const tarjeta = document.createElement('article');
                    tarjeta.className = 'rounded-lg border border-slate-200 p-4 dark:border-slate-700';
                    const nombre = document.createElement('h3');
                    nombre.className = 'mb-3 font-semibold text-slate-900 dark:text-white';
                    nombre.textContent = demanda.personal || `Personal ${demanda.codigoPers}`;
                    const datos = document.createElement('dl');
                    datos.className = 'grid grid-cols-1 gap-3 sm:grid-cols-2';
                    [
                        ['Sucursal', demanda.sucursal],
                        ['Tipo de trabajador', demanda.tipo_trabajador],
                        ['Cargo', demanda.cargo],
                        ['DNI', demanda.dni],
                    ].forEach(([etiqueta, valor]) => {
                        const campo = document.createElement('div');
                        const titulo = document.createElement('dt');
                        titulo.className = 'text-xs font-medium uppercase tracking-wide text-slate-500';
                        titulo.textContent = etiqueta;
                        const contenido = document.createElement('dd');
                        contenido.className = 'mt-1 text-sm text-slate-800 dark:text-slate-200';
                        contenido.textContent = valor || '—';
                        campo.append(titulo, contenido);
                        datos.appendChild(campo);
                    });
                    tarjeta.append(nombre, datos);
                    lista.appendChild(tarjeta);
                });
                const modal = document.getElementById('modal-detalle-solicitud-sip');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            window.cerrarDetalleDemandaSip = function() {
                const modal = document.getElementById('modal-detalle-solicitud-sip');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            document.getElementById('modal-detalle-solicitud-sip').addEventListener('click', event => {
                if (event.target.id === 'modal-detalle-solicitud-sip') window.cerrarDetalleDemandaSip();
            });
            // Delegamos los eventos porque notifications.js vuelve a dibujar
            // #notif-list periódicamente y reemplaza los nodos de cada solicitud.
            listaNotificacionesSip?.addEventListener('click', event => {
                const fila = event.target.closest('[data-demanda-sip-grupo]');
                if (!fila) return;
                window.abrirDetalleDemandaSip('grupo');
            });
            listaNotificacionesSip?.addEventListener('keydown', event => {
                const fila = event.target.closest('[data-demanda-sip-grupo]');
                if (!fila || (event.key !== 'Enter' && event.key !== ' ')) return;
                event.preventDefault();
                window.abrirDetalleDemandaSip('grupo');
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') window.cerrarDetalleDemandaSip();
            });

            function checkDemandasAdmin() {
                fetch("{{ url('/api/notificaciones/demandas-admin') }}")
                    .then(response => response.json())
                    .then(result => {
                        if (result.success && result.data.length > 0) {
                            const container = document.getElementById("demandas-toast-container");
                            const notifList = document.getElementById("notif-list");
                            const badge = document.getElementById("notif-count");
                            
                            if (notifList && notifList.innerHTML.includes('¡Todo al día!')) {
                                notifList.innerHTML = '';
                            }

                            const nuevasSolicitudes = result.data.filter(demanda => !notificacionesMostradas.has(String(demanda.id)));
                            demandasPorId.clear();
                            result.data.forEach(demanda => {
                                demandasPorId.set(String(demanda.id), demanda);
                            });

                            // Una sola notificación en la campanita para todo el grupo SIP.
                            if (notifList) {
                                let itemGrupo = document.getElementById('campana-demanda-sip-grupo');
                                if (!itemGrupo) {
                                    itemGrupo = document.createElement('div');
                                    itemGrupo.id = 'campana-demanda-sip-grupo';
                                    itemGrupo.dataset.demandaSipGrupo = '1';
                                    itemGrupo.className = 'flex cursor-pointer items-center justify-between border-b border-gray-100 px-4 py-3 transition-colors hover:bg-slate-50';
                                    itemGrupo.setAttribute('role', 'button');
                                    itemGrupo.setAttribute('tabindex', '0');
                                    itemGrupo.innerHTML = `
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-md border border-blue-100 bg-blue-50 text-blue-600"><i class="bx bx-info-circle text-lg"></i></div>
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-gray-700">Solicitudes de actualización desde el SIP</span>
                                                <span class="text-[11px] text-gray-500 demanda-sip-resumen"></span>
                                            </div>
                                        </div>
                                        <span class="demanda-sip-total ml-3 flex h-6 min-w-6 items-center justify-center rounded-full bg-blue-100 px-2 text-xs font-bold text-blue-700"></span>
                                    `;
                                    notifList.prepend(itemGrupo);
                                }
                                itemGrupo.querySelector('.demanda-sip-resumen').textContent =
                                    `${result.data.length} ${result.data.length === 1 ? 'solicitud pendiente' : 'solicitudes pendientes'}. Haz clic para ver el detalle.`;
                                itemGrupo.querySelector('.demanda-sip-total').textContent = result.data.length;
                                if (badge) {
                                    const count = parseInt(badge.textContent) || 0;
                                    badge.textContent = Math.min(99, count + (itemGrupo.dataset.badgeCuenta === '1' ? 0 : 1));
                                    badge.classList.remove('hidden');
                                    itemGrupo.dataset.badgeCuenta = '1';
                                }
                            }

                            // Un único aviso resumido por inicio de sesión, sin duplicarlo por cada persona.
                            if (popupsActivos && !popupResumenMostrado && nuevasSolicitudes.length && container) {
                                nuevasSolicitudes.forEach(demanda => notificacionesMostradas.add(String(demanda.id)));
                                popupResumenMostrado = true;
                                const toast = document.createElement('div');
                                toast.id = 'demanda-toast-resumen';
                                toast.style.cssText = 'order:1;background:white;border-left:5px solid #3b82f6;padding:16px 20px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.15);min-width:320px;max-width:460px;position:relative;opacity:0;transform:translateX(100%);transition:all .4s ease;';
                                const cerrar = document.createElement('button');
                                cerrar.type = 'button';
                                cerrar.setAttribute('aria-label', 'Cerrar aviso');
                                cerrar.style.cssText = 'position:absolute;top:10px;right:10px;background:transparent;border:0;font-size:20px;cursor:pointer;color:#9ca3af;';
                                cerrar.innerHTML = '<i class="bx bx-x"></i>';
                                cerrar.addEventListener('click', () => window.cerrarToastSip('resumen'));
                                const titulo = document.createElement('div');
                                titulo.style.cssText = 'font-weight:600;margin-bottom:5px;color:#1e3a8a;padding-right:15px;';
                                titulo.innerHTML = '<i class="bx bx-info-circle"></i> Solicitudes de actualización desde el SIP';
                                const mensaje = document.createElement('div');
                                mensaje.style.cssText = 'font-size:13px;color:#4b5563;line-height:1.4;';
                                mensaje.textContent = `Hay ${nuevasSolicitudes.length} ${nuevasSolicitudes.length === 1 ? 'solicitud nueva' : 'solicitudes nuevas'} de actualización de datos desde el SIP. Revisa el detalle en la campanita.`;
                                toast.append(cerrar, titulo, mensaje);
                                container.appendChild(toast);
                                setTimeout(() => { toast.style.opacity = '1'; toast.style.transform = 'translateX(0)'; }, 100);
                                setTimeout(() => window.cerrarToastSip('resumen'), 7000);
                            }
                        } else if (result.success) {
                            demandasPorId.clear();
                            document.getElementById('campana-demanda-sip-grupo')?.remove();
                        }

                        // Después del primer check ya no se muestran más pop-ups
                        popupsActivos = false;
                    })
                    .catch(error => console.error('Error revisando demandas del SIP:', error));
            }

            // ── CERRAR EL POP-UP: solo lo oculta en esta sesión ──
            // La notificación NO se borra: sigue en la campanita y volverá a
            // popupear en el PRÓXIMO LOGIN hasta que alguien la atienda desde
            // la campanita. Por eso NO se saca de `notificacionesMostradas`.
            window.cerrarToastSip = function(id) {
                const toast = document.getElementById(id === 'resumen' ? 'demanda-toast-resumen' : `demanda-toast-${id}`);
                if (!toast) return;

                toast.style.opacity = "0";
                toast.style.transform = "translateX(100%)";
                setTimeout(() => toast.remove(), 400);
            };

            window.cerrarToastEtapa2 = function() {
                const toast = document.getElementById('etapa2-toast-resumen');
                if (!toast) return;
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 400);
            };

            // ── ATENDER DESDE LA CAMPANITA: archiva la notificación ──
            // Pone habilitado = 0 en la BD (NO borra la fila, se conserva el
            // historial) y la quita del popup y de la campanita para siempre.
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
                        window.cerrarToastSip(id);

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
                .catch(err => console.error('Error al atender la notificación', err));
            };

            setInterval(checkDemandasAdmin, 5000); 
            checkDemandasAdmin();
        });
    </script>

    <script>
        function matriculaNotificacion(usuarioId) {
            return {
                notificaciones: [],
                idCounter: 0,
                storageKey: 'matricula_notificacion_' + usuarioId,

                init() {
                    const stored = localStorage.getItem(this.storageKey);
                    if (stored) {
                        try {
                            const parsed = JSON.parse(stored);
                            let items = Array.isArray(parsed) ? parsed : [parsed];
                            items = items.map((item, i) => {
                                item._id = i + 1;
                                return item;
                            });
                            this.notificaciones = items;
                            this.idCounter = this.notificaciones.length;
                        } catch (e) {
                            localStorage.removeItem(this.storageKey);
                        }
                    }

                    window.Echo.private(`usuario.${usuarioId}`)
                        .listen('.matricula.progreso', (data) => {
                            this.actualizarProgreso(data);
                        })
                        .listen('.matricula.finalizada', (data) => {
                            this.finalizar(data);
                        });
                },

                actualizarProgreso(data) {
                    const existe = this.notificaciones.find(n => n.jobId === data.jobId);

                    if (existe) {
                        existe.porcentaje = data.porcentaje;
                        existe.procesados = data.procesados;
                    } else {
                        this.notificaciones.push({
                            _id: ++this.idCounter,
                            jobId: data.jobId,
                            curso: data.curso,
                            procesados: data.procesados,
                            total: data.total,
                            porcentaje: data.porcentaje,
                            finalizado: false,
                            enviados: 0,
                            fallidos: 0,
                        });
                    }

                    this.guardar();
                },

                finalizar(data) {
                    const existe = this.notificaciones.find(n => n.jobId === data.jobId);

                    if (existe) {
                        existe.porcentaje = 100;
                        existe.procesados = data.total;
                        existe.enviados = data.enviados;
                        existe.fallidos = data.fallidos;
                        existe.finalizado = true;
                    } else {
                        this.notificaciones.push({
                            _id: ++this.idCounter,
                            jobId: data.jobId,
                            curso: data.curso,
                            procesados: data.total,
                            total: data.total,
                            porcentaje: 100,
                            finalizado: true,
                            enviados: data.enviados,
                            fallidos: data.fallidos,
                        });
                    }

                    this.guardar();
                },

                cerrar(id) {
                    this.notificaciones = this.notificaciones.filter(n => n._id !== id);
                    if (this.notificaciones.length === 0) {
                        localStorage.removeItem(this.storageKey);
                    } else {
                        this.guardar();
                    }
                },

                guardar() {
                    const paraGuardar = this.notificaciones.map(({
                        _id,
                        ...resto
                    }) => resto);
                    localStorage.setItem(this.storageKey, JSON.stringify(paraGuardar));
                }
            }
        }
    </script>

    @yield('script')

    @vite(['resources/js/app.js'])

</body>

</html>
