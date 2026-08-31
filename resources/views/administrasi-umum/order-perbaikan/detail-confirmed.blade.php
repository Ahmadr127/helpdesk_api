@extends('administrasi-umum.layouts.app')

@section('title', 'Detail Order Perbaikan Terkonfirmasi')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-8xl">
    <!-- Back button and status badge -->
    <div class="flex justify-between items-center mb-6">
        <a href="{{ route('administrasi-umum.order-perbaikan.confirmed') }}"
            class="inline-flex items-center text-base font-medium text-blue-600 hover:text-blue-800 transition px-4 py-2 bg-white rounded-lg shadow-sm">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
            Kembali
        </a>
        <span class="px-4 py-2 text-sm font-medium rounded-full bg-blue-100 text-blue-800 flex items-center">
            <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
            Terkonfirmasi
        </span>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
        <!-- Header -->
        <div class="px-8 py-6 border-b border-gray-100 bg-blue-700">
            <h2 class="text-2xl font-bold text-white">Order Perbaikan #{{ $order->nomor }}</h2>
        </div>

        <!-- Main content -->
        <div class="p-8">
            <!-- Primary details -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-10">
                <div>
                    <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Tanggal</p>
                    <p class="text-base text-gray-800">{{ $order->created_at->format('d-m-Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Peminta</p>
                    <p class="text-base text-gray-800">{{ $order->creator->name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Prioritas</p>
                    <p class="text-base text-gray-800">{{ $order->prioritas }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Lokasi</p>
                    <p class="text-base text-gray-800">{{ $order->location->name ?? '-' }}</p>
                </div>
            </div>

            <!-- Order details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8 mb-10">
                <!-- Left Column -->
                <div>
                    <div class="mb-8">
                        <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Unit Proses</p>
                        <p class="text-base text-gray-800">{{ $order->unit_proses }}</p>
                    </div>
                    
                    <div class="mb-8">
                        <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Unit Penerima</p>
                        <p class="text-base text-gray-800">{{ $order->unit_penerima }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Jenis Barang</p>
                        <p class="text-base text-gray-800">{{ $order->jenis_barang }}</p>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <div class="mb-8">
                        <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Nama Barang</p>
                        <p class="text-base text-gray-800">{{ $order->nama_barang }}</p>
                    </div>
                    
                    <div class="mb-8">
                        <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Kode Inventaris</p>
                        <p class="text-base text-gray-800">{{ $order->kode_inventaris ?: '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <!-- Keluhan -->
                    <div class="mb-10">
                        <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Keluhan</p>
                        <div class="text-base text-gray-800 bg-gray-50 p-5 rounded-lg border border-gray-200">
                            {{ $order->keluhan }}
                        </div>
                    </div>

                    <!-- Foto -->
                    @if($order->foto)
                    <div class="mb-10">
                        <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Foto</p>
                        <div class="mt-2">
                            <img src="{{ Storage::url($order->foto) }}" alt="Foto Order"
                                class="max-h-80 rounded-lg shadow-sm border border-gray-200">
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Timeline Section -->
                <div class="bg-gray-50 rounded-lg p-6 h-fit">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Timeline Order</h3>
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            @foreach($order->history as $history)
                            <li>
                                <div class="relative pb-8">
                                    @if(!$loop->last)
                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white
                                                {{ match($history->status) {
                                                    'open' => 'bg-blue-500',
                                                    'in_progress' => 'bg-yellow-500',
                                                    'confirmed' => 'bg-green-500',
                                                    'rejected' => 'bg-red-500',
                                                    default => 'bg-gray-500'
                                                } }}">
                                                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ match($history->status) {
                                                        'open' => 'Order Dibuat',
                                                        'in_progress' => 'Dalam Proses',
                                                        'confirmed' => 'Order Dikonfirmasi',
                                                        'rejected' => 'Order Ditolak',
                                                        default => ucfirst($history->status)
                                                    } }}
                                                </div>
                                                <p class="mt-0.5 text-sm text-gray-500">
                                                    {{ $history->created_at->format('d M Y H:i') }}
                                                </p>
                                            </div>
                                            @if($history->keterangan)
                                            <div class="mt-2 text-sm text-gray-700">
                                                {{ $history->keterangan }}
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Admin Response Section -->
            <div class="mt-10 border-t border-blue-100 pt-8">
                <h3 class="text-xl font-medium text-blue-800 mb-6">Respon Admin</h3>
                <div class="bg-blue-50 rounded-lg p-6 border border-blue-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-blue-700 mb-2">Follow Up</label>
                            <p class="text-base text-gray-900">{{ $order->follow_up }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-blue-700 mb-2">Tanggal Konfirmasi</label>
                            <p class="text-base text-gray-900">{{ $order->updated_at->format('d-m-Y H:i') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-blue-700 mb-2">Nama Penanggung Jawab</label>
                            <p class="text-base text-gray-900">{{ $order->nama_penanggung_jawab }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection