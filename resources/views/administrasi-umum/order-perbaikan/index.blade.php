@extends('administrasi-umum.layouts.app')

@section('title', 'Daftar Order Perbaikan')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Orders -->
        <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl p-6 flex items-center justify-between cursor-pointer hover:shadow-lg transition duration-300 ease-in-out border border-blue-200"
            onclick="window.location.href='{{ route('administrasi-umum.order-perbaikan.total') }}'">
            <div class="flex items-center">
                <div class="bg-blue-500 rounded-full p-3 shadow-md">
                    <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-600 text-sm font-medium">Total Order</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $totalOrders }}</h3>
                </div>
            </div>
        </div>

        <!-- In Progress Orders -->
        <div class="bg-gradient-to-r from-yellow-50 to-yellow-100 rounded-xl p-6 flex items-center justify-between cursor-pointer hover:shadow-lg transition duration-300 ease-in-out border border-yellow-200"
            onclick="window.location.href='{{ route('administrasi-umum.order-perbaikan.in-progress') }}'">
            <div class="flex items-center">
                <div class="bg-yellow-500 rounded-full p-3 shadow-md">
                    <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-600 text-sm font-medium">Dalam Proses</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $inProgressOrders }}</h3>
                </div>
            </div>
        </div>

        <!-- Confirmed Orders -->
        <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-xl p-6 flex items-center justify-between cursor-pointer hover:shadow-lg transition duration-300 ease-in-out border border-green-200"
            onclick="window.location.href='{{ route('administrasi-umum.order-perbaikan.confirmed') }}'">
            <div class="flex items-center">
                <div class="bg-green-500 rounded-full p-3 shadow-md">
                    <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-600 text-sm font-medium">Dikonfirmasi</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $confirmedOrders }}</h3>
                </div>
            </div>
        </div>

        <!-- Rejected Orders -->
        <div class="bg-gradient-to-r from-red-50 to-red-100 rounded-xl p-6 flex items-center justify-between cursor-pointer hover:shadow-lg transition duration-300 ease-in-out border border-red-200"
            onclick="window.location.href='{{ route('administrasi-umum.order-perbaikan.rejected') }}'">
            <div class="flex items-center">
                <div class="bg-red-500 rounded-full p-3 shadow-md">
                    <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-600 text-sm font-medium">Ditolak</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $rejectedOrders }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8 border border-gray-200">
        <div class="p-5 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-700 flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                    </path>
                </svg>
                Filter Pencarian
            </h3>
        </div>
        <div class="p-5">
            <form id="filterForm" action="{{ route('administrasi-umum.order-perbaikan.index') }}" method="GET"
                class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="search">
                        Pencarian
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" name="search" id="search"
                            class="pl-10 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 filter-input"
                            placeholder="Cari nomor, barang..." value="{{ request('search') }}">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="date_from">
                        Dari Tanggal
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                        </div>
                        <input type="date" name="date_from" id="date_from"
                            class="pl-10 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 filter-input"
                            value="{{ request('date_from') }}">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="date_to">
                        Sampai Tanggal
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                        </div>
                        <input type="date" name="date_to" id="date_to"
                            class="pl-10 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 filter-input"
                            value="{{ request('date_to') }}">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="status">
                        Status
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <select name="status" id="status"
                            class="pl-10 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 filter-input">
                            <option value="">Semua Status</option>
                            <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Dibuka</option>
                            <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Dalam
                                Proses</option>
                            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed
                            </option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected
                            </option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="prioritas">
                        Prioritas
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <select name="prioritas" id="prioritas"
                            class="pl-10 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 filter-input">
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
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="location_id">
                        Lokasi
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <select name="location_id" id="location_id"
                            class="pl-10 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 filter-input">
                            <option value="">Semua Lokasi</option>
                            @foreach($locations as $location)
                            <option value="{{ $location->id }}"
                                {{ request('location_id') == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="md:col-span-3 lg:col-span-6 flex justify-end">
                    <button type="submit"
                        class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium py-2.5 px-5 rounded-lg transition duration-150 ease-in-out inline-flex items-center shadow-md">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                            </path>
                        </svg>
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Combined Orders Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8 border border-gray-200">
        <div
            class="p-5 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-700 flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                    </path>
                </svg>
                Daftar Order Perbaikan
            </h3>
            <div class="text-sm text-gray-500">
                Total: <span class="font-semibold">{{ $orders->total() }}</span> order
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nomor Order
                        </th>
                        <th scope="col"
                            class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tanggal
                        </th>
                        <th scope="col"
                            class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Peminta
                        </th>
                        <th scope="col"
                            class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Barang
                        </th>
                        <th scope="col"
                            class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Lokasi
                        </th>
                        <th scope="col"
                            class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Prioritas
                        </th>
                        <th scope="col"
                            class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col"
                            class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
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
                            {{ $order->nama_peminta }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $order->nama_barang }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $order->location ? $order->location->name : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1.5 text-xs font-medium rounded-full shadow-sm 
                                {{ match($order->prioritas) {
                                    'TINGGI/URGENT' => 'bg-gradient-to-r from-red-50 to-red-100 text-red-800 border border-red-200',
                                    'SEDANG' => 'bg-gradient-to-r from-yellow-50 to-yellow-100 text-yellow-800 border border-yellow-200',
                                    'RENDAH' => 'bg-gradient-to-r from-blue-50 to-blue-100 text-blue-800 border border-blue-200',
                                    default => 'bg-gradient-to-r from-gray-50 to-gray-100 text-gray-800 border border-gray-200'
                                } }}">
                                {{ match($order->prioritas) {
                                    'TINGGI/URGENT' => 'Tinggi/Urgent',
                                    'SEDANG' => 'Sedang',
                                    'RENDAH' => 'Rendah',
                                    default => $order->prioritas
                                } }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1.5 text-xs font-medium rounded-full shadow-sm
                                {{ match($order->status) {
                                    'open' => 'bg-gradient-to-r from-blue-50 to-blue-100 text-blue-800 border border-blue-200',
                                    'in_progress' => 'bg-gradient-to-r from-yellow-50 to-yellow-100 text-yellow-800 border border-yellow-200',
                                    'confirmed' => 'bg-gradient-to-r from-green-50 to-green-100 text-green-800 border border-green-200',
                                    'rejected' => 'bg-gradient-to-r from-red-50 to-red-100 text-red-800 border border-red-200',
                                    default => 'bg-gradient-to-r from-gray-50 to-gray-100 text-gray-800 border border-gray-200'
                                } }}">
                                {{ match($order->status) {
                                    'open' => 'Dibuka',
                                    'in_progress' => 'Dalam Diproses',
                                    'confirmed' => 'Dikonfirmasi',
                                    'rejected' => 'Ditolak',
                                    default => $order->status
                                } }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('administrasi-umum.order-perbaikan.show', $order) }}"
                                class="text-blue-600 hover:text-blue-900 inline-flex items-center hover:bg-blue-50 px-2.5 py-1.5 rounded-lg transition-colors duration-150">
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
                        <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500 bg-white">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                    </path>
                                </svg>
                                <p class="font-medium">Tidak ada order yang ditemukan</p>
                                <p class="text-gray-400 mt-1">Coba ubah filter pencarian Anda</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $orders->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when filter inputs change
    const filterForm = document.getElementById('filterForm');
    const filterInputs = document.querySelectorAll('.filter-input');

    filterInputs.forEach(input => {
        if (input.type === 'text') {
            // For text inputs, add debounce
            let debounceTimer;
            input.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    filterForm.submit();
                }, 500);
            });
        } else {
            // For select and date inputs
            input.addEventListener('change', function() {
                filterForm.submit();
            });
        }
    });
});
</script>
@endpush