@extends('admin.layouts.app')

@section('title', 'Order Saya')

@section('content')
<div class="w-full">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Order Saya</h1>
            <p class="text-sm text-gray-500">Order perbaikan yang Anda ajukan sebagai pengguna</p>
        </div>
        @if(auth()->user()->hasPermission('myorder'))
        <a href="{{ route('admin.order-perbaikan.myorder.create') }}"
            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            Buat Order
        </a>
        @endif
    </div>

    @include('admin.order-perbaikan._table', [
        'orders' => $orders,
        'filter' => $filter ?? null,
        'filterAction' => route('admin.order-perbaikan.myorder'),
        'title' => 'Order Saya',
        'kategoris' => $kategoris ?? collect(),
        'locations' => $locations ?? collect(),
        'detailRoute' => 'admin.order-perbaikan.myorder.show',
        'myorderActions' => true,
        'emptyTitle' => 'Belum ada order',
        'emptySubtitle' => 'Ajukan order perbaikan baru',
    ])

    @include('admin.order-perbaikan.myorder.component.modal-view')
    @include('admin.order-perbaikan.myorder.component.delete-confirm')
</div>
@endsection
