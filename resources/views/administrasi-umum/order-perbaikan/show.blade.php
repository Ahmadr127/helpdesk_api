@extends('administrasi-umum.layouts.app')

@section('title', 'Detail Order')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Back Button -->
    <div class="mb-4">
        <a href="{{ route('administrasi-umum.order-perbaikan.index') }}"
            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
            Kembali
        </a>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <!-- Header with status badge -->
        <div class="relative bg-gradient-to-r from-blue-300 to-gray-200 px-6 py-4">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div class="flex flex-col">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-700">Order #{{ $orderPerbaikan->nomor }}</h1>
                    <p class="text-gray-600 text-sm mt-1">Dibuat pada
                        {{ $orderPerbaikan->created_at->format('d M Y, H:i') }}</p>
                </div>
                <div class="mt-2 md:mt-0">
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $orderPerbaikan->status_color }}-100 text-{{ $orderPerbaikan->status_color }}-800 border border-{{ $orderPerbaikan->status_color }}-200">
                        <span class="w-2 h-2 rounded-full bg-{{ $orderPerbaikan->status_color }}-500 mr-2"></span>
                        {{ $orderPerbaikan->status_label }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="p-6">
            <!-- 2-column grid layout for all cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Order Info Card -->
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden h-full">
                    <div class="px-4 py-3 bg-gradient-to-r from-blue-300 to-gray-200 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-700">Informasi Order</h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-gray-500">Unit Proses</p>
                                <p class="font-medium">{{ $orderPerbaikan->unit_proses_name }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-gray-500">Prioritas</p>
                                <p>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ match($orderPerbaikan->prioritas) {
                                            'RENDAH' => 'bg-green-100 text-green-800',
                                            'SEDANG' => 'bg-yellow-100 text-yellow-800',
                                            'TINGGI/URGENT' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        } }}">
                                        {{ $orderPerbaikan->prioritas }}
                                    </span>
                                </p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-gray-500">Tanggal Order</p>
                                <p class="font-medium">{{ $orderPerbaikan->tanggal->format('d M Y, H:i') }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-gray-500">Dibuat Oleh</p>
                                <p class="font-medium">{{ $orderPerbaikan->creator->name }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Item Details Card -->
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden h-full">
                    <div class="px-4 py-3 bg-gradient-to-r from-blue-300 to-gray-200 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-700">Detail Barang</h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-gray-500">Jenis Barang</p>
                                <p class="font-medium">{{ $orderPerbaikan->jenis_barang }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-gray-500">Kode Inventaris</p>
                                <p class="font-medium">{{ $orderPerbaikan->kode_inventaris }}</p>
                            </div>
                            <div class="space-y-1 md:col-span-2">
                                <p class="text-sm font-medium text-gray-500">Nama Barang</p>
                                <p class="font-medium">{{ $orderPerbaikan->nama_barang }}</p>
                            </div>
                            <div class="space-y-1 md:col-span-2">
                                <p class="text-sm font-medium text-gray-500">Keluhan</p>
                                <p class="font-medium text-gray-700 bg-gray-50 p-3 rounded-md">
                                    {{ $orderPerbaikan->keluhan }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Photo Card -->
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden h-full">
                    <div class="px-4 py-3 bg-gradient-to-r from-blue-300 to-gray-200 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-700">Foto Barang</h2>
                    </div>
                    <div class="p-4">
                        @if($orderPerbaikan->foto)
                        <div
                            class="group relative rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center">
                            <img src="{{ Storage::url($orderPerbaikan->foto) }}" alt="Foto Order"
                                class="w-full h-auto object-contain max-h-80">
                            <div
                                class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-60 flex items-center justify-center transition-all duration-300">
                                <a href="{{ Storage::url($orderPerbaikan->foto) }}" target="_blank"
                                    class="p-2 bg-white rounded-full opacity-0 group-hover:opacity-100 transition-all duration-300 transform scale-50 group-hover:scale-100">
                                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7">
                                        </path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                        @else
                        <div
                            class="flex flex-col items-center justify-center h-40 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Tidak ada foto</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Status Update Form -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
                    <div class="bg-gradient-to-r from-blue-300 to-gray-200 px-6 py-3 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-700">Update Status</h3>
                    </div>
                    <div class="p-6">
                        <form action="{{ route('administrasi-umum.order-perbaikan.update-status', $orderPerbaikan) }}"
                            method="POST">
                            @csrf
                            @method('PUT')
                            <div class="grid grid-cols-1 gap-4">
                                @if($orderPerbaikan->status === 'open')
                                <!-- Fields for initial processing -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <select name="status" required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="in_progress">In Progress</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Penanggung
                                        Jawab</label>
                                    <input type="text" name="nama_penanggung_jawab" required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Masukkan nama penanggung jawab">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tindak Lanjut</label>
                                    <textarea name="follow_up" required rows="3"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Masukkan tindak lanjut yang akan dilakukan..."></textarea>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" name="action" value="in_progress"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z">
                                            </path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Proses Order
                                    </button>
                                </div>
                                @else
                                <!-- Fields for status update -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <select name="status" required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Pilih Status</option>
                                        @if($orderPerbaikan->status === 'in_progress')
                                        <option value="confirmed">Konfirmasi</option>
                                        <option value="rejected">Tolak</option>
                                        @endif
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tindak Lanjut</label>
                                    <textarea name="follow_up" required rows="3"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Masukkan tindak lanjut yang dilakukan...">{{ $orderPerbaikan->follow_up }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Penanggung Jawab</label>
                                    <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">
                                        {{ $orderPerbaikan->nama_penanggung_jawab }}</p>
                                    <input type="hidden" name="nama_penanggung_jawab"
                                        value="{{ $orderPerbaikan->nama_penanggung_jawab }}">
                                </div>
                                <div class="flex justify-end space-x-3">
                                    @if($orderPerbaikan->status === 'in_progress')
                                    <button type="submit" name="action" value="update"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                            </path>
                                        </svg>
                                        Update
                                    </button>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- History Timeline (Full Width) -->
            <div class="mt-6 bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 bg-gradient-to-r from-blue-300 to-gray-200 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Timeline</h2>
                </div>
                <div class="p-4">
                    <div class="relative flex items-center justify-between w-full py-2">
                        @foreach($orderPerbaikan->history as $index => $history)
                        <div class="relative flex flex-col items-center">
                            <div class="w-8 h-8 flex items-center justify-center rounded-full {{ match($history->status) {
                                'open' => 'bg-gradient-to-r from-blue-500 to-blue-600',
                                'in_progress' => 'bg-gradient-to-r from-yellow-500 to-yellow-600',
                                'rejected' => 'bg-gradient-to-r from-red-500 to-red-600',
                                'confirmed' => 'bg-gradient-to-r from-green-500 to-green-600',
                                default => 'bg-gradient-to-r from-gray-500 to-gray-600'
                            } }} text-white">
                                @if($history->status === 'confirmed')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                @elseif($history->status === 'rejected')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                @elseif($history->status === 'in_progress')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                @endif
                            </div>
                            <div class="mt-2 text-center">
                                <p class="text-xs text-gray-500">{{ $history->created_at->format('d M Y H:i') }}</p>
                                <p class="text-sm font-medium text-gray-700">{{ $history->status_label }}</p>
                            </div>

                            <!-- Connecting Line -->
                            @if(!$loop->last)
                            <div class="absolute top-4 left-full w-full h-0.5 bg-gray-200"></div>
                            @endif

                            <!-- Hover Card -->
                            <div
                                class="absolute bottom-full mb-2 w-48 bg-white rounded-lg shadow-lg p-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none transform -translate-x-1/2 left-1/2">
                                <div class="text-xs">
                                    <p class="font-medium text-gray-900">{{ $history->keterangan }}</p>
                                    <p class="text-gray-600 mt-1">Oleh: {{ $history->creator->name }}</p>
                                    @if($orderPerbaikan->nama_penanggung_jawab && $history->status !== 'open')
                                    <p class="text-gray-600">PJ: {{ $orderPerbaikan->nama_penanggung_jawab }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection