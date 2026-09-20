@props([
    'name',
    'options' => [], // ['value' => 'label'] or Collection of ['code','name'] or ['id','name']
    'selected' => null,
    'placeholder' => 'Ketik untuk cari...',
    'required' => false,
    'id' => null,
])

@php
    $selectId = $id ?? 'searchable-'.$name.'-'.uniqid();
    $isAssoc = is_array($options) && array_keys($options) !== range(0, count($options)-1);
@endphp

<div data-searchable-select="{{ $selectId }}" class="relative">
    <input
        type="text"
        id="{{ $selectId }}-search"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-colors text-sm mb-1"
        oninput="filterSearchableSelect('{{ $selectId }}', this.value)"
        onclick="this.select()"
    >
    <div class="relative">
        <select
            name="{{ $name }}"
            id="{{ $selectId }}"
            @if($required) required @endif
            class="appearance-none w-full px-4 py-2.5 rounded-lg border border-gray-200 bg-white focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-colors pr-10 text-sm"
            onchange="syncSearchableSelectInput('{{ $selectId }}')"
        >
            <option value="">Select {{ ucfirst($name) }}</option>
            @foreach($options as $key => $label)
                @php
                    // Support: ['code'=>'name'], Collection of models, or ['id','name'] array
                    if ($label instanceof \Illuminate\Database\Eloquent\Model) {
                        $optValue = $label->code ?? $label->id ?? $key;
                        $optLabel = $label->name ?? $label->code ?? $label;
                    } elseif (is_array($label) && isset($label['code'])) {
                        $optValue = $label['code'];
                        $optLabel = $label['name'] ?? $label['code'];
                    } elseif (is_array($label) && isset($label['id'])) {
                        $optValue = $label['id'];
                        $optLabel = $label['name'] ?? $label['id'];
                    } elseif ($isAssoc) {
                        $optValue = $key;
                        $optLabel = $label;
                    } else {
                        $optValue = $label;
                        $optLabel = $label;
                    }
                    // normalize selected comparison (code or name, case-insensitive)
                    $isSelected = false;
                    if ($selected !== null && $selected !== '') {
                        $isSelected = strtolower((string)$selected) === strtolower((string)$optValue)
                                   || strtolower((string)$selected) === strtolower((string)$optLabel);
                    }
                @endphp
                <option value="{{ $optValue }}" @if($isSelected) selected @endif>{{ $optLabel }}</option>
            @endforeach
        </select>
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </div>
    </div>
    <p class="text-xs text-gray-400 mt-1">Ketik di atas untuk filter • <span id="{{ $selectId }}-count"></span> opsi</p>
</div>

@once
@push('scripts')
<script>
function filterSearchableSelect(selectId, query) {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    const q = (query || '').toLowerCase().trim();
    let visible = 0;
    for (let i = 0; i < sel.options.length; i++) {
        const opt = sel.options[i];
        if (opt.value === '') { opt.hidden = false; visible++; continue; }
        const text = (opt.textContent || '').toLowerCase();
        const val = (opt.value || '').toLowerCase();
        const match = q === '' || text.includes(q) || val.includes(q);
        opt.hidden = !match;
        if (match) visible++;
    }
    const cnt = document.getElementById(selectId+'-count');
    if (cnt) cnt.textContent = visible - 1 + ' cocok';
}

function syncSearchableSelectInput(selectId) {
    const sel = document.getElementById(selectId);
    const inp = document.getElementById(selectId+'-search');
    if (!sel || !inp) return;
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value !== '') inp.value = opt.textContent.trim();
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-searchable-select]').forEach(function(wrapper){
        const sid = wrapper.getAttribute('data-searchable-select');
        const sel = document.getElementById(sid);
        const inp = document.getElementById(sid+'-search');
        if (!sel || !inp) return;
        // init input from selected
        if (sel.value !== '') {
            const opt = sel.options[sel.selectedIndex];
            if (opt) inp.value = opt.textContent.trim();
        }
        filterSearchableSelect(sid, inp.value);
    });
});
</script>
@endpush
@endonce
