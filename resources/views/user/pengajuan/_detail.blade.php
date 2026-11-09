{{--
    Partial detail pengajuan user yang dipakai bersama (Tiket IT + Order IPSRS + Pengajuan umum).
    Kandidat: user.pengajuan._detail

    Parameter (@include):
    - $pengajuan   (wajib) model App\Models\Pengajuan (sudah di-load relasi: kategori.jenis, pemohon, department, lokasi, building, orderDetail, historis.actor, pesans.sender, lampirans, penugasan.user)
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
    <div class="mb-2 flex items-center justify-between">
        <a href="{{ $backUrl }}"
            class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            {{ $backLabel }}
        </a>
        <div class="flex flex-wrap gap-1.5">
            @if(!empty($konfirmasiUrl) && $pengajuan->status === 'ditutup' && !$pengajuan->user_confirmation)
                @if($konfirmasiFields === 'ticket')
                    <form action="{{ $konfirmasiUrl }}" method="POST" class="inline-block" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="action" value="confirm">
                        <button type="button" onclick="openConfirmationModal('confirm')"
                            class="bg-green-600 text-white px-3 py-1.5 rounded-md hover:bg-green-700 transition-colors shadow-sm flex items-center text-xs">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Konfirmasi Selesai
                        </button>
                    </form>
                    <form action="{{ $konfirmasiUrl }}" method="POST" class="inline-block" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="action" value="reject">
                        <button type="button" onclick="openConfirmationModal('reject')"
                            class="bg-red-600 text-white px-3 py-1.5 rounded-md hover:bg-red-700 transition-colors shadow-sm flex items-center text-xs">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            Masih Ada Masalah
                        </button>
                    </form>
                @endif
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
    <div class="bg-gradient-to-r {{ $headerGradient }} px-3 py-2 rounded-lg mb-2 flex flex-wrap items-center gap-2">
        <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $statusBadge }}">{{ $statusLabel }}</span>
        <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $prioritasBadge }}">Prioritas {{ $prioritasLabel }}</span>
        <span class="text-xs font-medium text-white">#{{ $pengajuan->nomor }}</span>
        <span class="text-xs text-white/90 ml-auto">{{ $pengajuan->created_at?->format('d M Y H:i') }}</span>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        <!-- Details Column -->
        <div class="lg:col-span-2 space-y-3">
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <!-- Content -->
                <div class="p-3">
                    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-3 gap-y-1.5 text-xs">
                        <div><dt class="text-gray-500">Kategori</dt><dd class="font-medium text-gray-900">{{ $pengajuan->kategori?->name ?? '-' }}</dd></div>
                        <div><dt class="text-gray-500">Departemen</dt><dd class="font-medium text-gray-900">{{ $pengajuan->department?->name ?? '-' }}</dd></div>
                        <div><dt class="text-gray-500">Gedung</dt><dd class="font-medium text-gray-900">{{ $pengajuan->building?->name ?? '-' }}</dd></div>
                        <div><dt class="text-gray-500">Lokasi</dt><dd class="font-medium text-gray-900">{{ $pengajuan->lokasi?->name ?? '-' }}</dd></div>
                        <div><dt class="text-gray-500">Pengguna</dt><dd class="font-medium text-gray-900">{{ $pengajuan->pemohon_nama ?? $pengajuan->pemohon?->name ?? '-' }}</dd></div>
                        <div><dt class="text-gray-500">Tanggal</dt><dd class="font-medium text-gray-900">{{ $pengajuan->tanggal?->format('d M Y, H:i') ?? $pengajuan->created_at?->format('d M Y, H:i') }}</dd></div>
                    </dl>

                    @if($showOrderDetail && $pengajuan->orderDetail)
                    <div class="pt-2 border-t border-gray-100 mt-2">
                        <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-3 gap-y-1.5 text-xs">
                            @if(!empty($pengajuan->orderDetail->kode_inventaris))
                            <div><dt class="text-gray-500">Kode Inventaris</dt><dd class="font-medium text-gray-900">{{ $pengajuan->orderDetail->kode_inventaris }}</dd></div>
                            @endif
                            <div><dt class="text-gray-500">Kategori Order</dt><dd class="font-medium text-gray-900">{{ $pengajuan->kategori?->name ?? '-' }}</dd></div>
                            @if(!empty($pengajuan->orderDetail->nama_barang))
                            <div><dt class="text-gray-500">Nama Barang</dt><dd class="font-medium text-gray-900">{{ $pengajuan->orderDetail->nama_barang }}</dd></div>
                            @endif
                            @if(!empty($pengajuan->orderDetail->unit_penerima))
                            <div><dt class="text-gray-500">Unit Penerima</dt><dd class="font-medium text-gray-900">{{ $pengajuan->orderDetail->unit_penerima }}</dd></div>
                            @endif
                            @if($pengajuan->unitProses)
                            <div><dt class="text-gray-500">Unit Proses</dt><dd class="font-medium text-gray-900">{{ $pengajuan->unitProses->name ?? $pengajuan->unitProses->code ?? '-' }}</dd></div>
                            @endif
                        </dl>
                    </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 pt-2 border-t border-gray-100 mt-2">
                        <div class="sm:col-span-1">
                            <h3 class="text-xs font-medium text-gray-500 mb-1">Foto</h3>
                            <div class="bg-gray-50 p-2 rounded-lg border border-gray-100 h-28 flex items-center justify-center">
                                @if($initialLampiran)
                                    <img src="{{ asset('storage/' . $initialLampiran->path) }}" alt="Foto"
                                        class="max-w-full max-h-full object-contain rounded"
                                        onerror="this.onerror=null; this.classList.add('error-image'); this.parentNode.innerHTML = '<div class=\'text-center p-4\'><svg class=\'mx-auto h-12 w-12 text-gray-400\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'></path></svg><p class=\'mt-2 text-sm text-gray-500\'>Gagal memuat gambar</p></div>';">
                                @else
                                    <div class="text-center">
                                        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <p class="mt-1 text-xs text-gray-500">Tidak ada foto</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="sm:col-span-3">
                            <h3 class="text-xs font-medium text-gray-500 mb-1">Deskripsi</h3>
                            <div class="bg-gray-50 p-2 rounded-lg border border-gray-100 h-28 overflow-auto">
                                <div class="text-xs text-gray-700 whitespace-pre-line">{{ $pengajuan->deskripsi }}</div>
                            </div>
                            @if(!empty($pengajuan->follow_up))
                            <p class="text-xs text-gray-600 mt-1"><span class="font-medium">Tindak Lanjut:</span> {{ $pengajuan->follow_up }}</p>
                            @endif
                        </div>
                    </div>

                    @if($pengajuan->penugasan && $pengajuan->penugasan->isNotEmpty())
                    <div class="pt-2 border-t border-gray-100 mt-2">
                        <h3 class="text-xs font-medium text-gray-500 mb-1">Penanggung Jawab: <span class="text-gray-900">{{ ($activePj ?? $pengajuan->penugasan->where('released_at', null))->pluck('user.name')->filter()->join(', ') ?: 'Belum ada PJ' }}</span></h3>
                        @if(!empty($canAssign) && !empty($assignUrl) && !empty($candidates))
                        <form method="POST" action="{{ $assignUrl }}" class="mt-1 flex gap-2">
                            @csrf
                            <select name="user_ids[]" multiple class="flex-1 px-2 py-1 rounded-md border border-gray-300 text-xs">
                                @foreach($candidates as $candidate)
                                    <option value="{{ $candidate->id }}" @selected((($activePj ?? collect())->pluck('user_id')->contains($candidate->id)))>{{ $candidate->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-xs">Simpan PJ</button>
                        </form>
                        @endif
                    </div>
                    @endif

                    <div class="pt-2 border-t border-gray-100">
                        <h3 class="text-xs font-medium text-gray-500 mb-1">Riwayat Status</h3>
                        <ol class="flex flex-wrap gap-2 text-[11px]">
                            <li class="px-2 py-0.5 rounded-full bg-blue-50 ring-1 ring-blue-200">Dibuat · {{ $pengajuan->created_at?->format('d M H:i') }}</li>
                            @if($pengajuan->started_at)
                            <li class="px-2 py-0.5 rounded-full bg-purple-50 ring-1 ring-purple-200">Dalam Proses · {{ $pengajuan->started_at->format('d M H:i') }}</li>
                            @endif
                            @if($pengajuan->closed_at)
                            <li class="px-2 py-0.5 rounded-full bg-yellow-50 ring-1 ring-yellow-200">Ditutup · {{ $pengajuan->closed_at->format('d M H:i') }}</li>
                            @endif
                            @if($pengajuan->user_confirmed_at)
                            <li class="px-2 py-0.5 rounded-full bg-green-50 ring-1 ring-green-200">Dikonfirmasi · {{ $pengajuan->user_confirmed_at->format('d M H:i') }}</li>
                            @endif
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1 space-y-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-3 py-2 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-xs font-semibold text-gray-800">Riwayat Percakapan</h3>
                    <span class="text-[11px] text-gray-500">{{ count($allResponses) }} pesan</span>
                </div>
                @include('components.conversation-history', ['responses' => $allResponses, 'pengajuName' => $pengajuName])
            </div>
            @if($pengajuan->lampirans->whereNotIn('kind', ['initial'])->isNotEmpty())
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-3">
                <h3 class="text-xs font-medium text-gray-800 mb-1">Lampiran</h3>
                <div class="space-y-1 text-xs">
                    @foreach($pengajuan->lampirans as $lampiran)
                        <a href="{{ asset('storage/' . $lampiran->path) }}" target="_blank" class="block text-blue-600 hover:underline truncate">
                            {{ $lampiran->original_name ?? basename($lampiran->path) }} <span class="text-gray-500">({{ $lampiran->kind }})</span>
                        </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    @if(!empty($replyUrl) && in_array($pengajuan->status, ['in_progress', 'pending', 'open']))
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden mt-2">
        <div class="px-3 py-2 bg-gray-50 border-b border-gray-100">
            <h3 class="text-xs font-semibold text-gray-800">Balas Pengajuan</h3>
        </div>
        <div class="p-3">
            <form action="{{ $replyUrl }}" method="POST" class="flex flex-col md:flex-row gap-2" enctype="multipart/form-data" id="replyForm">
                @csrf
                <textarea name="message" rows="2" required class="flex-1 px-2.5 py-2 rounded-md border border-gray-300 text-sm focus:ring-2 focus:ring-blue-300" placeholder="Ketik pesan Anda di sini..."></textarea>
                <div class="flex items-center gap-2">
                    <label class="inline-flex items-center px-3 py-2 border border-dashed border-gray-300 rounded-md cursor-pointer text-xs">
                        <input type="file" name="photo" accept="image/*" class="hidden" id="reply-photo-upload" onchange="handleImagePreview(this)">
                        <span class="text-blue-600 font-medium">Unggah Foto</span>
                    </label>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-xs font-medium">Kirim Balasan</button>
                </div>
            </form>
            <div id="image-preview" class="hidden mt-1"><img src="" class="max-h-20 rounded"><button type="button" onclick="removeImage()" class="text-xs text-red-600 ml-1">Hapus</button></div>
        </div>
    </div>
    @endif

    @if(!empty($konfirmasiUrl) && $pengajuan->status === 'ditutup' && $konfirmasiFields === 'order')
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mt-2">
        <div class="px-3 py-2 border-b border-gray-100 bg-purple-50">
            <h2 class="text-xs font-semibold text-purple-900">Konfirmasi Selesai</h2>
        </div>
        <div class="p-3">
            <form action="{{ $konfirmasiUrl }}" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-2">
                @csrf
                <select name="konfirmasi" required class="rounded-md border border-gray-200 bg-white text-xs py-2 px-2">
                    <option value="selesai">✅ Ya, sudah selesai</option>
                    <option value="belum">❌ Belum selesai</option>
                </select>
                <textarea name="catatan" rows="1" class="flex-1 rounded-md border border-gray-200 text-xs py-2 px-2" placeholder="Catatan (opsional)..."></textarea>
                <input type="file" name="lampiran" accept=".jpg,.jpeg,.png" class="text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-medium file:bg-purple-50 file:text-violet-700">
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-md">Kirim Konfirmasi</button>
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
function openConfirmationModal(action) {
    var modal = document.getElementById('confirmationModal');
    var form = document.getElementById('confirmationForm');
    var actionInput = document.getElementById('confirmationAction');
    var modalTitle = document.getElementById('modalTitle');
    var submitButton = document.getElementById('submitButton');
    var konfirmasiUrl = @json($konfirmasiUrl ?? null);
    if (form && konfirmasiUrl) form.action = konfirmasiUrl;
    if (actionInput) actionInput.value = action;
    if (modalTitle) modalTitle.textContent = action === 'confirm' ? 'Konfirmasi Penyelesaian' : 'Laporkan Masalah';
    if (submitButton) {
        submitButton.className = 'px-4 py-2 text-white rounded-md ' + (action === 'confirm' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-red-600 hover:bg-red-700');
        submitButton.textContent = action === 'confirm' ? 'Konfirmasi' : 'Laporkan';
    }
    if (modal) modal.classList.remove('hidden');
}
function closeConfirmationModal() {
    var modal = document.getElementById('confirmationModal');
    if (modal) modal.classList.add('hidden');
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeConfirmationModal(); }
});
document.addEventListener('DOMContentLoaded', function() {
    function adjustHeight() {
        var ticketContainer = document.querySelector('.ticket-container');
        var conversationContainer = document.querySelector('.conversation-container');
        if (ticketContainer && conversationContainer) {
            conversationContainer.style.height = ticketContainer.scrollHeight + 'px';
        }
    }
    adjustHeight();
    window.addEventListener('resize', adjustHeight);
    window.addEventListener('load', adjustHeight);
});
</script>

@if($konfirmasiFields === 'ticket' && !empty($konfirmasiUrl))
<div id="confirmationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4" id="modalTitle">Konfirmasi Pengajuan</h3>
                <form id="confirmationForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="action" id="confirmationAction">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                        <textarea name="confirmation_notes" rows="4" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Berikan catatan atau komentar Anda..."></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto (Opsional)</label>
                        <input type="file" name="photo" accept="image/*" class="w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeConfirmationModal()" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Batal</button>
                        <button type="submit" id="submitButton" class="px-4 py-2 text-white rounded-md">Kirim</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
