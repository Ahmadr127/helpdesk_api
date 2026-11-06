@extends('user.layouts.app')
@section('content')
<div class="container">
    <h2>Pengajuan</h2>
    <a href="{{ route('user.pengajuan.create') }}" class="btn btn-primary">Buat Pengajuan</a>
    <table class="table mt-3">
        <thead><tr><th>Nomor</th><th>Kategori</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        @foreach($pengajuans as $p)
            <tr>
                <td>{{ $p->nomor }}</td>
                <td>{{ $p->kategori?->name }}</td>
                <td>{{ $p->status }}</td>
                <td><a href="{{ route('user.pengajuan.show', $p) }}" class="btn btn-sm btn-info">Detail</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $pengajuans->links() }}
</div>
@endsection