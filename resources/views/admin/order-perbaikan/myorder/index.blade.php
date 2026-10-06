@extends('admin.layouts.app')

@section('title', 'Order Saya')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Order Saya</h1>
            <p class="text-sm text-gray-500">Order perbaikan yang Anda ajukan sebagai pengguna</p>
        </div>
        @if(auth()->user()->hasPermission('myorder'))
        <a href="{{ route('admin.order-perbaikan.myorder.create') }}"
            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            Buat Order
        </a>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
        <form action="{{ route('admin.order-perbaikan.myorder') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Cari Order</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-white/80 text-gray-900"
                        placeholder="Nomor, barang, atau keluhan">
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="all" {{ request('status', 'all') == 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Dibuka</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Dalam Proses</option>
                        <option value="tutup" {{ request('status') == 'tutup' ? 'selected' : '' }}>Tutup</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Dikonfirmasi</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>
            </div>
            <div class="space-x-2">
                <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Terapkan Filter
                </button>
                <a href="{{ route('admin.order-perbaikan.myorder') }}"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            @include('admin.order-perbaikan._table', ['orders' => $orders, 'myticketActions' => true])
        </div>
    </div>

    @include('admin.order-perbaikan.myorder.component.modal-view')
    @include('admin.order-perbaikan.myorder.component.delete-confirm')

    <div class="mt-6">
        {{ $orders->links() }}
    </div>
</div>
@endsection
