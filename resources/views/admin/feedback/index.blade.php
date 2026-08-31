@extends('admin.layouts.app')

@section('title', 'Feedback')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold mb-6">Feedback Management</h2>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rating</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reply</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($feedback as $item)
                    <tr>
                        <td class="px-6 py-4">{{ $item->user->name }}</td>
                        <td class="px-6 py-4">
                            <div class="flex text-yellow-400">
                                @for($i = 1; $i <= 5; $i++) <span
                                    class="{{ $i <= $item->rating ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                                    @endfor
                            </div>
                        </td>
                        <td class="px-6 py-4">{{ $item->category }}</td>
                        <td class="px-6 py-4">{{ $item->subject }}</td>
                        <td class="px-6 py-4">{{ Str::limit($item->message, 50) }}</td>
                        <td class="px-6 py-4">
                            <form action="{{ route('admin.feedback.reply', $item) }}" method="POST" class="flex gap-2">
                                @csrf
                                <input type="text" name="admin_reply" class="rounded-md border-gray-300 shadow-sm"
                                    placeholder="Type your reply..." value="{{ $item->admin_reply }}">
                                <button type="submit"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                    Reply
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-4">
                            <form action="{{ route('admin.feedback.destroy', $item) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $feedback->links() }}
        </div>
    </div>
</div>
@endsection