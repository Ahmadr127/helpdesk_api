{{-- Popup pengingat konfirmasi: muncul setiap halaman dimuat selama masih ada
     tiket `closed` / order `tutup` milik user yang menunggu konfirmasi.
     Data: $pendingConfirmations dari View composer (AppServiceProvider). --}}
@if(!empty($pendingConfirmations['total']))
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Swal === 'undefined') return;
    Swal.fire({
        title: 'Perlu Konfirmasi ({{ $pendingConfirmations['total'] }})',
        html: `{!! implode('', array_map(fn($t) => '<a href="' . e($t['url']) . '" style="display:flex;gap:8px;align-items:center;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:6px;text-decoration:none;">'
            . '<span style="font-size:11px;font-weight:700;color:#fff;background:#f59e0b;border-radius:4px;padding:2px 6px;white-space:nowrap;">TIKET</span>'
            . '<span style="text-align:left;"><b style="display:block;font-size:13px;color:#0f172a;">' . e($t['label']) . '</b>'
            . '<span style="font-size:12px;color:#64748b;">' . e($t['title']) . '</span></span></a>', $pendingConfirmations['tickets'])) !!}{!! implode('', array_map(fn($o) => '<a href="' . e($o['url']) . '" style="display:flex;gap:8px;align-items:center;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:6px;text-decoration:none;">'
            . '<span style="font-size:11px;font-weight:700;color:#fff;background:#10b981;border-radius:4px;padding:2px 6px;white-space:nowrap;">ORDER</span>'
            . '<span style="text-align:left;"><b style="display:block;font-size:13px;color:#0f172a;">' . e($o['label']) . '</b>'
            . '<span style="font-size:12px;color:#64748b;">' . e($o['title']) . '</span></span></a>', $pendingConfirmations['orders'])) !!}<p style="font-size:12px;color:#64748b;margin:4px 0 0;">Klik item untuk melihat & konfirmasi. Tutup untuk lanjut.</p>`,
        icon: 'warning',
        showConfirmButton: false,
        showCloseButton: true,
        allowOutsideClick: true,
        width: 480,
    });
});
</script>
@endif
