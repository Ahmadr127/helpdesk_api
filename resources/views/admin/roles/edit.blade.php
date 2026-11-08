@extends('admin.layouts.app')

@section('title', 'Edit Role')

@section('content')
<div class="w-full">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Edit Role: {{ $role->slug }}</h1>
        <p class="text-sm text-gray-500 mb-6">Slug tidak dapat diubah setelah role dibuat.</p>

        <form method="POST" action="{{ route('admin.roles.update', $role) }}">
            @csrf
            @method('PUT')
            @include('admin.roles._form', ['role' => $role])
        </form>
    </div>
</div>
@endsection
