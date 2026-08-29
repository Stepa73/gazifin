<?php

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
        $exceptions->shouldRenderJsonWhen(
            // Kalkulačka se ukládá přes fetch, takže potřebuje chyby v JSONu.
            // Bez toho by validační chyba skončila jako HTML redirect a prohlížeč
            // by ho následoval — uložení by vypadalo úspěšně, i když neproběhlo.
            fn (Request $request) => $request->is('api/*') || $request->routeIs('calculator.update'),
        );
    })->create();
