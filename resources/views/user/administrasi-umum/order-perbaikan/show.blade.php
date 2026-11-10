@extends('user.layouts.app')

@section('title', 'Detail Order Perbaikan')

@section('content')
@include('user.partials.notification')
@include('user.pengajuan._detail', [
    'pengajuan' => $order,
    'accent' => 'green',
    'backUrl' => route('user.administrasi-umum.order-barang'),
    'backLabel' => 'Kembali ke Daftar',
    'titleLabel' => 'Detail Order Perbaikan',
    'editUrl' => route('user.administrasi-umum.order-perbaikan.edit', $order),
    'deleteUrl' => route('user.administrasi-umum.order-perbaikan.delete', $order),
    'replyUrl' => route('user.pengajuan.message', $order),
    'konfirmasiUrl' => route('user.administrasi-umum.order-perbaikan.konfirmasi-selesai', $order),
    'konfirmasiFields' => 'order',
    'showOrderDetail' => true,
    'candidates' => $candidates ?? collect(),
    'activePj' => $activePj ?? collect(),
    'canAssign' => $canAssign ?? false,
    'assignUrl' => route('user.pengajuan.assign', $order),
])
@endsection
