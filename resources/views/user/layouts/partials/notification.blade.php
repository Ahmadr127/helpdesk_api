<div x-data="{ open: false }" class="relative">
    <!-- Notification Button -->
    <button @click="open = !open"
        class="relative p-2 bg-white rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        <span class="sr-only">View notifications</span>
        <div class="flex items-center">
            <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                </path>
            </svg>
            @if(auth()->user()->unreadNotifications->count() > 0)
            <span class="absolute top-0 right-0 block h-5 w-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center">
                {{ auth()->user()->unreadNotifications->count() }}
            </span>
            @endif
        </div>
    </button>

    <!-- Notification Panel -->
    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-2 w-96 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
        <div class="py-1" role="menu">
            @forelse(auth()->user()->notifications()->take(5)->get() as $notification)
            <a href="{{ $notification->data['url'] }}"
                class="block px-4 py-3 hover:bg-gray-100 {{ $notification->read_at ? 'opacity-75' : '' }}"
                onclick="event.preventDefault(); markAsRead('{{ $notification->id }}', '{{ $notification->data['url'] }}')">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-2 h-2 rounded-full {{ $notification->read_at ? 'bg-gray-400' : 'bg-blue-500' }}"></div>
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="flex justify-between">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $notification->data['title'] }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ $notification->data['message'] }}
                        </p>
                        <div class="mt-2 flex items-center space-x-4 text-xs">
                            <div>
                                <span class="text-gray-500">Ticket #:</span>
                                <span class="font-medium">{{ $notification->data['ticket_number'] }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Status:</span>
                                <span class="px-2 py-1 rounded-full text-xs 
                                    {{ $notification->data['ticket_status'] === 'open' ? 'bg-blue-100 text-blue-800' : 
                                       ($notification->data['ticket_status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 
                                       ($notification->data['ticket_status'] === 'closed' ? 'bg-gray-100 text-gray-800' : 
                                       'bg-green-100 text-green-800')) }}">
                                    {{ ucfirst($notification->data['ticket_status']) }}
                                </span>
                            </div>
                        </div>
                        @if(isset($notification->data['responder_name']))
                        <div class="mt-2 text-xs text-gray-500">
                            By: {{ $notification->data['responder_name'] }} ({{ $notification->data['responder_role'] }})
                        </div>
                        @endif
                    </div>
                </div>
            </a>
            @empty
            <div class="px-4 py-3 text-sm text-gray-500">
                No notifications
            </div>
            @endforelse

            @if(auth()->user()->notifications->count() > 5)
            <div class="border-t border-gray-100">
                <a href="{{ route('user.notifications.index') }}"
                    class="block px-4 py-2 text-sm text-gray-500 hover:bg-gray-100">
                    View all notifications
                </a>
            </div>
            @endif
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
</script>
@endpush 