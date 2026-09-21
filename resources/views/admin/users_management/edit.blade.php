@extends('admin.layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="max-w-4xl mx-auto">
    @if(session('success'))
    <div id="success-alert" class="mb-6 p-4 bg-white border-l-4 border-green-500 rounded-lg shadow-sm relative" role="alert">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="ml-3"><p class="text-sm font-medium text-green-800">{{ session('success') }}</p></div>
            <button onclick="document.getElementById('success-alert').remove()" class="ml-auto -mx-1.5 -my-1.5 bg-white text-green-500 rounded-lg p-1.5 hover:bg-green-50 inline-flex h-8 w-8">
                <span class="sr-only">Close</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </div>
    <script>setTimeout(function(){ const a=document.getElementById('success-alert'); if(a){ a.style.transition='all 0.5s ease'; a.style.opacity='0'; setTimeout(()=>a.remove(),500);} },3000);</script>
    @endif

    <div class="card bg-white shadow-lg rounded-xl overflow-hidden">
        <div class="bg-white p-6 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                Edit User: {{ $user->name }}
            </h2>
        </div>
        @include('admin.users_management._form', ['user' => $user, 'positions' => $positions, 'departments' => $departments, 'roles' => $roles, 'selectedRole' => $selectedRole])
    </div>
</div>
@endsection
