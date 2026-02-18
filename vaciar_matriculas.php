<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Vaciando tabla sw_matriculas...\n";

$deleted = \Illuminate\Support\Facades\DB::table('sw_matriculas')->delete();

echo "✅ Se eliminaron {$deleted} registros de matrículas\n";

$count = \Illuminate\Support\Facades\DB::table('sw_matriculas')->count();
echo "Total de registros restantes: {$count}\n";
