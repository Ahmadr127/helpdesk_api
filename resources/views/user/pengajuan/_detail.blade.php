{{--
    Partial detail pengajuan user yang dipakai bersama (Tiket IT + Order IPSRS + Pengajuan umum).
    Kandidat: user.pengajuan._detail

    Parameter (@include):
    - $pengajuan   (wajib) model App\Models\Pengajuan (sudah di-load relasi: kategori.jenisList, pemohon, department, lokasi, building, historis.actor, pesans.sender, lampirans, penugasan.user)
    - $accent      (opsional) 'blue' | 'green', default 'blue'
    - $backUrl     (opsional) URL tombol kembali, default route('user.pengajuan.index')
    - $backLabel   (opsional) label tombol kembali, default 'Kembali ke Daftar Pengajuan'
    - $titleLabel  (opsional) label judul, default 'Detail Pengajuan'
    - $editUrl     (opsional) URL tombol edit (jika ada & status open)
    - $deleteUrl   (opsional) URL tombol hapus (jika ada & status open/pending)
    - $replyUrl    (opsional) URL form balas (jika ada & status in_progress/pending)
    - $konfirmasiUrl (opsional) URL form konfirmasi selesai (jika ada & status ditutup)
    - $konfirmasiFields (opsional) 'ticket' | 'order', default 'ticket'
    - $showOrderDetail (opsional) bool, tampilkan section detail order (IPSRS), default auto dari jenis
    - $candidates  (opsional) koleksi user kandidat PJ
    - $activePj    (opsional) koleksi penugasan aktif
    - $assignUrl   (opsional) URL form assign PJ
    - $canAssign   (opsional) bool, tampilkan form assign
--}}
@php
    $pengajuan = $pengajuan ?? ($ticket ?? $order ?? null);
    $accent = $accent ?? 'blue';
    $backUrl = $backUrl ?? route('user.pengajuan.index');
    $backLabel = $backLabel ?? 'Kembali ke Daftar Pengajuan';
    $titleLabel = $titleLabel ?? 'Detail Pengajuan';
    $isOrder = ($pengajuan?->kategori?->jenis?->code ?? null) === 'IPSRS';
    $showOrderDetail = $showOrderDetail ?? $isOrder;
    $konfirmasiFields = $konfirmasiFields ?? ($isOrder ? 'order' : 'ticket');

    $statusBadge = match($pengajuan->status ?? null) {
        'open' => 'bg-blue-100 text-blue-800',
        'in_progress' => 'bg-purple-100 text-purple-800',
        'pending' => 'bg-yellow-100 text-yellow-800',
        'ditutup' => 'bg-gray-100 text-gray-800',
        default => 'bg-green-100 text-green-800',
    };
    $statusLabel = match($pengajuan->status ?? null) {
        'open' => 'Dibuka',
        'in_progress' => 'Dalam Proses',
        'pending' => 'Menunggu',
        'ditutup' => 'Ditutup',
        default => 'Selesai',
    };
    $prioritasBadge = match($pengajuan->prioritas ?? null) {
        'low' => 'bg-gray-100 text-gray-800',
        'medium' => 'bg-yellow-100 text-yellow-800',
        default => 'bg-red-100 text-red-800',
    };
    $prioritasLabel = match($pengajuan->prioritas ?? null) {
        'low' => 'Rendah',
        'medium' => 'Sedang',
        default => 'Tinggi',
    };
    $headerGradient = $accent === 'green'
        ? 'from-green-600 to-green-500'
        : 'from-blue-600 to-blue-400';

    $initialLampiran = $pengajuan->lampirans->where('kind', 'initial')->first();
    $initialLampirans = $pengajuan->lampirans->where('kind', 'initial');
    $lampiranByPesan = $pengajuan->lampirans->whereNotNull('pesan_id')->groupBy('pesan_id');
    $allResponses = $pengajuan->pesans->map(function ($pesan) use ($lampiranByPesan) {
        $photo = $lampiranByPesan->get($pesan->id)?->first()?->path;
        $isAdmin = ($pesan->sender_type ?? '') === 'admin';
        return [
            'type' => $isAdmin ? 'admin' : 'user',
            'notes' => $pesan->body,
            'message' => $pesan->body,
            'admin_name' => $pesan->sender?->name ?? ($isAdmin ? 'Admin' : null),
            'photo' => $photo,
            'timestamp' => $pesan->created_at?->toDateTimeString() ?? $pesan->created_at,
            'action' => null,
        ];
    })->sortBy('timestamp')->values()->all();
    if (count($allResponses) > 0) {
        // Balasan konfirmasi ("user_reply" type=confirm) hanya jadi histori action=confirmed
        // dan tidak pernah ada di pengajuan_pesan — gabungkan agar bubble pengaju tampil.
        $confirmBubbles = $pengajuan->historis
            ->where('action', 'confirmed')
            ->filter(fn ($h) => ($h->actor_type ?? '') === 'user' && trim((string) ($h->notes ?? '')) !== '' && $h->notes !== 'Pengajuan dikonfirmasi.')
            ->map(fn ($h) => [
                'type' => 'user',
                'notes' => $h->notes,
                'message' => $h->notes,
                'admin_name' => null,
                'photo' => null,
                'timestamp' => $h->created_at?->toDateTimeString() ?? $h->created_at,
                'action' => 'confirm',
            ])->values()->all();
        $allResponses = collect($allResponses)->concat($confirmBubbles)->sortBy('timestamp')->values()->all();
    }
    if (count($allResponses) === 0) {
        $allResponses = $pengajuan->historis->whereIn('action', ['admin_response', 'user_reply', 'confirmed'])->map(function ($h) {
            $isAdmin = ($h->action ?? '') === 'admin_response';
            return [
                'type' => $isAdmin ? 'admin' : 'user',
                'notes' => $h->notes,
                'message' => $h->notes,
                'admin_name' => $h->actor?->name ?? ($isAdmin ? 'Admin' : null),
                'photo' => null,
                'timestamp' => $h->created_at?->toDateTimeString() ?? $h->created_at,
                'action' => ($h->action ?? '') === 'confirmed' ? 'confirm' : null,
            ];
        })->sortBy('timestamp')->values()->all();
    }
    $pengajuName = 'Anda';
@endphp

<div class="container mx-auto px-4 py-4 max-w-8xl text-sm">
    <!-- Back Button -->
    <div class="mb-3 flex items-center justify-between">
        <a href="{{ $backUrl }}"
            class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            {{ $backLabel }}
        </a>
        <div class="flex flex-wrap gap-1.5">
            @if(!empty($konfirmasiUrl) && $pengajuan->status === 'ditutup' && !$pengajuan->user_confirmation)
                <form action="{{ $konfirmasiUrl }}" method="POST" class="inline-block">
                    @csrf
                    <input type="hidden" name="action" value="confirm">
                    <button type="submit" onclick="return confirm('Konfirmasi pengajuan ini sudah selesai?');"
                        class="bg-green-600 text-white px-3 py-1.5 rounded-md hover:bg-green-700 transition-colors shadow-sm flex items-center text-xs">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Konfirmasi Selesai
                    </button>
                </form>
                <form action="{{ $konfirmasiUrl }}" method="POST" class="inline-block">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" onclick="return confirm('Tolak konfirmasi dan kembalikan ke proses?');"
                        class="bg-red-600 text-white px-3 py-1.5 rounded-md hover:bg-red-700 transition-colors shadow-sm flex items-center text-xs">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Masih Ada Masalah
                    </button>
                </form>
            @endif
            @if(!empty($editUrl) && $pengajuan->status === 'open')
            <a href="{{ $editUrl }}"
                class="bg-blue-600 text-white px-3 py-1.5 rounded-md hover:bg-blue-700 transition-colors shadow-sm flex items-center text-xs">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit
            </a>
            @endif
            @if(!empty($deleteUrl) && in_array($pengajuan->status, ['open', 'pending']))
            <form action="{{ $deleteUrl }}" method="POST" class="inline-block"
                onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengajuan ini?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="bg-red-600 text-white px-3 py-1.5 rounded-md hover:bg-red-700 transition-colors shadow-sm flex items-center text-xs">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Hapus
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- Status strip compact -->
    <div class="bg-gradient-to-r {{ $headerGradient }} px-4 py-3 rounded-xl mb-4 flex flex-wrap items-center gap-2">
        <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $statusBadge }}">{{ $statusLabel }}</span>
        <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $prioritasBadge }}">Prioritas {{ $prioritasLabel }}</span>
        <span class="text-xs font-medium text-white">#{{ $pengajuan->nomor }}</span>
        <span class="text-xs text-white ml-auto">{{ $pengajuan->created_at?->format('d M Y H:i') }}</span>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Details Column -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Content -->
                <div class="p-4">
                    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-2 text-sm">
                        <div><dt class="text-gray-500 text-sm">Kategori</dt><dd class="font-medium text-gray-900 text-sm">{{ $pengajuan->kategori?->name ?? '-' }}</dd></div>
                        <div><dt class="text-gray-500 text-sm">Departemen</dt><dd class="font-medium text-gray-900 text-sm">{{ $pengajuan->department?->name ?? '-' }}</dd></div>
                        <div><dt class="text-gray-500 text-sm">Gedung</dt><dd class="font-medium text-gray-900 text-sm">{{ $pengajuan->building?->name ?? '-' }}</dd></div>
                        <div><dt class="text-gray-500 text-sm">Lokasi</dt><dd class="font-medium text-gray-900 text-sm">{{ $pengajuan->lokasi?->name ?? '-' }}</dd></div>
                        <div><dt class="text-gray-500 text-sm">Pengguna</dt><dd class="font-medium text-gray-900 text-sm">{{ $pengajuan->pemohon_nama ?? $pengajuan->pemohon?->name ?? '-' }}</dd></div>
                        <div><dt class="text-gray-500 text-sm">Tanggal</dt><dd class="font-medium text-gray-900 text-sm">{{ $pengajuan->tanggal?->format('d M Y, H:i') ?? $pengajuan->created_at?->format('d M Y, H:i') }}</dd></div>
                    </dl>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 pt-4 border-t border-gray-100 mt-4">
                        <div class="sm:col-span-1">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Foto</h3>
                            @if($initialLampirans->isNotEmpty())
                                <div class="bg-gray-50 p-2 rounded-lg border border-gray-100 min-h-[7rem]">
                                    <div class="grid grid-cols-2 gap-1.5">
                                        @foreach($initialLampirans as $lampiran)
                                            <a href="{{ asset('storage/' . $lampiran->path) }}" target="_blank" class="block">
                                                <img src="{{ asset('storage/' . $lampiran->path) }}" alt="Foto"
                                                    class="w-full h-14 object-cover rounded"
                                                    loading="lazy"
                                                    onerror="this.onerror=null; this.classList.add('error-image'); this.parentNode.innerHTML = '<div class=\'text-center p-1\'><svg class=\'mx-auto h-6 w-6 text-gray-400\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'></path></svg><p class=\'mt-1 text-xs text-gray-500\'>Gagal memuat gambar</p></div>';">
                                            </a>
                                        @endforeach
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">{{ $initialLampirans->count() }} foto</p>
                                </div>
                            @else
                                <div class="bg-gray-50 p-2 rounded-lg border border-gray-100 h-28 flex items-center justify-center">
                                    <div class="text-center">
                                        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <p class="mt-1 text-xs text-gray-500">Tidak ada foto</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="sm:col-span-3">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Deskripsi</h3>
                            <div class="bg-gray-50 p-2 rounded-lg border border-gray-100 h-28 overflow-auto">
                                <div class="text-sm text-gray-700 whitespace-pre-line">{{ $pengajuan->deskripsi }}</div>
                            </div>
                            @if(!empty($pengajuan->follow_up))
                            <p class="text-sm text-gray-600 mt-1"><span class="font-medium">Tindak Lanjut:</span> {{ $pengajuan->follow_up }}</p>
                            @endif
                        </div>
                    </div>

                    @if(($pengajuan->penugasan && $pengajuan->penugasan->isNotEmpty()) || (!empty($canAssign) && !empty($candidates) && $candidates->isNotEmpty()))
                    @php $pjNames = ($activePj ?? $pengajuan->penugasan->where('released_at', null))->pluck('user.name')->filter()->join(', '); @endphp
                    <div class="pt-3 mt-4 border-t border-gray-100">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span class="text-xs font-medium text-gray-500 flex-shrink-0">Penanggung Jawab:</span>
                                <span class="text-xs text-gray-800 truncate" title="{{ $pjNames }}">{{ $pjNames ?: 'Belum ada PJ' }}</span>
                            </div>
                            @if(!empty($canAssign) && !empty($assignUrl) && !empty($candidates) && $candidates->isNotEmpty())
                            <details class="group flex-shrink-0">
                                <summary class="list-none cursor-pointer inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 whitespace-nowrap">
                                    Ubah PJ
                                    <svg class="w-3 h-3 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </summary>
                                <form method="POST" action="{{ $assignUrl }}" class="mt-1.5 flex gap-1.5 items-start">
                                    @csrf
                                    @include('components.searchable-multiselect', [
                                        'name' => 'user_ids[]',
                                        'options' => $candidates,
                                        'selected' => ($activePj ?? collect())->pluck('user_id')->all(),
                                        'placeholder' => 'Pilih penanggung jawab…',
                                        'searchPlaceholder' => 'Cari nama…',
                                        'compact' => true,
                                    ])
                                    <button type="submit" class="px-2.5 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-[11px] font-semibold whitespace-nowrap">Simpan</button>
                                </form>
                            </details>
                            @endif
                        </div>
                    </div>
                    @endif

                    <div class="pt-4 border-t border-gray-100">
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Riwayat Status</h3>
                        @php
                            $statusActions = ['created', 'started', 'closed', 'confirmed', 'rejected', 'migrated_history'];
                            $timeline = $pengajuan->historis
                                ->filter(fn ($h) => in_array($h->action, $statusActions, true))
                                ->sortBy(fn ($h) => $h->created_at?->timestamp ?? 0)
                                ->values();
                            $historyLabel = fn ($action, $status) => match ($action) {
                                'created', 'migrated' => 'Dibuat',
                                'started' => 'Dalam Proses',
                                'closed' => 'Ditutup',
                                'confirmed' => 'Dikonfirmasi',
                                'rejected' => 'Ditolak',
                                'migrated_history' => match ($status) {
                                    'open' => 'Dibuat',
                                    'in_progress' => 'Dalam Proses',
                                    'ditutup' => 'Ditutup',
                                    'confirmed' => 'Dikonfirmasi',
                                    'rejected' => 'Ditolak',
                                    default => ucfirst(str_replace('_', ' ', (string) $status)),
                                },
                                default => ucfirst(str_replace('_', ' ', (string) $action)),
                            };
                            $historyDot = fn ($action, $status) => match (true) {
                                $action === 'created' || $action === 'migrated' || ($action === 'migrated_history' && $status === 'open') => 'bg-green-500',
                                $action === 'started' || ($action === 'migrated_history' && $status === 'in_progress') => 'bg-purple-500',
                                $action === 'closed' || ($action === 'migrated_history' && $status === 'ditutup') => 'bg-yellow-500',
                                $action === 'confirmed' || ($action === 'migrated_history' && $status === 'confirmed') => 'bg-green-600',
                                $action === 'rejected' || ($action === 'migrated_history' && $status === 'rejected') => 'bg-red-500',
                                default => 'bg-gray-400',
                            };
                            $historyIcon = fn ($action, $status) => match (true) {
                                $action === 'started' || ($action === 'migrated_history' && $status === 'in_progress') => 'clock',
                                $action === 'closed' || ($action === 'migrated_history' && $status === 'ditutup') => 'close',
                                $action === 'rejected' || ($action === 'migrated_history' && $status === 'rejected') => 'x',
                                default => 'check',
                            };
                        @endphp
                        {{-- Riwayat status HORIZONTAL — mengikuti desain detail tiket
                             admin (`admin/tickets/show.blade.php`): lingkaran status
                             berjajar dengan satu garis penghubung tipis di belakang,
                             jarak antar langkah dibagi rata (justify-between) sehingga
                             stepper selalu memenuhi lebar kartu.
                             `min-width` + overflow-x-auto menjaga langkah tetap terbaca
                             di layar sempit (bisa digeser ke samping). --}}
                        <div class="bg-gray-50 rounded-lg border border-gray-100 p-3">
                            @php $stepCount = $timeline->count(); @endphp
                            <div class="overflow-x-auto">
                                <div class="relative" style="min-width: {{ max(480, 72 * $stepCount) }}px;">
                                    {{-- Satu garis penghubung di belakang semua lingkaran,
                                         membentang dari pusat langkah pertama sampai pusat
                                         langkah terakhir. Karena tiap langkah lebarnya sama
                                         (flex-1), pusatnya tepat di 1/(2n) lebar container. --}}
                                    @if($stepCount > 1)
                                        <div class="absolute top-3 h-0.5 bg-gray-200"
                                             style="left: {{ round(100 / (2 * $stepCount), 4) }}%; right: {{ round(100 / (2 * $stepCount), 4) }}%;"></div>
                                    @endif
                                    <div class="relative flex items-start justify-between w-full">
                                        @forelse($timeline as $step)
                                            <div class="flex flex-1 min-w-0 flex-col items-center">
                                                <div class="w-6 h-6 flex items-center justify-center rounded-full {{ $historyDot($step->action, $step->status) }} text-white flex-shrink-0">
                                                    @switch($historyIcon($step->action, $step->status))
                                                        @case('clock')
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                            @break
                                                        @case('close')
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                            @break
                                                        @case('x')
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                            @break
                                                        @default
                                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                                    @endswitch
                                                </div>
                                                <div class="mt-1 text-center px-1 w-full">
                                                    <h4 class="text-[11px] font-medium text-gray-800 leading-tight">{{ $historyLabel($step->action, $step->status) }}</h4>
                                                    {{-- Tanggal & jam dibuat lebih kecil agar stepper tetap
                                                         ringkas dan tidak bersaing dengan label status. --}}
                                                    <p class="text-[9px] text-gray-500 leading-tight mt-0.5">{{ $step->created_at?->format('d M Y') }}</p>
                                                    <p class="text-[9px] text-gray-500 leading-tight">{{ $step->created_at?->format('H:i') }}</p>
                                                    <p class="text-[9px] text-gray-400 truncate" title="{{ $step->actor?->name ?? 'Sistem' }}">{{ $step->actor?->name ?? 'Sistem' }}</p>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-xs text-gray-500 text-center w-full py-2">
                                                Tidak ada riwayat status.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            {{-- Catatan tiap langkah ditampilkan di bawah stepper agar
                                 teks panjang tidak merusak garis horizontal. --}}
                            @php
                                $notedSteps = $timeline->filter(fn ($s) => trim((string) ($s->notes ?? '')) !== '');
                            @endphp
                            @if($notedSteps->isNotEmpty())
                                <ul class="mt-3 pt-2 border-t border-gray-200 space-y-1">
                                    @foreach($notedSteps as $step)
                                        <li class="text-[9px] text-gray-600 flex gap-1.5">
                                            <span class="text-[9px] font-semibold text-gray-700 flex-shrink-0">{{ $historyLabel($step->action, $step->status) }}:</span>
                                            <span class="text-[9px] whitespace-pre-line break-words">{{ $step->notes }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-4 py-2.5 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Riwayat Percakapan</h3>
                    <span class="text-sm text-gray-500">{{ count($allResponses) }} pesan</span>
                </div>
                @include('components.conversation-history', ['responses' => $allResponses, 'pengajuName' => $pengajuName])
            </div>
        </div>
    </div>

    @if(!empty($replyUrl) && in_array($pengajuan->status, ['in_progress', 'pending', 'open']))
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mt-4">
        <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-800">Balas Pengajuan</h3>
        </div>
        <div class="p-3">
            <form action="{{ $replyUrl }}" method="POST" class="flex flex-col md:flex-row gap-2" enctype="multipart/form-data" id="replyForm">
                @csrf
                <textarea name="message" rows="2" required class="flex-1 px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-blue-300" placeholder="Ketik pesan Anda di sini..."></textarea>
                <div class="flex items-center gap-2">
                    <label class="inline-flex items-center px-3 py-2 border border-dashed border-gray-300 rounded-lg cursor-pointer text-sm">
                        <input type="file" name="photo" accept="image/*" class="hidden" id="reply-photo-upload" onchange="handleImagePreview(this)">
                        <span class="text-blue-600 font-medium">Unggah Foto</span>
                    </label>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">Kirim Balasan</button>
                </div>
            </form>
            <div id="image-preview" class="hidden mt-1"><img src="" class="max-h-20 rounded"><button type="button" onclick="removeImage()" class="text-sm text-red-600 ml-1">Hapus</button></div>
        </div>
    </div>
    @endif

    @if(!empty($konfirmasiUrl) && $pengajuan->status === 'ditutup' && !$pengajuan->user_confirmation)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-4">
        <div class="px-4 py-2.5 border-b border-gray-100 bg-purple-50">
            <h2 class="text-sm font-semibold text-purple-900">Konfirmasi Selesai</h2>
        </div>
        <div class="p-3 flex gap-2">
            <form action="{{ $konfirmasiUrl }}" method="POST">
                @csrf
                <input type="hidden" name="action" value="confirm">
                <button type="submit" onclick="return confirm('Konfirmasi pengajuan ini sudah selesai?');" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg">✅ Ya, sudah selesai</button>
            </form>
            <form action="{{ $konfirmasiUrl }}" method="POST">
                @csrf
                <input type="hidden" name="action" value="reject">
                <button type="submit" onclick="return confirm('Tolak konfirmasi dan kembalikan ke proses?');" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg">❌ Belum selesai</button>
            </form>
        </div>
    </div>
    @endif
</div>

<style>
</style>

<script>
function handleImagePreview(input) {
    var preview = document.getElementById('image-preview');
    var placeholder = document.getElementById('upload-placeholder');
    var previewImg = preview ? preview.querySelector('img') : null;
    if (input.files && input.files[0] && previewImg) {
        var reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function removeImage() {
    var input = document.getElementById('reply-photo-upload');
    var preview = document.getElementById('image-preview');
    var placeholder = document.getElementById('upload-placeholder');
    if (input) input.value = '';
    if (preview) preview.classList.add('hidden');
    if (placeholder) placeholder.classList.remove('hidden');
}
function closeConfirmationModal() {
    var modal = document.getElementById('confirmationModal');
    if (modal) modal.classList.add('hidden');
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeConfirmationModal(); }
});
document.addEventListener('DOMContentLoaded', function() {
    // Tinggi kolom percakapan dibatasi lewat CSS (komponen conversation-history),
    // bukan disamakan dengan kolom kiri — kalau disamakan, container ikut
    // memanjang mengikuti isi dan area pesan tidak pernah overflow.
    // Yang diperlukan hanya: mulai dari pesan terbaru.
    var messages = document.querySelector('.conversation-messages');
    if (messages) {
        messages.scrollTop = messages.scrollHeight;
    }
});
</script>
