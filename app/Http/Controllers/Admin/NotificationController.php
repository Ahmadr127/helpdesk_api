<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\NotificationSetting;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Auth::user()->notifications();
        
        // Filter by read/unread status
        if ($request->filter === 'read') {
            $query->whereNotNull('read_at');
        } elseif ($request->filter === 'unread') {
            $query->whereNull('read_at');
        }

        // Filter by date range
        if ($request->date_range) {
            switch ($request->date_range) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'week':
                    $query->where('created_at', '>=', Carbon::now()->subWeek());
                    break;
                case 'month':
                    $query->where('created_at', '>=', Carbon::now()->subMonth());
                    break;
                case 'custom':
                    if ($request->start_date && $request->end_date) {
                        $query->whereBetween('created_at', [
                            Carbon::parse($request->start_date)->startOfDay(),
                            Carbon::parse($request->end_date)->endOfDay()
                        ]);
                    }
                    break;
            }
        }

        // Auto-delete expired notifications
        $this->cleanupExpiredNotifications();

        // Allow admin to choose how many notifications per page or use a default of 8
        $perPage = $request->input('per_page', 8);
        if (!in_array($perPage, [8, 15, 30, 50])) {
            $perPage = 8;
        }
        
        $notifications = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        // Maintain all current query parameters in pagination links
        $notifications->appends($request->except('page'));
        
        $settings = Auth::user()->notificationSetting ?? new NotificationSetting();

        return view('admin.notifications.index', compact('notifications', 'settings', 'perPage'));
    }

    public function markAsRead($id)
    {
        try {
            $notification = Auth::user()->notifications()->findOrFail($id);
            $notification->markAsRead();
            
            if (request()->wantsJson()) {
                return response()->json(['success' => true]);
            }
            
            return redirect()->route('admin.tickets.show', ['ticket' => $notification->data['ticket_id']]);
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['error' => 'Notification not found'], 404);
            }
            
            return redirect()->back()->with('error', 'Notification not found');
        }
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return redirect()->back()->with('success', 'All notifications marked as read');
    }

    public function deleteOld(Request $request)
    {
        $days = $request->days ?? 7;
        $type = $request->type ?? 'read';

        if ($type === 'read') {
            Auth::user()->notifications()
                ->whereNotNull('read_at')
                ->where('created_at', '<=', Carbon::now()->subDays($days))
                ->delete();
        } elseif ($type === 'unread') {
            Auth::user()->notifications()
                ->whereNull('read_at')
                ->where('created_at', '<=', Carbon::now()->subDays($days))
                ->delete();
        } else {
            Auth::user()->notifications()
                ->where('created_at', '<=', Carbon::now()->subDays($days))
                ->delete();
        }

        return redirect()->back()->with('success', 'Old notifications deleted successfully');
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'read_expiry_days' => 'required|integer|min:1',
            'unread_expiry_days' => 'required|integer|min:1',
            'auto_delete_read' => 'boolean',
            'auto_delete_unread' => 'boolean',
        ]);

        $settings = Auth::user()->notificationSetting ?? new NotificationSetting();
        $settings->notifiable_type = get_class(Auth::user());
        $settings->notifiable_id = Auth::id();
        $settings->fill($request->only([
            'read_expiry_days',
            'unread_expiry_days',
            'auto_delete_read',
            'auto_delete_unread',
        ]));
        $settings->save();

        return redirect()->back()->with('success', 'Notification settings updated successfully');
    }

    protected function cleanupExpiredNotifications()
    {
        $settings = Auth::user()->notificationSetting;
        if (!$settings) return;

        if ($settings->auto_delete_read) {
            Auth::user()->notifications()
                ->whereNotNull('read_at')
                ->where('created_at', '<=', Carbon::now()->subDays($settings->read_expiry_days))
                ->delete();
        }

        if ($settings->auto_delete_unread) {
            Auth::user()->notifications()
                ->whereNull('read_at')
                ->where('created_at', '<=', Carbon::now()->subDays($settings->unread_expiry_days))
                ->delete();
        }
    }
} 