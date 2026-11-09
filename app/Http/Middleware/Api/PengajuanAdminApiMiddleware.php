<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang untuk endpoint pengajuan yang UNIFIED (lintas jenis).
 *
 * Prefix lama tetap memakai gerbang spesifik (dashboard.it untuk
 * /api/admin/tickets, dashboard.ipsrs untuk
 * /api/administrasi-umum/order-perbaikan). Endpoint baru
 * /api/admin/pengajuan harus bisa dipakai kedua admin, karena pemisahan
 * datanya sudah ditangani PengajuanScopeService (role_kategori).
 */
class PengajuanAdminApiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        if (! $user->hasPermission('dashboard.it') && ! $user->hasPermission('dashboard.ipsrs')) {
            return response()->json(['success' => false, 'message' => 'Forbidden - Admin only'], 403);
        }

        return $next($request);
    }
}
