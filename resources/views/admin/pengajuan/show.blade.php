@extends('admin.layouts.app')

@php
    $isOrder = ($pengajuan->kategori?->jenis?->code ?? null) === 'IPSRS';
    $title = ($isOrder ? 'Detail Order Perbaikan' : 'Detail Tiket').' #'.$pengajuan->nomor;
@endphp

@section('title', $title)

@section('content')
    @include('user.pengajuan._detail', [
        'pengajuan' => $pengajuan,
        'backUrl' => url()->previous(route('admin.reports.index')),
        'backLabel' => 'Kembali',
        'titleLabel' => $isOrder ? 'Detail Order Perbaikan' : 'Detail Tiket',
        'accent' => $isOrder ? 'green' : 'blue',
    ])
@endsection
