@extends('user.layouts.app')

@section('title', 'Buat Tiket Baru')

@section('content')
<!-- Include the notification partial -->
@include('user.partials.notification')

<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">

        @if(!auth()->user()->department && !auth()->user()->department_id)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Anda belum memiliki data departemen. Silakan lengkapi profil Anda terlebih dahulu di
                        <a href="{{ route('user.settings') }}"
                            class="font-medium underline text-yellow-700 hover:text-yellow-600">
                            pengaturan profil
                        </a>.
                    </p>
                </div>
            </div>
        </div>
        @endif

        <div class="">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Header - BLUE theme for Ticket IT -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-400 px-6 py-4 flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-white">Buat Tiket Dukungan Baru</h2>
                    <a href="{{ route('user.ticket.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-700 bg-opacity-90 rounded-lg text-white hover:bg-blue-800 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Kembali
                    </a>
                </div>

                <!-- Form Content -->
                <div class="p-6">
                    @if($errors->any())
                        <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                            <ul class="list-disc list-inside text-sm">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @include('user.pengajuan._form', [
                        'action' => route('user.pengajuan.store'),
                        'method' => 'POST',
                        'accent' => 'blue',
                        'kategoris' => $categories,
                        'locations' => $locations,
                        'buildings' => $buildings,

                        'userDepartment' => $userDepartment,
                        'submitLabel' => 'Buat Tiket',
                        'cancelUrl' => route('user.ticket.index'),
                        'showOrderFields' => false,
                    ])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection