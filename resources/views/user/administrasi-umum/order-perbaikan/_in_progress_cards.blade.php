@forelse($inProgressOrders ?? [] as $order)
<div class="flex-none w-80">
    <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden">
        <div class="p-4">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <span class="text-sm font-medium text-gray-900">{{ $order->nomor }}</span>
                    <p class="text-xs text-gray-500">{{ $order->tanggal->format('d/m/Y H:i') }}</p>
                </div>
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                    In Progress
                </span>
            </div>
            <div class="mb-3">
                <h3 class="text-sm font-medium text-gray-900">{{ $order->nama_barang }}</h3>
                <p class="text-xs text-gray-600 line-clamp-2">{{ $order->keluhan }}</p>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-xs font-medium {{ $order->prioritas === 'OCTO' ? 'text-yellow-600' : 'text-green-600' }}">
                    {{ $order->prioritas }}
                </span>
                <button onclick="showOrderDetail('{{ $order->id }}')"
                    class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    Detail →
                </button>
            </div>
        </div>
    </div>
</div>
@empty
<div class="w-full bg-gray-50 rounded-lg p-6 text-center">
    <p class="text-gray-500">Tidak ada order dalam proses</p>
</div>
@endforelse 