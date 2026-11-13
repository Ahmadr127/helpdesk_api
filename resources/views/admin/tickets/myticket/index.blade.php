@extends('admin.layouts.app')

@section('title', 'Tiket Saya')

@section('content')
<div class="w-full">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Tiket Saya</h1>
            <p class="text-sm text-gray-500">Tiket yang Anda ajukan sebagai pengguna</p>
        </div>
        @if(auth()->user()->hasPermission('myticket'))
        <a href="{{ route('admin.tickets.myticket.create') }}"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Buat Tiket
        </a>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
        <p class="text-sm text-gray-600">
            Filter melekat pada tabel di bawah — gunakan kolom tabel untuk menyaring
            (nomor tiket, pemohon, kategori, departemen, deskripsi, status, tanggal dibuat).
        </p>
    </div>

    <div class="bg-white rounded-lg shadow-md border border-gray-100 overflow-hidden">
        @include('admin.tickets.partials.ticket-table', [
            'tickets' => $tickets,
            'myticketActions' => true,
            'filter' => $filter,
            'kategoris' => $kategoris,
            'departments' => $departments,
            'filterAction' => route('admin.tickets.myticket'),
        ])
    </div>

    @include('admin.tickets.myticket.component.modal-view')
    @include('admin.tickets.myticket.component.delete-confirm')

    <div class="mt-6">
        {{ $tickets->links() }}
    </div>
</div>
@endsection
