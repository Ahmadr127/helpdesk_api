@extends('admin.layouts.app')

@section('title', 'Detail Order')

@section('content')
<div class="w-full  py-6">
    {{-- <!-- Back Button -->
    <div class="mb-5">
        <a href="{{ route('admin.order-perbaikan.index') }}"
            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke Daftar
        </a>
    </div> --}}



    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Info Cards -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Order Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Informasi Order
                        </h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ match($orderPerbaikan->status) {
                                'open' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20',
                                'in_progress' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20',
                                'tutup' => 'bg-violet-50 text-violet-700 ring-1 ring-violet-600/20',
                                'confirmed' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20',
                                'rejected' => 'bg-red-50 text-red-700 ring-1 ring-red-600/20',
                                default => 'bg-gray-50 text-gray-700 ring-1 ring-gray-600/20'
                            } }}">
                            <span class="w-1.5 h-1.5 rounded-full mr-2
                                {{ match($orderPerbaikan->status) {
                                    'open' => 'bg-blue-500',
                                    'in_progress' => 'bg-amber-500',
                                    'tutup' => 'bg-violet-500',
                                    'confirmed' => 'bg-emerald-500',
                                    'rejected' => 'bg-red-500',
                                    default => 'bg-gray-500'
                                } }}"></span>
                            {{ $orderPerbaikan->status_label }}
                        </span>
                    </div>
                    <p class="text-sm font-bold text-gray-900 mt-2">Order #{{ $orderPerbaikan->nomor }}</p>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Unit Pengaju</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $orderPerbaikan->department?->name ?? $orderPerbaikan->unit_proses_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Unit Proses</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                @php
                                $unitProsesModel = $orderPerbaikan->relationLoaded('unitProses') ? $orderPerbaikan->getRelation('unitProses') : $orderPerbaikan->unitProses()->getResults();
                                @endphp
                                {{ $unitProsesModel?->name ? $unitProsesModel->name.' ('.$unitProsesModel->code.')' : '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Prioritas</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold {{ match($orderPerbaikan->prioritas) {
                                    'RENDAH' => 'bg-green-50 text-green-700 ring-1 ring-green-600/20',
                                    'SEDANG' => 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-600/20',
                                    'TINGGI/URGENT' => 'bg-red-50 text-red-700 ring-1 ring-red-600/20',
                                    default => 'bg-gray-50 text-gray-700 ring-1 ring-gray-600/20'
                                } }}">
                                    {{ $orderPerbaikan->prioritas }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tanggal Order</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $orderPerbaikan->tanggal->format('d M Y, H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Unit Penerima</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $orderPerbaikan->unit_penerima ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Peminta</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $orderPerbaikan->nama_peminta ?? '-' }}</dd>
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 content-start {{ $orderPerbaikan->foto ? '' : 'md:col-span-2' }}">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Kategori Order</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $orderPerbaikan->kategori_order ?? '-' }}</dd>
                        </div>
                        @if(!empty($orderPerbaikan->kode_inventaris) && $orderPerbaikan->kode_inventaris !== '-')
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Kode Inventaris</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $orderPerbaikan->kode_inventaris }}</dd>
                        </div>
                        @endif
                        @if(!empty($orderPerbaikan->nama_barang))
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nama Barang</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $orderPerbaikan->nama_barang }}</dd>
                        </div>
                        @endif
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Lokasi</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $orderPerbaikan->location?->name ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Keluhan</dt>
                            <dd class="mt-1 text-sm text-gray-700 bg-gray-50 rounded-lg p-4 border border-gray-100">{{ $orderPerbaikan->keluhan }}</dd>
                        </div>
                    </dl>
                    @if($orderPerbaikan->foto)
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Foto Barang</p>
                        <div class="relative rounded-lg overflow-hidden bg-gray-100">
                            <img src="{{ Storage::url($orderPerbaikan->foto) }}" alt="Foto Order"
                                class="w-full h-64 object-cover">
                            <div class="absolute inset-0 bg-black/0 hover:bg-black/40 transition-all duration-300 flex items-center justify-center">
                                <a href="{{ Storage::url($orderPerbaikan->foto) }}" target="_blank"
                                    class="opacity-0 hover:opacity-100 transition-opacity bg-white/90 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                    </svg>
                                    Lihat Full Size
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column: Status Update Form + Timeline -->
        <div class="lg:col-span-1 space-y-6 lg:sticky lg:top-6 lg:self-start lg:max-h-[calc(100dvh-4rem)] lg:overflow-y-auto lg:pr-1">
            <!-- Riwayat Status (bubble chat: pengaju kanan, admin kiri) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-base font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        Riwayat Status
                    </h2>
                </div>
                <div class="px-4 py-5 h-72 overflow-y-auto bg-[#ECE5DD]">
                    <div class="space-y-3">
                        @forelse($orderPerbaikan->history->sortBy('created_at') as $history)
                        @php
                            $isPengaju = $history->created_by
                                ? ($orderPerbaikan->created_by && $history->created_by == $orderPerbaikan->created_by)
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
                    <h2 class="text-base font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Update Status
                    </h2>
                </div>
                <div class="p-6">
                    <form action="{{ route('admin.order-perbaikan.update-status', $orderPerbaikan) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-1">Status</label>
                                    <select name="status" required
                                        class="w-full rounded-lg border border-black bg-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm py-2 px-3 transition-all">
                                        <option value="in_progress" {{ in_array($orderPerbaikan->status, ['open', 'in_progress']) ? 'selected' : '' }}>In Progress{{ $orderPerbaikan->status === 'open' ? '' : ' (Buka Kembali)' }}</option>
                                        <option value="tutup" {{ $orderPerbaikan->status === 'tutup' ? 'selected' : '' }}>Tutup (Konfirmasi Selesai)</option>
                                        <option value="rejected" {{ $orderPerbaikan->status === 'rejected' ? 'selected' : '' }}>Tolak</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-1">Penanggung Jawab</label>
                                    <input type="text" name="nama_penanggung_jawab" required
                                        value="{{ old('nama_penanggung_jawab', $orderPerbaikan->nama_penanggung_jawab ?? auth()->user()->name) }}"
                                        class="w-full rounded-lg border border-gray-200 bg-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm py-2 px-3 transition-all"
                                        placeholder="Masukkan nama penanggung jawab">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-1">Tindak Lanjut</label>
                                <textarea name="follow_up" required rows="2"
                                    class="w-full rounded-lg border border-black bg-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm py-2 px-3 transition-all resize-none"
                                    placeholder="Masukkan tindak lanjut yang dilakukan...">{{ old('follow_up', $orderPerbaikan->follow_up) }}</textarea>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                                <div class="flex-1 min-w-0">
                                    <label class="block text-sm font-semibold text-gray-800 mb-1">Lampiran <span class="text-gray-400 font-normal">(opsional, JPG/PNG 5MB)</span></label>
                                    <input type="file" name="lampiran" accept=".jpg,.jpeg,.png"
                                        class="w-full text-sm text-gray-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all">
                                </div>
                                <button type="submit" name="action" value="update"
                                    class="sm:w-auto w-full inline-flex justify-center items-center px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Update Status
                                </button>
                            </div>
                        </div>

                    </form>
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