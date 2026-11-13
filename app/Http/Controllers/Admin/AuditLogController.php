<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query();

        if ($request->filled('event')) {
            $query->where('event', $request->string('event')->toLowerCase());
        }
        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->string('auditable_type'));
        }
        if ($request->filled('auditable_id')) {
            $query->where('auditable_id', (int) $request->input('auditable_id'));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        $logs = $query->with('user')->orderBy('created_at', 'desc')->paginate((int) $request->input('per_page', 15));

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'events' => ['created', 'updated', 'deleted'],
        ]);
    }

    public function show($id)
    {
        $log = AuditLog::with('user')->findOrFail((int) $id);

        return view('admin.audit-logs.show', [
            'log' => $log,
            'events' => ['created', 'updated', 'deleted'],
        ]);
    }
}
