@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div
        class="mb-6 bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.master.index') }}"
                class="bg-slate-500 text-white px-4 py-2 rounded-lg hover:bg-slate-600 transition-all flex items-center shadow-md">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
            <h2 class="text-xl font-semibold text-gray-800">Manajemen Gedung</h2>
        </div>
        <button onclick="openModal()"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all shadow-md">
            Tambah Gedung
        </button>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6 border border-gray-100">
        <form id="filterForm" action="{{ route('admin.master.buildings.index') }}" method="GET"
            class="grid grid-cols-1 md:grid-cols-4 gap-4" onsubmit="return validateFilterForm()">
            <div class="md:col-span-4">
                <label for="filter_search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <x-searchable-select name="search" :options="$searchOptions ?? []" :selected="request('search')" placeholder="Cari berdasarkan nama atau kode gedung…" id="filter_search" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="filter_status"
                    class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">Semua Status</option>
                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Tidak Aktif</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                <input type="date" name="from_date" id="filter_from_date" value="{{ request('from_date') }}"
                    class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                <input type="date" name="to_date" id="filter_to_date" value="{{ request('to_date') }}"
                    class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-all shadow-sm">Filter</button>
                <a href="{{ route('admin.master.buildings.index') }}"
                    class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-all shadow-sm">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-100">
        <form action="{{ route('admin.master.bulk-action', 'buildings') }}" method="POST">
            @csrf
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input type="checkbox"
                                    class="select-all rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Kode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal Dibuat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($buildings as $building)
                        <tr class="hover:bg-slate-50 transition-all">
                            <td class="px-6 py-4">
                                <input type="checkbox" name="selected[]" value="{{ $building->id }}"
                                    class="select-item rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-6 py-4">{{ $building->name }}</td>
                            <td class="px-6 py-4">{{ $building->code }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs rounded-full 
                                    {{ $building->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $building->status ? 'Aktif' : 'Tidak Aktif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ $building->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-sm">
                                <button type="button" onclick='editBuilding(@json($building))'
                                    class="text-blue-600 hover:text-blue-900 mr-3 transition-all">Edit</button>
                                <button type="button" onclick="deleteBuilding({{ $building->id }})"
                                    class="text-red-600 hover:text-red-900 transition-all">Hapus</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <select name="action"
                        class="rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="">Aksi Massal</option>
                        <option value="activate">Aktifkan</option>
                        <option value="deactivate">Nonaktifkan</option>
                        <option value="delete">Hapus</option>
                    </select>
                    <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-all shadow-sm"
                        onclick="return confirmBulkAction()">
                        Terapkan
                    </button>
                </div>
                <div>
                    {{ $buildings->links() }}
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal -->
<div id="formModal"
    class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full backdrop-blur-sm transition-all">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white border-gray-100">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-800" id="modalTitle">Tambah Gedung</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500 transition-all">
                <span class="text-2xl">&times;</span>
            </button>
        </div>

        <form id="buildingForm" action="{{ route('admin.master.buildings.store') }}" method="POST">
            @csrf
            <div id="methodField"></div>

            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                <input type="text" name="name" id="name" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Kode</label>
                <input type="text" name="code" id="code" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="1">Aktif</option>
                    <option value="0">Tidak Aktif</option>
                </select>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeModal()"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-all">Batal</button>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-all shadow-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function validateFilterForm() {
    // searchable-select: jika user mengetik tanpa memilih opsi,
    // kirim teks ketikan sebagai kata kunci search (fallback free-text)
    const searchWrap = document.querySelector('#filterForm [data-searchable-dropdown]');
    if (searchWrap) {
        const hiddenSearch = searchWrap.querySelector('input[type="hidden"]');
        const textSearch = searchWrap.querySelector('input[type="text"]');
        if (hiddenSearch && textSearch && !hiddenSearch.value && textSearch.value.trim() !== '') {
            hiddenSearch.value = textSearch.value.trim();
        }
    }
    const status = document.getElementById('filter_status').value;
    const fromDate = document.getElementById('filter_from_date').value;
    const toDate = document.getElementById('filter_to_date').value;

    // Status, pencarian, dan rentang tanggal bisa dipakai sendiri-sendiri
    // atau dikombinasikan; yang dicek hanya validitas rentang tanggal.
    if (fromDate && toDate && fromDate > toDate) {
        Swal.fire({
            title: 'Rentang Tanggal Tidak Valid',
            text: 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir',
            icon: 'error',
            confirmButtonText: 'Baik',
            confirmButtonColor: '#ef4444',
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        });
        return false;
    }

    return true;
}

function openModal(building = null) {
    const modal = document.getElementById('formModal');
    const form = document.getElementById('buildingForm');
    const methodField = document.getElementById('methodField');
    const modalTitle = document.getElementById('modalTitle');

    if (building) {
        form.action = `{{ url('admin/master/buildings') }}/${building.id}`;
        methodField.innerHTML = '@method("PUT")';
        document.getElementById('name').value = building.name;
        document.getElementById('code').value = building.code;
        document.getElementById('status').value = building.status;
        modalTitle.textContent = 'Edit Gedung';
    } else {
        form.action = "{{ route('admin.master.buildings.store') }}";
        methodField.innerHTML = '';
        form.reset();
        modalTitle.textContent = 'Tambah Gedung';
    }

    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('formModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('formModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

function editBuilding(building) {
    openModal(building);
}

function deleteBuilding(id) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal',
        showClass: {
            popup: 'animate__animated animate__fadeInDown'
        },
        hideClass: {
            popup: 'animate__animated animate__fadeOutUp'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `{{ url('admin/master/buildings') }}/${id}`;
            form.innerHTML = `
                @csrf
                @method('DELETE')
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Select all functionality
    const selectAll = document.querySelector('.select-all');
    const selectItems = document.querySelectorAll('.select-item');

    selectAll.addEventListener('change', function() {
        selectItems.forEach(item => {
            item.checked = this.checked;
        });
    });

    selectItems.forEach(item => {
        item.addEventListener('change', function() {
            const allChecked = Array.from(selectItems).every(item => item.checked);
            selectAll.checked = allChecked;
        });
    });
});

function confirmBulkAction() {
    const action = document.querySelector('select[name="action"]').value;
    const selectedItems = document.querySelectorAll('input[name="selected[]"]:checked');

    if (selectedItems.length === 0) {
        Swal.fire({
            title: 'Tidak Ada Item Dipilih',
            text: 'Mohon pilih item untuk melakukan tindakan massal',
            icon: 'warning',
            confirmButtonText: 'Baik',
            confirmButtonColor: '#eab308',
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        });
        return false;
    }

    if (action === '') {
        Swal.fire({
            title: 'Tidak Ada Tindakan Dipilih',
            text: 'Mohon pilih tindakan yang ingin dilakukan',
            icon: 'warning',
            confirmButtonText: 'Baik',
            confirmButtonColor: '#eab308',
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        });
        return false;
    }

    if (action === 'delete') {
        return Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Ya, hapus semua!',
            cancelButtonText: 'Batal',
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        }).then((result) => {
            return result.isConfirmed;
        });
    }

    return true;
}
</script>
@endpush
@endsection