<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Laravel\Ai\Exceptions\AiException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {})
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AiException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'AI service is unavailable. Please try again shortly.',
                ], 503);
            }
        });

        $exceptions->render(function (RequestException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Upstream service is unavailable. Please try again shortly.',
                ], 502);
            }
        });
    })->create();
