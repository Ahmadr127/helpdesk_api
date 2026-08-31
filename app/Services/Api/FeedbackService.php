<?php

namespace App\Services\Api;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FeedbackService
{
    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Feedback::with('user')->latest();
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search){
                $q->where('subject','like',"%{$search}%")
                  ->orWhere('message','like',"%{$search}%")
                  ->orWhereHas('user', fn($qq)=>$qq->where('name','like',"%{$search}%"));
            });
        }
        return $query->paginate($perPage);
    }

    public function listForUser(User $user, int $perPage = 15)
    {
        return Feedback::where('user_id',$user->id)->latest()->paginate($perPage);
    }

    public function create(User $user, array $data): Feedback
    {
        return Feedback::create([
            'user_id' => $user->id,
            'rating' => $data['rating'],
            'category' => $data['category'] ?? null,
            'subject' => $data['subject'],
            'message' => $data['message'],
        ]);
    }

    public function reply(Feedback $feedback, string $reply): Feedback
    {
        $feedback->update([
            'admin_reply' => $reply,
            'replied_at' => now(),
        ]);
        return $feedback->fresh()->load('user');
    }

    public function delete(Feedback $feedback): void
    {
        $feedback->delete();
    }
}
