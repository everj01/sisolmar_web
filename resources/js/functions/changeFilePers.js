const archivoInput   = document.getElementById('archivoInput');
const btnSeleccionar = document.getElementById('btnSeleccionar');
const listaArchivos  = document.getElementById('listaArchivos');

// Abrir selector al hacer click en la zona
btnSeleccionar.addEventListener('click', () => {
    archivoInput.click();
});

// Validar y mostrar el archivo seleccionado
archivoInput.addEventListener('change', () => {
    const archivo = archivoInput.files[0];
    if (!archivo) return;

    if (archivo.type !== 'application/pdf') {
        alert('Solo se aceptan archivos PDF.');
        archivoInput.value = '';
        return;
    }

    if (archivo.size > 5 * 1024 * 1024) {
        alert('El archivo supera el limite de 5MB.');
        archivoInput.value = '';
        return;
    }

    // Mostrar el archivo seleccionado en la lista
    listaArchivos.innerHTML = '';
    const item = document.createElement('li');
    item.className = 'flex items-center justify-between bg-gray-100 p-2 rounded';
    item.innerHTML = `
        <span class="text-sm text-gray-800">
            <i class="i-tabler-file-type-pdf me-1 text-red-500"></i>${archivo.name}
        </span>
        <button type="button" id="btnQuitarPdf" class="text-red-500 hover:text-red-700 text-xs">
            <i class="i-tabler-trash size-4 shrink-0" style="margin-bottom:-4px;"></i> Quitar
        </button>
    `;
    listaArchivos.appendChild(item);

    document.getElementById('btnQuitarPdf').addEventListener('click', () => {
        archivoInput.value = '';
        listaArchivos.innerHTML = '';
    });
});
