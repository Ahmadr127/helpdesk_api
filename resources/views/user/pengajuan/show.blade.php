@extends('user.layouts.app')
@section('content')
<div class="container">
    <h2>{{ $pengajuan->nomor }}</h2>
    <p><strong>Status:</strong> {{ $pengajuan->status }}</p>
    <p><strong>Kategori:</strong> {{ $pengajuan->kategori?->name }}</p>
    <p><strong>Deskripsi:</strong> {{ $pengajuan->deskripsi }}</p>
    <p><strong>Prioritas:</strong> {{ $pengajuan->prioritas }}</p>

    <h4>Penanggung Jawab</h4>
    <ul>
        @forelse($activePj as $pj)
            <li>{{ $pj->user?->name ?? '-' }}</li>
        @empty
            <li>Belum ada PJ.</li>
        @endforelse
    </ul>

    @can('update', $pengajuan)
    <form method="POST" action="{{ route('user.pengajuan.assign', $pengajuan) }}">
        @csrf
        <div class="form-group">
            <label for="user_ids">Pilih PJ</label>
            <select name="user_ids[]" id="user_ids" multiple class="form-control">
                @foreach($candidates as $candidate)
                    <option value="{{ $candidate->id }}" @selected($activePj->pluck('user_id')->contains($candidate->id))>
                        {{ $candidate->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Simpan PJ</button>
    </form>
    @endcan
    <h4>Histori</h4>
    <ul>
        @foreach($pengajuan->historis as $h)
            <li>{{ $h->created_at }} - {{ $h->action }}: {{ $h->notes }}</li>
        @endforeach
    </ul>
    <a href="{{ route('user.pengajuan.index') }}" class="btn btn-secondary">Kembali</a>
</div>
@endsection