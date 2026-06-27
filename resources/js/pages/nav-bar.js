/*document.addEventListener("DOMContentLoaded", () => {
    const desktopBtn = document.getElementById("desktop-toggle-sidebar");
    const body = document.body;

    desktopBtn?.addEventListener("click", () => {
        if (window.innerWidth >= 1024) {
            body.classList.toggle("sidebar-closed");

            // 🔥 IMPORTANTE: esperar el cambio de layout y redibujar Tabulator
            setTimeout(() => {
                if (typeof tblFolio !== "undefined") {
                    tblFolio.redraw(true);
                    alert('here');
                }
            }, 300); // ajusta a la duración de tu animación CSS
        }
    });
});
*/
document.addEventListener("DOMContentLoaded", () => {
    const desktopBtn = document.getElementById("desktop-toggle-sidebar");
    const body = document.body;

    desktopBtn?.addEventListener("click", () => {
        body.classList.toggle("sidebar-closed");
    
        setTimeout(() => {
            window.dispatchEvent(new Event("sidebar-toggled"));
        }, 350);
    });
    
});
