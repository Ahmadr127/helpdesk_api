<!-- Sidebar -->
<div id="sidebar"
    class="fixed left-0 h-screen px-3 w-30 md:w-60 lg:w-60 overflow-y-auto p-6 bg-white border-r border-gray-100 z-30"
    :class="{'hidden': !sidenav, 'block': sidenav, 'md:block': true}">
    <div class="space-y-6 md:space-y-10 mt-10 pb-8">
        <h1 class="font-bold text-4xl text-center md:hidden">
            A<span class="text-blue-600">.</span>
        </h1>
        <div class="flex justify-center">
            <img src="{{ asset('images/logoazra.png') }}" alt="Logo"
                class="hidden md:block w-32 mx-auto hover:scale-105 transition-transform duration-300" />
        </div>
        <div id="profile" class="space-y-3">
            <div
                class="w-16 h-16 rounded-full mx-auto bg-blue-600 text-white flex items-center justify-center shadow-md">
                <span
                    class="text-3xl font-bold">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}{{ strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1)) }}</span>
            </div>
            <div>
                <h2 class="font-medium text-xs md:text-sm text-center text-gray-800">
                    {{ Auth::user()->name }}
                </h2>
                <p class="text-xs text-gray-500 text-center">{{ Auth::user()->role }}</p>
            </div>
        </div>

        <div id="menu" class="flex flex-col space-y-1">
            {{-- Menu digerakkan config/admin_menu.php — tiap item membawa permission-nya sendiri --}}
            @foreach(config('admin_menu') as $item)
                @if(collect($item['permissions'])->contains(fn ($p) => auth()->user()->hasPermission($p)))
                    @php
                        $isActive = request()->routeIs(...$item['pattern'])
                            && ! request()->routeIs(...($item['exclude'] ?? ['__never__']));
                    @endphp
                    <a href="{{ route($item['route']) }}"
                        class="text-sm font-medium text-gray-700 py-3 px-3 hover:bg-blue-50 rounded-lg transition duration-200 ease-in-out flex items-center {{ $isActive ? 'bg-blue-50 border-l-4 border-blue-600 pl-2' : '' }}">
                        <svg class="w-5 h-5 mr-3 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                        </svg>
                        <span class="text-gray-800">{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</div>

<!-- Overlay -->
<div x-show="sidenav" @click="sidenav = false" class="fixed inset-0 bg-black bg-opacity-50 z-20 md:hidden"></div>
