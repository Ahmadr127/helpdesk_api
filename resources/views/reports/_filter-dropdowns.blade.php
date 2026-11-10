{{--
    Dropdown berisi checkbox untuk filter Jenis & Kategori.
    Variabel: $jenisList, $kategoris, $currentJenis (array), $currentKategori (array int).
--}}
@php
    $showJenis = $showJenis ?? true;
    $showKategori = $showKategori ?? true;
@endphp
<style>
.dropdown { position: relative; }
.dropdown summary { list-style: none; }
.dropdown summary::-webkit-details-marker { display: none; }
.dropdown .chevron { transition: transform .15s ease; }
.dropdown[open] .chevron { transform: rotate(180deg); }
</style>
<div class="flex flex-wrap items-center gap-2">
    @if($showJenis && $jenisList->isNotEmpty())
        <details class="dropdown">
            <summary class="inline-flex h-9 select-none cursor-pointer items-center gap-2 rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Jenis
                @if(count($currentJenis) > 0)
                    <span class="inline-flex items-center justify-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">
                        {{ count($currentJenis) }}
                    </span>
                @endif
                <svg class="chevron h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </summary>
            <div class="absolute z-30 mt-1 min-w-[13rem] overflow-hidden rounded-md border border-gray-200 bg-white shadow-lg">
                <div class="max-h-72 overflow-y-auto p-2">
                    @foreach($jenisList as $jenis)
                        <label class="flex cursor-pointer select-none items-center gap-2 rounded px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
                            <input type="checkbox" name="jenis[]" value="{{ $jenis->code }}"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                {{ in_array($jenis->code, $currentJenis, true) ? 'checked' : '' }}>
                            {{ $jenis->name }}
                        </label>
                    @endforeach
                </div>
            </div>
        </details>
    @endif

    @if($showKategori && $kategoris->isNotEmpty())
        <details class="dropdown">
            <summary class="inline-flex h-9 select-none cursor-pointer items-center gap-2 rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Kategori
                @if(count($currentKategori) > 0)
                    <span class="inline-flex items-center justify-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">
                        {{ count($currentKategori) }}
                    </span>
                @endif
                <svg class="chevron h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </summary>
            <div class="absolute z-30 mt-1 min-w-[16rem] overflow-hidden rounded-md border border-gray-200 bg-white shadow-lg">
                <div class="max-h-80 overflow-y-auto p-2">
                    @foreach($kategoris->groupBy(fn ($kategori) => $kategori->jenis?->name ?? 'Lainnya') as $group => $items)
                        <div class="px-2 pb-1 pt-2 text-xs font-semibold uppercase tracking-wider text-gray-400">
                            {{ $group }}
                        </div>
                        @foreach($items as $kategori)
                            <label class="flex cursor-pointer select-none items-center gap-2 rounded px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
                                <input type="checkbox" name="kategori_id[]" value="{{ $kategori->id }}"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    {{ in_array((int) $kategori->id, $currentKategori, true) ? 'checked' : '' }}>
                                {{ $kategori->name }}
                            </label>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </details>
    @endif
</div>
