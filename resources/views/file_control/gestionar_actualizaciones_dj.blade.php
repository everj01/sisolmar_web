@extends('layouts.vertical', ['title' => 'Gestionar Periodos DJ'])

@section('css')
    <style>
        /* Pequeño ajuste para que el modal no se sobreponga feo */
        .modal-abierto { overflow: hidden; }
    </style>
@endsection

@section('content')
<div class="grid grid-cols-1 gap-6 p-6">
    <div class="card bg-white dark:bg-slate-800 shadow-md rounded-lg">
        
        <div class="p-6 flex items-center justify-between border-b border-gray-200 dark:border-slate-700">
            <h4 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Periodos de Actualización</h4>
            <button onclick="GestionActualizacionesDJ.abrirModalPeriodo()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors shadow-sm flex items-center gap-2">
                <i class="bx bx-plus"></i> Crear periodo de actualización
            </button>
        </div>
        
        <div class="p-6">
            <div class="overflow-x-auto">
                <table id="tablaPeriodos" class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                    <thead class="bg-gray-50 dark:bg-slate-700/50 text-gray-700 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3 font-medium">Periodo</th>
                            <th class="px-4 py-3 font-medium">F. Inicio</th>
                            <th class="px-4 py-3 font-medium">F. Fin</th>
                            <th class="px-4 py-3 font-medium">F. Corte</th>
                            <th class="px-4 py-3 font-medium text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- JS inyectará la data aquí -->
                        <tr><td colspan="5" class="text-center py-4">Cargando datos...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL PASO 1: DATOS DEL PERIODO            --}}
{{-- ========================================== --}}
<div id="modalPeriodo" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-2xl w-full max-w-2xl mx-4 relative flex flex-col">
        <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Nuevo Periodo de Actualización</h3>
            <button onclick="GestionActualizacionesDJ.cerrarModal('modalPeriodo')" class="text-gray-400 hover:text-gray-600"><i class="bx bx-x text-2xl"></i></button>
        </div>
        
        <div class="p-5">
            <form id="formPeriodo" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre del periodo (Opcional)</label>
                    <input type="text" id="nombre_periodo" class="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 form-input" placeholder="Ej. Actualización Anual 2026">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha de Inicio</label>
                    <input type="date" id="fecha_inicio" class="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 form-input">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha de Fin</label>
                    <input type="date" id="fecha_fin" class="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 form-input">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha de Corte</label>
                    <input type="date" id="fecha_corte" class="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 form-input">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Año</label>
                    <input type="number" id="anio_periodo" readonly class="w-full rounded-md bg-gray-100 dark:bg-slate-700 border-gray-300 text-gray-500 cursor-not-allowed form-input" placeholder="Se calcula solo">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cod. Empresa</label>
                    <select id="cod_empresa" class="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 form-select">
                        <option value="01" selected>01 - Empresa Principal</option>
                    </select>
                </div>
            </form>
        </div>

        <div class="p-5 border-t border-gray-200 dark:border-slate-700 flex justify-end gap-3">
            <button onclick="GestionActualizacionesDJ.cerrarModal('modalPeriodo')" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancelar</button>
            <button onclick="GestionActualizacionesDJ.irPasoPersonal()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center gap-2">
                Siguiente: Seleccionar Personal <i class="bx bx-right-arrow-alt"></i>
            </button>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL PASO 2: LISTADO APARTE DEL PERSONAL  --}}
{{-- ========================================== --}}
<div id="modalPersonal" class="hidden fixed inset-0 z-[110] flex items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity py-6">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] flex flex-col overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 z-20 shrink-0">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Seleccionar Personal</h3>
                <p class="text-sm text-gray-500" id="txtFiltroCorte">Filtrado por fecha de corte: --</p>
            </div>
            <button onclick="GestionActualizacionesDJ.volverPasoPeriodo()" class="text-gray-400 hover:text-gray-600"><i class="bx bx-arrow-back text-2xl"></i></button>
        </div>

        <!-- NUEVO: Filtros agregados por pedido del jefe -->
        <div class="px-5 py-3 bg-gray-50 dark:bg-slate-700/30 border-b border-gray-200 dark:border-slate-700 grid grid-cols-1 md:grid-cols-3 gap-4 shrink-0">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Buscar (DNI o Nombres)</label>
                <input type="text" id="filtro_buscador" class="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 form-input text-sm" placeholder="Ej. 7012... o Juan...">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Tipo de Personal</label>
                <select id="filtro_tipo" class="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 form-select text-sm">
                    <option value="">Todos los tipos</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Sucursal</label>
                <select id="filtro_sucursal" class="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 form-select text-sm">
                    <option value="">Todas las sucursales</option>
                </select>
            </div>
        </div>
        
        <!-- El min-h-0 es la magia para que flexbox respete el scroll -->
        <!-- Quitamos el p-5 y ponemos px-5 pb-5 pt-0 para sellar el techo -->
        <div class="px-5 pb-5 pt-0 overflow-y-auto flex-1 min-h-0">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400 relative">
                <!-- Hacemos la cabecera sticky para que siempre se vea al bajar -->
                <thead class="sticky top-0 z-[50] shadow-sm">
                    <tr>
                        <th class="px-4 py-3 bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-200"><input type="checkbox" class="form-checkbox rounded text-blue-600" id="checkAllPersonal"></th>
                        <th class="px-4 py-3 bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-200 font-semibold">Código</th>
                        <th class="px-4 py-3 bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-200 font-semibold">Tipo</th>
                        <th class="px-4 py-3 bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-200 font-semibold">DNI</th>
                        <th class="px-4 py-3 bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-200 font-semibold min-w-[200px]">Nombre del Empleado</th>
                        <th class="px-4 py-3 bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-200 font-semibold">Cargo</th>
                        <th class="px-4 py-3 bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-200 font-semibold">Sucursal</th>
                    </tr>
                </thead>
                <tbody id="tbodyPersonalCorte">
                    <!-- JS inyectará el personal simulado -->
                </tbody>
            </table>
        </div>

        <div class="p-5 border-t border-gray-200 dark:border-slate-700 flex justify-between gap-3">
            <button onclick="GestionActualizacionesDJ.volverPasoPeriodo()" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Atrás</button>
            <button onclick="GestionActualizacionesDJ.guardarTodoElPeriodo()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center gap-2">
                <i class="bx bx-save"></i> Guardar y Crear Periodo
            </button>
        </div>
    </div>
</div>
@endsection

@section('script')
{{-- NUEVO: Definimos un objeto global con las URLs dinámicas generadas por Laravel --}}
    <script>
        window.AppConfig = {
            urls: {
                listarPeriodos: "{{ url('/api/dj/periodos/listado') }}",
                personalCorte: "{{ url('/api/dj/periodos/personal-corte') }}",
                guardarPeriodo: "{{ url('/api/dj/periodos/guardar') }}"
            }
        };
    </script>
    {{-- Como está en resources, usamos la directiva de Vite --}}
    @vite(['resources/js/functions/gestionar_actualizaciones.js'])
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Un pequeño retardo en caso de que Vite cargue el módulo de forma asíncrona
            setTimeout(() => {
                if (typeof GestionActualizacionesDJ !== 'undefined') {
                    GestionActualizacionesDJ.init();
                } else {
                    console.warn("Vite cargó, pero no se encontró el objeto GestionActualizacionesDJ.");
                }
            }, 100);
        });
    </script>
@endsection