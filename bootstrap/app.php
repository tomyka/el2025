<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureIsLevel9Admin;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            SetLocale::class,
        ]);
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'superadmin' => SuperAdminMiddleware::class,
            'level9admin' => EnsureIsLevel9Admin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
