@extends('user.layouts.app')

@section('title', 'Edit Order Perbaikan')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container mx-auto max-w-6xl p-2 mt-6">
    <div class="bg-white rounded-lg shadow-lg mb-4">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-green-600 to-emerald-400 p-3 rounded-t-lg">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-semibold text-white">Edit Order Perbaikan</h1>
                <a href="{{ route('user.administrasi-umum.order-perbaikan.show', $orderPerbaikan) }}"
                    class="inline-flex items-center px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-sm font-medium rounded-md transition duration-150 ease-in-out">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali
                </a>
            </div>
        </div>

        <!-- Form Section -->
        <form action="{{ route('user.administrasi-umum.order-perbaikan.update', $orderPerbaikan) }}" method="POST"
            enctype="multipart/form-data" class="p-4 space-y-4" id="editOrderForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="department_id" value="{{ $departmentId ?? $orderPerbaikan->department_id ?? '' }}">

            @if(session('error'))
            <div class="mb-3 bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded relative text-sm">
                {{ session('error') }}
            </div>
            @endif

            @if($errors->any())
            <div class="mb-3 bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded relative text-sm">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Order Information (Read Only) -->
            <div class="grid grid-cols-2 gap-3 bg-gray-50 p-3 rounded-lg">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Order</label>
                    <input type="text" value="{{ $orderPerbaikan->nomor }}"
                        class="w-full bg-gray-100 border border-gray-300 rounded-md shadow-sm py-1.5 px-2 text-sm text-gray-700"
                        readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                    <input type="text" value="{{ $orderPerbaikan->tanggal->format('d/m/Y H:i') }}"
                        class="w-full bg-gray-100 border border-gray-300 rounded-md shadow-sm py-1.5 px-2 text-sm text-gray-700"
                        readonly>
                </div>
            </div>

            <!-- Editable Fields -->
            <div class="bg-gray-50 p-3 rounded-lg space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Peminta</label>
                    <input type="text" value="{{ auth()->user()->name }}"
                        class="w-full bg-gray-100 border border-gray-300 rounded-md shadow-sm py-1.5 px-2 text-sm text-gray-700"
                        readonly>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keluhan</label>
                    <textarea name="keluhan" rows="2"
                        class="w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-2 text-sm focus:ring-green-500 focus:border-green-500">{{ old('keluhan', $orderPerbaikan->keluhan) }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioritas</label>
                        <select name="prioritas"
                            class="w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-2 text-sm focus:ring-green-500 focus:border-green-500">
                            <option value="RENDAH"
                                {{ old('prioritas', $orderPerbaikan->prioritas) === 'RENDAH' ? 'selected' : '' }}>RENDAH
                            </option>
                            <option value="SEDANG"
                                {{ old('prioritas', $orderPerbaikan->prioritas) === 'SEDANG' ? 'selected' : '' }}>SEDANG
                            </option>
                            <option value="TINGGI/URGENT"
                                {{ old('prioritas', $orderPerbaikan->prioritas) === 'TINGGI/URGENT' ? 'selected' : '' }}>
                                TINGGI/URGENT</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    {{-- <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Barang</label>
                        <input type="text" name="nama_barang"
                            value="{{ old('nama_barang', $orderPerbaikan->nama_barang) }}"
                            class="w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-2 text-sm focus:ring-green-500 focus:border-green-500">
                    </div> --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Order <span class="text-red-500">*</span></label>
                        <select name="kategori_order" required
                            class="w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-2 text-sm focus:ring-green-500 focus:border-green-500">
                            <option value="">Pilih Kategori</option>
                            @foreach($kategoriOrders as $kat)
                            <option value="{{ $kat->name }}"
                                {{ old('kategori_order', $orderPerbaikan->kategori_order) == $kat->name ? 'selected' : '' }}>
                                {{ $kat->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                        <select name="lokasi"
                            class="w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-2 text-sm focus:ring-green-500 focus:border-green-500">
                            <option value="">Pilih Lokasi (opsional)</option>
                            @foreach($locations as $location)
                            <option value="{{ $location->id }}"
                                {{ old('lokasi', $orderPerbaikan->lokasi ?? (isset($departmentLocation) && $departmentLocation ? $departmentLocation->id : null)) == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gedung</label>
                        <div class="flex items-center px-3 py-1.5 border border-gray-200 bg-gray-100 rounded-md">
                            <div class="flex-1">
                                <p class="text-sm text-gray-900">
                                    {{ $departmentBuilding?->name ?? '-' }}
                                </p>
                                @if(isset($departmentBuilding) && $departmentBuilding)
                                <p class="text-xs text-gray-500">Otomatis dari departemen</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Foto Section -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto</label>
                    <x-image-upload name="fotos[]" accent="green" :max="3" :max-size="2"
                        accept="image/jpeg,image/png,image/jpg,image/gif"
                        :existing="$orderPerbaikan->foto_urls"
                        existing-label="Foto order saat ini" />
                    @error('foto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    @error('fotos') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    @error('fotos.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-2 pt-2">
                <a href="{{ route('user.administrasi-umum.order-perbaikan.show', $orderPerbaikan) }}"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Batal
                </a>
                <button type="submit"
                    class="inline-flex items-center px-3 py-1.5 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
