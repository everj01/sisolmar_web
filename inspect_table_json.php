<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = Illuminate\Support\Facades\DB::select("
    SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'sw_MIGRA_PERSONAL'
");

$data = [];
foreach ($columns as $column) {
    $data[$column->COLUMN_NAME] = [
        'type' => $column->DATA_TYPE,
        'length' => $column->CHARACTER_MAXIMUM_LENGTH
    ];
}

echo json_encode($data, JSON_PRETTY_PRINT);
