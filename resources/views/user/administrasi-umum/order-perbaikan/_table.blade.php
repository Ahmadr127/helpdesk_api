@forelse($orders as $order)
<tr>
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm font-medium text-gray-900">{{ $order->nomor }}</div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900">{{ $order->tanggal->format('d/m/Y H:i') }}</div>
        @if($order->status == 'confirmed' || $order->status == 'rejected')
        @php
        $statusHistory = $order->history->where('status', $order->status)->first();
        @endphp
        @if($statusHistory)
        <div class="text-xs text-gray-500">
            Respon: {{ \Carbon\Carbon::parse($statusHistory->created_at)->format('d/m/Y H:i') }}
        </div>
        @endif
        @endif
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900">{{ $order->unit_proses_name }}</div>
        <div class="text-xs text-gray-500">{{ $order->unit_proses }}</div>
    </td>
    <td class="px-6 py-4">
        <div class="text-sm text-gray-900">{{ $order->nama_barang }}</div>
        <div class="text-xs text-gray-500">{{ $order->keluhan }}</div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $order->getStatusBadgeClass() }}">
            {{ $order->getStatusText() }}
        </span>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="inline-flex px-2 py-1 text-xs rounded-full 
            {{ match($order->prioritas) {
                'RENDAH' => 'bg-green-100 text-green-800',
                'SEDANG' => 'bg-yellow-100 text-yellow-800',
                'TINGGI/URGENT' => 'bg-red-100 text-red-800',
                default => 'bg-gray-100 text-gray-800'
            } }}">
            {{ $order->prioritas }}
        </span>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
        <button onclick="showOrderDetail('{{ $order->id }}')" class="text-blue-600 hover:text-blue-900">Detail</button>
    </td>
</tr>
@empty
<tr>
    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
        Tidak ada order yang ditemukan
    </td>
</tr>
@endforelse