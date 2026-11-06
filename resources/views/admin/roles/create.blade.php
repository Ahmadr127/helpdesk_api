@extends('admin.layouts.app')

@section('title', 'Tambah Role')

@section('content')
<div class="w-full">
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
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Tambah Role</h1>

        <form method="POST" action="{{ route('admin.roles.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    <p class="text-xs text-gray-500 mt-1">Huruf kecil, angka, underscore. Tidak dapat diubah setelah dibuat.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('description') }}</textarea>
            </div>

            <div class="mb-6">
                <h2 class="font-semibold text-gray-800 mb-3">Permission</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($permissions as $group => $items)
                        @foreach($items as $permission)
                            <label class="flex items-start gap-2.5 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors min-h-[4.25rem] {{ in_array($permission->id, old('permission_ids', [])) ? 'border-blue-400 bg-blue-50/60 shadow-sm' : 'border-gray-200 bg-white' }}">
                                <input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permission_ids', []))) class="mt-0.5 shrink-0 w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                <span class="text-sm min-w-0">
                                    <span class="font-medium text-gray-800 block leading-tight">{{ $permission->name }}</span>
                                    <span class="text-xs text-gray-400 block font-mono truncate">{{ $permission->slug }}</span>
                                </span>
                            </label>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <div class="mb-6">
                <h2 class="font-semibold text-gray-800 mb-3">Kategori Pengajuan</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($kategoris->groupBy(fn ($kategori) => $kategori->jenis->code ?? 'Lainnya') as $jenisCode => $items)
                        @foreach($items as $kategori)
                            <label class="flex items-start gap-2.5 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors min-h-[4.25rem] {{ in_array($kategori->id, old('kategori_ids', [])) ? 'border-blue-400 bg-blue-50/60 shadow-sm' : 'border-gray-200 bg-white' }}">
                                <input type="checkbox" name="kategori_ids[]" value="{{ $kategori->id }}" @checked(in_array($kategori->id, old('kategori_ids', []))) class="mt-0.5 shrink-0 w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                <span class="text-sm min-w-0">
                                    <span class="font-medium text-gray-800 block leading-tight">{{ $kategori->name }}</span>
                                </span>
                            </label>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100 mt-4 gap-3">
                <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Batal</a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium shadow-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
