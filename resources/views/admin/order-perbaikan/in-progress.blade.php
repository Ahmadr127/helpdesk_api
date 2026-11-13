@extends('admin.layouts.app')

@section('title', 'Order Perbaikan - Dalam Proses')

@section('content')
<div class="w-full">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="bg-white -mx-6 -mt-6 px-6 py-4 mb-6 border-b border-gray-200 rounded-t-lg">
            <div class="flex items-center">
                <a href="{{ route('admin.order-perbaikan.index') }}"
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

        @include('admin.order-perbaikan._table', [
            'orders' => $orders,
            'filter' => $filter ?? null,
            'filterAction' => route('admin.order-perbaikan.in-progress'),
            'title' => 'Order Perbaikan - Dalam Proses',
            'kategoris' => $kategoris ?? collect(),
            'locations' => $locations ?? collect(),
            'lockedStatus' => $lockedStatus ?? 'in_progress',
            'showLokasi' => true,
            'showKeluhan' => true,
            'showPenanggungJawab' => true,
            'showDurasi' => true,
            'showComplete' => true,
            'emptyTitle' => 'Tidak ada order dalam proses',
            'emptySubtitle' => 'Semua order telah selesai dikerjakan',
        ])
    </div>
</div>

@endsection