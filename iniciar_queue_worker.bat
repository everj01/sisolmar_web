@echo off
cd /d "C:\Users\Usuario\Desktop\proyect\SISOLMAR"
title Queue Worker - SISOLMAR
echo ========================================
echo  Queue Worker SISOLMAR
echo  NO CERRAR ESTA VENTANA
echo ========================================
echo.

:loop
echo [%date% %time%] Iniciando Queue Worker...
php artisan queue:work --tries=3 --timeout=120 --sleep=3 --max-jobs=1000

echo.
echo [%date% %time%] Queue Worker detenido. Reiniciando en 5 segundos...
timeout /t 5
goto loop
