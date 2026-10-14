@extends('admin.layouts.app')

@section('title', 'Detail Order Perbaikan Terkonfirmasi')

@section('content')
<div class="w-full">
    <!-- Back button and status badge -->
    <div class="flex justify-between items-center mb-6">
        <a href="{{ route('admin.order-perbaikan.confirmed') }}"
            class="inline-flex items-center text-base font-medium text-blue-600 hover:text-blue-800 transition px-4 py-2 bg-white rounded-lg shadow-sm">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
            Kembali
        </a>
        <span class="px-4 py-2 text-sm font-medium rounded-full bg-blue-100 text-blue-800 flex items-center">
            <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
            Terkonfirmasi
        </span>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
        <!-- Header -->
        <div class="px-8 py-6 border-b border-gray-100 bg-blue-700">
            <h2 class="text-2xl font-bold text-white">Order Perbaikan #{{ $order->nomor }}</h2>
        </div>

        <!-- Main content -->
        <div class="p-8">
            <!-- Primary details -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-10">
                <div>
                    <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Tanggal</p>
                    <p class="text-base text-gray-800">{{ $order->created_at->format('d-m-Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Peminta</p>
                    <p class="text-base text-gray-800">{{ $order->creator->name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Prioritas</p>
                    <p class="text-base text-gray-800">{{ $order->prioritas }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Lokasi</p>
                    <p class="text-base text-gray-800">{{ $order->location?->name ?? '-' }}</p>
                </div>
            </div>

            <!-- Order details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8 mb-10">
                <!-- Left Column -->
                <div>
                    <div class="mb-8">
                        <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Unit Pengaju</p>
                        <p class="text-base text-gray-800">{{ $order->department?->name ?? '-' }}</p>
                    </div>

                    <div class="mb-8">
                        <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Unit Proses</p>
                        @php
                        $unitProsesModel = $order->relationLoaded('unitProses') ? $order->getRelation('unitProses') : $order->unitProses()->getResults();
                        @endphp
                        <p class="text-base text-gray-800">{{ $unitProsesModel?->name ? $unitProsesModel->name.' ('.$unitProsesModel->code.')' : '-' }}</p>
                    </div>

                    <div class="mb-8">
                        <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Unit Penerima</p>
                        <p class="text-base text-gray-800">{{ $order->unit_penerima }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Kategori Order</p>
                        <p class="text-base text-gray-800">{{ $order->kategori_order ?: '-' }}</p>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    @if(!empty($order->nama_barang))
                    <div class="mb-8">
                        <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Nama Barang</p>
                        <p class="text-base text-gray-800">{{ $order->nama_barang }}</p>
                    </div>
                    @endif

                    @if(!empty($order->kode_inventaris) && $order->kode_inventaris !== '-')
                    <div class="mb-8">
                        <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Kode Inventaris</p>
                        <p class="text-base text-gray-800">{{ $order->kode_inventaris }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <!-- Keluhan -->
                    <div class="mb-10">
                        <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Keluhan</p>
                        <div class="text-base text-gray-800 bg-gray-50 p-5 rounded-lg border border-gray-200">
                            {{ $order->keluhan }}
                        </div>
                    </div>

                    <!-- Foto -->
                    @if($order->foto)
                    <div class="mb-10">
                        <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-2">Foto</p>
                        <div class="mt-2">
                            <img src="{{ Storage::url($order->foto) }}" alt="Foto Order"
                                class="max-h-80 rounded-lg shadow-sm border border-gray-200">
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Riwayat Status + Update Status -->
                <div class="space-y-6">
                <!-- Riwayat Status (bubble chat: pengaju kanan, admin kiri) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="text-base font-semibold text-gray-900">Riwayat Status</h3>
                    </div>
                    <div class="px-4 py-5 h-[420px] overflow-y-auto bg-[#ECE5DD]">
                        <div class="space-y-3">
                            @forelse($order->history->sortBy('created_at') as $history)
                            @php
                                $isPengaju = $history->created_by
                                    ? ($order->created_by && $history->created_by == $order->created_by)
                                    : in_array($history->status, ['open', 'confirmed']);
                            @endphp
                            @include('components.order-history-bubble', ['history' => $history, 'isPengaju' => $isPengaju])
                            @empty
                            <p class="text-center text-gray-500 text-sm py-8">Belum ada aktivitas</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="text-base font-semibold text-gray-900">Update Status</h3>
                    </div>
                    <div class="p-6">
                        <form action="{{ route('admin.order-perbaikan.update-status', $order) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">Status</label>
                                    <select name="status" required
                                        class="w-full rounded-lg border border-gray-200 bg-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm py-2.5 px-3 transition-all">
                                        <option value="">Pilih Status</option>
                                        <option value="in_progress">In Progress (Proses Ulang)</option>
                                        <option value="tutup">Tutup (Konfirmasi Selesai)</option>
                                        <option value="rejected">Tolak</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">Tindak Lanjut</label>
                                    <textarea name="follow_up" required rows="3"
                                        class="w-full rounded-lg border border-gray-200 bg-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm py-2.5 px-3 transition-all resize-none"
                                        placeholder="Masukkan tindak lanjut...">{{ $order->follow_up }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">Penanggung Jawab</label>
                                    <p class="text-sm text-gray-900 bg-white px-3 py-2.5 rounded-lg border border-gray-200 font-medium">{{ $order->nama_penanggung_jawab ?? '-' }}</p>
                                    <input type="hidden" name="nama_penanggung_jawab" value="{{ $order->nama_penanggung_jawab }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">Lampiran <span class="text-gray-400 font-normal">(opsional)</span></label>
                                    <input type="file" name="lampiran" accept=".jpg,.jpeg,.png"
                                        class="w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all">
                                    <p class="text-xs text-gray-500 mt-1">Format: JPG, JPEG, PNG. Maks 5MB.</p>
                                </div>
                                <button type="submit"
                                    class="w-full inline-flex justify-center items-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Update Status
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                </div>
            </div>

            <!-- Admin Response Section -->
            <div class="mt-10 border-t border-blue-100 pt-8">
                <h3 class="text-xl font-medium text-blue-800 mb-6">Respon Admin</h3>
                <div class="bg-blue-50 rounded-lg p-6 border border-blue-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-blue-700 mb-2">Follow Up</label>
                            <p class="text-base text-gray-900">{{ $order->follow_up }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-blue-700 mb-2">Tanggal Konfirmasi</label>
                            <p class="text-base text-gray-900">{{ $order->updated_at->format('d-m-Y H:i') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-blue-700 mb-2">Nama Penanggung Jawab</label>
                            <p class="text-base text-gray-900">{{ $order->nama_penanggung_jawab ?? '-' }}</p>
                        </div>
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
            <img id="modalImage" src="" alt="Lampiran" class="max-h-[70vh] max-w-full object-contain mx-auto rounded-lg">
        </div>
        <button onclick="closeImageModal()" class="absolute -top-4 -right-4 bg-white p-2.5 rounded-full shadow-lg hover:bg-gray-100 transition-colors">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
</div>

<script>
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
    }
});
</script>
@endsection