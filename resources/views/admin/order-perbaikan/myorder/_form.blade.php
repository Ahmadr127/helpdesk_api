{{-- Tidak dipakai: create/edit myorder adalah salinan penuh form user. --}}
{{-- Form fields order milik sendiri (dipakai create & edit). Variabel: $orderPerbaikan (nullable), $locations, $kategoriOrders, $departmentId, $departmentLocation, $departmentBuilding --}}
@php($orderPerbaikan = $orderPerbaikan ?? null)
<input type="hidden" name="tanggal" value="{{ old('tanggal', $orderPerbaikan->tanggal ?? now()->format('Y-m-d H:i:s')) }}">
<input type="hidden" name="department_id" value="{{ old('department_id', $departmentId ?? '') }}">
<input type="hidden" name="unit_proses_code" value="">

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Inventaris</label>
        <input type="text" name="kode_inventaris" value="{{ old('kode_inventaris', $orderPerbaikan->kode_inventaris ?? '') }}" placeholder="Kode inventaris (opsional)"
            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
        @error('kode_inventaris') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="prioritas-input" class="block text-sm font-medium text-gray-700 mb-1">Prioritas <span class="text-red-500">*</span></label>
        <x-searchable-select name="prioritas" id="prioritas"
            :options="['RENDAH' => 'RENDAH', 'SEDANG' => 'SEDANG', 'TINGGI/URGENT' => 'TINGGI/URGENT']"
            :selected="old('prioritas', $orderPerbaikan->prioritas ?? '')"
            placeholder="Ketik untuk cari prioritas..." empty-label="— Pilih Prioritas —"
            accent="green" :required="true" />
        @error('prioritas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Barang</label>
        <input type="text" name="nama_barang" value="{{ old('nama_barang', $orderPerbaikan->nama_barang ?? '') }}" placeholder="Nama barang / peralatan"
            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
        @error('nama_barang') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="kategori_order-input" class="block text-sm font-medium text-gray-700 mb-1">Kategori Order</label>
        <x-searchable-select name="kategori_order" id="kategori_order"
            :options="$kategoriOrders->pluck('name', 'name')->toArray()" :selected="old('kategori_order', $orderPerbaikan->kategori_order ?? '')"
            placeholder="Ketik untuk cari kategori..." empty-label="— Pilih Kategori —"
            accent="green" />
        @error('kategori_order') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="lokasi-input" class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
        @if(isset($departmentLocation) && $departmentLocation)
        <div class="flex items-center px-3 py-2.5 border border-gray-200 bg-gray-50 rounded-lg">
            <div class="flex-1">
                <p class="text-sm text-gray-900">{{ $departmentLocation->name }}</p>
                <p class="text-xs text-gray-500">Otomatis dari departemen Anda</p>
            </div>
        </div>
        <input type="hidden" name="lokasi" value="{{ $departmentLocation->id }}">
        @else
        <x-searchable-select name="lokasi" id="lokasi"
            :options="$locations->pluck('name', 'id')->toArray()" :selected="old('lokasi', $orderPerbaikan->lokasi ?? '')"
            placeholder="Ketik untuk cari lokasi..." empty-label="— Pilih Lokasi —"
            accent="green" />
        @endif
        @error('lokasi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Gedung</label>
        <div class="flex items-center px-3 py-2.5 border border-gray-200 bg-gray-50 rounded-lg">
            <div class="flex-1">
                <p class="text-sm text-gray-900">{{ $departmentBuilding?->name ?? '-' }}</p>
                @if(isset($departmentBuilding) && $departmentBuilding)
                <p class="text-xs text-gray-500">Otomatis dari departemen</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div>
    <label for="keluhan" class="block text-sm font-medium text-gray-700 mb-1">Keluhan / Kerusakan *</label>
    <textarea name="keluhan" id="keluhan" rows="4"
        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
        placeholder="Jelaskan kerusakan yang terjadi..." required>{{ old('keluhan', $orderPerbaikan->keluhan ?? '') }}</textarea>
    @error('keluhan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Foto (opsional, maks 2MB)</label>
    <label class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg cursor-pointer bg-white hover:bg-gray-50 transition-colors">
        <span class="text-sm text-gray-700">Pilih Foto</span>
        <input type="file" name="foto" accept="image/*" class="hidden photo-input">
    </label>
    <p class="mt-1 text-xs text-gray-500 file-name">Ukuran file maksimum: 2MB</p>
    @error('foto') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
