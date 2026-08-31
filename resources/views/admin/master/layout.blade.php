@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-lg">
        <div class="p-6 border-b flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">@yield('title')</h2>
            <button onclick="openModal()"
                class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                @yield('add-button-text')
            </button>
        </div>

        <!-- Table Content -->
        @yield('table-content')

        <!-- Modal -->
        <div id="formModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold" id="modalTitle">@yield('modal-title')</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @yield('modal-content')
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openModal(data = null) {
    const modal = document.getElementById('formModal');
    const form = document.getElementById('dataForm');
    const titleElement = document.getElementById('modalTitle');

    if (data) {
        titleElement.textContent = 'Edit ' + '@yield('
        item - name ')';
        fillFormData(data);
    } else {
        titleElement.textContent = 'Add New ' + '@yield('
        item - name ')';
        form.reset();
    }

    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('formModal').classList.add('hidden');
}

function fillFormData(data) {
    Object.keys(data).forEach(key => {
        const input = document.getElementById(key);
        if (input) input.value = data[key];
    });
}

// Close modal when clicking outside
document.getElementById('formModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

@yield('extra-scripts')
</script>
@endpush
@endsection