<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Client\RequestException;

//https://laravel.com/framework/docs/errors
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi(); //hace que Sanctum auntentique por cookie session
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen( //<- hace que cualquier error se devuelva como un json
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        //manejamos los errores de peticion de manera centralizada
        $exceptions->render(function (RequestException $e, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            return match ($e->response->status()) {
                404 => response()->json([
                        'message' => 'Película no encontrada.'
                        ], 404),
                default => response()->json([
                        'message' => 'El servicio de películas no está disponible.'
                        ], 502),
            };
        });
    })->create();
