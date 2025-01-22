<?php

use App\Http\Middleware\CheckPermissionMiddleware;
use App\Http\Middleware\TextResponseMiddleware;
use App\Http\Middleware\XmlResponseMiddleware;
use App\Http\Middleware\YamlResponseMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'check.permission' => CheckPermissionMiddleware::class,
            'text' => TextResponseMiddleware::class,
            'yaml' => YamlResponseMiddleware::class,
            'xml' => XmlResponseMiddleware::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
