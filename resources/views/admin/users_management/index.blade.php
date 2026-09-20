@extends('admin.layouts.app')

@section('title', 'Manage Users')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Users Management</h2>
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.users.create') }}"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                    Add User
                </a>
            </div>
        </div>
    </div>

    <div class="card bg-white shadow-md rounded-xl overflow-hidden">
        <div class="p-6">
            <!-- Search and Filter Bar (server-side, support pagination) -->
            <form method="GET" action="{{ route('admin.users.index') }}" id="filter-form" class="mb-6 space-y-4">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="relative flex-1">
                        <input type="text" name="search" id="search-users" value="{{ request('search') }}" placeholder="Search users (nama, username, phone, dept, position)..."
                            class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-colors">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <select name="role" id="filter-role"
                            class="rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-colors">
                            <option value="">Semua Role</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->slug }}" {{ request('role') === $role->slug ? 'selected' : '' }}>
                                {{ $role->name ?: $role->slug }}
                            </option>
                            @endforeach
                        </select>
                        <select name="status" id="filter-status"
                            class="rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-colors">
                            <option value="">All Status</option>
                            <option value="1" {{ request('status')==='1'?'selected':'' }}>Active</option>
                            <option value="0" {{ request('status')==='0'?'selected':'' }}>Inactive</option>
                        </select>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">Cari</button>
                        @if(request('search') || (request('role') !== null && request('role') !== '') || (request('status') !== null && request('status') !== '') || request('department') || request('position'))
                            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Reset</a>
                        @endif
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Filter Department (kolom pencarian)</label>
                        <x-searchable-select name="department" :options="$departments" :selected="request('department')" placeholder="Cari department..." id="filter_department" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Filter Position (kolom pencarian)</label>
                        <x-searchable-select name="position" :options="$positions" :selected="request('position')" placeholder="Cari position..." id="filter_position" />
                    </div>
                </div>
            </form>
            @if(request('search'))
                <div class="mb-4 text-sm text-gray-600">Hasil pencarian untuk "<strong>{{ request('search') }}</strong>" — {{ $users->total() }} user ditemukan</div>
            @endif

            <div class="overflow-x-auto rounded-lg shadow-sm border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                Username
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($users as $user)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-500 text-white flex items-center justify-center">
                                        <span class="font-medium">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $user->username ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $roleModel = $roles->firstWhere('slug', $user->role);
                                    $roleLabel = $roleModel?->name ?: ucfirst($user->role ?? 'user');
                                    $badge = match ($user->role) {
                                        'admin' => 'bg-blue-100 text-blue-700 border border-blue-200',
                                        'ipsrs' => 'bg-green-100 text-green-700 border border-green-200',
                                        default => 'bg-gray-100 text-gray-800 border border-gray-200',
                                    };
                                @endphp
                                <span class="px-3 py-1 text-xs rounded-full {{ $badge }}">
                                    {{ $roleLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-800 border border-gray-200">
                                    {{ ucfirst($user->department) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 text-xs rounded-full inline-flex items-center
                                    {{ $user->status === 1 
                                    ? 'bg-green-100 text-green-800 border border-green-200' 
                                    : 'bg-red-100 text-red-800 border border-red-200' }}">
                                    <span
                                        class="h-2 w-2 mr-1 rounded-full {{ $user->status === 1 ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                    {{ $user->status === 1 ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap space-x-2">
                                <a href="{{ route('admin.users.edit', $user) }}"
                                    class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors duration-200 border border-blue-200">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </a>

                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        onclick="return confirm('Are you sure you want to delete this user?')"
                                        class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition-colors duration-200 border border-red-200">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-users');
    const filterRole = document.getElementById('filter-role');
    const filterStatus = document.getElementById('filter-status');
    const filterDept = document.getElementById('filter_department');
    const filterPos = document.getElementById('filter_position');
    const form = document.getElementById('filter-form');

    // Auto-submit dengan debounce untuk search, instant untuk select
    let timeout;
    if (searchInput) searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => form.submit(), 600);
    });
    if (filterRole) filterRole.addEventListener('change', () => form.submit());
    if (filterStatus) filterStatus.addEventListener('change', () => form.submit());
    if (filterDept) filterDept.addEventListener('change', () => form.submit());
    if (filterPos) filterPos.addEventListener('change', () => form.submit());
});
</script>
@endpush
@endsection