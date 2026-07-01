
import axios from 'axios';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';

import { init as initFoliosVigentes } from './reportes/folios-vigentes.js';
import { init as initFoliosPendientesSucursal } from './reportes/folios-pendientes-sucursal.js';
import { init as initFoliosPorVencer } from './reportes/folios-por-vencer.js';
import { init as initFoliosPendientesEscaneo, limpiarSelectorPersonalEscaneo } from './reportes/folios-pendientes-escaneo.js';
import { init as initFoliosPendientesRegistro, limpiarSelectorPersonalRegistro } from './reportes/folios-pendientes-registro.js';
import { init as initCarnet } from './reportes/carnet.js';
import { init as initVigenciaDocumentos } from './reportes/vigencia-documentos.js';
import { init as initCertificados } from './reportes/certificados.js';


document.addEventListener('DOMContentLoaded', () => {

    // ==========================================================
    // LÓGICA PARA TABS Y LIMPIEZA: CARNET Y CERTIFICADOS
    // ==========================================================
    const modalUnificado = document.getElementById('modalCarnet');
    const tabCarnet = document.getElementById('tabCarnet');
    const tabCertificados = document.getElementById('tabCertificados');
    const contenidoCarnet = document.getElementById('contenidoCarnet');
    const contenidoCertificados = document.getElementById('contenidoCertificados');

    if (tabCarnet && tabCertificados && contenidoCarnet && contenidoCertificados) {
        // Evento click para la pestaña Carnet
        tabCarnet.addEventListener('click', () => {
            contenidoCarnet.classList.remove('hidden');
            contenidoCarnet.classList.add('flex');
            
            contenidoCertificados.classList.add('hidden');
            contenidoCertificados.classList.remove('flex');

            tabCarnet.classList.add('border-primary', 'bg-white', 'text-primary');
            tabCarnet.classList.remove('border-transparent', 'text-default-500');

            tabCertificados.classList.remove('border-primary', 'bg-white', 'text-primary');
            tabCertificados.classList.add('border-transparent', 'text-default-500');
        });

        // Evento click para la pestaña Certificados
        tabCertificados.addEventListener('click', () => {
            contenidoCertificados.classList.remove('hidden');
            contenidoCertificados.classList.add('flex');
            
            contenidoCarnet.classList.add('hidden');
            contenidoCarnet.classList.remove('flex');

            tabCertificados.classList.add('border-primary', 'bg-white', 'text-primary');
            tabCertificados.classList.remove('border-transparent', 'text-default-500');

            tabCarnet.classList.remove('border-primary', 'bg-white', 'text-primary');
            tabCarnet.classList.add('border-transparent', 'text-default-500');
        });
    }

    // Lógica de Limpieza al cerrar
    if (modalUnificado) {
        const resetearFiltros = () => {
            // 1. Limpiar selects de Carnet
            const inputsCarnet = ['filtroCarnetCategoria', 'filtroCarnetSucursal', 'filtroCarnetTipoPers', 'filtroCarnetVigencia', 'filtroCarnetEstado'];
            inputsCarnet.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = (id === 'filtroCarnetCategoria') ? '' : 'T';
            });

            // 2. Limpiar TomSelects de Certificados
            const defaultsTomSelect = {
                'filtroCertSucursal': '',
                'filtroCertTipoPers': 'T',
                'filtroCertCertificado': '',
                'filtroCertVigencia': 'T',
                'filtroCertEstado': 'T'
            };

            for (const [id, defaultVal] of Object.entries(defaultsTomSelect)) {
                const el = document.getElementById(id);
                if (el && el.tomselect) {
                    el.tomselect.setValue(defaultVal);
                }
            }

            // 3. Restaurar fecha
            const fechaInput = document.getElementById('filtroCertFechaVenc');
            if (fechaInput) {
                fechaInput.value = new Date().toISOString().split('T')[0];
            }

            // 4. Ocultar resultados
            const resCarnet = document.getElementById('resultadosCarnet');
            const resCert = document.getElementById('resultadosCertificados');
            if (resCarnet) resCarnet.classList.add('hidden');
            if (resCert) resCert.classList.add('hidden');

            // 5. Forzar tab de carnet activa al volver a abrir
            if (tabCarnet) {
                contenidoCarnet.classList.remove('hidden');
                contenidoCarnet.classList.add('flex');
                if (contenidoCertificados) contenidoCertificados.classList.add('hidden');
                
                tabCarnet.classList.add('border-primary', 'bg-white', 'text-primary');
                if (tabCertificados) tabCertificados.classList.remove('border-primary', 'bg-white', 'text-primary');
            }
        };

        // Asignar el evento de limpieza a todos los botones de cierre
        const botonesCerrar = modalUnificado.querySelectorAll('.btnCerrarModal');
        botonesCerrar.forEach(btn => {
            btn.addEventListener('click', resetearFiltros);
        });

        // Clic fuera del modal para limpiar
        modalUnificado.addEventListener('click', (e) => {
            if (e.target === modalUnificado) {
                resetearFiltros();
            }
        });
    }
    
    const modales = {
        foliosVigentes: document.getElementById('modalFoliosVigentes'),
        foliosPendientesSucursal: document.getElementById('modalFoliosPendientesSucursal'),
        foliosPorVencer: document.getElementById('modalFoliosPorVencer'),
        foliosPendientesEscaneo: document.getElementById('modalFoliosPendientesEscaneo'),
        foliosPendientesRegistro: document.getElementById('modalFoliosPendientesRegistro'),
        vigenciaDocumentos: document.getElementById('modalVigenciaDocumentos'),
        carnet: document.getElementById('modalCarnet'),
        certificados: document.getElementById('modalCertificados'),
        constanciasEntrega: document.getElementById('modalConstanciasEntrega'),
    };

    function abrirModal(modal) {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModal(modal) {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function cerrarTodo() {
        Object.values(modales).forEach(m => { if (m) cerrarModal(m); });
        [
            'resultadosFoliosVigentes',
            'resultadosFoliosPendientes',
            'resultadosFoliosPorVencer',
            'resultadosEscaneo',
            'resultadosRegistro',
            'resultadosCarnet',
            'resultadosVigencia'
        ].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.add('hidden');
        });
        limpiarSelectorPersonalEscaneo();
        limpiarSelectorPersonalRegistro();
    }

    document.querySelectorAll('.btnCerrarModal').forEach(btn => btn.addEventListener('click', cerrarTodo));

    Object.values(modales).forEach(modal => {
        if (!modal) return;
        modal.addEventListener('click', (e) => { if (e.target === modal) cerrarTodo(); });
    });

    new TomSelect('#filtroClienteSelect', { 
    placeholder: '-Seleccionar-', 
    allowEmptyOption: true,
    dropdownParent: 'body' // <--- ¡Esta es la magia!
});

new TomSelect('#filtroSucursalSelect', { 
    placeholder: '-Seleccionar-', 
    allowEmptyOption: true,
    dropdownParent: 'body' // <--- Agrégalo aquí también
});

    const btnMap = {
        btnReporteFoliosVigentes: modales.foliosVigentes,
        btnReporteFoliosPendientesSucursal: modales.foliosPendientesSucursal,
        btnReporteFoliosPorVencer: modales.foliosPorVencer,
        btnReporteFoliosPendientesEscaneo: modales.foliosPendientesEscaneo,
        btnReporteFoliosPendientesRegistro: modales.foliosPendientesRegistro,
        btnReporteVigenciaDocumentos: modales.vigenciaDocumentos,
        btnReporteCarnet: modales.carnet,
        btnReporteCertificados: modales.certificados,
        btnReporteConstanciasEntrega: modales.constanciasEntrega,
    };

    Object.entries(btnMap).forEach(([id, modal]) => {
        const btn = document.getElementById(id);
        if (btn) btn.addEventListener('click', () => abrirModal(modal));
    });

    const filtroSucursalDiv = document.getElementById('filtroSucursalDiv');
    const filtroClienteDiv = document.getElementById('filtroClienteDiv');
    const filtroCodigoDiv = document.getElementById('filtroCodigoDiv');

    document.querySelectorAll('input[name="tipoFiltro"]').forEach(radio => {
        radio.addEventListener('change', function () {
            filtroSucursalDiv.classList.add('hidden');
            filtroClienteDiv.classList.add('hidden');
            filtroCodigoDiv.classList.add('hidden');
            if (this.value === 'sucursal') filtroSucursalDiv.classList.remove('hidden');
            else if (this.value === 'cliente') filtroClienteDiv.classList.remove('hidden');
            else if (this.value === 'servicio') filtroCodigoDiv.classList.remove('hidden');
        });
    });

    initFoliosVigentes();
    initFoliosPendientesSucursal();
    initFoliosPorVencer();
    initFoliosPendientesEscaneo();
    initFoliosPendientesRegistro();
    initCarnet();
    initVigenciaDocumentos();
    initCertificados();
});