<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    \App\Models\NotificacionMatricula::crearNotificacionExitosa(6, 69, 'Prueba Manual', 2);
    echo "✅ Notificacion creada OK\n";
    
    $count = \Illuminate\Support\Facades\DB::table('sw_notificaciones_matriculas')->count();
    echo "Total notificaciones: {$count}\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
