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


  {{-- ======================== MODAL EDITAR ======================== --}}
  <div id="modal-editar-usuario"
      class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-x-hidden overflow-y-auto hidden pointer-events-none">
      <div class="-translate-y-5 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 sm:max-w-xl sm:w-full my-8 sm:mx-auto flex flex-col bg-white shadow-sm rounded">
          <div class="flex flex-col border border-default-200 shadow-sm rounded-lg pointer-events-auto">
              <div class="flex justify-between items-center py-3 px-4 border-b border-default-200">
                  <h3 class="text-lg font-medium text-default-900">Editar Usuario</h3>
                  <button type="button" class="text-default-600 cursor-pointer" data-hs-overlay="#modal-editar-usuario">
                      <i class="i-tabler-x text-lg"></i>
                  </button>
              </div>
              <div class="p-4 overflow-y-auto">
                  <input type="hidden" id="e-codigo">
                  <div class="grid grid-cols-2 gap-3">
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
                              Nueva Contraseña
                              <span class="text-default-400 font-normal">(dejar vacío para no cambiar)</span>
                          </label>
                          <input type="password" id="e-clave" autocomplete="new-password" class="mt-1 w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-primary">
                      </div>
                      <div class="col-span-2 flex items-center gap-2 mt-1">
                          <input type="checkbox" id="e-limitarSucursal" class="form-checkbox text-primary w-4 h-4">
                          <label for="e-limitarSucursal" class="text-sm font-medium text-default-700 cursor-pointer">Limitar a sus sucursales asignadas</label>
                      </div>
                  </div>
                  <p id="e-error" class="text-red-500 text-sm mt-3 hidden"></p>
              </div>
              <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-default-200">
                  <button type="button" class="btn bg-secondary text-white" data-hs-overlay="#modal-editar-usuario">
                      <i class="i-tabler-x me-1"></i>Cerrar
                  </button>
                  <button type="button" id="btn-actualizar" class="btn bg-primary text-white">
                      <i class="i-tabler-device-floppy me-1"></i>Actualizar
                  </button>
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
                          <th class="px-3 py-2 text-left font-semibold text-default-700">Nombre Completo</th>
                          <th class="px-3 py-2 text-left font-semibold text-default-700">Usuario</th>
                          <th class="px-3 py-2 text-left font-semibold text-default-700">Rol</th>
                          <th class="px-3 py-2 text-center font-semibold text-default-700">Limit. Sucursal</th>
                          <th class="px-3 py-2 text-left font-semibold text-default-700">Limit. Tipo Personal</th>
                          <th class="px-3 py-2 text-center font-semibold text-default-700">Estado</th>
                          <th class="px-3 py-2 text-center font-semibold text-default-700">Acciones</th>
                      </tr>
                  </thead>
                  <tbody id="tbody-usuarios">
                      <tr>
                          <td colspan="7" class="text-center py-8 text-default-400">
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

          if (!list.length) {
              tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-default-400">Sin registros.</td></tr>';
              return;
          }

          tbody.innerHTML = list.map(u => {
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
                  <td class="px-3 py-2">${nombre}</td>
                  <td class="px-3 py-2 font-mono text text-default-600">${u.usuario}</td>
                  <td class="px-3 py-2">${rolBadge}</td>
                  <td class="px-3 py-2 text-center">${sucBadge}</td>
                  <td class="px-3 py-2">${tipoPer}</td>
                  <td class="px-3 py-2 text-center">${estBadge}</td>
                   <td class="px-3 py-2 whitespace-nowrap">
                        <button onclick="abrirEditar('${dataStr}')"
                            class="btn btn-sm bg-info/10 text-info hover:bg-info hover:text-white px-2 py-1 text-xs rounded me-1" title="Editar">
                            <i class="i-tabler-pencil"></i>
                        </button>
                        ${btnSucursal}
                        ${btnToggle}
                    </td>
              </tr>`;
          }).join('');
      } catch (e) {
          tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-red-400">Error al cargar usuarios.</td></tr>';
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

   // ─── EDITAR ────────────────────────────────────────────────────────────────────
  function abrirEditar(dataStr) {
      const u = JSON.parse(decodeURIComponent(dataStr));
      document.getElementById('e-codigo').value              = u.codigo;
      document.getElementById('e-nombre1').value             = (u.nombre_1 ?? '').trim();
      document.getElementById('e-nombre2').value             = (u.nombre_2 ?? '').trim();
      document.getElementById('e-apellido1').value           = (u.apellido_1 ?? '').trim();
      document.getElementById('e-apellido2').value           = (u.apellido_2 ?? '').trim();
      document.getElementById('e-rol').value                 = u.tipo_rol ?? '';
      document.getElementById('e-tipoPer').value             = u.limitarTipoPer ?? '0';
      document.getElementById('e-limitarSucursal').checked   = parseInt(u.limitarSucursal) === 1;  // fix clave
      document.getElementById('e-clave').value               = '';
      ocultarError('e-error');
      document.getElementById('__toggle-editar').click();
  }

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