@extends('admin.layouts.app')

@section('title', 'Edit Role')

@section('content')
<div class="w-full max-w-4xl">
    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Edit Role: {{ $role->slug }}</h1>
        <p class="text-sm text-gray-500 mb-6">Slug tidak dapat diubah setelah role dibuat.</p>

        <form method="POST" action="{{ route('admin.roles.update', $role) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                <input type="text" name="name" value="{{ old('name', $role->name) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('description', $role->description) }}</textarea>
            </div>

            @php
                $selectedPermissions = collect(old('permission_ids', $role->permissions->pluck('id')->all()));
                $selectedKategoris = collect(old('kategori_ids', $role->kategoris->pluck('id')->all()));
            @endphp

            <div class="mb-6">
                <h2 class="font-semibold text-gray-800 mb-3">Permission</h2>
                @foreach($permissions as $group => $items)
                    <div class="mb-4">
                        <h3 class="text-sm font-medium text-gray-600 mb-2">{{ $group }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($items as $permission)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" @checked($selectedPermissions->contains($permission->id))>
                                    <span>{{ $permission->name }} <span class="text-gray-400">({{ $permission->slug }})</span></span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mb-6">
                <h2 class="font-semibold text-gray-800 mb-3">Kategori Pengajuan</h2>
                @foreach($kategoris->groupBy(fn ($kategori) => $kategori->jenis->code ?? 'Lainnya') as $jenisCode => $items)
                    <div class="mb-4">
                        <h3 class="text-sm font-medium text-gray-600 mb-2">{{ $jenisCode }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($items as $kategori)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="kategori_ids[]" value="{{ $kategori->id }}" @checked($selectedKategoris->contains($kategori->id))>
                                    <span>{{ $kategori->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">Simpan</button>
                <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
