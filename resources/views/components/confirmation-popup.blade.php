{{-- Popup pengingat konfirmasi: muncul setiap halaman dimuat selama masih ada
     tiket `closed` / order `tutup` milik user yang menunggu konfirmasi.
     Data: $pendingConfirmations dari View composer (AppServiceProvider).

     HTML daftar item dibangun di PHP lalu di-pass lewat @json (escaped
     sepenuhnya sebagai string JS) sehingga konten dari user tidak bisa
     merusak sintaks <script>. --}}
@if(!empty($pendingConfirmations['total']))
@php
    $link = fn (array $item, string $badge, string $badgeBg): string =>
        '<a href="' . e($item['url']) . '" style="display:flex;gap:8px;align-items:center;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:6px;text-decoration:none;">'
        . '<span style="font-size:11px;font-weight:700;color:#fff;background:' . $badgeBg . ';border-radius:4px;padding:2px 6px;white-space:nowrap;">' . $badge . '</span>'
        . '<span style="text-align:left;"><b style="display:block;font-size:13px;color:#0f172a;">' . e($item['label']) . '</b>'
        . '<span style="font-size:12px;color:#64748b;">' . e($item['title']) . '</span></span></a>';

    $html = implode('', array_map(fn ($t) => $link($t, 'TIKET', '#f59e0b'), $pendingConfirmations['tickets']))
        . implode('', array_map(fn ($o) => $link($o, 'ORDER', '#10b981'), $pendingConfirmations['orders']))
        . '<p style="font-size:12px;color:#64748b;margin:4px 0 0;">Klik item untuk melihat &amp; konfirmasi. Tutup untuk lanjut.</p>';
@endphp
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Swal === 'undefined') return;
    Swal.fire({
        title: @json('Perlu Konfirmasi (' . $pendingConfirmations['total'] . ')'),
        html: @json($html),
        icon: 'warning',
        showConfirmButton: false,
        showCloseButton: true,
        allowOutsideClick: true,
        width: 480,
    });
});
</script>
@endif
