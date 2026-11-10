@extends('admin.layouts.app')

@section('title', 'Jenis Pengajuan')

@section('content')
<div class="w-full">
    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6 border border-gray-100">
        <form action="{{ route('admin.master.jenis-pengajuan.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-3">
                <label for="filter_search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" id="filter_search" value="{{ request('search') }}" placeholder="Cari berdasarkan nama atau kode jenis…"
                    class="w-full rounded-md border border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="is_active" id="filter_status"
                    class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">Semua Status</option>
                    <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="md:col-span-4 flex items-end space-x-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-all shadow-sm">Filter</button>
                <a href="{{ route('admin.master.jenis-pengajuan.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-all shadow-sm">Reset</a>
                <a href="{{ route('admin.master.kategori-pengajuan.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-all shadow-sm ml-auto">Kategori Pengajuan</a>
                <button type="button" onclick="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-all shadow-sm">Tambah Jenis</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">Daftar Jenis Pengajuan</h2>
            <p class="text-sm text-gray-500 mt-1">Jenis menentukan flavour pengajuan (SIRS dan UMUM). Kategori bisa terhubung ke beberapa jenis sekaligus.</p>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prefix</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($jenisList as $jenis)
                    <tr class="hover:bg-slate-50 transition-all">
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $jenis->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $jenis->code }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $jenis->prefix ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 text-xs rounded-full {{ $jenis->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $jenis->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <button type="button" onclick='editJenis(@json($jenis))' class="text-blue-600 hover:text-blue-900 text-sm mr-2">Edit</button>
                            <form action="{{ route('admin.master.jenis-pengajuan.destroy', $jenis) }}" method="POST" class="inline" onsubmit="return confirm('Hapus jenis?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $jenisList->links() }}</div>
    </div>

    <!-- Modal -->
    <div id="formModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full backdrop-blur-sm transition-all">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white border-gray-100">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-800" id="modalTitle">Tambah Jenis Pengajuan</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500 transition-all">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>
            <form id="jenisForm" action="{{ route('admin.master.jenis-pengajuan.store') }}" method="POST">
                @csrf
                <div id="methodField"></div>
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                    <input type="text" name="name" id="name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Contoh: SIRS, UMUM">
                </div>
                <div class="mb-4">
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Kode</label>
                    <input type="text" name="code" id="code" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Contoh: IT, IPSRS, UMUM">
                    <p class="text-xs text-gray-500 mt-1">Kode unik dipakai sebagai key scope (<code>sirs()</code>, <code>umum()</code>).</p>
                </div>
                <div class="mb-4">
                    <label for="prefix" class="block text-sm font-medium text-gray-700 mb-1">Prefix Nomor</label>
                    <input type="text" name="prefix" id="prefix" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Contoh: T, OP/RTG/MTC">
                </div>
                <div class="mb-4">
                    <label for="is_active" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="is_active" id="is_active" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-all">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-all shadow-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openModal(jenis = null) {
    const modal = document.getElementById('formModal');
    const form = document.getElementById('jenisForm');
    const methodField = document.getElementById('methodField');
    const modalTitle = document.getElementById('modalTitle');
    if (jenis) {
        form.action = "{{ url('master/jenis-pengajuan') }}/" + jenis.id;
        methodField.innerHTML = '@method("PUT")';
        document.getElementById('name').value = jenis.name;
        document.getElementById('code').value = jenis.code;
        document.getElementById('prefix').value = jenis.prefix || '';
        document.getElementById('is_active').value = jenis.is_active ? 1 : 0;
        modalTitle.textContent = 'Edit Jenis Pengajuan';
    } else {
        form.action = "{{ route('admin.master.jenis-pengajuan.store') }}";
        methodField.innerHTML = '';
        form.reset();
        modalTitle.textContent = 'Tambah Jenis Pengajuan';
    }
    modal.classList.remove('hidden');
}
function closeModal() {
    document.getElementById('formModal').classList.add('hidden');
}
document.getElementById('formModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
function editJenis(jenis) { openModal(jenis); }
</script>
@endpush
@endsection
