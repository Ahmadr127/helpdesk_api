@extends('user.layouts.app')

@section('title', 'Detail Tiket')

@section('content')
@include('user.partials.notification')
@include('user.pengajuan._detail', [
    'pengajuan' => $ticket,
    'accent' => 'blue',
    'backUrl' => route('user.ticket.index'),
    'backLabel' => 'Kembali Ke Daftar Tiket',
    'titleLabel' => 'Detail Tiket',
    'editUrl' => route('user.ticket.edit', $ticket),
    'deleteUrl' => route('user.ticket.destroy', $ticket),
    'replyUrl' => route('user.ticket.reply', $ticket),
    'konfirmasiUrl' => route('user.ticket.confirm', $ticket),
    'konfirmasiFields' => 'ticket',
])
@endsection
