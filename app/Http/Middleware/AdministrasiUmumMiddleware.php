<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdministrasiUmumMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($user->role !== 'admin' || strtolower($user->position) !== 'administrasi') {
            return redirect()->route('login')
                ->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
} 