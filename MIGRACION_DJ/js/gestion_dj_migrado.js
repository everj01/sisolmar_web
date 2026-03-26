$(document).ready(function() {
    // Configuración inicial
    const AJAX_URL = 'ajax_gestion_dj.php';

    // Fix para el error de recursión de foco (RangeError: Maximum call stack size exceeded)
    if ($.fn.modal && $.fn.modal.Constructor) {
        $.fn.modal.Constructor.prototype.enforceFocus = function() {};
    }

    // Almacén de datos personales para autollenado de combos dependientes (Ubicación, Carreras)
    let personalData = null;

    // Abrir Modal
    window.abrirModalDJ = function() {
        $('#modalDJ').modal('show');
    };

    // Al mostrar el modal, cargamos los catálogos base y luego los datos del usuario
    $('#modalDJ').on('shown.bs.modal', function () {
        cargarCatalogos(() => {
            cargarDatosPersonales();
            cargarUbicacion('dept');
        });
    });

    function cargarCatalogos(callback) {
        axios.get(`${AJAX_URL}?action=get_catalogs`)
            .then(res => {
                const { grados, instituciones, carreras } = res.data;
                populateSelect('#selGrado', grados, 'GRINST_CODIGO', 'GRINST_DESC');
                populateSelect('#selInstitucion', instituciones, 'INST_CODIGO', 'INST_DESC');
                
                // Cargamos todas las carreras inicialmente para que el autolleno funcione
                // Luego se filtrarán si el usuario cambia la institución
                populateSelect('#selCarrera', carreras, 'CARRERA_CODIGO', 'CARRERA_DESC');
                
                if (callback) callback();
            });
    }

    function cargarDatosPersonales() {
        axios.get(`${AJAX_URL}?action=get_personal_data`)
            .then(res => {
                if (res.data.success) {
                    const d = res.data.data;
                    personalData = d; // Guardamos para cascadas

                    // Llenado de campos de texto
                    $('#APEL_1').val(d.APEL_1);
                    $('#APEL_2').val(d.APEL_2);
                    $('#NOMBRES').val(`${d.NOMB_1} ${d.NOMB_2 || ''}`);
                    $('#PERS_DNI').val(d.PERS_DNI);
                    $('#PERS_EMAIL').val(d.PERS_EMAIL);
                    $('#PERS_TELEFONO').val(d.PERS_TELEFONO);
                    $('#DIRECCION').val(d.DIRECCION);
                    $('#PERS_NOMCONTACTO').val(d.PERS_NOMCONTACTO);
                    $('#PERS_NROEMERGENCIA').val(d.PERS_NROEMERGENCIA);
                    
                    // Educación
                    $('#selGrado').val(d.GRINST_CODIGO);
                    $('#selInstitucion').val(d.INST_CODIGO);
                    $('#selCarrera').val(d.CARRERA_CODIGO);
                    $('#PERS_ANIO_EGRESO').val(d.PERS_ANIO_EGRESO || d.ANIO_EGRESO);
                    
                    // Ubicación (Se dispara la cascada al cargar depts)
                    $('#selDept').val(d.DEPT_NOMB).trigger('change');

                    if (d.FOTO_PATH) {
                        $('#photoPreview').attr('src', d.FOTO_PATH);
                    }
                }
            });
    }

    function populateSelect(selector, data, valKey, textKey) {
        const sel = $(selector);
        const currentVal = sel.val();
        sel.empty().append('<option value="">Seleccione...</option>');
        if (data) {
            data.forEach(item => {
                sel.append(`<option value="${item[valKey]}">${item[textKey]}</option>`);
            });
        }
        if (currentVal) sel.val(currentVal);
    }

    // Lógica de Ubicación en Cascada
    function cargarUbicacion(type, dept = '', prov = '', callback) {
        axios.get(`${AJAX_URL}?action=get_ubicacion&type=${type}&dept=${dept}&prov=${prov}`)
            .then(res => {
                const target = type === 'dept' ? '#selDept' : (type === 'prov' ? '#selProv' : '#selDist');
                populateSelect(target, res.data, 'id', 'text');
                if (callback) callback();
            });
    }

    $('#selDept').on('change', function() {
        const dept = $(this).val();
        if (dept) {
            cargarUbicacion('prov', dept, '', () => {
                if (personalData && personalData.PROV_NOMB) {
                    $('#selProv').val(personalData.PROV_NOMB).trigger('change');
                }
            });
        }
        $('#selProv, #selDist').empty().append('<option value="">Seleccione...</option>');
    });

    $('#selProv').on('change', function() {
        const dept = $('#selDept').val();
        const prov = $(this).val();
        if (dept && prov) {
            cargarUbicacion('dist', dept, prov, () => {
                if (personalData && personalData.DIST_NOMB) {
                    $('#selDist').val(personalData.DIST_NOMB);
                }
            });
        }
        $('#selDist').empty().append('<option value="">Seleccione...</option>');
    });

    // Cascada de Carreras por Institución
    $('#selInstitucion').on('change', function() {
        const instId = $(this).val();
        if (instId) {
            axios.get(`${AJAX_URL}?action=get_carreras&iedu_codigo=${instId}`)
                .then(res => {
                    populateSelect('#selCarrera', res.data, 'CARRERA_CODIGO', 'CARRERA_DESC');
                    if (personalData && personalData.CARRERA_CODIGO) {
                        $('#selCarrera').val(personalData.CARRERA_CODIGO);
                    }
                });
        }
    });

    // Guardado del Formulario
    $('#formDJ').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        axios.post(`${AJAX_URL}?action=save_dj_completo`, formData)
            .then(res => {
                if (res.data.success) {
                    Swal.fire('¡Éxito!', res.data.message, 'success');
                    $('#modalDJ').modal('hide');
                } else {
                    Swal.fire('Error', res.data.message, 'error');
                }
            })
            .catch(err => Swal.fire('Error', 'No se pudo guardar la información', 'error'));
    });

    // Generación de PDF
    $('#btnPreviewPDF').on('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        doc.setFontSize(14);
        doc.text('DECLARACIÓN JURADA DE DATOS (RH-02)', 105, 20, null, null, 'center');
        doc.setFontSize(10);
        doc.text(`Fecha: ${new Date().toLocaleDateString()}`, 190, 20, null, null, 'right');
        
        let y = 40;
        const data = [
            ['Apellidos:', `${$('#APEL_1').val()} ${$('#APEL_2').val()}`],
            ['Nombres:', $('#NOMBRES').val()],
            ['DNI:', $('#PERS_DNI').val()],
            ['Email:', $('#PERS_EMAIL').val()],
            ['Teléfono:', $('#PERS_TELEFONO').val()],
            ['Dirección:', $('#DIRECCION').val()],
            ['Institución:', $('#selInstitucion option:selected').text()],
            ['Carrera:', $('#selCarrera option:selected').text()],
            ['Año Egreso:', $('#PERS_ANIO_EGRESO').val()]
        ];
        
        data.forEach(item => {
            doc.setFont(undefined, 'bold');
            doc.text(item[0], 20, y);
            doc.setFont(undefined, 'normal');
            doc.text(item[1], 60, y);
            y += 10;
        });
        
        doc.save('RH-02_Declaracion_Jurada.pdf');
    });
});
