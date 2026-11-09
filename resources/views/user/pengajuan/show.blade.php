@extends('user.layouts.app')
@section('title', 'Detail Pengajuan')
@section('content')
@include('user.partials.notification')
@include('user.pengajuan._detail', [
    'pengajuan' => $pengajuan,
    'accent' => 'blue',
    'backUrl' => route('user.pengajuan.index'),
    'backLabel' => 'Kembali ke Daftar Pengajuan',
    'titleLabel' => 'Detail Pengajuan',
    'editUrl' => route('user.pengajuan.edit', $pengajuan),
    'deleteUrl' => route('user.pengajuan.destroy', $pengajuan),
    'replyUrl' => route('user.pengajuan.message', $pengajuan),
    'konfirmasiUrl' => route('user.pengajuan.confirm', $pengajuan),
    'konfirmasiFields' => (($pengajuan->kategori?->jenis?->code ?? null) === 'IPSRS') ? 'order' : 'ticket',
    'candidates' => $candidates ?? collect(),
    'activePj' => $activePj ?? collect(),
    'canAssign' => true,
    'assignUrl' => route('user.pengajuan.assign', $pengajuan),
])
@endsection
