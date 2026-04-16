<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureContentManager
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Authentication required.');
        }

        if ($user->isSuperAdmin() || $user->hasPermission('content.manage')) {
            return $next($request);
        }

        abort(403, 'You are not allowed to manage content.');
    }
}
