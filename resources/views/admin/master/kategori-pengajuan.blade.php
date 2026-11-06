@extends('admin.layouts.app')
@section('title', 'Kategori Pengajuan')
@section('content')
<div class="w-full">
    <div class="bg-white rounded-lg shadow-md p-4 mb-6 border border-gray-100">
        <form action="{{ route('admin.master.kategori-pengajuan.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" placeholder="Cari nama atau kode...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis</label>
                <select name="jenis_id" class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">Semua</option>
                    @foreach($jenisList as $j)
                        <option value="{{ $j->id }}" {{ request('jenis_id') == $j->id ? 'selected' : '' }}>{{ $j->code }} - {{ $j->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">Semua</option>
                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-all shadow-sm">Filter</button>
                <a href="{{ route('admin.master.kategori-pengajuan.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-all shadow-sm">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($kategoris as $k)
                <tr>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $k->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $k->code ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $k->jenis?->code }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-2 py-1 text-xs rounded-full {{ $k->status ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">{{ $k->status ? 'Aktif' : 'Nonaktif' }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <form action="{{ route('admin.master.kategori-pengajuan.update', $k) }}" method="POST" class="inline">
                            @csrf @method('PUT')
                            <input type="hidden" name="jenis_id" value="{{ $k->jenis_id }}">
                            <input type="hidden" name="name" value="{{ $k->name }}">
                            <input type="hidden" name="code" value="{{ $k->code }}">
                            <input type="hidden" name="status" value="{{ $k->status ? 0 : 1 }}">
                            <button type="submit" class="text-blue-600 hover:text-blue-900 text-sm">{{ $k->status ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        </form>
                        <form action="{{ route('admin.master.kategori-pengajuan.destroy', $k) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kategori?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900 text-sm ml-2">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $kategoris->links() }}</div>
    </div>

    <!-- Add form -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-6 border border-gray-100">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Tambah Kategori Pengajuan</h2>
        <form action="{{ route('admin.master.kategori-pengajuan.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis</label>
                <select name="jenis_id" required class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    @foreach($jenisList as $j)
                        <option value="{{ $j->id }}">{{ $j->code }} - {{ $j->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                <input type="text" name="name" required class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kode</label>
                <input type="text" name="code" class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" required class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>
            <div class="md:col-span-4">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-all shadow-sm">Tambah</button>
            </div>
        </form>
    </div>
</div>
@endsection