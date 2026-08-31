<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function markAsRead($id)
    {
        try {
            $notification = Auth::user()->notifications()->findOrFail($id);
            $notification->markAsRead();
            
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'unreadCount' => Auth::user()->unreadNotifications->count()
                ]);
            }
            
            return redirect($notification->data['url']);
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

    public function delete($id)
    {
        try {
            $notification = Auth::user()->notifications()->findOrFail($id);
            $notification->delete();
            
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'unreadCount' => Auth::user()->unreadNotifications->count()
                ]);
            }
            
            return redirect()->back()->with('success', 'Notification deleted');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['error' => 'Notification not found'], 404);
            }
            
            return redirect()->back()->with('error', 'Notification not found');
        }
    }

    public function deleteAll()
    {
        Auth::user()->notifications()->delete();
        return redirect()->back()->with('success', 'All notifications deleted');
    }
} 