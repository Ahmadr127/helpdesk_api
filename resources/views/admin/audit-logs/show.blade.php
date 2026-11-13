@extends('admin.layouts.app')

@section('title', 'Detail Audit Log')

@section('content')
<div class="w-full">
    <div class="w-full">
        <div class="flex justify-between items-center mb-6">
            <a href="{{ route('admin.audit-logs.index') }}"
                class="inline-flex items-center px-4 py-2 bg-slate-500 text-white rounded-lg shadow hover:bg-slate-600 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m7-7h18"></path>
                </svg>
                Back
            </a>
        </div>

        <div class="mb-6 rounded-xl shadow-md p-5 bg-white border border-gray-100 overflow-hidden">
            <div class="card-header -mx-5 -mt-5 px-5 py-4 mb-5 bg-slate-50 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">Detail Audit Log</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">Event</p>
                    <p class="text-sm font-medium text-gray-800">{{ $log->event }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">Model</p>
                    <p class="text-sm font-medium text-gray-800">{{ $log->auditable_type }} #{{ $log->auditable_id }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">User</p>
                    <p class="text-sm font-medium text-gray-800">{{ $log->user?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">Waktu</p>
                    <p class="text-sm font-medium text-gray-800">{{ $log->created_at->format('d M Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">IP Address</p>
                    <p class="text-sm font-medium text-gray-800">{{ $log->ip_address }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">User Agent</p>
                    <p class="text-sm font-medium text-gray-800">{{ $log->user_agent }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">URL</p>
                    <p class="text-sm font-medium text-gray-800 break-all">{{ $log->url }}</p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm font-semibold text-gray-800 mb-2">Old Values</p>
                    <pre class="text-xs bg-slate-50 border border-gray-200 rounded-lg p-4 overflow-auto max-h-96">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}' }}</pre>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800 mb-2">New Values</p>
                    <pre class="text-xs bg-slate-50 border border-gray-200 rounded-lg p-4 overflow-auto max-h-96">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}' }}</pre>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('admin.audit-logs.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-slate-500 text-white rounded-lg shadow hover:bg-slate-600 transition-all text-sm">
                    Back to List
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
