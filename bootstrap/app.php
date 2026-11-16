<?php

use App\Exceptions\SupabaseUnavailable;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\PrivateResponse;
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
        $middleware->web(append: [PrivateResponse::class]);
        $middleware->alias(['role' => EnsureRole::class]);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['password', 'password_confirmation', 'token_hash']);
        $exceptions->render(function (SupabaseUnavailable $exception, Request $request) {
            return $request->expectsJson()
                ? response()->json(['message' => $exception->getMessage()], 503, ['Cache-Control' => 'no-store, private'])
                : response()->view('errors.service-unavailable', [], 503, ['Cache-Control' => 'no-store, private']);
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
