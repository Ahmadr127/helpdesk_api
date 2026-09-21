<?php

namespace App\Services\Api;

use App\Models\Building;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Models\Ticket;
use App\Models\TicketPhoto;
use App\Models\User;
use App\Notifications\TicketRespondedNotification;
use App\Support\Notifications\Notify;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TicketService
{
    public function listForUser(User $user, array $filters = [], int $perPage = 15)
    {
        $query = Ticket::where('user_id', $user->id)->with(['user', 'photos']);

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if (! empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function listForAdmin(array $filters = [], int $perPage = 15)
    {
        $query = Ticket::with(['user', 'category', 'department'])->latest();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }
        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            if ($filters['status'] === 'pending') {
                $query->where('status', 'closed')->where('user_confirmation', false);
            } else {
                $query->where('status', $filters['status']);
            }
        } else {
            // default exclude confirmed? keep all for api flexibility
        }
        if (! empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }
        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->paginate($perPage);
    }

    public function create(User $user, array $data, $photoFile = null): Ticket
    {
        if (! $user->department) {
            throw new \Exception('Mohon lengkapi data departemen Anda terlebih dahulu.');
        }

        // Robust lookup: support department_id FK, code, name, and case-insensitive match
        $userDepartment = null;
        if (! empty($user->department_id)) {
            $userDepartment = Department::find($user->department_id);
        }
        if (! $userDepartment && $user->department) {
            $deptValue = $user->department;
            $userDepartment = Department::where('code', $deptValue)->first()
                ?? Department::where('name', $deptValue)->first()
                ?? Department::whereRaw('LOWER(code) = LOWER(?)', [$deptValue])->first()
                ?? Department::whereRaw('LOWER(name) = LOWER(?)', [$deptValue])->first();
        }
        if (! $userDepartment) {
            // Last resort: try to auto-create from the raw department string (imported SIMUTU nama_unit)
            if (! empty($user->department)) {
                $deptValue = trim($user->department);
                $exists = Department::whereRaw('LOWER(code) = LOWER(?)', [$deptValue])
                    ->orWhereRaw('LOWER(name) = LOWER(?)', [$deptValue])->first();
                if ($exists) {
                    $userDepartment = $exists;
                } else {
                    try {
                        $userDepartment = Department::create([
                            'code' => strtoupper(str_replace(' ', '_', $deptValue)),
                            'name' => $deptValue,
                            'status' => 1,
                        ]);
                    } catch (\Throwable $e) {
                        \Log::warning('TicketService auto-create department failed: '.$e->getMessage());
                    }
                }
            }
        }
        if (! $userDepartment) {
            throw new \Exception('Data departemen "'.$user->department.'" tidak ditemukan di master data. Silakan perbarui departemen di pengaturan profil.');
        }

        // Gedung mengikuti departemen (single source of truth); form boleh override via building_id
        $userDepartment->loadMissing(['building', 'location']);
        $building = ! empty($data['building_id'])
            ? Building::find($data['building_id'])
            : $userDepartment->building;

        // Lokasi opsional: dari form, fallback lokasi departemen, boleh null
        $location = ! empty($data['location_id'])
            ? Location::find($data['location_id'])
            : $userDepartment->location;
        $category = Category::findOrFail($data['category_id']);

        // Validate category belongs to SIRS
        $category->load('unitProses');
        if (! $category->unitProses || $category->unitProses->code !== 'SIRS') {
            throw new \Exception('Kategori yang dipilih harus kategori dari unit SIRS.');
        }

        $ticket = DB::transaction(function () use ($user, $data, $photoFile, $userDepartment, $location, $category, $building) {
            $date = date('dm');
            $lastTicket = Ticket::where('ticket_number', 'like', "T-{$date}-%")->orderBy('ticket_number', 'desc')->first();
            if ($lastTicket) {
                $lastSequence = (int) substr($lastTicket->ticket_number, -3);
                $sequence = str_pad($lastSequence + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $sequence = '001';
            }
            $ticketNumber = "T-{$date}-{$sequence}";

            $ticket = Ticket::create([
                'user_id' => $user->id,
                'ticket_number' => $ticketNumber,
                'description' => $data['description'],
                'category_id' => $category->id,
                'category' => $category->name,
                'department_id' => $userDepartment->id,
                'department' => $userDepartment->name,
                'building_id' => $building?->id,
                'building' => $building?->name,
                'location_id' => $location?->id,
                'location' => $location?->name,
                'priority' => $data['priority'],
                'status' => 'open',
            ]);

            if ($photoFile) {
                $extension = $photoFile->getClientOriginalExtension();
                $filename = 'ticket_'.uniqid().'_'.time().'.'.$extension;
                $path = $photoFile->storeAs('ticket-photos', $filename, 'public');
                TicketPhoto::create([
                    'ticket_id' => $ticket->id,
                    'photo_path' => $path,
                    'type' => 'initial',
                ]);
            }

            return $ticket->load(['user', 'photos']);
        });

        // Simple FCM: notify Admin IT — 1 baris
        Notify::ticketToAdmins($ticket, 'ticket_created', $user);
        // DB inbox juga — agar lonceng Flutter tetap muncul meski FCM token belum ada / queue belum jalan
        $admins = User::adminIT()->get();
        foreach ($admins as $admin) {
            $admin->notify(new TicketRespondedNotification($ticket, $user, "Tiket baru #{$ticket->ticket_number}: {$ticket->description}", false, 'created'));
        }

        return $ticket;
    }

    public function updateUserTicket(User $user, Ticket $ticket, array $data, $photoFile = null): Ticket
    {
        if ($ticket->user_id !== $user->id) {
            throw new \Exception('Unauthorized', 403);
        }
        if ($ticket->status !== 'open') {
            throw new \Exception('Ticket can only be edited when in open status.', 422);
        }

        return DB::transaction(function () use ($ticket, $data, $photoFile) {
            $category = Category::findOrFail($data['category_id']);
            $department = Department::with('building')->findOrFail($data['department_id']);
            $location = ! empty($data['location_id']) ? Location::find($data['location_id']) : null;
            $building = ! empty($data['building_id'])
                ? Building::find($data['building_id'])
                : $department->building;

            $ticket->update([
                'description' => $data['description'],
                'category_id' => $category->id,
                'category' => $category->name,
                'department_id' => $department->id,
                'department' => $department->name,
                'building_id' => $building?->id,
                'building' => $building?->name,
                'location_id' => $location?->id,
                'location' => $location?->name,
                'priority' => $data['priority'],
            ]);

            if ($photoFile) {
                $oldPhoto = $ticket->photos()->where('type', 'initial')->first();
                if ($oldPhoto) {
                    Storage::disk('public')->delete($oldPhoto->photo_path);
                    $oldPhoto->delete();
                }
                $extension = $photoFile->getClientOriginalExtension();
                $filename = 'ticket_'.uniqid().'_'.time().'.'.$extension;
                $path = $photoFile->storeAs('ticket-photos', $filename, 'public');
                TicketPhoto::create([
                    'ticket_id' => $ticket->id,
                    'photo_path' => $path,
                    'type' => 'initial',
                ]);
            }

            return $ticket->fresh()->load(['user', 'photos']);
        });
    }

    public function deleteUserTicket(User $user, Ticket $ticket): void
    {
        if ($ticket->user_id !== $user->id) {
            throw new \Exception('Unauthorized', 403);
        }
        if ($ticket->status !== 'open') {
            throw new \Exception('Only open tickets can be deleted.', 422);
        }
        $ticket->delete();
    }

    public function reply(User $user, Ticket $ticket, string $message, $photoFile = null): Ticket
    {
        if ($ticket->user_id !== $user->id) {
            throw new \Exception('Unauthorized', 403);
        }

        $reply = [
            'message' => $message,
            'timestamp' => now(),
        ];

        if ($photoFile) {
            $path = $photoFile->store('ticket-replies', 'public');
            $reply['photo'] = $path;
            TicketPhoto::create([
                'ticket_id' => $ticket->id,
                'photo_path' => $path,
                'type' => 'user_response',
            ]);
        }

        $replies = $ticket->user_replies ? json_decode($ticket->user_replies, true) : [];
        $replies[] = $reply;

        $ticket->update([
            'user_replies' => json_encode($replies),
        ]);

        // Notify admins (DB)
        $admins = User::adminIT()->get();
        foreach ($admins as $admin) {
            $admin->notify(new TicketRespondedNotification($ticket, $user, "User has replied to ticket #{$ticket->ticket_number}", false, 'replied'));
        }

        // FCM 1 baris — ke Admin IT
        Notify::ticketToAdmins($ticket->fresh(), 'ticket_replied', $user);

        return $ticket->fresh();
    }

    public function confirm(User $user, Ticket $ticket, string $notes, string $action, $photoFile = null): Ticket
    {
        if ($ticket->user_id !== $user->id) {
            throw new \Exception('Unauthorized', 403);
        }

        $replies = $ticket->user_replies ? json_decode($ticket->user_replies, true) : [];
        $reply = [
            'type' => $action,
            'notes' => $notes,
            'timestamp' => now()->toDateTimeString(),
        ];

        if ($photoFile) {
            $path = $photoFile->store('ticket-responses', 'public');
            $reply['photo'] = $path;
            TicketPhoto::create([
                'ticket_id' => $ticket->id,
                'photo_path' => $path,
                'type' => $action === 'confirm' ? 'user_response' : 'user_rejection',
            ]);
        }

        $replies[] = $reply;
        $ticket->user_replies = json_encode($replies);

        if ($action === 'confirm') {
            $ticket->status = 'confirmed';
            $ticket->user_confirmation = true;
            $ticket->user_confirmed_at = now();
            $ticket->save();
            $admins = User::adminIT()->get();
            foreach ($admins as $admin) {
                $admin->notify(new TicketRespondedNotification($ticket, $user, "User has confirmed ticket #{$ticket->ticket_number} as completed", false, 'confirmed'));
            }
            Notify::ticketToAdmins($ticket->fresh(), 'ticket_confirmed', $user);
        } else {
            $ticket->status = 'in_progress';
            $ticket->user_confirmation = false;
            $ticket->rejection_count = $ticket->rejection_count + 1;
            $ticket->last_rejection_at = now();
            $ticket->save();
            $admins = User::adminIT()->get();
            foreach ($admins as $admin) {
                $admin->notify(new TicketRespondedNotification($ticket, $user, "User has rejected ticket #{$ticket->ticket_number}", false, 'rejected'));
            }
            Notify::ticketToAdmins($ticket->fresh(), 'ticket_rejected', $user);
        }

        return $ticket->fresh();
    }

    public function adminRespond(User $admin, Ticket $ticket, string $notes, string $status, $photoFile = null): Ticket
    {
        $response = [
            'notes' => $notes,
            'timestamp' => now(),
            'status' => $status,
        ];

        $responses = json_decode($ticket->admin_responses, true) ?? [];

        if ($photoFile) {
            $path = $photoFile->store('ticket-responses', 'public');
            TicketPhoto::create([
                'ticket_id' => $ticket->id,
                'photo_path' => $path,
                'type' => 'admin_response',
            ]);
            $response['photo'] = $path;
        }

        $responses[] = $response;

        $ticket->update([
            'admin_responses' => json_encode($responses),
            'status' => $status,
            'in_progress_at' => $status === 'in_progress' ? now() : $ticket->in_progress_at,
            'closed_at' => $status === 'closed' ? now() : $ticket->closed_at,
        ]);

        $ticket->user->notify(new TicketRespondedNotification($ticket, $admin, $notes, true, 'responded'));

        // FCM ke pengaju
        Notify::ticketToUser($ticket->fresh(), 'ticket_responded', $admin, $notes);

        return $ticket->fresh();
    }

    public function adminUpdate(User $admin, Ticket $ticket, string $notes, ?string $status, ?string $action, $photoFile = null): Ticket
    {
        return DB::transaction(function () use ($admin, $ticket, $notes, $status, $action, $photoFile) {
            $responses = $ticket->admin_responses ? json_decode($ticket->admin_responses, true) : [];
            $timestamp = now();
            $response = [
                'notes' => $notes,
                'timestamp' => $timestamp->toDateTimeString(),
            ];
            if ($photoFile) {
                $path = $photoFile->store('ticket-responses', 'public');
                TicketPhoto::create([
                    'ticket_id' => $ticket->id,
                    'photo_path' => $path,
                    'type' => 'admin_response',
                    'created_at' => $timestamp,
                ]);
                $response['photo'] = $path;
            }
            $responses[] = $response;

            if ($action === 'reply') {
                $ticket->update(['admin_responses' => json_encode($responses)]);
            } else {
                $ticket->update([
                    'admin_responses' => json_encode($responses),
                    'status' => $status,
                    'in_progress_at' => $status === 'in_progress' ? $timestamp : $ticket->in_progress_at,
                    'closed_at' => $status === 'closed' ? $timestamp : $ticket->closed_at,
                ]);
            }

            $ticket->user->notify(new TicketRespondedNotification($ticket, $admin, $notes, true, $action === 'reply' ? 'replied' : 'updated'));
            $event = $action === 'reply' ? 'ticket_replied' : 'ticket_updated';
            Notify::ticketToUser($ticket->fresh(), $event, $admin, $notes);

            return $ticket->fresh();
        });
    }
}
