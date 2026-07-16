<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response|JsonResponse
    {
        if ($request->user()?->role?->name !== $role) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Bạn không có quyền truy cập tài nguyên này.',
            ], 403);
        }

        return $next($request);
    }
}
