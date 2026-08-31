<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TicketResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'user' => $this->whenLoaded('user', fn()=> new UserResource($this->user)),
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'category' => $this->category,
            'category_relation' => $this->whenLoaded('categoryRelation'),
            'department_id' => $this->department_id,
            'department' => $this->department,
            'building_id' => $this->building_id,
            'building' => $this->building,
            'location_id' => $this->location_id,
            'location' => $this->location,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'admin_responses' => $this->admin_responses ? json_decode($this->admin_responses, true) : [],
            'user_replies' => $this->user_replies ? json_decode($this->user_replies, true) : [],
            'user_confirmation' => (bool)$this->user_confirmation,
            'user_confirmed_at' => $this->user_confirmed_at,
            'rejection_count' => $this->rejection_count,
            'last_rejection_at' => $this->last_rejection_at,
            'opened_at' => $this->opened_at,
            'in_progress_at' => $this->in_progress_at,
            'closed_at' => $this->closed_at,
            'photos' => $this->whenLoaded('photos', function(){
                return $this->photos->map(fn($p)=>[
                    'id'=>$p->id,
                    'photo_path'=>$p->photo_path,
                    'url'=> Storage::disk('public')->url($p->photo_path),
                    'type'=>$p->type,
                    'created_at'=>$p->created_at,
                ]);
            }),
            'timeline' => $this->buildTimeline(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function buildTimeline(): array
    {
        $timeline = [];

        // Created — oleh pembuat tiket
        if ($this->created_at) {
            $userName = $this->whenLoaded('user', fn() => $this->user->name, $this->user_id ? 'User' : 'System');
            // whenLoaded returns MissingValue if not loaded, fallback manual
            if ($userName instanceof \Illuminate\Http\Resources\MissingValue) {
                $userName = $this->user ? $this->user->name : 'User';
            }
            $timeline[] = [
                'at' => $this->created_at instanceof \Carbon\Carbon ? $this->created_at->toISOString() : (string) $this->created_at,
                'by' => $userName,
                'action' => 'Created',
                'notes' => $this->description,
                'is_admin' => false,
            ];
        }

        // Admin status changes — hanya yang punya status
        $adminResponses = $this->admin_responses ? json_decode($this->admin_responses, true) : [];
        if (is_array($adminResponses)) {
            foreach ($adminResponses as $r) {
                if (empty($r['status'])) continue;
                $timeline[] = [
                    'at' => isset($r['timestamp']) ? \Carbon\Carbon::parse($r['timestamp'])->toISOString() : ($this->updated_at ? $this->updated_at->toISOString() : now()->toISOString()),
                    'by' => $r['admin_name'] ?? $r['by'] ?? 'Admin IT',
                    'action' => $r['status'],
                    'notes' => $r['notes'] ?? $r['message'] ?? null,
                    'is_admin' => true,
                ];
            }
        }

        // Confirmed oleh user
        if ($this->user_confirmation && $this->user_confirmed_at) {
            $userName = $this->whenLoaded('user', fn() => $this->user->name, 'User');
            if ($userName instanceof \Illuminate\Http\Resources\MissingValue) {
                $userName = $this->user ? $this->user->name : 'User';
            }
            $at = $this->user_confirmed_at instanceof \Carbon\Carbon ? $this->user_confirmed_at->toISOString() : \Carbon\Carbon::parse($this->user_confirmed_at)->toISOString();
            $timeline[] = [
                'at' => $at,
                'by' => $userName,
                'action' => 'Confirmed',
                'notes' => 'Dikonfirmasi oleh pemohon',
                'is_admin' => false,
            ];
        } else {
            // Fallback: cari user_replies type confirm jika user_confirmation belum set
            $userReplies = $this->user_replies ? json_decode($this->user_replies, true) : [];
            if (is_array($userReplies)) {
                foreach ($userReplies as $ur) {
                    if (($ur['type'] ?? '') === 'confirm') {
                        $userName = $this->whenLoaded('user', fn() => $this->user->name, 'User');
                        if ($userName instanceof \Illuminate\Http\Resources\MissingValue) {
                            $userName = $this->user ? $this->user->name : 'User';
                        }
                        $timeline[] = [
                            'at' => isset($ur['timestamp']) ? \Carbon\Carbon::parse($ur['timestamp'])->toISOString() : now()->toISOString(),
                            'by' => $userName,
                            'action' => 'Confirmed',
                            'notes' => $ur['notes'] ?? 'Dikonfirmasi',
                            'is_admin' => false,
                        ];
                    }
                }
            }
        }

        // Sort kronologis & dedup
        usort($timeline, fn($a, $b) => strcmp($a['at'], $b['at']));
        $seen = [];
        $filtered = [];
        foreach ($timeline as $e) {
            $key = $e['at'] . '|' . $e['action'] . '|' . $e['by'];
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $filtered[] = $e;
        }
        return $filtered;
    }
}
