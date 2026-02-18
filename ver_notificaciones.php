<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$notificaciones = \Illuminate\Support\Facades\DB::table('sw_notificaciones_matriculas')
    ->select('codigo', 'nombre_curso', 'tipo', 'total_personas', 'enviados', 'fallidos', 'leido')
    ->orderBy('created_at', 'desc')
    ->get();

echo "📬 Total notificaciones: " . $notificaciones->count() . "\n\n";

foreach ($notificaciones as $n) {
    $icono = match($n->tipo) {
        'completado' => '✅',
        'error_conexion' => '❌',
        'multiples_fallos' => '⚠️',
        default => 'ℹ️'
    };
    
    $leido = $n->leido ? '(leído)' : '(NO LEÍDO)';
    
    echo "{$icono} [{$n->codigo}] {$n->nombre_curso} {$leido}\n";
    echo "   Tipo: {$n->tipo}\n";
    echo "   Total: {$n->total_personas}, Enviados: {$n->enviados}, Fallidos: {$n->fallidos}\n\n";
}
