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

        if ($user->role !== 'admin' || strtolower($user->position) !== 'it') {
            if ($user->role === 'admin' && strtolower($user->position) === 'administrasi') {
                return redirect()->route('administrasi-umum.dashboard')
                    ->with('error', 'Anda tidak memiliki akses ke area admin. Silakan gunakan dashboard Administrasi Umum.');
            }
            
            return redirect()->route('user.dashboard')
                ->with('error', 'Unauthorized access. You must be an admin with IT position to access this area.');
        }

        return $next($request);
    }
} 