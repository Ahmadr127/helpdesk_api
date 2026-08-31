@extends('user.layouts.app')

@section('title', 'Tiket')

@section('content')
<!-- Include the notification partial -->
@include('user.partials.notification')

<div class="container mx-auto px-4 py-8 max-w-8xl">
    <!-- Filter Section -->
    <div class="mb-8 rounded-xl shadow-sm bg-white border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-blue-200 px-5 py-4 mb-5">
            <h2 class="text-lg font-semibold text-white">Filter Tiket</h2>
        </div>
        <div class="p-5">
            <form action="{{ route('user.ticket.index') }}" method="GET" class="flex flex-wrap gap-4">
                <div class="w-full md:w-auto">
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                    <div class="relative">
                        <select name="category" id="category"
                            class="w-full md:w-48 px-4 py-2.5 border border-gray-200 rounded-lg appearance-none pr-10 bg-white focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-colors">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->name }}"
                                {{ request('category') == $category->name ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
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

                <div class="w-full md:w-auto">
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">Prioritas</label>
                    <div class="relative">
                        <select name="priority" id="priority"
                            class="w-full md:w-48 px-4 py-2.5 border border-gray-200 rounded-lg appearance-none pr-10 bg-white focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-colors">
                            <option value="">Semua Prioritas</option>
                            <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Tinggi</option>
                            <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Sedang
                            </option>
                            <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Rendah</option>
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

                <div class="w-full md:w-auto flex items-end">
                    <button type="submit"
                        class="bg-green-500 text-white px-5 py-2.5 rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                        Terapkan Filter
                    </button>
                    @if(request('category') || request('priority'))
                    <a href="{{ route('user.ticket.index') }}"
                        class="ml-3 bg-gray-100 text-gray-700 px-5 py-2.5 rounded-lg hover:bg-gray-200 transition-colors">
                        Bersihkan Filter
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Active Tickets Section -->
    <div class="mb-12">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Tiket Dukungan</h1>
            <a href="{{ route('user.ticket.create') }}"
                class="bg-green-600 text-white px-5 py-2.5 rounded-lg hover:bg-green-700 transition-colors shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Buat Tiket Baru
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
            <div class="bg-gradient-to-r from-green-600 to-blue-200 px-6 py-4">
                <h2 class="text-base font-semibold text-white">Tiket Aktif</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                No Tiket
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                Kategori
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                Departemen</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                Lokasi
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                Prioritas
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                Linimasa
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @php
                        $filteredTickets = $tickets->where('status', '!=', 'confirmed');

                        // Apply category filter if set
                        if(request('category')) {
                        $filteredTickets = $filteredTickets->where('category', request('category'));
                        }

                        // Apply priority filter if set
                        if(request('priority')) {
                        $filteredTickets = $filteredTickets->where('priority', request('priority'));
                        }

                        $activeTickets = $filteredTickets->sortBy(function($ticket) {
                        // Sort by priority (high, medium, low)
                        $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];
                        return $priorityOrder[$ticket->priority] ?? 4;
                        });
                        @endphp
                        @forelse($activeTickets as $ticket)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 relative text-sm">
                                {{ $ticket->ticket_number }}
                                @if(auth()->user()->notifications->where('data->ticket_id',
                                $ticket->id)->whereNull('read_at')->count() > 0)
                                <span
                                    class="absolute top-1/2 -translate-y-1/2 left-2 h-2 w-2 bg-blue-500 rounded-full"></span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">{{ ucfirst($ticket->category) }}</td>
                            <td class="px-6 py-4 text-sm">
                                {{ $ticket->department }}
                                <div class="text-xs text-gray-500">{{ $ticket->building }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $ticket->location }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2.5 py-1 text-xs rounded-full inline-flex items-center
                                {{ $ticket->priority === 'low' ? 'bg-blue-100 text-blue-800 border border-blue-200' : 
                                   ($ticket->priority === 'medium' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' : 
                                   ($ticket->priority === 'high' ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-gray-100 text-gray-800')) }}">
                                    @if($ticket->priority === 'high')
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" />
                                    </svg>
                                    @elseif($ticket->priority === 'medium')
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495z" />
                                    </svg>
                                    @elseif($ticket->priority === 'low')
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 2a8 8 0 100 16 8 8 0 000-16z" />
                                    </svg>
                                    @endif
                                    {{ $ticket->priority === 'low' ? 'Rendah' : 
                                       ($ticket->priority === 'medium' ? 'Sedang' : 
                                       ($ticket->priority === 'high' ? 'Tinggi' : $ticket->priority)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs leading-5 font-semibold rounded-full 
                                {{ $ticket->status === 'open' ? 'bg-blue-100 text-blue-800' : 
                                   ($ticket->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' :
                                   ($ticket->status === 'closed' ? 'bg-red-100 text-red-800' : 
                                   ($ticket->status === 'confirmed' ? 'bg-green-100 text-green-800' :
                                   ($ticket->status === 'rejected' ? 'bg-gray-100 text-gray-800' : 
                                   'bg-gray-100 text-gray-600')))) }}">
                                    {{ $ticket->status === 'open' ? 'Dibuka' : 
                                       ($ticket->status === 'in_progress' ? 'Sedang Diproses' : 
                                       ($ticket->status === 'closed' ? 'Menunggu Konfirmasi' : 
                                       ($ticket->status === 'confirmed' ? 'Selesai' : 
                                       ($ticket->status === 'rejected' ? 'Ditolak' : $ticket->status)))) }}
                                    @if(auth()->user()->notifications->where('data->ticket_id',
                                    $ticket->id)->whereNull('read_at')->count() > 0)
                                    <span class="ml-1 text-xs">•</span>
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs space-y-1">
                                    <div>Dibuat: {{ $ticket->created_at->format('d M Y H:i') }}</div>

                                    @if($ticket->in_progress_at)
                                    <div>Dalam Proses: {{ $ticket->in_progress_at->format('d M Y H:i') }}</div>
                                    @endif
                                    @if($ticket->closed_at)
                                    <div>Ditutup: {{ $ticket->closed_at->format('d M Y H:i') }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex space-x-3">
                                    <a href="{{ route('user.ticket.show', $ticket) }}"
                                        class="text-blue-600 hover:text-blue-900">Lihat Detail</a>

                                    @if($ticket->status === 'open')
                                    <a href="{{ route('user.ticket.edit', $ticket) }}"
                                        class="text-blue-600 hover:text-blue-900">Edit</a>
                                    @endif

                                    @if(in_array($ticket->status, ['open', 'pending']))
                                    <button
                                        onclick="showDeleteConfirmation('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')"
                                        class="text-red-600 hover:text-red-900 font-medium">
                                        Hapus
                                    </button>
                                    <form id="delete-form-{{ $ticket->id }}"
                                        action="{{ route('user.ticket.destroy', $ticket) }}" method="POST"
                                        class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    No active tickets found
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Ticket History Section -->
    <div class="mb-12">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">Riwayat Tiket</h2>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
            <div class="bg-gradient-to-r from-green-600 to-blue-200 px-6 py-4">
                <h2 class="text-base font-semibold text-white">Tiket Selesai</h2>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @php
                    $filteredHistory = $tickets->where('status', 'confirmed');

                    // Apply category filter if set
                    if(request('category')) {
                    $filteredHistory = $filteredHistory->where('category', request('category'));
                    }

                    // Apply priority filter if set
                    if(request('priority')) {
                    $filteredHistory = $filteredHistory->where('priority', request('priority'));
                    }

                    $confirmedTickets = $filteredHistory->sortBy(function($ticket) {
                    // Sort by priority (high, medium, low)
                    $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];
                    return $priorityOrder[$ticket->priority] ?? 4;
                    });
                    @endphp

                    @forelse($confirmedTickets as $ticket)
                    <div
                        class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-green-600 to-blue-200 p-4">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-white">#{{ $ticket->ticket_number }}</h3>
                                <span
                                    class="px-3 py-1 text-xs font-medium rounded-full bg-green-50 text-green-700 border border-green-100">
                                    Selesai
                                </span>
                            </div>
                        </div>

                        <div class="p-4">
                            <p class="text-sm text-gray-700 mb-4">{{ ucfirst($ticket->category) }}</p>

                            <div class="space-y-3 mb-4">
                                <div class="flex items-start">
                                    <span class="text-sm text-gray-500 w-24">Departemen:</span>
                                    <span class="text-sm font-medium text-gray-800">
                                        {{ ucfirst($ticket->department) }}
                                        <div class="text-xs text-gray-500 mt-0.5">{{ $ticket->building }}</div>
                                    </span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-sm text-gray-500 w-24">Prioritas:</span>
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full inline-flex items-center
                                    {{ $ticket->priority === 'low' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 
                                       ($ticket->priority === 'medium' ? 'bg-yellow-50 text-yellow-700 border border-yellow-200' : 
                                       'bg-red-50 text-red-700 border border-red-200') }}">
                                        @if($ticket->priority === 'high')
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" />
                                        </svg>
                                        @elseif($ticket->priority === 'medium')
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495z" />
                                        </svg>
                                        @elseif($ticket->priority === 'low')
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 2a8 8 0 100 16 8 8 0 000-16z" />
                                        </svg>
                                        @endif
                                        {{ $ticket->priority === 'low' ? 'Rendah' : 
                                           ($ticket->priority === 'medium' ? 'Sedang' : 
                                           ($ticket->priority === 'high' ? 'Tinggi' : ucfirst($ticket->priority))) }}
                                    </span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-sm text-gray-500 w-24">Lokasi:</span>
                                    <span class="text-sm text-gray-800">{{ $ticket->location }}</span>
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-3 mt-3">
                                <a href="{{ route('user.ticket.show', $ticket) }}"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium">Lihat Detail</a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-full flex justify-center items-center p-8 text-gray-500">
                        <div class="text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            Tidak ada tiket yang selesai
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm mx-auto" x-show="open">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Konfirmasi Hapus</h3>
            <p class="text-sm text-gray-500 mb-6">
                Apakah Anda yakin ingin menghapus tiket <span id="ticketNumber" class="font-medium"></span>?
                Tindakan ini tidak dapat dibatalkan.
            </p>
            <div class="flex justify-center space-x-3">
                <button type="button" onclick="hideDeleteConfirmation()"
                    class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Batal
                </button>
                <button type="button" onclick="confirmDelete()"
                    class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentTicketId = null;

function showDeleteConfirmation(ticketId, ticketNumber) {
    currentTicketId = ticketId;
    document.getElementById('ticketNumber').textContent = ticketNumber;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
}

function hideDeleteConfirmation() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
    currentTicketId = null;
}

function confirmDelete() {
    if (currentTicketId) {
        document.getElementById(`delete-form-${currentTicketId}`).submit();
    }
}

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideDeleteConfirmation();
    }
});
</script>

@endsection