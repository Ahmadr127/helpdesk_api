<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationInboxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $notifications = $user->notifications()->latest()->paginate($perPage);
        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
            'unread_count' => $unreadCount,
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if (! $notification) {
            return response()->json(['success' => false, 'message' => 'Notifikasi tidak ditemukan'], 404);
        }

        $notification->markAsRead();

        return response()->json(['success' => true, 'message' => 'Ditandai sudah dibaca']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true, 'message' => 'Semua ditandai sudah dibaca']);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if (! $notification) {
            return response()->json(['success' => false, 'message' => 'Notifikasi tidak ditemukan'], 404);
        }

        $notification->delete();

        return response()->json(['success' => true, 'message' => 'Notifikasi dihapus']);
    }

    public function deleteOld(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:read,unread,all',
            'days' => 'sometimes|integer|min:1|max:365',
        ]);
        $type = $validated['type'] ?? 'read';
        $days = (int) ($validated['days'] ?? 7);
        $cutoff = now()->subDays($days);
        $query = $request->user()->notifications()->where('created_at', '<=', $cutoff);
        if ($type === 'read') {
            $query->whereNotNull('read_at');
        } elseif ($type === 'unread') {
            $query->whereNull('read_at');
        }
        $deleted = $query->delete();
        return response()->json(['success' => true, 'message' => "$deleted notifikasi lama dihapus", 'deleted' => $deleted]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'read_expiry_days' => 'required|integer|min:1|max:365',
            'unread_expiry_days' => 'required|integer|min:1|max:365',
            'auto_delete_read' => 'sometimes|boolean',
            'auto_delete_unread' => 'sometimes|boolean',
        ]);
        $user = $request->user();
        $settings = \App\Models\NotificationSetting::firstOrNew([
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
        ]);
        $settings->fill($validated);
        $settings->save();
        return response()->json(['success' => true, 'message' => 'Pengaturan notifikasi diperbarui', 'data' => $settings]);
    }
}
