@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 bg-gradient-to-r from-white to-blue-300 p-6 rounded-lg shadow-md flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.master.index') }}"
                class="bg-gradient-to-r from-gray-500 to-gray-600 text-white px-4 py-2 rounded-lg hover:from-gray-600 hover:to-gray-700 transition-all flex items-center shadow-md">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back
            </a>
            <h2 class="text-xl font-semibold text-gray-800">Categories Management</h2>
        </div>
        <button onclick="openModal()"
            class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-2 rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all shadow-md">
            Add Category
        </button>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6 border border-gray-100">
        <form id="filterForm" action="{{ route('admin.master.categories.index') }}" method="GET"
            class="grid grid-cols-1 md:grid-cols-4 gap-4" onsubmit="return validateFilterForm()">
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
                    class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-2 rounded-md hover:from-blue-600 hover:to-blue-700 transition-all shadow-sm">Filter</button>
                <a href="{{ route('admin.master.categories.index') }}"
                    class="bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 px-4 py-2 rounded-md hover:from-gray-200 hover:to-gray-300 transition-all shadow-sm">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-100">
        <form action="{{ route('admin.master.bulk-action', 'categories') }}" method="POST">
            @csrf
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-green-50 to-blue-50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input type="checkbox"
                                    class="select-all rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Unit Proses</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created At</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($categories as $category)
                        <tr class="hover:bg-gradient-to-r hover:from-green-50 hover:to-blue-50 transition-all">
                            <td class="px-6 py-4">
                                <input type="checkbox" name="selected[]" value="{{ $category->id }}"
                                    class="select-item rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-6 py-4">{{ $category->name }}</td>
                            <td class="px-6 py-4">{{ $category->unitProses ? $category->unitProses->name : '-' }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs rounded-full 
                                    {{ $category->status ? 'bg-gradient-to-r from-green-100 to-green-200 text-green-800' : 'bg-gradient-to-r from-red-100 to-red-200 text-red-800' }}">
                                    {{ $category->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ $category->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-sm">
                                <button type="button" onclick='editCategory(@json($category))'
                                    class="text-blue-600 hover:text-blue-900 mr-3 transition-all">Edit</button>
                                <button type="button" onclick="deleteCategory({{ $category->id }})"
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
                        class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-2 rounded-md hover:from-blue-600 hover:to-blue-700 transition-all shadow-sm"
                        onclick="return confirmBulkAction()">
                        Apply
                    </button>
                </div>
                <div>
                    {{ $categories->links() }}
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
            <h3 class="text-lg font-medium text-gray-800" id="modalTitle">Add Category</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500 transition-all">
                <span class="text-2xl">&times;</span>
            </button>
        </div>

        <form id="categoryForm" action="{{ route('admin.master.categories.store') }}" method="POST">
            @csrf
            <div id="methodField"></div>

            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" id="name" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label for="unit_proses_id" class="block text-sm font-medium text-gray-700 mb-1">Unit Proses</label>
                <select name="unit_proses_id" id="unit_proses_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">Select Unit Proses</option>
                    @foreach($unitProses as $unit)
                    <option value="{{ $unit->id }}" {{ old('unit_proses_id') == $unit->id ? 'selected' : '' }}>
                        {{ $unit->name }} ({{ $unit->code }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeModal()"
                    class="px-4 py-2 bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 rounded-md hover:from-gray-200 hover:to-gray-300 transition-all">Cancel</button>
                <button type="submit"
                    class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-md hover:from-blue-600 hover:to-blue-700 transition-all shadow-sm">Save</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openModal(category = null) {
    const modal = document.getElementById('formModal');
    const form = document.getElementById('categoryForm');
    const methodField = document.getElementById('methodField');
    const modalTitle = document.getElementById('modalTitle');

    if (category) {
        form.action = `{{ url('admin/master/categories') }}/${category.id}`;
        methodField.innerHTML = '@method("PUT")';
        document.getElementById('name').value = category.name;
        document.getElementById('status').value = category.status;
        document.getElementById('unit_proses_id').value = category.unit_proses_id || '';
        modalTitle.textContent = 'Edit Category';
    } else {
        form.action = "{{ route('admin.master.categories.store') }}";
        methodField.innerHTML = '';
        form.reset();
        modalTitle.textContent = 'Add Category';
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

function editCategory(category) {
    openModal(category);
}

function deleteCategory(id) {
    if (confirm('Are you sure you want to delete this category?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `{{ url('admin/master/categories') }}/${id}`;
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

    // Check if any of the filter fields are filled
    if (status || fromDate || toDate) {
        // If any field is filled, all fields must be filled
        if (!status || !fromDate || !toDate) {
            alert('Please fill in all filter fields (Status, From Date, and To Date) to apply the filter.');
            return false;
        }
        
        // Validate that from_date is not greater than to_date
        if (fromDate > toDate) {
            alert('From Date cannot be greater than To Date');
            return false;
        }
    }
    
    return true;
}
</script>
@endpush
@endsection