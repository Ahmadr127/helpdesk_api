@extends('user.layouts.app')
@section('content')
<div class="container">
    <h2>Edit Pengajuan {{ $pengajuan->nomor }}</h2>
    <form method="POST" action="{{ route('user.pengajuan.update', $pengajuan) }}">
        @csrf @method('PUT')
        <div class="mb-3">
            <label>Deskripsi</label>
            <textarea name="deskripsi" class="form-control" required>{{ $pengajuan->deskripsi }}</textarea>
        </div>
        <div class="mb-3">
            <label>Prioritas</label>
            <select name="prioritas" class="form-control">
                <option value="low" @selected($pengajuan->prioritas==='low')>Low</option>
                <option value="medium" @selected($pengajuan->prioritas==='medium')>Medium</option>
                <option value="high" @selected($pengajuan->prioritas==='high')>High</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
</div>
@endsection