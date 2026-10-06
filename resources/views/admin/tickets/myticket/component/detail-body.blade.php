{{-- Isi modal detail tiket milik sendiri (dimuat via AJAX ?modal=1). Mirip halaman detail user, ringkas untuk modal. --}}
<div class="flex flex-wrap items-center gap-2">
    <span class="px-3 py-1 text-xs leading-5 font-medium rounded-full
        {{ $ticket->status === 'open' ? 'bg-blue-100 text-blue-800' :
        ($ticket->status === 'in_progress' ? 'bg-purple-100 text-purple-800' :
        ($ticket->status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
        ($ticket->status === 'closed' ? 'bg-gray-100 text-gray-800' : 'bg-green-100 text-green-800'))) }}">
        {{ $ticket->status === 'open' ? 'Dibuka' :
        ($ticket->status === 'in_progress' ? 'Dalam Proses' :
        ($ticket->status === 'pending' ? 'Menunggu' :
        ($ticket->status === 'closed' ? 'Ditutup' : 'Selesai'))) }}
    </span>
    <span class="px-3 py-1 text-xs leading-5 font-medium rounded-full
        {{ $ticket->priority === 'low' ? 'bg-gray-100 text-gray-800' :
        ($ticket->priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
        Prioritas {{ $ticket->priority === 'low' ? 'Rendah' : ($ticket->priority === 'medium' ? 'Sedang' : 'Tinggi') }}
    </span>
    <span class="text-sm font-medium text-gray-700">Tiket #{{ $ticket->ticket_number }}</span>
    <span class="text-xs text-gray-500 ml-auto">Dibuat: {{ $ticket->created_at->format('d M Y H:i') }}</span>
</div>

<div class="grid grid-cols-2 gap-3 mt-4">
    <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
        <h3 class="text-xs font-medium text-gray-500 mb-1">Kategori</h3>
        <p class="text-sm text-gray-800">{{ ucfirst($ticket->category) }}</p>
    </div>
    <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
        <h3 class="text-xs font-medium text-gray-500 mb-1">Departemen</h3>
        <p class="text-sm text-gray-800">{{ $ticket->department }}</p>
    </div>
    <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
        <h3 class="text-xs font-medium text-gray-500 mb-1">Gedung</h3>
        <p class="text-sm text-gray-800">
            @if($ticket->buildingRelation)
            {{ $ticket->buildingRelation->name }} ({{ $ticket->building }})
            @else
            {{ $ticket->building }}
            @endif
        </p>
    </div>
    <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
        <h3 class="text-xs font-medium text-gray-500 mb-1">Lokasi</h3>
        <p class="text-sm text-gray-800">{{ $ticket->location }}</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
    <div>
        <h3 class="text-xs font-medium text-gray-500 mb-1">Foto Tiket</h3>
        <div class="bg-gray-50 p-2 rounded-lg border border-gray-100 h-40 flex items-center justify-center overflow-hidden">
            @if($ticket->initialPhoto)
            <img src="{{ asset('storage/' . $ticket->initialPhoto->photo_path) }}" alt="Foto Tiket"
                class="max-w-full max-h-full object-contain rounded">
            @else
            <p class="text-xs text-gray-500">Tidak ada foto</p>
            @endif
        </div>
    </div>
    <div>
        <h3 class="text-xs font-medium text-gray-500 mb-1">Deskripsi Masalah</h3>
        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 h-40 overflow-auto">
            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $ticket->description }}</p>
        </div>
    </div>
</div>

@if($ticket->admin_notes)
<div class="mt-3">
    <h3 class="text-xs font-medium text-gray-500 mb-1">Catatan Admin</h3>
    <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
        <p class="text-sm whitespace-pre-line text-gray-800">{{ $ticket->admin_notes }}</p>
    </div>
</div>
@endif

<div class="mt-4">
    <h3 class="text-sm font-medium text-gray-800 mb-2">Riwayat Status</h3>
    <div class="bg-gray-50 rounded-lg border border-gray-100 p-3">
        <ul class="space-y-2 text-xs">
            <li class="flex justify-between"><span class="font-medium text-gray-700">Dibuat</span><span class="text-gray-500">{{ $ticket->created_at->format('d M Y H:i') }}</span></li>
            @if($ticket->in_progress_at)
            <li class="flex justify-between"><span class="font-medium text-gray-700">Dalam Proses</span><span class="text-gray-500">{{ $ticket->in_progress_at->format('d M Y H:i') }}</span></li>
            @endif
            @if($ticket->closed_at)
            <li class="flex justify-between"><span class="font-medium text-gray-700">Ditutup</span><span class="text-gray-500">{{ $ticket->closed_at->format('d M Y H:i') }}</span></li>
            @endif
            @if($ticket->user_confirmed_at)
            <li class="flex justify-between"><span class="font-medium text-gray-700">Dikonfirmasi</span><span class="text-gray-500">{{ $ticket->user_confirmed_at->format('d M Y H:i') }}</span></li>
            @endif
        </ul>
    </div>
</div>

@php
$adminResponses = is_array($ticket->admin_responses) ? $ticket->admin_responses : (json_decode($ticket->admin_responses, true) ?? []);
$userReplies = is_array($ticket->user_replies) ? $ticket->user_replies : (json_decode($ticket->user_replies, true) ?? []);
$allResponses = [];
foreach ($adminResponses as $response) { $response['type'] = 'admin'; $allResponses[] = $response; }
foreach ($userReplies as $reply) { $reply['type'] = 'user'; $allResponses[] = $reply; }
usort($allResponses, fn($a, $b) => strtotime($b['timestamp']) - strtotime($a['timestamp']));
@endphp
<div class="mt-4">
    <h3 class="text-sm font-medium text-gray-800 mb-2">Riwayat Percakapan</h3>
    @if(count($allResponses) > 0)
    <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
        @foreach($allResponses as $response)
        @if($response['type'] === 'admin')
        <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
            <div class="flex justify-between items-start mb-1">
                <div class="font-medium text-blue-800 text-xs">Admin</div>
                <div class="text-[11px] text-gray-500">{{ \Carbon\Carbon::parse($response['timestamp'])->format('d M Y H:i') }}</div>
            </div>
            <p class="text-sm whitespace-pre-line text-gray-800">{{ $response['notes'] ?? '' }}</p>
            @if(isset($response['photo']))
            <a href="{{ asset('storage/' . $response['photo']) }}" target="_blank" class="block mt-2">
                <img src="{{ asset('storage/' . $response['photo']) }}" alt="Foto respon admin" class="max-h-28 w-auto rounded border border-gray-200">
            </a>
            @endif
        </div>
        @else
        <div class="bg-green-50 p-3 rounded-lg border border-green-100">
            <div class="flex justify-between items-start mb-1">
                <div class="font-medium text-green-800 text-xs">Anda</div>
                <div class="text-[11px] text-gray-500">{{ \Carbon\Carbon::parse($response['timestamp'])->format('d M Y H:i') }}</div>
            </div>
            @if(isset($response['message']))
            <p class="text-sm whitespace-pre-line text-gray-800">{{ $response['message'] }}</p>
            @elseif(isset($response['notes']))
            <p class="text-sm whitespace-pre-line text-gray-800">{{ $response['notes'] }}</p>
            @endif
            @if(isset($response['photo']) && !empty($response['photo']))
            <a href="{{ asset('storage/' . $response['photo']) }}" target="_blank" class="block mt-2">
                <img src="{{ asset('storage/' . $response['photo']) }}" alt="Foto respon Anda" class="max-h-28 w-auto rounded border border-gray-200">
            </a>
            @endif
        </div>
        @endif
        @endforeach
    </div>
    @else
    <p class="text-xs text-gray-500">Belum ada percakapan.</p>
    @endif
</div>
