<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Tenant\TenantStatus;
use App\Models\Tenant\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenant
{
    /**
     * Handle an incoming request.
     *
     * Resolves the tenant from the authenticated user and makes it
     * available on the request for downstream use.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'No tenant associated with your account.',
            ], 403);
        }

        $tenant = Tenant::find($user->tenant_id);

        if (! $tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found.',
            ], 404);
        }

        if ($tenant->status === TenantStatus::SUSPENDED) {
            return response()->json([
                'success' => false,
                'message' => 'Your organization has been suspended. Please contact support.',
            ], 403);
        }

        if ($tenant->status === TenantStatus::TERMINATED) {
            return response()->json([
                'success' => false,
                'message' => 'Your organization has been terminated.',
            ], 403);
        }

        // Make tenant available on the request and in the app container
        $request->attributes->set('tenant', $tenant);
        app()->instance('current_tenant', $tenant);

        return $next($request);
    }
}
