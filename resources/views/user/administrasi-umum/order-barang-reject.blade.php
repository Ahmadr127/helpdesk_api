@extends('user.layouts.app')

@section('title', 'Permintaan Barang - Ditolak')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="min-h-screen bg-gradient-to-r from-green-50 to-blue-50 pb-24">
    <div class="container mx-auto px-4 py-6">
        <!-- Page Header -->
        <div class="mb-6 bg-gradient-to-r from-green-600 to-blue-300 rounded-lg p-6 shadow-sm">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-white">Permintaan Barang - Ditolak</h1>
                    <p class="mt-2 text-sm text-white">Daftar permintaan barang yang ditolak</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('user.administrasi-umum.order-barang') }}"
                        class="inline-flex items-center px-4 py-2 bg-white/10 hover:bg-gray-500 hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 text-white text-sm font-medium rounded-lg cursor-pointer">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                        </svg>
                        Kembali ke Permintaan Menunggu
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content Card -->
        <div class="bg-white rounded-lg shadow-sm">
            <!-- Header with Search -->
            <div class="p-6 border-b border-gray-100">
                <div class="flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="relative">
                            <input type="text" id="search" name="search"
                                class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200"
                                placeholder="Cari nomor permintaan, nama barang...">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <input type="date" id="start_date" name="start_date"
                                class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200"
                                placeholder="Tanggal Mulai">
                        </div>
                        <div>
                            <input type="date" id="end_date" name="end_date"
                                class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200"
                                placeholder="Tanggal Akhir">
                        </div>
                        <div>
                            <button id="filterDate"
                                class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <i class="fas fa-filter mr-1"></i> Terapkan Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nomor Permintaan
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama Barang
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Prioritas
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="ordersTableBody">
                        @include('user.administrasi-umum.order-perbaikan._table', ['orders' => $orders])
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="hidden p-12 text-center">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 text-blue-600 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak Ada Permintaan Ditolak</h3>
                <p class="text-gray-500">Belum ada permintaan yang ditolak saat ini</p>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</div>

<script>
let refreshInterval;

async function refreshOrderList() {
    try {
        const response = await fetch('{{ route("user.administrasi-umum.order-barang.reject") }}', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) throw new Error('Gagal menyegarkan daftar permintaan');

        const result = await response.json();
        const tableBody = document.getElementById('ordersTableBody');
        tableBody.innerHTML = result.html;

        // Toggle empty state
        const emptyState = document.getElementById('emptyState');
        const hasOrders = tableBody.querySelector('tr:not([data-empty])');
        emptyState.classList.toggle('hidden', hasOrders);
    } catch (error) {
        console.error('Error refreshing order list:', error);
    }
}

function showOrderDetail(id) {
    window.location.href = `/user/administrasi-umum/order-perbaikan/${id}`;
}

document.addEventListener('DOMContentLoaded', function() {
    refreshInterval = setInterval(refreshOrderList, 30000);
});

window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

// Fungsi pencarian
document.getElementById('search').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#ordersTableBody tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });

    // Toggle empty state berdasarkan hasil pencarian
    const hasVisibleRows = Array.from(rows).some(row => row.style.display !== 'none');
    document.getElementById('emptyState').classList.toggle('hidden', hasVisibleRows);
});

// Fungsi filter tanggal
document.getElementById('filterDate').addEventListener('click', async function() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    if (!startDate || !endDate) {
        alert('Silakan pilih tanggal mulai dan tanggal akhir');
        return;
    }

    try {
        const response = await fetch(
            `{{ route('user.administrasi-umum.order-barang.reject') }}?start_date=${startDate}&end_date=${endDate}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

        if (!response.ok) throw new Error('Gagal memfilter permintaan');

        const result = await response.json();
        const tableBody = document.getElementById('ordersTableBody');
        tableBody.innerHTML = result.html;

        // Toggle empty state
        const emptyState = document.getElementById('emptyState');
        const hasOrders = tableBody.querySelector('tr:not([data-empty])');
        emptyState.classList.toggle('hidden', hasOrders);
    } catch (error) {
        console.error('Error filtering orders:', error);
        alert('Terjadi kesalahan saat memfilter data');
    }
});
</script>
@endsection