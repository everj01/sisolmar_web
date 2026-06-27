@extends('layouts.vertical', ['title' => 'Requisitos de Legajos'])

  @section('css')
  @endsection

  @section('content')
  @include("layouts.shared/page-title", ["subtitle" => "File Control", "title" => "Requisitos de Legajos"])

  <style>
      .disabled-table { pointer-events: none; opacity: 0.3; }
  </style>

  <div class="grid lg:grid-cols-8 gap-6 mt-8">

      {{-- ── COLUMNA IZQUIERDA ── --}}
      <div class="lg:col-span-3 flex flex-col gap-6">

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
              <div class="px-4 pb-4">
                  <div id="tblCargo" class="w-full mt-2"></div>
              </div>
          </div>

      </div>

      {{-- ── COLUMNA DERECHA: FOLIOS ── --}}
      <div class="card lg:col-span-5">

          <div class="card-header flex justify-between items-center">
              <div class="flex flex-col">
                  <h4 class="card-title">Folios del Legajo</h4>
                  <span id="infoSeleccion" class="text-sm text-gray-400 italic">
                      — Seleccione cliente y cargo
                  </span>
              </div>
              <input type="text" id="buscarFolio" placeholder="Buscar folio..."
                  class="w-36 px-3 py-1 border border-gray-300 rounded-full text-sm
                         focus:outline-none focus:border-blue-500 transition-colors">
          </div>

          <div class="px-5 py-4">

              <div id="dismiss-alert"
                   class="hs-removing:translate-x-5 hs-removing:opacity-0 transition duration-300
                          bg-teal-50 border border-teal-200 rounded-md p-3 mb-4 flex items-center gap-3">
                  <i class="i-tabler-circle-check text-teal-600 text-lg"></i>
                  <span class="text-sm text-teal-800 font-medium flex-1">
                      Las notificaciones están vigentes 24 horas.
                  </span>
                  <button data-hs-remove-element="#dismiss-alert" type="button"
                      class="h-7 w-7 rounded-full bg-default-200 flex justify-center items-center">
                      <i class="i-tabler-x text-sm"></i>
                  </button>
              </div>

              <div id="tblFolio" class="w-full"></div>
              <input type="hidden" id="hidLegajo">
              <input type="hidden" id="txtNombre">

          </div>
      </div>

  </div>

  @endsection

  @vite(['resources/js/functions/legajo_comercial.js'])
  @section('script')
  @endsection