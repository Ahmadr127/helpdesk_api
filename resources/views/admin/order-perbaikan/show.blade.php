
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
                                'ditutup' => 'bg-violet-50 text-violet-700 ring-1 ring-violet-600/20',
                                'confirmed' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20',
                                'rejected' => 'bg-red-50 text-red-700 ring-1 ring-red-600/20',
                                default => 'bg-gray-50 text-gray-700 ring-1 ring-gray-600/20'
                            } }}">
                            <span class="w-1.5 h-1.5 rounded-full mr-2
                                {{ match($orderPerbaikan->status) {
                                    'open' => 'bg-blue-500',
                                    'in_progress' => 'bg-amber-500',
                                    'ditutup' => 'bg-violet-500',
                                    'confirmed' => 'bg-emerald-500',
                                    'rejected' => 'bg-red-500',
                                    default => 'bg-gray-500'
                                } }}"></span>
                            {{ $orderPerbaikan->status_label }}
                        </span>
                    </div>
                    <p class="text-sm font-bold text-gray-900 mt-2">Order #{{ $orderPerbaikan->nomor }}</p>
                <p class="text-sm text-gray-600 mt-1">Nama Pengaju: <span class="font-semibold text-gray-900">{{ $orderPerbaikan->pemohon_nama ?? $orderPerbaikan->pemohon?->name ?? '-' }}</span></p>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Unit Pengaju</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $orderPerbaikan->department?->name ?? $orderPerbaikan->unit_proses_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Prioritas</dt>
                            <dd class="mt-1">
                                <form action="{{ route('admin.order-perbaikan.priority', $orderPerbaikan) }}" method="POST" class="inline-flex items-center gap-1.5">
                                    @csrf
                                    @method('PUT')
                                    <select name="prioritas" required onchange="this.form.submit()"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold text-white border-0 cursor-pointer {{ match($orderPerbaikan->prioritas) {
                                            'low', 'RENDAH', 'rendah' => 'bg-green-600',
                                            'medium', 'SEDANG', 'sedang' => 'bg-yellow-600',
                                            'high', 'TINGGI/URGENT', 'TINGGI', 'tinggi' => 'bg-red-600',
                                            default => 'bg-gray-600'
                                        } }}">
                                        <option value="low" @selected(in_array($orderPerbaikan->prioritas, ['low','RENDAH','rendah'])) class="bg-green-600 text-white">Rendah</option>
                                        <option value="medium" @selected(in_array($orderPerbaikan->prioritas, ['medium','SEDANG','sedang'])) class="bg-yellow-600 text-white">Sedang</option>
                                        <option value="high" @selected(in_array($orderPerbaikan->prioritas, ['high','TINGGI/URGENT','TINGGI','tinggi'])) class="bg-red-600 text-white">Tinggi</option>
                                    </select>
                                </form>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tanggal Order</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $orderPerbaikan->tanggal->format('d M Y, H:i') }}</dd>
                        </div>
                        {{-- Nama Pengaju sudah ditampilkan di atas --}}
                    </dl>
                </div>
            </div>

            <!-- Item Details -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                {{-- <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-base font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        Detail Barang
                    </h2>
                </div> --}}
                <div class="p-6">
                    @php
                        $initialLampirans = \App\Support\Conversation::initialLampirans($orderPerbaikan);
                        $initialLampiran = $initialLampirans->first();
                    @endphp
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 content-start {{ $initialLampiran ? '' : 'md:col-span-2' }}">
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
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $orderPerbaikan->lokasi?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Gedung</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $orderPerbaikan->building?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Penanggung Jawab</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $orderPerbaikan->penugasan->where('released_at', null)->pluck('user.name')->filter()->join(', ') ?: '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Keluhan</dt>
                            <dd class="mt-1 text-sm text-gray-700 bg-gray-50 rounded-lg p-4 border border-gray-100">{{ $orderPerbaikan->keluhan }}</dd>
                        </div>
                    </dl>
                    @if($initialLampirans->isNotEmpty())
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Foto Barang</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($initialLampirans as $lampiran)
                                @if($lampiran->path)
                                    <a href="{{ Storage::url($lampiran->path) }}" target="_blank" class="inline-block">
                                        <img src="{{ Storage::url($lampiran->path) }}" alt="Foto Order {{ $loop->iteration }}"
                                            class="w-32 h-32 object-cover rounded-lg border border-gray-200 bg-gray-100">
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @php $historiLampirans = $orderPerbaikan->lampirans->whereNotNull('histori_id')->values(); @endphp
                    @if($historiLampirans->isNotEmpty())
                    <div class="mt-4">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Lampiran</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($historiLampirans as $lamp)
                                @if($lamp->path)
                                    <a href="{{ Storage::url($lamp->path) }}" target="_blank" class="inline-block">
                                        <img src="{{ Storage::url($lamp->path) }}" alt="Lampiran" class="w-24 h-24 object-cover rounded-lg border border-gray-200">
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                    </div>
                </div>
            </div>
            
        </div>

        <!-- Right Column: Status Update Form + Timeline -->
        {{-- <div class="lg:col-span-1 space-y-6 lg:sticky lg:top-6 lg:self-start lg:max-h-[calc(100dvh-4rem)] lg:overflow-y-auto lg:pr-1">
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
                        @forelse($orderPerbaikan->historis->sortBy('created_at') as $history)
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
            </div> --}}
            <!-- Riwayat Percakapan (bubble chat: pengaju kiri, admin kanan) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Riwayat Percakapan</h2>
                </div>
                @php
                    $allResponses = \App\Support\Conversation::responses($orderPerbaikan);
                    $pengajuName = $orderPerbaikan->pemohon?->name ?? 'Pengaju';
                @endphp
                @include('components.conversation-history', ['responses' => $allResponses, 'pengajuName' => $pengajuName])
            </div>

        </div>
    </div>

    <!-- Update Status Form (full-width, sama seperti form admin tiket) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 mt-0">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Update Status</h2>
        </div>
        <div class="p-4">
            <form action="{{ route('admin.order-perbaikan.update-status', $orderPerbaikan) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- Left: status + PJ -->
                    <div class="lg:col-span-1 space-y-3">
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1">Status</label>
                            <select name="status" required
                                class="w-full rounded-lg border border-gray-200 bg-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm py-2 px-3 transition-all">
                                <option value="in_progress" {{ in_array($orderPerbaikan->status, ['open', 'in_progress']) ? 'selected' : '' }}>In Progress{{ $orderPerbaikan->status === 'open' ? '' : ' (Buka Kembali)' }}</option>
                                <option value="tutup" {{ $orderPerbaikan->status === 'ditutup' ? 'selected' : '' }}>Tutup (Konfirmasi Selesai)</option>
                                <option value="rejected" {{ $orderPerbaikan->status === 'rejected' ? 'selected' : '' }}>Tolak</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1">Penanggung Jawab</label>
                            @if(!empty($canAssign) && $candidates->isNotEmpty())
                                    @include('components.searchable-multiselect', [
                                        'name' => 'penanggung_jawab_ids[]',
                                        'options' => $candidates,
                                        'selected' => ($activePj ?? collect())->pluck('user_id')->all(),
                                        'placeholder' => 'Pilih penanggung jawab…',
                                        'searchPlaceholder' => 'Cari nama…',
                                        'compact' => true,
                                    ])
                                    <p class="mt-1 text-xs text-gray-500">Kandidat PJ mengikuti kategori role.</p>
                            @else
                                <p class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                                    {{ ($activePj ?? collect())->pluck('user.name')->filter()->join(', ') ?: 'Belum ada PJ. Kategori ini belum memiliki role PJ.' }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <!-- Middle: catatan -->
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-semibold text-gray-800 mb-1">Catatan</label>
                        <textarea name="follow_up" required rows="5"
                            class="w-full rounded-lg border border-gray-200 bg-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm py-2 px-3 transition-all resize-none h-full"
                            placeholder="Masukkan catatan...">{{ old('follow_up', $orderPerbaikan->follow_up) }}</textarea>
                    </div>

                    <!-- Right: lampiran + tombol -->
                    <div class="lg:col-span-1 flex flex-col justify-between gap-3">
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1">Lampiran <span class="text-gray-400 font-normal">(opsional, JPG/PNG 5MB)</span></label>
                            <label class="block border border-dashed border-gray-300 rounded-lg p-4 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50/50 transition-colors">
                                <input type="file" name="lampiran" accept=".jpg,.jpeg,.png" class="sr-only" onchange="const n=document.getElementById('lampiran-name'); n.textContent = this.files && this.files.length ? this.files[0].name : 'Belum ada file dipilih'">
                                <svg class="w-9 h-9 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span class="mt-1 block text-sm font-medium text-blue-600 hover:text-blue-700">Unggah Foto</span>
                                <span id="lampiran-name" class="mt-1 block text-xs text-gray-500">Belum ada file dipilih · JPG/PNG maks 5MB</span>
                            </label>
                        </div>
                        <button type="submit" name="action" value="update"
                            class="inline-flex justify-center items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
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
</script>
@endsection