<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Connectors\SqlServerConnector;
use PDO;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Interceptamos la creación del conector para SQL Server
        $this->app->bind('db.connector.sqlsrv', function () {
            $connector = new SqlServerConnector();
            
            // Obtenemos las opciones PDO por defecto de Laravel
            $options = $connector->getDefaultOptions();
            
            // Quitamos el atributo que los drivers antiguos de SQL Server no soportan
            unset($options[PDO::ATTR_STRINGIFY_FETCHES]);
            
            // Aplicamos las opciones "limpias" al conector
            $connector->setDefaultOptions($options);
            
            return $connector;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // <proyecto>/temp es el directorio temporal global de la app (ignorado por
        // git). Si no existe, sys_get_temp_dir() apunta a una carpeta inexistente y
        // todo lo que escriba ahí (PhpSpreadsheet, reportes, subidas) revienta con
        // "fopen(): Failed to open stream: No such file or directory" (500).
        $dir = base_path('temp');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        putenv('TMPDIR=' . $dir);
        putenv('TEMP=' . $dir);
        putenv('TMP=' . $dir);
    }
}
