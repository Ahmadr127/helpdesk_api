@extends('admin.layouts.app')

@section('title', 'Admin Notifications')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
/* Custom pagination styling */
.pagination-container nav {
    display: flex;
    justify-content: center;
}

.pagination-container nav .flex.justify-between {
    display: none;
    /* Hide default text */
}

.pagination-container nav .relative.inline-flex {
    position: relative;
    display: inline-flex;
    margin: 0 2px;
}

.pagination-container nav .relative.inline-flex button,
.pagination-container nav .relative.inline-flex a {
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.875rem;
    line-height: 1.25rem;
    transition: all 0.2s;
}

.pagination-container nav .relative.inline-flex button:hover,
.pagination-container nav .relative.inline-flex a:hover {
    background-color: rgba(59, 130, 246, 0.1);
}

.pagination-container nav .relative.inline-flex [aria-current="page"] {
    background-color: #3b82f6;
    color: white;
    font-weight: 600;
}

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
<div class="container mx-auto px-4 py-8">
    <div class="max-w-9xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <a href="{{ route('admin.tickets.index') }}"
                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-lg shadow hover:from-gray-600 hover:to-gray-700 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back
            </a>
        </div>

        <!-- Filter Section -->
        <div class="mb-6 rounded-xl shadow-md p-5 bg-white border border-gray-100 overflow-hidden">
            <div class="card-header -mx-5 -mt-5 px-5 py-4 mb-5 bg-gradient-to-r from-green-50 to-blue-50 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">Notification Filters</h2>
                <form action="{{ route('admin.notifications.mark-all-as-read') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-500 to-blue-500 text-white rounded-lg shadow hover:from-green-600 hover:to-blue-600 transition-all duration-200 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Mark All as Read
                    </button>
                </form>
            </div>

            <form action="{{ route('admin.notifications.index') }}" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="filter"
                            class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring focus:ring-green-200">
                            <option value="all" {{ request('filter') == 'all' ? 'selected' : '' }}>All
                            </option>
                            <option value="read" {{ request('filter') == 'read' ? 'selected' : '' }}>Read
                            </option>
                            <option value="unread" {{ request('filter') == 'unread' ? 'selected' : '' }}>
                                Unread</option>
                        </select>
                    </div>

                    <!-- Date Range Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                        <select name="date_range" id="date_range"
                            class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring focus:ring-green-200">
                            <option value="">All Time</option>
                            <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>
                                Today</option>
                            <option value="week" {{ request('date_range') == 'week' ? 'selected' : '' }}>
                                Last 7 Days</option>
                            <option value="month" {{ request('date_range') == 'month' ? 'selected' : '' }}>
                                Last 30 Days</option>
                            <option value="custom"
                                {{ request('date_range') == 'custom' ? 'selected' : '' }}>Custom Range
                            </option>
                        </select>
                    </div>

                    <!-- Custom Date Range -->
                    <div id="custom_dates" class="grid grid-cols-2 gap-2"
                        style="{{ request('date_range') == 'custom' ? '' : 'display: none;' }}">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" name="start_date" value="{{ request('start_date') }}"
                                class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring focus:ring-green-200">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" name="end_date" value="{{ request('end_date') }}"
                                class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring focus:ring-green-200">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="bg-gradient-to-r from-green-500 to-blue-500 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-blue-600 transition-colors shadow-sm">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
            <!-- Notifications List -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 flex flex-col">
                    <div class="bg-gradient-to-r from-green-50 to-blue-50 p-6 border-b border-gray-200">
                        <h1 class="text-xl font-bold text-gray-800">All Notifications</h1>
                        <p class="text-gray-600">View and manage your notifications</p>
                    </div>

                    <!-- Notifications Content with Fixed Height -->
                    <div class="overflow-y-auto" style="height: 65vh; min-height: 500px;">
                        <div class="divide-y divide-gray-200">
                            @forelse($notifications as $notification)
                            <div
                                class="p-5 {{ $notification->read_at ? 'bg-gray-50' : 'bg-white' }} hover:bg-gradient-to-r hover:from-gray-50 hover:to-blue-50 transition-all">
                                <a href="{{ route('admin.tickets.show', ['ticket' => $notification->data['ticket_id']]) }}"
                                    class="block"
                                    onclick="event.preventDefault(); markAsRead('{{ $notification->id }}', '{{ route('admin.tickets.show', ['ticket' => $notification->data['ticket_id']]) }}')">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 mt-1">
                                            @if(!$notification->read_at)
                                            <div
                                                class="w-3 h-3 rounded-full bg-gradient-to-r from-green-400 to-blue-500 shadow-sm">
                                            </div>
                                            @else
                                            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
                                            @endif
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center">
                                                <h4 class="text-base font-medium text-gray-900">
                                                    {{ $notification->data['title'] }}
                                                </h4>
                                                <p class="text-xs text-gray-500 mt-1 sm:mt-0">
                                                    {{ $notification->created_at->format('d M Y H:i') }}
                                                    ({{ $notification->created_at->diffForHumans() }})
                                                </p>
                                            </div>
                                            <p class="mt-2 text-sm text-gray-600">
                                                {{ $notification->data['message'] }}
                                            </p>
                                            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                                                <div class="bg-gray-100 px-3 py-1 rounded-full">
                                                    <span class="text-gray-500">Ticket #:</span>
                                                    <span
                                                        class="font-medium">{{ $notification->data['ticket_number'] }}</span>
                                                </div>
                                                <div>
                                                    <span class="px-3 py-1 rounded-full text-xs 
                                            {{ $notification->data['ticket_status'] === 'open' ? 'bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800' : 
                                               ($notification->data['ticket_status'] === 'in_progress' ? 'bg-gradient-to-r from-yellow-100 to-yellow-200 text-yellow-800' : 
                                               ($notification->data['ticket_status'] === 'closed' ? 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800' : 
                                               'bg-gradient-to-r from-green-100 to-green-200 text-green-800')) }}">
                                                        {{ ucfirst(str_replace('_', ' ', $notification->data['ticket_status'])) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="px-3 py-1 rounded-full text-xs 
                                            {{ $notification->data['ticket_priority'] === 'low' ? 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800' : 
                                               ($notification->data['ticket_priority'] === 'medium' ? 'bg-gradient-to-r from-yellow-100 to-yellow-200 text-yellow-800' : 
                                               'bg-gradient-to-r from-red-100 to-red-200 text-red-800') }}">
                                                        Priority: {{ ucfirst($notification->data['ticket_priority']) }}
                                                    </span>
                                                </div>
                                            </div>
                                            @if(isset($notification->data['responder_name']))
                                            <div class="mt-2 text-xs text-gray-500">
                                                <span
                                                    class="bg-gradient-to-r from-gray-100 to-blue-50 px-3 py-1 rounded-full">
                                                    Responder: {{ $notification->data['responder_name'] }}
                                                    ({{ ucfirst($notification->data['responder_role']) }})
                                                </span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            </div>
                            @empty
                            <div class="p-8 text-center">
                                <div
                                    class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-r from-green-50 to-blue-100 text-green-500 mb-4">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                </div>
                                <p class="text-gray-500 text-lg">No notifications found</p>
                                <p class="text-gray-400 mt-1">You'll receive notifications when there are updates on
                                    your tickets</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Pagination Section -->
                    @if($notifications->hasPages())
                    <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-blue-50 border-t border-gray-200 mt-auto">
                        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                            <div class="text-sm text-gray-600">
                                Showing {{ $notifications->firstItem() ?? 0 }}-{{ $notifications->lastItem() ?? 0 }} of
                                {{ $notifications->total() }} notifications
                            </div>
                            <div class="flex items-center gap-4">
                                <form action="{{ route('admin.notifications.index') }}" method="GET"
                                    class="flex items-center gap-2">
                                    <!-- Preserve existing filter parameters -->
                                    @if(request('filter'))
                                    <input type="hidden" name="filter" value="{{ request('filter') }}">
                                    @endif
                                    @if(request('date_range'))
                                    <input type="hidden" name="date_range" value="{{ request('date_range') }}">
                                    @endif
                                    @if(request('start_date'))
                                    <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                                    @endif
                                    @if(request('end_date'))
                                    <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                                    @endif

                                    <label for="per_page" class="text-sm text-gray-600">Show:</label>
                                    <select id="per_page" name="per_page"
                                        class="text-sm rounded-md border-gray-300 focus:border-green-500 focus:ring focus:ring-green-200"
                                        onchange="this.form.submit()">
                                        <option value="8" {{ $perPage == 8 ? 'selected' : '' }}>8</option>
                                        <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>15</option>
                                        <option value="30" {{ $perPage == 30 ? 'selected' : '' }}>30</option>
                                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                    </select>
                                </form>
                                <div class="pagination-container">
                                    {{ $notifications->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Settings Panel -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
                    <div class="bg-gradient-to-r from-green-50 to-blue-50 px-4 py-3 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800">Notification Settings</h2>
                    </div>
                    <div class="p-4">
                        <form action="{{ route('admin.notifications.settings.update') }}" method="POST"
                            class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Auto-delete read notifications after
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="number" name="read_expiry_days"
                                        value="{{ $settings->read_expiry_days ?? 7 }}" min="1"
                                        class="w-20 rounded-md border-gray-300 focus:border-green-500 focus:ring focus:ring-green-200">
                                    <span class="text-gray-600">days</span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Auto-delete unread notifications after
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="number" name="unread_expiry_days"
                                        value="{{ $settings->unread_expiry_days ?? 30 }}" min="1"
                                        class="w-20 rounded-md border-gray-300 focus:border-green-500 focus:ring focus:ring-green-200">
                                    <span class="text-gray-600">days</span>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="auto_delete_read" id="auto_delete_read"
                                    {{ $settings->auto_delete_read ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                <label for="auto_delete_read" class="ml-2 text-sm text-gray-700">
                                    Enable auto-delete for read notifications
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="auto_delete_unread" id="auto_delete_unread"
                                    {{ $settings->auto_delete_unread ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                <label for="auto_delete_unread" class="ml-2 text-sm text-gray-700">
                                    Enable auto-delete for unread notifications
                                </label>
                            </div>

                            <div class="pt-3">
                                <button type="submit"
                                    class="w-full bg-gradient-to-r from-green-500 to-blue-500 text-white px-4 py-2 rounded-md hover:from-green-600 hover:to-blue-600 transition-colors shadow-sm">
                                    Save Settings
                                </button>
                            </div>
                        </form>

                        <!-- Manual Deletion Section -->
                        <div class="mt-5 pt-4 border-t border-gray-200">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Manual Cleanup</h3>
                            <form action="{{ route('admin.notifications.delete-old') }}" method="POST"
                                class="space-y-3">
                                @csrf
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Delete notifications older than
                                    </label>
                                    <div class="flex items-center space-x-2">
                                        <input type="number" name="days" value="7" min="1"
                                            class="w-20 rounded-md border-gray-300 focus:border-green-500 focus:ring focus:ring-green-200">
                                        <span class="text-gray-600">days</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                    <select name="type"
                                        class="w-full rounded-md border-gray-300 focus:border-green-500 focus:ring focus:ring-green-200">
                                        <option value="read">Read notifications</option>
                                        <option value="unread">Unread notifications</option>
                                        <option value="all">All notifications</option>
                                    </select>
                                </div>

                                <button type="submit"
                                    class="w-full bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-md hover:from-red-600 hover:to-red-700 transition-colors mt-1 shadow-sm">
                                    Delete Old Notifications
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination Information -->
        @if($notifications->hasPages())
        <div class="text-center text-sm text-gray-500 mt-4">
            <p>Showing page {{ $notifications->currentPage() }} of {{ $notifications->lastPage() }} pages</p>
            <p class="mt-1">You can adjust the number of notifications shown per page using the dropdown above</p>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function markAsRead(notificationId, url) {
    fetch(`/admin/notifications/${notificationId}/mark-as-read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    }).then(() => {
        window.location.href = url;
    });
}

document.getElementById('date_range').addEventListener('change', function() {
    const customDates = document.getElementById('custom_dates');
    if (this.value === 'custom') {
        customDates.style.display = '';
    } else {
        customDates.style.display = 'none';
    }
});

// Add smooth scrolling behavior
document.addEventListener('DOMContentLoaded', function() {
    const notificationContainer = document.querySelector('.overflow-y-auto');
    if (notificationContainer) {
        notificationContainer.style.scrollBehavior = 'smooth';
    }
});
</script>
@endpush
@endsection