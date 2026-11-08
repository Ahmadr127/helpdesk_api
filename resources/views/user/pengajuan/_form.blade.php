{{--
    Shared form untuk semua pengajuan (Tiket IT, Order Perbaikan IPSRS, Pengajuan umum).
    Variabel yang dipakai:
    - $action          (wajib) URL submit
    - $method          (opsional) POST/PUT, default POST
    - $kategoris       (wajib) semua kategori aktif (tanpa filter jenis)
    - $locations       (opsional) koleksi Location
    - $buildings       (opsional) koleksi Building
    - $unitProses      (opsional) koleksi UnitProses (dipakai bila $showOrderFields)
    - $userDepartment  (opsional) Department user
    - $departmentId    (opsional) id department default
    - $pengajuan       (opsional) model Pengajuan untuk mode edit
    - $showOrderFields (opsional) tampilkan field khusus IPSRS
    - $accent          (opsional) 'blue' | 'green', default 'green'
    - $submitLabel     (opsional) label tombol submit
    - $cancelUrl       (opsional) URL tombol batal
--}}
@php
    $isEdit = isset($pengajuan) && $pengajuan;
    $accent = $accent ?? 'green';
    $submitLabel = $submitLabel ?? ($isEdit ? 'Simpan Perubahan' : 'Kirim Pengajuan');
    $cancelUrl = $cancelUrl ?? null;


    $kategoris = $kategoris ?? collect();
    $locations = $locations ?? collect();
    $buildings = $buildings ?? collect();


    $userDepartment = $userDepartment ?? null;
    $departmentId = $departmentId ?? null;
    $departmentLocation = $departmentLocation ?? null;
    $departmentBuilding = $departmentBuilding ?? null;

    $defaultDepartmentId = old('department_id', $isEdit
        ? $pengajuan->department_id
        : ($departmentId ?? ($userDepartment->id ?? auth()->user()->department_id)));

    $defaultLocationId = old('lokasi_id', $isEdit
        ? $pengajuan->lokasi_id
        : ($departmentLocation?->id ?? $userDepartment?->location_id ?? optional($userDepartment?->location)->id));

    $defaultBuildingId = old('building_id', $isEdit
        ? $pengajuan->building_id
        : ($departmentBuilding?->id ?? $userDepartment?->building_id ?? optional($userDepartment?->building)->id));

    $selectedKategori = old('kategori_id', $isEdit ? $pengajuan->kategori_id : null);
    $selectedPrioritas = old('prioritas', $isEdit ? $pengajuan->prioritas : 'medium');

    $departmentName = $userDepartment?->name
        ?? $userDepartment?->code
        ?? ($unitPengajuName ?? null)
        ?? auth()->user()->department;
    $departmentCode = $userDepartment?->code ?? ($unitPengajuCode ?? null);

    $focusClass = $accent === 'blue'
        ? 'focus:ring-blue-500 focus:border-blue-500'
        : 'focus:ring-green-500 focus:border-green-500';
    $buttonClass = $accent === 'blue'
        ? 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'
        : 'bg-green-600 hover:bg-green-700 focus:ring-green-500';
    $topBorder = $accent === 'blue' ? 'border-blue-200' : 'border-green-200';
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if(($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <input type="hidden" name="department_id" value="{{ $defaultDepartmentId }}">

    {{-- Departemen (read-only) --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Departemen / Unit Pengaju</label>
        @if($defaultDepartmentId)
            <div class="flex items-center px-4 py-2.5 rounded-lg border border-gray-200 bg-gray-50">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900">{{ $departmentName ?: '-' }}</p>
                    <p class="text-xs text-gray-500">Kode: {{ $departmentCode ?: '-' }}</p>
                </div>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
        @else
            <div class="px-4 py-2.5 rounded-lg border border-red-200 bg-red-50">
                <p class="text-sm text-red-700">Departemen belum diisi.</p>
                <p class="text-xs text-red-500">Lengkapi di
                    <a href="{{ route('user.settings') }}" class="underline">pengaturan profil</a>.
                </p>
            </div>
        @endif
        @error('department_id')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Kategori & Prioritas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="kategori_id-input" class="block text-sm font-medium text-gray-700 mb-1">
                Kategori <span class="text-red-500">*</span>
            </label>
            <x-searchable-select name="kategori_id" id="kategori_id"
                :options="$kategoris" :selected="$selectedKategori"
                value-field="id" label-field="name"
                placeholder="Ketik untuk cari kategori..." empty-label="— Pilih Kategori —"
                :accent="$accent" :required="true" />
            @error('kategori_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="prioritas" class="block text-sm font-medium text-gray-700 mb-1">
                Prioritas <span class="text-red-500">*</span>
            </label>
            <select name="prioritas" id="prioritas" required
                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-sm focus:ring-2 {{ $focusClass }}">
                <option value="low" @selected($selectedPrioritas === 'low')>Rendah</option>
                <option value="medium" @selected($selectedPrioritas === 'medium')>Sedang</option>
                <option value="high" @selected($selectedPrioritas === 'high')>Tinggi</option>
            </select>
            @error('prioritas')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Deskripsi --}}
    <div>
        <label for="deskripsi" class="block text-sm font-medium text-gray-700 mb-1">
            Deskripsi / Keluhan <span class="text-red-500">*</span>
        </label>
        <textarea name="deskripsi" id="deskripsi" rows="4" required
            placeholder="Jelaskan masalah / keluhan yang Anda alami..."
            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:ring-2 {{ $focusClass }}">{{ old('deskripsi', $isEdit ? $pengajuan->deskripsi : '') }}</textarea>
        @error('deskripsi')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Lokasi & Gedung --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="lokasi_id-input" class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
            <x-searchable-select name="lokasi_id" id="lokasi_id"
                :options="$locations" :selected="$defaultLocationId"
                value-field="id" label-field="name"
                placeholder="Ketik untuk cari lokasi..." empty-label="— Pilih Lokasi (opsional) —"
                :accent="$accent" />
            @error('lokasi_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="building_id-input" class="block text-sm font-medium text-gray-700 mb-1">Gedung</label>
            <x-searchable-select name="building_id" id="building_id"
                :options="$buildings" :selected="$defaultBuildingId"
                value-field="id" label-field="name"
                placeholder="Ketik untuk cari gedung..." empty-label="— Pilih Gedung (opsional) —"
                :accent="$accent" />
            @error('building_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>


    {{-- Foto --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Foto Lampiran (opsional, maks. 3 foto)</label>
        <div class="flex items-center">
            <label class="w-full flex items-center justify-center px-4 py-2.5 border border-gray-300 rounded-lg cursor-pointer bg-white hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-sm text-gray-700">Pilih Foto</span>
                <input type="file" name="photos[]" id="pengajuan-photos" accept="image/*" multiple class="hidden">
            </label>
        </div>
        <p id="pengajuan-photos-info" class="mt-1 text-xs text-gray-500">Format JPG, PNG, GIF. Maksimal 3 foto, 5MB per foto.</p>
        @error('photos')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
        @error('photos.*')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Actions --}}
    <div class="flex justify-end items-center gap-3 pt-4 border-t border-gray-200">
        @if($cancelUrl)
            <a href="{{ $cancelUrl }}"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                Batal
            </a>
        @endif
        <button type="submit"
            class="px-6 py-2 text-white rounded-md focus:ring-2 focus:ring-offset-2 transition-colors {{ $buttonClass }}">
            {{ $submitLabel }}
        </button>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('pengajuan-photos');
    var info = document.getElementById('pengajuan-photos-info');
    if (!input || !info) return;

    input.addEventListener('change', function () {
        var files = Array.prototype.slice.call(this.files || []);
        if (files.length > 3) {
            var dt = new DataTransfer();
            files.slice(0, 3).forEach(function (f) { dt.items.add(f); });
            this.files = dt.files;
            files = files.slice(0, 3);
        }
        if (!files.length) {
            info.textContent = 'Format JPG, PNG, GIF. Maksimal 3 foto, 5MB per foto.';
            return;
        }
        info.textContent = files.map(function (f) {
            return f.name + ' (' + (f.size / 1024 / 1024).toFixed(2) + ' MB)';
        }).join(', ');
    });
});
</script>
@endpush
