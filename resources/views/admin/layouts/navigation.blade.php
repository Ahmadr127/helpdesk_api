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
            @if(auth()->user()->hasPermission('admin.dashboard') || auth()->user()->hasPermission('ticket.manage'))
            <a href="{{ route('admin.dashboard') }}"
                class="text-sm font-medium text-gray-700 py-3 px-3 hover:bg-blue-50 rounded-lg transition duration-200 ease-in-out flex items-center {{ request()->routeIs('admin.dashboard') ? 'bg-blue-50 border-l-4 border-blue-600 pl-2' : '' }}">
                <svg class="w-5 h-5 mr-3 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
                <span class="text-gray-800">Dashboard IT</span>
            </a>
            @endif
            @if(auth()->user()->hasPermission('ipsrs.dashboard') || auth()->user()->hasPermission('order.manage'))
            <a href="{{ route('admin.ipsrs.dashboard') }}"
                class="text-sm font-medium text-gray-700 py-3 px-3 hover:bg-blue-50 rounded-lg transition duration-200 ease-in-out flex items-center {{ request()->routeIs('admin.ipsrs.*') ? 'bg-blue-50 border-l-4 border-blue-600 pl-2' : '' }}">
                <svg class="w-5 h-5 mr-3 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
                <span class="text-gray-800">Dashboard IPSRS</span>
            </a>
            @endif

            @if(auth()->user()->hasPermission('ticket.manage'))
            <a href="{{ route('admin.tickets.index') }}"
                class="text-sm font-medium text-gray-700 py-3 px-3 hover:bg-blue-50 rounded-lg transition duration-200 ease-in-out flex items-center {{ request()->routeIs('admin.tickets.*', 'admin.ticket.*') ? 'bg-blue-50 border-l-4 border-blue-600 pl-2' : '' }}">
                <svg class="w-5 h-5 mr-3 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" />
                </svg>
                <span class="text-gray-800">Tickets</span>
            </a>
            @endif
            @if(auth()->user()->hasPermission('order.manage') || auth()->user()->hasPermission('order.view'))
            <a href="{{ route('admin.order-perbaikan.index') }}"
                class="text-sm font-medium text-gray-700 py-3 px-3 hover:bg-blue-50 rounded-lg transition duration-200 ease-in-out flex items-center {{ request()->routeIs('admin.order-perbaikan.*') ? 'bg-blue-50 border-l-4 border-blue-600 pl-2' : '' }}">
                <svg class="w-5 h-5 mr-3 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085" />
                </svg>
                <span class="text-gray-800">Order Perbaikan</span>
            </a>
            @endif

            @if(auth()->user()->hasPermission('master.view') || auth()->user()->hasPermission('master.manage'))
            <a href="{{ route('admin.master.index') }}"
                class="text-sm font-medium text-gray-700 py-3 px-3 hover:bg-blue-50 rounded-lg transition duration-200 ease-in-out flex items-center {{ request()->routeIs('admin.master.*') ? 'bg-blue-50 border-l-4 border-blue-600 pl-2' : '' }}">
                <svg class="w-5 h-5 mr-3 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 3.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125m16.5 3.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                </svg>
                <span class="text-gray-800">Master Data</span>
            </a>
            @endif

            @if(auth()->user()->hasPermission('user.view') || auth()->user()->hasPermission('user.manage'))
            <a href="{{ route('admin.users.index') }}"
                class="text-sm font-medium text-gray-700 py-3 px-3 hover:bg-blue-50 rounded-lg transition duration-200 ease-in-out flex items-center {{ request()->routeIs('admin.users.*') ? 'bg-blue-50 border-l-4 border-blue-600 pl-2' : '' }}">
                <svg class="w-5 h-5 mr-3 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
                <span class="text-gray-800">Users Management</span>
            </a>
            @endif

            @if(auth()->user()->hasPermission('user.manage'))
            <a href="{{ route('admin.permissions.index') }}"
                class="text-sm font-medium text-gray-700 py-3 px-3 hover:bg-blue-50 rounded-lg transition duration-200 ease-in-out flex items-center {{ request()->routeIs('admin.permissions.*') ? 'bg-blue-50 border-l-4 border-blue-600 pl-2' : '' }}">
                <svg class="w-5 h-5 mr-3 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
                <span class="text-gray-800">Permissions</span>
            </a>
            @endif

            @if(auth()->user()->hasPermission('report.view') || auth()->user()->hasPermission('report.manage'))
            <a href="{{ route('admin.reports.index') }}"
                class="text-sm font-medium text-gray-700 py-3 px-3 hover:bg-blue-50 rounded-lg transition duration-200 ease-in-out flex items-center {{ request()->routeIs('admin.reports.*') ? 'bg-blue-50 border-l-4 border-blue-600 pl-2' : '' }}">
                <svg class="w-5 h-5 mr-3 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                <span class="text-gray-800">Reports</span>
            </a>
            @endif
            @if(auth()->user()->hasPermission('report.sirs') || auth()->user()->hasPermission('report.manage'))
            <a href="{{ route('admin.report-sirs.index') }}"
                class="text-sm font-medium text-gray-700 py-3 px-3 hover:bg-blue-50 rounded-lg transition duration-200 ease-in-out flex items-center {{ request()->routeIs('admin.report-sirs.*') ? 'bg-blue-50 border-l-4 border-blue-600 pl-2' : '' }}">
                <svg class="w-5 h-5 mr-3 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                </svg>
                <span class="text-gray-800">Report SIRS</span>
            </a>
            @endif

            @if(auth()->user()->hasPermission('feedback.view') || auth()->user()->hasPermission('feedback.manage'))
            <a href="{{ route('admin.feedback.index') }}"
                class="text-sm font-medium text-gray-700 py-3 px-3 hover:bg-blue-50 rounded-lg transition duration-200 ease-in-out flex items-center {{ request()->routeIs('admin.feedback.*') ? 'bg-blue-50 border-l-4 border-blue-600 pl-2' : '' }}">
                <svg class="w-5 h-5 mr-3 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                </svg>
                <span class="text-gray-800">Feedback</span>
            </a>
            @endif
            @if(auth()->user()->hasPermission('notification.view'))
            <a href="{{ route('admin.notifications.index') }}"
                class="text-sm font-medium text-gray-700 py-3 px-3 hover:bg-blue-50 rounded-lg transition duration-200 ease-in-out flex items-center {{ request()->routeIs('admin.notifications.*') ? 'bg-blue-50 border-l-4 border-blue-600 pl-2' : '' }}">
                <svg class="w-5 h-5 mr-3 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                <span class="text-gray-800">Notifications</span>
            </a>
            @endif
            @if(auth()->user()->hasPermission('fcm.manage'))
            <a href="{{ route('admin.fcm.index') }}"
                class="text-sm font-medium text-gray-700 py-3 px-3 hover:bg-blue-50 rounded-lg transition duration-200 ease-in-out flex items-center {{ request()->routeIs('admin.fcm.*') ? 'bg-blue-50 border-l-4 border-blue-600 pl-2' : '' }}">
                <svg class="w-5 h-5 mr-3 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.651a3.75 3.75 0 010-5.303m5.304 0a3.75 3.75 0 010 5.303m-7.425 2.122a6.75 6.75 0 010-9.546m9.546 0a6.75 6.75 0 010 9.546M5.106 18.894c-3.808-3.808-3.808-9.98 0-13.789m13.788 0c3.808 3.808 3.808 9.981 0 13.79M12 12h.008v.008H12V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
                <span class="text-gray-800">FCM Monitoring</span>
            </a>
            @endif
        </div>
    </div>
</div>

<!-- Overlay -->
<div x-show="sidenav" @click="sidenav = false" class="fixed inset-0 bg-black bg-opacity-50 z-20 md:hidden"></div>
