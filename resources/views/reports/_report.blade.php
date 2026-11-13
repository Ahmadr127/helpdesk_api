{{--
    Partial report pengajuan (dipakai bersama shell admin & user).

    Parameter:
    - $pengajuans      (wajib) LengthAwarePaginator App\Models\Pengajuan
    - $summary         (opsional) array hasil ReportService::summary() → kartu default
    - $summaryCards    (opsional) override array kartu: [label, value, sub?, color?]
    - $columns         (opsional) array kolom; default lengkap
    - $route           (opsional) URL form filter; default url()->current()
    - $detailRoute     (opsional) nama route detail; jika diisi kolom Aksi muncul
    - $exportUrl       (opsional) href export (GET)
    - $exportAction    (opsional) nama fungsi JS yang menangani export (mis. exportSirs)
    - $showJenis / $showKategori / $showDepartment / $showYear / $selectable (bool, opsional)
    - $kategoris, $jenisList, $departments, $years (opsional) opsi dropdown
--}}
@php
    $pengajuans = $pengajuans ?? null;

    $columns = $columns ?? ['nomor', 'tanggal', 'pemohon', 'jenis', 'kategori', 'department', 'prioritas', 'status', 'pj', 'closed_at', 'aksi'];
    $statuses = \App\Services\Report\ReportService::STATUSES;
    $prioritasList = \App\Services\Report\ReportService::PRIORITAS;
    $perPageOptions = \App\Services\Report\ReportService::PER_PAGE_OPTIONS;

    $kategoris = $kategoris ?? collect();
    $jenisList = $jenisList ?? collect();
    $departments = $departments ?? collect();
    $years = $years ?? null;

    $showJenis = $showJenis ?? true;
    $showKategori = $showKategori ?? true;
    $showDepartment = $showDepartment ?? false;
    $showYear = $showYear ?? false;
    $selectable = $selectable ?? false;
    $compactFilter = $compactFilter ?? false;
    $defaultJenis = $defaultJenis ?? null;
    // _filter=1 menandakan form di-submit; tanpa itu gunakan default jenis halaman.
    // Tidak ada checkbox yang dicentang = semua.
    $submittedFilter = request()->filled('_filter');
    $rawJenis = $submittedFilter ? (array) request()->input('jenis', []) : [$defaultJenis];
    $currentJenis = array_values(array_filter(array_map('strval', array_map('trim', $rawJenis))));
    $currentKategori = array_values(array_filter(array_map('intval', (array) request()->input('kategori_id', []))));
    $fieldClass = 'h-9 rounded-md border border-gray-300 bg-white shadow-sm py-1 px-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500';
    $tableFixed = $tableFixed ?? false;
    $detailRoute = $detailRoute ?? null;
    $exportUrl = $exportUrl ?? null;
    $exportAction = $exportAction ?? null;
    $route = $route ?? url()->current();

    // Kartu ringkasan: gunakan $summaryCards bila diberikan, selainnya bangun dari $summary.
    // Definisi status (selaras dengan dashboard & ringkasan mobile):
    // - aktif    = open + in_progress (sedang dikerjakan)
    // - menunggu = ditutup (menunggu konfirmasi pemohon)
    // - selesai  = confirmed + rejected (sudah final)
    $summaryCards = $summaryCards ?? null;
    if (! $summaryCards && ! empty($summary)) {
        $summaryCards = [
            [
                'label' => 'Total Pengajuan',
                'value' => number_format($summary['total'], 0, ',', '.'),
                'sub' => $summary['active'].' aktif · '.$summary['pending'].' menunggu · '.$summary['completion_rate'].'% selesai',
                'color' => 'blue',
            ],
            [
                'label' => 'Sedang Diproses',
                'value' => number_format($summary['active'], 0, ',', '.'),
                'sub' => $summary['open'].' dibuka · '.$summary['in_progress'].' proses',
                'color' => 'yellow',
            ],
            [
                'label' => 'Menunggu Konfirmasi',
                'value' => number_format($summary['pending'], 0, ',', '.'),
                'sub' => 'Menunggu konfirmasi pemohon',
                'color' => 'orange',
            ],
            [
                'label' => 'Selesai',
                'value' => number_format($summary['done'], 0, ',', '.'),
                'sub' => $summary['confirmed'].' dikonfirmasi · '.$summary['rejected'].' ditolak',
                'color' => 'green',
            ],
            [
                'label' => 'Ditolak',
                'value' => number_format($summary['rejected'], 0, ',', '.'),
                'sub' => 'Permintaan ditolak',
                'color' => 'red',
            ],
        ];
    }

    $cardBorder = function (?string $color) {
        return match ($color ?? 'blue') {
            'blue' => 'border-blue-500',
            'green' => 'border-green-500',
            'yellow' => 'border-yellow-500',
            'orange' => 'border-orange-500',
            'red' => 'border-red-500',
            'purple' => 'border-purple-500',
            'gray' => 'border-gray-500',
            default => 'border-blue-500',
        };
    };

    $statusBadge = function (?string $status) {
        return match ($status) {
            'open' => 'bg-blue-100 text-blue-800',
            'in_progress' => 'bg-yellow-100 text-yellow-800',
            'pending' => 'bg-orange-100 text-orange-800',
            'ditutup' => 'bg-gray-100 text-gray-800',
            'confirmed' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    };

    $prioritasBadge = function (?string $prioritas) {
        return match ($prioritas) {
            'low' => 'bg-gray-100 text-gray-800',
            'medium' => 'bg-yellow-100 text-yellow-800',
            'high' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    };

    $labels = [
        'nomor' => 'Nomor',
        'tanggal' => 'Tanggal',
        'created_at' => 'Tanggal Dibuat',
        'pemohon' => 'Pemohon',
        'jenis' => 'Jenis',
        'kategori' => 'Kategori',
        'department' => 'Departemen',
        'lokasi' => 'Lokasi',
        'prioritas' => 'Prioritas',
        'status' => 'Status',
        'pj' => 'Penanggung Jawab',
        'response_time' => 'Waktu Respond',
        'waktu_proses' => 'Waktu Proses',
        'closed_at' => 'Tanggal Selesai',
        'kinerja' => 'Status Kinerja',
        'aksi' => 'Aksi',
    ];

    $formatDuration = function ($from, $to) {
        if (! $from || ! $to) {
            return '-';
        }

        $duration = $from->diff($to);
        $parts = [];

        if ($duration->days > 0) {
            $parts[] = $duration->days.' hari';
        }
        if ($duration->h > 0) {
            $parts[] = $duration->h.' jam';
        }
        if ($duration->i > 0) {
            $parts[] = $duration->i.' menit';
        }

        return $parts === [] ? '0 menit' : implode(', ', $parts);
    };

    $sortable = ['nomor', 'tanggal', 'prioritas', 'status', 'created_at', 'closed_at'];
    $sort = (string) request('sort', 'tanggal');
    $direction = strtolower((string) request('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

    $visibleColumns = array_values(array_filter($columns, function ($column) use ($detailRoute) {
        return $column !== 'aksi' || $detailRoute;
    }));
    $columnCount = count($visibleColumns) + ($selectable ? 1 : 0);

    $sortUrl = function (string $column) use ($sort, $direction) {
        $next = ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';

        return request()->fullUrlWithQuery(['sort' => $column, 'direction' => $next, 'page' => 1]);
    };

    $activeFilters = collect(['q', 'status', 'kategori_id', 'jenis', 'department_id', 'prioritas', 'date_from', 'date_to', 'year'])
        ->filter(fn ($key) => filled(request($key)))
        ->count();
@endphp

<div class="space-y-6">
    {{-- Ringkasan --}}
    @if(! empty($summaryCards))
        <div class="grid {{ count($summaryCards) >= 5 ? 'grid-cols-2 md:grid-cols-3 xl:grid-cols-5' : 'grid-cols-2 lg:grid-cols-4' }} gap-4">
            @foreach($summaryCards as $card)
                <div class="bg-white rounded-lg shadow-md p-5 border-l-4 {{ $cardBorder($card['color'] ?? null) }}">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $card['label'] }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-800">{{ $card['value'] }}</p>
                    @if(! empty($card['sub']))
                        <p class="text-xs text-gray-400 mt-1">{{ $card['sub'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Filter & Tabel --}}
    @if($compactFilter)
        {{-- Filter satu baris, menyatu dengan tabel --}}
        <div class="bg-white rounded-lg shadow-md border border-gray-100 overflow-hidden">
            <form action="{{ $route }}" method="GET"
                class="flex flex-wrap items-center gap-2 px-3 py-3 border-b border-gray-100 bg-gray-50">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <input type="hidden" name="_filter" value="1">

                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari tiket / pemohon..."
                    aria-label="Pencarian" class="{{ $fieldClass }} flex-1 min-w-[180px]">

                @include('reports._filter-dropdowns')

                <select name="status" aria-label="Status" class="{{ $fieldClass }}">
                    <option value="">Semua Status</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                @if($showYear && ! empty($years))
                    <select name="year" aria-label="Tahun" class="{{ $fieldClass }}">
                        <option value="">Tahun</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ (string) request('year') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                @endif

                <select name="prioritas" aria-label="Prioritas" class="{{ $fieldClass }}">
                    <option value="">Prioritas</option>
                    @foreach($prioritasList as $value => $label)
                        <option value="{{ $value }}" {{ request('prioritas') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <input type="date" name="date_from" value="{{ request('date_from') }}" aria-label="Dari tanggal" class="{{ $fieldClass }} w-[9.25rem]">
                <input type="date" name="date_to" value="{{ request('date_to') }}" aria-label="Sampai tanggal" class="{{ $fieldClass }} w-[9.25rem]">

                <select name="per_page" aria-label="Baris per halaman" onchange="this.form.submit()" class="{{ $fieldClass }} w-[7rem]">
                    @foreach($perPageOptions as $option)
                        <option value="{{ $option }}" {{ (int) request('per_page', 10) === $option ? 'selected' : '' }}>
                            {{ $option }} baris
                        </option>
                    @endforeach
                </select>

                <button type="submit"
                    class="inline-flex items-center px-4 h-9 bg-blue-600 text-white text-sm font-medium rounded-md shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter
                </button>

                @if($activeFilters > 0)
                    <a href="{{ $route }}"
                        class="inline-flex items-center px-3 h-9 bg-gray-100 text-gray-600 text-sm font-medium rounded-md hover:bg-gray-200 transition-all"
                        title="Reset filter">Reset</a>
                @endif

                @if($exportUrl)
                    <a href="{{ $exportUrl }}"
                        class="inline-flex items-center px-4 h-9 bg-green-600 text-white text-sm font-medium rounded-md shadow hover:bg-green-700 transition-all">
                        Export CSV
                    </a>
                @elseif($exportAction)
                    <button type="button" onclick="{{ $exportAction }}()"
                        class="inline-flex items-center px-4 h-9 bg-green-600 text-white text-sm font-medium rounded-md shadow hover:bg-green-700 transition-all">
                        Export CSV
                    </button>
                @endif

            </form>

            @include('reports._table')
        </div>
    @else
    {{-- Filter --}}
    <div class="bg-white rounded-lg shadow-md border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-800">Filter Report</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $activeFilters > 0 ? $activeFilters.' filter aktif' : 'Belum ada filter aktif' }}
                </p>
            </div>
            @if($activeFilters > 0)
                <a href="{{ $route }}" class="text-sm text-gray-500 hover:text-gray-700 underline">Reset</a>
            @endif
        </div>

        <form action="{{ $route }}" method="GET" class="p-6">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
            <input type="hidden" name="_filter" value="1">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label for="filter-q" class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
                    <input type="text" id="filter-q" name="q" value="{{ request('q') }}"
                        placeholder="Nomor, nama pemohon, atau deskripsi..."
                        class="w-full rounded-md border border-gray-300 bg-white shadow-sm py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="filter-status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="filter-status" name="status"
                        class="w-full rounded-md border border-gray-300 bg-white shadow-sm py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua Status</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-prioritas" class="block text-sm font-medium text-gray-700 mb-1">Prioritas</label>
                    <select id="filter-prioritas" name="prioritas"
                        class="w-full rounded-md border border-gray-300 bg-white shadow-sm py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua Prioritas</option>
                        @foreach($prioritasList as $value => $label)
                            <option value="{{ $value }}" {{ request('prioritas') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-4 flex items-center">
                    @include('reports._filter-dropdowns')
                </div>

                @if($showDepartment && $departments->isNotEmpty())
                    <div>
                        <label for="filter-department" class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
                        <select id="filter-department" name="department_id"
                            class="w-full rounded-md border border-gray-300 bg-white shadow-sm py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua Departemen</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ (string) request('department_id') === (string) $department->id ? 'selected' : '' }}>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if($showYear && ! empty($years))
                    <div>
                        <label for="filter-year" class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                        <select id="filter-year" name="year"
                            class="w-full rounded-md border border-gray-300 bg-white shadow-sm py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua Tahun</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ (string) request('year') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label for="filter-date-from" class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                    <input type="date" id="filter-date-from" name="date_from" value="{{ request('date_from') }}"
                        class="w-full rounded-md border border-gray-300 bg-white shadow-sm py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="filter-date-to" class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" id="filter-date-to" name="date_to" value="{{ request('date_to') }}"
                        class="w-full rounded-md border border-gray-300 bg-white shadow-sm py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="filter-per-page" class="block text-sm font-medium text-gray-700 mb-1">Baris / Halaman</label>
                    <select id="filter-per-page" name="per_page" onchange="this.form.submit()"
                        class="w-full rounded-md border border-gray-300 bg-white shadow-sm py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}" {{ (int) request('per_page', 10) === $option ? 'selected' : '' }}>
                                {{ $option }} baris
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Terapkan Filter
                </button>

                <a href="{{ $route }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 transition-all">
                    Reset
                </a>

                @if($exportUrl)
                    <a href="{{ $exportUrl }}"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md shadow hover:bg-green-700 transition-all ml-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export CSV
                    </a>
                @elseif($exportAction)
                    <button type="button" onclick="{{ $exportAction }}()"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md shadow hover:bg-green-700 transition-all ml-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export CSV
                    </button>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-lg shadow-md border border-gray-100 overflow-hidden">
        @include('reports._table')
    </div>
    @endif
</div>