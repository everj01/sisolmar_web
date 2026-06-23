<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(145deg, #0a1628 0%, #0d1e36 50%, #0a1e3d 100%);
            min-height: 100vh;
        }

        /* Círculos decorativos sutiles en el fondo */
        body::before {
            content: '';
            position: fixed;
            top: -150px;
            right: -150px;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(250, 185, 50, 0.07) 0%, transparent 65%);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -150px;
            left: -100px;
            width: 450px;
            height: 450px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(250, 185, 50, 0.05) 0%, transparent 65%);
            pointer-events: none;
        }

        .login-card {
            animation: slideUp 0.45s ease-out both;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .input-field {
            border: 1.5px solid #e8edf3;
            background-color: #f8fafc;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .input-field:focus {
            outline: none;
            border-color: #FAB932;
            box-shadow: 0 0 0 3px rgba(250, 185, 50, 0.18);
            background-color: #fff;
        }

        .btn-login {
            background-color: #FAB932;
            color: #0a1628;
            font-weight: 700;
            letter-spacing: 0.03em;
            transition: background-color 0.2s, transform 0.15s, box-shadow 0.2s;
        }

        .btn-login:hover {
            background-color: #e5a620;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(250, 185, 50, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: none;
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen p-4">

    <div class="w-full max-w-sm login-card">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">

            {{-- Barra superior amarilla --}}
            <div class="h-1" style="background: #FAB932;"></div>

            <div class="px-8 pt-8 pb-6">

                {{-- Logo: agrega el src de tu imagen aquí --}}
                {{-- Ejemplo: src="{{ asset('images/logo.png') }}" --}}
                <div class="flex justify-center mb-7">
                    <img src="{{ asset('images/logo_sol.png') }}" alt="Logo" class="h-14 object-contain">
                </div>

                {{-- Título --}}
                <div class="text-center mb-7">
                    <h1 class="text-xl font-bold tracking-tight" style="color: #0a1628;">
                        Iniciar Sesión
                    </h1>
                    <p class="text-xs text-gray-400 mt-1">Ingresa tus credenciales para continuar</p>
                </div>

                {{-- Error de sesión --}}
                @if(session('error'))
                    <div
                        class="mb-5 p-3 rounded-lg bg-red-50 border border-red-200 flex items-start gap-2 text-sm text-red-700">
                        <i class="fas fa-circle-exclamation mt-0.5 text-red-400 flex-shrink-0"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                {{-- Formulario --}}
                <form action="{{ url('validate') }}" method="POST" class="space-y-4">
                    @csrf

                    {{-- Usuario --}}
                    <div>
                        <label for="username"
                            class="block text-xs font-semibold mb-1.5 text-gray-600 uppercase tracking-wide">
                            Usuario
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                                <i class="fas fa-user text-xs"></i>
                            </span>
                            <input type="text" id="username" name="username"
                                class="input-field w-full pl-9 pr-4 py-2.5 rounded-lg text-sm text-gray-800"
                                placeholder="Nombre de usuario" required autocomplete="username">
                        </div>
                    </div>

                    {{-- Contraseña --}}
                    <div>
                        <label for="password"
                            class="block text-xs font-semibold mb-1.5 text-gray-600 uppercase tracking-wide">
                            Contraseña
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                                <i class="fas fa-lock text-xs"></i>
                            </span>
                            <input type="password" id="password" name="password"
                                class="input-field w-full pl-9 pr-10 py-2.5 rounded-lg text-sm text-gray-800"
                                placeholder="Contraseña" required autocomplete="current-password">
                            <button type="button" id="toggle-password"
                                class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="fas fa-eye text-xs" id="eye-icon"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Recordarme --}}
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="remember_me" name="remember_me"
                            class="w-4 h-4 rounded border-gray-300 cursor-pointer" style="accent-color: #FAB932;">
                        <label for="remember_me" class="text-xs text-gray-500 cursor-pointer select-none">
                            Recordarme
                        </label>
                    </div>

                    {{-- Botón --}}
                    <button type="submit" class="btn-login w-full py-2.5 rounded-lg text-sm mt-2">
                        <i class="fas fa-right-to-bracket me-2"></i>
                        Ingresar
                    </button>

                </form>
            </div>

            {{-- Pie de card --}}
            <div class="px-8 py-3 text-center border-t border-gray-100 bg-gray-50">
                <p class="text-xs text-gray-400">Sol Security &copy; {{ date('Y') }}</p>
            </div>

        </div>
    </div>

    <script>

        document.getElementById('toggle-password').addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !isPassword);
            icon.classList.toggle('fa-eye-slash', isPassword);
        });

        // Recordarme — localStorage
        const inputUsuario = document.getElementById('username');
        const chkRecordarme = document.getElementById('remember_me');

        // Al cargar: si hay usuario guardado, precargarlo
        const usuarioGuardado = localStorage.getItem('login_usuario');
        if (usuarioGuardado) {
            inputUsuario.value = usuarioGuardado;
            chkRecordarme.checked = true;
        }

        // Al enviar el form: guardar o limpiar según el checkbox
        document.querySelector('form').addEventListener('submit', function () {
            if (chkRecordarme.checked) {
                localStorage.setItem('login_usuario', inputUsuario.value.trim());
            } else {
                localStorage.removeItem('login_usuario');
            }
        });
    </script>



</body>

</html>