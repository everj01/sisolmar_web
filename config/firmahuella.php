<?php

$venvPython = PHP_OS_FAMILY === 'Windows'
    ? base_path('.venv/Scripts/python.exe')
    : base_path('.venv/bin/python');

return [
    // En desarrollo se usa el entorno virtual del proyecto. Si no existe,
    // se intenta con el lanzador de Windows o Python 3 en Linux.
    'python' => env('FIRMAHUELLA_PYTHON') ?: (file_exists($venvPython)
        ? $venvPython
        : (PHP_OS_FAMILY === 'Windows' ? 'py' : 'python3')),
    'timeout' => (int) env('FIRMAHUELLA_TIMEOUT', 120),
    'destinos' => [
        'dj' => '\\\\192.168.10.5\\Extranet_2024\\apps\\sisolmar\\storage\\app\\dj\\2026',
        'firmas' => '\\\\192.168.10.2\\Biblioteca_Grafica\\FIRMAS\\PERSONAL',
        'huellas' => '\\\\192.168.10.2\\Biblioteca_Grafica\\HUELLAS_DIGITALES\\PERSONAL',
    ],
];
