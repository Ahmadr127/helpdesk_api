@foreach($recentOrders as $order)
<a href="{{ route('administrasi-umum.order-perbaikan.show', $order->id) }}" class="block group">
    <div
        class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-all duration-300 relative overflow-hidden">
        <!-- Accent Border -->
        <div class="absolute top-0 left-0 w-full h-1 
                @switch($order->status)
                    @case('open')
                        bg-green-500
                        @break
                    @case('in_progress')
                        bg-yellow-500
                        @break
                    @default
                        bg-gray-500
                @endswitch">
        </div>

        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-900">No. Order: {{ $order->nomor }}</span>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-medium
                    @switch($order->status)
                        @case('open')
                            bg-green-100 text-green-800
                            @break
                        @case('in_progress')
                            bg-yellow-100 text-yellow-800
                            @break
                        @default
                            bg-gray-100 text-gray-800
                    @endswitch">
                @switch($order->status)
                @case('open')
                Dibuka
                @break
                @case('in_progress')
                Dalam proses
                @break
                @default
                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                @endswitch
            </span>
        </div>

        <div class="mb-4">
            <h4 class="text-sm font-semibold text-gray-900 mb-2">{{ $order->title }}</h4>
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div class="text-xs">
                    <p class="text-gray-500 mb-1">Peminta:</p>
                    <p class="font-medium text-gray-900">{{ $order->nama_peminta }}</p>
                </div>
                <div class="text-xs">
                    <p class="text-gray-500 mb-1">Lokasi:</p>
                    <p class="font-medium text-gray-900">{{ $order->location->name }}</p>
                </div>
                <div class="text-xs col-span-2">
                    <p class="text-gray-500 mb-1">Barang:</p>
                    <p class="font-medium text-gray-900">{{ $order->nama_barang }}</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 line-clamp-2">{{ $order->description }}</p>
        </div>

        <div class="flex items-center justify-between text-xs border-t border-gray-100 pt-4">
            <div class="flex items-center space-x-4">
                <div class="flex items-center text-gray-500">
                    <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ $order->created_at->diffForHumans() }}
                </div>
                @if($order->prioritas)
                <div class="flex items-center">
                    <span class="px-2 py-1 rounded-full text-xs font-medium 
                        {{ $order->prioritas === 'TINGGI/URGENT' ? 'bg-red-100 text-red-800' : 
                           ($order->prioritas === 'SEDANG' ? 'bg-yellow-100 text-yellow-800' : 
                           'bg-blue-100 text-blue-800') }}">
                        {{ $order->prioritas }}
                    </span>
                </div>
                @endif
            </div>

            <div
                class="text-blue-600 group-hover:text-blue-800 font-medium transition-colors duration-200 flex items-center">
                Lihat Detail
                <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
        </div>

        <!-- Hover Effect Overlay -->
        <div
            class="absolute inset-0 bg-gradient-to-r from-blue-50 to-transparent opacity-0 group-hover:opacity-10 transition-opacity duration-300">
        </div>
    </div>
</a>
@endforeach