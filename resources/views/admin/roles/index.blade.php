@extends('admin.layouts.app')

@section('title', 'Manajemen Role')

@section('content')
<div class="w-full">
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">{{ session('error') }}</div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Manajemen Role</h1>
            <p class="text-sm text-gray-500">Kelola role, permission, dan assignment kategori pengajuan.</p>
        </div>
        <a href="{{ route('admin.roles.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">Tambah Role</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Permission</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($roles as $role)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $role->slug }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $role->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $role->permissions->count() }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $role->kategoris->count() }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ route('admin.roles.edit', $role) }}" class="text-blue-600 hover:text-blue-800 mr-3">Edit</a>
                            <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="inline" onsubmit="return confirm('Hapus role ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
