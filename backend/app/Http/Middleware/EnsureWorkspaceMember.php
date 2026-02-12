<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Workspace\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureWorkspaceMember
{
    /**
     * Handle an incoming request.
     *
     * Ensures the authenticated user is a member of the workspace
     * specified in the route parameter.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $workspace = $request->route('workspace');

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Resolve workspace from route parameter if it's a string (UUID)
        if (is_string($workspace)) {
            $workspace = Workspace::find($workspace);
        }

        if (! $workspace instanceof Workspace) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace not found.',
            ], 404);
        }

        // Ensure workspace belongs to user's tenant
        // Return 404 for cross-tenant access to not reveal workspace existence
        if ($workspace->tenant_id !== $user->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace not found.',
            ], 404);
        }

        // Check membership
        if (! $workspace->hasMember($user->id)) {
            // Allow tenant owners and admins to access any workspace in their tenant
            if (! $user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. Not a workspace member.',
                ], 403);
            }
        }

        return $next($request);
    }
}
