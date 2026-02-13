@include('layouts.shared/main')

<head>
    @include('layouts.shared/title-meta', ['title' => $title])
    @yield('css')
    @include('layouts.shared/head-css')
</head>

<body>

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

    @include('layouts.shared/footer-scripts')


    <script>
        document.addEventListener("DOMContentLoaded", function () {

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







    @yield('script')

    @vite(['resources/js/app.js'])

</body>

</html>