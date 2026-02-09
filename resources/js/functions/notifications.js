document.addEventListener('DOMContentLoaded', () => {
    loadNotifications()
    setInterval(loadNotifications, 60000)
})

async function loadNotifications() {
    try {
        const response = await fetch('/api/notificaciones/folios-por-vencer');
        const data = await response.json();

        const list = document.getElementById('notif-list');
        const badge = document.getElementById('notif-count');

        if (!data.length) {
            list.innerHTML = `
                <div class="px-4 py-6 text-center text-sm text-default-500">
                    No hay documentos por vencer 🎉
                </div>`;
            badge.classList.add('hidden');
            return;
        }

        let totalDocs = 0;
        let notificationsHtml = [];

        data.forEach(persona => {
            totalDocs += persona.documentos.length;

            let docsHtml = persona.documentos.map(doc => `
                <div class="text-xs text-default-600">
                    • ${doc.folio} vence en <b>${doc.dias_restantes}</b> días
                </div>
            `).join('');

            notificationsHtml.push(`
                <a href="/file_control/chargefile?codPersonal=${persona.codPersonal}&nombre=${encodeURIComponent(persona.personal)}"
                   class="flex px-4 py-3 hover:bg-default-100 transition-colors">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center w-11 h-11 rounded-full bg-red-100 text-red-600">
                            <i class="i-tabler-alert-circle text-xl"></i>
                        </div>
                    </div>
                    <div class="w-full ps-3">
                        <div class="text-sm font-semibold text-default-900 mb-1">
                            ${persona.personal}
                        </div>
                        <div class="space-y-0.5">
                            ${docsHtml}
                        </div>
                    </div>
                </a>
            `);
        });

        // Asignar todo el HTML de una vez
        list.innerHTML = notificationsHtml.join('');

        badge.textContent = totalDocs;
        badge.classList.remove('hidden');

    } catch (error) {
        console.error('Error cargando notificaciones', error);
        const list = document.getElementById('notif-list');
        list.innerHTML = `
            <div class="px-4 py-6 text-center text-sm text-red-500">
                Error al cargar notificaciones
            </div>`;
    }
}