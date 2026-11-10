@extends('admin.layouts.app')
@section('title', 'Kategori Pengajuan')
@section('content')
<div class="w-full">
    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6 border border-gray-100">
        <form id="filterForm" action="{{ route('admin.master.kategori-pengajuan.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4" onsubmit="return validateFilterForm()">
            <div class="md:col-span-4">
                <label for="filter_search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <x-searchable-select name="search" :options="$searchOptions ?? []" :selected="request('search')" placeholder="Cari berdasarkan nama atau kode kategori…" id="filter_search" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis</label>
                <select name="jenis_id" id="filter_jenis" class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">Semua</option>
                    @foreach($jenisList as $j)
                        <option value="{{ $j->id }}" {{ request('jenis_id') == $j->id ? 'selected' : '' }}>{{ $j->code }} - {{ $j->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="filter_status" class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">Semua Status</option>
                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-all shadow-sm">Filter</button>
                <a href="{{ route('admin.master.kategori-pengajuan.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-all shadow-sm">Reset</a>
                <a href="{{ route('admin.master.jenis-pengajuan.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-all shadow-sm">Kelola Jenis</a>
                <button type="button" onclick="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-all shadow-sm">Tambah Kategori</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($kategoris as $k)
                <tr class="hover:bg-slate-50 transition-all">
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $k->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $k->code ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $k->jenisNames ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-2 py-1 text-xs rounded-full {{ $k->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $k->status ? 'Aktif' : 'Nonaktif' }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @php
                            $editPayload = [
                                'id' => $k->id,
                                'name' => $k->name,
                                'code' => $k->code ?? '',
                                'status' => (int) $k->status,
                                'jenis_ids' => $k->jenisList->pluck('id')->values()->all(),
                            ];
                        @endphp
                        <button type="button" onclick='editKategori(@json($editPayload, 15, 512))' class="text-blue-600 hover:text-blue-900 text-sm mr-2">Edit</button>
                        <form action="{{ route('admin.master.kategori-pengajuan.destroy', $k) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kategori?')">
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
        <div class="p-4">{{ $kategoris->links() }}</div>
    </div>

    <!-- Modal -->
    <div id="formModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full backdrop-blur-sm transition-all">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white border-gray-100">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-800" id="modalTitle">Tambah Kategori Pengajuan</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500 transition-all">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>
            <form id="kategoriForm" action="{{ route('admin.master.kategori-pengajuan.store') }}" method="POST">
                @csrf
                <div id="methodField"></div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis <span class="text-xs text-gray-400 font-normal">(bisa lebih dari satu)</span></label>
                    <div class="max-h-44 overflow-y-auto border border-gray-200 rounded-md p-2 space-y-1">
                        @foreach($jenisList as $j)
                            <label class="flex items-center gap-2 px-1 py-1 rounded hover:bg-gray-50 cursor-pointer text-sm text-gray-700 select-none">
                                <input type="checkbox" name="jenis_ids[]" value="{{ $j->id }}" class="jenis-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>{{ $j->code }} &mdash; {{ $j->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p id="jenis_ids_error" class="text-xs text-red-600 mt-1 hidden">Pilih minimal satu jenis.</p>
                </div>
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                    <input type="text" name="name" id="name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Kode</label>
                    <input type="text" name="code" id="code" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
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

    @push('scripts')
    <script>
    function validateFilterForm() { return true; }
    function openModal(kategori = null) {
        const modal = document.getElementById('formModal');
        const form = document.getElementById('kategoriForm');
        const methodField = document.getElementById('methodField');
        const modalTitle = document.getElementById('modalTitle');
        if (kategori) {
            form.action = "{{ url('master/kategori-pengajuan') }}/" + kategori.id;
            methodField.innerHTML = '@method("PUT")';
            const ids = Array.isArray(kategori.jenisList_ids) ? kategori.jenisList_ids.map(id => parseInt(id)) : [];
            document.querySelectorAll('.jenis-checkbox').forEach(function (cb) {
                cb.checked = ids.includes(parseInt(cb.value));
            });
            document.getElementById('name').value = kategori.name;
            document.getElementById('code').value = kategori.code || '';
            document.getElementById('status').value = kategori.status;
            modalTitle.textContent = 'Edit Kategori Pengajuan';
        } else {
            form.action = "{{ route('admin.master.kategori-pengajuan.store') }}";
            methodField.innerHTML = '';
            form.reset();
            document.getElementById('jenis_ids_error').classList.add('hidden');
            modalTitle.textContent = 'Tambah Kategori Pengajuan';
        }
        modal.classList.remove('hidden');
    }
    document.getElementById('kategoriForm').addEventListener('submit', function (e) {
        const checked = document.querySelectorAll('.jenis-checkbox:checked');
        const errorEl = document.getElementById('jenis_ids_error');
        if (checked.length === 0) {
            e.preventDefault();
            errorEl.classList.remove('hidden');
        } else {
            errorEl.classList.add('hidden');
        }
    });
    function closeModal() {
        document.getElementById('formModal').classList.add('hidden');
    }
    document.getElementById('formModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    function editKategori(kategori) { openModal(kategori); }
    function deleteKategori(id) {
        if (confirm('Apakah Anda yakin ingin menghapus kategori ini?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ url('master/kategori-pengajuan') }}/" + id;
            form.innerHTML = '@csrf @method("DELETE")';
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
    @endpush
</div>
@endsection