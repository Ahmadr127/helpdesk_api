@extends('user.layouts.app')
@section('title', 'Edit Pengajuan')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-green-400 px-6 py-4">
                <h2 class="text-2xl font-bold text-white">Edit Pengajuan {{ $pengajuan->nomor }}</h2>
            </div>
            <div class="p-6">
                @if($errors->any())
                    <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                        <ul class="list-disc list-inside text-sm">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @include('user.pengajuan._form', [
                    'action' => route('user.pengajuan.update', $pengajuan),
                    'method' => 'PUT',
                    'accent' => 'green',
                    'pengajuan' => $pengajuan,
                    'kategoris' => $kategoris,
                    'locations' => $locations,
                    'buildings' => $buildings,

                    'userDepartment' => $userDepartment,
                    'submitLabel' => 'Simpan Perubahan',
                    'cancelUrl' => route('user.pengajuan.show', $pengajuan),
                    'showOrderFields' => ($pengajuan->kategori?->jenis?->code === 'IPSRS'),
                ])
            </div>
        </div>
    </div>
</div>
@endsection