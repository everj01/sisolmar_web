import axios from 'axios';
import Swal from 'sweetalert2';
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';

document.addEventListener('DOMContentLoaded', function () {
  const container = document.getElementById('familyContainer');
  const addBtn = document.getElementById('addFamilyMember');
  const modal = document.getElementById('formModal'); // tu modal

  function makeFamilyRow() {
    return `
      <div class="family-row grid grid-cols-1 md:grid-cols-3 gap-4 p-4 border rounded-lg relative">
        <div>
          <label class="text-sm font-medium inline-block mb-2">Parentesco</label>
          <select name="parentesco[]" class="form-select w-full">
            <option value="">Seleccionar</option>
            <option value="padre">Padre</option>
            <option value="madre">Madre</option>
            <option value="esposo">Esposo</option>
            <option value="esposa">Esposa</option>
            <option value="hijo">Hijo</option>
            <option value="hija">Hija</option>
            <option value="hermano">Hermano</option>
            <option value="hermana">Hermana</option>
            <option value="abuelo">Abuelo</option>
            <option value="abuela">Abuela</option>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium inline-block mb-2">Apellidos y Nombres</label>
          <input type="text" name="apellidosNombres[]" class="form-input w-full" placeholder="Apellidos y nombres completos">
        </div>
        <div class="flex gap-2 items-end">
          <div class="flex-1">
            <label class="text-sm font-medium inline-block mb-2">Fecha Nacimiento</label>
            <input type="date" name="fechaNacimiento[]" class="form-input w-full">
          </div>
          <button type="button" class="remove-family self-end px-3 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200">
            Eliminar
          </button>
        </div>
      </div>
    `;
  }

  // Agregar fila
  if (addBtn) {
    addBtn.addEventListener('click', function (e) {
      e.preventDefault();
      container.insertAdjacentHTML('beforeend', makeFamilyRow());
    });
  }

  // Eliminar fila con delegación
  container.addEventListener('click', function (e) {
    const btn = e.target.closest('button.remove-family');
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation(); // 🔑 evita que burbujee al overlay del modal

    const row = btn.closest('.family-row');
    if (row) row.remove();
  });

  // 🔹 Función para abrir el modal limpio
  window.openFormModal = function () {
    container.innerHTML = '';              // limpiar familiares anteriores
    container.insertAdjacentHTML('beforeend', makeFamilyRow()); // iniciar con una fila vacía
    modal.classList.remove('hidden');
  };

  // 🔹 Función para cerrar el modal
  window.closeFormModal = function () {
    modal.classList.add('hidden');
  };
});
