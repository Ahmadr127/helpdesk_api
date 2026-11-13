@extends('admin.layouts.app')

@section('title', 'Order Perbaikan - Confirmed')

@section('content')
<div class="w-full">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <!-- Header with back button -->
        <div
            class="bg-white -mx-6 -mt-6 px-6 py-4 mb-6 border-b border-gray-200 rounded-t-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="{{ route('admin.order-perbaikan.index') }}"
                        class="mr-4 text-gray-700 hover:text-gray-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <h2 class="text-2xl font-bold text-gray-700">Order Perbaikan - Confirmed</h2>
                </div>

                <!-- Export Buttons -->
                <div class="flex space-x-2">
                    <button id="exportSelected" disabled
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-blue-400 cursor-not-allowed transition-colors duration-200"
                        onclick="exportSelectedData()">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export Terpilih (<span id="selectedCount">0</span>)
                    </button>

                    <form action="{{ route('admin.order-perbaikan.export-data') }}" method="POST"
                        class="inline">
                        @csrf
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export Semua
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info badge -->
        <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Halaman ini menampilkan order perbaikan yang telah dikonfirmasi dan selesai. Pilih data yang
                        ingin diekspor dengan mencentang checkbox di samping kiri.
                    </p>
                </div>
            </div>
        </div>

        <!-- Selection Info -->
        <div class="mb-4 flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-200">
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <input type="checkbox" id="selectAll"
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <label for="selectAll" class="text-sm text-gray-600">Pilih Semua di Halaman Ini</label>
                </div>
            </div>
            <div class="text-sm text-gray-500">
                <span id="selectedInfo">0 data dipilih</span>
            </div>
        </div>

        @include('admin.order-perbaikan._table', [
            'orders' => $orders,
            'filter' => $filter ?? null,
            'filterAction' => route('admin.order-perbaikan.confirmed'),
            'title' => 'Order Perbaikan - Confirmed',
            'kategoris' => $kategoris ?? collect(),
            'locations' => $locations ?? collect(),
            'lockedStatus' => $lockedStatus ?? 'confirmed',
            'showCheckbox' => true,
            'showConfirmedAt' => true,
            'showPenanggungJawab' => true,
            'emptyTitle' => 'Tidak ada order perbaikan yang terkonfirmasi',
            'emptySubtitle' => 'Data akan muncul di sini ketika ada order yang terkonfirmasi',
        ])
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const orderCheckboxes = () => Array.from(document.querySelectorAll('.order-checkbox'));
    const exportSelectedButton = document.getElementById('exportSelected');
    const selectedCountElement = document.getElementById('selectedCount');
    const selectedInfoElement = document.getElementById('selectedInfo');

    // Select all functionality
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        orderCheckboxes().forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        updateExportButton();
    });

    // Individual checkbox functionality
    document.addEventListener('change', function(event) {
        if (event.target.classList.contains('order-checkbox')) {
            updateExportButton();
            const boxes = orderCheckboxes();
            selectAllCheckbox.checked = boxes.length > 0 && boxes.every(cb => cb.checked);
            selectAllCheckbox.indeterminate = !selectAllCheckbox.checked && boxes.some(cb => cb.checked);
        }
    });

    // Update export button state and selection info
    function updateExportButton() {
        const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;

        selectedCountElement.textContent = checkedCount;
        selectedInfoElement.textContent = `${checkedCount} data dipilih`;

        if (checkedCount > 0) {
            exportSelectedButton.disabled = false;
            exportSelectedButton.classList.remove('bg-gray-blue-400', 'cursor-not-allowed');
            exportSelectedButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
        } else {
            exportSelectedButton.disabled = true;
            exportSelectedButton.classList.add('bg-gray-blue-400', 'cursor-not-allowed');
            exportSelectedButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        }
    }

    // Export selected data
    window.exportSelectedData = function() {
        const selectedIds = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb
            .value);
        if (selectedIds.length > 0) {
            const dateFrom = document.querySelector('[data-pengajuan-filter-form] input[name="date_from"]')?.value;
            const dateTo = document.querySelector('[data-pengajuan-filter-form] input[name="date_to"]')?.value;

            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ route('admin.order-perbaikan.export-data') }}";

            // Add CSRF token
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);

            // Add selected_ids as hidden input
            const selectedIdsInput = document.createElement('input');
            selectedIdsInput.type = 'hidden';
            selectedIdsInput.name = 'selected_ids';
            selectedIdsInput.value = selectedIds.join(',');
            form.appendChild(selectedIdsInput);

            // Add status as hidden input
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = 'confirmed';
            form.appendChild(statusInput);

            // Add date_from if exists
            if (dateFrom) {
                const dateFromInput = document.createElement('input');
                dateFromInput.type = 'hidden';
                dateFromInput.name = 'date_from';
                dateFromInput.value = dateFrom;
                form.appendChild(dateFromInput);
            }

            // Add date_to if exists
            if (dateTo) {
                const dateToInput = document.createElement('input');
                dateToInput.type = 'hidden';
                dateToInput.name = 'date_to';
                dateToInput.value = dateTo;
                form.appendChild(dateToInput);
            }

            // Append form to body and submit
            document.body.appendChild(form);
            form.submit();
        }
    };
});
</script>
@endsection