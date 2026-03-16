<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        $schedule->command('app:enviar-alertas-caducidad')
                 ->dailyAt('06:00')
                 ->withoutOverlapping()
                 ->onSuccess(function () {
                     \Log::info('Comando de envío de alertas de caducidad ejecutado exitosamente.');
                 })
                 ->onFailure(function () {
                     \Log::error('Error al ejecutar el comando de envío de alertas de caducidad.');
                 });

        $schedule->command('capacitacion:clonar-vencidos')
                 ->dailyAt('00:00')
                 ->withoutOverlapping()
                 ->onSuccess(function () {
                     \Log::info('Comando de clonación de cursos ejecutado exitosamente.');
                 })
                 ->onFailure(function () {
                     \Log::error('Error al ejecutar el comando de clonación de cursos.');
                 });

        $schedule->command('capacitacion:procesar-induccion')
                 ->hourly()
                 ->withoutOverlapping()
                 ->onSuccess(function () {
                     \Log::info('Comando de inducción automática ejecutado exitosamente.');
                 });

        $schedule->command('capacitacion:procesar-aniversario-pce')
                 ->monthlyOn(1, '02:00')
                 ->withoutOverlapping()
                 ->onSuccess(function () {
                     \Log::info('Comando de matrícula por aniversario PCE ejecutado exitosamente.');
                 });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
