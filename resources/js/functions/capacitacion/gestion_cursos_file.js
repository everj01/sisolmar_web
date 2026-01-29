const archivoInput = document.getElementById("archivoInput");
const btnSeleccionar = document.getElementById("btnSeleccionar");
const listaArchivos = document.getElementById("listaArchivos");

let archivoSeleccionado = null;

btnSeleccionar.addEventListener("click", () => {
    archivoInput.click();
});

archivoInput.addEventListener("change", (e) => {
    const archivo = e.target.files[0]; // Solo el primero

    if (!archivo) return;

    // Validar peso (1MB máx)
    if (archivo.size > 1024 * 1024) {
        alert(`El archivo "${archivo.name}" supera 1 MB y fue omitido.`);
        archivoInput.value = "";
        return;
    }

    // Validar extensión
    const ext = archivo.name.split('.').pop().toLowerCase();
    if (!["doc", "docx"].includes(ext)) {
        alert(`Solo se permiten archivos .doc o .docx`);
        archivoInput.value = "";
        return;
    }

    // Guardar archivo
    archivoSeleccionado = archivo;

    actualizarLista();
    archivoInput.value = ""; // Reset para poder volver a elegir el mismo
});

// Actualiza la lista en pantalla
function actualizarLista() {
    listaArchivos.innerHTML = "";

    if (archivoSeleccionado) {
        const item = document.createElement("li");
        item.className =
            "flex items-center justify-between bg-gray-100 p-2 rounded";

        item.innerHTML = `
            <span class="text-sm text-gray-800">${archivoSeleccionado.name}</span>
            <div class="flex items-center gap-x-2">
                <button type="button" class="text-red-500 hover:text-red-700 text-xs" id="btnQuitar">
                    <i class="i-tabler-trash size-4 shrink-0" style="margin-bottom: -4px;"></i> Quitar
                </button>
            </div>
        `;

        listaArchivos.appendChild(item);

        // Quitar archivo
        document.getElementById("btnQuitar").addEventListener("click", () => {
            archivoSeleccionado = null;
            actualizarLista();
        });
    }
}
