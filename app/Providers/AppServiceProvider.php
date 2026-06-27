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
        putenv('TMPDIR=' . base_path('temp'));
        putenv('TEMP=' . base_path('temp'));
        putenv('TMP=' . base_path('temp'));
    }
}
