<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Listar todas las columnas de si_solm.dbo.CARGOS
    $columns = DB::connection('sqlsrv')->select("
        SELECT COLUMN_NAME 
        FROM si_solm.INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'CARGOS'
    ");
    foreach ($columns as $col) {
        echo $col->COLUMN_NAME . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
