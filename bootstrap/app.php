<?php

use App\Support\UserFacingError;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (QueryException $exception, Request $request) {
            if (! in_array($exception->getConnectionName(), ['sigh', 'sqlsrv'], true)) {
                return null;
            }

            $reference = UserFacingError::report($exception, 'CITAS-HSJ', [
                'path' => $request->path(),
                'connection' => $exception->getConnectionName(),
            ]);

            $message = 'El servicio de citas no está disponible temporalmente. Intenta nuevamente en unos minutos.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'ok' => false,
                    'success' => false,
                    'error' => $message,
                    'reference' => $reference,
                ], 503);
            }

            return response()->view('errors.service-unavailable', [
                'message' => $message,
                'reference' => $reference,
            ], 503);
        });
    })->create();
