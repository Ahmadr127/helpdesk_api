{{--
    Script binding filter tabel pengajuan.

    - Select & tanggal: submit otomatis saat berubah.
    - Kolom teks: Enter = submit.

    Dipakai oleh admin.tickets.partials.ticket-table (hanya saat filter tampil).
--}}
@push('scripts')
<script>
    (function () {
        if (window.__pengajuanFilterBound) {
            return;
        }
        window.__pengajuanFilterBound = true;

        function bindPengajuanFilter() {
            document.querySelectorAll('[data-pengajuan-filter-form]').forEach(function (form) {
                form.querySelectorAll('[data-filter-auto]').forEach(function (el) {
                    el.addEventListener('change', function () {
                        form.submit();
                    });
                });

                form.querySelectorAll('input[type="text"], input[type="search"]').forEach(function (el) {
                    el.addEventListener('keydown', function (event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            form.submit();
                        }
                    });
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindPengajuanFilter);
        } else {
            bindPengajuanFilter();
        }
    })();
</script>
@endpush
