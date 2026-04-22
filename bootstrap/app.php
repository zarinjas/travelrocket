<?php

use App\Http\Middleware\EnsureTenantWorkspaceAccess;
use App\Http\Middleware\EnsureContentManager;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/public.php',
            __DIR__.'/../routes/app.php',
            __DIR__.'/../routes/admin.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.workspace' => EnsureTenantWorkspaceAccess::class,
            'super.admin' => EnsureSuperAdmin::class,
            'content.manage' => EnsureContentManager::class,
        ]);

        $middleware->redirectUsersTo(function (Request $request): string {
            $user = $request->user();
            $user?->loadMissing('tenant');

            if ($user?->isSuperAdmin()) {
                return route('admin.content.index');
            }

            if ($user?->tenant) {
                return route('dashboard', ['tenant' => $user->tenant]);
            }

            return route('landing');
        });

        $middleware->appendToGroup('web', HandleInertiaRequests::class);
        $middleware->appendToGroup('web', \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class);
        $middleware->validateCsrfTokens(except: [
            'webhooks/payments/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
