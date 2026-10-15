{{-- Partial generik untuk MasterDataController@getData.
     Dipakai semua tipe master agar tidak butuh file partial per-tipe.
     Variabel: $type (canonical, underscore), $rows (Collection). --}}
<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-slate-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
            @if ($type !== 'locations')
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
            @endif
            @if ($type === 'categories')
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Proses</th>
            @endif
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @foreach ($rows as $row)
            <tr class="hover:bg-slate-50 transition-all">
                <td class="px-6 py-4">{{ $row->name }}</td>
                @if ($type !== 'locations')
                    <td class="px-6 py-4">{{ $row->code ?? '-' }}</td>
                @endif
                @if ($type === 'categories')
                    <td class="px-6 py-4">{{ $row->unitProses ? $row->unitProses->name : '-' }}</td>
                @endif
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded-full {{ $row->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $row->status ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-6 py-4">{{ $row->created_at ? $row->created_at->format('d M Y') : '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
