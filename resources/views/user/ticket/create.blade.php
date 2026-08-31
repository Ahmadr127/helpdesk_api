@extends('user.layouts.app')

@section('title', 'Buat Tiket Baru')

@section('content')
<!-- Include the notification partial -->
@include('user.partials.notification')

<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">

        @if(!auth()->user()->department)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Anda belum memiliki data departemen. Silakan lengkapi profil Anda terlebih dahulu di
                        <a href="{{ route('user.settings') }}"
                            class="font-medium underline text-yellow-700 hover:text-yellow-600">
                            pengaturan profil
                        </a>.
                    </p>
                </div>
            </div>
        </div>
        @endif

        <div class="">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-green-600 to-blue-300 px-6 py-4 flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-white">Buat Tiket Dukungan Baru</h2>
                    <a href="{{ route('user.ticket.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-green-600 bg-opacity-80 rounded-lg text-white hover:bg-opacity-30 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Kembali
                    </a>
                </div>

                <!-- Form Content -->
                <div class="p-6">
                    <form action="{{ route('user.ticket.store') }}" method="POST" enctype="multipart/form-data"
                        class="space-y-6">
                        @csrf

                        <!-- Description Field -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi
                                Masalah</label>
                            <textarea name="description" id="description" rows="4"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                                placeholder="Jelaskan masalah yang Anda alami..."
                                required>{{ old('description') }}</textarea>
                            @error('description')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- First Row: Category & Department -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="category_id"
                                    class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                                <select name="category_id" id="category_id"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                                    required>
                                    <option value="">Pilih Kategori</option>
                                    @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
                                <div class="flex items-center px-4 py-2 rounded-lg border border-gray-200 bg-gray-50">
                                    <div class="flex-1">
                                        <p class="text-gray-900">{{ $userDepartment->name }}</p>
                                        <p class="text-xs text-gray-500">Kode: {{ $userDepartment->code }}</p>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input type="hidden" name="department_id" value="{{ $userDepartment->id }}">
                            </div>
                        </div>

                        <!-- Second Row: Location & Building -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="location_id"
                                    class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                                <select name="location_id" id="location_id"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                                    required onchange="updateBuilding()">
                                    <option value="">Pilih Lokasi</option>
                                    @foreach($locations as $location)
                                    <option value="{{ $location->id }}" data-building-id="{{ $location->building_id }}"
                                        data-building-name="{{ $location->building->name }}">
                                        {{ $location->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('location_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Gedung</label>
                                <div class="flex items-center px-4 py-2 rounded-lg border border-gray-200 bg-gray-50">
                                    <div class="flex-1">
                                        <p id="building_display" class="text-gray-900">Pilih lokasi terlebih dahulu</p>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <input type="hidden" name="building_id" id="building_id_hidden">
                            </div>
                        </div>

                        <!-- Third Row: Priority & Photo -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="priority"
                                    class="block text-sm font-medium text-gray-700 mb-1">Prioritas</label>
                                <select name="priority" id="priority"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                                    required>
                                    <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Rendah</option>
                                    <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>Sedang
                                    </option>
                                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>Tinggi
                                    </option>
                                </select>
                                @error('priority')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Foto (opsional)</label>
                                <div class="flex items-center">
                                    <label
                                        class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg cursor-pointer bg-white hover:bg-gray-50 transition-colors">
                                        <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        <span class="text-sm text-gray-700">Pilih Foto</span>
                                        <input type="file" name="photo" accept="image/*" class="hidden"
                                            id="photo-input">
                                    </label>
                                </div>
                                <p id="file-name" class="mt-1 text-xs text-gray-500">Ukuran file maksimum: 5MB</p>
                                @error('photo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                            <a href="{{ route('user.ticket.index') }}"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                                Batal
                            </a>
                            <button type="submit"
                                class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                                Buat Tiket
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function updateBuilding() {
    const locationSelect = document.getElementById('location_id');
    const buildingDisplay = document.getElementById('building_display');
    const buildingIdHidden = document.getElementById('building_id_hidden');
    const selectedOption = locationSelect.options[locationSelect.selectedIndex];

    if (selectedOption.value) {
        const buildingName = selectedOption.getAttribute('data-building-name');
        const buildingId = selectedOption.getAttribute('data-building-id');
        buildingDisplay.textContent = buildingName;
        buildingIdHidden.value = buildingId;
    } else {
        buildingDisplay.textContent = 'Pilih lokasi terlebih dahulu';
        buildingIdHidden.value = '';
    }
}

// Initialize building on page load if location is already selected
document.addEventListener('DOMContentLoaded', function() {
    const locationSelect = document.getElementById('location_id');
    if (locationSelect.value) {
        updateBuilding();
    }

    // Show selected file name
    const photoInput = document.getElementById('photo-input');
    const fileNameDisplay = document.getElementById('file-name');

    photoInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const fileName = this.files[0].name;
            const fileSize = (this.files[0].size / 1024 / 1024).toFixed(2); // Convert to MB
            fileNameDisplay.textContent = `${fileName} (${fileSize} MB)`;
        } else {
            fileNameDisplay.textContent = 'Ukuran file maksimum: 5MB';
        }
    });
});
</script>
@endpush
@endsection