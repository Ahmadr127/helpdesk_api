@extends('user.layouts.app')
@section('content')
<div class="container">
    <h2>{{ $pengajuan->nomor }}</h2>
    <p><strong>Status:</strong> {{ $pengajuan->status }}</p>
    <p><strong>Kategori:</strong> {{ $pengajuan->kategori?->name }}</p>
    <p><strong>Deskripsi:</strong> {{ $pengajuan->deskripsi }}</p>
    <p><strong>Prioritas:</strong> {{ $pengajuan->prioritas }}</p>
    <h4>Histori</h4>
    <ul>
        @foreach($pengajuan->historis as $h)
            <li>{{ $h->created_at }} - {{ $h->action }}: {{ $h->notes }}</li>
        @endforeach
    </ul>
    <a href="{{ route('user.pengajuan.index') }}" class="btn btn-secondary">Kembali</a>
</div>
@endsection