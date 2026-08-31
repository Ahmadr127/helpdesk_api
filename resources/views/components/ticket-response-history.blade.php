<div class="mt-8 bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold">Response History</h3>
        <button id="toggleHistory" class="text-blue-600 hover:text-blue-800 flex items-center">
            <span class="mr-2">Show History</span>
            <svg class="w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
    </div>

    <div id="historyContent" class="space-y-4 hidden">
        <!-- Initial Ticket -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
            <div class="flex justify-between items-start mb-3">
                <span class="px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-800">
                    Initial Ticket
                </span>
                <span class="text-sm text-gray-500">
                    {{ $ticket->created_at->format('d M Y H:i') }}
                </span>
            </div>
            <p class="text-gray-700 mb-3">{{ $ticket->description }}</p>
            @if($ticket->initialPhoto)
            <div class="mt-3">
                <a href="{{ Storage::url($ticket->initialPhoto->photo_path) }}" target="_blank"
                    class="block max-w-xs overflow-hidden rounded-lg shadow-sm hover:opacity-90">
                    <img src="{{ Storage::url($ticket->initialPhoto->photo_path) }}" alt="Initial Ticket Photo"
                        class="w-full h-auto">
                </a>
            </div>
            @endif
        </div>

        <!-- Combined Responses (Admin & User) -->
        @php
        $allResponses = collect();

        // Add admin responses
        if($ticket->admin_responses) {
        foreach($ticket->admin_responses as $response) {
        $allResponses->push([
        'type' => 'admin',
        'content' => $response,
        'timestamp' => \Carbon\Carbon::parse($response['timestamp'])
        ]);
        }
        }

        // Add user replies
        if($ticket->user_replies) {
        foreach(json_decode($ticket->user_replies, true) as $reply) {
        $allResponses->push([
        'type' => 'user',
        'content' => $reply,
        'timestamp' => \Carbon\Carbon::parse($reply['timestamp'])
        ]);
        }
        }

        // Sort all responses by timestamp (newest first)
        $allResponses = $allResponses->sortByDesc('timestamp');
        @endphp

        @foreach($allResponses as $response)
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
            <div class="flex justify-between items-start mb-3">
                <span class="px-3 py-1 text-sm rounded-full 
                    @if($response['type'] === 'admin')
                        bg-purple-100 text-purple-800
                    @else
                        @if(isset($response['content']['type']))
                            {{ $response['content']['type'] === 'confirm' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}
                        @else
                            bg-blue-100 text-blue-800
                        @endif
                    @endif">
                    @if($response['type'] === 'admin')
                    Admin Response
                    @else
                    @if(isset($response['content']['type']))
                    {{ ucfirst($response['content']['type']) }} Response
                    @else
                    User Reply
                    @endif
                    @endif
                </span>
                <span class="text-sm text-gray-500">
                    {{ $response['timestamp']->format('d M Y H:i') }}
                </span>
            </div>

            <p class="text-gray-700 mb-3">
                @if($response['type'] === 'admin')
                {{ $response['content']['notes'] }}
                @else
                {{ isset($response['content']['notes']) ? $response['content']['notes'] : $response['content']['message'] }}
                @endif
            </p>

            @if(isset($response['content']['photo']))
            <div class="mt-3">
                <a href="{{ Storage::url($response['content']['photo']) }}" target="_blank"
                    class="block max-w-xs overflow-hidden rounded-lg shadow-sm hover:opacity-90">
                    <img src="{{ Storage::url($response['content']['photo']) }}" alt="Response Photo"
                        class="w-full h-auto">
                </a>
            </div>
            @endif
        </div>
        @endforeach

        <!-- Timeline Events -->
        @if($ticket->in_progress_at)
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
            <div class="flex justify-between items-start mb-3">
                <span class="px-3 py-1 text-sm rounded-full bg-yellow-100 text-yellow-800">
                    Status Changed: In Progress
                </span>
                <span class="text-sm text-gray-500">
                    {{ $ticket->in_progress_at->format('d M Y H:i') }}
                </span>
            </div>
        </div>
        @endif

        @if($ticket->closed_at)
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
            <div class="flex justify-between items-start mb-3">
                <span class="px-3 py-1 text-sm rounded-full bg-gray-100 text-gray-800">
                    Status Changed: Closed
                </span>
                <span class="text-sm text-gray-500">
                    {{ $ticket->closed_at->format('d M Y H:i') }}
                </span>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleButton = document.getElementById('toggleHistory');
    const historyContent = document.getElementById('historyContent');
    const arrowIcon = toggleButton.querySelector('svg');

    toggleButton.addEventListener('click', function() {
        historyContent.classList.toggle('hidden');
        arrowIcon.classList.toggle('rotate-180');

        const isHidden = historyContent.classList.contains('hidden');
        toggleButton.querySelector('span').textContent = isHidden ? 'Show History' : 'Hide History';
    });
});
</script>
@endpush