@extends('admin.layouts.app')

@section('title', 'Semua Tiket')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header with Back Button -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-white to-blue-300 p-5">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Semua Tiket</h2>
                <a href="{{ route('admin.tickets.index') }}"
                    class="text-xs font-medium text-gray-800 hover:text-black px-3 py-1.5 rounded-lg hover:bg-gray-400 transition-all duration-200 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali ke Dasbor
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-gradient-to-r from-white to-blue-300 rounded-lg shadow-md p-6 mb-6 border border-gray-100">
        <form action="{{ route('admin.tickets.all') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status"
                        class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="">Semua Status</option>
                        <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Dibuka</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Dalam
                            Proses</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu
                            Konfirmasi</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Dikonfirmasi
                        </option>
                    </select>
                </div>

                <!-- Start Date Filter -->
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}"
                        class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>

                <!-- End Date Filter -->
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}"
                        class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>

                <!-- Confirmation Status -->
                <div>
                    <label for="confirmation" class="block text-sm font-medium text-gray-700 mb-1">Status
                        Konfirmasi</label>
                    <select name="confirmation" id="confirmation"
                        class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="">Semua</option>
                        <option value="pending" {{ request('confirmation') == 'pending' ? 'selected' : '' }}>Menunggu
                            Konfirmasi</option>
                        <option value="confirmed" {{ request('confirmation') == 'confirmed' ? 'selected' : '' }}>
                            Dikonfirmasi</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end space-x-2">
                <a href="{{ route('admin.tickets.all') }}"
                    class="bg-gradient-to-r from-gray-500 to-gray-600 text-white px-4 py-2 rounded-md hover:from-gray-600 hover:to-gray-700 transition-all shadow-sm">
                    Atur Ulang
                </a>
                <button type="submit"
                    class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-2 rounded-md hover:from-blue-600 hover:to-blue-700 transition-all shadow-sm">
                    Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Tickets Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-100">
        @if($tickets->count() > 0)
        @include('admin.tickets.partials.ticket-table', ['tickets' => $tickets])
        @else
        <div class="p-6 text-center text-gray-500">
            Tidak ada tiket yang ditemukan sesuai filter yang dipilih.
        </div>
        @endif
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $tickets->links() }}
    </div>
</div>
@endsection