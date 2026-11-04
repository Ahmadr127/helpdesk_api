<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->latest();

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }
        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('admin.audit-log.index', compact('logs'));
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');

        return view('admin.audit-log.show', compact('auditLog'));
    }
}
