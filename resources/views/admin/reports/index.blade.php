@extends('admin.layouts.app')

@section('title', 'Report Umum')

@section('content')
<div class="w-full mx-auto">
    @include('reports._report', [
        'pengajuans' => $pengajuans,
        'summaryCards' => $summaryCards,
        'columns' => $columns,
        'route' => $route,
        'detailRoute' => $detailRoute,
        'exportRoute' => $exportRoute,
        'exportAction' => $exportAction,
        'kategoris' => $kategoris,
        'jenisList' => $jenisList,
        'departments' => $departments,
        'years' => $years,
        'showJenis' => $showJenis,
        'showDepartment' => $showDepartment,
        'showYear' => $showYear,
        'selectable' => $selectable,
        'defaultJenis' => $defaultJenis,
        'compactFilter' => true,
    ])
</div>
@endsection

@push('scripts')
    @include('reports._report-scripts', ['exportRoute' => $exportRoute])
@endpush
