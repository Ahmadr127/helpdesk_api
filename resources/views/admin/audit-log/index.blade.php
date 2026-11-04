@extends('admin.layouts.app')

@section('title', 'Audit Log')

@section('content')
<div class="w-full">
    <div class="bg-white rounded-lg shadow-md p-4 mb-6 border border-gray-100">
        <form method="GET" action="{{ route('admin.audit-log.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Event</label>
                <select name="event" class="w-full rounded-md border-gray-300">
                    <option value="">Semua</option>
                    @foreach(['created','updated','deleted','restored'] as $e)
                        <option value="{{ $e }}" {{ request('event') === $e ? 'selected' : '' }}>{{ $e }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                <input type="text" name="auditable_type" value="{{ request('auditable_type') }}" class="w-full rounded-md border-gray-300" placeholder="App\Models\...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dari</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full rounded-md border-gray-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sampai</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full rounded-md border-gray-300">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">Filter</button>
                <a href="{{ route('admin.audit-log.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Model</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detail</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perubahan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($logs as $log)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-xs text-gray-600">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-3 text-xs">{{ $log->user?->name ?? 'System' }}</td>
                            <td class="px-4 py-3 text-xs">
                                <span class="px-2 py-1 rounded-full text-xs {{ $log->event === 'created' ? 'bg-green-100 text-green-800' : ($log->event === 'updated' ? 'bg-blue-100 text-blue-800' : ($log->event === 'deleted' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                                    {{ $log->event }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs">{{ class_basename($log->auditable_type) }}</td>
                            <td class="px-4 py-3 text-xs">{{ $log->auditable_id }}</td>
                            <td class="px-4 py-3 text-xs">
                                <a href="{{ route('admin.audit-log.show', $log) }}" class="text-blue-600 hover:underline">Detail</a>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if($log->old_values)
                                    <div class="text-red-600">- {{ json_encode($log->old_values) }}</div>
                                @endif
                                @if($log->new_values)
                                    <div class="text-green-600">+ {{ json_encode($log->new_values) }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection