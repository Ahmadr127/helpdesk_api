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

    <!-- Header Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900">Order #{{ $orderPerbaikan->nomor }}</h1>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
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
                <p class="text-sm text-gray-500 mt-1">Dibuat pada {{ $orderPerbaikan->created_at->format('d M Y, H:i') }} oleh {{ $orderPerbaikan->creator->name }}</p>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Info Cards -->
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
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Kategori Order</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $orderPerbaikan->kategori_order ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Kode Inventaris</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $orderPerbaikan->kode_inventaris ?: '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nama Barang</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $orderPerbaikan->nama_barang }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Lokasi</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $orderPerbaikan->location?->name ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Keluhan</dt>
                            <dd class="mt-1 text-sm text-gray-700 bg-gray-50 rounded-lg p-4 border border-gray-100">{{ $orderPerbaikan->keluhan }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Photo -->
            @if($orderPerbaikan->foto)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-base font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Foto Barang
                    </h2>
                </div>
                <div class="p-6">
                    <div class="relative rounded-lg overflow-hidden bg-gray-100 max-w-md">
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
            </div>
            @endif

        </div>

        <!-- Right Column: Status Update Form + Timeline -->
        <div class="lg:col-span-1 space-y-6 sticky top-6 self-start">
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
                    <form action="{{ route('admin.order-perbaikan.update-status', $orderPerbaikan) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @if($orderPerbaikan->status === 'open')
                        <div class="space-y-5">
                            <div class="bg-gray-50/50 rounded-lg p-4 border border-gray-100">
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Status</label>
                                <select name="status" required
                                    class="w-full rounded-lg border border-gray-200 bg-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm py-2.5 px-3 transition-all">
                                    <option value="in_progress">In Progress</option>
                                </select>
                            </div>
                            <div class="bg-gray-50/50 rounded-lg p-4 border border-gray-100">
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Nama Penanggung Jawab</label>
                                <input type="text" name="nama_penanggung_jawab" required
                                    class="w-full rounded-lg border border-gray-200 bg-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm py-2.5 px-3 transition-all"
                                    placeholder="Masukkan nama penanggung jawab"
                                    value="{{ old('nama_penanggung_jawab', auth()->user()->name) }}">
                            </div>
                            <div class="bg-gray-50/50 rounded-lg p-4 border border-gray-100">
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Tindak Lanjut</label>
                                <textarea name="follow_up" required rows="3"
                                    class="w-full rounded-lg border border-black bg-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm py-2.5 px-3 transition-all resize-none"
                                    placeholder="Masukkan tindak lanjut yang akan dilakukan..."></textarea>
                            </div>
                            <button type="submit" name="action" value="in_progress"
                                class="w-full inline-flex justify-center items-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Proses Order
                            </button>
                        </div>

                        @elseif($orderPerbaikan->status === 'in_progress')
                        <div class="space-y-5">
                            <div class="bg-gray-50/50 rounded-lg p-4 border border-gray-100">
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Status</label>
                                <select name="status" required
                                    class="w-full rounded-lg border border-black bg-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm py-2.5 px-3 transition-all">
                                    <option value="">Pilih Status</option>
                                    <option value="tutup">Tutup (Konfirmasi Selesai)</option>
                                    <option value="rejected">Tolak</option>
                                </select>
                            </div>
                            <div class="bg-gray-50/50 rounded-lg p-4 border border-gray-100">
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Tindak Lanjut</label>
                                <textarea name="follow_up" required rows="3"
                                    class="w-full rounded-lg border border-black bg-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm py-2.5 px-3 transition-all resize-none"
                                    placeholder="Masukkan tindak lanjut yang dilakukan...">{{ $orderPerbaikan->follow_up }}</textarea>
                            </div>
                            <div class="bg-gray-50/50 rounded-lg p-4 border border-gray-100">
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Penanggung Jawab</label>
                                <p class="text-sm text-gray-900 bg-white px-3 py-2.5 rounded-lg border border-gray-200 font-medium">{{ $orderPerbaikan->nama_penanggung_jawab }}</p>
                                <input type="hidden" name="nama_penanggung_jawab" value="{{ $orderPerbaikan->nama_penanggung_jawab }}">
                            </div>
                            <button type="submit" name="action" value="update"
                                class="w-full inline-flex justify-center items-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Update Status
                            </button>
                        </div>

                        @else
                        <div class="bg-violet-50 border border-violet-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-violet-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-violet-900">Order Ditutup</p>
                                    <p class="text-sm text-violet-700 mt-1 leading-relaxed">
                                        Pengerjaan sudah diselesaikan oleh admin, namun belum dikonfirmasi oleh user.
                                        Status akan menjadi <strong>Confirmed</strong> setelah user mengonfirmasi selesai.
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </form>
                </div>
            </div>

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
                            @forelse($orderPerbaikan->history->sortBy('created_at') as $history)
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
                                                'tutup' => 'bg-violet-500',
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
                                            @if($history->keterangan)
                                            <p class="mt-2 text-sm text-gray-700 bg-gray-50 rounded-lg px-3 py-2 border border-gray-100">{{ $history->keterangan }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </li>
                            @empty
                            <li class="text-center text-gray-500 text-sm py-8">Belum ada aktivitas</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection