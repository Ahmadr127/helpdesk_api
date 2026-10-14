{{-- Bubble chat satu riwayat order perbaikan: pengaju rata kanan (biru),
     admin rata kiri (putih + avatar inisial).
     Dipakai oleh: admin/order-perbaikan/show, user/administrasi-umum/order-perbaikan/show.

     Parameter (@include):
     - $history: OrderPerbaikanHistory (status, keterangan, follow_up, lampiran, creator, created_at).
     - $isPengaju: bool.
--}}
@php
    $avatarBg = match($history->status) {
        'open' => 'bg-blue-500',
        'in_progress' => 'bg-amber-500',
        'tutup' => 'bg-violet-500',
        'confirmed' => 'bg-emerald-500',
        'rejected' => 'bg-red-500',
        default => 'bg-slate-500'
    };
    $initial = mb_strtoupper(mb_substr($history->creator?->name ?? 'S', 0, 1));
    $bubbleText = $history->keterangan ?? $history->follow_up;
    $lampiran = $history->lampiran;
    $lampiranIsImage = $lampiran
        ? in_array(strtolower(pathinfo($lampiran, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'])
        : false;
@endphp
<div class="flex items-end gap-2 {{ $isPengaju ? 'justify-end' : 'justify-start' }}">
    @if(!$isPengaju)
    <div class="flex-shrink-0 w-8 h-8 rounded-full {{ $avatarBg }} flex items-center justify-center text-xs font-bold text-white" title="{{ $history->creator?->name ?? 'System' }}">
        {{ $initial }}
    </div>
    @endif
    <div class="max-w-[85%] px-3.5 py-2.5 shadow-sm border {{ $isPengaju ? 'bg-blue-100 border-blue-200 rounded-2xl rounded-br-sm' : 'bg-white border-gray-200 rounded-2xl rounded-tl-sm' }}">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold {{ $history->status_badge_class }}">
                {{ $history->status_label }}
            </span>
            <span class="text-[11px] text-gray-500">{{ $history->creator?->name ?? 'System' }}</span>
        </div>
        @if($bubbleText)
        <p class="mt-1.5 text-sm text-gray-700 leading-relaxed">{{ $bubbleText }}</p>
        @endif
        @if($lampiran)
        <div class="mt-2">
            @if($lampiranIsImage)
                <button type="button" onclick="openImageModal('{{ Storage::url($lampiran) }}')" class="inline-flex items-center px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Lihat Lampiran
                </button>
            @else
                <a href="{{ Storage::url($lampiran) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-700 text-xs font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    {{ basename($lampiran) }}
                </a>
            @endif
        </div>
        @endif
        <p class="mt-1.5 text-[11px] text-gray-400 text-right">{{ $history->created_at->format('d M Y H:i') }}</p>
    </div>
</div>
