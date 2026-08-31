@extends('user.layouts.app')

@section('title', 'Dasbor')

@section('content')
<!-- Include the notification partial -->
@include('user.partials.notification')

<div class="container mx-auto px-4 py-8 max-w-8xl">
    <h1 class="text-2xl font-semibold text-gray-800 mb-8">Selamat Datang di Helpdesk RS Azra</h1>

    <!-- Main Grid Layout - Split into Two Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
        <!-- Left Column - Ticket Section -->
        <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
            <div class="bg-gradient-to-r from-green-600 to-blue-300 p-4 rounded-lg mb-6">
                <h2 class="text-xl font-semibold text-white flex items-center">
                    <svg class="w-6 h-6 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                    </path>
                </svg>
                Informasi Tiket Support
                <span class="ml-auto">
                    <a href="{{ route('user.ticket.create') }}"
                            class="bg-white text-green-600 px-3 py-1.5 text-sm rounded-lg hover:bg-gray-50 transition-colors shadow-sm flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        Buat Tiket
                    </a>
                </span>
            </h2>
            </div>

            <!-- Ticket Stats Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <!-- Total Tickets Card -->
                <a href="{{ route('user.ticket.filter.status', 'all') }}"
                    class="bg-gradient-to-br from-blue-50 to-green-100 rounded-xl p-4 hover:shadow-md transition-all flex flex-col items-center justify-center">
                    <span class="text-blue-600 font-bold text-2xl">{{ $totalTickets }}</span>
                    <span class="text-gray-700 text-sm mt-1">Total Tiket</span>
                </a>

                <!-- Open Tickets Card -->
                <a href="{{ route('user.ticket.filter.status', 'open') }}"
                    class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-4 hover:shadow-md transition-all flex flex-col items-center justify-center">
                    <span class="text-indigo-600 font-bold text-2xl">{{ $openTickets }}</span>
                    <span class="text-gray-700 text-sm mt-1">Dibuka</span>
                </a>

                <!-- In Progress Tickets Card -->
                <a href="{{ route('user.ticket.filter.status', 'in_progress') }}"
                    class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-4 hover:shadow-md transition-all flex flex-col items-center justify-center">
                    <span class="text-yellow-600 font-bold text-2xl">{{ $inProgressTickets }}</span>
                    <span class="text-gray-700 text-sm mt-1">Diproses</span>
                </a>

                <!-- Confirmed Tickets Card -->
                <a href="{{ route('user.ticket.filter.status', 'confirmed') }}"
                    class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 hover:shadow-md transition-all flex flex-col items-center justify-center">
                    <span class="text-green-600 font-bold text-2xl">{{ $confirmedTickets }}</span>
                    <span class="text-gray-700 text-sm mt-1">Selesai</span>
                </a>
            </div>

            <!-- Recent Tickets -->
            <div class="mb-4">
                <h3 class="text-md font-semibold text-gray-700 mb-3 flex items-center">
                    Tiket Terbaru
                    <a href="{{ route('user.ticket.index') }}"
                        class="ml-auto text-green-600 hover:text-green-800 text-sm font-medium inline-flex items-center">
                        Lihat Semua
                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </a>
                </h3>
                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                        No Tiket</th>
                                    <th
                                        class="px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                        Subjek</th>
                                    <th
                                        class="px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                        Status</th>
                                    <th
                                        class="px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                        Prioritas</th>
                                    <th
                                        class="px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                        Tanggal</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($recentTickets as $ticket)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-xs">
                                        <a href="{{ route('user.ticket.show', $ticket) }}"
                                            class="text-blue-600 hover:text-blue-900">
                                            {{ $ticket->ticket_number }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-xs">{{ Str::limit($ticket->description, 30) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs leading-4 font-semibold rounded-full 
                                {{ $ticket->status === 'open' ? 'bg-blue-100 text-blue-800' : 
                                   ($ticket->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 
                                   ($ticket->status === 'closed' ? 'bg-purple-100 text-purple-800' : 
                                   ($ticket->status === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                   'bg-gray-100 text-gray-800'))) }}">
                                            {{ $ticket->status === 'closed' ? 'Menunggu Konfirmasi' : 
                                               ($ticket->status === 'open' ? 'Dibuka' :
                                               ($ticket->status === 'in_progress' ? 'Diproses' :
                                               ($ticket->status === 'confirmed' ? 'Selesai' : 
                                               ucfirst($ticket->status)))) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs leading-4 font-semibold rounded-full 
                                        {{ $ticket->priority === 'high' ? 'bg-red-100 text-red-800' : 
                                           ($ticket->priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : 
                                           'bg-green-100 text-green-800') }}">
                                            {{ $ticket->priority === 'high' ? 'Tinggi' :
                                               ($ticket->priority === 'medium' ? 'Sedang' : 'Rendah') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">
                                        {{ $ticket->created_at->format('d M Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                </path>
                                            </svg>
                                            Belum ada tiket yang dibuat
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Order Section -->
        <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
            <div class="bg-gradient-to-r from-blue-300 to-green-600 p-4 rounded-lg mb-6">
                <h2 class="text-xl font-semibold text-white flex items-center">
                    <svg class="w-6 h-6 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                    </path>
                </svg>
                Informasi Order Perbaikan
                <span class="ml-auto">
                    <a href="{{ route('user.administrasi-umum.order-barang') }}"
                            class="bg-white text-green-600 px-3 py-1.5 text-sm rounded-lg hover:bg-gray-50 transition-colors shadow-sm flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        Buat Order
                    </a>
                </span>
            </h2>
            </div>

            <!-- Order Stats Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <!-- Total Orders Card -->
                <a href="{{ route('user.administrasi-umum.order-perbaikan.index') }}"
                    class="bg-gradient-to-br from-green-50 to-blue-100 rounded-xl p-4 hover:shadow-md transition-all flex flex-col items-center justify-center">
                    <span class="text-green-600 font-bold text-2xl">{{ $totalOrders }}</span>
                    <span class="text-gray-700 text-sm mt-1">Total Order</span>
                </a>

                <!-- In Progress Orders Card -->
                <a href="{{ route('user.administrasi-umum.order-perbaikan.index', ['status' => 'in_progress']) }}"
                    class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-4 hover:shadow-md transition-all flex flex-col items-center justify-center">
                    <span class="text-yellow-600 font-bold text-2xl">{{ $inProgressOrders }}</span>
                    <span class="text-gray-700 text-sm mt-1">Diproses</span>
                </a>

                <!-- Confirmed Orders Card -->
                <a href="{{ route('user.administrasi-umum.order-perbaikan.index', ['status' => 'confirmed']) }}"
                    class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 hover:shadow-md transition-all flex flex-col items-center justify-center">
                    <span class="text-teal-600 font-bold text-2xl">{{ $confirmedOrders }}</span>
                    <span class="text-gray-700 text-sm mt-1">Dikonfirmasi</span>
                </a>

                <!-- Rejected Orders Card -->
                <a href="{{ route('user.administrasi-umum.order-perbaikan.index', ['status' => 'rejected']) }}"
                    class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-4 hover:shadow-md transition-all flex flex-col items-center justify-center">
                    <span class="text-red-600 font-bold text-2xl">{{ $rejectedOrders }}</span>
                    <span class="text-gray-700 text-sm mt-1">Ditolak</span>
                </a>
            </div>

            <!-- Recent Orders -->
            <div class="mb-4">
                <h3 class="text-md font-semibold text-gray-700 mb-3 flex items-center">
                    Order Terbaru
                    <a href="{{ route('user.administrasi-umum.order-barang') }}"
                        class="ml-auto text-green-600 hover:text-green-800 text-sm font-medium inline-flex items-center">
                        Lihat Semua
                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </a>
                </h3>
                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        No Order</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Barang</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Prioritas</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($recentOrders as $order)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-xs">
                                        <a href="{{ route('user.administrasi-umum.order-perbaikan.show', $order) }}"
                                            class="text-green-600 hover:text-green-900">
                                            {{ $order->nomor }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-xs">{{ Str::limit($order->nama_barang, 30) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs leading-4 font-semibold rounded-full 
                                        {{ $order->status === 'open' ? 'bg-blue-100 text-blue-800' : 
                                           ($order->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 
                                           ($order->status === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                           ($order->status === 'rejected' ? 'bg-red-100 text-red-800' : 
                                           'bg-gray-100 text-gray-800'))) }}">
                                            {{ $order->status === 'open' ? 'Dibuka' : 
                                               ($order->status === 'in_progress' ? 'Diproses' : 
                                               ($order->status === 'confirmed' ? 'Dikonfirmasi' : 
                                               ($order->status === 'rejected' ? 'Ditolak' : 
                                               ucfirst($order->status)))) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs leading-4 font-semibold rounded-full 
                                        {{ strtoupper($order->prioritas) === 'TINGGI/URGENT' ? 'bg-red-100 text-red-800' : 
                                   (strtoupper($order->prioritas) === 'SEDANG' ? 'bg-yellow-100 text-yellow-800' : 
                                   (strtoupper($order->prioritas) === 'RENDAH' ? 'bg-green-100 text-green-800' : 
                                   'bg-blue-100 text-blue-800')) }}">
                                            {{ $order->prioritas }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                                                </path>
                                            </svg>
                                            Belum ada order yang dibuat
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
        <!-- User Reviews -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-blue-300 p-4">
                <h3 class="text-lg font-semibold text-white">Ulasan Pengguna</h3>
            </div>
            <div class="p-6">
                <div class="fixed-height-container max-h-80 overflow-y-auto pr-2">
                    @foreach($userFeedback as $feedback)
                    <div class="border-b border-gray-100 pb-4 mb-4 last:border-b-0 last:mb-0 last:pb-0">
                        <div class="flex items-center mb-2">
                            <div class="flex-shrink-0">
                                <div
                                    class="h-10 w-10 rounded-full bg-gradient-to-r from-green-50 to-blue-50 flex items-center justify-center text-green-600 font-medium">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                <div class="flex items-center">
                                    <div class="flex text-yellow-400">
                                        @for($i = 1; $i <= 5; $i++) <span
                                            class="{{ $i <= $feedback->rating ? 'text-yellow-400' : 'text-gray-300' }}">
                                            ★</span>
                                            @endfor
                                    </div>
                                    <span class="text-xs text-gray-500 ml-2">{{ $feedback->rating }}.0/5.0</span>
                                </div>
                            </div>
                            <div class="ml-auto text-xs text-gray-400">{{ $feedback->created_at->format('d M Y') }}
                            </div>
                        </div>
                        <p class="text-sm font-medium text-gray-800">{{ $feedback->subject }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ $feedback->message }}</p>

                        @if($feedback->admin_reply)
                        <div class="mt-3 pl-4 border-l-2 border-green-500">
                            <p class="text-sm font-medium text-green-600">Balasan Admin:</p>
                            <p class="text-sm text-gray-600">{{ $feedback->admin_reply }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $feedback->replied_at->format('d M Y') }}</p>
                        </div>
                        @endif
                    </div>
                    @endforeach

                    @if(count($userFeedback) === 0)
                    <div class="text-center py-6 text-gray-500">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
                            </path>
                        </svg>
                        Belum ada ulasan
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Feedback Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-300 to-green-600 p-4">
                <h3 class="text-lg font-semibold text-white">Berikan Umpan Balik Anda</h3>
            </div>
            <div class="p-6">
                <form action="{{ route('user.feedback.store') }}" method="POST" class="space-y-5">
                    @csrf
                    <div>
                        <label for="rating" class="block text-sm font-medium text-gray-700 mb-2">Penilaian</label>
                        <div class="flex space-x-2">
                            <input type="hidden" name="rating" id="rating_input" value="0">

                            <span class="text-3xl cursor-pointer rating-star" data-value="1">☆</span>
                            <span class="text-3xl cursor-pointer rating-star" data-value="2">☆</span>
                            <span class="text-3xl cursor-pointer rating-star" data-value="3">☆</span>
                            <span class="text-3xl cursor-pointer rating-star" data-value="4">☆</span>
                            <span class="text-3xl cursor-pointer rating-star" data-value="5">☆</span>
                        </div>

                        <p id="rating-error" class="text-red-500 text-xs mt-1 hidden">Harap pilih rating bintang</p>
                        @error('rating')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                        <div class="relative">
                            <select id="category" name="category"
                                class="appearance-none w-full px-4 py-2.5 rounded-lg border border-gray-200 bg-white focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-colors pr-10"
                                required>
                                <option value="">Pilih Kategori</option>
                                <option value="SIRS">SIRS</option>
                                <option value="IPSRS">IPSRS</option>
                                <option value="Other">Lainnya</option>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                        @error('category')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subjek</label>
                        <input type="text" id="subject" name="subject"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-colors"
                            required>
                        @error('subject')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Pesan</label>
                        <textarea id="message" name="message" rows="4"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-100 focus:border-green-400 transition-colors"
                            required></textarea>
                        @error('message')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-5 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                            Kirim Umpan Balik
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.fixed-height-container {
    max-height: 400px;
}

.rating-star {
    color: #D1D5DB;
    /* text-gray-300 */
    transition: color 0.2s;
}

.rating-star.active {
    color: #F59E0B;
    /* text-yellow-400 */
}

.rating-star:hover {
    color: #F59E0B;
    /* text-yellow-400 */
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple Star Rating
    const stars = document.querySelectorAll('.rating-star');
    const ratingInput = document.getElementById('rating_input');
    const ratingError = document.getElementById('rating-error');

    if (!stars.length || !ratingInput) return;

    // Set initial stars if a value exists
    const initialRating = parseInt(ratingInput.value);
    if (initialRating > 0) {
        highlightStars(initialRating);
    }

    // Add click event to stars
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const value = parseInt(this.getAttribute('data-value'));
            ratingInput.value = value;
            highlightStars(value);

            if (ratingError) {
                ratingError.classList.add('hidden');
            }
        });

        // Add hover effect
        star.addEventListener('mouseenter', function() {
            const hoverValue = parseInt(this.getAttribute('data-value'));
            stars.forEach(s => {
                const starValue = parseInt(s.getAttribute('data-value'));
                s.classList.toggle('active', starValue <= hoverValue);
            });
        });
    });

    // Reset stars on mouse leave
    const starContainer = stars[0].parentElement;
    starContainer.addEventListener('mouseleave', function() {
        const currentValue = parseInt(ratingInput.value) || 0;
        highlightStars(currentValue);
    });

    // Function to highlight stars up to a given value
    function highlightStars(value) {
        stars.forEach(star => {
            const starValue = parseInt(star.getAttribute('data-value'));
            star.classList.toggle('active', starValue <= value);
            if (starValue <= value) {
                star.innerHTML = '★'; // Filled star
            } else {
                star.innerHTML = '☆'; // Empty star
            }
        });
    }

    // Form validation
    const feedbackForm = document.querySelector('form[action="{{ route("user.feedback.store") }}"]');
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', function(e) {
            if (ratingInput.value === '0') {
                e.preventDefault();
                if (ratingError) {
                    ratingError.classList.remove('hidden');
                }
            }
        });
    }
});
</script>
@endpush

@endsection