{{-- Isi modal detail order milik sendiri (dimuat via AJAX ?modal=1). Mirip halaman detail user, ringkas untuk modal. --}}
@php
    $unitProsesModel = $order->relationLoaded('unitProses') ? $order->getRelation('unitProses') : $order->unitProses()->getResults();
@endphp
<div class="flex items-center gap-3 flex-wrap">
    <h3 class="text-base font-bold text-gray-900">Order #{{ $order->nomor }}</h3>
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold {{ $order->getStatusBadgeClass() }}">
        {{ $order->getStatusText() }}
    </span>
    <span class="text-xs text-gray-500 ml-auto">Dibuat pada {{ $order->tanggal->format('d M Y, H:i') }}</span>
</div>

<div class="mt-4 bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-4 py-2 border-b border-gray-100 bg-gray-50/50">
        <h4 class="text-sm font-semibold text-gray-900">Informasi Order</h4>
    </div>
    <div class="p-4">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">{{ $order->getStatusText() }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Prioritas</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">{{ $order->prioritas }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Unit Pengaju</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">{{ $order->department?->name ?? $order->unit_proses_name }}</dd>
            </div>
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

<div class="mt-3 bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-4 py-2 border-b border-gray-100 bg-gray-50/50">
        <h4 class="text-sm font-semibold text-gray-900">Detail Barang</h4>
    </div>
    <div class="p-4">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
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
                <dd class="mt-1 text-sm text-gray-700 bg-gray-50 rounded-lg p-3 border border-gray-100 leading-relaxed">{{ $order->keluhan }}</dd>
            </div>
        </dl>
    </div>
</div>

@if($order->foto)
<div class="mt-3 bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-4 py-2 border-b border-gray-100 bg-gray-50/50">
        <h4 class="text-sm font-semibold text-gray-900">Foto</h4>
    </div>
    <div class="p-4">
        <img src="{{ Storage::url($order->foto) }}" alt="Foto Order Perbaikan" class="w-full max-h-64 object-contain rounded-lg border border-gray-200 bg-gray-100">
    </div>
</div>
@endif

<div class="mt-3 bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-4 py-2 border-b border-gray-100 bg-gray-50/50">
        <h4 class="text-sm font-semibold text-gray-900">Riwayat Timeline</h4>
    </div>
    <div class="p-4 max-h-64 overflow-y-auto">
        <ul role="list" class="-mb-6">
            @forelse($order->history()->latest()->get() as $history)
            <li>
                <div class="relative pb-6">
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
                                <span class="text-white text-xs font-bold">{{ strtoupper(substr($history->status, 0, 1)) }}</span>
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
                        </div>
                    </div>
                </div>
            </li>
            @empty
            <li class="text-center text-gray-500 text-sm py-6">Belum ada riwayat</li>
            @endforelse
        </ul>
    </div>
</div>
