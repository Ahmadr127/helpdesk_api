@extends('administrasi-umum.layouts.app')

@section('title', 'Order Perbaikan - Dalam Proses')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="bg-gradient-to-r from-blue-200 to-white -mx-6 -mt-6 px-6 py-4 mb-6 border-b border-gray-200 rounded-t-lg">
            <div class="flex items-center">
                <a href="{{ route('administrasi-umum.order-perbaikan.index') }}"
                    class="mr-4 text-gray-700 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h2 class="text-2xl font-bold text-gray-700">Order Perbaikan - Dalam Proses</h2>
            </div>
        </div>

        <!-- Info badge -->
        <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Daftar order perbaikan yang sedang dalam pengerjaan
                    </p>
                </div>
            </div>
        </div>

        <!-- Statistics Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <p class="text-yellow-600 text-sm font-medium">Total Order Dalam Proses</p>
                <p class="text-2xl font-bold text-yellow-800">{{ $inProgressOrders }}</p>
            </div>
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <p class="text-blue-600 text-sm font-medium">Order Prioritas Tinggi</p>
                <p class="text-2xl font-bold text-blue-800">{{ $orders->where('prioritas', 'TINGGI/URGENT')->count() }}
                </p>
            </div>
            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                <p class="text-green-600 text-sm font-medium">Rata-rata Waktu Pengerjaan</p>
                <p class="text-2xl font-bold text-green-800">3.5 Hari</p>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="mb-6">
            <form id="filterForm" action="{{ route('administrasi-umum.order-perbaikan.in-progress') }}" method="GET"
                class="bg-gradient-to-r from-blue-200 to-gray-200 p-4 rounded-lg border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2" for="search">
                            Pencarian
                        </label>
                        <input type="text" name="search" placeholder="Cari order..."
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 filter-input"
                            value="{{ request('search') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2" for="prioritas">
                            Prioritas
                        </label>
                        <select name="prioritas"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 filter-input">
                            <option value="">Semua Prioritas</option>
                            <option value="TINGGI/URGENT"
                                {{ request('prioritas') == 'TINGGI/URGENT' ? 'selected' : '' }}>
                                Tinggi/Urgent</option>
                            <option value="SEDANG" {{ request('prioritas') == 'SEDANG' ? 'selected' : '' }}>Sedang
                            </option>
                            <option value="RENDAH" {{ request('prioritas') == 'RENDAH' ? 'selected' : '' }}>Rendah
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2" for="location_id">
                            Lokasi
                        </label>
                        <select name="location_id" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 filter-input">
                            <option value="">Semua Lokasi</option>
                            @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-blue-200 to-gray-200 border-b border-gray-200 rounded-t-lg">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">No.
                            Order</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                            Tanggal</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                            Pemohon</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                            Lokasi</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                            Nama Barang</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                            Keluhan</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                            Prioritas</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                            Penanggung Jawab</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                            Durasi</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($orders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $order->nomor }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $order->created_at->format('d/m/Y') }}
                            <div class="text-xs text-gray-400">{{ $order->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $order->nama_peminta }}
                            <div class="text-xs text-gray-400">{{ $order->creator->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ optional($order->location)->name ?? $order->lokasi }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $order->nama_barang }}
                            <div class="text-xs text-gray-400">{{ $order->kode_inventaris }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <div class="max-w-xs overflow-hidden">
                                {{ Str::limit($order->keluhan, 50) }}
                                @if(strlen($order->keluhan) > 50)
                                <button type="button" class="text-blue-600 hover:text-blue-800 text-xs ml-1"
                                    onclick="alert('{{ $order->keluhan }}')">Selengkapnya</button>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $order->prioritas === 'TINGGI/URGENT' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $order->prioritas === 'SEDANG' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $order->prioritas === 'RENDAH' ? 'bg-blue-100 text-blue-800' : '' }}">
                                {{ $order->prioritas }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $order->nama_penanggung_jawab }}
                            @if($order->started_at)
                            <div class="text-xs text-gray-400">Mulai: {{ $order->started_at->format('d/m/Y H:i') }}
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($order->started_at)
                            {{ $order->started_at->diffForHumans(null, true) }}
                            @else
                            -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="{{ route('administrasi-umum.order-perbaikan.show', $order->id) }}"
                                class="text-blue-600 hover:text-blue-900 inline-flex items-center">
                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                                Detail
                            </a>
                            <form action="{{ route('administrasi-umum.order-perbaikan.complete', $order->id) }}"
                                method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                    class="text-green-600 hover:text-green-900 inline-flex items-center">
                                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Selesai
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-10 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                                    </path>
                                </svg>
                                <p class="text-gray-600 font-medium">Tidak ada order dalam proses</p>
                                <p class="text-gray-400 text-sm mt-1">Semua order telah selesai dikerjakan</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    const filterInputs = document.querySelectorAll('.filter-input');

    function updateTableWithFilters() {
        const formData = new FormData(filterForm);
        const searchParams = new URLSearchParams(formData);

        // Show loading state
        document.querySelector('tbody').innerHTML = `
            <tr>
                <td colspan="10" class="px-6 py-4 text-center">
                    <div class="flex justify-center">
                        <svg class="animate-spin h-6 w-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </td>
            </tr>
        `;

        fetch(filterForm.action + '?' + searchParams.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTbody = doc.querySelector('tbody');
            const newPagination = doc.querySelector('.mt-4'); // Pagination container

            if (newTbody) {
                document.querySelector('tbody').replaceWith(newTbody);
            }
            
            const paginationContainer = document.querySelector('.mt-4');
            if (paginationContainer && newPagination) {
                paginationContainer.replaceWith(newPagination);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.querySelector('tbody').innerHTML = `
                <tr>
                    <td colspan="10" class="px-6 py-4 text-center text-red-500">
                        Terjadi kesalahan saat memuat data. Silakan coba lagi.
                    </td>
                </tr>
            `;
        });
    }

    // Handle text input with debouncing
    filterInputs.forEach(input => {
        if (input.type === 'text') {
            let debounceTimer;
            input.addEventListener('input', function(e) {
                e.preventDefault();
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    updateTableWithFilters();
                }, 500);
            });
        } else {
            // For other input types (select, etc.)
            input.addEventListener('change', function(e) {
                e.preventDefault();
                updateTableWithFilters();
            });
        }
    });

    // Prevent form submission
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        updateTableWithFilters();
    });
});
</script>

@endsection