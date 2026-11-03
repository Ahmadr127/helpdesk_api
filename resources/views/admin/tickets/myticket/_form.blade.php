{{-- Tidak dipakai: create/edit myticket adalah salinan penuh form user. --}}
{{-- Form fields tiket milik sendiri (dipakai create & edit). Variabel: $ticket (nullable), $categories, $locations, $buildings, $userDepartment --}}
@php($ticket = $ticket ?? null)
<div>
    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Masalah *</label>
    <textarea name="description" id="description" rows="4"
        class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
        placeholder="Jelaskan masalah yang Anda alami..."
        required>{{ old('description', $ticket->description ?? '') }}</textarea>
    @error('description')
    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label for="category_id-input" class="block text-sm font-medium text-gray-700 mb-1">Kategori *</label>
        <x-searchable-select name="category_id" id="category_id"
            :options="$categories" :selected="old('category_id', $ticket->category_id ?? null)"
            value-field="id" label-field="name"
            placeholder="Ketik untuk cari kategori..." empty-label="— Pilih Kategori —"
            accent="blue" :required="true" />
        @error('category_id')
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
        @if(isset($userDepartment) && $userDepartment)
        <div class="flex items-center px-4 py-2 rounded-lg border border-gray-200 bg-gray-50">
            <div class="flex-1">
                <p class="text-gray-900">{{ $userDepartment->name ?? $userDepartment->code ?? auth()->user()->department }}</p>
                <p class="text-xs text-gray-500">Kode: {{ $userDepartment->code ?? '-' }}</p>
            </div>
        </div>
        <input type="hidden" name="department_id" value="{{ $userDepartment->id }}">
        @else
        <div class="flex items-center px-4 py-2 rounded-lg border border-red-200 bg-red-50">
            <p class="text-red-700 text-sm">Departemen tidak ditemukan. Perbarui di <a href="{{ route('user.settings') }}" class="underline">pengaturan profil</a>.</p>
        </div>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label for="location_id-input" class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
        @if(isset($userDepartment) && $userDepartment && $userDepartment->location)
        <div class="flex items-center px-4 py-2 rounded-lg border border-gray-200 bg-gray-50">
            <div class="flex-1">
                <p class="text-gray-900">{{ $userDepartment->location->name }}</p>
                <p class="text-xs text-gray-500">Otomatis dari departemen</p>
            </div>
        </div>
        <input type="hidden" name="location_id" value="{{ $userDepartment->location->id }}">
        @else
        <x-searchable-select name="location_id" id="location_id"
            :options="$locations" :selected="old('location_id', $ticket->location_id ?? null)"
            value-field="id" label-field="name"
            placeholder="Ketik untuk cari lokasi..." empty-label="— Pilih Lokasi (opsional) —"
            accent="blue" />
        @endif
        @error('location_id')
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="building_id-input" class="block text-sm font-medium text-gray-700 mb-1">Gedung</label>
        @if(isset($userDepartment) && $userDepartment && $userDepartment->building)
        <div class="flex items-center px-4 py-2 rounded-lg border border-gray-200 bg-gray-50">
            <div class="flex-1">
                <p class="text-gray-900">{{ $userDepartment->building->name }}</p>
                <p class="text-xs text-gray-500">Otomatis dari departemen</p>
            </div>
        </div>
        <input type="hidden" name="building_id" value="{{ $userDepartment->building->id }}">
        @else
        <x-searchable-select name="building_id" id="building_id"
            :options="$buildings" :selected="old('building_id', $ticket->building_id ?? null)"
            value-field="id" label-field="name"
            placeholder="Ketik untuk cari gedung..." empty-label="— Tidak Ada —"
            accent="blue" />
        @endif
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Prioritas *</label>
        <select name="priority" id="priority"
            class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            required>
            <option value="low" {{ old('priority', $ticket->priority ?? '') == 'low' ? 'selected' : '' }}>Rendah</option>
            <option value="medium" {{ old('priority', $ticket->priority ?? '') == 'medium' ? 'selected' : '' }}>Sedang</option>
            <option value="high" {{ old('priority', $ticket->priority ?? '') == 'high' ? 'selected' : '' }}>Tinggi</option>
        </select>
        @error('priority')
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Foto (opsional)</label>
        <label class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg cursor-pointer bg-white hover:bg-gray-50 transition-colors">
            <span class="text-sm text-gray-700">Pilih Foto</span>
            <input type="file" name="photo" accept="image/*" class="hidden photo-input">
        </label>
        <p class="mt-1 text-xs text-gray-500 file-name">Ukuran file maksimum: 5MB</p>
        @error('photo')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
