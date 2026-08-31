@extends('user.layouts.app')

@section('title', 'Order Perbaikan')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<!-- Latar belakang dengan warna biru muda solid dan padding bawah yang sesuai -->
<div class="min-h-screen bg-gradient-to-r from-green-50 to-blue-50 pb-24">
    <div class="container mx-auto px-4 py-6">
        <!-- Header Halaman dengan Filter -->
        <div class="mb-6 bg-white bg-gradient-to-r from-green-600 to-blue-300 rounded-lg p-6 shadow-sm">
            <div class="flex flex-col space-y-4">
                <!-- Judul dan Tombol Aksi -->
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-white">Order Perbaikan</h1>
                        <p class="mt-2 text-sm text-white">Kelola permintaan perbaikan barang dan peralatan</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('user.administrasi-umum.order-barang.konfirmasi') }}"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            Order Dikonfirmasi
                        </a>
                        <a href="{{ route('user.administrasi-umum.order-barang.reject') }}"
                            class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Order Ditolak
                        </a>
                        <button onclick="showNewOrderForm()"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition duration-150 ease-in-out shadow-sm hover:shadow transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4"></path>
                            </svg>
                            Buat Order Baru
                        </button>
                    </div>
                </div>

                <!-- Bagian Pencarian dan Filter -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="relative md:col-span-2">
                        <input type="text" id="search" name="search"
                            class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200"
                            placeholder="Cari nomor order, nama barang...">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <input type="date" id="start_date" name="start_date"
                            class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200"
                            placeholder="Tanggal Mulai">
                    </div>
                    <div>
                        <input type="date" id="end_date" name="end_date"
                            class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200"
                            placeholder="Tanggal Akhir">
                    </div>
                </div>
            </div>
        </div>

        <!-- In Progress Orders Cards -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Order Dalam Proses</h2>
                <div class="flex space-x-2">
                    <button onclick="scrollCards('left')"
                        class="p-2 rounded-full bg-white shadow-sm hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7">
                            </path>
                        </svg>
                    </button>
                    <button onclick="scrollCards('right')"
                        class="p-2 rounded-full bg-white shadow-sm hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="relative">
                <div id="cardsContainer" class="flex overflow-x-auto pb-2 space-x-4 scrollbar-hide scroll-smooth">
                    @include('user.administrasi-umum.order-perbaikan._in_progress_cards', ['inProgressOrders' =>
                    $inProgressOrders])
                </div>
            </div>
        </div>

        <!-- Open Orders Table Card -->
        <div class="bg-white rounded-lg shadow-sm">
            <!-- Table Header Card -->
            <div class="bg-gradient-to-r from-green-600 to-blue-200 p-4 rounded-t-lg">
                <h2 class="text-xl font-semibold text-white">Daftar Order</h2>
                <p class="text-sm text-white opacity-80">Kelola dan pantau status order Anda</p>
            </div>

            <!-- Table Section -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                Nomor Order
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                Unit
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                Nama Barang
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                Prioritas
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-sm" id="ordersTableBody">
                        @include('user.administrasi-umum.order-perbaikan._table', ['orders' => $orders])
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="hidden p-12 text-center">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 text-blue-600 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Order</h3>
                <p class="text-gray-500 mb-6">Mulai dengan membuat order perbaikan baru</p>
                <button onclick="showNewOrderForm()"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition duration-150 ease-in-out transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Buat Order Baru
                </button>
            </div>
        </div>
    </div>
</div>

<!-- New Order Form Modal -->
<div id="newOrderModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-start justify-center pt-10">
    <div class="bg-white w-full mx-4 relative rounded-lg" style="max-width: 1200px;">
        <!-- Modal Header -->
        <div class="px-6 py-3 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-lg">
            <h2 class="text-xl font-bold text-gray-900">Data Baru</h2>
            <button onclick="closeNewOrderForm()"
                class="text-gray-400 hover:text-gray-500 transition-colors duration-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="newOrderForm" class="p-4">
            @csrf
            <div class="grid grid-cols-12 gap-4">
                <!-- Kolom Kiri -->
                <div class="col-span-8 space-y-4">
                    <!-- Nomor dan Tanggal -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nomor</label>
                            <input type="text" name="nomor" id="nomor" readonly
                                class="w-full px-3 py-1.5 bg-gray-50 border border-gray-300 rounded-md text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                            <div class="flex gap-2">
                                <input type="text" id="tanggal_display" readonly
                                    class="w-2/3 px-3 py-1.5 bg-gray-50 border border-gray-300 rounded-md text-sm">
                                <input type="text" id="waktu_display" readonly
                                    class="w-1/3 px-3 py-1.5 bg-gray-50 border border-gray-300 rounded-md text-sm">
                            </div>
                            <input type="hidden" name="tanggal" id="tanggal_input">
                        </div>
                    </div>

                    <!-- Unit Proses dan Penerima -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Proses</label>
                            <div class="flex gap-2">
                                <select id="unit_proses_code" name="unit_proses_code" required
                                    class="w-1/3 px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                                    <option value="">Pilih Kode</option>
                                    @foreach($unitProses as $unit)
                                    <option value="{{ $unit->code }}" data-name="{{ $unit->name }}">{{ $unit->code }}
                                    </option>
                                    @endforeach
                                </select>
                                <input type="text" id="unit_proses_name" name="unit_proses_name" readonly
                                    class="w-2/3 px-3 py-1.5 bg-gray-50 border border-gray-300 rounded-md text-sm">
                                <input type="hidden" name="unit_proses" id="unit_proses">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Penerima</label>
                            <div class="flex gap-2">
                                <input type="text" value="MTC" readonly
                                    class="w-1/3 px-3 py-1.5 bg-gray-50 border border-gray-300 rounded-md text-sm">
                                <input type="text" value="Maintenance" readonly
                                    class="w-2/3 px-3 py-1.5 bg-gray-50 border border-gray-300 rounded-md text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- Peminta dan Prioritas -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Peminta</label>
                            <input type="text" value="{{ auth()->user()->name }}" readonly
                                class="w-full px-3 py-1.5 bg-gray-50 border border-gray-300 rounded-md text-sm">
                            <input type="hidden" name="nama_peminta" value="{{ auth()->user()->name }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Permintaan Pengerjaan</label>
                            <select name="prioritas" required
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                                <option value="">Pilih Prioritas</option>
                                <option value="RENDAH">RENDAH</option>
                                <option value="SEDANG">SEDANG</option>
                                <option value="TINGGI/URGENT">TINGGI/URGENT</option>
                            </select>
                        </div>
                    </div>

                    <!-- Barang Details -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Barang</label>
                            <select name="jenis_barang" required
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Pilih Jenis Barang</option>
                                <option value="Inventaris">Inventaris</option>
                                <option value="Umum">Umum</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kode Inventaris</label>
                            <input type="text" name="kode_inventaris" required
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Barang</label>
                        <input type="text" name="nama_barang" required
                            class="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                        <div class="relative">
                            <input type="text" id="lokasi_search"
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm"
                                placeholder="Cari lokasi..." autocomplete="off">
                            <div id="lokasi_results"
                                class="absolute z-10 w-full mt-1 bg-white shadow-lg rounded-md border border-gray-200 hidden max-h-60 overflow-y-auto">
                            </div>
                            <select name="lokasi" id="lokasi_select" required class="hidden">
                                @foreach($locations ?? [] as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keluhan</label>
                        <textarea name="keluhan" rows="2" required
                            class="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto</label>
                        <div class="flex items-center space-x-2">
                            <div class="flex-1">
                                <div
                                    class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                            viewBox="0 0 48 48">
                                            <path
                                                d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600">
                                            <label for="foto"
                                                class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                                <span>Upload foto</span>
                                                <input id="foto" name="foto" type="file" class="sr-only"
                                                    accept="image/*">
                                            </label>
                                            <p class="pl-1">atau drag and drop</p>
                                        </div>
                                        <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                                    </div>
                                </div>
                            </div>
                            <div id="preview-container" class="hidden w-32">
                                <img id="preview-image" src="#" alt="Preview"
                                    class="w-full h-32 object-cover rounded-lg">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan - Status Section -->
                <div class="col-span-4">
                    <div class="bg-gray-50 p-4 rounded-lg h-full">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <div class="text-sm text-gray-900">
                                    <span
                                        class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Open</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Follow Up</label>
                                <div class="text-sm text-gray-900">xxx</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                                <div class="text-sm text-gray-900">xxx</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama P. Jawab</label>
                                <div class="text-sm text-gray-900">xxx</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end space-x-3 mt-4 pt-3 border-t border-gray-100">
                <button type="button" onclick="closeNewOrderForm()"
                    class="px-4 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit"
                    class="px-4 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

<script>
let refreshInterval;

function handleUnitProsesSelection() {
    const select = document.getElementById('unit_proses_code');
    const nameInput = document.getElementById('unit_proses_name');
    const hiddenInput = document.getElementById('unit_proses');

    select.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const name = selectedOption.getAttribute('data-name');
        const code = selectedOption.value;

        nameInput.value = name || '';
        hiddenInput.value = code || '';
    });
}

async function showNewOrderForm() {
    const modal = document.getElementById('newOrderModal');
    const nomorInput = document.getElementById('nomor');

    try {
        modal.classList.remove('hidden');
        const token = document.querySelector('meta[name="csrf-token"]').content;
        const response = await fetch('{{ route("user.administrasi-umum.order-perbaikan.create") }}', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            }
        });

        if (!response.ok) throw new Error('Koneksi jaringan bermasalah');

        const result = await response.json();
        console.log('Response:', result); // Debug log

        if (result.success && result.data) {
            // Clear any existing value first
            if (nomorInput) {
                nomorInput.value = '';
                // Set the new order number
                nomorInput.value = result.data.nomor;
            }

            const date = new Date(result.data.tanggal);
            const formattedDate = date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            }).split('/').join('-');
            const formattedTime = date.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });

            document.getElementById('tanggal_display').value = formattedDate;
            document.getElementById('waktu_display').value = formattedTime;
            document.getElementById('tanggal_input').value = result.data.tanggal;

            // Reset and populate unit proses select
            const unitProsesSelect = document.getElementById('unit_proses_code');
            if (unitProsesSelect && result.data.unitProses) {
                unitProsesSelect.innerHTML = '<option value="">Pilih Kode</option>' +
                    result.data.unitProses.map(unit =>
                        `<option value="${unit.code}" data-name="${unit.name}">${unit.code}</option>`
                    ).join('');
            }

            // Reset and populate location select
            const locationSelect = document.querySelector('select[name="lokasi"]');
            if (locationSelect && result.data.locations) {
                locationSelect.innerHTML = result.data.locations.map(loc =>
                    `<option value="${loc.id}">${loc.name}</option>`
                ).join('');

                // Initialize autocomplete after populating locations
                initLocationAutocomplete();
            }

            handleUnitProsesSelection();

            // Set user data
            if (result.data.user) {
                const pemintaInput = document.querySelector('input[name="nama_peminta"]');
                if (pemintaInput) {
                    pemintaInput.value = result.data.user.name;
                }
                const pemintaDisplay = document.querySelector(
                    'input[value="{{ auth()->user()->name }}"][readonly]');
                if (pemintaDisplay) {
                    pemintaDisplay.value = result.data.user.name;
                }
            }
        } else {
            throw new Error(result.message || 'Format respons tidak valid');
        }
    } catch (error) {
        console.error('Error:', error);
        createNotification('Terjadi kesalahan saat membuat order baru. Silakan coba lagi.', 'error');
        closeNewOrderForm();
    }
}

function closeNewOrderForm() {
    const modal = document.getElementById('newOrderModal');
    const form = document.getElementById('newOrderForm');
    modal.classList.add('hidden');
    if (form) {
        form.reset();
        // Clear specific fields
        document.getElementById('unit_proses_name').value = '';
        document.getElementById('unit_proses').value = '';
        document.getElementById('nomor').value = '';
    }
}

document.getElementById('newOrderForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    try {
        const formData = new FormData(this);

        const unitProsesCode = formData.get('unit_proses_code');
        if (!unitProsesCode) {
            throw new Error('Unit Proses harus dipilih');
        }

        const response = await fetch('{{ route("user.administrasi-umum.order-perbaikan.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();

        if (response.ok && result.success) {
            createNotification(result.message, 'success');
            closeNewOrderForm();
            await refreshOrderList();
        } else if (response.status === 422) {
            const errors = result.errors;
            let errorMessage = '<ul>';
            Object.values(errors).forEach(error => {
                errorMessage += `<li>${error[0]}</li>`;
            });
            errorMessage += '</ul>';

            createNotification(errorMessage, 'error');
        } else {
            throw new Error(result.message || 'Terjadi kesalahan saat membuat order');
        }
    } catch (error) {
        console.error('Error:', error);
        createNotification(error.message || 'Terjadi kesalahan saat membuat order. Silakan coba lagi.',
            'error');
    }
});

async function refreshOrderList() {
    try {
        const response = await fetch('{{ route("user.administrasi-umum.order-barang") }}', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) throw new Error('Gagal memperbarui daftar order');

        const result = await response.json();
        const tableBody = document.getElementById('ordersTableBody');
        tableBody.innerHTML = result.html;

        const emptyState = document.getElementById('emptyState');
        const hasOrders = tableBody.querySelector('tr:not([data-empty])');
        emptyState.classList.toggle('hidden', hasOrders);
    } catch (error) {
        console.error('Kesalahan memperbarui daftar order:', error);
    }
}

function showOrderDetail(id) {
    window.location.href = `/user/administrasi-umum/order-perbaikan/${id}`;
}

document.addEventListener('DOMContentLoaded', function() {
    refreshInterval = setInterval(refreshOrderList, 30000);

    // Initialize location autocomplete on page load
    initLocationAutocomplete();
});

window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

document.getElementById('search').addEventListener('input', debounce(function(e) {
    filterOrders();
}, 300));

document.getElementById('start_date').addEventListener('change', filterOrders);
document.getElementById('end_date').addEventListener('change', filterOrders);

async function filterOrders() {
    const searchTerm = document.getElementById('search').value;
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    try {
        const response = await fetch(
            `{{ route('user.administrasi-umum.order-barang') }}?search=${searchTerm}&start_date=${startDate}&end_date=${endDate}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

        if (!response.ok) throw new Error('Gagal memfilter data order');

        const result = await response.json();

        const tableBody = document.getElementById('ordersTableBody');
        tableBody.innerHTML = result.html;

        const cardsContainer = document.getElementById('cardsContainer');
        cardsContainer.innerHTML = result.inProgressHtml;

        const emptyState = document.getElementById('emptyState');
        const hasOrders = tableBody.querySelector('tr:not([data-empty])');
        emptyState.classList.toggle('hidden', hasOrders);
    } catch (error) {
        console.error('Kesalahan saat memfilter order:', error);
    }
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

document.getElementById('foto').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-image').src = e.target.result;
            document.getElementById('preview-container').classList.remove('hidden');
        }
        reader.readAsDataURL(file);
    }
});

const dropZone = document.querySelector('.border-dashed');
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, unhighlight, false);
});

function highlight(e) {
    dropZone.classList.add('border-blue-500', 'bg-blue-50');
}

function unhighlight(e) {
    dropZone.classList.remove('border-blue-500', 'bg-blue-50');
}

dropZone.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const file = dt.files[0];

    if (file && file.type.startsWith('image/')) {
        document.getElementById('foto').files = dt.files;
        const event = new Event('change');
        document.getElementById('foto').dispatchEvent(event);
    }
}

function scrollCards(direction) {
    const container = document.getElementById('cardsContainer');
    const scrollAmount = 320;

    if (direction === 'left') {
        container.scrollBy({
            left: -scrollAmount,
            behavior: 'smooth'
        });
    } else {
        container.scrollBy({
            left: scrollAmount,
            behavior: 'smooth'
        });
    }
}

// Location autocomplete functionality
function initLocationAutocomplete() {
    const lokasiSearch = document.getElementById('lokasi_search');
    const lokasiResults = document.getElementById('lokasi_results');
    const lokasiSelect = document.getElementById('lokasi_select');

    if (!lokasiSearch || !lokasiResults || !lokasiSelect) return;

    // Store all locations for filtering
    const locations = [];
    for (const option of lokasiSelect.options) {
        locations.push({
            id: option.value,
            name: option.text
        });
    }

    // Set default selected value if exists
    if (lokasiSelect.value) {
        const selectedLocation = locations.find(loc => loc.id == lokasiSelect.value);
        if (selectedLocation) {
            lokasiSearch.value = selectedLocation.name;
        }
    }

    // Filter locations based on input
    lokasiSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const filteredLocations = locations.filter(loc =>
            loc.name.toLowerCase().includes(searchTerm)
        );

        // Display results
        displayLocationResults(filteredLocations);
    });

    // Show all options when focusing on the input
    lokasiSearch.addEventListener('focus', function() {
        displayLocationResults(locations);
    });

    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target !== lokasiSearch && e.target !== lokasiResults) {
            lokasiResults.classList.add('hidden');
        }
    });

    function displayLocationResults(results) {
        // Clear previous results
        lokasiResults.innerHTML = '';

        if (results.length > 0) {
            results.forEach(location => {
                const div = document.createElement('div');
                div.className = 'px-4 py-2 cursor-pointer hover:bg-gray-100';
                div.textContent = location.name;
                div.dataset.id = location.id;

                div.addEventListener('click', function() {
                    lokasiSearch.value = location.name;
                    lokasiSelect.value = location.id;
                    lokasiResults.classList.add('hidden');
                });

                lokasiResults.appendChild(div);
            });

            lokasiResults.classList.remove('hidden');
        } else {
            lokasiResults.classList.add('hidden');
        }
    }
}
</script>
@endsection