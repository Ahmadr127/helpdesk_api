{{-- Searchable multi select (vanilla JS, tanpa dependency eksternal).
     Dipakai untuk memilih banyak opsi dengan pencarian.

     Parameter (@include):
     - $name              (wajib) nama field form, mis. 'user_ids[]'
     - $options           (wajib) koleksi objek dengan id & name
     - $selected          (opsional) array id yang terpilih (default [])
     - $placeholder       (opsional) teks tombol saat kosong
     - $searchPlaceholder (opsional) placeholder input cari
     - $width             (opsional) class lebar tombol (default w-full) --}}
@php
    $selected = collect($selected ?? [])->map(fn ($v) => (int) $v)->all();
    $compact = (bool) ($compact ?? false);
    $options = collect($options ?? []);
    $toggleClass = $compact
        ? 'sms-toggle w-full flex items-center justify-between gap-2 px-2 py-1.5 rounded-md border border-gray-300 bg-white text-xs text-left focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[2.25rem]'
        : 'sms-toggle w-full flex items-center justify-between gap-2 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-left focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[2.5rem]';
@endphp

<div class="sms-root relative {{ $width ?? 'w-full' }}" data-sms-root>
    <button type="button"
            class="{{ $toggleClass }}"
            data-sms-toggle aria-haspopup="listbox" aria-expanded="false">
        <span class="sms-chips flex flex-wrap gap-1 min-w-0" data-sms-chips></span>
        <span class="sms-placeholder text-gray-400 {{ count($selected) ? 'hidden' : '' }} {{ $compact ? 'text-xs' : '' }}" data-sms-placeholder>{{ $placeholder ?? 'Pilih penanggung jawab…' }}</span>
        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" data-sms-chevron fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div class="sms-panel absolute z-40 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden hidden" data-sms-panel role="listbox">
        <div class="p-2 border-b border-gray-100">
            <input type="text"
                   class="sms-search w-full px-3 py-2 rounded-md border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                   placeholder="{{ $searchPlaceholder ?? 'Cari nama…' }}"
                   autocomplete="off"
                   data-sms-search>
        </div>
        <div class="max-h-60 overflow-y-auto p-1.5" data-sms-list>
            @foreach($options as $option)
                <label class="sms-option flex items-center gap-2 px-2 py-2 rounded-md hover:bg-blue-50 cursor-pointer"
                       data-sms-option
                       data-search="{{ mb_strtolower($option->name ?? (string) $option->id) }}">
                    <input type="checkbox"
                           class="sms-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                           name="{{ $name }}"
                           value="{{ $option->id }}"
                           @checked(in_array((int) $option->id, $selected, true))
                           data-sms-checkbox>
                    <span class="text-sm text-gray-800">{{ $option->name }}</span>
                </label>
            @endforeach
            <p class="sms-empty hidden text-center text-sm text-gray-400 py-4" data-sms-empty>Tidak ada hasil</p>
        </div>
    </div>
</div>

@once
<style>
    .sms-chips span.sms-chip { display: inline-flex; align-items: center; gap: .25rem; background: #eff6ff; color: #1d4ed8; font-size: 11px; font-weight: 600; padding: .125rem .5rem; border-radius: 9999px; max-width: 100%; }
    .sms-chips span.sms-chip button { line-height: 1; opacity: .7; }
    .sms-chips span.sms-chip button:hover { opacity: 1; }
</style>
<script>
(function () {
    'use strict';

    function initRoot(root) {
        var toggle = root.querySelector('[data-sms-toggle]');
        var panel = root.querySelector('[data-sms-panel]');
        var search = root.querySelector('[data-sms-search]');
        var options = root.querySelectorAll('[data-sms-option]');
        var empty = root.querySelector('[data-sms-empty]');
        var chevron = root.querySelector('[data-sms-chevron]');

        renderChips(root);

        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            var willOpen = panel.classList.contains('hidden');
            closeAll(root);
            panel.classList.toggle('hidden', !willOpen);
            chevron.style.transform = willOpen ? 'rotate(180deg)' : '';
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            if (willOpen) {
                search.value = '';
                options.forEach(function (opt) { opt.classList.remove('hidden'); });
                empty.classList.add('hidden');
                search.focus();
            }
        });

        search.addEventListener('input', function () {
            var q = this.value.toLowerCase().trim();
            var visible = 0;
            options.forEach(function (opt) {
                var match = !q || (opt.getAttribute('data-search') || '').indexOf(q) !== -1;
                opt.classList.toggle('hidden', !match);
                if (match) visible++;
            });
            empty.classList.toggle('hidden', visible > 0);
        });

        root.querySelectorAll('[data-sms-checkbox]').forEach(function (input) {
            input.addEventListener('change', function () { renderChips(root); });
        });
    }

    function initAll() {
        document.querySelectorAll('[data-sms-root]').forEach(initRoot);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    function renderChips(root) {
        var chips = root.querySelector('[data-sms-chips]');
        var placeholder = root.querySelector('[data-sms-placeholder]');
        var checked = root.querySelectorAll('[data-sms-checkbox]:checked');
        chips.innerHTML = '';

        checked.forEach(function (input) {
            var name = input.closest('[data-sms-option]').querySelector('span:last-child').textContent.trim();
            var chip = document.createElement('span');
            chip.className = 'sms-chip';
            chip.textContent = name;
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.innerHTML = '&times;';
            btn.setAttribute('aria-label', 'Hapus ' + name);
            btn.addEventListener('click', function () {
                input.checked = false;
                renderChips(root);
            });
            chip.appendChild(btn);
            chips.appendChild(chip);
        });

        placeholder.classList.toggle('hidden', checked.length > 0);
    }

    function closeAll(except) {
        document.querySelectorAll('[data-sms-root]').forEach(function (root) {
            if (root !== except) {
                root.querySelector('[data-sms-panel]').classList.add('hidden');
                root.querySelector('[data-sms-chevron]').style.transform = '';
                root.querySelector('[data-sms-toggle]').setAttribute('aria-expanded', 'false');
            }
        });
    }

    document.addEventListener('click', function () { closeAll(null); });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') { closeAll(null); }
    });
})();
</script>
@endonce
