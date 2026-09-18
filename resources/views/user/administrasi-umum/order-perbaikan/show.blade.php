@extends('user.layouts.app')

@section('title', 'Detail Order Perbaikan')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
function confirmDelete(orderId) {
    orderIdToDelete = orderId;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    orderIdToDelete = null;
}

function deleteOrder() {
    if (!orderIdToDelete) return;

    fetch(`/user/administrasi-umum/order-perbaikan/${orderIdToDelete}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(json => Promise.reject(json));
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.location.href = '{{ route("user.administrasi-umum.order-barang") }}';
            } else {
                throw new Error(data.message || 'Terjadi kesalahan saat menghapus order.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Terjadi kesalahan saat menghapus order.');
        })
        .finally(() => {
            closeDeleteModal();
        });
}

let orderIdToDelete = null;

function openImageModal(src) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    modalImage.src = src;

    // Show modal with fade-in effect
    modal.classList.remove('hidden');
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.style.opacity = '1';
    }, 10);

    // Close modal when clicking outside the image
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeImageModal();
        }
    });
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');

    // Fade-out effect
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

// Close modal with escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
        closeDeleteModal();
    }
});

// Close delete modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    }
});
</script>

<div class="container mx-auto p-4">
    <div class="bg-white rounded-lg shadow-lg">
        <!-- Header Section -->
        <div class="bg-green-600 px-4 py-3 rounded-t-2xl">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-semibold text-white">Detail Order Perbaikan</h1>
                <div class="flex space-x-2">
                    @if($order->status === 'open')
                    <button onclick="confirmDelete('{{ $order->id }}')"
                        class="inline-flex items-center px-3 py-1.5 bg-white/10 hover:bg-red-500 hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 text-white text-sm font-medium rounded-md cursor-pointer">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                        Hapus
                    </button>
                    <a href="{{ route('user.administrasi-umum.order-perbaikan.edit', $order) }}"
                        class="inline-flex items-center px-3 py-1.5 bg-white/10 hover:bg-green-600 hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 text-white text-sm font-medium rounded-md cursor-pointer">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        Edit
                    </a>
                    @endif
                    <a href="{{ route('user.administrasi-umum.order-barang') }}"
                        class="inline-flex items-center px-3 py-1.5 bg-white/10 hover:bg-gray-500 hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 text-white text-sm font-medium rounded-md cursor-pointer">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Kembali
                    </a>
                </div>
            </div>
        </div>

        <!-- Order Details -->
        <div class="p-3">
            <!-- Main Info Grid -->
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-gray-50 p-2.5 rounded-lg">
                    <h3 class="text-base font-semibold mb-2">Informasi Order</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="space-y-0.5">
                            <p class="text-xs text-gray-500">Nomor Order</p>
                            <p class="text-sm font-medium">{{ $order->nomor }}</p>
                        </div>
                        <div class="space-y-0.5">
                            <p class="text-xs text-gray-500">Tanggal</p>
                            <p class="text-sm font-medium">{{ $order->tanggal->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="space-y-0.5">
                            <p class="text-xs text-gray-500">Status</p>
                            <span
                                class="inline-flex px-2 py-0.5 text-xs rounded-full {{ $order->getStatusBadgeClass() }}">
                                {{ $order->getStatusText() }}
                            </span>
                        </div>
                        <div class="space-y-0.5">
                            <p class="text-xs text-gray-500">Prioritas</p>
                            <span class="inline-flex px-2 py-0.5 text-xs rounded-full 
                                {{ match($order->prioritas) {
                                    'RENDAH' => 'bg-green-100 text-green-800',
                                    'SEDANG' => 'bg-yellow-100 text-yellow-800',
                                    'TINGGI/URGENT' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                } }}">
                                {{ $order->prioritas }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-2.5 rounded-lg">
                    <h3 class="text-base font-semibold mb-2">Informasi Unit</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="space-y-0.5">
                            <p class="text-xs text-gray-500">Unit Pengaju</p>
                            <p class="text-sm font-medium">{{ $order->department?->name ?? $order->unit_proses_name }}
                            </p>
                        </div>
                        @php
                            $unitProsesModel = $order->relationLoaded('unitProses') ? $order->getRelation('unitProses') : $order->unitProses()->getResults();
                        @endphp
                        <div class="space-y-0.5">
                            <p class="text-xs text-gray-500">Unit Proses</p>
                            <p class="text-sm font-medium">{{ $unitProsesModel?->name ? $unitProsesModel->name.' ('.$unitProsesModel->code.')' : '-' }}</p>
                        </div>
                        <div class="space-y-0.5">
                            <p class="text-xs text-gray-500">Unit Penerima</p>
                            <p class="text-sm font-medium">{{ $order->unit_penerima }}</p>
                        </div>
                        <div class="space-y-0.5">
                            <p class="text-xs text-gray-500">Peminta</p>
                            <p class="text-sm font-medium">{{ $order->nama_peminta }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-2.5 rounded-lg">
                    <h3 class="text-base font-semibold mb-2">Informasi Barang</h3>
                    <div class="grid grid-cols-1 gap-2">
                        <div class="space-y-0.5">
                            <p class="text-xs text-gray-500">Kode Inventaris</p>
                            <p class="text-sm font-medium">{{ $order->kode_inventaris ?: '-' }}</p>
                        </div>
                        <div class="space-y-0.5">
                            <p class="text-xs text-gray-500">Kategori Order</p>
                            <p class="text-sm font-medium">{{ $order->kategori_order ?? '-' }}</p>
                        </div>
                        <div class="space-y-0.5">
                            <p class="text-xs text-gray-500">Nama Barang</p>
                            <p class="text-sm font-medium">{{ $order->nama_barang }}</p>
                        </div>
                        <div class="space-y-0.5">
                            <p class="text-xs text-gray-500">Lokasi</p>
                            <p class="text-sm font-medium">{{ $order->location->name }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Keluhan and Foto Grid -->
            <div class="grid grid-cols-{{ $order->foto ? '2' : '1' }} gap-3 mt-3">
                <div class="bg-gray-50 p-2.5 rounded-lg">
                    <h3 class="text-base font-semibold mb-2">Keluhan</h3>
                    <p class="text-sm text-gray-700">{{ $order->keluhan }}</p>
                </div>

                @if($order->foto)
                <div class="bg-gray-50 p-2.5 rounded-lg">
                    <h3 class="text-base font-semibold mb-2">Foto</h3>
                    <div class="flex justify-center items-center">
                        <div class="relative group">
                            <img src="{{ Storage::url($order->foto) }}" alt="Foto Order Perbaikan"
                                class="rounded-lg w-48 h-36 object-cover bg-white shadow-sm cursor-zoom-in hover:opacity-95 hover:scale-105 transition-all duration-200"
                                onclick="openImageModal(this.src)">
                            <div class="absolute top-2 right-2 hidden group-hover:flex space-x-1">
                                <button onclick="openImageModal('{{ Storage::url($order->foto) }}')"
                                    class="p-1.5 bg-white rounded-full shadow hover:bg-gray-100 transition-all duration-200">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                </button>
                                <a href="{{ Storage::url($order->foto) }}" target="_blank"
                                    class="p-1.5 bg-white rounded-full shadow hover:bg-gray-100 transition-all duration-200">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14">
                                        </path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            <!-- User Confirmation Section (status tutup) -->
            @if($order->status === 'tutup')
            <div class="bg-violet-50 p-2.5 rounded-lg mt-3 border border-violet-200">
                <h3 class="text-base font-semibold mb-2 text-violet-900">Konfirmasi Selesai</h3>
                <p class="text-sm text-violet-700 mb-3">
                    Admin telah menutup order ini dan menunggu konfirmasi Anda. Apakah order sudah benar-benar selesai?
                    Konfirmasi Anda akan menjadi status akhir dari order ini.
                </p>
                <form action="{{ route('user.administrasi-umum.order-perbaikan.konfirmasi-selesai', $order) }}"
                    method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="konfirmasi" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            <option value="selesai">Ya, sudah selesai</option>
                            <option value="belum">Belum selesai</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                        <textarea name="catatan" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                            placeholder="Tulis catatan (opsional)..."></textarea>
                    </div>
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Kirim Konfirmasi
                    </button>
                </form>
            </div>
            @endif

            <!-- Order History -->
            <div class="bg-gray-50 p-3 rounded-lg">
                <h3 class="text-base font-semibold mb-3">Riwayat Order</h3>
                <div class="space-y-3">
                    @foreach($order->history()->latest()->get() as $history)
                    <div class="border-l-4
                        {{ match($history->status) {
                            'open' => 'border-blue-400',
                            'in_progress' => 'border-yellow-400',
                            'tutup' => 'border-violet-400',
                            'confirmed' => 'border-green-400',
                            'rejected' => 'border-red-400',
                            default => 'border-gray-400'
                        } }}
                        pl-3 py-2">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="inline-flex px-2 py-1 text-xs rounded-full
                                    {{ $history->status_badge_class }}">
                                    {{ $history->status_label }}
                                </span>
                                <p class="mt-2 text-sm text-gray-600">
                                    <span class="font-medium">Catatan:</span> {{ $history->keterangan ?? $history->follow_up ?? '-' }}
                                </p>
                            </div>
                            <div class="text-right text-sm text-gray-500">
                                <p>{{ $history->created_at->format('d/m/Y H:i') }}</p>
                                <p class="text-xs text-gray-400">{{ $history->creator->name }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal - Improved with animation -->
<div id="imageModal"
    class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4 transition-opacity duration-300">
    <div class="relative max-w-3xl w-full">
        <div class="bg-white p-1 rounded-lg shadow-lg">
            <img id="modalImage" src="" alt="Foto Order Perbaikan"
                class="max-h-[60vh] max-w-full object-contain mx-auto rounded">
        </div>
        <button onclick="closeImageModal()"
            class="absolute -top-3 -right-3 bg-white p-2 rounded-full shadow-md hover:bg-gray-100 transition-colors duration-200">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-3">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-4">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Konfirmasi Hapus</h3>
                <p class="text-sm text-gray-600 mb-4">Apakah Anda yakin ingin menghapus order perbaikan ini? Tindakan
                    ini tidak dapat dibatalkan.</p>
                <div class="flex justify-end space-x-2">
                    <button onclick="closeDeleteModal()"
                        class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md text-sm">
                        Batal
                    </button>
                    <button onclick="deleteOrder()"
                        class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection