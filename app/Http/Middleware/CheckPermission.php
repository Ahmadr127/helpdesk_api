<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     * Usage: ->middleware('permission:ticket.create') or permission:ticket.create|order.create (OR logic) or permission:ticket.create,order.create for AND
     */
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (!$permission) {
            return $next($request);
        }

        $user = auth()->user();

        // Support pipe for OR: permission:ticket.create|order.create
        // Support comma for AND: permission:ticket.create,order.create
        if (str_contains($permission, '|')) {
            $slugs = explode('|', $permission);
            foreach ($slugs as $slug) {
                if ($user->hasPermission(trim($slug))) {
                    return $next($request);
                }
            }
            return $this->deny($request, $permission);
        }

        if (str_contains($permission, ',')) {
            $slugs = explode(',', $permission);
            foreach ($slugs as $slug) {
                if (!$user->hasPermission(trim($slug))) {
                    return $this->deny($request, $permission);
                }
            }
            return $next($request);
        }

        if (!$user->hasPermission(trim($permission))) {
            return $this->deny($request, $permission);
        }

        return $next($request);
    }

    protected function deny(Request $request, string $permission)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: missing permission ' . $permission,
            ], 403);
        }
        abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini. Required: ' . $permission);
    }
}
