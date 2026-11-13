@extends('admin.layouts.app')

@section('title', 'Daftar Order Perbaikan')

@section('content')
<div class="w-full">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Orders -->
        <div class="bg-blue-50 rounded-xl p-6 flex items-center justify-between cursor-pointer hover:shadow-lg transition duration-300 ease-in-out border border-blue-200"
            onclick="window.location.href='{{ route('admin.order-perbaikan.total') }}'">
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
        <div class="bg-yellow-50 rounded-xl p-6 flex items-center justify-between cursor-pointer hover:shadow-lg transition duration-300 ease-in-out border border-yellow-200"
            onclick="window.location.href='{{ route('admin.order-perbaikan.in-progress') }}'">
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
        <div class="bg-green-50 rounded-xl p-6 flex items-center justify-between cursor-pointer hover:shadow-lg transition duration-300 ease-in-out border border-green-200"
            onclick="window.location.href='{{ route('admin.order-perbaikan.confirmed') }}'">
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
        <div class="bg-red-50 rounded-xl p-6 flex items-center justify-between cursor-pointer hover:shadow-lg transition duration-300 ease-in-out border border-red-200"
            onclick="window.location.href='{{ route('admin.order-perbaikan.rejected') }}'">
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

    @include('admin.order-perbaikan._table', [
        'orders' => $orders,
        'filter' => $filter ?? null,
        'filterAction' => route('admin.order-perbaikan.index'),
        'title' => 'Daftar Order Perbaikan',
        'kategoris' => $kategoris ?? collect(),
        'locations' => $locations ?? collect(),
        'showLokasi' => true,
        'showStatus' => true,
    ])
</div>
@endsection