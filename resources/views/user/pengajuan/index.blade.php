@extends('user.layouts.app')

@section('title', 'Pengajuan')

@section('content')
<div class="w-full px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Pengajuan</h2>
            <p class="text-sm text-gray-600 mt-1">
                Daftar pengajuan yang Anda buat atau yang menjadi tanggung jawab Anda.
            </p>
        </div>
        <a href="{{ route('user.pengajuan.create') }}"
            class="inline-flex items-center justify-center px-4 py-2 bg-green-700 hover:bg-green-800 text-white text-sm font-semibold rounded-lg shadow transition duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Buat Pengajuan
        </a>
    </div>

    @include('reports._report', [
        'pengajuans' => $pengajuans,
        'summary' => $summary,
        'columns' => $columns,
        'kategoris' => $kategoris,
        'jenisList' => $jenisList,
        'departments' => $departments,
        'route' => $route,
        'detailRoute' => $detailRoute,
        'exportUrl' => $exportUrl,
        'showJenis' => $showJenis,
        'showDepartment' => $showDepartment,
        'showYear' => $showYear,
        'selectable' => $selectable,
    ])
</div>
@endsection
