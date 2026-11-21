<?php

use App\Http\Auth\HomeRoute;
use App\Http\Middleware\EnsureRole;
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
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();

            if ($user === null) {
                return '/login';
            }

            return route(app(HomeRoute::class)->nameFor((string) $user->role));
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (DomainException $exception, Request $request) {
            if (! $request->isMethod('POST')) {
                return null;
            }

            $message = $exception->getMessage();

            if ($message === '') {
                $message = 'No se pudo completar la acción.';
            }

            return back()->withInput()->withErrors(['action' => $message]);
        });
    })->create();
