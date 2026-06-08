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

    @include('layouts.shared/footer-scripts')


    <script>
        document.addEventListener("DOMContentLoaded", function() {

            // 🔹 Evita que se muestre más de una vez por sesión
            if (sessionStorage.getItem("folioToastShown")) {
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