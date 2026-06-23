<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        // Sesión expirada (CSRF inválido) → redirigir a login limpiamente
        if ($exception instanceof TokenMismatchException) {
            return redirect()->route('login')
                ->with('error', 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.');
        }

        return parent::render($request, $exception);
    }
}
