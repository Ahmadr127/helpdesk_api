{{--
    Baris filter per kolom tabel pengajuan (reusable).

    Satu baris <tr> di dalam <thead> tabel: setiap kolom tabel punya kontrol
    filter sendiri (teks / select / rentang tanggal). Semua kontrol berada di
    dalam form GET milik tabel, jadi cukup satu klik "Terapkan".

    Parameter (@include):
    - $filter        (opsional) App\Services\Pengajuan\PengajuanListFilter
    - $kategoris     (opsional) koleksi kategori
    - $departments   (opsional) koleksi departemen
    - $lockedStatus  (opsional) status yang dikunci halaman
    - $filterAction  (opsional) URL reset filter
--}}
@php
    $filter = $filter ?? null;
    $kategoris = $kategoris ?? collect();
    $departments = $departments ?? collect();
    $lockedStatus = $lockedStatus ?? null;
    $filterAction = $filterAction ?? url()->current();

    $filterValue = function (string $key) use ($filter) {
        return $filter ? $filter->value(request(), $key) : request($key);
    };

    $cellClass = 'w-full rounded-md border border-gray-300 bg-white px-2 py-1 text-xs text-gray-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
@endphp

<tr class="bg-slate-100/60">
    {{-- No Tiket --}}
    <td class="px-4 py-2">
        <input type="text" name="nomor" value="{{ $filterValue('nomor') }}" placeholder="No Tiket"
            aria-label="Filter No Tiket" class="{{ $cellClass }}">
    </td>

    {{-- Pengguna --}}
    <td class="px-4 py-2">
        <input type="text" name="pemohon" value="{{ $filterValue('pemohon') }}" placeholder="Nama pemohon"
            aria-label="Filter Pengguna" class="{{ $cellClass }}">
    </td>

    {{-- Kategori --}}
    <td class="px-4 py-2">
        <select name="kategori_id" aria-label="Filter Kategori" class="{{ $cellClass }}" data-filter-auto>
            <option value="">Semua</option>
            @foreach($kategoris as $kategori)
                <option value="{{ $kategori->id }}"
                    @selected((string) $filterValue('kategori_id') === (string) $kategori->id)>{{ $kategori->name }}</option>
            @endforeach
        </select>
    </td>

    {{-- Departemen --}}
    <td class="px-4 py-2">
        <select name="department_id" aria-label="Filter Departemen" class="{{ $cellClass }}" data-filter-auto>
            <option value="">Semua</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}"
                    @selected((string) $filterValue('department_id') === (string) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </td>

    {{-- Deskripsi --}}
    <td class="px-4 py-2">
        <input type="text" name="deskripsi" value="{{ $filterValue('deskripsi') }}" placeholder="Kata kunci deskripsi"
            aria-label="Filter Deskripsi" class="{{ $cellClass }}">
    </td>

    {{-- Status --}}
    <td class="px-4 py-2">
        @if($lockedStatus)
            <select aria-label="Status" class="{{ $cellClass }} bg-gray-50 text-gray-500" disabled>
                <option selected>{{ \App\Support\TicketStatus::label($lockedStatus) }}</option>
            </select>
        @else
            <select name="status" aria-label="Filter Status" class="{{ $cellClass }}" data-filter-auto>
                <option value="">Semua</option>
                @foreach(\App\Support\TicketStatus::options() as $value => $label)
                    <option value="{{ $value }}"
                        @selected(\App\Support\TicketStatus::normalize(request('status')) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        @endif
    </td>

    {{-- Tanggal Dibuat --}}
    <td class="px-4 py-2">
        <div class="flex items-center gap-1">
            <input type="date" name="date_from" value="{{ $filterValue('date_from') }}" aria-label="Dari tanggal"
                class="{{ $cellClass }}" data-filter-auto>
            <span class="text-xs text-gray-400">s/d</span>
            <input type="date" name="date_to" value="{{ $filterValue('date_to') }}" aria-label="Sampai tanggal"
                class="{{ $cellClass }}" data-filter-auto>
        </div>
    </td>

    {{-- Aksi: terapkan / reset --}}
    <td class="px-4 py-2 whitespace-nowrap">
        <button type="submit"
            class="inline-flex h-8 items-center rounded-md bg-blue-600 px-3 text-xs font-medium text-white shadow-sm hover:bg-blue-700">
            Terapkan
        </button>
        <a href="{{ $filterAction }}"
            class="ml-1 inline-flex h-8 items-center rounded-md bg-white px-3 text-xs font-medium text-gray-600 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            Reset
        </a>
    </td>
</tr>
