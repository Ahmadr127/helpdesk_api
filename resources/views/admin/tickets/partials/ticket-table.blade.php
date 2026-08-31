<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gradient-to-r from-green-100 to-blue-100">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Tiket</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departemen</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Dibuat
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Proses</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal
                Konfirmasi</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse($tickets as $ticket)
        <tr class="hover:bg-gradient-to-r hover:from-green-50 hover:to-blue-50 transition-all">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $ticket->ticket_number }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $ticket->user->name }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $ticket->category }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $ticket->department }}</td>
            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
                <div class="truncate tooltip w-48 md:w-64" title="{{ $ticket->description }}">{{ $ticket->description }}
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                @php
                $statusClasses = [
                'open' => 'bg-blue-100 text-blue-800',
                'in_progress' => 'bg-yellow-100 text-yellow-800',
                'pending' => 'bg-red-100 text-red-800',
                'closed' => 'bg-red-100 text-red-800',
                'confirmed' => 'bg-green-100 text-green-800'
                ];
                $statusLabels = [
                'open' => 'Dibuka',
                'in_progress' => 'Dalam Proses',
                'pending' => 'Menunggu Konfirmasi',
                'closed' => 'Menunggu Konfirmasi',
                'confirmed' => 'Dikonfirmasi'
                ];
                @endphp
                <span
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClasses[$ticket->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ $statusLabels[$ticket->status] ?? ucfirst($ticket->status) }}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ $ticket->created_at->format('d M Y H:i') }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                @if($ticket->in_progress_at)
                {{ $ticket->in_progress_at->format('H:i') }}
                @else
                -
                @endif
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                @if($ticket->user_confirmed_at)
                {{ $ticket->user_confirmed_at->format('H:i') }}
                @elseif($ticket->status === 'closed' || $ticket->status === 'pending')
                {{ $ticket->closed_at ? $ticket->closed_at->format('H:i') : '-' }}
                @else
                -
                @endif
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                @if($ticket->status === 'confirmed')
                <a href="{{ route('admin.tickets.history.show', $ticket->id) }}"
                    class="text-blue-600 hover:text-blue-900">Lihat Detail</a>
                @else
                <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="text-blue-600 hover:text-blue-900">Lihat
                    Detail</a>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="10" class="px-6 py-4 text-center text-sm text-gray-500">
                Tidak ada tiket ditemukan
            </td>
        </tr>
        @endforelse
    </tbody>
</table>