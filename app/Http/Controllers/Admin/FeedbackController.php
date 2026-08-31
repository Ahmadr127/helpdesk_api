<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index()
    {
        $feedback = Feedback::with('user')
                          ->orderBy('created_at', 'desc')
                          ->paginate(10);
        return view('admin.feedback.index', compact('feedback'));
    }

    public function reply(Request $request, Feedback $feedback)
    {
        $validated = $request->validate([
            'admin_reply' => 'required|string'
        ]);

        $feedback->update([
            'admin_reply' => $validated['admin_reply'],
            'replied_at' => now()
        ]);

        return redirect()->back()->with('success', 'Balasan berhasil dikirim');
    }

    public function destroy(Feedback $feedback)
    {
        $feedback->delete();
        return redirect()->route('admin.feedback.index')
            ->with('success', 'Feedback deleted successfully');
    }
} 