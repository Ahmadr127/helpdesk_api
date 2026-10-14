{{-- Area riwayat percakapan (isi di bawah header "Riwayat Percakapan").
    Dipakai oleh: admin/tickets/show, user/ticket/show, myticket detail-body.
    Header + card luar tetap milik halaman pemanggil; komponen ini hanya merender
    area percakapan (background abu muda, daftar bubble, empty state).

    Parameter (@include):
    - $responses: array gabungan balasan, masing-masing ['type' => 'admin'|'user', ...].
      Urutan kronologis (lama di atas) disiapkan oleh pemanggil.
    - $pengajuName: nama pengaju tiket untuk bubble kiri. Default 'Pengaju'.
--}}
@php
    $responses = $responses ?? [];
    $pengajuName = $pengajuName ?? 'Pengaju';
@endphp
@once
<style>
    .conversation-content { flex: 1; overflow: hidden; display: flex; flex-direction: column; background-color: #f9fafb; }
    .conversation-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        min-height: 350px;
    }
    .conversation-messages::-webkit-scrollbar { width: 4px; }
    .conversation-messages::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 2px; }
    .conversation-messages::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 2px; }
    .conversation-messages::-webkit-scrollbar-thumb:hover { background: #a0aec0; }
    .conversation-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        padding: 1.5rem;
        text-align: center;
        color: #6b7280;
    }
    .conversation-empty svg { width: 3.5rem; height: 3.5rem; margin-bottom: 0.75rem; color: #d1d5db; }
    .conversation-empty__title { font-size: 1rem; font-weight: 500; }
    .conversation-empty__subtitle { margin-top: 0.25rem; font-size: 0.875rem; }
    @media (min-width: 1024px) {
        .conversation-messages { height: calc(100vh - 180px); max-height: none; }
    }
</style>
@endonce
<div class="conversation-content">
    <div class="conversation-messages">
        @if(count($responses) > 0)
            @foreach($responses as $response)
                @if(($response['type'] ?? '') === 'admin')
                    @include('components.chat-bubble', [
                        'align' => 'right',
                        'role' => 'admin',
                        'senderName' => $response['admin_name'] ?? 'Admin',
                        'message' => $response['notes'] ?? '',
                        'photo' => $response['photo'] ?? null,
                        'timestamp' => $response['timestamp'] ?? null,
                    ])
                @else
                    @include('components.chat-bubble', [
                        'align' => 'left',
                        'role' => 'pengaju',
                        'senderName' => $pengajuName,
                        'message' => $response['message'] ?? $response['notes'] ?? '',
                        'photo' => $response['photo'] ?? null,
                        'timestamp' => $response['timestamp'] ?? null,
                        'action' => $response['action'] ?? null,
                    ])
                @endif
            @endforeach
        @else
            <div class="conversation-empty">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <p class="conversation-empty__title">Belum Ada Respon</p>
                <p class="conversation-empty__subtitle">Tiket ini belum memiliki balasan</p>
            </div>
        @endif
    </div>
</div>
