<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-slate-50">
        <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">No Tiket</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-36 truncate">Pengguna</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-28 truncate">Kategori</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32 truncate">Departemen</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Status</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32 whitespace-nowrap">Tanggal Dibuat</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Aksi</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse($tickets as $ticket)
        <tr class="hover:bg-slate-50 transition-all">
            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $ticket->ticket_number }}</td>
            <td class="px-4 py-3 text-sm text-gray-500 truncate" title="{{ $ticket->user->name }}">{{ $ticket->user->name }}</td>
            <td class="px-4 py-3 text-sm text-gray-500 truncate" title="{{ $ticket->category }}">{{ $ticket->category }}</td>
            <td class="px-4 py-3 text-sm text-gray-500 truncate" title="{{ $ticket->department?->name }}">{{ $ticket->department?->name ?? '-' }}</td>
            <td class="px-4 py-3 text-sm text-gray-500 max-w-xs">
                <div class="truncate tooltip w-48 md:w-64" title="{{ $ticket->description }}">{{ $ticket->description }}
                </div>
            </td>
            <td class="px-4 py-3 whitespace-nowrap">
                @php
                $statusClasses = [
                'open' => 'bg-blue-100 text-blue-800',
                'in_progress' => 'bg-yellow-100 text-yellow-800',
                'pending' => 'bg-red-100 text-red-800',
                'ditutup' => 'bg-red-100 text-red-800',
                'confirmed' => 'bg-green-100 text-green-800'
                ];
                $statusLabels = [
                'open' => 'Dibuka',
                'in_progress' => 'Dalam Proses',
                'pending' => 'Menunggu Konfirmasi',
                'ditutup' => 'Menunggu Konfirmasi',
                'confirmed' => 'Dikonfirmasi'
                ];
                @endphp
                <span
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClasses[$ticket->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ $statusLabels[$ticket->status] ?? ucfirst($ticket->status) }}
                </span>
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                {{ $ticket->created_at->format('d M Y H:i') }}
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="text-blue-600 hover:text-blue-900">Lihat
                    Detail</a>
                @if(!empty($myticketActions) && $ticket->user_id === auth()->id() && $ticket->status === 'open')
                <a href="{{ route('admin.tickets.myticket.edit', $ticket->id) }}" class="ml-2 text-yellow-600 hover:text-yellow-900">Ubah</a>
                <button type="button" data-delete-url="{{ route('admin.tickets.myticket.destroy', $ticket->id) }}" onclick="openMyticketDelete(this)" class="ml-2 text-red-600 hover:text-red-900">Hapus</button>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" class="px-4 py-4 text-center text-sm text-gray-500">
                Tidak ada tiket ditemukan
            </td>
        </tr>
        @endforelse
    </tbody>
</table>