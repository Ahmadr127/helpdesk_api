@extends('admin.layouts.app')

@section('title', 'Reports')

@section('content')
<div class="space-y-6">
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm mb-2">Total Reports</h3>
            <p class="text-2xl font-semibold">{{ $totalReports }}</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm mb-2">Bug Reports</h3>
            <p class="text-2xl font-semibold">{{ $bugReports }}</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm mb-2">Feature Requests</h3>
            <p class="text-2xl font-semibold">{{ $featureRequests }}</p>
        </div>
    </div>

    <!-- Report Filters -->
    <div class="bg-white p-6 rounded-lg shadow">
        <form action="{{ route('admin.reports.index') }}" method="GET" class="flex gap-4 mb-4">
            <select name="type" id="type" class="border rounded-md px-3 py-2">
                <option value="all" {{ request('type') === 'all' || !request('type') ? 'selected' : '' }}>All Types</option>
                <option value="bug" {{ request('type') === 'bug' ? 'selected' : '' }}>Bug Reports</option>
                <option value="feature" {{ request('type') === 'feature' ? 'selected' : '' }}>Feature Requests</option>
                <option value="other" {{ request('type') === 'other' ? 'selected' : '' }}>Other Issues</option>
            </select>

            <select name="period" id="period" class="border rounded-md px-3 py-2">
                <option value="all" {{ request('period') === 'all' || !request('period') ? 'selected' : '' }}>All Time</option>
                <option value="7" {{ request('period') === '7' ? 'selected' : '' }}>Last 7 days</option>
                <option value="30" {{ request('period') === '30' ? 'selected' : '' }}>Last 30 days</option>
                <option value="90" {{ request('period') === '90' ? 'selected' : '' }}>Last 90 days</option>
                <option value="custom" {{ request('period') === 'custom' ? 'selected' : '' }}>Custom Range</option>
            </select>

            <div id="customDateRange" class="hidden flex gap-4">
                <input type="date" name="start_date" class="border rounded-md px-3 py-2" value="{{ request('start_date') }}">
                <input type="date" name="end_date" class="border rounded-md px-3 py-2" value="{{ request('end_date') }}">
            </div>

            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                Filter
            </button>
        </form>
    </div>

    <!-- Report Tables -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4">User Reports</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($reports as $report)
                    <tr>
                        <td class="px-6 py-4">{{ $report->user->name }}</td>
                        <td class="px-6 py-4">
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $report->type === 'bug' ? 'bg-red-100 text-red-800' : 
                                   ($report->type === 'feature' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                                {{ ucfirst($report->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">{{ Str::limit($report->description, 50) }}</td>
                        <td class="px-6 py-4">{{ $report->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-4">
                            @if($report->screenshot)
                            <a href="{{ route('admin.reports.view-screenshot', $report) }}" target="_blank"
                                class="inline-flex items-center text-blue-600 hover:text-blue-900 mr-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"
                                        clip-rule="evenodd" />
                                </svg>
                                View Screenshot
                            </a>
                            @endif
                            <form action="{{ route('admin.reports.destroy', $report) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $reports->links() }}
        </div>
    </div>
</div>

<script>
document.getElementById('period').addEventListener('change', function() {
    const customDateRange = document.getElementById('customDateRange');
    if (this.value === 'custom') {
        customDateRange.classList.remove('hidden');
    } else {
        customDateRange.classList.add('hidden');
    }
});

// Show custom date range if period is custom on page load
if (document.getElementById('period').value === 'custom') {
    document.getElementById('customDateRange').classList.remove('hidden');
}
</script>
@endsection