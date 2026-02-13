@extends('layouts.vertical', ['title' => 'Gestión DJ'])

@section('css')

@endsection

@section('content')

    @include("layouts.shared/page-title", ["subtitle" => "Recursos Humanos", "title" => "File Control"])


    <style>
        .fila-formulario {
            margin-bottom: 1.5rem;
            /* 32px */
        }
    </style>


    <div class="grid lg:grid-cols-2 gap-6 mt-8">
        <div class="card overflow-hidden">
            <div class="card-header">
                <h4 class="card-title">Listado de Personal</h4>
            </div>

            <div class="w-full px-5 py-2 mt-3 flex justify-between items-center">
                <input 
                    type="text" 
                    id="buscarPersonal" 
                    placeholder="Buscar..."
                    class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm uppercase"
                    autocomplete="off" 
                />

                <button 
                    id="btnNuevaDJ"
                    class="btn rounded-full bg-primary/25 text-primary hover:bg-primary hover:text-white flex items-center gap-1 px-4 py-1"
                >
                    <i class='bx bx-plus text-base'></i>
                    <span>Nueva DJ</span>
                </button>
            </div>

            <div class="w-full px-5 py-2 mt-3">
                <div id="tblPersonas" class="w-full mt-5"></div>
                <div class="flex items-center gap-2">
                    <label for="page-size" class="text-sm text-gray-600">
                        Mostrar
                    </label>

                    <select id="page-size"
                         class="w-20 px-3 py-1.5 pr-8 text-sm border border-gray-300 rounded-lg 
                                    focus:ring-primary focus:border-primary bg-white
                                    bg-none appearance-auto">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>

                    <span class="text-sm text-gray-600">registros</span>
                </div>
            </div>
        </div>
    </div>


    <div id="divCoincidencias" class="grid lg:grid-cols-1 gap-6 mt-8 hidden">
        <div class="card overflow-hidden">
            <div class="card-header">
                <h4 class="card-title">Listado de COINCIDENCIAS</h4>
            </div>
            <div class="w-full px-5 py-2 mt-3">
                <input type="text" id="buscar" placeholder="Buscar..."
                    class="w-40 px-3 py-1 border border-gray-300 rounded-full focus:outline-none focus:border-blue-500 transition-all text-sm" />
                <div id="tblPersonasCN" class="w-full mt-8"></div>
            </div>
        </div>
    </div>


    <!-- Modal del Formulario -->
    <div id="formModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto flex py-10">
        <!-- Contenedor -->
        <!-- <div class="bg-white rounded-lg shadow-lg max-w-2xl w-auto mx-auto my-10 max-h-[70vh] overflow-y-auto"> -->
        <div class="bg-white rounded-lg shadow-lg w-full max-w-6xl mx-auto my-10 max-h-[85vh] overflow-y-auto">
            <form id="formDatos" method="POST" class="p-6 space-y-8">
                @csrf

                <input type="hidden" name="cod_postulante" id="cod_postulante">

                <!-- Mis Datos Personales -->
                <div class="border rounded-lg shadow mb-2">
                    <div class="bg-primary text-white px-4 py-2 font-semibold rounded-t-lg"> MIS DATOS PERSONALES</div>
                    <div class="p-6 space-y-10">

                        <section class="bg-white shadow-md rounded-xl p-6 mb-8 border border-gray-200">
                            <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5.121 17.804A9 9 0 1118.879 6.196 9 9 0 015.121 17.804zM15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <h2 class="text-lg font-semibold text-gray-800">Identidad</h2>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                                <div>
                                <label for="nombres_apellidos" class="block text-sm font-medium text-gray-700 mb-1">
                                    Nombres y Apellidos
                                </label>
                                <input
                                    type="text"
                                    id="nombres_apellidos"
                                    name="nombres_apellidos"
                                    placeholder="Ingrese nombres y apellidos completos"
                                    required
                                    class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary transition duration-200"
                                >

                                <a
                                    href="https://eldni.com/pe/buscar-datos-por-dni"
                                    target="_blank"
                                    class="mt-3 inline-block border border-primary text-primary font-medium px-4 py-2 rounded-lg text-sm hover:bg-primary hover:text-white transition duration-200"
                                >
                                    Consultar DNI
                                </a>
                                </div>

                                <div class="flex flex-col items-center">
                                <div class="flex items-center justify-center rounded-lg w-32 h-32 relative">
                                    <div
                                    id="placeholderFoto"
                                    class="text-center w-full h-full flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                    >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-400 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 7a4 4 0 014-4h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4V7zm4 0h10m-5 4v5m0 0l-2-2m2 2l2-2" />
                                    </svg>
                                    <p class="text-sm text-gray-500">FOTO</p>
                                    </div>

                                    <img id="previewFoto" class="hidden w-full h-full object-cover rounded-lg" />

                                    <input type="file" id="inputFoto" accept="image/*" class="hidden" />
                                </div>

                                <div class="mt-3 flex gap-2">
                                    <button
                                    type="button"
                                    id="btnSubirFoto"
                                    class="border rounded-md bg-gray-100 text-gray-700 px-3 py-1 text-sm hover:bg-gray-200 transition"
                                    >
                                    Subir Foto
                                    </button>

                                    <button
                                    type="button"
                                    id="btnEliminarFoto"
                                    class="hidden border rounded-md bg-red-100 text-red-700 px-3 py-1 text-sm hover:bg-red-200 transition"
                                    >
                                    Eliminar
                                    </button>
                                </div>
                                </div>
                            </div>
                        </section>

                        <!-- ======== DATOS PERSONALES ======== -->
                        <section class="bg-white shadow-md rounded-xl p-6 mb-8 border border-gray-200 mt-4">
                            <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M5.121 17.804A10.97 10.97 0 0112 15c2.21 0 4.26.64 5.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <h2 class="text-lg font-semibold text-gray-800">Datos Personales</h2>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 mb-6">
                                <div>
                                    <label for="dni" class="block text-sm font-medium text-gray-700 mb-1">DNI</label>
                                    <input type="text" id="dni" name="dni" maxlength="8" required
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                                        placeholder="12345678">
                                </div>
                                <div>
                                    <label for="caduca" class="block text-sm font-medium text-gray-700 mb-1">Caduca</label>
                                    <input type="date" id="caduca" name="caduca"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                </div>

                                <div>
                                    <label for="estado_civil" class="block text-sm font-medium text-gray-700 mb-1">Estado Civil</label>
                                    <select id="estado_civil" name="estado_civil"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>
                                        <option value="SOLTERO">Soltero(a)</option>
                                        <option value="CASADO">Casado(a)</option>
                                        <option value="DIVORCIADO">Divorciado(a)</option>
                                        <option value="VIUDO">Viudo(a)</option>
                                        <option value="CONVIVIENTE">Conviviente</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <label for="sexo" class="block text-sm font-medium text-gray-700 mb-1">Sexo</label>
                                    <select id="sexo" name="sexo"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>
                                        <option value="MASCULINO">Masculino</option>
                                        <option value="FEMENINO">Femenino</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="fecha_nacimiento" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
                                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                </div>

                                <div>
                                    <label for="sabe_nadar" class="block text-sm font-medium text-gray-700 mb-1">¿Sabe nadar?</label>
                                    <select id="sabe_nadar" name="sabe_nadar"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>
                                        <option value="SI">Sí</option>
                                        <option value="NO">No</option>
                                    </select>
                                </div>
                            </div>
                        </section>



                        <!-- INFORMACIÓN DE CONTACTO -->
                        <section class="bg-white shadow-md rounded-xl p-6 border border-gray-200 mt-4">
                            <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.372 4.115a1 1 0 01-.21.979l-2.073 2.073a11.05 11.05 0 005.293 5.293l2.073-2.073a1 1 0 01.979-.21l4.115 1.372a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <h2 class="text-lg font-semibold text-gray-800">Información de Contacto</h2>
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                <div>
                                    <label for="celular" class="block text-sm font-medium text-gray-700 mb-1">Celular</label>
                                    <input type="text" id="celular" name="celular" maxlength="9" required placeholder="999 999 999"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                </div>

                                <div>
                                    <label for="correo" class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                                    <input type="email" id="correo" name="correo" placeholder="ejemplo@correo.com" required
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                </div>

                                <div>
                                    <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
                                    <input type="text" id="whatsapp" name="whatsapp" maxlength="9" placeholder="999 999 999"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                </div>
                            </div>
                        </section>
                        
                        <!-- ======== INFORMACIÓN MÉDICA ======== -->
                        <section class="bg-white shadow-md rounded-xl p-6 mb-8 border border-gray-200 mt-4">
                            <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v8m-4-4h8m7 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h2 class="text-lg font-semibold text-gray-800">Información Médica</h2>
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                <div>
                                    <label for="tipo_sangre" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Sangre</label>
                                    <select id="tipo_sangre" name="tipo_sangre"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="peso" class="block text-sm font-medium text-gray-700 mb-1">Peso (Kg)</label>
                                    <input type="number" id="peso" name="peso" step="0.1" placeholder="70"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                </div>

                                <div>
                                    <label for="talla" class="block text-sm font-medium text-gray-700 mb-1">Talla (m)</label>
                                    <input type="number" id="talla" name="talla" step="0.01" placeholder="1.75"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                </div>
                            </div>
                        </section>

                        <!-- INFORMACIÓN PREVISIONAL -->
                        <section class="bg-white shadow-md rounded-xl p-6 mb-8 border border-gray-200 mt-4">
                            <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8c-1.657 0-3 1.343-3 3v7h6v-7c0-1.657-1.343-3-3-3zM5 13h14M5 17h14M9 21h6" />
                                </svg>
                                <h2 class="text-lg font-semibold text-gray-800">Información Previsional</h2>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <label for="sistema_previsional" class="block text-sm font-medium text-gray-700 mb-1">
                                        Sistema Previsional
                                    </label>
                                    <select id="sistema_previsional" name="sistema_previsional"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>
                                        <option value="ONP">ONP</option>
                                        <option value="AFP">AFP</option>
                                        <option value="APF-MIXTA">AFP - Mixta</option>
                                        <option value="AFP-FLUJO">AFP - Flujo</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="essalud" class="block text-sm font-medium text-gray-700 mb-1">
                                        ESSALUD Vida
                                    </label>
                                    <select id="essalud" name="essalud"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>
                                        <option value="SI">Sí</option>
                                        <option value="NO">No</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="pensionista" class="block text-sm font-medium text-gray-700 mb-1">
                                        Pensionista
                                    </label>
                                    <select id="pensionista" name="pensionista"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>
                                        <option value="SI">Sí</option>
                                        <option value="NO">No</option>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <!-- EDUCACIÓN -->
                        <section class="bg-white shadow-md rounded-xl p-6 mb-8 border border-gray-200 mt-4">
                            <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 14l9-5-9-5-9 5 9 5zM12 14v7m0 0l-3-2m3 2l3-2" />
                                </svg>
                                <h2 class="text-lg font-semibold text-gray-800">Educación</h2>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label for="grado_instruccion" class="block text-sm font-medium text-gray-700 mb-1">
                                        Grado de Instrucción
                                    </label>
                                    <select id="grado_instruccion" name="grado_instruccion"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>

                                        @foreach ($grados as $grado)
                                            <option value="{{ $grado->codigo }}">
                                                {{ $grado->descripcion }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="institucion" class="block text-sm font-medium text-gray-700 mb-1">
                                        Institución
                                    </label>
                                    <select id="institucion" name="institucion"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>

                                        @foreach ($instituciones as $institucion)
                                            <option value="{{ $institucion->codigo }}">
                                                {{ $institucion->descripcion }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="carrera" class="block text-sm font-medium text-gray-700 mb-1">
                                        Carrera
                                    </label>
                                    <select id="carrera" name="carrera"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>

                                        @foreach ($carreras as $carrera)
                                            <option value="{{ $carrera->codigo }}">
                                                {{ $carrera->descripcion }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="anio_egreso" class="block text-sm font-medium text-gray-700 mb-1">
                                        Año de egreso
                                    </label>
                                    <input type="number" id="anio_egreso" name="anio_egreso" min="1950" max="2030" placeholder="2020"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                </div>
                            </div>
                        </section>

                        <!-- INFORMACIÓN ADICIONAL -->
                        <section class="bg-white shadow-md rounded-xl p-6 mb-8 border border-gray-200 mt-4">
                            <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                </svg>
                                <h2 class="text-lg font-semibold text-gray-800">Información Adicional</h2>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label for="embargos" class="block text-sm font-medium text-gray-700 mb-1">
                                        Embargos en instituciones financieras
                                    </label>
                                    <select id="embargos" name="embargos"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>
                                        <option value="SI">Sí</option>
                                        <option value="NO">No</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="consumo_sustancias" class="block text-sm font-medium text-gray-700 mb-1">
                                        Consumo de sustancias ilícitas
                                    </label>
                                    <select id="consumo_sustancias" name="consumo_sustancias"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>
                                        <option value="SI">Sí</option>
                                        <option value="NO">No</option>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <!-- DIRECCIÓN ACTUAL -->
                        <section class="bg-white shadow-md rounded-xl p-6 mb-8 border border-gray-200 mt-4">
                            <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 9 6.343 9 8s1.343 3 3 3zm0 0c-4.418 0-8 3.582-8 8a8 8 0 0016 0c0-4.418-3.582-8-8-8z" />
                                </svg>
                                <h2 class="text-lg font-semibold text-gray-800">Dirección Actual</h2>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <label for="departamento_actual" class="block text-sm font-medium text-gray-700 mb-1">
                                        Departamento
                                    </label>
                                    <select id="departamento_actual" name="departamento_actual"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="provincia_actual" class="block text-sm font-medium text-gray-700 mb-1">
                                        Provincia
                                    </label>
                                    <select id="provincia_actual" name="provincia_actual"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="distrito_actual" class="block text-sm font-medium text-gray-700 mb-1">
                                        Distrito
                                    </label>
                                    <select id="distrito_actual" name="distrito_actual"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-4 mt-6">
                                <div>
                                    <label for="direccion_actual" class="block text-sm font-medium text-gray-700 mb-1">
                                        Descripción
                                    </label>
                                    <textarea id="direccion_actual" name="direccion_actual" rows="2" required placeholder="Ingrese su dirección actual completa"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"></textarea>
                                </div>
                            </div>
                        </section>

                        <!-- DIRECCIÓN DNI -->
                        <section class="bg-white shadow-md rounded-xl p-6 mb-8 border border-gray-200 mt-4">
                            <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 9 6.343 9 8s1.343 3 3 3zm0 0c-4.418 0-8 3.582-8 8a8 8 0 0016 0c0-4.418-3.582-8-8-8z" />
                                </svg>
                                <h2 class="text-lg font-semibold text-gray-800">Dirección DNI</h2>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <label for="departamento_dni" class="block text-sm font-medium text-gray-700 mb-1">
                                        Departamento
                                    </label>
                                    <select id="departamento_dni" name="departamento_dni"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="provincia_dni" class="block text-sm font-medium text-gray-700 mb-1">
                                        Provincia
                                    </label>
                                    <select id="provincia_dni" name="provincia_dni"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="distrito_dni" class="block text-sm font-medium text-gray-700 mb-1">
                                        Distrito
                                    </label>
                                    <select id="distrito_dni" name="distrito_dni"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="">Seleccionar</option>
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-4 mt-6">
                                <div>
                                    <label for="direccion_dni" class="block text-sm font-medium text-gray-700 mb-1">
                                        Descripción
                                    </label>
                                    <textarea id="direccion_dni" name="direccion_dni" rows="2" placeholder="Dirección registrada en el DNI"
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"></textarea>
                                </div>
                            </div>
                        </section>

                        <!-- CONTACTO DE EMERGENCIA -->
                        <section class="bg-white shadow-md rounded-xl p-6 mb-8 border border-gray-200 mt-4">
                            <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01M4.93 4.93l14.14 14.14M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                </svg>
                                <h2 class="text-lg font-semibold text-gray-800">Contacto de Emergencia</h2>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <div>
                                <label for="contacto_emergencia" class="block text-sm font-medium text-gray-700 mb-1">
                                    En caso de emergencia llamar a
                                </label>
                                <input
                                    type="text"
                                    id="contacto_emergencia"
                                    name="contacto_emergencia"
                                    required
                                    placeholder="Ej: Juan Pérez García"
                                    class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary transition duration-200"
                                >
                                </div>

                                <div>
                                <label for="celular_emergencia" class="block text-sm font-medium text-gray-700 mb-1">
                                    Número de celular
                                </label>
                                <input
                                    type="text"
                                    id="celular_emergencia"
                                    name="celular_emergencia"
                                    maxlength="9"
                                    required
                                    pattern="[0-9]{9}"
                                    placeholder="999 999 999"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                    class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary transition duration-200"
                                >
                                </div>

                                <div>
                                <label for="parentesco_emergencia" class="block text-sm font-medium text-gray-700 mb-1">
                                    Parentesco
                                </label>
                                <input
                                    type="text"
                                    id="parentesco_emergencia"
                                    name="parentesco_emergencia"
                                    required
                                    placeholder="Ej: Madre, Hermano, Esposo(a)"
                                    class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary transition duration-200"
                                >
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <!-- Mis Datos Laborales -->
                <div class="border rounded-lg shadow mb-2">
                    <div class="bg-primary text-white px-4 py-2 font-semibold rounded-t-lg">
                        MIS DATOS LABORALES
                    </div>
                    <div class="p-6 space-y-6">


                        <!-- 🟦 CURSO SUCAMEC / SMO -->
                        <section class="bg-white shadow-md rounded-xl p-6 mb-8 border border-gray-200">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m0 0a9 9 0 11-9-9" />
                            </svg>
                            <h2 class="text-lg font-semibold text-gray-800">Curso SUCAMEC y Servicio Militar</h2>
                        </div>

                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                            <label for="curso_sucamec" class="block text-sm font-medium text-gray-700 mb-1">Curso SUCAMEC</label>
                            <select id="curso_sucamec" name="curso_sucamec"
                                class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                <option value="">Seleccionar</option>
                                <option value="SI">Sí</option>
                                <option value="NO">No</option>
                            </select>
                            </div>

                            <div id="institucion_container" class="hidden">
                            <label for="institucion_laboral" class="block text-sm font-medium text-gray-700 mb-1">Institución</label>
                            <input type="text" id="institucion_laboral" name="institucion_laboral"
                                class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                                placeholder="Institución donde realizó el curso">
                            </div>

                            <div>
                            <label for="smo" class="block text-sm font-medium text-gray-700 mb-1">S.M.O.</label>
                            <select id="smo" name="smo"
                                class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                <option value="">Seleccionar</option>
                                <option value="NO">No</option>
                                <option value="MARINA">Marina</option>
                                <option value="EJERCITO">Ejército</option>
                                <option value="AVIACION">Aviación</option>
                            </select>
                            </div>
                        </div>
                        </section>

                        <!-- 🟦 LICENCIA DE ARMA -->
                        <section class="bg-white shadow-md rounded-xl p-6 mb-8 border border-gray-200 mt-4">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 13l6-6m0 0l6 6m-6-6v12" />
                            </svg>
                            <h2 class="text-lg font-semibold text-gray-800">Licencia y Tipo de Arma</h2>
                        </div>

                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                            <label for="licencia_arma" class="block text-sm font-medium text-gray-700 mb-1">Licencia de Arma</label>
                            <input id="licencia_arma" name="licencia_arma" placeholder="Ingrese licencias..."
                                class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            </div>

                            <div>
                            <label for="tipo_arma" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Arma</label>
                            <select id="tipo_arma" name="tipo_arma"
                                class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                <option value="">Seleccionar</option>
                                <option value="PISTOLA">Pistola</option>
                                <option value="REVOLVER">Revólver</option>
                                <option value="ESCOPETA">Escopeta</option>
                                <option value="RIFLE">Rifle</option>
                            </select>
                            </div>

                            <div>
                            <label for="arma_propia" class="block text-sm font-medium text-gray-700 mb-1">Arma Propia</label>
                            <select id="arma_propia" name="arma_propia"
                                class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                <option value="">Seleccionar</option>
                                <option value="SI">Sí</option>
                                <option value="NO">No</option>
                            </select>
                            </div>
                        </div>
                        </section>

                        <!-- 🟦 BREVETE -->
                        <section class="bg-white shadow-md rounded-xl p-6 mb-8 border border-gray-200 mt-4">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 13l2-5h14l2 5M5 13v5h2v-2h10v2h2v-5M5 13h14" />
                            </svg>
                            <h2 class="text-lg font-semibold text-gray-800">Licencia de Conducir</h2>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                            <label for="brevete" class="block text-sm font-medium text-gray-700 mb-1">N° Brevete</label>
                            <input type="text" id="brevete" name="brevete"
                                class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                                placeholder="Número de brevete">
                            </div>

                            <div>
                            <label for="clase_brevete" class="block text-sm font-medium text-gray-700 mb-1">Clase</label>
                            <select id="clase_brevete" name="clase_brevete"
                                class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                <option value="">Seleccionar</option>
                                <option value="A-I">A-I</option>
                                <option value="A-IIa">A-IIa</option>
                                <option value="A-IIb">A-IIb</option>
                                <option value="A-III">A-III</option>
                                <option value="B-I">B-I</option>
                                <option value="B-IIa">B-IIa</option>
                                <option value="B-IIb">B-IIb</option>
                                <option value="B-IIc">B-IIc</option>
                            </select>
                            </div>

                            <div>
                            <label for="tipo_vehiculo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Vehículo</label>
                            <input type="text" id="tipo_vehiculo" name="tipo_vehiculo"
                                class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                                placeholder="Tipo de vehículo">
                            </div>

                            <div>
                            <label for="vehiculo_propio" class="block text-sm font-medium text-gray-700 mb-1">Vehículo Propio</label>
                            <select id="vehiculo_propio" name="vehiculo_propio"
                                class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                <option value="">Seleccionar</option>
                                <option value="SI">Sí</option>
                                <option value="NO">No</option>
                            </select>
                            </div>
                        </div>
                        </section>

                        <!-- 🟦 EXPERIENCIA LABORAL -->
                        <section class="bg-white shadow-md rounded-xl p-6 mb-8 border border-gray-200 mt-4">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 7V4h6v3m-9 4h12v9H6V11z" />
                            </svg>
                            <h2 class="text-lg font-semibold text-gray-800">Experiencia Laboral</h2>
                        </div>

                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                            <label for="empresa_anterior" class="block text-sm font-medium text-gray-700 mb-1">Empresa Anterior</label>
                            <input type="text" id="empresa_anterior" name="empresa_anterior"
                                class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                                placeholder="Nombre de la empresa">
                            </div>

                            <div>
                            <label for="cargo_anterior" class="block text-sm font-medium text-gray-700 mb-1">Cargo</label>
                            <input type="text" id="cargo_anterior" name="cargo_anterior"
                                class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                                placeholder="Cargo desempeñado">
                            </div>

                            <div>
                            <label for="duracion_anterior" class="block text-sm font-medium text-gray-700 mb-1">Duración</label>
                            <input type="text" id="duracion_anterior" name="duracion_anterior"
                                class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                                placeholder="Ej: 2 años, 6 meses">
                            </div>
                        </div>
                        </section>

                        <!-- 🟦 PROFESIÓN ALTERNA -->
                        <section class="bg-white shadow-md rounded-xl p-6 mb-8 border border-gray-200 mt-4">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 11c0 1.104-.896 2-2 2H4v5h16v-5h-6a2 2 0 01-2-2v-1a4 4 0 00-8 0v1z" />
                            </svg>
                            <h2 class="text-lg font-semibold text-gray-800">Profesión u Ocupación Alterna</h2>
                        </div>

                        <div>
                            <label for="profesion_alterna" class="block text-sm font-medium text-gray-700 mb-1">
                            Profesión u Ocupación Alterna
                            </label>
                            <input type="text" id="profesion_alterna" name="profesion_alterna"
                            class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                            placeholder="Otra profesión u ocupación">
                        </div>
                        </section>
                    </div>
                </div>

                <!-- Mis Datos Familiares -->
                <div class="border rounded-lg shadow">
                    <div class="bg-primary text-white px-4 py-2 font-semibold rounded-t-lg">
                        MIS DATOS FAMILIARES
                    </div>
                    <div class="p-6 space-y-6">

                        <section class="bg-white shadow-md rounded-xl p-6 mb-8 border border-gray-200">
                            <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16 12a4 4 0 10-8 0 4 4 0 008 0zM12 14v7m-7-7a9 9 0 0118 0v7H5v-7z" />
                                </svg>
                                <h2 class="text-lg font-semibold text-gray-800">Registros Familiares</h2>
                            </div>

                            <div id="familyContainer" class="space-y-4">
                                <!-- Familiar (item) -->
                                <div class="family-row grid grid-cols-1 md:grid-cols-3 gap-4 p-4 border border-gray-200 rounded-lg relative bg-gray-50" data-familia-row>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Parentesco</label>
                                        <select name="parentesco[]"
                                            class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                            <option value="">Seleccionar</option>
                                            <option value="PADRE">Padre</option>
                                            <option value="MADRE">Madre</option>
                                            <option value="ESPOSO">Esposo</option>
                                            <option value="ESPOSA">Esposa</option>
                                            <option value="HIJO">Hijo</option>
                                            <option value="HIJA">Hija</option>
                                            <option value="HERMANO">Hermano</option>
                                            <option value="HERMANA">Hermana</option>
                                            <option value="ABUELO">Abuelo</option>
                                            <option value="ABUELA">Abuela</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Apellidos y Nombres</label>
                                        <input type="text" name="apellidosNombres[]" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                                            placeholder="Apellidos y nombres completos">
                                    </div>
                                    <div class="flex gap-2">
                                        <div class="flex-1">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Nacimiento</label>
                                            <input type="date" name="fechaNacimiento[]" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        </div>
                                        <button type="button"
                                            class="remove-family self-end px-3 py-1 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition">
                                            Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button id="addFamilyMember" type="button"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                                    Agregar Familiar
                                </button>
                            </div>
                        </section>

                    </div>
                </div>

            </form>

            <!-- Footer fijo -->
            <div class="border-t bg-white px-6 py-4 flex justify-end space-x-3 sticky bottom-0">
                <button id="cerrarModal" type="button"
                    class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-100">
                    Cancelar
                </button>
                <button id="btnPrevisualizar" type="button"
                    class="px-4 py-2 rounded-md bg-secondary text-white hover:bg-secondary-dark">
                    Previsualizar
                </button>
                <button id="btnGuardar" type="submit" form="formDatos"
                    class="px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark">
                    Guardar
                </button>
            </div>


        </div>
    </div>

    <script>

    </script>



@endsection

@section('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.1/dist/jspdf.plugin.autotable.min.js"></script>

    <!-- <script>
        window.logoUrl = "{{ asset('images/logo_sol.png') }}";
    </script> -->

@endsection

@vite(['resources/js/functions/gestion_dj.js'])
@section('script')

@endsection