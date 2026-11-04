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
        if (! empty($filters['ticket_number'])) {
            $query->where('ticket_number', 'like', '%'.$filters['ticket_number'].'%');
        }
        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (! empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }
        if (! empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Seluruh tiket milik user sebagai collection (dipakai halaman web yang
     * melakukan grouping/filter di sisi view).
     */
    public function allForUser(User $user)
    {
        return Ticket::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function listForAdmin(array $filters = [], int $perPage = 15)
    {
        $query = Ticket::with(['user']);

        if (! empty($filters['exclude_confirmed'])) {
            $query->whereNotIn('status', ['confirmed']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $status = $filters['status'] ?? null;
        if (is_array($status)) {
            $query->whereIn('status', $status);
        } elseif (! empty($status) && $status !== 'all') {
            if ($status === 'pending') {
                $query->where('status', 'closed')->where('user_confirmation', false);
            } else {
                $query->where('status', $status);
            }
        }

        if (! empty($filters['confirmation'])) {
            if ($filters['confirmation'] === 'confirmed') {
                $query->where('status', 'confirmed');
            } elseif ($filters['confirmation'] === 'pending') {
                $query->where('status', 'closed')->where('user_confirmation', false);
            }
        }

        if (array_key_exists('user_confirmation', $filters)) {
            $query->where('user_confirmation', (bool) $filters['user_confirmation']);
        }

        $start = $filters['start_date'] ?? $filters['date_from'] ?? null;
        $end = $filters['end_date'] ?? $filters['date_to'] ?? null;
        if (! empty($start)) {
            $query->whereDate('created_at', '>=', $start);
        }
        if (! empty($end)) {
            $query->whereDate('created_at', '<=', $end);
        }

        if (! empty($filters['month'])) {
            $query->whereMonth('created_at', $filters['month'])
                ->whereYear('created_at', $filters['year'] ?? now()->year);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (($filters['sort'] ?? null) === 'priority') {
            return $query->orderByRaw("CASE WHEN status = 'open' THEN 1 WHEN status = 'in_progress' THEN 2 WHEN status = 'closed' THEN 3 ELSE 4 END")
                ->orderByRaw("CASE WHEN priority = 'high' THEN 1 WHEN priority = 'medium' THEN 2 WHEN priority = 'low' THEN 3 ELSE 4 END")
                ->orderByDesc('created_at')
                ->paginate($perPage);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Riwayat tiket yang sudah dikonfirmasi user.
     */
    public function history(array $filters = [], int $perPage = 15)
    {
        $query = Ticket::with(['user'])
            ->where('status', 'confirmed')
            ->where('user_confirmation', true);

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('user_confirmed_at', 'desc')->paginate($perPage);
    }

    public function statistics(): array
    {
        return [
            'totalTickets' => Ticket::count(),
            'openTickets' => Ticket::where('status', 'open')->count(),
            'inProgressTickets' => Ticket::where('status', 'in_progress')->count(),
            'closedTickets' => Ticket::whereIn('status', ['closed', 'confirmed'])->count(),
            'pendingConfirmationTickets' => Ticket::where('status', 'closed')
                ->where('user_confirmation', false)
                ->count(),
        ];
    }

    public function userStatistics(User $user): array
    {
        return [
            'totalTickets' => Ticket::where('user_id', $user->id)->count(),
            'openTickets' => Ticket::where('user_id', $user->id)->where('status', 'open')->count(),
            'inProgressTickets' => Ticket::where('user_id', $user->id)->where('status', 'in_progress')->count(),
            'closedTickets' => Ticket::where('user_id', $user->id)->where('status', 'closed')->count(),
            'confirmedTickets' => Ticket::where('user_id', $user->id)->where('status', 'confirmed')->count(),
        ];
    }

    public function create(User $user, array $data, $photoFiles = null): Ticket
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

        // Normalisasi ke array (maks 3)
        if ($photoFiles instanceof \Illuminate\Http\UploadedFile) {
            $photoFiles = [$photoFiles];
        } elseif (! is_array($photoFiles)) {
            $photoFiles = $photoFiles ? [$photoFiles] : [];
        }
        $photoFiles = array_slice(array_values(array_filter($photoFiles)), 0, 3);

        $ticket = DB::transaction(function () use ($user, $data, $photoFiles, $userDepartment, $location, $category, $building) {
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

            foreach ($photoFiles as $photoFile) {
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

        // Pengajuan gabungan: buat Order Perbaikan bila kategori tiket termasuk target sinkron.
        try {
            app(\App\Services\PengajuanSinkronService::class)->createCounterpartForTicket($user, $ticket);
        } catch (\Throwable $e) {
            \Log::warning('Sinkron pengajuan dari tiket gagal: '.$e->getMessage());
        }

        return $ticket;
    }

    public function updateUserTicket(User $user, Ticket $ticket, array $data, $photoFiles = null): Ticket
    {
        if ($ticket->user_id !== $user->id) {
            throw new \Exception('Unauthorized', 403);
        }
        if ($ticket->status !== 'open') {
            throw new \Exception('Ticket can only be edited when in open status.', 422);
        }

        if ($photoFiles instanceof \Illuminate\Http\UploadedFile) {
            $photoFiles = [$photoFiles];
        } elseif (! is_array($photoFiles)) {
            $photoFiles = $photoFiles ? [$photoFiles] : [];
        }
        $photoFiles = array_slice(array_values(array_filter($photoFiles)), 0, 3);

        return DB::transaction(function () use ($ticket, $data, $photoFiles) {
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

            if (! empty($photoFiles)) {
                $oldPhotos = $ticket->photos()->where('type', 'initial')->get();
                foreach ($oldPhotos as $oldPhoto) {
                    Storage::disk('public')->delete($oldPhoto->photo_path);
                    $oldPhoto->delete();
                }
                foreach ($photoFiles as $photoFile) {
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
                }
            }

            return $ticket->fresh()->load(['user', 'photos']);
        });
    }

    public function updateAdminTicket(User $admin, Ticket $ticket, array $data, $photoFiles = null, array $pjIds = []): Ticket
    {
        if ($photoFiles instanceof \Illuminate\Http\UploadedFile) {
            $photoFiles = [$photoFiles];
        } elseif (! is_array($photoFiles)) {
            $photoFiles = $photoFiles ? [$photoFiles] : [];
        }
        $photoFiles = array_slice(array_values(array_filter($photoFiles)), 0, 3);

        return DB::transaction(function () use ($admin, $ticket, $data, $photoFiles, $pjIds) {
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

            if (! empty($photoFiles)) {
                $oldPhotos = $ticket->photos()->where('type', 'initial')->get();
                foreach ($oldPhotos as $oldPhoto) {
                    Storage::disk('public')->delete($oldPhoto->photo_path);
                    $oldPhoto->delete();
                }
                foreach ($photoFiles as $photoFile) {
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
                }
            }

            $ids = empty($pjIds) ? [$admin->id] : array_values(array_unique($pjIds));
            $ticket->penanggungJawab()->sync($ids);

            return $ticket->fresh()->load(['user', 'photos', 'penanggungJawab']);
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

    public function reply(User $user, Ticket $ticket, string $message, $photoFiles = null): Ticket
    {
        if ($ticket->user_id !== $user->id) {
            throw new \Exception('Unauthorized', 403);
        }

        if ($photoFiles instanceof \Illuminate\Http\UploadedFile) {
            $photoFiles = [$photoFiles];
        } elseif (! is_array($photoFiles)) {
            $photoFiles = $photoFiles ? [$photoFiles] : [];
        }
        $photoFiles = array_slice(array_values(array_filter($photoFiles)), 0, 3);

        $reply = [
            'message' => $message,
            'timestamp' => now(),
        ];

        $paths = [];
        foreach ($photoFiles as $photoFile) {
            if ($photoFile) {
                $path = $photoFile->store('ticket-replies', 'public');
                $paths[] = $path;
                TicketPhoto::create([
                    'ticket_id' => $ticket->id,
                    'photo_path' => $path,
                    'type' => 'user_response',
                ]);
            }
        }
        if (! empty($paths)) {
            $reply['photo'] = count($paths) === 1 ? $paths[0] : $paths;
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

    public function confirm(User $user, Ticket $ticket, string $notes, string $action, $photoFiles = null): Ticket
    {
        if ($ticket->user_id !== $user->id) {
            throw new \Exception('Unauthorized', 403);
        }

        if ($photoFiles instanceof \Illuminate\Http\UploadedFile) {
            $photoFiles = [$photoFiles];
        } elseif (! is_array($photoFiles)) {
            $photoFiles = $photoFiles ? [$photoFiles] : [];
        }
        $photoFiles = array_slice(array_values(array_filter($photoFiles)), 0, 3);

        $replies = $ticket->user_replies ? json_decode($ticket->user_replies, true) : [];
        $reply = [
            'type' => $action,
            'notes' => $notes,
            'timestamp' => now()->toDateTimeString(),
        ];

        $paths = [];
        foreach ($photoFiles as $photoFile) {
            if ($photoFile) {
                $path = $photoFile->store('ticket-responses', 'public');
                $paths[] = $path;
                TicketPhoto::create([
                    'ticket_id' => $ticket->id,
                    'photo_path' => $path,
                    'type' => $action === 'confirm' ? 'user_response' : 'user_rejection',
                ]);
            }
        }
        if (! empty($paths)) {
            $reply['photo'] = count($paths) === 1 ? $paths[0] : $paths;
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

        $ticket = $ticket->fresh();
        try {
            app(\App\Services\PengajuanSinkronService::class)->syncFromTicket($ticket, $user, 'user-confirm');
        } catch (\Throwable $e) {
            \Log::warning('Sinkron status tiket gagal: '.$e->getMessage());
        }

        return $ticket;
    }

    public function adminRespond(User $admin, Ticket $ticket, string $notes, string $status, $photoFiles = null): Ticket
    {
        if ($photoFiles instanceof \Illuminate\Http\UploadedFile) {
            $photoFiles = [$photoFiles];
        } elseif (! is_array($photoFiles)) {
            $photoFiles = $photoFiles ? [$photoFiles] : [];
        }
        $photoFiles = array_slice(array_values(array_filter($photoFiles)), 0, 3);

        $response = [
            'notes' => $notes,
            'timestamp' => now(),
            'status' => $status,
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
        ];

        $responses = json_decode($ticket->admin_responses, true) ?? [];

        $paths = [];
        foreach ($photoFiles as $photoFile) {
            if ($photoFile) {
                $path = $photoFile->store('ticket-responses', 'public');
                $paths[] = $path;
                TicketPhoto::create([
                    'ticket_id' => $ticket->id,
                    'photo_path' => $path,
                    'type' => 'admin_response',
                ]);
            }
        }
        if (! empty($paths)) {
            $response['photo'] = count($paths) === 1 ? $paths[0] : $paths;
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

        $ticket = $ticket->fresh();
        try {
            app(\App\Services\PengajuanSinkronService::class)->syncFromTicket($ticket, $admin, 'admin-respond');
        } catch (\Throwable $e) {
            \Log::warning('Sinkron status tiket gagal: '.$e->getMessage());
        }

        return $ticket;
    }

    public function adminUpdate(User $admin, Ticket $ticket, string $notes, ?string $status, ?string $action, $photoFiles = null): Ticket
    {
        if ($photoFiles instanceof \Illuminate\Http\UploadedFile) {
            $photoFiles = [$photoFiles];
        } elseif (! is_array($photoFiles)) {
            $photoFiles = $photoFiles ? [$photoFiles] : [];
        }
        $photoFiles = array_slice(array_values(array_filter($photoFiles)), 0, 3);

        $ticket = DB::transaction(function () use ($admin, $ticket, $notes, $status, $action, $photoFiles) {
            $responses = $ticket->admin_responses ? json_decode($ticket->admin_responses, true) : [];
            $timestamp = now();
            $response = [
                'notes' => $notes,
                'timestamp' => $timestamp->toDateTimeString(),
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
            ];
            $paths = [];
            foreach ($photoFiles as $photoFile) {
                if ($photoFile) {
                    $path = $photoFile->store('ticket-responses', 'public');
                    $paths[] = $path;
                    TicketPhoto::create([
                        'ticket_id' => $ticket->id,
                        'photo_path' => $path,
                        'type' => 'admin_response',
                        'created_at' => $timestamp,
                    ]);
                }
            }
            if (! empty($paths)) {
                $response['photo'] = count($paths) === 1 ? $paths[0] : $paths;
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

        try {
            app(\App\Services\PengajuanSinkronService::class)->syncFromTicket($ticket, $admin, 'admin-update');
        } catch (\Throwable $e) {
            \Log::warning('Sinkron status tiket gagal: '.$e->getMessage());
        }

        return $ticket;
    }

/**
     * Auto-confirm ticket yang sudah 'pending' (admin closed) lebih dari $hours jam
     * tanpa konfirmasi user. Dipanggil oleh scheduler (system actor, tanpa auth).
     */
    public function autoConfirmStale(int $hours = 24): int
    {
        $threshold = now()->subHours($hours);
        $tickets = Ticket::where('status', 'pending')
            ->where('user_confirmation', false)
            ->where('closed_at', '<=', $threshold)
            ->get();

        $count = 0;
        foreach ($tickets as $ticket) {
            DB::transaction(function () use ($ticket) {
                $ticket->status = 'confirmed';
                $ticket->user_confirmation = true;
                $ticket->user_confirmed_at = now();
                $ticket->save();
            });

            try {
                $user = $ticket->user ?? User::find($ticket->user_id);
                if ($user) {
                    $user->notify(new \App\Notifications\TicketRespondedNotification($ticket, $user, "Auto-confirm setelah 24 jam tanpa konfirmasi user", false, 'confirmed'));
                }
                $admins = User::adminIT()->get();
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\TicketRespondedNotification($ticket, $admin, "Auto-confirm setelah 24 jam tanpa konfirmasi user", true, 'confirmed'));
                }
                Notify::ticketToAdmins($ticket->fresh(), 'ticket_confirmed', $user ?? $admins->first());
            } catch (\Throwable $e) {
                \Log::warning('Gagal kirim notif auto-confirm ticket', [
                    'ticket_id' => $ticket->id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                app(\App\Services\PengajuanSinkronService::class)->syncFromTicket($ticket->fresh(), null, 'auto-confirm');
            } catch (\Throwable $e) {
                \Log::warning('Sinkron status tiket gagal: '.$e->getMessage());
            }

            $count++;
        }

        return $count;
    }
}
