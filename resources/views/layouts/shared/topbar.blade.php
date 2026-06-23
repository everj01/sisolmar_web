<!-- Topbar Start -->
<header
    class="app-header sticky top-0 z-50 min-h-topbar flex items-center bg-white dark:bg-slate-900 border-b border-gray-100 dark:border-slate-700 transition-colors duration-300">
    <div class="container flex items-center justify-between gap-4 mx-0 py-0">

        {{-- Lado izquierdo: toggle móvil + título de página --}}
        <div class="flex items-center gap-5">
            <div class="lg:hidden flex">
                <button
                    class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-white border border-gray-200 hover:bg-gray-50 text-gray-500 transition-all"
                    data-hs-overlay="#app-menu" aria-label="Toggle navigation">
                    <i class="i-ph-list-duotone text-xl"></i>
                </button>
            </div>

            @stack('page-heading')
        </div>

        {{-- Lado derecho: botones uniformes --}}
        <div class="flex items-center gap-2">

            {{-- Notificaciones --}}
            <div class="hs-dropdown relative inline-flex [--placement:bottom-right]">
                <button id="btn-notifications" class="relative inline-flex items-center justify-center w-9 h-9 rounded-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:bg-gray-50
  dark:hover:bg-slate-700 text-gray-500 hover:text-gray-700 dark:text-slate-400 transition-all">
                    <i class="bx bx-bell text-lg"></i>
                    <span id="notif-count"
                        class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[17px] h-[17px] flex items-center justify-center px-1 hidden">
                        0
                    </span>
                </button>
                <div
                    class="hs-dropdown-menu duration mt-2 w-full max-w-sm rounded-lg border border-default-200 bg-white opacity-0 shadow-md transition-[opacity,margin] hs-dropdown-open:opacity-100 hidden">
                    <div class="block px-4 py-2 font-medium text-center text-default-700 rounded-t-lg bg-default-50">
                        Folios por vencer en los próximos 10 días
                    </div>
                    <div class="max-h-[400px] overflow-y-auto">
                        <div class="divide-y divide-default-100" id="notif-list">
                            <div class="px-4 py-6 text-center text-sm text-default-500">
                                Cargando notificaciones...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pantalla completa --}}
            <button data-toggle="fullscreen" type="button" title="Pantalla completa" class="hidden md:inline-flex items-center justify-center w-9 h-9 rounded-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700
  text-gray-500 hover:text-gray-700 dark:text-slate-400 transition-all">
                <i class="i-ph-arrows-out-duotone text-lg flex group-[-fullscreen]:hidden"></i>
                <i class="i-ph-arrows-in-duotone text-lg hidden group-[-fullscreen]:flex"></i>
            </button>

            {{-- Tema claro/oscuro --}}
            <button id="theme-toggle" type="button" title="Cambiar tema" class="hidden md:inline-flex items-center justify-center w-9 h-9 rounded-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700
  text-gray-500 dark:text-slate-400 transition-all">
                <i id="theme-icon-light" class="i-ph-sun-duotone text-lg text-amber-500"></i>
                <i id="theme-icon-dark" class="i-ph-moon-duotone text-lg text-slate-300 hidden"></i>
            </button>

            {{-- Perfil --}}
            <div class="hs-dropdown relative inline-flex [--placement:bottom-right]">
                <button type="button" class="hs-dropdown-toggle inline-flex items-center justify-center w-9 h-9 rounded-full border-2 border-gray-200 dark:border-slate-700 hover:border-gray-300 dark:hover:border-slate-500
  overflow-hidden transition-all">
                    <img src="{{ asset('images/user.png') }}" alt="usuario" class="w-full h-full object-cover">
                </button>

                <div
                    class="hs-dropdown-menu duration mt-2 min-w-52 rounded-lg border border-default-200 bg-white p-2 opacity-0 shadow-md transition-[opacity,margin] hs-dropdown-open:opacity-100 hidden">

                    <div class="px-3 py-2 mb-1 border-b border-gray-100">
                        <p class="text-xs font-semibold text-gray-800">{{ session('nombre') }} {{ session('apellido') }}
                        </p>
                        <p class="text-xs text-gray-400">{{ session('usuario') }}</p>
                    </div>

                    <a class="flex items-center gap-2 py-2 px-3 rounded-md text-sm text-default-800 hover:bg-default-100"
                        onclick="abrirModalPasswordChange()" href="#">
                        <i class="i-tabler-lock text-gray-400"></i>
                        Cambiar contraseña
                    </a>

                    <button hidden style="display:none;" type="button" data-hs-overlay="#modal-password-change-user"
                        id="btn-modal-password-change-user">
                    </button>

                    <hr class="my-1.5 border-gray-100">

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                        @csrf
                    </form>
                    <button id="__btn-abrir-modal-logout" data-hs-overlay="#modal-confirmar-logout"
                        class="hidden"></button>

                    <a class="flex items-center gap-2 py-2 px-3 rounded-md text-sm text-red-600 hover:bg-red-50"
                        href="#" onclick="document.getElementById('__btn-abrir-modal-logout').click(); return false;">
                        <i class="i-tabler-logout text-red-400"></i>
                        Cerrar Sesión
                    </a>

                </div>
            </div>

        </div>
    </div>
</header>
{{-- Modal: Cambiar contraseña --}}
<div id="modal-password-change-user"
    class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-x-hidden overflow-y-auto hidden pointer-events-none">
    <div class="-translate-y-5 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 sm:max-w-lg sm:w-full my-8 sm:mx-auto flex flex-col bg-white
  shadow-sm rounded">
        <div class="flex flex-col border border-default-200 shadow-sm rounded-lg pointer-events-auto">
            <div class="flex justify-between items-center py-3 px-4 border-b border-default-200">
                <h3 class="text-lg font-medium text-default-900">Cambiar contraseña</h3>
                <button type="button" class="text-default-600 cursor-pointer"
                    data-hs-overlay="#modal-password-change-user">
                    <i class="i-tabler-x text-lg"></i>
                </button>
            </div>
            <div class="p-4 overflow-y-auto">
                <div class="grid lg:grid-cols-3 gap-6">
                    <div class="col-span-2">
                        <label for="txt-new-password-user"
                            class="text-default-800 text-sm font-medium inline-block mb-2">
                            Nueva contraseña
                        </label>
                        <div x-data="{ show: false }" class="flex gap-1 items-center">
                            <input :type="show ? 'text' : 'password'" id="txt-new-password-user" class="form-input">
                            <input type="hidden" name="txtUserNombre" id="txt-user-nombre-pass"
                                value="{{ session('usuario') }}">
                            <button type="button" class="btn bg-info text-white" @click="show = !show">
                                <span x-text="show ? 'Ocultar' : 'Ver'"></span>
                            </button>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <button type="button" class="btn bg-primary text-white" onclick="passwordChangeUserGeneralMe()">
                            Actualizar
                        </button>
                    </div>
                </div>
            </div>
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-default-200">
                <button type="button" class="btn bg-secondary text-white" data-hs-overlay="#modal-password-change-user">
                    <i class="i-tabler-x me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Confirmar logout --}}
<div id="modal-confirmar-logout"
    class="hs-overlay w-full h-full fixed top-0 left-0 z-[80] transition-all duration-300 overflow-x-hidden overflow-y-auto hidden pointer-events-none">
    <div class="-translate-y-5 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-300 sm:max-w-sm sm:w-full my-8 sm:mx-auto flex flex-col bg-white
  shadow-lg rounded-xl">
        <div class="flex flex-col border border-default-200 shadow-sm rounded-xl pointer-events-auto">
            <div class="p-6 text-center">
                <div class="flex items-center justify-center w-14 h-14 rounded-full bg-danger/10 mx-auto mb-4">
                    <i class="i-tabler-logout text-2xl text-danger"></i>
                </div>
                <h3 class="text-lg font-semibold text-default-900 mb-1">¿Cerrar sesión?</h3>
                <p class="text-sm text-default-500">Se cerrará tu sesión actual y serás redirigido al inicio.</p>
            </div>
            <div class="flex gap-2 px-6 pb-5">
                <button type="button" class="btn bg-default-100 text-default-700 hover:bg-default-200 w-full"
                    data-hs-overlay="#modal-confirmar-logout">
                    Cancelar
                </button>
                <button type="button" class="btn bg-danger text-white hover:bg-danger/90 w-full"
                    onclick="sessionStorage.removeItem('folioToastShown'); document.getElementById('logout-form').submit();">
                    <i class="i-tabler-logout me-1"></i> Cerrar Sesión
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts Topbar -->
@vite(['resources/js/functions/notifications.js'])
<!-- Topbar End -->