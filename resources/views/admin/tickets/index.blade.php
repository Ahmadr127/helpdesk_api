@extends('admin.layouts.app')

@section('title', 'Kelola Tiket')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header with Notification -->
    <div class="flex justify-between items-center mb-6">
        <div></div>
    </div>

    <!-- Filter Form -->
    <div class="bg-gradient-to-r from-white to-blue-200 rounded-lg shadow-sm p-6 mb-8">
        <form action="{{ route('admin.tickets.index') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search Input -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Cari Tiket</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-white/80 text-gray-900"
                        placeholder="Nomor tiket atau nama">
                </div>

                <!-- Start Date -->
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-white/80 text-gray-900">
                </div>

                <!-- End Date -->
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Dibuka</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Dalam
                            Proses</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Ditutup</option>
                    </select>
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="flex justify-between items-center">
                <!-- Reset and Filter Buttons -->
                <div class="space-x-2">
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Terapkan Filter
                    </button>
                    <a href="{{ route('admin.tickets.index') }}"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Reset
                    </a>
                </div>

                <!-- Notification and History Buttons -->
                <div class="flex items-center space-x-2">
                    <!-- Notification Button -->
                    @include('admin.layouts.partials.notifications')

                    <!-- History Link -->
                    <a href="{{ route('admin.tickets.history.index') }}"
                        class="px-4 py-2 text-sm font-medium text-white bg-gray-500 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150 ease-in-out">
                        Lihat Riwayat
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- All Tickets Card -->
        <a href="{{ route('admin.tickets.all') }}"
            class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 p-6 cursor-pointer">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 mr-4 transition duration-300 hover:bg-blue-200">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                        </path>
                    </svg>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-medium">Semua Tiket</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $totalTickets }}</h3>
                </div>
            </div>
        </a>

        <!-- Open Tickets Card -->
        <a href="{{ route('admin.tickets.open') }}"
            class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 p-6 cursor-pointer">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 mr-4 transition duration-300 hover:bg-green-200">
                    <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                        </path>
                    </svg>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-medium">Tiket Dibuka</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $openTickets }}</h3>
                </div>
            </div>
        </a>

        <!-- In Progress Tickets Card -->
        <a href="{{ route('admin.tickets.in-progress') }}"
            class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 p-6 cursor-pointer">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 mr-4 transition duration-300 hover:bg-yellow-200">
                    <svg class="w-7 h-7 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-medium">Dalam Proses</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $inProgressTickets }}</h3>
                </div>
            </div>
        </a>

        <!-- Closed Tickets Card -->
        <a href="{{ route('admin.tickets.closed') }}"
            class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 p-6 cursor-pointer">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 mr-4 transition duration-300 hover:bg-purple-200">
                    <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-medium">Tiket Ditutup</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $closedTickets }}</h3>
                </div>
            </div>
        </a>
    </div>

    @if(request()->hasAny(['search', 'start_date', 'end_date', 'status']) && $tickets->isEmpty())
    <div class="bg-yellow-50 border border-yellow-100 rounded-lg p-4 mb-8">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-base font-medium text-yellow-800">
                    Tidak ada tiket yang sesuai dengan filter
                </h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>
                        Coba ubah kriteria pencarian Anda atau <a href="{{ route('admin.tickets.index') }}"
                            class="font-medium underline hover:text-yellow-900">reset filter</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Priority Groups -->
    <div class="space-y-8">
        <!-- High Priority Tickets -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-red-50 border-b border-red-100">
                <button onclick="toggleSection('high-priority')" class="w-full text-left">
                    <h3 class="text-lg font-semibold text-red-700 flex items-center justify-between">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-2 transform transition-transform" id="icon-high-priority"
                                fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                            Tiket Prioritas Tinggi
                        </span>
                        <span class="text-sm text-red-600">{{ $highPriorityTickets->count() }} tiket</span>
                    </h3>
                </button>
            </div>
            <div id="section-high-priority" class="overflow-x-auto">
                @include('admin.tickets.partials.ticket-table', ['tickets' => $highPriorityTickets])
            </div>
        </div>

        <!-- Medium Priority Tickets -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-yellow-50 border-b border-yellow-100">
                <button onclick="toggleSection('medium-priority')" class="w-full text-left">
                    <h3 class="text-lg font-semibold text-yellow-700 flex items-center justify-between">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-2 transform transition-transform" id="icon-medium-priority"
                                fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                            Tiket Prioritas Sedang
                        </span>
                        <span class="text-sm text-yellow-600">{{ $mediumPriorityTickets->count() }} tiket</span>
                    </h3>
                </button>
            </div>
            <div id="section-medium-priority" class="overflow-x-auto">
                @include('admin.tickets.partials.ticket-table', ['tickets' => $mediumPriorityTickets])
            </div>
        </div>

        <!-- Low Priority Tickets -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-blue-50 border-b border-blue-100">
                <button onclick="toggleSection('low-priority')" class="w-full text-left">
                    <h3 class="text-lg font-semibold text-blue-700 flex items-center justify-between">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-2 transform transition-transform" id="icon-low-priority"
                                fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                            Tiket Prioritas Rendah
                        </span>
                        <span class="text-sm text-blue-600">{{ $lowPriorityTickets->count() }} tiket</span>
                    </h3>
                </button>
            </div>
            <div id="section-low-priority" class="overflow-x-auto">
                @include('admin.tickets.partials.ticket-table', ['tickets' => $lowPriorityTickets])
            </div>
        </div>
    </div>

    <div class="mt-6">
        {{ $tickets->links() }}
    </div>
</div>

@push('scripts')
<script>
function toggleSection(sectionId) {
    const section = document.getElementById('section-' + sectionId);
    const icon = document.getElementById('icon-' + sectionId);

    if (section.style.display === 'none') {
        section.style.display = 'block';
        icon.classList.remove('rotate-180');
        localStorage.setItem(sectionId, 'shown');
    } else {
        section.style.display = 'none';
        icon.classList.add('rotate-180');
        localStorage.setItem(sectionId, 'hidden');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const sections = ['high-priority', 'medium-priority', 'low-priority'];

    sections.forEach(sectionId => {
        const section = document.getElementById('section-' + sectionId);
        const icon = document.getElementById('icon-' + sectionId);

        if (section && icon) {
            const savedState = localStorage.getItem(sectionId) || 'shown';

            if (savedState === 'hidden') {
                section.style.display = 'none';
                icon.classList.add('rotate-180');
            } else {
                section.style.display = 'block';
                icon.classList.remove('rotate-180');
            }
        }
    });
});
</script>
<style>
.tooltip {
    position: relative;
    cursor: pointer;
}

.tooltip:hover::after {
    content: attr(title);
    position: absolute;
    left: 0;
    top: 100%;
    z-index: 50;
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 0.5rem;
    border-radius: 0.25rem;
    white-space: normal;
    max-width: 300px;
    width: max-content;
    font-size: 0.75rem;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}
</style>
@endpush

@endsection