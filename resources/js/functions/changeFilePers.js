const archivoInput = document.getElementById("archivoInput");
const btnSeleccionar = document.getElementById("btnSeleccionar");
const listaArchivos = document.getElementById("listaArchivos");

// Almacena los archivos seleccionados (persistentes)
//let archivosSeleccionados = [];
window.archivosSeleccionados = [];

// Abrir el selector de archivos
btnSeleccionar.addEventListener("click", () => {
    archivoInput.click();
});

// Cuando se seleccionan archivos
archivoInput.addEventListener("change", (e) => {
    const nuevosArchivos = Array.from(e.target.files);
    const maxHojas = parseInt(document.getElementById('cantArchivos').value, 10);

    for (let archivo of nuevosArchivos) {
        //Máximo peso
        if (archivo.size > 1024 * 1024) {
            alert(`El archivo "${archivo.name}" supera 1 MB y fue omitido.`);
            continue;
        }

        // Evitar más de la cantidad máxima
        if (archivosSeleccionados.length >= maxHojas) {
            alert(`No puedes seleccionar más de ${maxHojas} archivos.`);
            break;
        }

        // Evitar duplicados (por nombre y tamaño)
        const yaExiste = archivosSeleccionados.some(a =>
            a.name === archivo.name && a.size === archivo.size
        );

        if (!yaExiste) {
            archivosSeleccionados.push(archivo);
        }
    }

    actualizarLista();
    archivoInput.value = ""; // reset para permitir subir mismos archivos después
});

// Actualiza la lista en pantalla
function actualizarLista() {
    listaArchivos.innerHTML = "";

    archivosSeleccionados.forEach((archivo, index) => {
        const item = document.createElement("li");
        item.className =
            "flex items-center justify-between bg-gray-100 p-2 rounded";

        item.innerHTML = `
        <span class="text-sm text-gray-800">${archivo.name}</span>
        <div class="flex items-center gap-x-2">
            <button type="button" class="text-red-500 hover:text-red-700 text-xs" data-index="${index}">
                <i class="i-tabler-trash size-4 shrink-0" style="margin-bottom: -4px;"></i> Quitar
            </button>
        </div>
      `;

        listaArchivos.appendChild(item);
    });
}

// Eliminar archivo individual
listaArchivos.addEventListener("click", (e) => {
    if (e.target.tagName === "BUTTON") {
        const index = parseInt(e.target.dataset.index);
        archivosSeleccionados.splice(index, 1);
        actualizarLista();
    }
});
