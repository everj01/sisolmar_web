@extends('layouts.vertical', ['title' => 'Gestión de Usuarios'])
  @section('css')
  @endsection
  @section('content')
  @include("layouts.shared/page-title", ["subtitle" => "Maestros", "title" => "Gestión de Usuarios"])

  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- Triggers ocultos para abrir/cerrar modales programáticamente --}}
  <button id="__toggle-crear" data-hs-overlay="#modal-crear-usuario" class="hidden"></button>
  <button id="__toggle-editar" data-hs-overlay="#modal-editar-usuario" class="hidden"></button>
  <button id="__toggle-sucursales" data-hs-overlay="#modal-sucursales-usuario" class="hidden"></button>

  {{-- ======================== MODAL CREAR ======================== --}}
  <div id="modal-crear-usuario"
      class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-x-hidden overflow-y-auto hidden pointer-events-none">
      <div class="-translate-y-5 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 sm:max-w-xl sm:w-full my-8 sm:mx-auto flex flex-col bg-white
  shadow-sm rounded">
          <div class="flex flex-col border border-default-200 shadow-sm rounded-lg pointer-events-auto">
              <div class="flex justify-between items-center py-3 px-4 border-b border-default-200">
                  <h3 class="text-lg font-medium text-default-900">Nuevo Usuario</h3>
                  <button type="button" class="text-default-600 cursor-pointer" data-hs-overlay="#modal-crear-usuario">
                      <i class="i-tabler-x text-lg"></i>
                  </button>
              </div>
              <div class="p-4 overflow-y-auto">
                  <div class="grid grid-cols-2 gap-3">
                      <div>
                          <label class="text-sm font-medium text-default-700">Primer Nombre <span class="text-red-500">*</span></label>
                          <input type="text" id="c-nombre1" class="mt-1 w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-primary">
                      </div>
                      <div>
                          <label class="text-sm font-medium text-default-700">Segundo Nombre</label>
                          <input type="text" id="c-nombre2" class="mt-1 w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-primary">
                      </div>
                      <div>
                          <label class="text-sm font-medium text-default-700">Primer Apellido <span class="text-red-500">*</span></label>
                          <input type="text" id="c-apellido1" class="mt-1 w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-primary">
                      </div>
                      <div>
                          <label class="text-sm font-medium text-default-700">Segundo Apellido</label>
                          <input type="text" id="c-apellido2" class="mt-1 w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-primary">
                      </div>
                      <div>
                          <label class="text-sm font-medium text-default-700">Usuario <span class="text-red-500">*</span></label>
                          <input type="text" id="c-usuario" autocomplete="off" class="mt-1 w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-primary">
                      </div>
                      <div>
                          <label class="text-sm font-medium text-default-700">Contraseña <span class="text-red-500">*</span></label>
                          <input type="password" id="c-clave" autocomplete="new-password" class="mt-1 w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-primary">
                      </div>
                      <div>
                          <label class="text-sm font-medium text-default-700">Rol <span class="text-red-500">*</span></label>
                          <select id="c-rol" class="mt-1 w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-primary">
                              <option value="">— Seleccione —</option>
                              @foreach($roles as $rol)
                                  <option value="{{ $rol->codigo }}">{{ $rol->nombre }}</option>
                              @endforeach
                          </select>
                      </div>
                      <div>
                          <label class="text-sm font-medium text-default-700">Tipo de Personal</label>
                          <select id="c-tipoPer" class="mt-1 w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-primary">
                              <option value="0">Todos</option>
                              <option value="1">Administrativos</option>
                              <option value="2">Operativos</option>
                              <option value="3">Especiales</option>
                          </select>
                      </div>
                      <div class="col-span-2 flex items-center gap-2 mt-1">
                          <input type="checkbox" id="c-limitarSucursal" class="form-checkbox text-primary w-4 h-4">
                          <label for="c-limitarSucursal" class="text-sm font-medium text-default-700 cursor-pointer">Limitar a sus sucursales asignadas</label>
                      </div>
                  </div>
                  <p id="c-error" class="text-red-500 text-sm mt-3 hidden"></p>
              </div>
              <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-default-200">
                  <button type="button" class="btn bg-secondary text-white" data-hs-overlay="#modal-crear-usuario">
                      <i class="i-tabler-x me-1"></i>Cerrar
                  </button>
                  <button type="button" id="btn-guardar" class="btn bg-primary text-white">
                      <i class="i-tabler-device-floppy me-1"></i>Guardar
                  </button>
              </div>
          </div>
      </div>
  </div>

   {{-- ======================== MODAL SUCURSALES ======================== --}}
  <div id="modal-sucursales-usuario"
      class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-x-hidden overflow-y-auto hidden pointer-events-none">
      <div class="-translate-y-5 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 sm:max-w-md sm:w-full my-8 sm:mx-auto flex flex-col bg-white
  shadow-sm rounded">
          <div class="flex flex-col border border-default-200 shadow-sm rounded-lg pointer-events-auto">
              <div class="flex justify-between items-center py-3 px-4 border-b border-default-200">
                  <h3 id="titulo-modal-sucursales" class="text-base font-medium text-default-900">
                      Sucursales del usuario
                  </h3>
                  <button type="button" class="text-default-600 cursor-pointer" data-hs-overlay="#modal-sucursales-usuario">
                      <i class="i-tabler-x text-lg"></i>
                  </button>
              </div>
              <div class="p-4 overflow-y-auto max-h-96">
                  <div id="body-sucursales">
                      <div class="text-center py-4 text-default-400">Cargando...</div>
                  </div>
              </div>
              <div class="flex justify-between items-center py-3 px-4 border-t border-default-200">
                  <button type="button" id="btn-marcar-todas" class="text-xs text-primary hover:underline">
                      Marcar todas
                  </button>
                  <div class="flex gap-2">
                      <button type="button" class="btn bg-secondary text-white text-sm" data-hs-overlay="#modal-sucursales-usuario">
                          <i class="i-tabler-x me-1"></i>Cerrar
                      </button>
                      <button type="button" id="btn-guardar-sucursales" class="btn bg-primary text-white text-sm">
                          <i class="i-tabler-device-floppy me-1"></i>Guardar
                      </button>
                  </div>
              </div>
          </div>
      </div>
  </div>

{{-- ======================== MODAL EDITAR (CON PESTAÑAS) ======================== --}}
  <div id="modal-editar-usuario" class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-x-hidden overflow-y-auto hidden pointer-events-none">
      <div class="-translate-y-5 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 sm:max-w-2xl sm:w-full my-8 sm:mx-auto flex flex-col bg-white shadow-sm rounded">
          <div class="flex flex-col border border-default-200 shadow-sm rounded-lg pointer-events-auto">
              
              <div class="flex justify-between items-center py-3 px-4 border-b border-default-200">
                  <h3 class="text-lg font-medium text-default-900">Editar Usuario</h3>
                  <button type="button" class="text-default-600 cursor-pointer" data-hs-overlay="#modal-editar-usuario">
                      <i class="i-tabler-x text-lg"></i>
                  </button>
              </div>

              <div class="flex border-b border-default-200 px-4 pt-2 gap-4">
                  <button type="button" onclick="switchTab('datos')" id="btn-tab-datos" class="py-2 border-b-2 border-primary text-primary font-medium text-sm transition-colors">
                      Datos Generales
                  </button>
                  <button type="button" onclick="switchTab('menus')" id="btn-tab-menus" class="py-2 border-b-2 border-transparent text-default-500 hover:text-default-700 font-medium text-sm transition-colors">
                      Menús y Permisos
                  </button>
              </div>

              <div class="overflow-y-auto">
                  
                  <div id="pane-datos" class="block">
                      <div class="p-4 grid grid-cols-2 gap-3">
                          <input type="hidden" id="e-codigo">
                          <div>
                              <label class="text-sm font-medium text-default-700">Primer Nombre <span class="text-red-500">*</span></label>
                              <input type="text" id="e-nombre1" class="mt-1 w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-primary">
                          </div>
                          <div>
                              <label class="text-sm font-medium text-default-700">Segundo Nombre</label>
                              <input type="text" id="e-nombre2" class="mt-1 w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-primary">
                          </div>
                          <div>
                              <label class="text-sm font-medium text-default-700">Primer Apellido <span class="text-red-500">*</span></label>
                              <input type="text" id="e-apellido1" class="mt-1 w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-primary">
                          </div>
                          <div>
                              <label class="text-sm font-medium text-default-700">Segundo Apellido</label>
                              <input type="text" id="e-apellido2" class="mt-1 w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-primary">
                          </div>
                          <div>
                              <label class="text-sm font-medium text-default-700">Rol <span class="text-red-500">*</span></label>
                              <select id="e-rol" class="mt-1 w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-primary">
                                  @foreach($roles as $rol)
                                      <option value="{{ $rol->codigo }}">{{ $rol->nombre }}</option>
                                  @endforeach
                              </select>
                          </div>
                          <div>
                              <label class="text-sm font-medium text-default-700">Tipo de Personal</label>
                              <select id="e-tipoPer" class="mt-1 w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-primary">
                                  <option value="0">Todos</option>
                                  <option value="1">Administrativos</option>
                                  <option value="2">Operativos</option>
                                  <option value="3">Especiales</option>
                              </select>
                          </div>
                          <div class="col-span-2">
                              <label class="text-sm font-medium text-default-700">
                                  Nueva Contraseña <span class="text-default-400 font-normal">(dejar vacío para no cambiar)</span>
                              </label>
                              <input type="password" id="e-clave" autocomplete="new-password" class="mt-1 w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-primary">
                          </div>
                          <div class="col-span-2 flex items-center gap-2 mt-1">
                              <input type="checkbox" id="e-limitarSucursal" class="form-checkbox text-primary w-4 h-4">
                              <label for="e-limitarSucursal" class="text-sm font-medium text-default-700 cursor-pointer">Limitar a sus sucursales asignadas</label>
                          </div>
                      </div>
                      <p id="e-error" class="text-red-500 text-sm mt-3 px-4 hidden"></p>
                      
                      <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-default-200 mt-2">
                          <button type="button" class="btn bg-secondary text-white" data-hs-overlay="#modal-editar-usuario">
                              <i class="i-tabler-x me-1"></i>Cerrar
                          </button>
                          <button type="button" id="btn-actualizar" class="btn bg-primary text-white">
                              <i class="i-tabler-device-floppy me-1"></i>Actualizar Datos
                          </button>
                      </div>
                  </div>

                  <div id="pane-menus" class="hidden">
                      <div class="px-4 py-3 border-b border-default-200 bg-default-50 flex items-center gap-3">
                          <label class="text-sm font-medium text-default-700 whitespace-nowrap">Copiar de:</label>
                          <select id="select-copiar-permisos" disabled class="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-primary disabled:bg-default-200 disabled:cursor-not-allowed cursor-pointer transition-colors">
                              <option value="">-- Seleccione un usuario --</option>
                          </select>
                          <button type="button" id="btn-desbloquear-copia" class="btn btn-sm bg-default-200 text-default-700 hover:bg-default-300 px-2 py-1.5 rounded transition-colors" title="Desbloquear para copiar">
                              <i class="i-tabler-lock" id="icono-lock"></i>
                          </button>
                      </div>

                      <div class="p-4 max-h-[50vh] overflow-y-auto">
                          <div id="body-menus"></div>
                      </div>
                      
                      <div class="flex justify-between items-center py-3 px-4 border-t border-default-200">
                          <div class="flex gap-3 items-center">
                              <button type="button" id="btn-marcar-todos-menus" class="text-xs text-primary hover:underline font-medium">Marcar todos</button>
                              <span class="text-default-300">|</span>
                              <button type="button" id="btn-restablecer-menus" class="text-xs text-warning-600 hover:text-warning-700 hover:underline font-medium flex items-center" title="Volver a los permisos guardados del usuario">
                                  <i class="i-tabler-refresh me-1"></i> Restablecer
                              </button>
                          </div>
                          <div class="flex gap-2">
                              <button type="button" class="btn bg-secondary text-white text-sm" data-hs-overlay="#modal-editar-usuario">
                                  <i class="i-tabler-x me-1"></i>Cerrar
                              </button>
                              <button type="button" id="btn-guardar-menus" class="btn bg-primary text-white text-sm">
                                  <i class="i-tabler-device-floppy me-1"></i>Guardar Menús
                              </button>
                          </div>
                      </div>
                  </div>

              </div>
          </div>
      </div>
  </div>
  {{-- ======================== TABLA PRINCIPAL ======================== --}}
  <div class="grid gap-6 mt-8">
      <div class="card">
          <div class="card-header flex justify-between items-center">
              <h3 class="card-title">Listado de Usuarios</h3>
              <button type="button" class="btn bg-primary text-white text-sm" data-hs-overlay="#modal-crear-usuario">
                  <i class="i-tabler-plus me-1"></i> Nuevo Usuario
              </button>
          </div>
          <div class="p-4 overflow-x-auto">
              <table class="min-w-full text-sm">
                  <thead>
                      <tr class="border-b border-default-200">
                          <th class="px-3 py-2 text-left font-semibold text-default-700">N°</th>
                          
                          <th class="px-3 py-2 text-left font-semibold text-default-700">Nombre Completo</th>
                          <th class="px-3 py-2 text-left font-semibold text-default-700">Usuario</th>
                          <th class="px-3 py-2 text-left font-semibold text-default-700">Rol</th>
                          <th class="px-3 py-2 text-center font-semibold text-default-700">Limit. Sucursal</th>
                          <th class="px-3 py-2 text-left font-semibold text-default-700">Limit. Tipo Personal</th>
                          <th class="px-3 py-2 text-center font-semibold text-default-700">Estado</th>
                          <th class="px-3 py-2 text-right font-semibold text-default-700">Acciones</th>
                      </tr>
                  </thead>
                  <tbody id="tbody-usuarios">
                      <tr>
                          <td colspan="8" class="text-center py-8 text-default-400">
                              <i class="i-tabler-loader-2 animate-spin text-xl me-1"></i> Cargando...
                          </td>
                      </tr>
                  </tbody>
              </table>
          </div>
      </div>
  </div>

  @endsection
  @section('script')
  <script>
  const CSRF = document.querySelector('meta[name="csrf-token"]').content;
  const tipoPerLabels = { 0: 'Todos', 1: 'Administrativos', 2: 'Operativos', 3: 'Especiales' };
let _codUsuarioSucursal = null;
   // ─── CARGAR TABLA ──────────────────────────────────────────────────────────────
  async function cargarUsuarios() {
      const tbody = document.getElementById('tbody-usuarios');
      try {
          const res  = await fetch(`${VITE_URL_APP}/api/get-usuarios`);
          const list = await res.json();

          // =========================================================
          // 👇 AQUÍ LLENAMOS EL SELECT DE COPIAR PERMISOS
          const selectCopiar = document.getElementById('select-copiar-permisos');
          selectCopiar.innerHTML = '<option value="">-- Seleccione un usuario --</option>' + 
              list.map(u => `<option value="${u.codigo}">${(u.apellido_1 || '')} ${(u.apellido_2 || '')} ${(u.nombre_1 || '')}</option>`).join('');
          // =========================================================

          if (!list.length) {
              tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-default-400">Sin registros.</td></tr>';
              return;
          }

          // Agregamos (u, index) para poder sacar el número de fila
          tbody.innerHTML = list.map((u, index) => {
              const habilitado      = parseInt(u.habilitado) === 1;
              const limitaSucursal  = parseInt(u.limitarSucursal) === 1;

               const nombre = [u.apellido_1, u.apellido_2, u.nombre_1, u.nombre_2]
      .filter(v => v && v.trim())
      .map(v => v.trim())
      .join(' ');

       // Botón sucursales — solo si limitarSucursal está activo
  const btnSucursal = limitaSucursal
      ? `<button onclick="abrirSucursales(${u.codigo}, '${nombre}')"
             class="btn btn-sm px-2 py-1 text-xs rounded bg-purple-100 text-purple-700 hover:bg-purple-600 hover:text-white me-1"
             title="Permisos de Sucursales">
             <i class="i-tabler-building-store"></i>
         </button>`
      : '';

              const rolBadge = u.nombre_rol ?? '<span class="text-default-400">—</span>';

              const sucBadge = limitaSucursal
                  ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-warning/15 text-warning-700">Sí</span>'
                  : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-default-100 text-default-500">No</span>';

              const estBadge = habilitado
                  ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success/15 text-success">Activo</span>'
                  : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-danger/15 text-danger">Inactivo</span>';

              const tipoPer  = tipoPerLabels[u.limitarTipoPer] ?? '—';
              const dataStr  = encodeURIComponent(JSON.stringify(u));
              const btnToggle = habilitado
                  ? `<button onclick="toggleEstado(${u.codigo}, 1)" class="btn btn-sm px-2 py-1 text-xs rounded bg-danger/10 text-danger hover:bg-danger hover:text-white" title="Deshabilitar"><i class="i-tabler-user-off"></i></button>`
                  : `<button onclick="toggleEstado(${u.codigo}, 0)" class="btn btn-sm px-2 py-1 text-xs rounded bg-success/10 text-success hover:bg-success hover:text-white" title="Habilitar"><i class="i-tabler-user-check"></i></button>`;

              return `<tr class="border-b border-default-100 hover:bg-default-50 transition-colors">
                  <td class="px-3 py-2 text-default-500 font-medium">${index + 1}</td>
                  <td class="px-3 py-2">${nombre}</td>
                  <td class="px-3 py-2 font-mono text text-default-600">${u.usuario}</td>
                  <td class="px-3 py-2">${rolBadge}</td>
                  <td class="px-3 py-2 text-center">${sucBadge}</td>
                  <td class="px-3 py-2">${tipoPer}</td>
                  <td class="px-3 py-2 text-center">${estBadge}</td>
                  <td class="px-3 py-2 whitespace-nowrap flex justify-end items-center gap-1">
                      <button onclick="abrirEditar('${dataStr}')"
                          class="btn btn-sm bg-info/10 text-info hover:bg-info hover:text-white px-2 py-1 text-xs rounded" title="Editar">
                          <i class="i-tabler-pencil"></i>
                      </button>
                      ${btnSucursal}
                      ${btnToggle}
                  </td>
              </tr>`;
          }).join('');
      } catch (e) {
          tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-red-400">Error al cargar usuarios.</td></tr>';
      }
  }

  // ─── HELPERS ───────────────────────────────────────────────────────────────────
  function mostrarError(id, msg) {
      const el = document.getElementById(id);
      el.textContent = msg;
      el.classList.remove('hidden');
  }
  function ocultarError(id) { document.getElementById(id).classList.add('hidden'); }

  // ─── CREAR ─────────────────────────────────────────────────────────────────────
  document.getElementById('btn-guardar').addEventListener('click', async () => {
      ocultarError('c-error');

      const payload = {
          nombre_1:        document.getElementById('c-nombre1').value.trim(),
          nombre_2:        document.getElementById('c-nombre2').value.trim(),
          apellido_1:      document.getElementById('c-apellido1').value.trim(),
          apellido_2:      document.getElementById('c-apellido2').value.trim(),
          usuario:         document.getElementById('c-usuario').value.trim(),
          clave:           document.getElementById('c-clave').value,
          tipo_rol:        document.getElementById('c-rol').value,
          limitarTipoPer:  document.getElementById('c-tipoPer').value,
          limitarSucursal: document.getElementById('c-limitarSucursal').checked ? 1 : 0,
      };

      if (!payload.nombre_1 || !payload.apellido_1 || !payload.usuario || !payload.clave || !payload.tipo_rol) {
          return mostrarError('c-error', 'Complete todos los campos obligatorios (*).');
      }

      const res  = await fetch(`${VITE_URL_APP}/api/save-usuario`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
          body: JSON.stringify(payload),
      });
      const data = await res.json();

      if (!data.success) return mostrarError('c-error', data.message ?? 'Error al guardar.');

      document.getElementById('__toggle-crear').click();
      limpiarCrear();
      cargarUsuarios();
  });

  function limpiarCrear() {
      ['c-nombre1','c-nombre2','c-apellido1','c-apellido2','c-usuario','c-clave']
          .forEach(id => document.getElementById(id).value = '');
      document.getElementById('c-rol').value = '';
      document.getElementById('c-tipoPer').value = '0';
      document.getElementById('c-limitarSucursal').checked = false;
  }

// ─── UX: CONTROL DE PESTAÑAS (TABS) ──────────────────────────────────────────
  function switchTab(tab) {
      const paneDatos = document.getElementById('pane-datos');
      const paneMenus = document.getElementById('pane-menus');
      const btnDatos = document.getElementById('btn-tab-datos');
      const btnMenus = document.getElementById('btn-tab-menus');

      if (tab === 'datos') {
          paneDatos.classList.replace('hidden', 'block');
          paneMenus.classList.replace('block', 'hidden');
          btnDatos.className = 'py-2 border-b-2 border-primary text-primary font-medium text-sm transition-colors';
          btnMenus.className = 'py-2 border-b-2 border-transparent text-default-500 hover:text-default-700 font-medium text-sm transition-colors';
      } else {
          paneMenus.classList.replace('hidden', 'block');
          paneDatos.classList.replace('block', 'hidden');
          btnMenus.className = 'py-2 border-b-2 border-primary text-primary font-medium text-sm transition-colors';
          btnDatos.className = 'py-2 border-b-2 border-transparent text-default-500 hover:text-default-700 font-medium text-sm transition-colors';
      }
  }

  // ─── EDITAR USUARIO Y MENÚS ──────────────────────────────────────────────────
  let _codUsuarioEdicion = null;

  function abrirEditar(dataStr) {
      const u = JSON.parse(decodeURIComponent(dataStr));
      _codUsuarioEdicion = u.codigo;

      // 1. Llenar datos personales
      document.getElementById('e-codigo').value              = u.codigo;
      document.getElementById('e-nombre1').value             = (u.nombre_1 ?? '').trim();
      document.getElementById('e-nombre2').value             = (u.nombre_2 ?? '').trim();
      document.getElementById('e-apellido1').value           = (u.apellido_1 ?? '').trim();
      document.getElementById('e-apellido2').value           = (u.apellido_2 ?? '').trim();
      document.getElementById('e-rol').value                 = u.tipo_rol ?? '';
      document.getElementById('e-tipoPer').value             = u.limitarTipoPer ?? '0';
      document.getElementById('e-limitarSucursal').checked   = parseInt(u.limitarSucursal) === 1;
      document.getElementById('e-clave').value               = '';
      ocultarError('e-error');

      // 2. Resetear la UI de copiar permisos (Cerrar candado y limpiar)
      const selectCopiar = document.getElementById('select-copiar-permisos');
      const iconoLock = document.getElementById('icono-lock');
      selectCopiar.value = '';
      selectCopiar.disabled = true;
      iconoLock.classList.replace('i-tabler-lock-open', 'i-tabler-lock');

      // 3. Mostrar la pestaña de datos por defecto
      switchTab('datos');

      // 4. Cargar los menús en segundo plano
      cargarEstructuraMenus(u.codigo);

      // 5. Abrir modal
      document.getElementById('__toggle-editar').click();
  }

  // Actualizar Datos Básicos
  document.getElementById('btn-actualizar').addEventListener('click', async () => {
      ocultarError('e-error');
      const payload = {
          codigo:          document.getElementById('e-codigo').value,
          nombre_1:        document.getElementById('e-nombre1').value.trim(),
          nombre_2:        document.getElementById('e-nombre2').value.trim(),
          apellido_1:      document.getElementById('e-apellido1').value.trim(),
          apellido_2:      document.getElementById('e-apellido2').value.trim(),
          tipo_rol:        document.getElementById('e-rol').value,
          limitarTipoPer:  document.getElementById('e-tipoPer').value,
          limitarSucursal: document.getElementById('e-limitarSucursal').checked ? 1 : 0,
          clave:           document.getElementById('e-clave').value,
      };

      if (!payload.nombre_1 || !payload.apellido_1 || !payload.tipo_rol) {
          return mostrarError('e-error', 'Complete todos los campos obligatorios (*).');
      }

      const res  = await fetch(`${VITE_URL_APP}/api/update-usuario`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
          body: JSON.stringify(payload),
      });
      const data = await res.json();

      if (!data.success) return mostrarError('e-error', data.message ?? 'Error al actualizar.');
      document.getElementById('__toggle-editar').click();
      cargarUsuarios();
  });

  // ─── FUNCIONES DE MENÚS (SOPORTE A 3 NIVELES) ────────────────────────────────
  async function cargarEstructuraMenus(codUsuario) {
      const bodyMenus = document.getElementById('body-menus');
      bodyMenus.innerHTML = '<div class="text-center py-8 text-default-400"><i class="i-tabler-loader-2 animate-spin text-2xl mx-auto mb-2 block"></i> Cargando permisos...</div>';

      try {
          const res  = await fetch(`${VITE_URL_APP}/api/get-menus-usuario/${codUsuario}`);
          if (!res.ok) throw new Error('Error servidor');
          const list = await res.json();

          if (!list || list.length === 0) {
              bodyMenus.innerHTML = '<div class="text-center py-8 text-default-400">No hay menús configurados.</div>';
              return;
          }

          let html = '<div class="flex flex-col gap-3">';
          list.forEach(menu => {
              const padreMarcado = menu.asignado ? 'checked' : '';
              html += `<div class="border border-default-200 rounded p-3 bg-default-50/50">
                  <label class="flex items-center gap-2 cursor-pointer mb-2 font-semibold text-default-800">
                      <input type="checkbox" value="${menu.codigo}" class="chk-menu-padre form-checkbox text-primary w-4 h-4 rounded transition-colors" ${padreMarcado} onchange="toggleSubmenus(this)">
                      <i class="i-tabler-folder text-default-400"></i> ${menu.nombre}
                  </label>
                  <div class="pl-6 flex flex-col gap-2 border-l-2 border-default-200 ml-2 mt-2">`;
              
              if (menu.submenus && menu.submenus.length > 0) {
                  menu.submenus.forEach(sub => {
                      const tieneOpciones = sub.opciones && sub.opciones.length > 0;
                      let hijoMarcado = sub.asignado ? 'checked' : '';
                      
                      // EL FIX: Si el submenú tiene opciones, pero NINGUNA está asignada en la BD, 
                      // forzamos a que el submenú se muestre desmarcado.
                      if (tieneOpciones && !sub.opciones.some(opc => opc.asignado)) {
                          hijoMarcado = '';
                      }
                      
                      html += `
                      <div class="flex flex-col gap-1">
                          <div class="flex items-center justify-between hover:bg-default-100/50 pr-2 rounded transition-colors">
                              <label class="flex items-center gap-2 cursor-pointer py-1 text-sm text-default-600 hover:text-default-900 flex-1">
                                  <input type="checkbox" value="${sub.codigo}" class="chk-menu-hijo form-checkbox text-primary w-4 h-4 rounded transition-colors" ${hijoMarcado} onchange="verificarPadre(this)">
                                  ${sub.nombre}
                              </label>
                              ${tieneOpciones ? `
                              <button type="button" onclick="toggleAccordionOpciones(this, ${sub.codigo})" class="text-default-400 hover:text-default-700 p-0.5 transition-transform" title="Ver opciones">
                                  <i class="i-tabler-chevron-down text-base block transition-transform duration-200"></i>
                              </button>` : ''}
                          </div>`;

                      if (tieneOpciones) {
                          html += `<div id="container-opc-${sub.codigo}" class="hidden pl-6 py-1 flex flex-col gap-1.5 border-l border-dashed border-default-300 ml-2 my-1">`;
                          sub.opciones.forEach(opc => {
                              const opcMarcada = opc.asignado ? 'checked' : '';
                              
                              // LA MAGIA AQUÍ: 
                              // 1. Le quitamos el "ETAPA: " original que viene de la BD (con o sin espacios extra).
                              const nombreLimpio = opc.nombre.replace(/^ETAPA:\s*/i, '');
                              // 2. Lo armamos bonito con el número: "Etapa 1: Actualización..."
                              const textoMostrar = `Etapa ${opc.nro}: ${nombreLimpio}`;

                              html += `
                              <label class="flex items-center gap-2 cursor-pointer text-xs text-default-500 hover:text-default-800">
                                  <input type="checkbox" value="${opc.codigo}" class="chk-submenu-opc form-checkbox text-primary w-3.5 h-3.5 rounded transition-colors" ${opcMarcada} onchange="verificarHijo(this)">
                                  ${textoMostrar}
                              </label>`;
                          });
                          html += `</div>`;
                      }
                      html += `</div>`;
                  });
              } else {
                  html += `<span class="text-xs text-default-400 italic">Sin submenús</span>`;
              }
              html += `</div></div>`;
          });
          html += '</div>';
          bodyMenus.innerHTML = html;
      } catch (e) {
          bodyMenus.innerHTML = '<div class="text-center py-8 text-red-400">Error al cargar la estructura de menús.</div>';
      }
  }

  // Desplegar/Colapsar opciones
  function toggleAccordionOpciones(btn, idSubmenu) {
      const container = document.getElementById(`container-opc-${idSubmenu}`);
      const icono = btn.querySelector('i');
      if (container.classList.contains('hidden')) {
          container.classList.remove('hidden');
          icono.classList.add('rotate-180');
      } else {
          container.classList.add('hidden');
          icono.classList.remove('rotate-180');
      }
  }

  function toggleSubmenus(checkboxPadre) {
      const contenedor = checkboxPadre.closest('.border');
      const hijos = contenedor.querySelectorAll('.chk-menu-hijo, .chk-submenu-opc');
      hijos.forEach(h => h.checked = checkboxPadre.checked);
  }

  function verificarPadre(checkboxHijo) {
      // 1. Vinculación segura usando el ID de la opción
      const idSubmenu = checkboxHijo.value;
      const containerOpciones = document.getElementById(`container-opc-${idSubmenu}`);

      // 2. Si marco/desmarco "Actualizar DJ", marco/desmarco todas sus etapas automáticamente
      if (containerOpciones) {
          const subOpc = containerOpciones.querySelectorAll('.chk-submenu-opc');
          subOpc.forEach(opc => opc.checked = checkboxHijo.checked);
      }

      // 3. Evalúa al abuelo (Menú principal)
      const contenedorPrincipal = checkboxHijo.closest('.border');
      contenedorPrincipal.querySelector('.chk-menu-padre').checked = Array.from(contenedorPrincipal.querySelectorAll('.chk-menu-hijo')).some(h => h.checked);
  }

  function verificarHijo(checkboxOpc) {
      // 1. Buscamos el contenedor exacto y sacamos el ID del submenú
      const containerOpciones = checkboxOpc.closest('[id^="container-opc-"]');
      const idSubmenu = containerOpciones.id.replace('container-opc-', '');

      // 2. Encontramos a "Actualizar DJ" sin importar cuántos divs haya de por medio
      const contenedorPrincipal = checkboxOpc.closest('.border');
      const hijoCheckbox = contenedorPrincipal.querySelector(`.chk-menu-hijo[value="${idSubmenu}"]`);
      const todasOpc = Array.from(containerOpciones.querySelectorAll('.chk-submenu-opc'));

      // 3. LA MAGIA: Si hay al menos una etapa marcada, "Actualizar DJ" se marca.
      // Si desmarcas la última etapa (ninguna seleccionada), "Actualizar DJ" se apaga automáticamente.
      if (hijoCheckbox) {
          hijoCheckbox.checked = todasOpc.some(o => o.checked);
      }

      // 4. Evalúa al abuelo final para ver si apaga el menú principal completo
      contenedorPrincipal.querySelector('.chk-menu-padre').checked = Array.from(contenedorPrincipal.querySelectorAll('.chk-menu-hijo')).some(h => h.checked);
  }

  document.getElementById('btn-guardar-menus').addEventListener('click', async () => {
      const btn = document.getElementById('btn-guardar-menus');
      const textoOriginal = btn.innerHTML;
      btn.innerHTML = '<i class="i-tabler-loader-2 animate-spin me-1"></i>Guardando...';
      btn.disabled = true;

      const payload = {
          codUsuario: _codUsuarioEdicion,
          menus_padres: Array.from(document.querySelectorAll('.chk-menu-padre:checked')).map(c => c.value),
          submenus: Array.from(document.querySelectorAll('.chk-menu-hijo:checked')).map(c => c.value),
          opciones: Array.from(document.querySelectorAll('.chk-submenu-opc:checked')).map(c => c.value)
      };

      try {
          const res = await fetch(`${VITE_URL_APP}/api/save-menus-usuario`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
              body: JSON.stringify(payload)
          });
          const data = await res.json();
          if (data.success) alert('Permisos guardados correctamente.'); 
          else alert(data.message ?? 'Error al guardar.');
      } catch (e) {
          alert('Error de conexión.');
      } finally {
          btn.innerHTML = textoOriginal;
          btn.disabled = false;
      }
  });

  document.getElementById('btn-marcar-todos-menus').addEventListener('click', (e) => {
      const checks = document.querySelectorAll('.chk-menu-padre, .chk-menu-hijo, .chk00000-submenu-opc');
      if (!checks.length) return;
      
      const accionMarcar = e.target.textContent.trim() === 'Marcar todos';
      checks.forEach(c => c.checked = accionMarcar);
      e.target.textContent = accionMarcar ? 'Desmarcar todos' : 'Marcar todos';
  });

    document.getElementById('select-copiar-permisos').addEventListener('change', (e) => {
      const codUsuarioCopia = e.target.value;

      // Si el usuario vuelve a elegir "-- Seleccione un usuario --", no hacemos nada
      if (!codUsuarioCopia) return;

      if (codUsuarioCopia == _codUsuarioEdicion) {
          e.target.value = ''; // Reseteamos la selección
          return alert('No puedes copiar los permisos del mismo usuario que estás editando.');
      }

      // ¡Magia! Al solo seleccionar, cargamos los checkboxes del usuario elegido.
      // Dejamos el nombre seleccionado en la cajita para que el admin sepa de quién copió.
      cargarEstructuraMenus(codUsuarioCopia);
  });

  // ─── UX: BOTÓN PARA DESBLOQUEAR / BLOQUEAR COPIA ────────────────────────────
  document.getElementById('btn-desbloquear-copia').addEventListener('click', () => {
      const selectCopiar = document.getElementById('select-copiar-permisos');
      const iconoLock = document.getElementById('icono-lock');
      
      if (selectCopiar.disabled) {
          // Desbloquear
          selectCopiar.disabled = false;
          selectCopiar.focus();
          iconoLock.classList.replace('i-tabler-lock', 'i-tabler-lock-open');
      } else {
          // Bloquear
          selectCopiar.disabled = true;
          selectCopiar.value = ''; // Limpiamos por si había algo seleccionado
          iconoLock.classList.replace('i-tabler-lock-open', 'i-tabler-lock');
      }
  });

  // ─── UX: BOTÓN RESTABLECER PERMISOS ORIGINALES ─────────────────────────────
  document.getElementById('btn-restablecer-menus').addEventListener('click', () => {
      if (!_codUsuarioEdicion) return;
      
      // Magia pura: Solo volvemos a llamar al Backend con el ID del usuario original.
      // Esto sobreescribe cualquier cambio manual o copia que hayan hecho.
      cargarEstructuraMenus(_codUsuarioEdicion);
      
      // Por limpieza, volvemos a cerrar el candado
      const selectCopiar = document.getElementById('select-copiar-permisos');
      const iconoLock = document.getElementById('icono-lock');
      selectCopiar.value = '';
      selectCopiar.disabled = true;
      iconoLock.classList.replace('i-tabler-lock-open', 'i-tabler-lock');
  });

   // ─── TOGGLE ESTADO ─────────────────────────────────────────────────────────────
  async function toggleEstado(codigo, habilitadoActual) {
      const accion = habilitadoActual === 1 ? 'deshabilitar' : 'habilitar';
      if (!confirm(`¿Desea ${accion} este usuario?`)) return;

      const res  = await fetch(`${VITE_URL_APP}/api/toggle-usuario`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
          body: JSON.stringify({ codigo }),
      });
      const data = await res.json();
      if (data.success) cargarUsuarios();
      else alert(data.message ?? 'Error al cambiar estado.');
  }

  cargarUsuarios();




   async function abrirSucursales(codUsuario, nombreUsuario) {
      _codUsuarioSucursal = codUsuario;
      document.getElementById('titulo-modal-sucursales').textContent = `Sucursales — ${nombreUsuario}`;
      document.getElementById('body-sucursales').innerHTML =
          '<div class="text-center py-4 text-default-400"><i class="i-tabler-loader-2 animate-spin me-1"></i>Cargando...</div>';
      document.getElementById('__toggle-sucursales').click();

      try {
          const res  = await fetch(`${VITE_URL_APP}/api/get-sucursales-usuario/${codUsuario}`);
          const list = await res.json();

          if (!list.length) {
              document.getElementById('body-sucursales').innerHTML =
                  '<div class="text-center py-4 text-default-400">Sin sucursales disponibles.</div>';
              return;
          }

          document.getElementById('body-sucursales').innerHTML =
              `<div class="grid grid-cols-2 gap-1">` +
              list.map(s => `
                  <label class="flex items-center gap-2 px-2 py-1.5 rounded cursor-pointer hover:bg-default-50">
                      <input type="checkbox" value="${s.codigo}" ${s.asignada ? 'checked' : ''}
                          class="chk-sucursal form-checkbox text-primary w-4 h-4">
                      <span class="text-sm text-default-700">${s.abreviatura}</span>
                  </label>`
              ).join('') +
              `</div>`;
      } catch (e) {
          document.getElementById('body-sucursales').innerHTML =
              '<div class="text-center py-4 text-red-400">Error al cargar sucursales.</div>';
      }
  }

  document.getElementById('btn-guardar-sucursales').addEventListener('click', async () => {
      const codigos = [...document.querySelectorAll('.chk-sucursal:checked')].map(c => c.value);

      const res  = await fetch(`${VITE_URL_APP}/api/save-sucursales-usuario`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
          body: JSON.stringify({ codUsuario: _codUsuarioSucursal, codigos }),
      });
      const data = await res.json();

      if (data.success) {
          document.getElementById('__toggle-sucursales').click();
      } else {
          alert(data.message ?? 'Error al guardar permisos.');
      }
  });

  document.getElementById('btn-marcar-todas').addEventListener('click', () => {
      const checks = document.querySelectorAll('.chk-sucursal');
      const todasMarcadas = [...checks].every(c => c.checked);
      checks.forEach(c => c.checked = !todasMarcadas);
      document.getElementById('btn-marcar-todas').textContent = todasMarcadas ? 'Marcar todas' : 'Desmarcar todas';
  });
  </script>
  @endsection