<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Testing Environment Middleware
 * 
 * Blocks access to testing endpoints in production environment.
 */
class EnsureTestingEnvironment
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            abort(403, 'Testing endpoints are not available in production');
        }

        return $next($request);
    }
}
