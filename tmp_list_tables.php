<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $tables = DB::connection('sqlsrv')->select("SELECT name FROM si_solm.sys.tables WHERE name LIKE '%CARG%'");
    foreach ($tables as $table) {
        echo $table->name . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
