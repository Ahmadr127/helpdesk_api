{{-- Component: Department Form Modal (Add/Edit) - searchable select untuk Lokasi & Gedung --}}
<div id="formModal"
    class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full backdrop-blur-sm transition-all z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white border-gray-100">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-800" id="modalTitle">Add Department</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500 transition-all">
                <span class="text-2xl">&times;</span>
            </button>
        </div>

        <form id="departmentForm" action="{{ route('admin.master.departments.store') }}" method="POST">
            @csrf
            <div id="methodField"></div>

            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" id="name" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                <input type="text" name="code" id="code" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi <span class="text-gray-400">(opsional)</span></label>
                <x-searchable-select name="location_id" :options="$locations->pluck('name','id')->toArray()" selected="" placeholder="Cari lokasi..." id="location_id" />
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Gedung <span class="text-gray-400">(opsional)</span></label>
                <x-searchable-select name="building_id" :options="$buildings->pluck('name','id')->toArray()" selected="" placeholder="Cari gedung..." id="building_id" />
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
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-all">Cancel</button>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-all shadow-sm">Save</button>
            </div>
        </form>
    </div>
</div>
