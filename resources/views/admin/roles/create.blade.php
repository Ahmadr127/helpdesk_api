@extends('admin.layouts.app')

@section('title', 'Tambah Role')

@section('content')
<div class="w-full">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Tambah Role</h1>

        <form method="POST" action="{{ route('admin.roles.store') }}">
            @csrf
            @include('admin.roles._form')
        </form>
    </div>
</div>
@endsection
