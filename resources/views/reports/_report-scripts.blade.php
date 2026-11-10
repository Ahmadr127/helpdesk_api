{{--
    JS bersama halaman report (select all + export terpilih).
    Parameter: $exportRoute — nama route POST export (mis. admin.reports.export).
--}}
@php
    $exportRoute = $exportRoute ?? 'admin.reports.export';
@endphp
<script>
const selectAll = document.getElementById('selectAll');
if (selectAll) {
    selectAll.addEventListener('change', function () {
        document.querySelectorAll('.report-checkbox').forEach(function (checkbox) {
            checkbox.checked = selectAll.checked;
        });
    });
}

function exportReport() {
    const selected = Array.from(document.querySelectorAll('.report-checkbox:checked'))
        .map(function (checkbox) { return parseInt(checkbox.value); })
        .filter(function (id) { return !isNaN(id); });

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = "{{ route($exportRoute) }}";

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);

    selected.forEach(function (id) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    // Salin semua field filter aktif (termasuk _filter & checkbox Jenis/Kategori) ke form export.
    const filterForm = document.querySelector('form[method="GET"]');
    if (filterForm) {
        filterForm.querySelectorAll('input, select').forEach(function (field) {
            if (!field.name || field.disabled || field.type === 'submit') return;

            if (field.type === 'checkbox') {
                if (!field.checked) return;
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = field.name;
                input.value = field.value;
                form.appendChild(input);
                return;
            }

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = field.name;
            input.value = field.value;
            form.appendChild(input);
        });
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
</script>
