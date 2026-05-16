
import axios from 'axios';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';

import { init as initFoliosVigentes } from './reportes/folios-vigentes.js';
import { init as initFoliosPendientesSucursal } from './reportes/folios-pendientes-sucursal.js';
import { init as initFoliosPorVencer } from './reportes/folios-por-vencer.js';
import { init as initFoliosPendientesEscaneo, limpiarSelectorPersonalEscaneo } from './reportes/folios-pendientes-escaneo.js';
import { init as initFoliosPendientesRegistro, limpiarSelectorPersonalRegistro } from './reportes/folios-pendientes-registro.js';
import { init as initCarnet } from './reportes/carnet.js';

document.addEventListener('DOMContentLoaded', () => {

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
            'resultadosCarnet'
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

    new TomSelect('#filtroClienteSelect', { placeholder: '-Seleccionar-', allowEmptyOption: true });
    new TomSelect('#filtroSucursalSelect', { placeholder: '-Seleccionar-', allowEmptyOption: true });

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
});