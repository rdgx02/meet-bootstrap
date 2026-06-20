<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cabeçalhos de segurança em toda resposta web.
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        // Confia no proxy/load-balancer TLS quando definido em TRUSTED_PROXIES
        // (ex.: "*" atrás de um LB conhecido). Sem isso, cookies seguros e a
        // geração de URLs https podem quebrar atrás de proxy.
        $proxies = env('TRUSTED_PROXIES');

        if (filled($proxies)) {
            $middleware->trustProxies(
                at: $proxies === '*' ? '*' : explode(',', (string) $proxies),
            );
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
