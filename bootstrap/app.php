<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo('/home');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Si la sesión venció (la pantalla quedó abierta mucho tiempo), se vuelve a pedir el ingreso.
        $exceptions->render(function (TokenMismatchException $e, $request) {
            if (! $request->expectsJson()) {
                return redirect()->route('login')->with('error', 'La sesión venció. Ingrese de nuevo.');
            }
        });
    })->create();
