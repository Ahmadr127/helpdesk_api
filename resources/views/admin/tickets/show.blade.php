@extends('admin.layouts.app')

@section('title', 'Proses Tiket')

@section('content')
<div class="w-full">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="{{ route('admin.tickets.index') }}"
            class="inline-flex items-center text-white bg-blue-600 hover:bg-blue-700 px-3 py-2 rounded-lg transition-all duration-200 text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
            Kembali ke Daftar Tiket
        </a>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <!-- Ticket Details Column -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden ticket-container">
                <!-- Status Bar -->
                <div class="bg-white px-4 py-3 border-b border-gray-100">
                    <div class="flex flex-col space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <span class="text-sm font-medium text-gray-800">Tiket
                                    #{{ $ticket->ticket_number }}</span>
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
                                <span class="px-2 py-0.5 text-xs leading-5 font-medium rounded-full 
                                {{ $ticket->priority === 'low' ? 'bg-gray-100 text-gray-800' : 
                                   ($ticket->priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : 
                                   'bg-red-100 text-red-800') }}">
                                    Prioritas {{ $ticket->priority === 'low' ? 'Rendah' : 
                                               ($ticket->priority === 'medium' ? 'Sedang' : 
                                               'Tinggi') }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-600">
                                {{ $ticket->created_at->format('d M Y H:i') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ticket Content -->
                <div class="p-4">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Kategori</h3>
                            <p class="text-sm text-gray-800">{{ ucfirst($ticket->category) }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Departemen</h3>
                            <p class="text-sm text-gray-800">{{ $ticket->department }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Gedung</h3>
                            <p class="text-sm text-gray-800">{{ $ticket->building }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Lokasi</h3>
                            <p class="text-sm text-gray-800">{{ $ticket->location }}</p>
                        </div>
                    </div>

                    <!-- Tiket Information and Photo -->
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Foto Tiket -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Foto Tiket</h3>
                            <div
                                class="bg-gray-50 p-3 rounded-lg border border-gray-100 h-40 flex items-center justify-center">
                                @if($ticket->initialPhoto)
                                @php
                                $photoPath = $ticket->initialPhoto->photo_path;
                                $fullPath = storage_path('app/public/' . $photoPath);
                                $publicPath = public_path('storage/' . $photoPath);
                                @endphp

                                @if(file_exists($fullPath) || file_exists($publicPath))
                                <a href="{{ asset('storage/' . $photoPath) }}" target="_blank" title="Klik untuk perbesar"
                                    onclick="openTicketPhoto(event, '{{ asset('storage/' . $photoPath) }}')"
                                    class="flex items-center justify-center h-full max-h-full max-w-full overflow-hidden cursor-zoom-in group relative">
                                    <img src="{{ asset('storage/' . $photoPath) }}" alt="Foto Tiket - klik untuk perbesar"
                                        class="max-w-full max-h-36 object-contain rounded-lg shadow-sm group-hover:opacity-90 transition"
                                        onerror="this.onerror=null; this.closest('a').outerHTML = '<div class=\'text-center p-4\'><svg class=\'mx-auto h-12 w-12 text-gray-400\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'></path></svg><p class=\'mt-2 text-sm text-gray-500\'>Gagal memuat gambar</p></div>';">
                                    <span
                                        class="absolute bottom-1 right-1 bg-black bg-opacity-50 text-white text-[10px] px-2 py-0.5 rounded opacity-0 group-hover:opacity-100 transition">Perbesar</span>
                                </a>
                                @else
                                <div class="text-center p-4">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">File foto tidak ditemukan</p>
                                </div>
                                @endif
                                @else
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24"
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

                        <!-- Deskripsi Tiket -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Deskripsi</h3>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 h-40 overflow-auto">
                                <p class="text-sm whitespace-pre-line text-gray-800">{{ $ticket->description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Ticket Timeline -->
                    <div class="mt-2">
                        <h3 class="text-sm font-medium text-gray-800 mb-1">Riwayat Status Tiket</h3>
                        <div class="bg-gray-50 rounded-lg border border-gray-100 p-2">
                            <div class="flex items-center">
                                <!-- Timeline Line -->
                                <div class="relative flex items-center justify-between w-full">
                                    <!-- Created -->
                                    <div class="relative flex flex-col items-center">
                                        <div
                                            class="w-6 h-6 flex items-center justify-center rounded-full bg-green-500 text-white">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div class="mt-1 text-center">
                                            <h4 class="text-xs font-medium text-gray-800">Dibuat</h4>
                                            <p class="text-xs text-gray-500">
                                                {{ $ticket->created_at->format('d M Y H:i') }}</p>
                                        </div>
                                        <!-- Line to next status -->
                                        @if($ticket->in_progress_at || $ticket->closed_at || $ticket->user_confirmed_at)
                                        <div class="absolute top-3 left-full w-full h-0.5 bg-gray-200"></div>
                                        @endif
                                    </div>

                                    <!-- In Progress -->
                                    @if($ticket->in_progress_at)
                                    <div class="relative flex flex-col items-center">
                                        <div
                                            class="w-6 h-6 flex items-center justify-center rounded-full bg-purple-500 text-white">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div class="mt-1 text-center">
                                            <h4 class="text-xs font-medium text-gray-800">Dalam Proses</h4>
                                            <p class="text-xs text-gray-500">
                                                {{ $ticket->in_progress_at->format('d M Y H:i') }}</p>
                                        </div>
                                        <!-- Line to next status -->
                                        @if($ticket->closed_at || $ticket->user_confirmed_at)
                                        <div class="absolute top-3 left-full w-full h-0.5 bg-gray-200"></div>
                                        @endif
                                    </div>
                                    @endif

                                    <!-- Closed -->
                                    @if($ticket->closed_at)
                                    <div class="relative flex flex-col items-center">
                                        <div
                                            class="w-6 h-6 flex items-center justify-center rounded-full bg-yellow-500 text-white">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div class="mt-1 text-center">
                                            <h4 class="text-xs font-medium text-gray-800">Ditutup</h4>
                                            <p class="text-xs text-gray-500">
                                                {{ $ticket->closed_at->format('d M Y H:i') }}</p>
                                        </div>
                                        <!-- Line to next status -->
                                        @if($ticket->user_confirmed_at)
                                        <div class="absolute top-3 left-full w-full h-0.5 bg-gray-200"></div>
                                        @endif
                                    </div>
                                    @endif

                                    <!-- Confirmed -->
                                    @if($ticket->user_confirmed_at)
                                    <div class="relative flex flex-col items-center">
                                        <div
                                            class="w-6 h-6 flex items-center justify-center rounded-full bg-green-600 text-white">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div class="mt-1 text-center">
                                            <h4 class="text-xs font-medium text-gray-800">Dikonfirmasi</h4>
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
                <div class="bg-white px-4 py-3 border-b border-gray-100">
                    <h3 class="text-base font-medium text-gray-800">Riwayat Percakapan</h3>
                </div>

                <!-- Conversation Content -->
                @php
                $adminResponses = is_array($ticket->admin_responses) ?
                $ticket->admin_responses :
                (json_decode($ticket->admin_responses, true) ?? []);

                $userReplies = is_array($ticket->user_replies) ?
                $ticket->user_replies :
                (json_decode($ticket->user_replies, true) ?? []);

                $allResponses = [];

                foreach($adminResponses as $response) {
                $response['type'] = 'admin';
                $allResponses[] = $response;
                }

                foreach($userReplies as $reply) {
                // Pertahankan aksi asli (confirm/reject) untuk badge — jangan tertimpa 'user'.
                $reply['action'] = $reply['type'] ?? $reply['action'] ?? null;
                $reply['type'] = 'user';
                $allResponses[] = $reply;
                }

                // Chat: urut kronologis (lama di atas, baru di bawah)
                usort($allResponses, function($a, $b) {
                return strtotime($a['timestamp']) - strtotime($b['timestamp']);
                });

                $pengajuName = $ticket->user->name ?? 'Pengaju';
                @endphp

                @include('components.conversation-history', ['responses' => $allResponses, 'pengajuName' => $pengajuName])
            </div>
        </div>
    </div>

    <!-- Admin Response Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-white px-4 py-3 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-800">
                @if($ticket->status === 'in_progress')
                Balasan Admin
                @else
                Tanggapan Admin
                @endif
            </h3>
        </div>
        <div class="p-4">
            <form action="{{ route('admin.tickets.update', $ticket) }}" method="POST" enctype="multipart/form-data"
                class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- Message Input -->
                    <div class="lg:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                            @if($ticket->status === 'in_progress')
                            Pesan Balasan
                            @else
                            Catatan Tanggapan
                            @endif
                        </label>
                        <textarea id="notes" name="notes" rows="3" required
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-colors"
                            placeholder="Ketik pesan Anda di sini...">{{ old('notes', $ticket->admin_notes) }}</textarea>
                    </div>

                    <!-- Image Upload -->
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Lampirkan Foto (Opsional)
                        </label>
                        <div class="relative border border-dashed border-gray-300 rounded-lg p-3">
                            <input type="file" name="photo" accept="image/*" class="hidden" id="photo-upload"
                                onchange="handleImagePreview(this)">
                            <label for="photo-upload" class="cursor-pointer block text-center">
                                <div class="flex flex-col items-center justify-center" id="upload-placeholder">
                                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                    <span class="mt-2 text-sm font-medium text-blue-600 hover:text-blue-700">Unggah
                                        Foto</span>
                                    <span class="mt-1 text-sm text-gray-500">Maks 5MB</span>
                                </div>
                            </label>
                            <div id="image-preview" class="hidden mt-2">
                                <img src="" alt="Preview" class="max-h-28 mx-auto rounded">
                                <button type="button" onclick="removeImage()"
                                    class="mt-2 text-sm text-red-600 hover:text-red-700">
                                    Hapus Foto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-3">
                    <div class="flex space-x-3">
                        @if($ticket->status === 'in_progress')
                        <button type="submit" name="action" value="reply"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all shadow-sm text-sm">
                            Kirim Balasan
                        </button>
                        <button type="submit" name="status" value="closed"
                            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-all shadow-sm text-sm">
                            Tutup Tiket
                        </button>
                        @else
                        <button type="submit" name="status" value="in_progress"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all shadow-sm text-sm">
                            Tandai sebagai Dalam Proses
                        </button>
                        <button type="submit" name="status" value="closed"
                            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-all shadow-sm text-sm">
                            Tutup Tiket
                        </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
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

@media (min-width: 1024px) {

    .ticket-container,
    .conversation-container {
        position: sticky;
        top: 1rem;
    }
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
    const input = document.getElementById('photo-upload');
    const preview = document.getElementById('image-preview');
    const placeholder = document.getElementById('upload-placeholder');

    input.value = '';
    preview.classList.add('hidden');
    placeholder.classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
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

    // Run when images load
    window.addEventListener('load', adjustHeight);
});

function openTicketPhoto(e, src) {
    e.preventDefault();
    const modal = document.getElementById('ticketPhotoModal');
    const img = document.getElementById('ticketPhotoModalImg');
    const link = document.getElementById('ticketPhotoModalLink');
    if (!modal || !img) {
        window.open(src, '_blank');
        return;
    }
    img.src = src;
    if (link) link.href = src;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeTicketPhoto() {
    const modal = document.getElementById('ticketPhotoModal');
    if (!modal) return;
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeTicketPhoto();
});
</script>

<!-- Lightbox Foto Tiket -->
<div id="ticketPhotoModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50" onclick="if(event.target===this)closeTicketPhoto()">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative max-w-4xl w-full">
            <button type="button" onclick="closeTicketPhoto()"
                class="absolute -top-10 right-0 text-white hover:text-gray-300 text-sm flex items-center gap-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Tutup
            </button>
            <img id="ticketPhotoModalImg" src="" alt="Foto Tiket"
                class="max-h-[85vh] w-auto mx-auto rounded-lg shadow-2xl">
            <div class="text-center mt-3">
                <a id="ticketPhotoModalLink" href="#" target="_blank" class="text-sm text-blue-300 hover:text-blue-100 underline">
                    Buka ukuran penuh di tab baru
                </a>
            </div>
        </div>
    </div>
</div>

@endsection