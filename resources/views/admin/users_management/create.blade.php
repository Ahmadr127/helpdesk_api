@extends('admin.layouts.app')

@section('title', 'Add New User')

@section('content')
<div class="max-w-4xl mx-auto">
    @if(session('success'))
    <div id="success-alert" class="mb-6 p-4 bg-white border-l-4 border-green-500 rounded-lg shadow-sm relative" role="alert">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
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
                <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                Add New User
            </h2>
        </div>
        @include('admin.users_management._form', ['user' => null, 'positions' => $positions, 'departments' => $departments, 'roles' => $roles, 'selectedRole' => $selectedRole])
    </div>
</div>
@endsection
