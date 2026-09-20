@props([
    'name',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Ketik untuk cari...',
    'required' => false,
    'id' => null,
])

@php
    $selectId = $id ?? 'searchable-'.$name.'-'.uniqid();
    $isAssoc = is_array($options) && array_keys($options) !== range(0, count($options)-1);
    // Build normalized options list: [['value'=>..., 'label'=>...]]
    $normalized = [];
    $selectedLabel = '';
    foreach ($options as $key => $label) {
        if ($label instanceof \Illuminate\Database\Eloquent\Model) {
            $optValue = $label->code ?? $label->id ?? $key;
            $optLabel = $label->name ?? $label->code ?? (string)$label;
        } elseif (is_array($label) && isset($label['code'])) {
            $optValue = $label['code'];
            $optLabel = $label['name'] ?? $label['code'];
        } elseif (is_array($label) && isset($label['id'])) {
            $optValue = $label['id'];
            $optLabel = $label['name'] ?? (string)$label['id'];
        } elseif ($isAssoc) {
            $optValue = $key;
            $optLabel = $label;
        } else {
            $optValue = $label;
            $optLabel = $label;
        }
        $optValue = (string)$optValue;
        $optLabel = (string)$optLabel;
        $isSelected = $selected !== null && $selected !== '' && (strtolower((string)$selected) === strtolower($optValue) || strtolower((string)$selected) === strtolower($optLabel));
        if ($isSelected) $selectedLabel = $optLabel;
        $normalized[] = ['value'=>$optValue, 'label'=>$optLabel, 'selected'=>$isSelected];
    }
@endphp

<div data-searchable-dropdown="{{ $selectId }}" class="relative">
    {{-- hidden value untuk submit --}}
    <input type="hidden" name="{{ $name }}" id="{{ $selectId }}-value" value="{{ $selected ?? '' }}" @if($required) required @endif>
    {{-- satu field: input pencarian + dropdown --}}
    <div class="relative">
        <input
            type="text"
            id="{{ $selectId }}-input"
            placeholder="{{ $placeholder }}"
            value="{{ $selectedLabel }}"
            autocomplete="off"
            class="w-full px-4 py-2.5 pr-10 rounded-lg border border-gray-200 bg-white focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-colors text-sm"
            onfocus="openSearchableDropdown('{{ $selectId }}')"
            oninput="filterSearchableDropdown('{{ $selectId }}', this.value)"
            onclick="openSearchableDropdown('{{ $selectId }}')"
        >
        <button type="button" tabindex="-1" onclick="toggleSearchableDropdown('{{ $selectId }}')" class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </button>
    </div>
    {{-- dropdown list --}}
    <div id="{{ $selectId }}-list" class="hidden absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
        <div class="px-3 py-2 text-xs text-gray-400 border-b" id="{{ $selectId }}-count"></div>
        <ul class="py-1">
            <li data-value="" class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-50 cursor-pointer {{ ($selected ?? '') === '' ? 'bg-green-50 text-green-700' : '' }}" onclick="selectSearchableOption('{{ $selectId }}', '', '— Kosong —')">— Kosong —</li>
            @foreach($normalized as $opt)
                <li data-value="{{ $opt['value'] }}" data-label="{{ $opt['label'] }}" class="px-4 py-2 text-sm hover:bg-green-50 hover:text-green-700 cursor-pointer flex justify-between items-center {{ $opt['selected'] ? 'bg-green-50 text-green-700 font-medium' : 'text-gray-700' }}" onclick="selectSearchableOption('{{ $selectId }}', '{{ addslashes($opt['value']) }}', '{{ addslashes($opt['label']) }}')">
                    <span>{{ $opt['label'] }}</span>
                    @if($opt['selected'])<svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>@endif
                </li>
            @endforeach
        </ul>
    </div>
</div>

@once
@push('scripts')
<script>
window._searchableOpenId = null;
function openSearchableDropdown(id){
    const list = document.getElementById(id+'-list');
    if (!list) return;
    // close others
    document.querySelectorAll('[id$="-list"]').forEach(el=>{ if(el.id!==id+'-list') el.classList.add('hidden'); });
    list.classList.remove('hidden');
    window._searchableOpenId = id;
    filterSearchableDropdown(id, document.getElementById(id+'-input')?.value || '');
}
function toggleSearchableDropdown(id){
    const list = document.getElementById(id+'-list');
    if (!list) return;
    if (list.classList.contains('hidden')) openSearchableDropdown(id);
    else list.classList.add('hidden');
}
function filterSearchableDropdown(id, q){
    const list = document.getElementById(id+'-list');
    if (!list) return;
    const query = (q||'').toLowerCase().trim();
    let visible = 0;
    list.querySelectorAll('li[data-value]').forEach(li=>{
        const label = (li.getAttribute('data-label')||li.textContent||'').toLowerCase();
        const val = (li.getAttribute('data-value')||'').toLowerCase();
        const match = query==='' || label.includes(query) || val.includes(query);
        li.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    const cnt = document.getElementById(id+'-count');
    if (cnt) cnt.textContent = (visible-1)+' opsi cocok';
    if (list.classList.contains('hidden') && query!=='') list.classList.remove('hidden');
}
function selectSearchableOption(id, value, label){
    const hidden = document.getElementById(id+'-value');
    const input = document.getElementById(id+'-input');
    const list = document.getElementById(id+'-list');
    if (hidden) { hidden.value = value; hidden.dispatchEvent(new Event('change', {bubbles:true})); }
    if (input) input.value = value==='' ? '' : label;
    if (list) list.classList.add('hidden');
    // highlight
    if (list) {
        list.querySelectorAll('li').forEach(li=>{
            const isSel = li.getAttribute('data-value')===value;
            li.classList.toggle('bg-green-50', isSel);
            li.classList.toggle('text-green-700', isSel);
            li.classList.toggle('font-medium', isSel);
        });
    }
}
// legacy helpers for old code (department modal)
window.filterSearchableSelect = filterSearchableDropdown;
window.syncSearchableSelectInput = function(id){
    const sel = document.getElementById(id);
    const input = document.getElementById(id+'-search');
    if (sel && input) {
        const opt = sel.options[sel.selectedIndex];
        if (opt) input.value = opt.textContent.trim();
    }
    // also handle new dropdown
    const hidden = document.getElementById(id+'-value');
    const inp = document.getElementById(id+'-input');
    if (hidden && inp) {
        // if old code set hidden via select, sync to new input
        const val = hidden.value;
        const list = document.getElementById(id+'-list');
        if (list) {
            const li = list.querySelector('li[data-value="'+CSS.escape(val)+'"]');
            if (li) inp.value = li.getAttribute('data-label')||'';
        }
    }
};
document.addEventListener('click', function(e){
    const openId = window._searchableOpenId;
    if (!openId) return;
    const wrapper = document.querySelector('[data-searchable-dropdown="'+openId+'"]');
    if (wrapper && !wrapper.contains(e.target)) {
        const list = document.getElementById(openId+'-list');
        if (list) list.classList.add('hidden');
        window._searchableOpenId = null;
    }
});
</script>
@endpush
@endonce
