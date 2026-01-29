<!-- Start Sidebar -->
<aside id="app-menu"
       class="fixed left-0 top-0 w-64 h-full bg-white transform transition-transform duration-300
              -translate-x-full lg:translate-x-0
              hs-overlay hs-overlay-open:translate-x-0
              lg:block overflow-y-auto z-60">

    <div class="flex flex-col h-full">
        <!-- Sidenav Logo -->
        <div class="sticky top-0 flex h-topbar items-center justify-between px-6">
            <a href="{{ env('APP_URL') }}">
                <img  src="{{ asset('images/logo-light.png') }}" alt="logo" class="flex" width="150">
            </a>
        </div>

        <div class="p-4 h-[calc(100%-theme('spacing.topbar'))] flex-grow" data-simplebar>
            <!-- Menu -->
            <ul class="admin-menu hs-accordion-group flex w-full flex-col gap-1">
                @if(session()->has('permisos') && !empty(session('permisos')))
                    @foreach(session('permisos') as $menu)
                        <li class="menu-item hs-accordion" id="menu-{{ $menu['modulo'] }}">
                            <a href="javascript:void(0)"
                                class="hs-accordion-toggle group flex items-center gap-x-3.5 rounded-md px-3 py-2 text-sm font-medium text-default-100 transition-all hover:bg-default-100/5 hs-accordion-active:bg-default-100/5 hs-accordion-active:text-default-100">

                                {!! $menu['icono'] ?? "<i class='bx bx-folder-cog'></i>" !!}
                                <span class="menu-text"> {{ $menu['nombre'] ?? ucfirst($menu['modulo']) }} </span>
                                <span class="menu-arrow"></span>
                            </a>

                            <div class="hs-accordion-content hidden w-full overflow-hidden transition-[height] duration-300">
                                <ul class="mt-1 space-y-1">
                                    @foreach($menu['submenus'] ?? [] as $submenu)
                                        <li class="menu-item">
                                            <a class="flex items-center gap-x-3.5 rounded-md px-3 py-1.5 text-sm font-medium text-default-100 transition-all hover:bg-default-100/5"
                                                href="{{ route('second', [$menu['modulo'], $submenu['vista']]) }}">
                                                <i class="menu-dot"></i>

                                                {{ $submenu['nombre'] ?? ucfirst($submenu['vista']) }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    @endforeach
                @endif
            </ul>
        </div>

    </div>
</aside>
<!-- End Sidebar -->
