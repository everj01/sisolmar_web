<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\DjService;
use Illuminate\Support\Facades\DB;

$data = [
    'cod_postulante' => '7',
    'dni' => '74344085',
    'nombres_apellidos' => 'Alexis Julca', // Corregido
    'estado_civil' => 'S',
    'sexo' => 'FEMENINO',
    'fecha_nacimiento' => '2000-01-10',
    'essalud' => 'SI',
    'tipo_sangre' => 'O+',
    'peso' => 80,
    'talla' => 1.51,
    'profesion_alterna' => '',
    'grado_instruccion' => '3',
    'institucion' => '3',
    'carrera' => '4',
    'anio_egreso' => 2021,
    'sabe_nadar' => 'SI',
    'consumo_sustancias' => 'SI',
    'departamento_actual' => '02',
    'provincia_actual' => '0218',
    'distrito_actual' => '021807',
    'direccion_actual' => 'Nuevo Chimbote 02710',
    'correo' => 'julcaaliesa@gmail.com',
    'contacto_emergencia' => 'asdasd',
    'celular_emergencia' => '921345654',
    'parentesco_emergencia' => 'madre',
    'curso_sucamec' => 'SI',
    'institucion_laboral' => 'asdasd',
    'licencia_arma' => '[{"value":"L2"},{"value":"L5"}]',
    'tipo_arma' => 'REVOLVER',
    'arma_propia' => 'NO',
    'brevete' => 'asdasdasd',
    'clase_brevete' => 'A-IIb', // Esto disparaba el truncamiento (varchar 1)
    'tipo_vehiculo' => 'asdasd',
    'empresa_anterior' => 'Universidad tegnologica del peru',
    'cargo_anterior' => 'asdas',
    'duracion_anterior' => '2',
    'direccion_dni' => 'Nuevo Chimbote 02710',
    'departamento_dni' => '15',
    'provincia_dni' => '1503',
    'distrito_dni' => '150302',
    'caduca' => '2026-02-24',
    'pensionista' => 'SI',
    'sistema_previsional' => 'ONP'
];

try {
    // No usamos transacción externa para no pelear con la interna de DjService en SQL Server
    $service = new DjService();
    $result = $service->guardarDeclaracionJurada($data);
    echo "SUCCESS: DJ saved correctly.\n";
    echo "Saved Data values for critical fields:\n";
    echo "PERS_CONLICARMAS: " . $result->PERS_CONLICARMAS . "\n";
    echo "PERS_NROLICENCIA: " . $result->PERS_NROLICENCIA . "\n";
    echo "CLASE_BREVETE: " . $result->CLASE_BREVETE . "\n";
    echo "ESSALUD: " . $result->ESSALUD . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getSql')) {
        echo "SQL: " . $e->getSql() . "\n";
    }
}
