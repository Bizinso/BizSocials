<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\User\UserService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CheckPermission
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  The permission to check
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (! $this->userService->hasPermission($user, $permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to perform this action.',
            ], 403);
        }

        return $next($request);
    }
}
