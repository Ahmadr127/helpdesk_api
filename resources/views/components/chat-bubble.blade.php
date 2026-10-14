{{-- Reusable bubble chat untuk riwayat percakapan tiket.
    Dipakai oleh: admin/tickets/show, user/ticket/show, myticket detail-body (via conversation-history).

    Parameter (@include):
    - $align: 'right' (admin) | 'left' (pengaju). Default 'left'.
    - $role: 'admin' | 'pengaju'. Default 'pengaju'.
    - $senderName: nama pengirim. Default ''.
    - $message: isi pesan teks. Default ''.
    - $photo: path storage foto (mis. 'ticket-replies/xxx.jpg') atau null. Default null.
    - $timestamp: string datetime. Default null.
    - $action: null | 'confirm' | 'reject' (badge status khusus pesan pengaju). Default null.
--}}
@php
    $align = $align ?? 'left';
    $role = $role ?? 'pengaju';
    $senderName = $senderName ?? '';
    $message = $message ?? '';
    $photo = $photo ?? null;
    $timestamp = $timestamp ?? null;
    $action = $action ?? null;
    $isRight = $align === 'right';
    $formattedTime = $timestamp ? \Carbon\Carbon::parse($timestamp)->format('d M Y H:i') : '';
@endphp
@once
<style>
    .cbubble-row { display: flex; width: 100%; }
    .cbubble-row--right { justify-content: flex-end; }
    .cbubble-row--left { justify-content: flex-start; }
    .cbubble {
        width: fit-content;
        max-width: min(75%, 32rem);
        min-width: 0;
        border-radius: 1rem;
        padding: 0.75rem;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .cbubble--admin {
        background-color: #3b82f6;
        color: #ffffff;
        border-bottom-right-radius: 0.375rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
    }
    .cbubble--user {
        background-color: #ffffff;
        color: #1f2937;
        border: 1px solid #e5e7eb;
        border-bottom-left-radius: 0.375rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    .cbubble__meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.25rem;
        min-width: 0;
        flex-wrap: wrap;
    }
    .cbubble-row--right .cbubble__meta { justify-content: flex-end; }
    .cbubble__name {
        font-size: 0.75rem;
        font-weight: 600;
        min-width: 0;
        overflow-wrap: anywhere;
    }
    .cbubble--admin .cbubble__name { color: #ffffff; }
    .cbubble--user .cbubble__name { color: #1f2937; }
    .cbubble__badge {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 0.125rem 0.5rem;
        border-radius: 9999px;
        white-space: nowrap;
    }
    .cbubble__badge--admin { background-color: rgba(255, 255, 255, 0.25); color: #ffffff; }
    .cbubble__badge--pengaju { background-color: #dcfce7; color: #15803d; }
    .cbubble__text {
        font-size: 0.875rem;
        line-height: 1.5;
        white-space: pre-line;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .cbubble--admin .cbubble__text { color: #ffffff; }
    .cbubble__status {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.75rem;
        font-weight: 500;
        padding: 0.125rem 0.625rem;
        border-radius: 9999px;
        margin-bottom: 0.25rem;
        max-width: 100%;
    }
    .cbubble__status--confirm { background-color: #dcfce7; color: #166534; }
    .cbubble__status--reject { background-color: #fee2e2; color: #991b1b; }
    .cbubble__status svg { width: 0.75rem; height: 0.75rem; flex-shrink: 0; }
    .cbubble__attach { margin-top: 0.5rem; min-width: 0; max-width: 100%; }
    .cbubble__attach a { display: block; max-width: 100%; }
    .cbubble__attach img {
        display: block;
        max-width: 100%;
        width: auto;
        max-height: 14rem;
        object-fit: contain;
        border-radius: 0.5rem;
    }
    .cbubble--admin .cbubble__attach img { border: 1px solid rgba(255, 255, 255, 0.4); margin-left: auto; }
    .cbubble--user .cbubble__attach img { border: 1px solid #e5e7eb; }
    .cbubble__attach img:hover { opacity: 0.9; }
    .cbubble__time { font-size: 10px; margin-top: 0.25rem; overflow-wrap: anywhere; }
    .cbubble-row--right .cbubble__time { text-align: right; color: #dbeafe; }
    .cbubble-row--left .cbubble__time { color: #9ca3af; }
    @media (max-width: 1024px) {
        .cbubble { max-width: 85%; }
    }
    @media (max-width: 640px) {
        .cbubble { max-width: 92%; }
        .cbubble__attach img { max-height: 10rem; }
    }
</style>
@endonce
<div class="cbubble-row {{ $isRight ? 'cbubble-row--right' : 'cbubble-row--left' }}">
    <div class="cbubble {{ $role === 'admin' ? 'cbubble--admin' : 'cbubble--user' }}">
        <div class="cbubble__meta">
            @if($role === 'admin')
                <span class="cbubble__badge cbubble__badge--admin">Admin</span>
                <span class="cbubble__name">{{ $senderName }}</span>
            @else
                <span class="cbubble__name">{{ $senderName }}</span>
                <span class="cbubble__badge cbubble__badge--pengaju">Pengaju</span>
            @endif
        </div>
        @if($role !== 'admin' && $action === 'confirm')
            <div>
                <span class="cbubble__status cbubble__status--confirm">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                    Dikonfirmasi selesai
                </span>
            </div>
        @elseif($role !== 'admin' && $action === 'reject')
            <div>
                <span class="cbubble__status cbubble__status--reject">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                    Masih ada masalah
                </span>
            </div>
        @endif
        @if($message !== '' && $message !== null)
            <p class="cbubble__text">{{ $message }}</p>
        @endif
        @if(!empty($photo))
            <div class="cbubble__attach">
                <a href="{{ asset('storage/' . $photo) }}" target="_blank">
                    <img src="{{ asset('storage/' . $photo) }}" alt="Lampiran percakapan" loading="lazy">
                </a>
            </div>
        @endif
        @if($formattedTime !== '')
            <div class="cbubble__time">{{ $formattedTime }}</div>
        @endif
    </div>
</div>
