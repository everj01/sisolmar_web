<!-- Topbar Start -->
<header class="app-header sticky top-0 z-50 min-h-topbar flex items-center bg-default-100/5 backdrop-blur-lg border-b border-gray-300/50">
    <div class="container flex items-center justify-between gap-4">
        <div class="flex items-center gap-5">
            <!-- Botón escritorio -->
            <div class="lg:flex hidden">
                <button id="desktop-toggle-sidebar" class="flex items-center ...">
                    <i class="i-ph-list-duotone text-2xl"></i>
                </button>
            </div>

            <!-- Sidenav Menu Toggle Button -->
            <div class="lg:hidden flex">
                <button
                    class="flex items-center text-default-500 rounded-full cursor-pointer p-2 bg-white border border-default-200 hover:bg-primary/15 hover:text-primary transition-all"
                    data-hs-overlay="#app-menu" aria-label="Toggle navigation">
                    <i class="i-ph-list-duotone text-2xl"></i>
                </button>
            </div>

            <!-- Topbar Brand Logo -->
            <a href="{{ route('any', 'index')}}" class="md:hidden flex">
                <img src="/images/logo-sm.png" class="h-8" alt="Small logo">
            </a>

            <!-- Topbar Search -->
            <!-- <div class="md:flex hidden items-center relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <i class="i-tabler-search text-base"></i>
                </div>
                <input type="search"
                    class="form-input px-10 rounded-lg  bg-default-500/10 border-transparent focus:border-transparent w-80"
                    placeholder="Buscar...">
                <button type="button" class="absolute inset-y-0 end-0 flex items-center pe-3">
                    <i class="i-tabler-microphone text-base hover:text-black"></i>
                </button>
            </div> -->
        </div>

        <div class="flex items-center gap-5">


            <!-- Notification Dropdown Button -->
            <div class="hs-dropdown relative inline-flex [--placement:bottom-right]">
                <button type="button"
                    class="hs-dropdown-toggle inline-flex items-center p-2 rounded-full bg-white border border-default-200 hover:bg-primary/15 hover:text-primary transition-all">
                    <i class="i-ph-bell-duotone text-2xl"></i>
                </button>

                <!-- Dropdown menu -->
                <div
                    class="hs-dropdown-menu duration mt-2 w-full max-w-sm rounded-lg border border-default-200 bg-white opacity-0 shadow-md transition-[opacity,margin] hs-dropdown-open:opacity-100 hidden">
                    <div class="block px-4 py-2 font-medium text-center text-default-700 rounded-t-lg bg-default-50">
                        Notifications
                    </div>

                    <div class="divide-y divide-default-100">
                        <a href="#" class="flex px-4 py-3 hover:bg-default-100">
                            <div class="flex-shrink-0">
                                <img class="rounded-full w-11 h-11" src="/images/users/avatar-6.jpg"
                                    alt="Alex image">
                                <div
                                    class="absolute flex items-center justify-center w-5 h-5 ms-6 -mt-5 bg-green-500 border border-white rounded-full">
                                    <i class="i-tabler-alert-circle text-white w-4 h-4"></i>
                                </div>
                            </div>
                            <div class="w-full ps-3">
                                <div class="text-default-500 text-sm mb-1.5">
                                    New alert from <span class="font-semibold text-default-900">Alex
                                        Johnson</span>:
                                    "System needs attention, check logs."
                                </div>
                                <div class="text-xs text-primary">2 minutes ago</div>
                            </div>
                        </a>

                        <a href="#" class="flex px-4 py-3 hover:bg-default-100">
                            <div class="flex-shrink-0">
                                <img class="rounded-full w-11 h-11" src="/images/users/avatar-7.jpg"
                                    alt="Sarah image">
                                <div
                                    class="absolute flex items-center justify-center w-5 h-5 ms-6 -mt-5 bg-primary-600 border border-white rounded-full">
                                    <i class="i-tabler-file-text text-white w-4 h-4"></i>
                                </div>
                            </div>
                            <div class="w-full ps-3">
                                <div class="text-default-500 text-sm mb-1.5">
                                    <span class="font-semibold text-default-900">Sarah Lee</span> shared a
                                    document with you.
                                </div>
                                <div class="text-xs text-primary">5 minutes ago</div>
                            </div>
                        </a>

                        <a href="#" class="flex px-4 py-3 hover:bg-default-100">
                            <div class="flex-shrink-0">
                                <img class="rounded-full w-11 h-11" src="/images/users/avatar-8.jpg"
                                    alt="Michael image">
                                <div
                                    class="absolute flex items-center justify-center w-5 h-5 ms-6 -mt-5 bg-purple-500 border border-white rounded-full">
                                    <i class="i-tabler-message text-white w-4 h-4"></i>
                                </div>
                            </div>
                            <div class="w-full ps-3">
                                <div class="text-default-500 text-sm mb-1.5">
                                    <span class="font-semibold text-default-900">Michael Clark</span> replied
                                    to your comment.
                                </div>
                                <div class="text-xs text-primary">15 minutes ago</div>
                            </div>
                        </a>

                        <a href="#" class="flex px-4 py-3 hover:bg-default-100">
                            <div class="flex-shrink-0">
                                <img class="rounded-full w-11 h-11" src="/images/users/avatar-9.jpg"
                                    alt="Emma image">
                                <div
                                    class="absolute flex items-center justify-center w-5 h-5 ms-6 -mt-5 bg-pink-500 border border-white rounded-full">
                                    <i class="i-tabler-heart text-white w-4 h-4"></i>
                                </div>
                            </div>
                            <div class="w-full ps-3">
                                <div class="text-default-500 text-sm mb-1.5">
                                    <span class="font-semibold text-default-900">Emma Stone</span> reacted to
                                    your post.
                                </div>
                                <div class="text-xs text-primary">30 minutes ago</div>
                            </div>
                        </a>
                    </div>


                    <a href="#"
                        class="block py-2 text-sm font-medium text-center text-default-900 rounded-b-lg bg-default-50 hover:bg-default-100">
                        <div class="inline-flex items-center ">
                            <svg class="w-4 h-4 me-2 text-default-500" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 14">
                                <path
                                    d="M10 0C4.612 0 0 5.336 0 7c0 1.742 3.546 7 10 7 6.454 0 10-5.258 10-7 0-1.664-4.612-7-10-7Zm0 10a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z" />
                            </svg>
                            View all
                        </div>
                    </a>
                </div>
            </div>

            <!-- Fullscreen Toggle Button -->
            <div class="md:flex hidden">
                <button data-toggle="fullscreen" type="button"
                    class="p-2 rounded-full bg-white border border-default-200 hover:bg-primary/15 hover:text-primary transition-all">
                    <span class="sr-only">Fullscreen Mode</span>
                    <span class="flex items-center justify-center size-6">
                        <i class="i-ph-arrows-out-duotone text-2xl flex group-[-fullscreen]:hidden"></i>
                        <i class="i-ph-arrows-in-duotone text-2xl hidden group-[-fullscreen]:flex"></i>
                    </span>
                </button>
            </div>

            <!-- Profile Dropdown Button -->
            <div class="relative">
                <div class="hs-dropdown relative inline-flex [--placement:bottom-right]">
                    <button type="button" class="hs-dropdown-toggle">
                        <img src="{{ asset('images/user.png') }}" alt="user-image" class="rounded-full h-10">
                    </button>
                    <div
                        class="hs-dropdown-menu duration mt-2 min-w-48 rounded-lg border border-default-200 bg-white p-2 opacity-0 shadow-md transition-[opacity,margin] hs-dropdown-open:opacity-100 hidden">
                        <a class="flex items-center py-2 px-3 rounded-md text-sm text-default-800 hover:bg-default-100" onclick="abrirModalPasswordChange()"
                            href="#">
                            Cambiar contraseña
                        </a>
                        <button hidden style="display: none;" type="button" class="btn bg-primary text-white" data-hs-overlay="#modal-password-change-user" id="btn-modal-password-change-user">
                            Open modal cambiar contraseña
                        </button>
                        <hr class="my-2">
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                        <a class="flex items-center py-2 px-3 rounded-md text-sm text-default-800 hover:bg-default-100"
                            href="#" onclick="document.getElementById('logout-form').submit();">
                            <i class="ti-power-off text-primary"></i>
                            Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<div id="modal-password-change-user" class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-x-hidden overflow-y-auto hidden pointer-events-none">
    <div class="-translate-y-5 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 sm:max-w-lg sm:w-full my-8 sm:mx-auto flex flex-col bg-white shadow-sm rounded">
        <div class="flex flex-col border border-default-200 shadow-sm rounded-lg  pointer-events-auto">
            <div class="flex justify-between items-center py-3 px-4 border-b border-default-200">
                <h3 class="text-lg font-medium text-default-900">
                    Cambiar contraseña
                </h3>
                <button type="button" class="text-default-600 cursor-pointer" data-hs-overlay="#modal-password-change-user">
                    <i class="i-tabler-x text-lg"></i>
                </button>
            </div>
            <div class="p-4 overflow-y-auto">
                <p class="mt-1 text-default-600">
                    <div class="grid lg:grid-cols-3 gap-6">
                        <div class="col-span-2">
                            <label for="txt-new-password-user" class="text-default-800 text-sm font-medium inline-block mb-2">Colocar Contraseña</label>
                            <div x-data="{ show: false }" class="flex gap-1 items-center">
                                <input
                                :type="show ? 'text' : 'password'"
                                id="txt-new-password-user"
                                class="form-input"
                                >
                                <input type="hidden" name="txtUserNombre" id="txt-user-nombre-pass" value="{{ session('usuario') }}">

                                <button
                                type="button"
                                class="btn bg-info text-white"
                                @click="show = !show"
                                >
                                    <span x-text="show ? 'ocultar' : 'ver'"></span>
                                </button>
                            </div>
                            
                        </div>
                        <div class="col-span-2">
                            <button type="button" class="btn bg-primary text-white" onclick="passwordChangeUserGeneralMe()">Actualizar</button>
                        </div>
                    </div>
                </p>
            </div>
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-default-200">
                <button type="button" class="btn bg-secondary text-white" data-hs-overlay="#modal-password-change-user">
                    <i class="i-tabler-x me-1"></i>
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Topbar End -->