<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$db = app('db');

try {
    $db->connection('sqlsrv')->getPdo();
    echo "Connected successfully.";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
