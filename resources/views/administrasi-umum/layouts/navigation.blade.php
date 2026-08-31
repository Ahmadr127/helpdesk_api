<!-- Sidebar -->
<div id="sidebar" class="fixed left-0 h-screen shadow-lg px-3 w-30 md:w-60 lg:w-60 overflow-y-auto p-6 bg-gradient-to-b from-white to-blue-300 border-r border-gray-100 z-30"
    :class="{'hidden': !sidenav, 'block': sidenav, 'md:block': true}">
    <div class="space-y-6 md:space-y-10 mt-10">
        <h1 class="font-bold text-4xl text-center md:hidden">
            A<span class="text-blue-600">.</span>
        </h1>
        <div class="flex justify-center">
            <img src="{{ asset('images/logoazra.png') }}" alt="Logo" class="hidden md:block w-32 mx-auto hover:scale-105 transition-transform duration-300" />
        </div>
        <div id="profile" class="space-y-3">
            <div class="w-16 h-16 rounded-full mx-auto bg-gradient-to-r from-blue-500 to-blue-600 text-white flex items-center justify-center shadow-md">
                <span class="text-3xl font-bold">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}{{ strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1)) }}</span>
            </div>
            <div>
                <h2 class="font-medium text-xs md:text-sm text-center text-gray-800">
                    {{ Auth::user()->name }}
                </h2>
                <p class="text-xs text-gray-500 text-center">Administrasi Umum</p>
            </div>
        </div>

        <div id="menu" class="flex flex-col space-y-2">
            <!-- Dashboard -->
            <a href="{{ route('administrasi-umum.dashboard') }}"
                class="text-sm font-medium text-gray-700 py-3 px-3 hover:bg-gradient-to-r hover:from-green-50 hover:to-blue-50 rounded-lg transition duration-200 ease-in-out flex items-center {{ request()->routeIs('administrasi-umum.dashboard') ? 'bg-gradient-to-r from-green-50 to-blue-50 border-l-4 border-blue-500 pl-2' : '' }}">
                <svg class="w-5 h-5 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                    </path>
                </svg>
                <span class="text-gray-800">Dashboard</span>
            </a>

            <!-- Order Perbaikan -->
            <a href="{{ route('administrasi-umum.order-perbaikan.index') }}"
                class="text-sm font-medium text-gray-700 py-3 px-3 hover:bg-gradient-to-r hover:from-green-50 hover:to-blue-50 rounded-lg transition duration-200 ease-in-out flex items-center {{ request()->routeIs('administrasi-umum.order-perbaikan.*') ? 'bg-gradient-to-r from-green-50 to-blue-50 border-l-4 border-blue-500 pl-2' : '' }}">
                <svg class="w-5 h-5 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span class="text-gray-800">Order Perbaikan</span>
            </a>
        </div>

        <form action="{{ route('logout') }}" method="POST" class="absolute bottom-5 w-[inherit] mb-4">
            @csrf
            <button type="submit"
                class="w-[90%] mx-auto p-2 px-3 py-2 text-sm text-white font-medium bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 rounded-lg shadow-md transition duration-200 ease-in-out">
                <span class="flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </span>
            </button>
        </form>
    </div>
</div>

<!-- Overlay -->
<div x-show="sidenav" @click="sidenav = false"
    class="fixed inset-0 bg-black bg-opacity-50 z-20 md:hidden"></div> 