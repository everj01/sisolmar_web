document.addEventListener('DOMContentLoaded', () => {
    loadNotifications();
    setInterval(loadNotifications, 30000); // 30 segundos
});

async function loadNotifications() {
    try {
        const response = await fetch(`${VITE_URL_APP}/api/notificaciones/pendientes-etapa2`);
        const result = await response.json();

        const list = document.getElementById('notif-list');
        const badge = document.getElementById('notif-count');
        
        // Elementos del Modal
        const modalList = document.getElementById('modal-etapa2-list');
        const modalTotal = document.getElementById('modal-etapa2-total');

        // ====================================================
        // PREVENIR BORRADO DE ALERTAS SIP DEMANDA
        // ====================================================
        const demandasVivas = Array.from(list.querySelectorAll('[id^="campana-demanda-"]')).map(el => el.outerHTML).join('');
        const cantidadDemandas = list.querySelectorAll('[id^="campana-demanda-"]').length;

        if (!result.success || result.total === 0) {
            // Si Etapa 2 es cero, pero SÍ hay Demandas, no borramos nada
            if (demandasVivas === '') {
                list.innerHTML = `
                    <div class="px-4 py-8 text-center flex flex-col items-center justify-center gap-2">
                        <i class="bx bx-check-shield text-4xl text-green-500"></i>
                        <span class="font-bold text-gray-700">¡Todo al día!</span>
                        <span class="text-xs text-gray-400">No hay notificaciones pendientes.</span>
                    </div>`;
                badge.classList.add('hidden');
            } else {
                list.innerHTML = demandasVivas; // Pintamos las demandas
                badge.textContent = cantidadDemandas;
                badge.classList.remove('hidden');
            }
            return;
        }

        // ====================================================
        // 1. UI DE LA CAMPANITA (Solo el resumen)
        // ====================================================
        list.innerHTML = demandasVivas + `
            <button type="button" onclick="document.getElementById('btn-open-modal-etapa2').click()" class="w-full text-left flex items-center justify-between px-4 py-4 hover:bg-slate-50 transition-colors border-b border-gray-100 group">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-orange-100 text-orange-600 group-hover:bg-orange-500 group-hover:text-white transition-colors border border-orange-200 shadow-sm">
                        <i class="bx bx-task text-xl"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-800 group-hover:text-primary transition-colors">Pendientes Etapa 2</span>
                        <span class="text-[11px] text-gray-500">Haz clic para ver el detalle</span>
                    </div>
                </div>
                <div class="flex items-center justify-center min-w-[28px] h-7 bg-red-100 text-red-600 border border-red-200 text-xs font-bold px-2 rounded-full shadow-sm">
                    ${result.total}
                </div>
            </button>
        `;

        // ====================================================
        // 2. UI DEL MODAL (Detalle por sucursales)
        // ====================================================
        if (modalTotal) modalTotal.textContent = result.total;
        
        let modalHtml = [];
        for (const [sucursal, cantidad] of Object.entries(result.data)) {
            modalHtml.push(`
                <div class="flex items-center justify-between px-4 py-3 hover:bg-slate-50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                            <i class="bx bx-store-alt text-lg"></i>
                        </div>
                        <span class="text-sm font-bold text-gray-700 uppercase">${sucursal}</span>
                    </div>
                    <span class="text-xs font-bold text-red-600 bg-red-50 px-2.5 py-1 rounded-full border border-red-100 shadow-sm">
                        ${cantidad} pend.
                    </span>
                </div>
            `);
        }
        if(modalList) modalList.innerHTML = modalHtml.join('');

        // Globito rojo de la campana (Sumamos Etapa 2 + Demandas)
        let totalFinal = result.total + cantidadDemandas;
        badge.textContent = totalFinal > 99 ? '99+' : totalFinal;
        badge.classList.remove('hidden');

    } catch (error) {
        console.error('Error cargando notificaciones', error);
        document.getElementById('notif-list').innerHTML = `
            <div class="px-4 py-6 text-center text-red-500 flex flex-col items-center">
                <i class="bx bx-error text-3xl mb-2"></i>
                <span class="font-bold text-sm">Error de sincronización</span>
            </div>`;
    }
}