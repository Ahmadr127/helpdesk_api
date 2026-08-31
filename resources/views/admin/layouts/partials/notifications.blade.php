<div x-data="{ open: false }" class="relative" @keydown.escape.stop="open = false" @click.away="open = false">
    <!-- Notification Button -->
    <button type="button" @click.stop="open = !open"
        class="relative p-2 bg-white rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
        <span class="sr-only">View notifications</span>
        <div class="flex items-center">
            <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                </path>
            </svg>
            @if(auth()->user()->unreadNotifications->count() > 0)
            <span
                class="absolute top-0 right-0 block h-5 w-5 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center">
                {{ auth()->user()->unreadNotifications->count() }}
            </span>
            @endif
        </div>
    </button>

    <!-- Notification Panel -->
    <div x-show="open" x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-2 w-96 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 max-h-[80vh] overflow-y-auto"
        style="display: none;">
        <div class="py-2" role="menu">
            <div class="px-4 py-2 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-900">Notifikasi Terbaru</h3>
            </div>
            @forelse(auth()->user()->notifications()->latest()->take(5)->get() as $notification)
            <div class="block px-4 py-3 hover:bg-gray-50 transition duration-150 ease-in-out {{ $notification->read_at ? 'bg-gray-50' : 'bg-white' }}"
                x-data="{ markAndNavigate: function() { markAsRead('{{ $notification->id }}', '{{ route('admin.tickets.show', ['ticket' => $notification->data['ticket_id']]) }}') } }"
                @click="markAndNavigate" role="menuitem" style="cursor: pointer;">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 mt-1">
                        <div
                            class="w-2.5 h-2.5 rounded-full {{ $notification->read_at ? 'bg-gray-400' : 'bg-blue-600' }}">
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex justify-between items-start mb-1">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ $notification->data['title'] }}
                            </p>
                            <p class="text-xs text-gray-500 whitespace-nowrap ml-2">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <p class="text-sm text-gray-600 line-clamp-2 mb-2">
                            {{ $notification->data['message'] }}
                        </p>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <div class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-800 rounded-md">
                                <span class="font-medium">Ticket #{{ $notification->data['ticket_number'] }}</span>
                            </div>
                            <div class="inline-flex items-center px-2 py-1 rounded-md
                                {{ $notification->data['ticket_status'] === 'open' ? 'bg-blue-100 text-blue-800' : 
                                   ($notification->data['ticket_status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 
                                   'bg-gray-100 text-gray-800') }}">
                                {{ ucfirst($notification->data['ticket_status']) }}
                            </div>
                            <div class="inline-flex items-center px-2 py-1 rounded-md
                                {{ $notification->data['ticket_priority'] === 'low' ? 'bg-gray-100 text-gray-800' : 
                                   ($notification->data['ticket_priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 
                                   'bg-red-100 text-red-800') }}">
                                {{ ucfirst($notification->data['ticket_priority']) }} Priority
                            </div>
                        </div>
                        <div class="mt-2 text-xs text-gray-500">
                            By: {{ $notification->data['responder_name'] }}
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="px-4 py-6 text-sm text-gray-500 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                    </path>
                </svg>
                <p class="mt-2">Tidak ada notifikasi</p>
            </div>
            @endforelse

            <div class="border-t border-gray-100 bg-gray-50">
                <a href="{{ route('admin.notifications.index') }}"
                    class="block px-4 py-3 text-sm text-gray-600 hover:bg-gray-100 transition duration-150 ease-in-out flex items-center justify-between">
                    <span class="font-medium">Lihat Semua Notifikasi</span>
                    @if(auth()->user()->unreadNotifications->count() > 0)
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                        {{ auth()->user()->unreadNotifications->count() }}
                    </span>
                    @endif
                </a>
            </div>
        </div>
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
    }).then(response => {
        if (response.ok) {
            window.location.href = url;
        }
    }).catch(error => {
        console.error('Error marking notification as read:', error);
    });
}
</script>
@endpush