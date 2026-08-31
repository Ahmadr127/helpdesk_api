<nav class="fixed top-0 z-50 w-full py-1 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="px-3 py-3 lg:px-5 lg:pl-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center justify-start rtl:justify-end">
                <a href="{{ route('user.ticket.index') }}" class="flex items-center">
                    <img src="{{ asset('images/logoazra.png') }}" class="h-8 me-3 ml-3" alt="Azra Logo" />
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="{{ route('user.dashboard') }}"
                    class="flex items-center {{ request()->routeIs('user.dashboard') ? 'text-blue-600' : 'text-gray-900 hover:text-blue-600' }}">
                    <span class="ml-2">Beranda</span>
                </a>
                <a href="{{ route('user.ticket.index') }}"
                    class="flex items-center {{ request()->routeIs('user.ticket.*') ? 'text-blue-600' : 'text-gray-900 hover:text-blue-600' }}">
                    <span class="ml-2">SIRS</span>
                </a>
                <a href="{{ route('user.administrasi-umum.order-barang') }}"
                    class="flex items-center {{ request()->routeIs('user.administrasi-umum.order-barang') ? 'text-blue-600' : 'text-gray-900 hover:text-blue-600' }}">
                    <span class="ml-2">IPSRS</span>
                </a>
                <a href="{{ route('user.faq') }}"
                    class="flex items-center {{ request()->routeIs('user.faq') ? 'text-blue-600' : 'text-gray-900 hover:text-blue-600' }}">
                    <span class="ml-2">FAQ</span>
                </a>
                <a href="{{ route('user.knowledge-base') }}"
                    class="flex items-center {{ request()->routeIs('user.knowledge-base') ? 'text-blue-600' : 'text-gray-900 hover:text-blue-600' }}">
                    <span class="ml-2">Knowledge Base</span>
                </a>
            </div>

            <!-- Mobile Menu Button -->
            <div class="md:hidden">
                <button type="button" class="text-gray-700 hover:text-blue-600" onclick="toggleMobileMenu()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>

            <!-- User Dropdown -->
            <div class="flex items-center ms-3 space-x-4">
                <!-- Notification Icon -->
                <div class="relative">
                    <button type="button" class="relative p-2 text-gray-500 hover:text-gray-700"
                        onclick="toggleNotifications()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        @if(auth()->user()->unreadNotifications->count() > 0)
                        <span
                            class="absolute -top-1 -right-1 block h-4 w-4 rounded-full bg-red-500 text-white text-xs flex items-center justify-center">
                            {{ auth()->user()->unreadNotifications->count() }}
                        </span>
                        @endif
                    </button>

                    <!-- Notifications Dropdown -->
                    <div id="notifications-dropdown"
                        class="hidden absolute right-0 mt-2 w-96 bg-white rounded-md shadow-lg py-1 z-50 max-h-96 overflow-y-auto">
                        <div class="px-4 py-2 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h3 class="text-sm font-medium text-gray-900">Notifikasi</h3>
                                <a href="{{ route('user.notifications.index') }}"
                                    class="text-xs text-blue-600 hover:text-blue-800">
                                    Lihat Semua
                                </a>
                            </div>
                        </div>
                        @forelse(auth()->user()->notifications()->take(5)->get() as $notification)
                        <a href="{{ route('user.ticket.show', ['ticket' => $notification->data['ticket_id']]) }}"
                            onclick="event.preventDefault(); markNotificationAsRead('{{ $notification->id }}', this)"
                            class="block px-4 py-3 hover:bg-gray-50 {{ $notification->read_at ? 'bg-gray-50' : 'bg-white' }}">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-2 h-2 rounded-full {{ $notification->read_at ? 'bg-gray-400' : 'bg-blue-500' }}">
                                    </div>
                                </div>
                                <div class="ml-3 flex-1">
                                    <div class="flex justify-between">
                                        <h4 class="text-sm font-medium text-gray-900">
                                            {{ $notification->data['title'] }}
                                        </h4>
                                        <p class="text-xs text-gray-500">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ $notification->data['message'] }}
                                    </p>
                                    <div class="mt-1 text-xs text-gray-500">
                                        Ticket #{{ $notification->data['ticket_number'] }}
                                    </div>
                                    @if(isset($notification->data['responder_name']))
                                    <div class="mt-1 text-xs text-gray-500">
                                        Oleh: {{ $notification->data['responder_name'] }}
                                        ({{ $notification->data['responder_role'] }})
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </a>
                        @empty
                        <div class="px-4 py-3 text-sm text-gray-500">
                            Tidak ada notifikasi
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- User Dropdown -->
                <div>
                    <button type="button" class="flex text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-gray-300"
                        aria-expanded="false" data-dropdown-toggle="dropdown-user" onclick="toggleDropdown()">
                        <span class="sr-only">Open user menu</span>
                        <div
                            class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium">
                            {{ substr(Auth::user()->name, 0, 2) }}
                        </div>
                    </button>
                </div>
                <div class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded-sm shadow-sm dark:bg-gray-700 dark:divide-gray-600 absolute right-0 mt-60"
                    id="dropdown-user">
                    <div class="px-4 py-3" role="none">
                        <p class="text-sm text-gray-900 dark:text-white" role="none">
                            {{ Auth::user()->name }}
                        </p>
                        <p class="text-sm font-medium text-gray-900 truncate dark:text-gray-300" role="none">
                            {{ Auth::user()->email }}
                        </p>
                    </div>
                    <ul class="py-1" role="none">
                        <li>
                            <a href="{{ route('user.settings') }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white"
                                role="menuitem">Settings</a>
                        </li>
                        <li>
                            <a href="{{ route('user.report') }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white"
                                role="menuitem">Report</a>
                        </li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white"
                                    role="menuitem">LogOut</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="hidden md:hidden bg-white" id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="{{ route('user.dashboard') }}"
                class="block px-3 py-2 {{ request()->routeIs('user.dashboard') ? 'text-blue-600 bg-blue-50' : 'text-gray-900 hover:bg-gray-100' }} rounded-md">
                Beranda
            </a>
            <a href="{{ route('user.ticket.index') }}"
                class="block px-3 py-2 {{ request()->routeIs('user.ticket.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-900 hover:bg-gray-100' }} rounded-md">
                SIRS
            </a>
            <a href="{{ route('user.administrasi-umum.order-barang') }}"
                class="block px-3 py-2 {{ request()->routeIs('user.administrasi-umum.order-barang') ? 'text-blue-600 bg-blue-50' : 'text-gray-900 hover:bg-gray-100' }} rounded-md">
                IPSRS
            </a>
            <a href="{{ route('user.faq') }}"
                class="block px-3 py-2 {{ request()->routeIs('user.faq') ? 'text-blue-600 bg-blue-50' : 'text-gray-900 hover:bg-gray-100' }} rounded-md">
                FAQ
            </a>
            <a href="{{ route('user.knowledge-base') }}"
                class="block px-3 py-2 {{ request()->routeIs('user.knowledge-base') ? 'text-blue-600 bg-blue-50' : 'text-gray-900 hover:bg-gray-100' }} rounded-md">
                Knowledge Base
            </a>
        </div>
    </div>
</nav>

<script>
function toggleDropdown() {
    const dropdown = document.getElementById('dropdown-user');
    dropdown.classList.toggle('hidden');
}

function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu.classList.toggle('hidden');
}

function toggleNotifications() {
    const dropdown = document.getElementById('notifications-dropdown');
    dropdown.classList.toggle('hidden');
}

function markNotificationAsRead(notificationId, element) {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch(`/user/notifications/${notificationId}/mark-as-read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
        }
    }).then(response => {
        if (response.ok) {
            // Update UI to show notification as read
            const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.classList.add('bg-gray-50');
                const dot = notificationElement.querySelector('.rounded-full');
                if (dot) {
                    dot.classList.remove('bg-blue-500');
                    dot.classList.add('bg-gray-400');
                }
            }

            // Update unread count
            const unreadCount = document.querySelector('.notification-count');
            if (unreadCount) {
                const currentCount = parseInt(unreadCount.textContent) - 1;
                if (currentCount > 0) {
                    unreadCount.textContent = currentCount;
                } else {
                    unreadCount.remove();
                }
            }

            // Redirect to ticket detail
            window.location.href = element.href;
        }
    });
}
</script>