@extends('admin.layouts.app')

@section('title', 'Riwayat Tiket')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <a href="{{ route('admin.tickets.index') }}"
            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-lg shadow hover:from-gray-600 hover:to-gray-700 transition-all">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-100 mb-8">
        <div class="bg-gradient-to-r from-white to-blue-300 p-6 border-b border-gray-200">
            <h1 class="text-2xl font-bold text-gray-800">Riwayat Tiket yang Dikonfirmasi</h1>
            <p class="text-gray-600">Lihat dan kelola semua tiket yang telah dikonfirmasi</p>
        </div>

        <div class="p-6">
            <form id="exportForm" action="{{ route('admin.tickets.history.export') }}" method="GET"
                class="mb-6 bg-gray-50 p-4 rounded-lg">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="w-full md:w-auto">
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                        <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                            class="w-full rounded-md border border-gray-300 shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div class="w-full md:w-auto">
                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                        <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                            class="w-full rounded-md border border-gray-300 shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" form="filterForm"
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-md shadow hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Terapkan Filter
                        </button>

                        <button type="button" onclick="exportSelected()"
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-md shadow hover:from-green-600 hover:to-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export Excel
                        </button>
                    </div>
                </div>
            </form>

            <form id="filterForm" action="{{ route('admin.tickets.history.index') }}" method="GET"></form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-green-100 to-blue-300">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" id="selectAll"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                No Tiket</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pengguna</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Kategori</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Departemen</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal Dibuat</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Waktu Proses</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Waktu Selesai</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Waktu Konfirmasi</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total Durasi</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($tickets as $ticket)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" name="selected_tickets[]" value="{{ $ticket->id }}"
                                    class="ticket-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $ticket->ticket_number }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $ticket->user->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ ucfirst($ticket->category) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $ticket->department }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $ticket->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($ticket->in_progress_at)
                                {{ $ticket->in_progress_at->format('d/m/Y H:i') }}
                                @else
                                -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($ticket->closed_at)
                                {{ $ticket->closed_at->format('d/m/Y H:i') }}
                                @else
                                -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($ticket->user_confirmed_at)
                                {{ $ticket->user_confirmed_at->format('d/m/Y H:i') }}
                                @else
                                -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @php
                                $start = $ticket->created_at;
                                $end = $ticket->user_confirmed_at;
                                if ($start && $end) {
                                $duration = $start->diff($end);
                                $timeString = [];
                                if ($duration->days > 0) {
                                $timeString[] = $duration->days . ' hari';
                                }
                                if ($duration->h > 0) {
                                $timeString[] = $duration->h . ' jam';
                                }
                                if ($duration->i > 0) {
                                $timeString[] = $duration->i . ' menit';
                                }
                                echo empty($timeString) ? '0 menit' : implode(', ', $timeString);
                                } else {
                                echo '-';
                                }
                                @endphp
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <a href="{{ route('admin.tickets.history.show', $ticket) }}"
                                    class="text-blue-600 hover:text-blue-900 hover:underline transition-colors">Lihat</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada riwayat tiket
                                ditemukan</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $tickets->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.getElementsByClassName('ticket-checkbox');
    for (let checkbox of checkboxes) {
        checkbox.checked = this.checked;
    }
});

function exportSelected() {
    const selectedCheckboxes = document.querySelectorAll('.ticket-checkbox:checked');
    const selectedIds = Array.from(selectedCheckboxes)
        .map(checkbox => parseInt(checkbox.value))
        .filter(id => !isNaN(id));

    // Create a temporary form
    const tempForm = document.createElement('form');
    tempForm.method = 'POST';
    tempForm.action = "{{ route('admin.tickets.history.export') }}";

    // Add CSRF token
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    tempForm.appendChild(csrfToken);

    // Add method override for PUT
    const methodField = document.createElement('input');
    methodField.type = 'hidden';
    methodField.name = '_method';
    methodField.value = 'POST';
    tempForm.appendChild(methodField);

    // Add selected IDs
    selectedIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_tickets[]';
        input.value = id;
        tempForm.appendChild(input);
    });

    // Add date filters if present
    const dateFrom = document.getElementById('date_from').value;
    if (dateFrom) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'date_from';
        input.value = dateFrom;
        tempForm.appendChild(input);
    }

    const dateTo = document.getElementById('date_to').value;
    if (dateTo) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'date_to';
        input.value = dateTo;
        tempForm.appendChild(input);
    }

    // Add the form to the document and submit it
    document.body.appendChild(tempForm);
    tempForm.submit();
    document.body.removeChild(tempForm);
}
</script>
@endpush
@endsection