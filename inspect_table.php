<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$fields = [
    'CODI_PERS', 'NRO_DOCU_IDEN', 'NOMB_1', 'NOMB_2', 'APEL_1', 'APEL_2',
    'PERS_EMAIL', 'DIRECCION', 'PERS_NOMCONTACTO', 'PERS_NROEMERGENCIA',
    'PERS_CONYUGE', 'PERS_CONDISCAMEC', 'PERS_NRODISCAMEC', 'PERS_CONLICARMAS',
    'PERS_TIPOARMA', 'PERS_CONARMAS', 'PERS_BREVETE', 'CLASE_BREVETE',
    'CATEGORIA_BREVETE', 'PERS_CTRABANT', 'PERS_CARGOTRABANT', 'PERS_DURACIONANT',
    'PERS_DIREC_DNI', 'tipo_sangr', 'PERS_PROFESION'
];

$placeholders = implode(',', array_fill(0, count($fields), '?'));
$columns = Illuminate\Support\Facades\DB::select("
    SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'sw_MIGRA_PERSONAL' 
    AND COLUMN_NAME IN ($placeholders)
", $fields);

echo "COLUMN_NAME | DATA_TYPE | LENGTH\n";
echo "-------------------------------\n";
foreach ($columns as $column) {
    echo "{$column->COLUMN_NAME} | {$column->DATA_TYPE} | {$column->CHARACTER_MAXIMUM_LENGTH}\n";
}
