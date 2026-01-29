<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #34A1E4;
            --dark-navy: #242746;
        }
        
        body {
            margin: 0;
            overflow: hidden;
            background: linear-gradient(135deg, var(--dark-navy) 0%, #1a1c38 40%, #2c3e60 100%);
            min-height: 100vh;
        }
        
        .background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        
        /* PRUEBA DE MODIFICACION - EVER */
        
        .shape {
            position: absolute;
            opacity: 0.15;
        }
        
        .circle {
            border-radius: 50%;
            background-color: var(--primary-blue);
        }
        
        .square {
            background-color: var(--primary-blue);
            transform: rotate(45deg);
        }
        
        .triangle {
            width: 0;
            height: 0;
            border-left: 50px solid transparent;
            border-right: 50px solid transparent;
            border-bottom: 86px solid var(--primary-blue);
        }
        
        @keyframes cardEntrance {
            0% {
                opacity: 0;
                transform: translateY(25px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-card {
            animation: cardEntrance 0.8s ease-out forwards;
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .btn-primary {
            background-color: var(--primary-blue);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #2b8ac5;
            transform: translateY(-2px);
        }
        
        .input-focus:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 2px rgba(52, 161, 228, 0.2);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <!-- Background with minimalist shapes -->
    <div class="background">
        <!-- Large circle -->
        <div class="shape circle" style="width: 300px; height: 300px; top: -100px; right: -50px;"></div>
        
        <!-- Small square -->
        <div class="shape square" style="width: 100px; height: 100px; bottom: 100px; left: 150px;"></div>
        
        <!-- Medium circle -->
        <div class="shape circle" style="width: 200px; height: 200px; bottom: -50px; right: 25%;"></div>
        
        <!-- Triangle -->
        <div class="shape triangle" style="top: 20%; left: 10%;"></div>
        
        <!-- Small circle -->
        <div class="shape circle" style="width: 80px; height: 80px; top: 30%; right: 20%;"></div>
    </div>

    <div class="w-full max-w-md p-8 space-y-6 rounded-xl login-card transition-all duration-300 hover:shadow-2xl mx-4">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 mb-4 rounded-full" style="background-color: #34A1E4;">
                <i class="fas fa-user-circle text-4xl text-white"></i>
            </div>
            <h2 class="text-3xl font-bold" style="color: #242746;">Bienvenido</h2>
            <p class="mt-2 text-sm" style="color: #242746;">Ingresa tus credenciales para acceder a tu cuenta</p>
        </div>
        
        @if(session('error'))
        <div class="p-3 text-sm text-red-700 bg-red-100 rounded-lg">
            {{ session('error') }}
        </div>
        @endif
        
        <form action="{{ url('login/validar') }}" method="post" class="space-y-5">
            @csrf
            <div>
                <label for="username" class="block text-sm font-medium" style="color: #242746;">Nombre de usuario</label>
                <div class="relative mt-1">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fas fa-user" style="color: #34A1E4;"></i>
                    </div>
                    <input type="text" id="username" name="username" 
                           class="w-full py-3 pl-10 pr-4 border rounded-lg focus:outline-none input-focus transition-all"
                           style="border-color: #e5e7eb;"
                           placeholder="Ingresa tu nombre de usuario" required>
                </div>
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium" style="color: #242746;">Contraseña</label>
                <div class="relative mt-1">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fas fa-lock" style="color: #34A1E4;"></i>
                    </div>
                    <input type="password" id="password" name="password" 
                           class="w-full py-3 pl-10 pr-4 border rounded-lg focus:outline-none input-focus transition-all"
                           style="border-color: #e5e7eb;"
                           placeholder="Ingresa tu contraseña" required>
                </div>
            </div>
            
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember_me" name="remember_me" type="checkbox" 
                           class="w-4 h-4 border-gray-300 rounded focus:ring-2"
                           style="--tw-ring-color: #34A1E4; color: #34A1E4;">
                    <label for="remember_me" class="block ml-2 text-sm" style="color: #242746;">Recordarme</label>
                </div>
                <div class="text-sm">
                    <a href="#" class="font-medium hover:underline" style="color: #34A1E4;">¿Olvidaste tu contraseña?</a>
                </div>
            </div>
            
            <button type="submit" 
                    class="w-full px-4 py-3 font-medium text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 btn-primary shadow-md hover:shadow-lg"
                    style="--tw-ring-color: #34A1E4;">
                Iniciar Sesión
            </button>
        </form>
        
        <!-- <div class="pt-4">
            <p class="text-sm text-center" style="color: #242746;">
                ¿No tienes cuenta? <a href="#" class="font-medium hover:underline" style="color: #34A1E4;">Regístrate ahora</a>
            </p>
        </div> -->
    </div>
</body>
</html>

