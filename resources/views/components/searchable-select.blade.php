@props([
    'name',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Ketik untuk cari...',
    'required' => false,
    'disabled' => false,
    'id' => null,
    // Field mapping saat options berupa Model / array assoc object
    'valueField' => 'id',
    'labelField' => 'name',
    // Label opsi kosong & apakah boleh dikosongkan
    'emptyLabel' => '— Pilih —',
    'allowClear' => true,
    // Tema warna fokus: 'blue' | 'green'
    'accent' => 'green',
])

@php
    $selectId = $id ?? 'searchable-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) $name).'-'.uniqid();
    $accent = in_array($accent, ['blue', 'green'], true) ? $accent : 'green';
    $focusClass = $accent === 'blue'
        ? 'focus:ring-blue-500 focus:border-blue-500'
        : 'focus:ring-green-500 focus:border-green-500';
    $hoverClass = $accent === 'blue' ? 'hover:bg-blue-50 hover:text-blue-700' : 'hover:bg-green-50 hover:text-green-700';
    $activeClass = $accent === 'blue' ? 'bg-blue-50 text-blue-700' : 'bg-green-50 text-green-700';

    // Normalisasi options -> [['value'=>..., 'label'=>...]]
    $normalized = [];
    $selectedLabel = '';
    $selectedValue = $selected !== null ? (string) $selected : '';

    $resolveOption = function ($key, $item) use ($valueField, $labelField) {
        // Eloquent model / object
        if (is_object($item)) {
            $val = null; $lab = null;
            if (isset($item->{$valueField})) $val = $item->{$valueField};
            elseif (method_exists($item, 'getKey')) $val = $item->getKey();
            elseif (isset($item->id)) $val = $item->id;
            elseif (isset($item->code)) $val = $item->code;
            else $val = $key;
            if (isset($item->{$labelField})) $lab = $item->{$labelField};
            elseif (isset($item->name)) $lab = $item->name;
            elseif (isset($item->label)) $lab = $item->label;
            elseif (isset($item->code)) $lab = $item->code;
            else $lab = (string) $val;
            return [(string) $val, (string) $lab];
        }
        // Array bentuk ['id'=>..,'name'=>..] / ['value'=>..,'label'=>..] / ['code'=>..,'name'=>..]
        if (is_array($item)) {
            $val = $item[$valueField] ?? $item['value'] ?? $item['id'] ?? $item['code'] ?? $key;
            $lab = $item[$labelField] ?? $item['label'] ?? $item['name'] ?? $item['code'] ?? (string) $val;
            return [(string) $val, (string) $lab];
        }
        // Scalar pada jalur ini tidak dipakai lagi (ditangani via $isList di bawah).
        return [(string) $item, (string) $item];
    };

    $arr = $options instanceof \Illuminate\Support\Collection ? $options->all() : (is_array($options) ? $options : []);
    // [id => nama] dari pluck()->toArray() punya key integer non-sekuensial
    // (mis. [3 => 'Parkiran']) -> tetap assoc, BUKAN list. array_is_list()
    // hanya true untuk [0, 1, 2, ...] sekuensial.
    $isList = array_is_list($arr);
    foreach ($arr as $ck => $it) {
        // Item array/object (termasuk Eloquent model) selalu satu opsi via field mapping
        if (is_array($it) || is_object($it)) {
            [$v, $l] = $resolveOption($ck, $it);
        } elseif ($isList) {
            $v = (string) $it; $l = (string) $it;
        } else {
            $v = (string) $ck; $l = (string) $it;
        }
        $isSel = $selectedValue !== '' && (string) $v === $selectedValue;
        if ($isSel) { $selectedLabel = $l; }
        $normalized[] = ['value' => $v, 'label' => $l, 'selected' => $isSel];
    }
@endphp

<div data-searchable-dropdown="{{ $selectId }}" data-accent="{{ $accent }}" class="relative">
    <input type="hidden" name="{{ $name }}" id="{{ $selectId }}-value" value="{{ $selectedValue }}"
        @if($required) required @endif @if($disabled) disabled @endif>
    <div class="relative">
        <input
            type="text"
            id="{{ $selectId }}-input"
            role="combobox"
            aria-expanded="false"
            aria-autocomplete="list"
            aria-controls="{{ $selectId }}-listbox"
            placeholder="{{ $placeholder }}"
            value="{{ $selectedLabel }}"
            autocomplete="off"
            @if($disabled) disabled @endif
            class="w-full px-4 py-2.5 pr-10 rounded-lg border border-gray-300 bg-white transition-colors text-sm focus:ring-2 {{ $focusClass }} @if($disabled) bg-gray-100 text-gray-400 cursor-not-allowed @endif"
            data-dropdown-id="{{ $selectId }}"
        >
        <button type="button" tabindex="-1" data-dropdown-toggle="{{ $selectId }}" aria-label="Buka pilihan"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </button>
    </div>
    <div id="{{ $selectId }}-list" class="hidden absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
        <div class="px-3 py-2 text-xs text-gray-400 border-b sticky top-0 bg-white" id="{{ $selectId }}-count"></div>
        <ul id="{{ $selectId }}-listbox" role="listbox" class="py-1">
            @if($allowClear)
            <li role="option" aria-selected="{{ $selectedValue === '' ? 'true' : 'false' }}" data-value="" data-label=""
                class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-50 cursor-pointer {{ $selectedValue === '' ? $activeClass.' font-medium' : '' }}">{{ $emptyLabel }}</li>
            @endif
            @foreach($normalized as $i => $opt)
                <li role="option" aria-selected="{{ $opt['selected'] ? 'true' : 'false' }}"
                    data-value="{{ $opt['value'] }}" data-label="{{ $opt['label'] }}" data-index="{{ $i }}"
                    class="px-4 py-2 text-sm hover:bg-gray-50 cursor-pointer flex justify-between items-center gap-2 {{ $hoverClass }} {{ $opt['selected'] ? $activeClass.' font-medium' : 'text-gray-700' }}">
                    <span class="truncate">{{ $opt['label'] }}</span>
                    @if($opt['selected'])
                    <svg class="w-4 h-4 shrink-0 {{ $accent === 'blue' ? 'text-blue-600' : 'text-green-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    @endif
                </li>
            @endforeach
            <li data-no-results class="hidden px-4 py-3 text-sm text-gray-400 text-center">Tidak ada hasil yang cocok.</li>
        </ul>
    </div>
</div>

@once
@push('scripts')
<script>
(function(){
    if (window.__searchableSelectInit) return;
    window.__searchableSelectInit = true;
    window._searchableOpenId = null;
    var _activeIndex = -1;

    function wrapper(id){ return document.querySelector('[data-searchable-dropdown="'+id+'"]'); }
    function hiddenEl(id){ return document.getElementById(id+'-value'); }
    function inputEl(id){ return document.getElementById(id+'-input'); }
    function listEl(id){ return document.getElementById(id+'-list'); }
    function accentOf(id){ var w = wrapper(id); return (w && w.getAttribute('data-accent')) || 'green'; }
    function activeCls(accent){ return accent === 'blue' ? ['bg-blue-50','text-blue-700'] : ['bg-green-50','text-green-700']; }

    function optionItems(id){
        var list = listEl(id);
        if (!list) return [];
        return Array.prototype.filter.call(
            list.querySelectorAll('li[data-value]'),
            function(li){ return li.style.display !== 'none'; }
        );
    }

    function paintCount(id, visible){
        var cnt = document.getElementById(id+'-count');
        if (!cnt) return;
        var emptyLi = listEl(id) ? listEl(id).querySelector('li[data-value=""]') : null;
        var n = visible - (emptyLi && emptyLi.style.display !== 'none' ? 1 : 0);
        cnt.textContent = n + ' opsi cocok';
        var noRes = listEl(id) ? listEl(id).querySelector('[data-no-results]') : null;
        if (noRes) noRes.classList.toggle('hidden', n > 0);
    }

    function highlight(id, items, idx){
        _activeIndex = idx;
        items.forEach(function(li, i){
            li.classList.toggle('ring-2', i === idx);
            li.classList.toggle('ring-inset', i === idx);
            li.classList.toggle('ring-gray-300', i === idx);
        });
        if (items[idx] && typeof items[idx].scrollIntoView === 'function') {
            items[idx].scrollIntoView({block:'nearest'});
        }
    }

    window.openSearchableDropdown = function(id){
        var list = listEl(id), input = inputEl(id);
        if (!list || !input || input.disabled) return;
        document.querySelectorAll('[id$="-list"]').forEach(function(el){ if (el.id !== id+'-list') el.classList.add('hidden'); });
        list.classList.remove('hidden');
        input.setAttribute('aria-expanded', 'true');
        window._searchableOpenId = id;
        window.filterSearchableDropdown(id, input.value);
    };

    window.toggleSearchableDropdown = function(id){
        var list = listEl(id);
        if (!list) return;
        if (list.classList.contains('hidden')) window.openSearchableDropdown(id);
        else window.closeSearchableDropdown(id);
    };

    window.closeSearchableDropdown = function(id){
        var list = listEl(id), input = inputEl(id);
        if (list) list.classList.add('hidden');
        if (input) input.setAttribute('aria-expanded', 'false');
        if (window._searchableOpenId === id) window._searchableOpenId = null;
        _activeIndex = -1;
    };

    window.filterSearchableDropdown = function(id, q){
        var list = listEl(id);
        if (!list) return;
        var query = (q || '').toLowerCase().trim();
        var visible = 0;
        list.querySelectorAll('li[data-value]').forEach(function(li){
            var label = ((li.getAttribute('data-label') || li.textContent) || '').toLowerCase();
            var val = (li.getAttribute('data-value') || '').toLowerCase();
            var match = query === '' || label.indexOf(query) !== -1 || val.indexOf(query) !== -1;
            li.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        paintCount(id, visible);
        if (list.classList.contains('hidden') && query !== '') {
            window.openSearchableDropdown(id);
        }
        highlight(id, optionItems(id), -1);
    };

    window.selectSearchableOption = function(id, value, label){
        var hidden = hiddenEl(id), input = inputEl(id), list = listEl(id);
        value = (value === undefined || value === null) ? '' : String(value);
        if (hidden) {
            hidden.value = value;
            hidden.dispatchEvent(new Event('change', {bubbles:true}));
            hidden.dispatchEvent(new Event('input', {bubbles:true}));
        }
        if (input) {
            if (label === undefined || label === null) {
                var li = list ? list.querySelector('li[data-value="'+CSS.escape(value)+'"]') : null;
                input.value = (value === '') ? '' : ((li && li.getAttribute('data-label')) || '');
            } else {
                input.value = (value === '') ? '' : String(label);
            }
            input.classList.remove('border-red-500', 'ring-red-200');
        }
        if (list) {
            var accent = accentOf(id), cls = activeCls(accent);
            list.querySelectorAll('li[data-value]').forEach(function(li){
                var isSel = li.getAttribute('data-value') === value;
                li.setAttribute('aria-selected', isSel ? 'true' : 'false');
                cls.forEach(function(c){ li.classList.toggle(c, isSel); });
                li.classList.toggle('font-medium', isSel);
                li.classList.toggle('text-gray-700', !isSel);
            });
            window.closeSearchableDropdown(id);
        }
    };

    // Delegasi klik: buka/tutup + pilih opsi (tanpa inline onclick -> aman untuk kutip)
    document.addEventListener('click', function(e){
        var openId = window._searchableOpenId;
        var toggle = e.target.closest ? e.target.closest('[data-dropdown-toggle]') : null;
        if (toggle) {
            e.preventDefault();
            window.toggleSearchableDropdown(toggle.getAttribute('data-dropdown-toggle'));
            return;
        }
        var inp = e.target.closest ? e.target.closest('input[data-dropdown-id]') : null;
        if (inp) {
            window.openSearchableDropdown(inp.getAttribute('data-dropdown-id'));
            return;
        }
        var li = e.target.closest ? e.target.closest('li[data-value]') : null;
        if (li) {
            var box = li.closest ? li.closest('[id$="-list"]') : null;
            if (box) {
                var id = box.id.replace(/-list$/, '');
                window.selectSearchableOption(id, li.getAttribute('data-value'), li.getAttribute('data-label'));
                return;
            }
        }
        if (openId) {
            var w = wrapper(openId);
            if (w && !w.contains(e.target)) {
                // Strict mode: jika teks tidak cocok opsi mana pun -> kosongkan hidden
                // (form filter bebas mengisi ulang hidden dari teks via validateFilterForm)
                strictSync(openId);
                window.closeSearchableDropdown(openId);
            }
        }
    });

    // Ketik + navigasi keyboard
    document.addEventListener('input', function(e){
        var inp = e.target.closest ? e.target.closest('input[data-dropdown-id]') : null;
        if (!inp) return;
        window.filterSearchableDropdown(inp.getAttribute('data-dropdown-id'), inp.value);
    });
    document.addEventListener('focusin', function(e){
        var inp = e.target.closest ? e.target.closest('input[data-dropdown-id]') : null;
        if (!inp) return;
        window.openSearchableDropdown(inp.getAttribute('data-dropdown-id'));
    });
    document.addEventListener('focusout', function(e){
        var inp = e.target.closest ? e.target.closest('input[data-dropdown-id]') : null;
        if (!inp) return;
        var id = inp.getAttribute('data-dropdown-id');
        // tunda agar klik opsi sempat diproses dulu
        setTimeout(function(){
            if (window._searchableOpenId === id) {
                var list = listEl(id);
                if (list && list.querySelector(':hover')) return;
                strictSync(id);
                window.closeSearchableDropdown(id);
            }
        }, 150);
    });
    document.addEventListener('keydown', function(e){
        var openId = window._searchableOpenId;
        if (!openId) return;
        var items = optionItems(openId);
        if (e.key === 'Escape') { strictSync(openId); window.closeSearchableDropdown(openId); }
        else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!items.length) return;
            var next = e.key === 'ArrowDown' ? _activeIndex + 1 : _activeIndex - 1;
            if (next < 0) next = items.length - 1;
            if (next >= items.length) next = 0;
            highlight(openId, items, next);
        }
        else if (e.key === 'Enter') {
            if (_activeIndex >= 0 && items[_activeIndex]) {
                e.preventDefault();
                var li = items[_activeIndex];
                window.selectSearchableOption(openId, li.getAttribute('data-value'), li.getAttribute('data-label'));
            } else {
                // Enter tanpa sorotan: sinkronkan ketat lalu tutup
                strictSync(openId);
                window.closeSearchableDropdown(openId);
            }
        }
    });

    function strictSync(id){
        var hidden = hiddenEl(id), input = inputEl(id), list = listEl(id);
        if (!hidden || !input || !list) return;
        var txt = (input.value || '').trim();
        if (txt === '') {
            if (hidden.value !== '') {
                hidden.value = '';
                hidden.dispatchEvent(new Event('change', {bubbles:true}));
            }
            return;
        }
        var exact = null;
        list.querySelectorAll('li[data-value]').forEach(function(li){
            var lab = (li.getAttribute('data-label') || li.textContent || '').trim().toLowerCase();
            var val = (li.getAttribute('data-value') || '').toLowerCase();
            if (lab === txt.toLowerCase() || (val !== '' && val === txt.toLowerCase())) exact = li;
        });
        if (exact) {
            var v = exact.getAttribute('data-value') || '';
            if (hidden.value !== v) {
                hidden.value = v;
                hidden.dispatchEvent(new Event('change', {bubbles:true}));
            }
            input.value = exact.getAttribute('data-label') || '';
        } else {
            // teks bebas -> hidden dikosongkan (validasi required akan menolak;
            // form filter bisa menyalin teks ke hidden via validateFilterForm)
            if (hidden.value !== '') {
                hidden.value = '';
                hidden.dispatchEvent(new Event('change', {bubbles:true}));
            }
        }
    }

    // Validasi submit: field required yang teksnya tidak cocok opsi -> blokir + tandai
    document.addEventListener('submit', function(e){
        var form = e.target;
        if (!form || !form.querySelector) return;
        var wraps = form.querySelectorAll('[data-searchable-dropdown]');
        if (!wraps.length) return;
        var firstBad = null;
        wraps.forEach(function(w){
            var id = w.getAttribute('data-searchable-dropdown');
            var hidden = hiddenEl(id), input = inputEl(id);
            if (!hidden || !input) return;
            if (!hidden.hasAttribute('required')) return;
            strictSync(id);
            // izinkan fallback free-text: jika form punya handler sendiri yang menyalin
            // teks ke hidden (mis. validateFilterForm), jangan blokir di sini.
            if (hidden.value === '' && input.value.trim() !== '' && form.getAttribute('onsubmit')) return;
            if (hidden.value === '') {
                input.classList.add('border-red-500', 'ring-red-200');
                if (!firstBad) firstBad = input;
            }
        });
        if (firstBad) {
            e.preventDefault();
            window.openSearchableDropdown(firstBad.getAttribute('data-dropdown-id'));
            firstBad.focus();
        }
    });

    // Helpers lama
    window.filterSearchableSelect = window.filterSearchableDropdown;
    window.syncSearchableSelectInput = function(id){
        var hidden = hiddenEl(id), inp = inputEl(id), list = listEl(id);
        if (hidden && inp && list) {
            var li = list.querySelector('li[data-value="'+CSS.escape(hidden.value)+'"]');
            if (li) inp.value = li.getAttribute('data-label') || '';
        }
    };
})();
</script>
@endpush
@endonce
