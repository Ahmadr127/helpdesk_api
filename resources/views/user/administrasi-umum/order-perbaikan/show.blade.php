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
    modal.classList.remove('hidden');
    modal.style.opacity = '0';
    setTimeout(() => { modal.style.opacity = '1'; }, 10);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeImageModal();
    });
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.style.opacity = '0';
    setTimeout(() => { modal.classList.add('hidden'); }, 300);
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
        closeDeleteModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
    }
});
</script>

<div class="min-h-screen bg-gradient-to-r from-green-50 to-blue-50 pb-24">
    <div class="container mx-auto px-4 py-6">
        {{-- <!-- Back Button -->
        <div class="mb-5">
            <a href="{{ route('user.administrasi-umum.order-barang') }}"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali ke Daftar
            </a>
        </div> --}}

        <!-- Header Card -->
        <div class="mb-6 bg-green-600 rounded-lg p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl font-bold text-white">Order #{{ $order->nomor }}</h1>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20 text-white ring-1 ring-white/30">
                            <span class="w-1.5 h-1.5 rounded-full mr-2 bg-white"></span>
                            {{ $order->getStatusText() }}
                        </span>
                    </div>
                    <p class="text-sm text-green-100 mt-1">Dibuat pada {{ $order->tanggal->format('d M Y, H:i') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    @if($order->status === 'open')
                    <a href="{{ route('user.administrasi-umum.order-perbaikan.edit', $order) }}"
                        class="inline-flex items-center px-4 py-2 bg-green-700 hover:bg-green-800 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </a>
                    <button onclick="confirmDelete('{{ $order->id }}')"
                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Hapus
                    </button>
                    @endif
                </div>
            </div>
        </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Order Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-base font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Informasi Order
                    </h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nomor Order</dt>
                            <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $order->nomor }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tanggal</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $order->tanggal->format('d M Y, H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold {{ $order->getStatusBadgeClass() }}">
                                    {{ $order->getStatusText() }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Prioritas</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold {{ match($order->prioritas) {
                                    'RENDAH' => 'bg-green-50 text-green-700 ring-1 ring-green-600/20',
                                    'SEDANG' => 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-600/20',
                                    'TINGGI/URGENT' => 'bg-red-50 text-red-700 ring-1 ring-red-600/20',
                                    default => 'bg-gray-50 text-gray-700 ring-1 ring-gray-600/20'
                                } }}">
                                    {{ $order->prioritas }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Unit Pengaju</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $order->department?->name ?? $order->unit_proses_name }}</dd>
                        </div>
                        @php
                            $unitProsesModel = $order->relationLoaded('unitProses') ? $order->getRelation('unitProses') : $order->unitProses()->getResults();
                        @endphp
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Unit Proses</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $unitProsesModel?->name ? $unitProsesModel->name.' ('.$unitProsesModel->code.')' : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Unit Penerima</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $order->unit_penerima ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Peminta</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $order->nama_peminta }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Item Details -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-base font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        Detail Barang
                    </h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        @if(!empty($order->kode_inventaris) && $order->kode_inventaris !== '-')
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Kode Inventaris</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $order->kode_inventaris }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Kategori Order</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $order->kategori_order ?? '-' }}</dd>
                        </div>
                        @if(!empty($order->nama_barang))
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nama Barang</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $order->nama_barang }}</dd>
                        </div>
                        @endif
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Lokasi</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $order->location?->name ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Keluhan</dt>
                            <dd class="mt-1 text-sm text-gray-700 bg-gray-50 rounded-lg p-4 border border-gray-100 leading-relaxed">{{ $order->keluhan }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Photo -->
            @if($order->foto)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-base font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Foto
                    </h2>
                </div>
                <div class="p-6">
                    <div class="relative rounded-lg overflow-hidden bg-gray-100 max-w-md">
                        <img src="{{ Storage::url($order->foto) }}" alt="Foto Order Perbaikan"
                            class="w-full h-64 object-cover cursor-zoom-in hover:opacity-95 transition-opacity"
                            onclick="openImageModal(this.src)">
                        <div class="absolute inset-0 bg-black/0 hover:bg-black/40 transition-all duration-300 flex items-center justify-center">
                            <button onclick="openImageModal('{{ Storage::url($order->foto) }}')"
                                class="opacity-0 hover:opacity-100 transition-opacity bg-white/90 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Lihat Foto
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Right Column: Confirmation Form + Timeline -->
        <div class="lg:col-span-1 space-y-6 sticky top-6 self-start">
            @if($order->status === 'tutup')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-purple-50">
                    <h2 class="text-base font-semibold text-purple-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Konfirmasi Selesai
                    </h2>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600  leading-relaxed">
                       Silakan konfirmasi apakah order sudah benar-benar selesai.
                    </p>
                    <form action="{{ route('user.administrasi-umum.order-perbaikan.konfirmasi-selesai', $order) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <label class="block text-sm font-semibold text-gray-800 mb-2">Status Konfirmasi</label>
                        <select name="konfirmasi" required
                            class="w-full rounded-lg border border-gray-200 bg-white shadow-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm py-2.5 px-3 transition-all">
                            <option value="selesai">✅ Ya, sudah selesai</option>
                            <option value="belum">❌ Belum selesai</option>
                        </select>
                        
                        <label class="block text-sm font-semibold text-gray-800 mb-2">Catatan <span class="text-gray-400 font-normal">(opsional)</span></label>
                        <textarea name="catatan" rows="3"
                            class="w-full rounded-lg border border-gray-200 bg-white shadow-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-sm py-2.5 px-3 transition-all resize-none"
                            placeholder="Tulis catatan jika diperlukan..."></textarea>
                        
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Lampiran <span class="text-gray-400 font-normal">(opsional)</span></label>
                            <input type="file" name="lampiran" accept=".jpg,.jpeg,.png"
                                class="w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-purple-50 file:text-violet-700 hover:file:bg-violet-100 transition-all">
                            <p class="text-xs text-gray-500 mt-1">Format: JPG, JPEG, PNG. Maks 5MB.</p>
                        
                        <button type="submit"
                            class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Kirim Konfirmasi
                        </button>
                    </form>
                </div>
            </div>
            @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden sticky top-6">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-base font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Status Order
                    </h2>
                </div>
                <div class="p-6">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-3">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-900">{{ $order->getStatusText() }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            @if($order->status === 'open')
                            Order sedang menunggu diproses oleh admin.
                            @elseif($order->status === 'in_progress')
                            Order sedang dalam proses pengerjaan.
                            @elseif($order->status === 'confirmed')
                            Order telah selesai dan dikonfirmasi.
                            @elseif($order->status === 'rejected')
                            Order ditolak/dikembalikan.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Timeline -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-base font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Riwayat Timeline
                    </h2>
                </div>
                <div class="p-6 max-h-[60vh] overflow-y-auto">
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            @forelse($order->history()->latest()->get() as $history)
                            <li>
                                <div class="relative pb-8">
                                    @if(!$loop->last)
                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-4 ring-white {{ match($history->status) {
                                                'open' => 'bg-blue-500',
                                                'in_progress' => 'bg-amber-500',
                                                'tutup' => 'bg-purple-500',
                                                'confirmed' => 'bg-emerald-500',
                                                'rejected' => 'bg-red-500',
                                                default => 'bg-slate-500'
                                            } }}">
                                                @if($history->status === 'confirmed')
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                @elseif($history->status === 'rejected')
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                @elseif($history->status === 'in_progress')
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                @else
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1 pt-0.5">
                                            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold {{ $history->status_badge_class }}">
                                                    {{ $history->status_label }}
                                                </span>
                                                <p class="text-xs text-gray-500">
                                                    {{ $history->creator?->name ?? 'System' }} · {{ $history->created_at->format('d M Y H:i') }}
                                                </p>
                                            </div>
                                            @if($history->keterangan ?? $history->follow_up)
                                            <p class="mt-2 text-sm text-gray-700 bg-gray-50 rounded-lg px-3 py-2 border border-gray-100">{{ $history->keterangan ?? $history->follow_up }}</p>
                                            @endif
                                            @if($history->lampiran)
                                            <div class="mt-2">
                                                <button type="button" onclick="openImageModal('{{ Storage::url($history->lampiran) }}')" class="inline-flex items-center px-3 py-1.5 bg-purple-50 hover:bg-violet-100 text-violet-700 text-xs font-medium rounded-lg transition-colors">
                                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    Lihat Lampiran
                                                </button>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </li>
                            @empty
                            <li class="text-center text-gray-500 text-sm py-8">Belum ada riwayat</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center p-4 transition-opacity duration-300">
    <div class="relative max-w-4xl w-full">
        <div class="bg-white p-2 rounded-xl shadow-2xl">
            <img id="modalImage" src="" alt="Foto Order Perbaikan" class="max-h-[70vh] max-w-full object-contain mx-auto rounded-lg">
        </div>
        <button onclick="closeImageModal()" class="absolute -top-4 -right-4 bg-white p-2.5 rounded-full shadow-lg hover:bg-gray-100 transition-colors">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-sm w-full mx-auto">
        <div class="p-6">
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 mx-auto mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Konfirmasi Hapus</h3>
            <p class="text-sm text-gray-500 text-center mb-6">Apakah Anda yakin ingin menghapus order perbaikan ini? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="flex gap-3">
                <button onclick="closeDeleteModal()" class="flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                    Batal
                </button>
                <button onclick="deleteOrder()" class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors">
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection