@extends('user.layouts.app')

@section('title', 'Order Perbaikan')

@section('content')
<div class="container px-6 mx-auto grid">
    <!-- Back Button -->
    <div class="mt-6">
        <a href="{{ route('user.dashboard') }}"
            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 border border-transparent rounded-md shadow-sm text-sm text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Kembali ke Dashboard
        </a>
    </div>

    <h2 class="my-6 text-2xl font-semibold text-gray-700">
        Order Perbaikan
    </h2>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-8">
        <!-- Total Orders -->
        <a href="{{ route('user.administrasi-umum.order-perbaikan.index') }}"
            class="bg-gradient-to-br from-green-50 to-blue-100 rounded-xl p-4 hover:shadow-md transition-all {{ $status === 'all' ? 'ring-2 ring-blue-500' : '' }}">
            <div class="flex flex-col items-center">
                <span class="text-green-600 font-bold text-2xl">{{ $stats['total'] }}</span>
                <span class="text-gray-700 text-sm mt-1">Total Order</span>
            </div>
        </a>

        <!-- In Progress Orders -->
        <a href="{{ route('user.administrasi-umum.order-perbaikan.index', ['status' => 'in_progress']) }}"
            class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-4 hover:shadow-md transition-all {{ $status === 'in_progress' ? 'ring-2 ring-yellow-500' : '' }}">
            <div class="flex flex-col items-center">
                <span class="text-yellow-600 font-bold text-2xl">{{ $stats['in_progress'] }}</span>
                <span class="text-gray-700 text-sm mt-1">Diproses</span>
            </div>
        </a>

        <!-- Confirmed Orders -->
        <a href="{{ route('user.administrasi-umum.order-perbaikan.index', ['status' => 'confirmed']) }}"
            class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 hover:shadow-md transition-all {{ $status === 'confirmed' ? 'ring-2 ring-green-500' : '' }}">
            <div class="flex flex-col items-center">
                <span class="text-teal-600 font-bold text-2xl">{{ $stats['confirmed'] }}</span>
                <span class="text-gray-700 text-sm mt-1">Dikonfirmasi</span>
            </div>
        </a>

        <!-- Rejected Orders -->
        <a href="{{ route('user.administrasi-umum.order-perbaikan.index', ['status' => 'rejected']) }}"
            class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-4 hover:shadow-md transition-all {{ $status === 'rejected' ? 'ring-2 ring-red-500' : '' }}">
            <div class="flex flex-col items-center">
                <span class="text-red-600 font-bold text-2xl">{{ $stats['rejected'] }}</span>
                <span class="text-gray-700 text-sm mt-1">Ditolak</span>
            </div>
        </a>
    </div>

    <!-- Search and Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form id="filterForm" action="{{ route('user.administrasi-umum.order-perbaikan.index') }}" method="GET"
            class="space-y-6">
            <!-- Date Range and Search Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-gray-700">Tanggal Berakhir</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-gray-700">Nomor Order</label>
                    <input type="text" name="order_number" value="{{ request('order_number') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Cari nomor order...">
                </div>
            </div>

            <!-- Advanced Filters Row -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Diproses
                        </option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai
                        </option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Prioritas</label>
                    <select name="priority"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Prioritas</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Rendah</option>
                        <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Sedang</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Tinggi</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Departemen</label>
                    <select name="department"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Departemen</option>
                        @foreach($departments ?? [] as $department)
                        <option value="{{ $department }}" {{ request('department') == $department ? 'selected' : '' }}>
                            {{ $department }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end space-x-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Terapkan Filter
                    </button>
                    <button type="button" id="resetFilter"
                        class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                        Atur Ulang
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="w-full overflow-hidden rounded-lg shadow-md">
        <div class="w-full overflow-x-auto">
            <table class="w-full whitespace-no-wrap">
                <thead>
                    <tr
                        class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                        <th class="px-4 py-3">Nomor Order</th>
                        <th class="px-4 py-3">Nama Barang</th>
                        <th class="px-4 py-3">Departemen</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Prioritas</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y">
                    @forelse($orders as $order)
                    <tr class="text-gray-700">
                        <td class="px-4 py-3">{{ $order->nomor }}</td>
                        <td class="px-4 py-3">{{ $order->nama_barang }}</td>
                        <td class="px-4 py-3">{{ $order->unit_proses_name }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ $order->status === 'open' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $order->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $order->status === 'in_progress' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                {{ $order->status === 'completed' ? 'bg-teal-100 text-teal-800' : '' }}
                                {{ $order->status === 'confirmed' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $order->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ $order->status === 'open' ? 'Terbuka' : 
                                   ($order->status === 'pending' ? 'Menunggu' :
                                   ($order->status === 'in_progress' ? 'Diproses' : 
                                   ($order->status === 'completed' ? 'Selesai' :
                                   ($order->status === 'confirmed' ? 'Dikonfirmasi' : 'Ditolak')))) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ strtoupper($order->prioritas) === 'TINGGI/URGENT' ? 'bg-red-100 text-red-800' : 
                                   (strtoupper($order->prioritas) === 'SEDANG' ? 'bg-yellow-100 text-yellow-800' : 
                                   (strtoupper($order->prioritas) === 'RENDAH' ? 'bg-green-100 text-green-800' : 
                                   'bg-blue-100 text-blue-800')) }}">
                                {{ $order->prioritas }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $order->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex space-x-3">
                                <a href="{{ route('user.administrasi-umum.order-perbaikan.show', $order) }}"
                                    class="text-blue-600 hover:text-blue-900">Lihat</a>

                                @if($order->status === 'pending')
                                <a href="{{ route('user.administrasi-umum.order-perbaikan.edit', $order) }}"
                                    class="text-yellow-600 hover:text-yellow-900">Ubah</a>

                                <form action="{{ route('user.administrasi-umum.order-perbaikan.delete', $order) }}"
                                    method="POST"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus order ini?');"
                                    class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-3 text-center text-gray-500">
                            Tidak ada order ditemukan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t bg-gray-50">
            {{ $orders->appends(request()->query())->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('filterForm');
    const resetButton = document.getElementById('resetFilter');

    // Reset button handler
    resetButton.addEventListener('click', function() {
        const inputs = form.querySelectorAll('input:not([name="status"])');
        const selects = form.querySelectorAll('select');

        inputs.forEach(input => input.value = '');
        selects.forEach(select => select.selectedIndex = 0);

        form.submit();
    });
});
</script>
@endpush
@endsection