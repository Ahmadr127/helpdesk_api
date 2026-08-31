@extends('user.layouts.app')

@section('title', $status === 'all' ? 'Semua Tiket' : ucfirst($status) . ' Tiket')

@section('content')
@include('user.partials.notification')

<div class="container px-6 mx-auto grid">
    <!-- Back Button -->
    <div class="mt-6">
        <a href="{{ route('user.dashboard') }}"
            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 border border-transparent rounded-md shadow-sm text-sm text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Kembali ke Dashboard
        </a>
    </div>

    <h2 class="my-6 text-2xl font-semibold text-gray-700">
        {{ $status === 'all' ? 'Semua Tiket' : 
           ($status === 'open' ? 'Tiket Dibuka' : 
           ($status === 'in_progress' ? 'Tiket Diproses' : 
           ($status === 'closed' ? 'Tiket Menunggu Konfirmasi' : 'Tiket Selesai'))) }}
        </h2>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 mb-8">
        <!-- All Tickets -->
        <a href="{{ route('user.ticket.filter.status', 'all') }}"
            class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 hover:shadow-md transition-all {{ $status === 'all' ? 'ring-2 ring-blue-500' : '' }}">
            <div class="flex flex-col items-center">
                <span class="text-blue-600 font-bold text-2xl">{{ $totalTickets }}</span>
                <span class="text-gray-700 text-sm mt-1">Total Tiket</span>
            </div>
        </a>

        <!-- Open Tickets -->
        <a href="{{ route('user.ticket.filter.status', 'open') }}"
            class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-4 hover:shadow-md transition-all {{ $status === 'open' ? 'ring-2 ring-indigo-500' : '' }}">
            <div class="flex flex-col items-center">
                <span class="text-indigo-600 font-bold text-2xl">{{ $openTickets }}</span>
                <span class="text-gray-700 text-sm mt-1">Dibuka</span>
            </div>
        </a>

        <!-- In Progress Tickets -->
        <a href="{{ route('user.ticket.filter.status', 'in_progress') }}"
            class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-4 hover:shadow-md transition-all {{ $status === 'in_progress' ? 'ring-2 ring-yellow-500' : '' }}">
            <div class="flex flex-col items-center">
                <span class="text-yellow-600 font-bold text-2xl">{{ $inProgressTickets }}</span>
                <span class="text-gray-700 text-sm mt-1">Diproses</span>
            </div>
        </a>

        <!-- Closed Tickets -->
        <a href="{{ route('user.ticket.filter.status', 'closed') }}"
            class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 hover:shadow-md transition-all {{ $status === 'closed' ? 'ring-2 ring-purple-500' : '' }}">
            <div class="flex flex-col items-center">
                <span class="text-purple-600 font-bold text-2xl">{{ $closedTickets }}</span>
                <span class="text-gray-700 text-sm mt-1">Menunggu Konfirmasi</span>
            </div>
        </a>

        <!-- Confirmed Tickets -->
        <a href="{{ route('user.ticket.filter.status', 'confirmed') }}"
            class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 hover:shadow-md transition-all {{ $status === 'confirmed' ? 'ring-2 ring-green-500' : '' }}">
            <div class="flex flex-col items-center">
                <span class="text-green-600 font-bold text-2xl">{{ $confirmedTickets }}</span>
                <span class="text-gray-700 text-sm mt-1">Selesai</span>
            </div>
        </a>
    </div>

    <!-- Search and Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form id="filterForm" action="{{ route('user.ticket.filter.status', $status) }}" method="GET" class="space-y-6">
            <!-- Date Range and Search Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-gray-700">Tanggal Berakhir</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-gray-700">Nomor Tiket</label>
                    <input type="text" name="ticket_number" value="{{ request('ticket_number') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Cari nomor tiket...">
                </div>
            </div>

            <!-- Advanced Filters Row -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Kategori</label>
                    <select name="category"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $category)
                        <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                            {{ $category }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Departemen</label>
                    <select name="department"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Departemen</option>
                        @foreach($departments as $department)
                        <option value="{{ $department }}" {{ request('department') == $department ? 'selected' : '' }}>
                            {{ $department }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Prioritas</label>
                    <select name="priority"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Prioritas</option>
                        @foreach($priorities as $priority)
                        <option value="{{ $priority }}" {{ request('priority') == $priority ? 'selected' : '' }}>
                            {{ $priority === 'low' ? 'Rendah' : ($priority === 'medium' ? 'Sedang' : 'Tinggi') }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end space-x-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Terapkan Filter
                    </button>
                    <button type="button" id="resetFilter"
                        class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                        Atur Ulang
                    </button>
                </div>
            </div>
            <input type="hidden" name="status" value="{{ $status }}">
        </form>
    </div>

    <!-- Tickets Table -->
    <div class="w-full overflow-hidden rounded-lg shadow-md">
        <div class="w-full overflow-x-auto">
            <table class="w-full whitespace-no-wrap">
                <thead>
                    <tr
                        class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                        <th class="px-4 py-3">Nomor Tiket</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Departemen</th>
                        <th class="px-4 py-3">Gedung</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Prioritas</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
                <tbody class="bg-white divide-y">
                @forelse($tickets as $ticket)
                    <tr class="text-gray-700">
                        <td class="px-4 py-3">{{ $ticket->ticket_number }}</td>
                        <td class="px-4 py-3">{{ $ticket->category }}</td>
                        <td class="px-4 py-3">{{ $ticket->department }}</td>
                        <td class="px-4 py-3">{{ $ticket->building }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ $ticket->status === 'open' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $ticket->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $ticket->status === 'closed' ? 'bg-purple-100 text-purple-800' : '' }}
                                {{ $ticket->status === 'confirmed' ? 'bg-green-100 text-green-800' : '' }}">
                                {{ $ticket->status === 'closed' ? 'Menunggu Konfirmasi' : 
                                   ($ticket->status === 'in_progress' ? 'Diproses' : 
                                   ($ticket->status === 'confirmed' ? 'Selesai' : 'Dibuka')) }}
                        </span>
                    </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ $ticket->priority === 'low' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $ticket->priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $ticket->priority === 'high' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ $ticket->priority === 'low' ? 'Rendah' : 
                                   ($ticket->priority === 'medium' ? 'Sedang' : 'Tinggi') }}
                        </span>
                    </td>
                        <td class="px-4 py-3">{{ $ticket->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex space-x-3">
                            <a href="{{ route('user.ticket.show', $ticket) }}"
                                class="text-blue-600 hover:text-blue-900">Lihat</a>

                            @if($ticket->status === 'open')
                            <a href="{{ route('user.ticket.edit', $ticket) }}"
                                    class="text-yellow-600 hover:text-yellow-900">Ubah</a>

                            <form action="{{ route('user.ticket.destroy', $ticket) }}" method="POST"
                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus tiket ini?');"
                                class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                        <td colspan="8" class="px-4 py-3 text-center text-gray-500">
                        Tidak ada tiket ditemukan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="px-4 py-3 border-t bg-gray-50">
            {{ $tickets->appends(request()->query())->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('filterForm');
    const resetButton = document.getElementById('resetFilter');
    const timeFilter = form.querySelector('select[name="time_filter"]');
    const monthFilter = document.getElementById('monthFilter');

    // Toggle month filter visibility based on time filter
    function toggleMonthFilter() {
        monthFilter.style.display = timeFilter.value === 'month' ? 'block' : 'none';
    }

    // Initial toggle
    toggleMonthFilter();

    // Time filter change handler
    timeFilter.addEventListener('change', toggleMonthFilter);

    // Reset button handler
    resetButton.addEventListener('click', function() {
        const inputs = form.querySelectorAll('input:not([name="status"])');
        const selects = form.querySelectorAll('select');

        inputs.forEach(input => input.value = '');
        selects.forEach(select => select.selectedIndex = 0);

        form.submit();
    });
});
</script>
@endpush
@endsection