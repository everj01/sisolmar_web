<?php
session_start();
require_once("CDatos/ClsDatos.DJ.php");

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$dj = new CD_DJ();

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'get_personal_data':
            $codi_pers = $_GET['codi_pers'] ?? $_SESSION['USER_Codi_Pers'];
            $data = $dj->Get_Personal_Data_Completo($codi_pers);
            // También necesitamos familiares
            $conyugue = $dj->DJ_Conyugue($codi_pers);
            $hijos = $dj->DJ_Hijos($codi_pers);
            $padres = $dj->DJ_Padre($codi_pers, $_SESSION['USER_Empresa_cod']);
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'familiares' => [
                    'conyugue' => $conyugue,
                    'hijos' => $hijos,
                    'padres' => $padres
                ]
            ]);
            break;

        case 'get_catalogs':
            echo json_encode([
                'success' => true,
                'grados' => $dj->Listar_Grados_Instruccion(),
                'instituciones' => $dj->Listar_Instituciones(),
                'carreras' => $dj->Listar_Carreras() // Aún cargamos todas inicialmente para el autocompletado/autolleno
            ]);
            break;

        case 'get_carreras':
            $iedu_codigo = $_GET['iedu_codigo'] ?? '';
            $carreras = $iedu_codigo ? $dj->Listar_Carreras_Por_Institucion($iedu_codigo) : $dj->Listar_Carreras();
            echo json_encode($carreras);
            break;

        case 'get_ubicacion':
            $type = $_GET['type'] ?? '';
            $dept = $_GET['dept'] ?? '';
            $prov = $_GET['prov'] ?? '';
            
            if ($type == 'dept') {
                echo json_encode($dj->Listar_Departamentos());
            } elseif ($type == 'prov') {
                echo json_encode($dj->Listar_Provincias($dept));
            } elseif ($type == 'dist') {
                echo json_encode($dj->Listar_Distritos($dept, $prov));
            }
            break;

        case 'save_dj_completo':
            // Aquí iría la lógica masiva de guardado
            // Por simplicidad en esta fase, usaremos el método existente para lo básico
            $data = $_POST;
            $dj->DJ_update_datos(
                $_SESSION['USER_Codi_Pers'],
                $data['PERS_EMAIL'] ?? '',
                $data['PERS_TELEFONO'] ?? '',
                $data['DIRECCION'] ?? '',
                $data['PERS_CONBREVETE'] ?? '',
                $data['PERS_BREVETE'] ?? '',
                $data['CATEGORIA_BREVETE'] ?? '',
                $data['CLASE_BREVETE'] ?? '',
                $data['PERS_NROEMERGENCIA'] ?? '',
                $data['PERS_NOMCONTACTO'] ?? ''
            );
            
            echo json_encode(['success' => true, 'message' => 'Datos básicos actualizados correctamente.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
