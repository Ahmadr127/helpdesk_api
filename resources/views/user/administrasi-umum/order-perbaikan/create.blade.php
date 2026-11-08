@extends('user.layouts.app')

@section('title', 'Buat Order Perbaikan')

@section('content')
<div class="max-w-7xl mx-auto py-6">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- Header - GREEN theme for Maintenance -->
        <div class="bg-green-600 p-5 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-white flex items-center">
                    Buat Order Perbaikan Baru
                </h1>
            </div>
            <a href="{{ route('user.administrasi-umum.order-barang') }}"
               class="inline-flex items-center px-4 py-2 bg-white text-green-600 font-medium rounded-lg hover:bg-green-50 transition-colors shadow">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali
            </a>
        </div>

        @if(session('error'))
            <div class="m-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="m-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="p-6">
            @include('user.pengajuan._form', [
                'action' => route('user.pengajuan.store'),
                'method' => 'POST',
                'accent' => 'green',
                'kategoris' => $kategoris,
                'locations' => $locations,
                'buildings' => $buildings,

                'user' => $user,
                'unitPengajuCode' => $unitPengajuCode,
                'unitPengajuName' => $unitPengajuName,
                'departmentId' => $departmentId,
                'departmentLocation' => $departmentLocation,
                'departmentBuilding' => $departmentBuilding,
                'userDepartment' => $userDepartment ?? null,
                'submitLabel' => 'Simpan Order',
                'cancelUrl' => route('user.administrasi-umum.order-barang'),
                'showOrderFields' => true,
            ])
        </div>
    </div>
</div>
@endsection