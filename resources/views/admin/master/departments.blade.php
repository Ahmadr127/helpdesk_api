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
                Back
            </a>
            <h2 class="text-xl font-semibold text-gray-800">Departments Management</h2>
        </div>
        <button onclick="openModal()"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all shadow-md">
            Add Department
        </button>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6 border border-gray-100">
        <form id="filterForm" action="{{ route('admin.master.departments.index') }}" method="GET"
            class="grid grid-cols-1 md:grid-cols-4 gap-4" onsubmit="return validateFilterForm()">
            <div class="md:col-span-4">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                    placeholder="Cari berdasarkan nama atau kode…"
                    class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="filter_status"
                    class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">All Status</option>
                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" name="from_date" id="filter_from_date" value="{{ request('from_date') }}"
                    class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" name="to_date" id="filter_to_date" value="{{ request('to_date') }}"
                    class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-all shadow-sm">Filter</button>
                <a href="{{ route('admin.master.departments.index') }}"
                    class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-all shadow-sm">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-100">
        <form action="{{ route('admin.master.bulk-action', 'departments') }}" method="POST">
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
                                Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Lokasi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Gedung</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created At</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($departments as $department)
                        <tr class="hover:bg-slate-50 transition-all">
                            <td class="px-6 py-4">
                                <input type="checkbox" name="selected[]" value="{{ $department->id }}"
                                    class="select-item rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-6 py-4">{{ $department->name }}</td>
                            <td class="px-6 py-4">{{ $department->code }}</td>
                            <td class="px-6 py-4">
                                @if($department->location)
                                <span class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800">{{ $department->location->name }}</span>
                                @else
                                <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($department->building)
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ $department->building->name }}</span>
                                @else
                                <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs rounded-full 
                                    {{ $department->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $department->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ $department->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-sm">
                                <button type="button" onclick='editDepartment(@json($department))'
                                    class="text-blue-600 hover:text-blue-900 mr-3 transition-all">Edit</button>
                                <button type="button" onclick="deleteDepartment({{ $department->id }})"
                                    class="text-red-600 hover:text-red-900 transition-all">Delete</button>
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
                        <option value="">Bulk Action</option>
                        <option value="activate">Activate</option>
                        <option value="deactivate">Deactivate</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-all shadow-sm"
                        onclick="return confirmBulkAction()">
                        Apply
                    </button>
                </div>
                <div>
                    {{ $departments->links() }}
                </div>
            </div>
        </form>
    </div>
</div>

@include('admin.master.partials.department-form-modal')

@push('scripts')
<script>
function openModal(department = null) {
    const modal = document.getElementById('formModal');
    const form = document.getElementById('departmentForm');
    const methodField = document.getElementById('methodField');
    const modalTitle = document.getElementById('modalTitle');

    if (department) {
        form.action = `{{ url('admin/master/departments') }}/${department.id}`;
        methodField.innerHTML = '@method("PUT")';
        document.getElementById('name').value = department.name;
        document.getElementById('code').value = department.code;
        document.getElementById('status').value = department.status;
        const loc = department.location ? department.location.id : department.location_id;
        document.getElementById('location_id').value = loc || '';
        const gedung = department.building ? department.building.id : department.building_id;
        document.getElementById('building_id').value = gedung || '';
        if (typeof syncSearchableSelectInput === 'function') {
            syncSearchableSelectInput('location_id');
            syncSearchableSelectInput('building_id');
        }
        modalTitle.textContent = 'Edit Department';
    } else {
        form.action = "{{ route('admin.master.departments.store') }}";
        methodField.innerHTML = '';
        form.reset();
        // reset searchable inputs
        const locSearch = document.getElementById('location_id-search');
        const bdgSearch = document.getElementById('building_id-search');
        if (locSearch) locSearch.value = '';
        if (bdgSearch) bdgSearch.value = '';
        if (typeof filterSearchableSelect === 'function') {
            filterSearchableSelect('location_id', '');
            filterSearchableSelect('building_id', '');
        }
        modalTitle.textContent = 'Add Department';
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

function editDepartment(department) {
    openModal(department);
}

function deleteDepartment(id) {
    if (confirm('Are you sure you want to delete this department?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `{{ url('admin/master/departments') }}/${id}`;
        form.innerHTML = `
            @csrf
            @method('DELETE')
        `;
        document.body.appendChild(form);
        form.submit();
    }
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
        alert('Please select items to perform bulk action');
        return false;
    }

    if (action === '') {
        alert('Please select an action to perform');
        return false;
    }

    if (action === 'delete') {
        return confirm('Are you sure you want to delete the selected items?');
    }

    return true;
}

function validateFilterForm() {
    const status = document.getElementById('filter_status').value;
    const fromDate = document.getElementById('filter_from_date').value;
    const toDate = document.getElementById('filter_to_date').value;

    // Status, pencarian, dan rentang tanggal bisa dipakai sendiri-sendiri
    // atau dikombinasikan; yang dicek hanya validitas rentang tanggal.
    if (fromDate && toDate && fromDate > toDate) {
        alert('From Date cannot be greater than To Date');
        return false;
    }

    return true;
}
</script>
@endpush
@endsection