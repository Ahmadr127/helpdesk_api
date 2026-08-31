<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', fn()=> new UserResource($this->user)),
            'user_id' => $this->user_id,
            'rating' => $this->rating,
            'category' => $this->category,
            'subject' => $this->subject,
            'message' => $this->message,
            'admin_reply' => $this->admin_reply,
            'replied_at' => $this->replied_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
