<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'category' => 'required|string|in:SIRS,IPSRS,Other',
            'subject' => 'required|string|max:255',
            'message' => 'required|string'
        ]);

        $feedback = Feedback::create([
            'user_id' => auth()->id(),
            'rating' => $validated['rating'],
            'category' => $validated['category'],
            'subject' => $validated['subject'],
            'message' => $validated['message']
        ]);

        return redirect()->back()->with('success', 'Terima kasih atas feedback Anda!');
    }

    public function getUserFeedback()
    {
        $feedback = Feedback::where('user_id', auth()->id())
                           ->orderBy('created_at', 'desc')
                           ->get();
        return $feedback;
    }
} 