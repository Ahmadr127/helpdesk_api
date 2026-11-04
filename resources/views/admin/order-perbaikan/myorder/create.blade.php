@extends('admin.layouts.app')

@section('title', 'Buat Order Perbaikan')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="w-full">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- Header - GREEN theme for Maintenance -->
        <div class="bg-green-600 p-5 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-white flex items-center">
                    Buat Order Perbaikan Baru
                </h1>
            </div>
            <a href="{{ route('admin.order-perbaikan.myorder') }}"
               class="inline-flex items-center px-4 py-2 bg-white text-green-600 font-medium rounded-lg hover:bg-green-50 transition-colors shadow">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali
            </a>
        </div>

        @if(session('error'))
            <div class="m-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="m-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form - separate page (not modal) -->
        <form id="createOrderForm" action="{{ route('admin.order-perbaikan.myorder.store') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            <div class="space-y-6">
                <input type="hidden" name="tanggal" value="{{ $tanggal ?? now()->format('Y-m-d H:i:s') }}">
                <input type="hidden" name="department_id" value="{{ $departmentId ?? '' }}">

                <div class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit Pengaju <span class="text-gray-400">(otomatis)</span></label>
                    <div class="flex items-center px-3 py-2.5 border border-gray-200 bg-gray-50 rounded-lg">
                        <span class="text-sm font-medium text-gray-900">{{ $unitPengajuName ?? '-' }} ({{ $unitPengajuCode ?? '-' }})</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Department ID: {{ $departmentId ?? '-' }} • {{ $user->department ?? '-' }}</p>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keluhan / Deskripsi Kerusakan <span class="text-red-500">*</span></label>
                        <textarea name="keluhan" rows="4" required placeholder="Jelaskan keluhan / kerusakan..."
                                  class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">{{ old('keluhan') }}</textarea>
                        @error('keluhan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="prioritas-input" class="block text-sm font-medium text-gray-700 mb-1">Prioritas <span class="text-red-500">*</span></label>
                        <x-searchable-select name="prioritas" id="prioritas"
                            :options="['RENDAH' => 'RENDAH', 'SEDANG' => 'SEDANG', 'TINGGI/URGENT' => 'TINGGI/URGENT']"
                            :selected="old('prioritas')"
                            placeholder="Ketik untuk cari prioritas..." empty-label="— Pilih Prioritas —"
                            accent="green" :required="true" />
                        @error('prioritas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="kategori_order-input" class="block text-sm font-medium text-gray-700 mb-1">Kategori Order <span class="text-red-500">*</span></label>
                        <x-searchable-select name="kategori_order" id="kategori_order"
                            :options="$kategoriOrders->pluck('name', 'name')->toArray()" :selected="old('kategori_order')"
                            placeholder="Ketik untuk cari kategori..." empty-label="— Pilih Kategori —"
                            accent="green" :required="true" />
                        @error('kategori_order') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="lokasi-input" class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                        @if(isset($departmentLocation) && $departmentLocation)
                        <div class="flex items-center px-3 py-2.5 border border-gray-200 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="text-sm text-gray-900">
                                    {{ $departmentLocation->name }}
                                </p>
                                <p class="text-xs text-gray-500">Otomatis dari departemen Anda</p>
                            </div>
                        </div>
                        <input type="hidden" name="lokasi" value="{{ $departmentLocation->id }}">
                        @else
                        <x-searchable-select name="lokasi" id="lokasi"
                            :options="$locations->pluck('name', 'id')->toArray()" :selected="old('lokasi')"
                            placeholder="Ketik untuk cari lokasi..." empty-label="— Pilih Lokasi —"
                            accent="green" />
                        @endif
                        @error('lokasi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gedung</label>
                        <div class="flex items-center px-3 py-2.5 border border-gray-200 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="text-sm text-gray-900" id="gedung_display">
                                    {{ $departmentBuilding?->name ?? '-' }}
                                </p>
                                @if(isset($departmentBuilding) && $departmentBuilding)
                                <p class="text-xs text-gray-500">Otomatis dari departemen</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto (opsional)</label>
                        <x-image-upload name="fotos[]" accent="green" :max="3" :max-size="2"
                            accept="image/jpeg,image/png,image/jpg,image/gif" />
                        @error('foto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @error('fotos') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @error('fotos.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Footer actions -->
            <div class="flex justify-end space-x-3 mt-8 pt-6 border-t">
                <a href="{{ route('admin.order-perbaikan.myorder') }}"
                   class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    Batal
                </a>
                <button type="submit"
                        class="px-8 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-200 transition-colors shadow">
                    Simpan Order
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
