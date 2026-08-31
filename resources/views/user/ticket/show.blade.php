@extends('user.layouts.app')

@section('title', 'Detail Tiket')

@section('content')
@include('user.partials.notification')

<div class="container mx-auto px-4 py-4 max-w-8xl mb-4">
    <!-- Back Button -->
    <div class="mb-4">
        <a href="{{ route('user.ticket.index') }}"
            class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
            Kembali Ke Daftar Tiket
        </a>
    </div>

    <!-- Ticket Header -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold text-gray-800">Detail Tiket</h2>
        <div class="flex space-x-3">
            @if($ticket->status === 'closed' && !$ticket->user_confirmation)
            <form action="{{ route('user.ticket.confirm', $ticket) }}" method="POST" class="inline-block"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="action" value="confirm">
                <button type="button" onclick="openConfirmationModal('confirm')"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors shadow-sm flex items-center text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Konfirmasi Selesai
                </button>
            </form>

            <form action="{{ route('user.ticket.confirm', $ticket) }}" method="POST" class="inline-block"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="action" value="reject">
                <button type="button" onclick="openConfirmationModal('reject')"
                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors shadow-sm flex items-center text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                    Masih Ada Masalah
                </button>
            </form>
            @endif

            @if($ticket->status === 'open')
            <a href="{{ route('user.ticket.edit', $ticket) }}"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors shadow-sm flex items-center text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                    </path>
                </svg>
                Edit Tiket
            </a>
            @endif

            @if(in_array($ticket->status, ['open', 'pending']))
            <form action="{{ route('user.ticket.destroy', $ticket) }}" method="POST"
                onsubmit="return confirm('Apakah Anda yakin ingin menghapus tiket ini?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors shadow-sm flex items-center text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                        </path>
                    </svg>
                    Hapus Tiket
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <!-- Ticket Details Column -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden ticket-container">
                <!-- Status Bar -->
                <div class="bg-gradient-to-r from-green-600 to-blue-400 px-4 py-3 rounded-t-lg">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center space-y-2 md:space-y-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-3 py-1 text-sm leading-5 font-medium rounded-full 
                                {{ $ticket->status === 'open' ? 'bg-blue-100 text-blue-800' : 
                               ($ticket->status === 'in_progress' ? 'bg-purple-100 text-purple-800' : 
                                   ($ticket->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                               ($ticket->status === 'closed' ? 'bg-gray-100 text-gray-800' :
                               'bg-green-100 text-green-800'))) }}">
                                {{ $ticket->status === 'open' ? 'Dibuka' : 
                                   ($ticket->status === 'in_progress' ? 'Dalam Proses' : 
                                   ($ticket->status === 'pending' ? 'Menunggu' : 
                                   ($ticket->status === 'closed' ? 'Ditutup' : 
                                   'Selesai'))) }}
                            </span>
                            <span class="px-3 py-1 text-sm leading-5 font-medium rounded-full 
                            {{ $ticket->priority === 'low' ? 'bg-gray-100 text-gray-800' : 
                               ($ticket->priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : 
                               'bg-red-100 text-red-800') }}">
                                Prioritas {{ $ticket->priority === 'low' ? 'Rendah' : 
                                           ($ticket->priority === 'medium' ? 'Sedang' : 
                                           'Tinggi') }}
                            </span>
                            <span class="text-sm font-medium text-white">Tiket #{{ $ticket->ticket_number }}</span>
                        </div>
                        <div class="text-sm text-white">
                            Dibuat: {{ $ticket->created_at->format('d M Y H:i') }}
                        </div>
                    </div>
                </div>

                <!-- Ticket Content -->
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Kategori</h3>
                            <p class="text-sm text-gray-800">{{ ucfirst($ticket->category) }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Departemen</h3>
                            <p class="text-sm text-gray-800">{{ $ticket->department }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Gedung</h3>
                            <p class="text-sm text-gray-800">
                                @if($ticket->buildingRelation)
                                {{ $ticket->buildingRelation->name }} ({{ $ticket->building }})
                                @else
                                {{ $ticket->building }}
                                @endif
                            </p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Lokasi</h3>
                            <p class="text-sm text-gray-800">{{ $ticket->location }}</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                        <h2 class="text-lg font-medium text-gray-800 mb-3">Informasi Tiket</h2>

                        <!-- Grid Layout untuk Foto dan Deskripsi -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <!-- Foto Tiket -->
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 mb-2">Foto Tiket</h3>
                                <div
                                    class="bg-gray-50 p-3 rounded-lg border border-gray-100 h-48 flex items-center justify-center">
                                    @if($ticket->initialPhoto)
                                    @php
                                    $photoPath = $ticket->initialPhoto->photo_path;
                                    $fullPath = storage_path('app/public/' . $photoPath);
                                    $publicPath = public_path('storage/' . $photoPath);

                                    // Debug information
                                    $debug = [
                                    'photo_exists_storage' => file_exists($fullPath),
                                    'photo_exists_public' => file_exists($publicPath),
                                    'storage_path' => $fullPath,
                                    'public_path' => $publicPath,
                                    'is_readable_storage' => is_readable($fullPath),
                                    'is_readable_public' => is_readable($publicPath),
                                    ];
                                    @endphp

                                    @if($debug['photo_exists_storage'] || $debug['photo_exists_public'])
                                    <img src="{{ asset('storage/' . $photoPath) }}" alt="Foto Tiket"
                                        class="max-w-full max-h-full object-contain rounded-lg shadow-sm"
                                        onerror="this.onerror=null; this.classList.add('error-image'); this.parentNode.innerHTML = '<div class=\'text-center p-4\'><svg class=\'mx-auto h-12 w-12 text-gray-400\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'></path></svg><p class=\'mt-2 text-sm text-gray-500\'>Gagal memuat gambar</p></div>';">
                                    @else
                                    <div class="text-center">
                                        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-500">Tidak ada foto</p>
                                    </div>
                                    @endif
                                    @else
                                    <div class="text-center">
                                        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        <p class="mt-2 text-gray-500">Tidak ada foto</p>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Deskripsi -->
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 mb-2">Deskripsi Masalah</h3>
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 h-48 overflow-auto">
                                    <div class="text-sm text-gray-700 whitespace-pre-line">
                                        {{ $ticket->description }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($ticket->admin_notes)
                    <div class="mb-4">
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Catatan Admin</h3>
                        <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                            <p class="text-sm whitespace-pre-line text-gray-800">{{ $ticket->admin_notes }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Ticket Timeline -->
                    <div class="mt-8">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Riwayat Status Tiket</h3>
                        <div class="bg-gray-50 rounded-lg border border-gray-100 p-5">
                            <div class="flex items-center">
                                <!-- Timeline Line -->
                                <div class="relative flex items-center justify-between w-full">
                                    <!-- Created -->
                                    <div class="relative flex flex-col items-center">
                                        <div
                                            class="w-10 h-10 flex items-center justify-center rounded-full bg-gradient-to-r from-green-400 to-green-500 text-white">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div class="mt-3 text-center">
                                            <h4 class="text-sm font-medium text-gray-800">Dibuat</h4>
                                            <p class="text-xs text-gray-500">
                                                {{ $ticket->created_at->format('d M Y H:i') }}</p>
                                        </div>
                                        <!-- Line to next status -->
                                        @if($ticket->in_progress_at || $ticket->closed_at || $ticket->user_confirmed_at)
                                        <div class="absolute top-5 left-full w-full h-0.5 bg-gray-200"></div>
                                        @endif
                                    </div>

                                    <!-- In Progress -->
                                    @if($ticket->in_progress_at)
                                    <div class="relative flex flex-col items-center">
                                        <div
                                            class="w-10 h-10 flex items-center justify-center rounded-full bg-gradient-to-r from-purple-400 to-purple-500 text-white">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div class="mt-3 text-center">
                                            <h4 class="text-sm font-medium text-gray-800">Dalam Proses</h4>
                                            <p class="text-xs text-gray-500">
                                                {{ $ticket->in_progress_at->format('d M Y H:i') }}</p>
                                        </div>
                                        <!-- Line to next status -->
                                        @if($ticket->closed_at || $ticket->user_confirmed_at)
                                        <div class="absolute top-5 left-full w-full h-0.5 bg-gray-200"></div>
                                        @endif
                                    </div>
                                    @endif

                                    <!-- Closed -->
                                    @if($ticket->closed_at)
                                    <div class="relative flex flex-col items-center">
                                        <div
                                            class="w-10 h-10 flex items-center justify-center rounded-full bg-gradient-to-r from-yellow-400 to-yellow-500 text-white">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div class="mt-3 text-center">
                                            <h4 class="text-sm font-medium text-gray-800">Ditutup</h4>
                                            <p class="text-xs text-gray-500">
                                                {{ $ticket->closed_at->format('d M Y H:i') }}</p>
                                        </div>
                                        <!-- Line to next status -->
                                        @if($ticket->user_confirmed_at)
                                        <div class="absolute top-5 left-full w-full h-0.5 bg-gray-200"></div>
                                        @endif
                                    </div>
                                    @endif

                                    <!-- Confirmed -->
                                    @if($ticket->user_confirmed_at)
                                    <div class="relative flex flex-col items-center">
                                        <div
                                            class="w-10 h-10 flex items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-green-600 text-white">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div class="mt-3 text-center">
                                            <h4 class="text-sm font-medium text-gray-800">Dikonfirmasi</h4>
                                            <p class="text-xs text-gray-500">
                                                {{ $ticket->user_confirmed_at->format('d M Y H:i') }}</p>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conversation History Column -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 h-full conversation-container">
                <!-- Conversation Header -->
                <div class="bg-gradient-to-r from-blue-400 to-green-600 px-4 py-3 rounded-t-lg">
                    <h3 class="text-base font-medium text-white">Riwayat Percakapan</h3>
                </div>

                <!-- Conversation Content -->
                <div class="conversation-content h-full flex flex-col">
                    <div class="conversation-messages flex-grow overflow-y-auto">
                        @php
                        // Check if responses are already arrays
                        $adminResponses = is_array($ticket->admin_responses) ?
                        $ticket->admin_responses :
                        (json_decode($ticket->admin_responses, true) ?? []);

                        $userReplies = is_array($ticket->user_replies) ?
                        $ticket->user_replies :
                        (json_decode($ticket->user_replies, true) ?? []);

                        // Combine both arrays and sort by timestamp
                        $allResponses = [];

                        foreach($adminResponses as $response) {
                        $response['type'] = 'admin';
                        $allResponses[] = $response;
                        }

                        foreach($userReplies as $reply) {
                        $reply['type'] = 'user';
                        $allResponses[] = $reply;
                        }

                        // Sort by timestamp (newest first)
                        usort($allResponses, function($a, $b) {
                        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
                        });
                        @endphp

                        @if(count($allResponses) > 0)
                        @foreach($allResponses as $response)
                        <div class="p-4 border-b border-gray-100">
                            @if($response['type'] === 'admin')
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="font-medium text-blue-800">Admin</div>
                                    <div class="text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($response['timestamp'])->format('d M Y H:i') }}
                                    </div>
                                </div>
                                <p class="text-sm whitespace-pre-line text-gray-800">{{ $response['notes'] ?? '' }}</p>
                                @if(isset($response['photo']))
                                <div class="mt-3">
                                    <a href="{{ asset('storage/' . $response['photo']) }}" target="_blank"
                                        class="block">
                                        <img src="{{ asset('storage/' . $response['photo']) }}" alt="Foto respon admin"
                                            class="max-h-32 w-auto rounded border border-gray-200 mx-auto hover:opacity-90 transition">
                                    </a>
                                </div>
                                @endif
                            </div>
                            @else
                            <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="font-medium text-green-800">Anda</div>
                                    <div class="text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($response['timestamp'])->format('d M Y H:i') }}
                                    </div>
                                </div>

                                @if(isset($response['message']))
                                <p class="text-sm whitespace-pre-line text-gray-800">{{ $response['message'] }}</p>
                                @elseif(isset($response['notes']))
                                <p class="text-sm whitespace-pre-line text-gray-800">
                                    @if(isset($response['type']) && $response['type'] === 'confirm')
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mb-2">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        Dikonfirmasi selesai
                                    </span>
                                    @elseif(isset($response['type']) && $response['type'] === 'reject')
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 mb-2">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        Masih ada masalah
                                    </span>
                                    @endif
                                    <span class="block">{{ $response['notes'] }}</span>
                                </p>
                                @endif

                                @if(isset($response['photo']) && !empty($response['photo']))
                                <div class="mt-3">
                                    <a href="{{ asset('storage/' . $response['photo']) }}" target="_blank"
                                        class="block">
                                        <img src="{{ asset('storage/' . $response['photo']) }}" alt="Foto respon Anda"
                                            class="max-h-32 w-auto rounded border border-gray-200 mx-auto hover:opacity-90 transition"
                                            onerror="this.onerror=null; this.classList.add('error-image'); this.parentNode.innerHTML = '<div class=\'text-center p-4\'><svg class=\'mx-auto h-12 w-12 text-gray-400\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'></path></svg><p class=\'mt-2 text-sm text-gray-500\'>Gagal memuat gambar</p></div>';">
                                    </a>
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                        @endforeach
                        @else
                        <div class="flex flex-col items-center justify-center h-full p-8 text-center text-gray-500">
                            <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                                </path>
                            </svg>
                            <p class="text-lg font-medium">Belum Ada Respon</p>
                            <p class="mt-1 text-sm">Tiket ini belum memiliki balasan dari admin atau pengguna</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reply Form Section -->
    @if(in_array($ticket->status, ['in_progress', 'pending']))
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-16">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
            <h3 class="text-lg font-medium text-gray-800">Balas Tiket</h3>
        </div>

        <div class="p-6">
            <form action="{{ route('user.ticket.reply', $ticket) }}" method="POST"
                class="grid grid-cols-1 lg:grid-cols-3 gap-6" enctype="multipart/form-data" id="replyForm">
                @csrf

                <!-- Message Input -->
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Pesan Anda
                    </label>
                    <textarea name="message" rows="4" required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-300 focus:border-blue-400 transition-colors"
                        placeholder="Ketik pesan Anda di sini..."></textarea>
                </div>

                <!-- Image Upload -->
                <div class="lg:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Lampirkan Foto (Opsional)
                    </label>
                    <div
                        class="relative border border-dashed border-gray-300 rounded-lg p-4 h-[calc(100%-2rem)] flex flex-col items-center justify-center">
                        <input type="file" name="photo" accept="image/*" class="hidden" id="reply-photo-upload"
                            onchange="handleImagePreview(this)">
                        <div class="flex flex-col items-center justify-center h-full w-full" id="upload-placeholder">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                            <label for="reply-photo-upload"
                                class="mt-2 text-sm font-medium text-blue-600 hover:text-blue-700 cursor-pointer">
                                Unggah Foto
                            </label>
                            <span class="mt-1 text-xs text-gray-500">Maks 5MB</span>
                        </div>
                        <div id="image-preview" class="hidden mt-2 text-center">
                            <img src="" alt="Pratinjau" class="max-h-32 max-w-full mx-auto rounded">
                            <button type="button" onclick="removeImage()"
                                class="mt-2 text-sm text-red-600 hover:text-red-700">
                                Hapus Foto
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="lg:col-span-3 flex justify-end">
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors shadow-sm flex items-center font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Kirim Balasan
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

<style>
.ticket-container {
    min-height: 350px;
    max-height: fit-content;
    height: auto;
    overflow: visible;
}

.conversation-container {
    min-height: 350px;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.conversation-content {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.conversation-messages {
    flex: 1;
    overflow-y: auto;
    padding-bottom: 0.75rem;
    min-height: 350px;
    max-height: none;
}

/* Custom scrollbar styles */
.conversation-messages::-webkit-scrollbar {
    width: 4px;
}

.conversation-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 2px;
}

.conversation-messages::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 2px;
}

.conversation-messages::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

@media (min-width: 1024px) {

    .ticket-container,
    .conversation-container {
        position: sticky;
        top: 1rem;
    }

    .conversation-messages {
        max-height: none;
        height: calc(100vh - 180px);
    }
}

/* Add these styles to your existing styles */
.timeline-line {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 2px;
    background-color: #E5E7EB;
    transform: translateY(-50%);
    z-index: 0;
}

.timeline-point {
    position: relative;
    z-index: 1;
    background-color: white;
}
</style>

<script>
function handleImagePreview(input) {
    const preview = document.getElementById('image-preview');
    const placeholder = document.getElementById('upload-placeholder');
    const previewImg = preview.querySelector('img');

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        }

        reader.readAsDataURL(input.files[0]);
    }
}

function removeImage() {
    const input = document.getElementById('reply-photo-upload');
    const preview = document.getElementById('image-preview');
    const placeholder = document.getElementById('upload-placeholder');

    input.value = '';
    preview.classList.add('hidden');
    placeholder.classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleConversation');
    const content = document.getElementById('conversationContent');
    const chevronIcon = document.getElementById('chevronIcon');

    if (toggleBtn && content && chevronIcon) {
        toggleBtn.addEventListener('click', function() {
            content.classList.toggle('expanded');
            chevronIcon.classList.toggle('rotate-180');
        });
    }

    // Apply image preview functionality to confirmation forms
    const confirmForms = document.querySelectorAll('form[action*="confirm"]');

    confirmForms.forEach(form => {
        const fileInput = form.querySelector('input[type="file"]');
        if (fileInput) {
            const previewContainer = document.createElement('div');
            previewContainer.className = 'mt-2 hidden';
            previewContainer.id = `preview-${fileInput.id}`;

            const previewImg = document.createElement('img');
            previewImg.className = 'max-h-32 rounded border border-gray-200';
            previewContainer.appendChild(previewImg);

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'mt-2 text-sm text-red-600 hover:text-red-700 block';
            removeButton.textContent = 'Hapus Foto';
            removeButton.onclick = () => {
                fileInput.value = '';
                previewContainer.classList.add('hidden');
            };
            previewContainer.appendChild(removeButton);

            fileInput.parentNode.appendChild(previewContainer);

            fileInput.onchange = function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        previewContainer.classList.remove('hidden');
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            };
        }
    });

    function adjustHeight() {
        const ticketContainer = document.querySelector('.ticket-container');
        const conversationContainer = document.querySelector('.conversation-container');

        if (ticketContainer && conversationContainer) {
            const ticketHeight = ticketContainer.scrollHeight;
            conversationContainer.style.height = `${ticketHeight}px`;
        }
    }

    // Run on page load
    adjustHeight();

    // Run on window resize
    window.addEventListener('resize', adjustHeight);

    // Run when images load (which might affect height)
    window.addEventListener('load', adjustHeight);
});

function openImageModal(src) {
    const modal = document.getElementById('imageModal');
    if (!modal) return;

    const modalImage = document.getElementById('modalImage');
    if (!modalImage) return;

    modalImage.src = src;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    if (!modal) return;

    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside the image
const imageModal = document.getElementById('imageModal');
if (imageModal) {
    imageModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeImageModal();
        }
    });
}

// Close modal with escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
    }
});

function openConfirmationModal(action) {
    const modal = document.getElementById('confirmationModal');
    const form = document.getElementById('confirmationForm');
    const actionInput = document.getElementById('confirmationAction');
    const modalTitle = document.getElementById('modalTitle');
    const submitButton = document.getElementById('submitButton');

    // Set the form action
    form.action = "{{ route('user.ticket.confirm', $ticket) }}";
    actionInput.value = action;

    // Update modal title and button based on action
    if (action === 'confirm') {
        modalTitle.textContent = 'Konfirmasi Penyelesaian Tiket';
        submitButton.className = 'px-4 py-2 text-white bg-green-600 rounded-md hover:bg-green-700';
        submitButton.textContent = 'Konfirmasi';
    } else {
        modalTitle.textContent = 'Laporkan Masalah';
        submitButton.className = 'px-4 py-2 text-white bg-red-600 rounded-md hover:bg-red-700';
        submitButton.textContent = 'Laporkan';
    }

    modal.classList.remove('hidden');
}

function closeConfirmationModal() {
    const modal = document.getElementById('confirmationModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

// Close modal when clicking outside
const confirmationModal = document.getElementById('confirmationModal');
if (confirmationModal) {
    confirmationModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeConfirmationModal();
        }
    });
}

// Close modal with escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeConfirmationModal();
    }
});

function handleConfirmationImagePreview(input) {
    const preview = document.getElementById('confirmation-image-preview');
    const placeholder = document.getElementById('confirmation-upload-placeholder');
    const previewImg = preview.querySelector('img');

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        }

        reader.readAsDataURL(input.files[0]);
    }
}

function removeConfirmationImage() {
    const input = document.getElementById('confirmation-photo-upload');
    const preview = document.getElementById('confirmation-image-preview');
    const placeholder = document.getElementById('confirmation-upload-placeholder');

    input.value = '';
    preview.classList.add('hidden');
    placeholder.classList.remove('hidden');
}
</script>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4" id="modalTitle">Konfirmasi Tiket</h3>

                <form id="confirmationForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="action" id="confirmationAction">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Catatan
                        </label>
                        <textarea name="confirmation_notes" rows="4" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Berikan catatan atau komentar Anda..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Foto (Opsional)
                        </label>
                        <div class="relative border border-dashed border-gray-300 rounded-lg p-4">
                            <input type="file" name="photo" accept="image/*" id="confirmation-photo-upload"
                                class="hidden" onchange="handleConfirmationImagePreview(this)">
                            <div class="flex flex-col items-center justify-center" id="confirmation-upload-placeholder">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <label for="confirmation-photo-upload"
                                    class="mt-2 text-sm font-medium text-blue-600 hover:text-blue-700 cursor-pointer">
                                    Unggah Foto
                                </label>
                                <span class="mt-1 text-xs text-gray-500">Maks 5MB</span>
                            </div>
                            <div id="confirmation-image-preview" class="hidden mt-2 text-center">
                                <img src="" alt="Pratinjau" class="max-h-32 max-w-full mx-auto rounded">
                                <button type="button" onclick="removeConfirmationImage()"
                                    class="mt-2 text-sm text-red-600 hover:text-red-700">
                                    Hapus Foto
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeConfirmationModal()"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                            Batal
                        </button>
                        <button type="submit" id="submitButton" class="px-4 py-2 text-white rounded-md">
                            Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection