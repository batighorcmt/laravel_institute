<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->statefulApi();
        // Ensure timezone is always set correctly for live server
        $middleware->append(\App\Http\Middleware\SetTimezone::class);
        $middleware->web(append: [
            \App\Http\Middleware\IdentifySchool::class,
            \App\Http\Middleware\EnsurePdfDownload::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/v1/billing/payment/ssl/*',
            'billing/payment/ssl/*',
            'admission/payment/ssl/*',
        ]);
        // Route middleware aliases
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            // Backward-compatible alias seen in older deployments/routes.
            // Prevents "Target class [auth.role] does not exist" when route cache or old code references it.
            'auth.role' => \App\Http\Middleware\RoleMiddleware::class,
            'strict_role' => \App\Http\Middleware\StrictRoleMiddleware::class,
            'active_school' => \App\Http\Middleware\EnsureSchoolIsActive::class,
            'module' => \App\Http\Middleware\CheckModuleAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                // Ignore standard HTTP exceptions (Validation, Authentication, etc.) to maintain default Laravel behavior
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ||
                    $e instanceof \Illuminate\Validation\ValidationException ||
                    $e instanceof \Illuminate\Auth\Access\AuthorizationException ||
                    $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return null;
                }

                // Return uniform error message for general server errors (500)
                return response()->json([
                    'message' => 'সার্ভারে একটি ত্রুটি হয়েছে',
                    'error_debug' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }
        });
    })->create();
