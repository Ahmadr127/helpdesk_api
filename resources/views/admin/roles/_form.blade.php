@php
    $roleModel = $role ?? null;
    $isEdit = $roleModel !== null;
    $selectedPermissions = collect(old('permission_ids', $roleModel?->permissions->pluck('id')->all() ?? []));
    $selectedKategoris = collect(old('kategori_ids', $roleModel?->kategoris->pluck('id')->all() ?? []));

    $totalPermissions = $permissions->flatten()->count();
    $totalKategoris = $kategoris->count();

    // Buka tab yang bermasalah saat validasi gagal.
    $initialTab = 'permission';
    if (isset($errors) && $errors->has('kategori_ids')) {
        $initialTab = 'kategori';
    }
@endphp

<style>
    [x-cloak] { display: none !important; }
</style>

@if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 {{ $isEdit ? '' : 'md:grid-cols-2' }} gap-4 mb-6">
    @unless($isEdit)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
            <input type="text" name="slug" value="{{ old('slug') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
            <p class="text-xs text-gray-500 mt-1">Huruf kecil, angka, underscore. Tidak dapat diubah setelah dibuat.</p>
        </div>
    @endunless
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
        <input type="text" name="name" value="{{ old('name', $roleModel?->name) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
    </div>
</div>

<div class="mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
    <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('description', $roleModel?->description) }}</textarea>
</div>

<div x-data="{ tab: '{{ $initialTab }}', permCount: {{ $selectedPermissions->count() }}, katCount: {{ $selectedKategoris->count() }} }">
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex flex-wrap gap-1 -mb-px" aria-label="Tabs">
            <button type="button" @click="tab = 'permission'"
                :class="tab === 'permission' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors">
                Permission
                <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-600">
                    <span x-text="permCount">{{ $selectedPermissions->count() }}</span>/{{ $totalPermissions }}
                </span>
            </button>
            <button type="button" @click="tab = 'kategori'"
                :class="tab === 'kategori' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors">
                Kategori
                <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-600">
                    <span x-text="katCount">{{ $selectedKategoris->count() }}</span>/{{ $totalKategoris }}
                </span>
            </button>
        </nav>
    </div>

    {{-- Tab: Permission --}}
    <div x-show="tab === 'permission'" x-cloak>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($permissions as $group => $items)
                @foreach($items as $permission)
                    <label class="flex items-start gap-2.5 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors min-h-[4.25rem] {{ $selectedPermissions->contains($permission->id) ? 'border-blue-400 bg-blue-50/60 shadow-sm' : 'border-gray-200 bg-white' }}">
                        <input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" @checked($selectedPermissions->contains($permission->id))
                            @change="permCount = $el.closest('form').querySelectorAll('input[name=&quot;permission_ids[]&quot;]:checked').length"
                            class="mt-0.5 shrink-0 w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                        <span class="text-sm min-w-0">
                            <span class="font-medium text-gray-800 block leading-tight">{{ $permission->name }}</span>
                            <span class="text-xs text-gray-400 block font-mono truncate">{{ $permission->slug }}</span>
                        </span>
                    </label>
                @endforeach
            @endforeach
        </div>
    </div>

    {{-- Tab: Kategori --}}
    <div x-show="tab === 'kategori'" x-cloak>
        <div class="space-y-5">
            @foreach($kategoris->groupBy(fn ($kategori) => $kategori->jenis->code ?? 'Lainnya') as $jenisCode => $items)
                @php
                    $jenis = $items->first()->jenis;
                @endphp
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-700">{{ $jenisCode }}</span>
                        <span class="text-sm font-medium text-gray-700">{{ $jenis?->name ?? 'Lainnya' }}</span>
                        <span class="text-xs text-gray-400">({{ $items->count() }} kategori)</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($items as $kategori)
                            <label class="flex items-start gap-2.5 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors min-h-[4.25rem] {{ $selectedKategoris->contains($kategori->id) ? 'border-blue-400 bg-blue-50/60 shadow-sm' : 'border-gray-200 bg-white' }}">
                                <input type="checkbox" name="kategori_ids[]" value="{{ $kategori->id }}" @checked($selectedKategoris->contains($kategori->id))
                                    @change="katCount = $el.closest('form').querySelectorAll('input[name=&quot;kategori_ids[]&quot;]:checked').length"
                                    class="mt-0.5 shrink-0 w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                <span class="text-sm min-w-0">
                                    <span class="font-medium text-gray-800 block leading-tight">{{ $kategori->name }}</span>
                                    <span class="text-xs text-gray-400 block">{{ $jenisCode }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="flex justify-end pt-4 border-t border-gray-100 mt-6 gap-3">
    <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Batal</a>
    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium shadow-sm">Simpan</button>
</div>
