<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdministrasiUmumApiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success'=>false,'message'=>'Unauthenticated'], 401);
        }
        if ($user->role !== 'admin' || strtolower($user->position) !== 'administrasi') {
            return response()->json(['success'=>false,'message'=>'Forbidden - Administrasi Umum only'], 403);
        }
        return $next($request);
    }
}
