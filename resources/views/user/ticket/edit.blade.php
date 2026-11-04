@extends('user.layouts.app')

@section('title', 'Edit Tiket')

@section('content')
<!-- Include the notification partial -->
@include('user.partials.notification')

<div class="container mx-auto px-4 py-8">
    <div class="max-w-5xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header with gradient and back button - BLUE theme -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-400 px-6 py-4 flex justify-between items-center">
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
                    class="inline-flex items-center text-white hover:text-blue-100 transition-colors bg-blue-700 hover:bg-blue-800 px-3 py-2 rounded-md">
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
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="">Pilih Lokasi (opsional)</option>
                                @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ old('location_id', $ticket->location_id ?? (isset($userDepartment) && $userDepartment && $userDepartment->location ? $userDepartment->location->id : null)) == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('location_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="building_id" class="block text-sm font-medium text-gray-700 mb-1">Gedung</label>
                            <select name="building_id" id="building_id"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="">-- Tidak Ada --</option>
                                @foreach($buildings as $b)
                                <option value="{{ $b->id }}" {{ old('building_id', $ticket->building_id ?? (isset($userDepartment) && $userDepartment && $userDepartment->building ? $userDepartment->building->id : null)) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Photo Upload Section -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto</label>
                        <x-image-upload name="photos[]" accent="blue" :max="3" :max-size="5"
                            :existing="$ticket->photos->where('type', 'initial')->map(fn ($p) => Storage::url($p->photo_path))->values()->all()"
                            existing-label="Foto tiket saat ini" />
                        @error('photo')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('photos')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('photos.*')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('user.ticket.index') }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                            Batal
                        </a>
                        <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            Perbarui Tiket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection