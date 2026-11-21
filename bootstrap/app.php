<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
<<<<<<< HEAD
        $middleware->alias(['role' => EnsureUserRole::class, 'auth.token' => \App\Http\Middleware\AuthenticateApiToken::class]);
=======
        $middleware->alias(['role' => EnsureUserRole::class]);
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
        $middleware->web(append: [SetLocale::class]);
        // Redirect unauthenticated users to the login page
        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
<<<<<<< HEAD
        // JSON errors for the mobile API
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());
=======
        //
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
    })->create();
