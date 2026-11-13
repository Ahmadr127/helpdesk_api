{{--
    Tabel daftar Order Perbaikan — dipakai bareng oleh semua halaman tabel:

    - admin.order-perbaikan.index        (showLokasi + showStatus)
    - admin.order-perbaikan.total        (semua kolom ringkasan)
    - admin.order-perbaikan.in-progress  (+ aksi Tutup)
    - admin.order-perbaikan.confirmed    (checkbox ekspor)
    - admin.order-perbaikan.rejected     (kolom ringkas)

    Filter kolom menempel di tabel ini (form GET + baris filter per kolom),
    sama seperti admin.tickets.partials.ticket-table.

    Tabel memakai `table-fixed w-full` + truncate pada setiap sel teks,
    jadi tidak butuh scroll horizontal — konten panjang dipotong dengan
    elipsis (teks lengkap tersedia di atribut title saat hover).

    Parameter (@include):
    - $orders             (wajib) paginator pengajuan (withQueryString)
    - $filter             (wajib) App\Services\Pengajuan\PengajuanListFilter
    - $filterAction       (wajib) URL action form GET & tombol Reset
    - $title              (opsional) judul kartu, default "Daftar Order Perbaikan"
    - $kategoris          (opsional) koleksi kategori dropdown filter
    - $locations          (opsional) koleksi lokasi dropdown filter
    - $lockedStatus       (opsional) status terkunci halaman (filter status disembunyikan)
    - $showLokasi         (bool) kolom Lokasi
    - $showKeluhan        (bool) kolom Keluhan
    - $showStatus         (bool) kolom Status
    - $showConfirmedAt    (bool) subtext "Konfirmasi" pada kolom Tanggal
    - $showPenanggungJawab (bool) kolom Penanggung Jawab (+ Mulai)
    - $showDurasi         (bool) kolom Durasi
    - $showCheckbox       (bool) kolom checkbox (id select-all / class order-checkbox)
    - $showComplete       (bool) aksi "Tutup" (POST complete)
    - $detailRoute        (opsional) nama route untuk tombol Detail, default 'admin.order-perbaikan.show'
    - $detailAsModal      (bool) tombol Detail membuka modal via openMyticketDetail(this) + ?modal=1 (halaman myorder)
    - $myorderActions     (bool) tampilkan Ubah/Hapus (hanya order status open) + Detail modal
    - $emptyTitle         (opsional) teks utama saat data kosong
    - $emptySubtitle      (opsional) teks kedua saat data kosong
--}}
@php
    $title = $title ?? 'Daftar Order Perbaikan';
    $showLokasi = $showLokasi ?? false;
    $showKeluhan = $showKeluhan ?? false;
    $showStatus = $showStatus ?? false;
    $showConfirmedAt = $showConfirmedAt ?? false;
    $showPenanggungJawab = $showPenanggungJawab ?? false;
    $showDurasi = $showDurasi ?? false;
    $showCheckbox = $showCheckbox ?? false;
    $showComplete = $showComplete ?? false;
    $detailRoute = $detailRoute ?? 'admin.order-perbaikan.show';
    $detailAsModal = $detailAsModal ?? false;
    $myorderActions = $myorderActions ?? false;
    $emptyTitle = $emptyTitle ?? 'Tidak ada order yang ditemukan';
    $emptySubtitle = $emptySubtitle ?? 'Coba ubah filter pencarian Anda';

    $colCount = 6 /* Nomor, Tanggal, Pengguna, Kategori, Prioritas, Aksi */
        + ($showCheckbox ? 1 : 0)
        + ($showLokasi ? 1 : 0)
        + ($showKeluhan ? 1 : 0)
        + ($showStatus ? 1 : 0)
        + ($showPenanggungJawab ? 1 : 0)
        + ($showDurasi ? 1 : 0);

    $filterValue = fn (string $key) => $filter->value(request(), $key) ?? request($key);

    $cellClass = 'w-full rounded-md border border-gray-300 bg-white px-2 py-1 text-xs text-gray-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';

    $prioritasMeta = function (?string $value): array {
        $p = strtoupper((string) $value);
        return match (true) {
            str_contains($p, 'TINGGI'), str_contains($p, 'HIGH') => ['Tinggi/Urgent', 'bg-red-50 text-red-800 border border-red-200'],
            str_contains($p, 'SEDANG'), str_contains($p, 'MEDIUM') => ['Sedang', 'bg-yellow-50 text-yellow-800 border border-yellow-200'],
            str_contains($p, 'RENDAH'), str_contains($p, 'LOW') => ['Rendah', 'bg-blue-50 text-blue-800 border border-blue-200'],
            default => [$value ?: '-', 'bg-gray-50 text-gray-800 border border-gray-200'],
        };
    };

    $statusMeta = function (?string $value): array {
        $s = \App\Support\TicketStatus::normalize($value) ?? $value;
        return match ($s) {
            'open' => ['Dibuka', 'bg-blue-50 text-blue-800 border border-blue-200'],
            'in_progress' => ['Dalam Diproses', 'bg-yellow-50 text-yellow-800 border border-yellow-200'],
            'ditutup' => ['Tutup', 'bg-violet-50 text-violet-800 border border-violet-200'],
            'confirmed' => ['Dikonfirmasi', 'bg-green-50 text-green-800 border border-green-200'],
            'rejected' => ['Ditolak', 'bg-red-50 text-red-800 border border-red-200'],
            default => [$value ?: '-', 'bg-gray-50 text-gray-800 border border-gray-200'],
        };
    };
@endphp

<div class="bg-white rounded-xl shadow-md overflow-hidden mb-6 border border-gray-200">
    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center gap-2">
        <h3 class="text-base font-semibold text-gray-700 flex items-center min-w-0">
            <svg class="w-5 h-5 mr-2 text-gray-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                </path>
            </svg>
            <span class="truncate">{{ $title }}</span>
        </h3>
        <div class="text-xs sm:text-sm text-gray-500 whitespace-nowrap">
            Total: <span class="font-semibold">{{ $orders->total() }}</span> order
        </div>
    </div>

    <form method="GET" action="{{ $filterAction }}" data-pengajuan-filter-form>
        <div class="w-full">
            <table class="w-full table-fixed divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @if($showCheckbox)
                            <th scope="col" class="px-2 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">
                                <span class="sr-only">Pilih</span>
                            </th>
                        @endif
                        <th scope="col" class="px-2 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nomor Order</th>
                        <th scope="col" class="px-2 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th scope="col" class="px-2 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                        <th scope="col" class="px-2 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                        @if($showLokasi)
                            <th scope="col" class="px-2 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                        @endif
                        @if($showKeluhan)
                            <th scope="col" class="px-2 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keluhan</th>
                        @endif
                        <th scope="col" class="px-2 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prioritas</th>
                        @if($showStatus)
                            <th scope="col" class="px-2 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        @endif
                        @if($showPenanggungJawab)
                            <th scope="col" class="px-2 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penanggung Jawab</th>
                        @endif
                        @if($showDurasi)
                            <th scope="col" class="px-2 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durasi</th>
                        @endif
                        <th scope="col" class="px-2 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                    <tr class="bg-slate-100/60">
                        @if($showCheckbox)
                            <td class="px-2 py-2"></td>
                        @endif
                        <td class="px-2 py-2">
                            <input type="text" name="nomor" value="{{ $filterValue('nomor') }}" placeholder="Nomor order"
                                aria-label="Filter Nomor Order" class="{{ $cellClass }}">
                        </td>
                        <td class="px-2 py-2">
                            <div class="flex flex-col gap-1">
                                <input type="date" name="date_from" value="{{ $filterValue('date_from') }}" aria-label="Dari tanggal"
                                    class="{{ $cellClass }}" data-filter-auto>
                                <input type="date" name="date_to" value="{{ $filterValue('date_to') }}" aria-label="Sampai tanggal"
                                    class="{{ $cellClass }}" data-filter-auto>
                            </div>
                        </td>
                        <td class="px-2 py-2">
                            <input type="text" name="pemohon" value="{{ $filterValue('pemohon') }}" placeholder="Nama pengguna"
                                aria-label="Filter Pengguna" class="{{ $cellClass }}">
                        </td>
                        <td class="px-2 py-2">
                            <select name="kategori_id" aria-label="Filter Kategori" class="{{ $cellClass }}" data-filter-auto>
                                <option value="">Semua</option>
                                @foreach(($kategoris ?? collect()) as $kategori)
                                    <option value="{{ $kategori->id }}" @selected((string) $filterValue('kategori_id') === (string) $kategori->id)>{{ $kategori->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        @if($showLokasi)
                            <td class="px-2 py-2">
                                <select name="location_id" aria-label="Filter Lokasi" class="{{ $cellClass }}" data-filter-auto>
                                    <option value="">Semua</option>
                                    @foreach(($locations ?? collect()) as $location)
                                        <option value="{{ $location->id }}" @selected((string) $filterValue('location_id') === (string) $location->id)>{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                        @endif
                        @if($showKeluhan)
                            <td class="px-2 py-2">
                                <input type="text" name="deskripsi" value="{{ $filterValue('deskripsi') }}" placeholder="Keluhan"
                                    aria-label="Filter Keluhan" class="{{ $cellClass }}">
                            </td>
                        @endif
                        <td class="px-2 py-2">
                            <select name="prioritas" aria-label="Filter Prioritas" class="{{ $cellClass }}" data-filter-auto>
                                <option value="">Semua</option>
                                <option value="high" @selected($filterValue('prioritas') === 'high')>Tinggi/Urgent</option>
                                <option value="medium" @selected($filterValue('prioritas') === 'medium')>Sedang</option>
                                <option value="low" @selected($filterValue('prioritas') === 'low')>Rendah</option>
                            </select>
                        </td>
                        @if($showStatus)
                            <td class="px-2 py-2">
                                @if(($lockedStatus ?? null) === null)
                                    <select name="status" aria-label="Filter Status" class="{{ $cellClass }}" data-filter-auto>
                                        <option value="">Semua</option>
                                        @foreach(\App\Support\TicketStatus::options() as $value => $label)
                                            <option value="{{ $value }}" @selected(\App\Support\TicketStatus::normalize(request('status')) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <span></span>
                                @endif
                            </td>
                        @endif
                        @if($showPenanggungJawab)
                            <td class="px-2 py-2"></td>
                        @endif
                        @if($showDurasi)
                            <td class="px-2 py-2"></td>
                        @endif
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="flex flex-col gap-1">
                                <button type="submit"
                                    class="inline-flex h-8 items-center justify-center rounded-md bg-blue-600 px-2 text-xs font-medium text-white shadow-sm hover:bg-blue-700">
                                    Terapkan
                                </button>
                                <a href="{{ $filterAction }}"
                                    class="inline-flex h-8 items-center justify-center rounded-md bg-white px-2 text-xs font-medium text-gray-600 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                    Reset
                                </a>
                            </div>
                        </td>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($orders as $order)
                    @php
                        [$priorityLabel, $priorityClass] = $prioritasMeta($order->prioritas);
                        $pemohonName = $order->pemohon_nama ?? $order->pemohon?->name ?? '-';
                        $pemohonEmail = $order->pemohon?->email ?? '-';
                        $tanggalOrder = $order->tanggal ?? $order->created_at;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        @if($showCheckbox)
                            <td class="px-2 py-2.5">
                                <input type="checkbox" value="{{ $order->id }}"
                                    class="order-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            </td>
                        @endif
                        <td class="px-2 py-2.5 text-sm font-medium text-gray-900">
                            <div class="truncate" title="{{ $order->nomor }}">{{ $order->nomor }}</div>
                        </td>
                        <td class="px-2 py-2.5 text-sm text-gray-500">
                            <div class="truncate">{{ $tanggalOrder->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-400 truncate">{{ $tanggalOrder->format('H:i') }}</div>
                            @if($showConfirmedAt)
                                <div class="text-xs text-gray-400 truncate" title="Dikonfirmasi {{ $order->updated_at->format('d/m/Y H:i') }}">Konf: {{ $order->updated_at->format('d/m/Y H:i') }}</div>
                            @endif
                        </td>
                        <td class="px-2 py-2.5 text-sm text-gray-500">
                            <div class="truncate" title="{{ $pemohonName }}">{{ $pemohonName }}</div>
                            <div class="text-xs text-gray-400 truncate" title="{{ $pemohonEmail }}">{{ $pemohonEmail }}</div>
                        </td>
                        <td class="px-2 py-2.5 text-sm text-gray-500">
                            <div class="truncate" title="{{ $order->kategori?->name }}">{{ $order->kategori?->name ?? '-' }}</div>
                        </td>
                        @if($showLokasi)
                            <td class="px-2 py-2.5 text-sm text-gray-500">
                                <div class="truncate" title="{{ $order->lokasi?->name }}">{{ $order->lokasi?->name ?? '-' }}</div>
                            </td>
                        @endif
                        @if($showKeluhan)
                            <td class="px-2 py-2.5 text-sm text-gray-500">
                                <div class="truncate" title="{{ $order->keluhan }}">{{ $order->keluhan }}</div>
                            </td>
                        @endif
                        <td class="px-2 py-2.5">
                            <span class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap {{ $priorityClass }}">
                                {{ $priorityLabel }}
                            </span>
                        </td>
                        @if($showStatus)
                            @php [$statusLabel, $statusClass] = $statusMeta($order->status); @endphp
                            <td class="px-2 py-2.5">
                                <span class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                        @endif
                        @if($showPenanggungJawab)
                            <td class="px-2 py-2.5 text-sm text-gray-500">
                                <div class="truncate" title="{{ $order->nama_penanggung_jawab }}">{{ $order->nama_penanggung_jawab ?? '-' }}</div>
                                @if($order->started_at)
                                    <div class="text-xs text-gray-400 truncate">Mulai: {{ $order->started_at->format('d/m/Y H:i') }}</div>
                                @endif
                            </td>
                        @endif
                        @if($showDurasi)
                            <td class="px-2 py-2.5 text-sm text-gray-500">
                                <div class="truncate">{{ $order->started_at ? $order->started_at->diffForHumans(null, true) : '-' }}</div>
                            </td>
                        @endif
                        <td class="px-2 py-2.5 text-sm font-medium">
                            <div class="flex flex-wrap items-center gap-1">
                                @if($myorderActions)
                                    <button type="button" onclick="openMyticketDetail(this)"
                                        data-url="{{ route($detailRoute, $order) }}?modal=1"
                                        class="text-blue-600 hover:text-blue-900 inline-flex items-center hover:bg-blue-50 px-2 py-1.5 rounded-lg transition-colors duration-150 whitespace-nowrap">
                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                            </path>
                                        </svg>
                                        <span class="hidden xl:inline">Detail</span>
                                    </button>
                                    @if($order->status === 'open')
                                        <a href="{{ route('admin.order-perbaikan.myorder.edit', $order) }}"
                                            class="text-amber-600 hover:text-amber-900 inline-flex items-center hover:bg-amber-50 px-2 py-1.5 rounded-lg transition-colors duration-150 whitespace-nowrap">
                                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                </path>
                                            </svg>
                                            <span class="hidden xl:inline">Ubah</span>
                                        </a>
                                        <button type="button" data-delete-url="{{ route('admin.order-perbaikan.myorder.destroy', $order) }}"
                                            onclick="openMyticketDelete(this)"
                                            class="text-red-600 hover:text-red-900 inline-flex items-center hover:bg-red-50 px-2 py-1.5 rounded-lg transition-colors duration-150 whitespace-nowrap">
                                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                            <span class="hidden xl:inline">Hapus</span>
                                        </button>
                                    @endif
                                @elseif($detailAsModal)
                                    <button type="button" onclick="openMyticketDetail(this)"
                                        data-url="{{ route($detailRoute, $order) }}?modal=1"
                                        class="text-blue-600 hover:text-blue-900 inline-flex items-center hover:bg-blue-50 px-2 py-1.5 rounded-lg transition-colors duration-150 whitespace-nowrap">
                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                            </path>
                                        </svg>
                                        <span class="hidden xl:inline">Detail</span>
                                    </button>
                                @else
                                    <a href="{{ route($detailRoute, $order) }}"
                                        class="text-blue-600 hover:text-blue-900 inline-flex items-center hover:bg-blue-50 px-2 py-1.5 rounded-lg transition-colors duration-150 whitespace-nowrap">
                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                            </path>
                                        </svg>
                                        <span class="hidden xl:inline">Detail</span>
                                    </a>
                                @endif
                                @if($showComplete)
                                    <form action="{{ route('admin.order-perbaikan.complete', $order->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="text-violet-600 hover:text-violet-900 inline-flex items-center hover:bg-violet-50 px-2 py-1.5 rounded-lg transition-colors duration-150 whitespace-nowrap">
                                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="hidden xl:inline">Tutup</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $colCount }}" class="px-6 py-10 text-center text-sm text-gray-500 bg-white">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                                    </path>
                                </svg>
                                <p class="font-medium">{{ $emptyTitle }}</p>
                                <p class="text-gray-400 mt-1">{{ $emptySubtitle }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <div class="px-4 py-3 border-t border-gray-200 bg-white">
        {{ $orders->links() }}
    </div>
</div>

@include('admin.tickets.partials.ticket-filter-scripts')
