@extends('admin.layouts.app')

@section('title', 'Detail Audit Log')

@section('content')
<div class="w-full max-w-5xl mx-auto">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Detail Audit Log #{{ $auditLog->id }}</h2>
        <a href="{{ route('admin.audit-log.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200">Kembali</a>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-100 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Waktu</p>
                <p class="text-sm text-gray-800">{{ $auditLog->created_at?->format('d/m/Y H:i:s') }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">User</p>
                <p class="text-sm text-gray-800">{{ $auditLog->user?->name ?? 'System' }} @if($auditLog->user) (ID: {{ $auditLog->user->id }}) @endif</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Event</p>
                <p class="text-sm text-gray-800">{{ $auditLog->event }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Model</p>
                <p class="text-sm text-gray-800">{{ $auditLog->auditable_type }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Record ID</p>
                <p class="text-sm text-gray-800">{{ $auditLog->auditable_id }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">IP Address</p>
                <p class="text-sm text-gray-800">{{ $auditLog->ip_address ?? '-' }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-xs font-medium text-gray-500 uppercase">URL</p>
                <p class="text-sm text-gray-800 break-all">{{ $auditLog->url ?? '-' }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-xs font-medium text-gray-500 uppercase">User Agent</p>
                <p class="text-sm text-gray-800 break-all">{{ $auditLog->user_agent ?? '-' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase mb-1">Old Values</p>
                <pre class="bg-red-50 border border-red-200 rounded p-3 text-xs overflow-auto max-h-96">{{ $auditLog->old_values ? json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '-' }}</pre>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase mb-1">New Values</p>
                <pre class="bg-green-50 border border-green-200 rounded p-3 text-xs overflow-auto max-h-96">{{ $auditLog->new_values ? json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '-' }}</pre>
            </div>
        </div>
    </div>
</div>
@endsection