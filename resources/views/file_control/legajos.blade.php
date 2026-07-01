  @extends('layouts.vertical', ['title' => 'Gestión de Legajos'])

  @section('css')
  @endsection

  @section('content')
  @include("layouts.shared/page-title", ["subtitle" => "File Control", "title" => "Gestión de Legajos"])

  <style>
      .disabled-table { pointer-events: none; opacity: 0.3; }
  </style>

  <div class="grid lg:grid-cols-5 gap-6 mt-8">

      {{-- ── COLUMNA IZQUIERDA ── --}}
      <div class="lg:col-span-2 flex flex-col gap-6">

          {{-- CLIENTE --}}
          <div class="card">
              <div class="card-header flex justify-between items-center">
                  <h3 class="card-title">Cliente</h3>
                  <input type="text" id="buscarCliente" placeholder="Buscar..."
                      class="w-36 px-3 py-1 border border-gray-300 rounded-full text-sm
                             focus:outline-none focus:border-blue-500 transition-colors">
              </div>
              <div class="px-4 pb-4">
                  <div id="tblCliente" class="w-full mt-2"></div>
              </div>
          </div>

          {{-- CARGO --}}
          <div class="card">
              <div class="card-header flex justify-between items-center">
                  <h3 class="card-title">Cargo</h3>
                  <input type="text" id="buscarCargo" placeholder="Buscar..."
                      class="w-36 px-3 py-1 border border-gray-300 rounded-full text-sm
                             focus:outline-none focus:border-blue-500 transition-colors">
              </div>
              <div class="flex justify-center items-center gap-4 px-4 pt-3">
                  <div class="form-check">
                      <input type="radio" class="form-radio text-primary" name="cargoFiltro" id="radioTodos" value="TODOS" checked>
                      <label class="ms-1.5 text-sm" for="radioTodos">Todos</label>
                  </div>
                  <div class="form-check">
                      <input type="radio" class="form-radio text-primary" name="cargoFiltro" id="radioOper" value="OPERATIVO">
                      <label class="ms-1.5 text-sm" for="radioOper">Operativo</label>
                  </div>
                  <div class="form-check">
                      <input type="radio" class="form-radio text-primary" name="cargoFiltro" id="radioAdmin" value="ADMINISTRATIVO">
                      <label class="ms-1.5 text-sm" for="radioAdmin">Administrativo</label>
                  </div>
              </div>
              <div class="px-4 pb-4">
                  <div id="tblCargo" class="w-full mt-2"></div>
              </div>
          </div>

      </div>

      {{-- ── COLUMNA DERECHA: FOLIOS ── --}}
      <div class="card lg:col-span-3">

          <div class="card-header flex justify-between items-center">
              <div class="flex flex-col ">
                  <h4 class="card-title ms-0 ps-0">Folios del Legajo</h4> 
                  <span id="infoSeleccion" class=" text-gray-400 italic text-lg">
                      — Seleccione cliente y cargo
                  </span>
              </div>
              <div class="flex items-center gap-3">
                  <input type="text" id="buscarFolio" placeholder="Buscar folio..."
                      class="w-36 px-3 py-1 border border-gray-300 rounded-full text-sm
                             focus:outline-none focus:border-blue-500 transition-colors">

                  {{-- Notificaciones --}}
                   <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="relative p-1">
                            <i class="fa-solid fa-envelope text-xl text-gray-500 hover:text-gray-700 transition-colors"></i>
                            <span id="notifBadge"
                                    class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs
                                        rounded-full w-4 h-4 flex items-center justify-center leading-none">
                            </span>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-transition
                            class="absolute top-full mt-2 right-0 w-80 max-h-96 overflow-y-auto
                                    bg-white border border-gray-200 rounded-xl shadow-xl z-50 p-4">
                            <div id="notifContainer">
                                <p class="text-center text-gray-400 text-sm py-6">Cargando...</p>
                            </div>
                        </div>
                    </div>
              </div>
          </div>

          <div class="px-5 py-4">
              <div id="notifiSoli" class="flex flex-col items-center gap-1 mb-3"></div>
              
              {{-- TABLA PRINCIPAL (Solo seleccionados) --}}
              <div id="tblFolio" class="w-full"></div>
              
              {{-- NUEVO BOTÓN PARA ABRIR MODAL --}}
              <div class="mt-4 flex justify-start">
                  <button type="button" id="btnAbrirModalFolios" onclick="document.getElementById('modalAgregarFolios').classList.remove('hidden')"
                      class="btn rounded-full bg-primary/10 text-primary hover:bg-primary hover:text-white text-sm hidden transition-all">
                      <i class="fa-solid fa-plus me-1"></i> Agregar más folios
                  </button>
              </div>

              <input type="hidden" id="txtNombre">
              <input type="hidden" id="hidLegajo">

              <div class="flex justify-center mt-5">
                  <button type="button" id="btnRegistrar" onclick="guardarLegajo()" disabled
                      class="btn rounded-full bg-success/25 text-success hover:bg-success hover:text-white
                             disabled:opacity-40 disabled:cursor-not-allowed">
                      <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Legajo
                  </button>
              </div>
          </div>

      </div>

  </div>

  {{-- MODAL AGREGAR FOLIOS ADICIONALES --}}
  <div id="modalAgregarFolios" class="fixed inset-0 z-[80] hidden bg-gray-900 bg-opacity-50 flex items-center justify-center transition-all">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl flex flex-col max-h-[90vh] mx-4">
          <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50 rounded-t-xl">
              <h3 class="font-bold text-lg text-gray-800"><i class="fa-solid fa-list-check me-2 text-primary"></i>Seleccionar Folios Adicionales</h3>
              <button type="button" onclick="document.getElementById('modalAgregarFolios').classList.add('hidden')" class="text-gray-400 hover:text-red-500 transition-colors">
                  <i class="fa-solid fa-xmark text-2xl"></i>
              </button>
          </div>
          <div class="p-4 overflow-y-auto flex-1 bg-white">
              <div id="tblFolioModal" class="w-full"></div>
          </div>
          <div class="p-4 border-t border-gray-200 flex justify-end gap-3 bg-gray-50 rounded-b-xl">
              <button type="button" onclick="document.getElementById('modalAgregarFolios').classList.add('hidden')" class="btn border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 rounded-lg font-medium">Cancelar</button>
              <button type="button" id="btnAgregarFoliosSeleccionados" class="btn bg-primary text-white hover:bg-blue-700 rounded-lg font-medium"><i class="fa-solid fa-check me-1"></i> Agregar Seleccionados</button>
          </div>
      </div>
  </div>

  @endsection

  @vite(['resources/js/functions/legajo.js'])
  @section('script')
  @endsection