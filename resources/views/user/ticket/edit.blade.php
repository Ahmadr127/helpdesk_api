@extends('user.layouts.app')

@section('title', 'Edit Tiket')

@section('content')
<!-- Include the notification partial -->
@include('user.partials.notification')

<div class="container mx-auto px-4 py-8">
    <div class="max-w-5xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header with gradient and back button -->
            <div class="bg-gradient-to-r from-green-600 to-blue-300 px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                        </path>
                    </svg>
                    Edit Tiket #{{ $ticket->ticket_number }}
                </h2>
                <a href="{{ route('user.ticket.index') }}"
                    class="inline-flex items-center text-white hover:text-blue-100 transition-colors bg-green-600 hover:bg-green-800 px-3 py-2 rounded-md">
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
                <form action="{{ route('user.ticket.update', $ticket) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Description Field at the top - spans full width -->
                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi
                            Masalah</label>
                        <textarea name="description" id="description" rows="3"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            required>{{ old('description', $ticket->description) }}</textarea>
                        @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Main input grid - 3 columns layout -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <!-- Column 1 -->
                        <div>
                            <label for="category_id"
                                class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                            <select name="category_id" id="category_id"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                required>
                                <option value="">Pilih Kategori</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('category_id', $ticket->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('category_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Column 2 -->
                        <div>
                            <label for="department"
                                class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
                            <div class="flex items-center px-4 py-2 rounded-lg border border-gray-200 bg-gray-50">
                                <div class="flex-1">
                                    <p class="text-gray-900">{{ $ticket->department }}</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input type="hidden" name="department_id" value="{{ $ticket->department_id }}">
                        </div>

                        <!-- Column 3 -->
                        <div>
                            <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Prioritas</label>
                            <select name="priority" id="priority"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                required>
                                <option value="low" {{ old('priority', $ticket->priority) == 'low' ? 'selected' : '' }}>
                                    Rendah</option>
                                <option value="medium"
                                    {{ old('priority', $ticket->priority) == 'medium' ? 'selected' : '' }}>Sedang
                                </option>
                                <option value="high"
                                    {{ old('priority', $ticket->priority) == 'high' ? 'selected' : '' }}>Tinggi</option>
                            </select>
                            @error('priority')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Location and Building grid - 2 columns -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="location_id" class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                            <select name="location_id" id="location_id"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                required onchange="updateBuilding()">
                                <option value="">Pilih Lokasi</option>
                                @foreach($locations as $location)
                                <option value="{{ $location->id }}" data-building-id="{{ $location->building_id }}"
                                    data-building-name="{{ $location->building->name }}"
                                    {{ old('location_id', $ticket->location_id) == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('location_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="building" class="block text-sm font-medium text-gray-700 mb-1">Gedung</label>
                            <div class="flex items-center px-4 py-2 rounded-lg border border-gray-200 bg-gray-50">
                                <div class="flex-1">
                                    <p id="building" class="text-gray-900">{{ $ticket->building }}</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <input type="hidden" name="building_id" id="building_id" value="{{ $ticket->building_id }}">
                        </div>
                    </div>

                    <!-- Photo Upload Section -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto</label>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Current Photo Preview -->
                            <div>
                                <h3 class="text-sm font-medium text-gray-600 mb-2">Foto Tiket</h3>
                                <div id="photo-container">
                                    @if($ticket->photos->where('type', 'initial')->first())
                                    <div class="relative rounded-lg overflow-hidden border border-gray-200 shadow-sm">
                                        <img id="displayed-photo"
                                            src="{{ Storage::url($ticket->photos->where('type', 'initial')->first()->photo_path) }}"
                                            alt="Ticket Photo" class="w-full h-48 object-cover">
                                        <div id="photo-filename"
                                            class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs py-1 px-2 truncate">
                                            {{ basename($ticket->photos->where('type', 'initial')->first()->photo_path) }}
                                        </div>
                                    </div>
                                    @else
                                    <div
                                        class="border border-dashed border-gray-300 rounded-lg h-48 flex items-center justify-center bg-gray-50">
                                        <div class="text-center px-4">
                                            <svg class="mx-auto h-10 w-10 text-gray-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                            <p class="mt-1 text-sm text-gray-500">
                                                Tidak ada foto
                                            </p>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Upload Area -->
                            <div>
                                <h3 class="text-sm font-medium text-gray-600 mb-2">Upload Foto Baru</h3>
                                <div
                                    class="border border-dashed border-gray-300 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                    <div class="text-center">
                                        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                            </path>
                                        </svg>
                                        <div class="mt-3 flex text-sm leading-6 text-gray-600 justify-center">
                                            <label for="photo"
                                                class="relative cursor-pointer rounded-md bg-white font-semibold text-blue-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-blue-600 focus-within:ring-offset-2 hover:text-blue-500">
                                                <span>Upload file</span>
                                                <input id="photo" name="photo" type="file" class="sr-only"
                                                    accept="image/*">
                                            </label>
                                            <p class="pl-1">atau drop file di sini</p>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-2">
                                            PNG, JPG, GIF hingga 5MB
                                        </p>
                                    </div>
                                </div>

                                @error('photo')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('user.ticket.index') }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                            Batal
                        </a>
                        <button type="submit"
                            class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            Perbarui Tiket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function updateBuilding() {
    const locationSelect = document.getElementById('location_id');
    const buildingElement = document.getElementById('building');
    const buildingIdInput = document.getElementById('building_id');

    if (locationSelect.value) {
        const selectedOption = locationSelect.options[locationSelect.selectedIndex];
        const buildingId = selectedOption.getAttribute('data-building-id');
        const buildingName = selectedOption.getAttribute('data-building-name');

        buildingElement.textContent = buildingName;
        buildingIdInput.value = buildingId;
    } else {
        buildingElement.textContent = '';
        buildingIdInput.value = '';
    }
}

// Initialize building on page load
document.addEventListener('DOMContentLoaded', function() {
    updateBuilding();

    // File preview functionality
    const photoInput = document.getElementById('photo');
    const displayedPhoto = document.getElementById('displayed-photo');
    const photoFilename = document.getElementById('photo-filename');
    const photoContainer = document.getElementById('photo-container');

    photoInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                // If there's no photo container yet, create one
                if (!displayedPhoto) {
                    const newPhotoContainer = `
                        <div class="relative rounded-lg overflow-hidden border border-gray-200 shadow-sm">
                            <img id="displayed-photo" src="${e.target.result}" alt="Ticket Photo" class="w-full h-48 object-cover">
                            <div id="photo-filename" class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs py-1 px-2 truncate">
                                ${file.name}
                            </div>
                        </div>
                    `;
                    photoContainer.innerHTML = newPhotoContainer;
                } else {
                    // Update existing photo
                    displayedPhoto.src = e.target.result;
                    photoFilename.textContent = file.name;
                }
            }

            reader.readAsDataURL(file);
        }
    });

    // Drag and drop functionality
    const dropZone = document.querySelector('.border-dashed');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        dropZone.classList.add('border-blue-400', 'bg-blue-50');
    }

    function unhighlight() {
        dropZone.classList.remove('border-blue-400', 'bg-blue-50');
    }

    dropZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;

        if (files && files.length) {
            photoInput.files = files;

            // Trigger change event
            const event = new Event('change', {
                bubbles: true
            });
            photoInput.dispatchEvent(event);
        }
    }
});
</script>
@endpush

@endsection