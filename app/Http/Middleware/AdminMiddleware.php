<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (! $user->hasAnyPermission(['admin.dashboard', 'ipsrs.dashboard', 'ticket.manage', 'order.manage', 'master.view', 'master.manage', 'user.view', 'user.manage'])) {
            return redirect()->route('user.dashboard')
                ->with('error', 'Unauthorized access. You must be an admin to access this area.');
        }

        return $next($request);
    }
} 