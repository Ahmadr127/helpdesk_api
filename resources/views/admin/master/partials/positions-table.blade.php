@if($positions->isEmpty())
    <tr>
        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
            Tidak ada data posisi yang tersedia
        </td>
    </tr>
@else
    @foreach($positions as $position)
    <tr class="hover:bg-gray-50">
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
            {{ $position->name }}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {{ $position->code }}
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $position->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                {{ $position->status ? 'Active' : 'Inactive' }}
            </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {{ $position->created_at->format('d/m/Y H:i') }}
        </td>
    </tr>
    @endforeach
@endif 