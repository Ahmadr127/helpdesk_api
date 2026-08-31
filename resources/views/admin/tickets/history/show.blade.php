@extends('admin.layouts.app')

@section('title', 'Ticket History Details')

@section('content')
<div class="container mx-auto px-4 py-4">
    <div class="flex justify-between items-center mb-4">
        <a href="{{ route('admin.tickets.history.index') }}"
            class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-lg shadow hover:from-gray-600 hover:to-gray-700 transition-all text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
            Back
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-100 mb-4">
        <div class="bg-gradient-to-r from-white to-blue-300 p-4 border-b border-gray-200">
            <h1 class="text-xl font-bold text-gray-800">Ticket #{{ $ticket->ticket_number }}</h1>
            <p class="text-sm text-gray-600">Submitted by {{ $ticket->user->name }} on
                {{ $ticket->created_at->format('d M Y H:i') }}</p>
        </div>

        <div class="p-4">
            <!-- Timeline Section - Now Horizontal -->
            <div class="mb-4">
                <h2 class="text-base font-semibold text-gray-800 mb-3">Timeline</h2>
                <div class="relative flex items-center justify-between w-full py-2">
                    <!-- Created -->
                    <div class="relative flex flex-col items-center">
                        <div
                            class="w-8 h-8 flex items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                        <div class="mt-2 text-center">
                            <p class="text-xs text-gray-500">{{ $ticket->created_at->format('d M Y H:i') }}</p>
                            <p class="text-sm font-medium text-gray-700">Created</p>
                        </div>
                        @if($ticket->in_progress_at || $ticket->closed_at || $ticket->user_confirmed_at)
                        <div class="absolute top-4 left-full w-full h-0.5 bg-gray-200"></div>
                        @endif
                    </div>

                    <!-- In Progress -->
                    @if($ticket->in_progress_at)
                    <div class="relative flex flex-col items-center">
                        <div
                            class="w-8 h-8 flex items-center justify-center rounded-full bg-gradient-to-r from-yellow-500 to-yellow-600 text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="mt-2 text-center">
                            <p class="text-xs text-gray-500">{{ $ticket->in_progress_at->format('d M Y H:i') }}</p>
                            <p class="text-sm font-medium text-gray-700">In Progress</p>
                        </div>
                        @if($ticket->closed_at || $ticket->user_confirmed_at)
                        <div class="absolute top-4 left-full w-full h-0.5 bg-gray-200"></div>
                        @endif
                    </div>
                    @endif

                    <!-- Closed -->
                    @if($ticket->closed_at)
                    <div class="relative flex flex-col items-center">
                        <div
                            class="w-8 h-8 flex items-center justify-center rounded-full bg-gradient-to-r from-purple-500 to-purple-600 text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="mt-2 text-center">
                            <p class="text-xs text-gray-500">{{ $ticket->closed_at->format('d M Y H:i') }}</p>
                            <p class="text-sm font-medium text-gray-700">Closed</p>
                        </div>
                        @if($ticket->user_confirmed_at)
                        <div class="absolute top-4 left-full w-full h-0.5 bg-gray-200"></div>
                        @endif
                    </div>
                    @endif

                    <!-- User confirmed -->
                    @if($ticket->user_confirmed_at)
                    <div class="relative flex flex-col items-center">
                        <div
                            class="w-8 h-8 flex items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-green-600 text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="mt-2 text-center">
                            <p class="text-xs text-gray-500">{{ $ticket->user_confirmed_at->format('d M Y H:i') }}</p>
                            <p class="text-sm font-medium text-gray-700">Confirmed</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 p-3 rounded-lg border border-gray-200">
                    <h2 class="text-sm font-semibold text-gray-800 mb-2">Ticket Information</h2>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Status:</span>
                            <span class="text-sm font-medium">{{ ucfirst($ticket->status) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Category:</span>
                            <span class="text-sm font-medium">{{ ucfirst($ticket->category) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Department:</span>
                            <span class="text-sm font-medium">{{ $ticket->department }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Location:</span>
                            <span class="text-sm font-medium">{{ $ticket->location }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Building:</span>
                            <span class="text-sm font-medium">{{ $ticket->building }}</span>
                        </div>
                    </div>
                </div>

                @if($ticket->user_confirmed_at)
                <div class="bg-gradient-to-r from-green-50 to-blue-50 p-3 rounded-lg border border-gray-200">
                    <h2 class="text-sm font-semibold text-gray-800 mb-2">Confirmation Details</h2>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Confirmed At:</span>
                            <span
                                class="text-sm font-medium">{{ $ticket->user_confirmed_at->format('d M Y H:i') }}</span>
                        </div>
                        @if($ticket->user_confirmation_notes)
                        <div>
                            <span class="text-sm text-gray-600 block mb-1">User Notes:</span>
                            <p class="bg-white p-2 rounded border border-gray-200 text-sm text-gray-700">
                                {{ $ticket->user_confirmation_notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <div class="mb-4">
                <h2 class="text-sm font-semibold text-gray-800 mb-2">Description</h2>
                <div class="bg-white p-3 rounded-lg border border-gray-200">
                    <p class="text-sm text-gray-700">{{ $ticket->description }}</p>

                    @if(isset($ticket->initialPhoto) && $ticket->initialPhoto)
                    <div class="mt-3">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Initial Photo</h3>
                        <a href="{{ Storage::url($ticket->initialPhoto->photo_path) }}" target="_blank">
                            <img src="{{ Storage::url($ticket->initialPhoto->photo_path) }}" alt="Initial Ticket Photo"
                                class="max-h-48 rounded-lg shadow-sm hover:shadow-md transition-all">
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            <div>
                <h2 class="text-sm font-semibold text-gray-800 mb-2">Processing Time</h2>
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 p-3 rounded-lg border border-gray-200">
                    @php
                    $start = $ticket->created_at;
                    $end = $ticket->user_confirmed_at;
                    $duration = $start->diff($end);

                    $timeString = [];
                    if ($duration->days > 0) {
                    $timeString[] = $duration->days . ' days';
                    }
                    if ($duration->h > 0) {
                    $timeString[] = $duration->h . ' hours';
                    }
                    if ($duration->i > 0) {
                    $timeString[] = $duration->i . ' minutes';
                    }
                    @endphp

                    <p class="text-sm text-gray-700">Total time from ticket creation to user confirmation: <span
                            class="font-medium">{{ implode(', ', $timeString) }}</span></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection