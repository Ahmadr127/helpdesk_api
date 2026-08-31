@extends('administrasi-umum.layouts.app')

@section('title', 'Order Perbaikan - Rejected')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <!-- Header with back button -->
        <div
            class="bg-gradient-to-r from-blue-200 to-white -mx-6 -mt-6 px-6 py-4 mb-6 border-b border-gray-200 rounded-t-lg">
            <div class="flex items-center">
                <a href="{{ route('administrasi-umum.order-perbaikan.index') }}"
                    class="mr-4 text-gray-700 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <h2 class="text-2xl font-bold text-gray-700">Order Perbaikan - Rejected</h2>
            </div>
        </div>

        <!-- Info badge -->
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">
                        Halaman ini menampilkan order perbaikan yang telah ditolak oleh admin.
                    </p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6">
            <form id="filterForm" action="{{ route('administrasi-umum.order-perbaikan.rejected') }}" method="GET"
                class="bg-gradient-to-r from-blue-200 to-gray-200 p-4 rounded-lg border border-gray-200 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="search">
                        Pencarian
                    </label>
                    <input type="text" name="search" id="search"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 filter-input"
                        placeholder="Cari nomor, barang..." value="{{ request('search') }}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="date_from">
                        Dari Tanggal
                    </label>
                    <input type="date" name="date_from" id="date_from"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 filter-input"
                        value="{{ request('date_from') }}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="date_to">
                        Sampai Tanggal
                    </label>
                    <input type="date" name="date_to" id="date_to"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 filter-input"
                        value="{{ request('date_to') }}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        &nbsp;
                    </label>
                    <button type="button" id="resetFilter"
                        class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-blue-200 hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-blue-200 to-gray-200 border-b border-gray-200 rounded-t-lg">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                            Nomor Order
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                            Tanggal
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                            Peminta
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                            Barang
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                            Prioritas
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-20">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($orders as $order)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $order->nomor }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $order->tanggal->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $order->creator->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $order->nama_barang }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="px-2 py-1 text-xs rounded-full 
                                {{ $order->prioritas === 'OCTO' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                {{ $order->prioritas }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('administrasi-umum.order-perbaikan.show', $order) }}"
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
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                                    </path>
                                </svg>
                                <p class="text-gray-600 font-medium">Tidak ada order perbaikan yang ditolak</p>
                                <p class="text-gray-400 text-sm mt-1">Data akan muncul di sini ketika ada order yang
                                    ditolak</p>
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
    const resetFilterButton = document.getElementById('resetFilter');

    function updateTableWithFilters() {
        const formData = new FormData(filterForm);
        const searchParams = new URLSearchParams(formData);

        // Show loading state
        document.querySelector('tbody').innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-4 text-center">
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
                    <td colspan="6" class="px-6 py-4 text-center text-red-500">
                        Terjadi kesalahan saat memuat data. Silakan coba lagi.
                    </td>
                </tr>
            `;
        });
    }

    // Reset filter functionality
    resetFilterButton.addEventListener('click', function() {
        // Clear all inputs
        filterInputs.forEach(input => {
            input.value = '';
        });
        // Update URL without parameters
        window.history.pushState({}, '', filterForm.action);
        // Trigger table update
        updateTableWithFilters();
    });

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
            // For other input types (date, etc.)
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