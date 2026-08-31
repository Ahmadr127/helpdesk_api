@extends('user.layouts.app')

@section('title', 'Edit Order Perbaikan')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
// Define previewImage function globally
function previewImage(input) {
    const previewImage = document.getElementById('preview-image');
    const noPhotoText = document.getElementById('no-photo-text');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewImage.classList.remove('hidden');
            noPhotoText.classList.add('hidden');
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<div class="container mx-auto max-w-6xl p-2 mt-6">
    <div class="bg-white rounded-lg shadow-lg mb-4">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-green-400 to-blue-300 p-3 rounded-t-lg">
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

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Barang</label>
                        <select name="jenis_barang"
                            class="w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="Umum"
                                {{ old('jenis_barang', $orderPerbaikan->jenis_barang) === 'Umum' ? 'selected' : '' }}>
                                Umum</option>
                            <option value="Inventaris"
                                {{ old('jenis_barang', $orderPerbaikan->jenis_barang) === 'Inventaris' ? 'selected' : '' }}>
                                Inventaris</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioritas</label>
                        <select name="prioritas"
                            class="w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-2 text-sm focus:ring-blue-500 focus:border-blue-500">
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Inventaris</label>
                        <input type="text" name="kode_inventaris"
                            value="{{ old('kode_inventaris', $orderPerbaikan->kode_inventaris) }}"
                            class="w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Barang</label>
                        <input type="text" name="nama_barang"
                            value="{{ old('nama_barang', $orderPerbaikan->nama_barang) }}"
                            class="w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                        <select name="lokasi"
                            class="w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            @foreach($locations as $location)
                            <option value="{{ $location->id }}"
                                {{ old('lokasi', $orderPerbaikan->lokasi) == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keluhan</label>
                        <textarea name="keluhan" rows="1"
                            class="w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-2 text-sm focus:ring-blue-500 focus:border-blue-500">{{ old('keluhan', $orderPerbaikan->keluhan) }}</textarea>
                    </div>
                </div>

                <!-- Foto Section -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto</label>
                    <div class="grid grid-cols-2 gap-3">
                        <!-- Photo Preview -->
                        <div class="bg-gray-100 p-2 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Preview Foto</h4>
                            <div class="bg-white rounded-lg overflow-hidden">
                                <div id="preview-container" class="flex items-center justify-center h-48">
                                    <img id="preview-image" 
                                        src="{{ $orderPerbaikan->foto ? Storage::url($orderPerbaikan->foto) : '' }}" 
                                        alt="Preview Photo" 
                                        class="w-full h-48 object-contain {{ $orderPerbaikan->foto ? '' : 'hidden' }}">
                                    <p id="no-photo-text" class="text-sm text-gray-500 {{ $orderPerbaikan->foto ? 'hidden' : '' }}">
                                        Tidak ada foto
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Upload New Photo -->
                        <div class="bg-gray-100 p-2 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Upload Foto Baru</h4>
                            <div class="mt-1 flex justify-center px-4 py-2 border-2 border-gray-300 border-dashed rounded-lg">
                                <div class="space-y-2 text-center">
                                    <svg class="mx-auto h-8 w-8 text-gray-400" stroke="currentColor" fill="none"
                                        viewBox="0 0 48 48">
                                        <path
                                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600 justify-center">
                                        <label for="foto"
                                            class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                            <span>Upload foto</span>
                                            <input id="foto" name="foto" type="file" class="sr-only" accept="image/*" onchange="previewImage(this)">
                                        </label>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-2 pt-2">
                <a href="{{ route('user.administrasi-umum.order-perbaikan.show', $orderPerbaikan) }}"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Batal
                </a>
                <button type="submit"
                    class="inline-flex items-center px-3 py-1.5 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Drag and drop functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.querySelector('.border-dashed');

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function handleDrop(e) {
        preventDefaults(e);
        const dt = e.dataTransfer;
        const file = dt.files[0];
        
        if (file && file.type.startsWith('image/')) {
            const input = document.getElementById('foto');
            input.files = dt.files;
            previewImage(input);
        }
    }

    function highlight(e) {
        preventDefaults(e);
        dropZone.classList.add('border-blue-500', 'bg-blue-50');
    }

    function unhighlight(e) {
        preventDefaults(e);
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');
    }

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight);
    });

    dropZone.addEventListener('drop', handleDrop);
});
</script>
@endsection