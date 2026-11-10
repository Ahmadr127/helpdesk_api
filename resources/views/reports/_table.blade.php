{{--
    Partial tabel + pagination report pengajuan.
    Memakai variabel yang disiapkan oleh reports._report (visibleColumns, pengajuans, dll).
--}}
@php
    $tableFixed = $tableFixed ?? false;
@endphp
<style>
.report-table.table-fixed th,
.report-table.table-fixed td {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
<div class="overflow-x-auto">
    <table class="report-table w-full divide-y divide-gray-200 {{ $tableFixed ? 'table-fixed' : '' }}">
        <thead class="bg-gray-50">
            <tr>
                @if($selectable)
                    <th scope="col" class="px-4 py-3 text-left">
                        <input type="checkbox" id="selectAll"
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    </th>
                @endif
                @foreach($visibleColumns as $column)
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">
                        @if(in_array($column, $sortable, true))
                            <a href="{{ $sortUrl($column) }}" class="inline-flex items-center hover:text-blue-600">
                                {{ $labels[$column] ?? $column }}
                                <span class="ml-1 text-gray-400">
                                    {{ $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}
                                </span>
                            </a>
                        @else
                            {{ $labels[$column] ?? $column }}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($pengajuans as $pengajuan)
                <tr class="{{ $detailRoute ? 'cursor-pointer hover:bg-gray-100' : 'hover:bg-gray-50' }} transition-colors"
                    @if($detailRoute) onclick="window.location='{{ route($detailRoute, $pengajuan) }}'" @endif>
                    @if($selectable)
                        <td class="px-4 py-3 whitespace-nowrap" onclick="event.stopPropagation();">
                            <input type="checkbox" name="selected_ids[]" value="{{ $pengajuan->id }}"
                                class="report-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                onclick="event.stopPropagation();">
                        </td>
                    @endif
                    @foreach($visibleColumns as $column)
                        @switch($column)
                            @case('nomor')
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    @if($detailRoute)
                                        <a href="{{ route($detailRoute, $pengajuan) }}"
                                            class="font-medium text-blue-600 hover:text-blue-800">
                                            {{ $pengajuan->nomor }}
                                        </a>
                                    @else
                                        <span class="font-medium text-gray-900">{{ $pengajuan->nomor }}</span>
                                    @endif
                                </td>
                                @break

                            @case('tanggal')
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                    {{ ($pengajuan->tanggal ?? $pengajuan->created_at)?->format('d/m/Y H:i') ?? '-' }}
                                </td>
                                @break

                            @case('created_at')
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                    {{ $pengajuan->created_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>
                                @break

                            @case('pemohon')
                                <td class="px-4 py-3 text-sm text-gray-600 max-w-[14rem] truncate"
                                    title="{{ $pengajuan->pemohon_nama ?? $pengajuan->pemohon?->name ?? '-' }}">
                                    {{ $pengajuan->pemohon_nama ?? $pengajuan->pemohon?->name ?? '-' }}
                                </td>
                                @break

                            @case('jenis')
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 max-w-[12rem] truncate"
                                    title="{{ $pengajuan->kategori?->jenis?->name ?? '-' }}">
                                    {{ $pengajuan->kategori?->jenis?->name ?? '-' }}
                                </td>
                                @break

                            @case('kategori')
                                <td class="px-4 py-3 text-sm text-gray-600 max-w-[13rem] truncate"
                                    title="{{ $pengajuan->kategori?->name ?? '-' }}">
                                    {{ $pengajuan->kategori?->name ?? '-' }}
                                </td>
                                @break

                            @case('department')
                                <td class="px-4 py-3 text-sm text-gray-600 max-w-[12rem] truncate"
                                    title="{{ $pengajuan->department?->name ?? '-' }}">
                                    {{ $pengajuan->department?->name ?? '-' }}
                                </td>
                                @break

                            @case('lokasi')
                                <td class="px-4 py-3 text-sm text-gray-600 max-w-[12rem] truncate"
                                    title="{{ $pengajuan->lokasi?->name ?? $pengajuan->building?->name ?? '-' }}">
                                    {{ $pengajuan->lokasi?->name ?? $pengajuan->building?->name ?? '-' }}
                                </td>
                                @break

                            @case('prioritas')
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $prioritasBadge($pengajuan->prioritas) }}">
                                        {{ $prioritasList[$pengajuan->prioritas] ?? ($pengajuan->prioritas ?: '-') }}
                                    </span>
                                </td>
                                @break

                            @case('status')
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusBadge($pengajuan->status) }}">
                                        {{ $statuses[$pengajuan->status] ?? ($pengajuan->status ?: '-') }}
                                    </span>
                                </td>
                                @break

                            @case('pj')
                                <td class="px-4 py-3 text-sm text-gray-600 max-w-[14rem] truncate"
                                    title="{{ $pengajuan->nama_penanggung_jawab ?? '-' }}">
                                    {{ $pengajuan->nama_penanggung_jawab ?? '-' }}
                                </td>
                                @break

                            @case('response_time')
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                    @if($pengajuan->created_at && $pengajuan->started_at)
                                        {{ $formatDuration($pengajuan->created_at, $pengajuan->started_at) }}
                                    @elseif($pengajuan->created_at)
                                        <span class="text-gray-400">Belum direspon</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                @break

                            @case('closed_at')
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                    {{ $pengajuan->closed_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>
                                @break

                            @case('kinerja')
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    @if($pengajuan->created_at && $pengajuan->started_at)
                                        @if($pengajuan->created_at->diffInMinutes($pengajuan->started_at) <= \App\Services\Report\ReportService::RESPONSE_SLA_MINUTES)
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Response ≤ 60 Menit</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Response &gt; 60 Menit</span>
                                        @endif
                                    @elseif($pengajuan->created_at)
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Belum Direspon</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">-</span>
                                    @endif
                                </td>
                                @break

                            @case('aksi')
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <a href="{{ route($detailRoute, $pengajuan) }}"
                                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100">
                                        Detail
                                    </a>
                                </td>
                                @break
                        @endswitch
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max($columnCount, 1) }}" class="px-6 py-10 text-center text-sm text-gray-500">
                        <p class="font-medium text-gray-700">Tidak ada data ditemukan</p>
                        <p class="mt-1">Coba ubah kata kunci atau reset filter.</p>
                        @if($activeFilters > 0)
                            <a href="{{ $route }}" class="inline-block mt-3 text-blue-600 hover:text-blue-800 underline text-sm">
                                Reset filter
                            </a>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <p class="text-sm text-gray-600">
        Menampilkan
        <span class="font-medium">{{ $pengajuans->firstItem() ?? 0 }}</span>–<span class="font-medium">{{ $pengajuans->lastItem() ?? 0 }}</span>
        dari <span class="font-medium">{{ $pengajuans->total() }}</span> data
    </p>
    <div>
        {{ $pengajuans->onEachSide(1)->appends(request()->query())->links() }}
    </div>
</div>
