@extends('layouts.vertical', ['title' => 'Gestión de Cargos'])

  @section('css')
  @endsection

  @section('content')

  @include("layouts.shared/page-title", ["subtitle" => "Recursos Humanos", "title" => "Búsqueda de Legajos"])

  <style>
      .disabled-table {
          pointer-events: none;
          opacity: 0.3;
      }
  </style>
  <script src="https://kit.fontawesome.com/76256ea07c.js" crossorigin="anonymous"></script>

  <div class="w-full">
      <div class="flex flex-row items-center justify-center gap-2">
          <button type="button" class="btn bg-primary text-white" id="btnTodos">Mostrar Todos</button>
      </div>
  </div>

  <div class="grid lg:grid-cols-5 gap-6 mt-8">

      {{-- ── COLUMNA IZQUIERDA ── --}}
      <div class="lg:col-span-2 flex flex-col gap-6">

          {{-- CLIENTE --}}
          <div class="card">
              <div class="card-header flex gap-1 justify-between items-center">
                  <h3 class="card-title">Cliente</h3>
                  <input type="text" id="buscarCliente" placeholder="Buscar cliente..."
                      class="w-40 px-3 py-1 border border-gray-300 rounded-full text-sm
                             focus:outline-none focus:border-blue-500 transition-all">
              </div>
              <div class="px-4 pb-4">
                  <div id="tblCliente" class="w-full mt-2 overflow-y-auto"></div>
              </div>
          </div>

          {{-- CARGO --}}
          <div class="card">
              <div class="card-header flex gap-1 justify-between items-center">
                  <h3 class="card-title">Cargo</h3>
                  <input type="text" id="buscarCargo" placeholder="Buscar cargo..."
                      class="w-40 px-3 py-1 border border-gray-300 rounded-full text-sm
                             focus:outline-none focus:border-blue-500 transition-all" />
              </div>
              <div class="px-4 pb-4">
                  <div id="tblCargo" class="w-full mt-2 overflow-y-auto overflow-x-hidden"></div>
              </div>
          </div>

      </div>

      {{-- ── COLUMNA DERECHA ── --}}
      <div class="lg:col-span-3 flex flex-col gap-6">

          <div class="card overflow-hidden">
              <div class="card-header flex gap-6">
                  <h4 class="card-title">Listado de Personal con LEGAJOS COMPLETOS</h4>
                  <span class="text-primary font-semibold text-lg" id="txtTextoilus"></span>
              </div>
              <div class="px-5 py-2 mt-3">
                  <input type="text" id="buscar" placeholder="Buscar..."
                      class="w-40 px-3 py-1 border border-gray-300 rounded-full text-sm
                             focus:outline-none focus:border-blue-500 transition-all" />
                  <div id="tblPersonas" class="w-full mt-5"></div>
              </div>
          </div>

          <div id="dataDocsLeg" class="card hidden">
              <div class="card-header">
                  <h4 class="card-title nombrePersDocs">Folios de</h4>
              </div>
              <div class="px-5 py-2 flex flex-col">
                  <div id="tblDocsLegajo" class="w-full flex-grow"></div>
              </div>
          </div>

          <div id="dataDocsLeg1" class="card hidden">
              <div class="card-header">
                  <h4 class="card-title nombrePersLeg">Legajos para</h4>
              </div>
              <div class="p-6">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-3">
                      <div>
                          <label for="inputState"
                              class="text-default-800 text-sm font-medium inline-block mb-2">Cliente</label>
                          <select id="clientes" class="form-select">
                              <option disabled selected>-Seleccionar-</option>
                              @foreach($clientes as $cliente)
                              <option value="{{ $cliente->codigo }}">{{ $cliente->abreviatura }}</option>
                              @endforeach
                          </select>
                      </div>
                      <div>
                          <label for="inputZip"
                              class="text-default-800 text-sm font-medium inline-block mb-2">Cargo</label>
                          <select id="cargos" class="form-select">
                              <option disabled selected>-Seleccionar-</option>
                              @foreach($cargos as $cargo)
                              <option value="{{ $cargo->codigo }}">{{ $cargo->descripcion }}</option>
                              @endforeach
                          </select>
                      </div>
                  </div>
                  <button type="button" class="btn bg-primary text-white btnTraerFolios"
                      id="btnTraerFolios">Traer folios</button>
              </div>
              <div class="px-5 py-2">
                  <input type="hidden" name="codPersonal" id="codPersonal">
                  <div id="tblDocsLegajo1" class="w-full hidden"></div>
              </div>
          </div>

      </div>

  </div>

  @endsection

  @vite(['resources/js/functions/search_legajos.js'])
  @section('script')
  @endsection