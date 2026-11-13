@extends('admin.layouts.app')

@section('title', 'Audit Logs')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .overflow-y-auto::-webkit-scrollbar {
        width: 8px;
    }
    .overflow-y-auto::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    .overflow-y-auto::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
@endpush

@section('content')
<div class="w-full">
    <div class="w-full">
        {{-- <div class="flex justify-between items-center mb-6">
            <a href="{{ route('admin.tickets.index') }}"
                class="inline-flex items-center px-4 py-2 bg-slate-500 text-white rounded-lg shadow hover:bg-slate-600 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m7-7h18"></path>
                </svg>
                Back
            </a>
        </div> --}}

        <div class="mb-6 rounded-xl shadow-md p-5 bg-white border border-gray-100 overflow-hidden">
            <div class="card-header -mx-5 -mt-5 px-5 py-4 mb-5 bg-slate-50 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">Riwayat Audit Log</h2>
            </div>

            <form action="{{ route('admin.audit-logs.index') }}" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Event</label>
                        <select name="event" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                            <option value="">Semua</option>
                            @foreach($events as $ev)
                                <option value="{{ $ev }}" @selected(request('event') === $ev)>{{ ucfirst($ev) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">User ID</label>
                        <input type="number" name="user_id" value="{{ request('user_id') }}" placeholder="0 = semua"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Per Halaman</label>
                        <select name="per_page" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                            @foreach([10, 15, 30, 50, 100] as $pp)
                                <option value="{{ $pp }}" @selected((int) request('per_page', 15) === $pp)>{{ $pp }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 transition-all text-sm">
                            Filter
                        </button>
                        <a href="{{ route('admin.audit-logs.index') }}"
                            class="ml-2 inline-flex items-center px-4 py-2 bg-slate-500 text-white rounded-lg shadow hover:bg-slate-600 transition-all text-sm">
                            Reset
                        </a>
                    </div>
                </div>
            </form>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">Event</th>
                            <th class="px-4 py-3">Model</th>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">IP</th>
                            <th class="px-4 py-3">User Agent</th>
                            <th class="px-4 py-3">URL</th>
                            <th class="px-4 py-3">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($logs as $log)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $log->created_at->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-100 text-slate-800">{{ $log->event }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $log->auditable_type }} #{{ $log->auditable_id }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $log->user?->name ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $log->ip_address }}</td>
                                <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ Str::limit($log->user_agent ?? '-', 50) }}</td>
                                <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ $log->url }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <a href="{{ route('admin.audit-logs.show', $log) }}" class="text-blue-600 hover:text-blue-900">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-4 text-center text-sm text-gray-500">Tidak ada audit log ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-between items-center">
                <p class="text-sm text-gray-600">
                    Menampilkan
                    <span class="font-medium">{{ $logs->firstItem() ?? 0 }}</span> –
                    <span class="font-medium">{{ $logs->lastItem() ?? 0 }}</span>
                    dari <span class="font-medium">{{ $logs->total() }}</span> data
                </p>
                {{ $logs->onEachSide(1)->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
