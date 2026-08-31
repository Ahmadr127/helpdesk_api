@extends('user.layouts.app')

@section('title', 'My Notifications')

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
<!-- Include the notification partial -->
@include('user.partials.notification')

<div class="container mx-auto px-4 py-8 max-w-8xl">
    <!-- Filter Section -->
    <div class="mb-8 rounded-xl shadow-sm p-5 bg-white border border-gray-100 overflow-hidden">
        <div class="card-header -mx-5 -mt-5 px-5 py-4 mb-5 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Notifikasi Saya</h2>
            <form action="{{ route('user.notifications.mark-all-as-read') }}" method="POST">
                @csrf
                <button type="submit"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors shadow-sm flex items-center text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Tandai Semua Sudah Dibaca
                </button>
            </form>
        </div>
        <form action="{{ route('user.notifications.index') }}" method="GET" class="flex flex-wrap gap-4">
            <!-- Status Filter -->
            <div class="w-full md:w-auto">
                <label for="filter" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <div class="relative">
                    <select name="filter" id="filter"
                        class="w-full md:w-48 px-4 py-2.5 border border-gray-200 rounded-lg appearance-none pr-10 bg-white focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-colors">
                        <option value="all" {{ request('filter') == 'all' ? 'selected' : '' }}>Semua Notifikasi</option>
                        <option value="read" {{ request('filter') == 'read' ? 'selected' : '' }}>Sudah Dibaca</option>
                        <option value="unread" {{ request('filter') == 'unread' ? 'selected' : '' }}>Belum Dibaca
                        </option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Date Range Filter -->
            <div class="w-full md:w-auto">
                <label for="date_range" class="block text-sm font-medium text-gray-700 mb-2">Rentang Waktu</label>
                <div class="relative">
                    <select name="date_range" id="date_range"
                        class="w-full md:w-48 px-4 py-2.5 border border-gray-200 rounded-lg appearance-none pr-10 bg-white focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-colors">
                        <option value="">Semua Waktu</option>
                        <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="week" {{ request('date_range') == 'week' ? 'selected' : '' }}>7 Hari Terakhir
                        </option>
                        <option value="month" {{ request('date_range') == 'month' ? 'selected' : '' }}>30 Hari Terakhir
                        </option>
                        <option value="custom" {{ request('date_range') == 'custom' ? 'selected' : '' }}>Rentang Kustom
                        </option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Custom Date Range -->
            <div id="custom_dates" class="w-full md:flex md:gap-4"
                style="{{ request('date_range') == 'custom' ? '' : 'display: none;' }}">
                <div class="w-full md:w-auto mb-4 md:mb-0">
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}"
                        class="w-full md:w-48 px-4 py-2.5 border border-gray-200 rounded-lg bg-white focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-colors">
                </div>
                <div class="w-full md:w-auto">
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Akhir</label>
                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}"
                        class="w-full md:w-48 px-4 py-2.5 border border-gray-200 rounded-lg bg-white focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-colors">
                </div>
            </div>

            <div class="w-full md:w-auto flex items-end">
                <button type="submit"
                    class="bg-green-600 text-white px-5 py-2.5 rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                    Terapkan Filter
                </button>
                @if(request('filter') || request('date_range') || request('start_date') || request('end_date'))
                <a href="{{ route('user.notifications.index') }}"
                    class="ml-3 bg-gray-100 text-gray-700 px-5 py-2.5 rounded-lg hover:bg-gray-200 transition-colors">
                    Bersihkan Filter
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Notifications List Section -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-12">
        <!-- Notifications List -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                <div class="card-header px-6 py-4">
                    <h2 class="text-base font-semibold text-gray-800">Daftar Notifikasi</h2>
                </div>

                <!-- Notifications Content -->
                <div class="overflow-y-auto" style="max-height: 65vh; min-height: 400px;">
                    <div class="divide-y divide-gray-200">
                        @forelse($notifications as $notification)
                        <div
                            class="p-5 {{ $notification->read_at ? 'bg-gray-50' : 'bg-white' }} hover:bg-gray-50 transition-all">
                            <a href="{{ route('user.ticket.show', ['ticket' => $notification->data['ticket_id']]) }}"
                                class="block"
                                onclick="event.preventDefault(); markAsRead('{{ $notification->id }}', '{{ route('user.ticket.show', ['ticket' => $notification->data['ticket_id']]) }}')">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 mt-1">
                                        @if(!$notification->read_at)
                                        <div
                                            class="w-3 h-3 rounded-full bg-gradient-to-r from-blue-400 to-blue-500 shadow-sm">
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
                                                <span class="text-gray-500">Tiket #:</span>
                                                <span
                                                    class="font-medium">{{ $notification->data['ticket_number'] }}</span>
                                            </div>
                                            <div>
                                                <span class="px-2.5 py-1 text-xs leading-5 font-semibold rounded-full 
                                                    {{ $notification->data['ticket_status'] === 'open' ? 'bg-blue-100 text-blue-800' : 
                                                       ($notification->data['ticket_status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 
                                                       ($notification->data['ticket_status'] === 'closed' ? 'bg-gray-100 text-gray-800' : 
                                                       'bg-green-100 text-green-800')) }}">
                                                    {{ ucfirst(str_replace('_', ' ', $notification->data['ticket_status'])) }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="px-2.5 py-1 text-xs rounded-full 
                                                    {{ $notification->data['ticket_priority'] === 'low' ? 'bg-gray-100 text-gray-800' : 
                                                       ($notification->data['ticket_priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 
                                                       'bg-red-100 text-red-800') }}">
                                                    Prioritas: {{ ucfirst($notification->data['ticket_priority']) }}
                                                </span>
                                            </div>
                                        </div>
                                        @if(isset($notification->data['responder_name']))
                                        <div class="mt-2 text-xs text-gray-500">
                                            <span class="bg-gray-100 px-3 py-1 rounded-full">
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
                                class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                            </div>
                            <p class="text-gray-500 text-lg">Tidak ditemukan notifikasi</p>
                            <p class="text-gray-400 mt-1">Anda akan menerima notifikasi saat ada pembaruan pada tiket
                                Anda</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Pagination Section -->
                @if($notifications->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">
                            Menampilkan {{ $notifications->firstItem() ?? 0 }}-{{ $notifications->lastItem() ?? 0 }}
                            dari
                            {{ $notifications->total() }} notifikasi
                        </div>
                        <div class="flex items-center gap-4">
                            <form action="{{ route('user.notifications.index') }}" method="GET"
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

                                <label for="per_page" class="text-sm text-gray-600">Tampilkan:</label>
                                <select id="per_page" name="per_page"
                                    class="text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200"
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

            @if($notifications->hasPages())
            <div class="text-center text-sm text-gray-500 mt-4">
                <p>Menampilkan halaman {{ $notifications->currentPage() }} dari {{ $notifications->lastPage() }} halaman
                </p>
                <p class="mt-1">Anda dapat mengatur jumlah notifikasi yang ditampilkan per halaman menggunakan dropdown
                    di atas</p>
            </div>
            @endif
        </div>

        <!-- Settings Panel -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                <div class="card-header px-6 py-4">
                    <h2 class="text-base font-semibold text-gray-800">Pengaturan Notifikasi</h2>
                </div>
                <div class="p-6">
                    <form action="{{ route('user.notifications.settings.update') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Hapus otomatis notifikasi yang sudah dibaca setelah
                            </label>
                            <div class="flex items-center space-x-2">
                                <input type="number" name="read_expiry_days"
                                    value="{{ $settings->read_expiry_days ?? 7 }}" min="1"
                                    class="w-20 rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="text-gray-600">hari</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Hapus otomatis notifikasi yang belum dibaca setelah
                            </label>
                            <div class="flex items-center space-x-2">
                                <input type="number" name="unread_expiry_days"
                                    value="{{ $settings->unread_expiry_days ?? 30 }}" min="1"
                                    class="w-20 rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="text-gray-600">hari</span>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="auto_delete_read" id="auto_delete_read"
                                {{ $settings->auto_delete_read ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-opacity-50">
                            <label for="auto_delete_read" class="ml-2 text-sm text-gray-700">
                                Aktifkan penghapusan otomatis untuk notifikasi yang sudah dibaca
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="auto_delete_unread" id="auto_delete_unread"
                                {{ $settings->auto_delete_unread ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-opacity-50">
                            <label for="auto_delete_unread" class="ml-2 text-sm text-gray-700">
                                Aktifkan penghapusan otomatis untuk notifikasi yang belum dibaca
                            </label>
                        </div>

                        <div class="pt-3">
                            <button type="submit"
                                class="w-full bg-green-600 text-white px-4 py-2.5 rounded-lg hover:bg-green-700 transition-colors">
                                Simpan Pengaturan
                            </button>
                        </div>
                    </form>

                    <!-- Manual Deletion Section -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-base font-medium text-gray-800 mb-4">Pembersihan Manual</h3>
                        <form action="{{ route('user.notifications.delete-old') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Hapus notifikasi yang lebih tua dari
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="number" name="days" value="7" min="1"
                                        class="w-20 rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="text-gray-600">hari</span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipe</label>
                                <div class="relative">
                                    <select name="type"
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-lg appearance-none pr-10 bg-white focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-colors">
                                        <option value="read">Notifikasi yang sudah dibaca</option>
                                        <option value="unread">Notifikasi yang belum dibaca</option>
                                        <option value="all">Semua notifikasi</option>
                                    </select>
                                    <div
                                        class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7">
                                            </path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <button type="submit"
                                class="w-full bg-red-600 text-white px-4 py-2.5 rounded-lg hover:bg-red-700 transition-colors mt-1">
                                Hapus Notifikasi Lama
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function markAsRead(notificationId, url) {
    fetch(`/user/notifications/${notificationId}/mark-as-read`, {
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