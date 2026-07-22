@extends('layouts.vertical', ['title' => 'Gestión de Cargos'])
@section('css')
@endsection
@section('content')
@include("layouts.shared/page-title", ["subtitle" => "File Control", "title" => "Gestión de Cargos"])

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mt-8">
      <!-- Listado de cargos -->
      <div class="card overflow-hidden lg:col-span-12">
        <div class="card-header flex flex-wrap justify-between items-center gap-4 border-b border-gray-100 pb-4">
            <h4 class="text-lg font-bold text-primary uppercase">Listado de Cargos</h4>
            
            <div class="flex items-center gap-4">
                {{-- Tarjetas de Indicadores --}}
                <div class="flex gap-2 border-r border-gray-200 pr-4">
                    <div class="bg-blue-50 px-3 py-2 rounded-lg border border-blue-200 text-center min-w-[90px]">
                        <span class="block text-[9px] text-blue-600 font-bold uppercase">Total</span>
                        <span id="countTotal" class="text-lg font-bold text-blue-700 leading-none">0</span>
                    </div>
                    <div class="bg-green-50 px-3 py-2 rounded-lg border border-green-200 text-center min-w-[90px]">
                        <span class="block text-[9px] text-green-600 font-bold uppercase">Activos</span>
                        <span id="countActivos" class="text-lg font-bold text-green-700 leading-none">0</span>
                    </div>
                    <div class="bg-red-50 px-3 py-2 rounded-lg border border-red-200 text-center min-w-[90px]">
                        <span class="block text-[9px] text-red-600 font-bold uppercase">Inactivos</span>
                        <span id="countInactivos" class="text-lg font-bold text-red-700 leading-none">0</span>
                    </div>
                </div>

                <button type="button" id="btnNuevoCargo" class="btn bg-primary text-white rounded-full text-sm px-4 py-2">
                    <i class="fa-solid fa-plus me-1"></i> Nuevo Cargo
                </button>
            </div>
        </div>
        
        <div class="px-5 py-4 space-y-4">
            
            {{-- Caja unificada de Filtros (Estilo Corporativo) --}}
            <div class="flex flex-wrap items-center justify-between gap-4 bg-slate-50 p-4 rounded-lg border border-slate-200">
                <div class="flex flex-wrap items-center gap-4">
                    
                    {{-- Búsqueda --}}
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            id="buscarCargo"
                            placeholder="Buscar cargo..."
                            autocomplete="off"
                            class="w-48 px-4 py-1.5 border border-gray-300 rounded-full focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-sm uppercase" 
                            style="min-width: 200px;"
                        />
                    </div>

                    {{-- Tipo de Personal --}}
                    <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                        <label for="filtroTipo" class="text-sm font-medium text-gray-700 whitespace-nowrap">Tipo de personal:</label>
                        <select id="filtroTipo" class="form-select text-sm w-44 pl-3 pr-8 py-1.5 border border-gray-300 rounded-lg focus:ring-primary">
                            <option value="TODOS" selected>Todos</option>
                            <option value="OPERATIVO">Operativos</option>
                            <option value="ADMINISTRATIVO">Administrativos</option>
                            <option value="ESPECIAL">Especiales</option>
                        </select>
                    </div>

                    {{-- Área --}}
                    <div class="flex items-center gap-2 border-l-2 border-gray-200 pl-4">
                        <label for="filtroArea" class="text-sm font-medium text-gray-700 whitespace-nowrap">Área:</label>
                        <select id="filtroArea" class="form-select text-sm w-44 pl-3 pr-8 py-1.5 border border-gray-300 rounded-lg focus:ring-primary">
                            <option value="">Todas</option>
                        </select>
                    </div>
                </div>

                {{-- Filtro de Estado --}}
                <div class="flex items-center gap-2 mt-2 lg:mt-0">
                    <label for="filtroEstado" class="text-sm font-medium text-gray-700 whitespace-nowrap">Estado:</label>
                    <select id="filtroEstado" class="form-select text-sm w-32 pl-3 pr-8 py-1.5 border border-gray-300 rounded-lg focus:ring-primary">
                        <option value="TODOS" selected>Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
            </div>

            <div id="tblCargos" class="w-full mt-8"></div>
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
    
    {{-- ── MODAL: Formulario de Gestión ────────────────────────── --}}
    <button type="button" class="hidden" id="btn-modal-gestion" data-hs-overlay="#modal-gestion-cargo"></button>

    <div id="modal-gestion-cargo"
         class="hs-overlay hidden fixed inset-0 z-[80] overflow-y-auto transition-all duration-500 pointer-events-none">
        <div class="hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100
                    translate-y-10 opacity-0 ease-in-out transition-all duration-500
                    sm:max-w-xl w-full my-8 sm:mx-auto flex flex-col bg-white shadow-sm rounded-lg pointer-events-auto
                    border border-gray-200">

            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 bg-slate-50 rounded-t-lg">
                <div class="flex items-center gap-3">
                    <h3 class="text-base font-bold text-gray-900">Gestión de Cargos</h3>
                    <span id="txtMensajeNuevo"
                          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                        Nuevo registro
                    </span>
                </div>
                <button type="button" id="btn-modal-gestion-close"
                        class="text-gray-500 hover:text-gray-700 transition-colors"
                        data-hs-overlay="#modal-gestion-cargo">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <div class="px-6 py-5">
                <form id="formSaveCargo">
                    <input type="hidden" name="codigoEditar" id="codigoEditar" value="0">
                    
                    <div x-data="{ nameCargo: '', description: '', abbreviation: '' }" class="space-y-4">
                        
                        {{-- Tipo de Cargo --}}
                        <div class="flex items-center justify-center gap-6 mt-2 mb-4">
                            <div class="form-check flex items-center">
                                <input type="radio" class="form-radio text-primary w-4 h-4 cursor-pointer" 
                                name="rdTipoCargo" id="opOperativo" checked="" value="1">
                                <label class="ms-2 font-medium text-sm text-gray-700 cursor-pointer" for="opOperativo">Operativo</label>
                            </div>
                            <div class="form-check flex items-center">
                                <input type="radio" class="form-radio text-primary w-4 h-4 cursor-pointer" 
                                name="rdTipoCargo" id="opAdmins" value="2">
                                <label class="ms-2 font-medium text-sm text-gray-700 cursor-pointer" for="opAdmins">Administrativo</label>
                            </div>
                            <div class="form-check flex items-center">
                                <input type="radio" class="form-radio text-primary w-4 h-4 cursor-pointer" 
                                name="rdTipoCargo" id="opEspecial" value="3">
                                <label class="ms-2 font-medium text-sm text-gray-700 cursor-pointer" for="opEspecial">Especial</label>
                            </div>
                        </div>

                        {{-- Campos principales --}}
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label for="slcArea" class="block text-sm font-bold text-gray-700 mb-1">Área</label>
                                <select class="form-select w-full px-3 py-2 border-gray-300 rounded-md focus:border-primary focus:ring-primary" id="slcArea" required>
                                    <option value="" disabled selected>-Seleccionar-</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="nombre" class="block text-sm font-bold text-gray-700 mb-1">Nombre</label>
                                <input type="text" id="nombre" class="form-input w-full px-3 py-2 border-gray-300 rounded-md focus:border-primary focus:ring-primary" x-model="nameCargo" placeholder="Nombre del cargo" autocomplete="off"
                                    @input="const pos = $event.target.selectionStart;
                                            nameCargo = nameCargo.toUpperCase();
                                            $nextTick(() => { $event.target.setSelectionRange(pos, pos); });" required>
                                <div id="avisoNombreRepetido" class="hidden mt-1 flex items-center gap-2 px-3 py-2 rounded-md bg-yellow-50 border border-yellow-300 text-yellow-800 text-xs">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <span>Ya existe un cargo con este nombre.</span>
                                </div>
                            </div>
                            
                            <div>
                                <label for="txtDescripcion" class="block text-sm font-bold text-gray-700 mb-1">Descripción (Función del cargo)</label>
                                <input type="text" id="txtDescripcion" class="form-input w-full px-3 py-2 border-gray-300 rounded-md focus:border-primary focus:ring-primary" x-model="description" @input="description = description.toUpperCase()" placeholder="Descripción del cargo" required>
                            </div>
                            
                            <div> 
                                <label for="txtAbreviatura" class="block text-sm font-bold text-gray-700 mb-1">Abreviatura</label>
                                <input type="text" id="txtAbreviatura" class="form-input w-full px-3 py-2 border-gray-300 rounded-md focus:border-primary focus:ring-primary" x-model="abbreviation"
                                    @input="const pos = $event.target.selectionStart; abbreviation = abbreviation.toUpperCase(); $nextTick(() => { $event.target.setSelectionRange(pos, pos); });" placeholder="Abreviatura del cargo" required>
                            </div>
                        </div>

                        {{-- Servicio y Subservicio --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                            <div>
                                <label for="slcPosicion" class="block text-sm font-bold text-gray-700 mb-1">Servicio</label>
                                <select class="form-select w-full px-3 py-2 border-gray-300 rounded-md focus:border-primary focus:ring-primary" id="slcPosicion" >
                                    <option value="" disabled selected>-Seleccionar-</option>
                                </select>
                            </div>
                            <div id="divSubservicios" class="hidden">
                                <label for="slcGrupo" class="block text-sm font-bold text-gray-700 mb-1">Subservicio</label>
                                <select class="form-select w-full px-3 py-2 border-gray-300 rounded-md focus:border-primary focus:ring-primary" id="slcGrupo">
                                    <option value="" disabled selected>-Seleccionar-</option>
                                </select>
                            </div>
                        </div>

                    </div>

                    {{-- Botones --}}
                    <div class="flex justify-end gap-3 mt-8 border-t border-gray-200 pt-4">
                        <button type="button" id="cancelButton" class="btn bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg px-4 py-2" data-hs-overlay="#modal-gestion-cargo">
                            Cancelar
                        </button>
                        <button type="submit" id="btnRegistrarCargo" class="btn bg-success text-white hover:bg-green-600 rounded-lg px-4 py-2 flex items-center gap-2">
                            Guardar <i class="fa-solid fa-floppy-disk"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</>

@endsection

@vite(['resources/js/functions/cargo.js'])
@section('script')

@endsection