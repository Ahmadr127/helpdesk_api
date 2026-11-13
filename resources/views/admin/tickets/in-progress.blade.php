@extends('admin.layouts.app')

@section('title', 'Tiket Dalam Proses')

@section('content')
<div class="w-full">
    <!-- Header with Back Button -->
    {{-- <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="bg-white p-5">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Tiket Dalam Proses</h2>
                    <p class="text-sm text-gray-600 mt-1">Tiket yang sedang ditangani</p>
                </div>
                <a href="{{ route('admin.tickets.index') }}"
                    class="text-xs font-medium text-gray-600 hover:text-black px-3 py-1.5 rounded-lg hover:bg-gray-400 transition-all duration-200 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    Kembali ke Dasbor
                </a>
            </div>
        </div>
    </div> --}}

    <!-- Tickets Table (+ filter per kolom di dalam tabel) -->
    <div class="bg-white rounded-lg shadow-md border border-gray-100 overflow-hidden">
        @include('admin.tickets.partials.ticket-table', [
            'tickets' => $tickets,
            'filter' => $filter,
            'kategoris' => $kategoris,
            'departments' => $departments,
            'lockedStatus' => $lockedStatus,
            'filterAction' => route('admin.tickets.in-progress'),
        ])
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $tickets->links() }}
    </div>
</div>
@endsection
