<?php

namespace App\Services\Api;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function list(array $filters = [], int $perPage = 15)
    {
        $query = User::query();
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $like = (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('name', $like, "%{$search}%")
                    ->orWhere('email', $like, "%{$search}%")
                    ->orWhere('username', $like, "%{$search}%")
                    ->orWhere('phone', $like, "%{$search}%")
                    ->orWhere('department', $like, "%{$search}%")
                    ->orWhere('position', $like, "%{$search}%");
            });
        }
        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['department'])) {
            $deptFilter = $filters['department'];
            // department filter bisa code (UNIT007) atau name (Rawat jalan) — handle keduanya
            $dept = \App\Models\Department::where('code', $deptFilter)->orWhere('name', $deptFilter)->first();
            $deptName = $dept ? $dept->name : $deptFilter;
            $deptCode = $dept ? $dept->code : null;
            $query->where(function ($q) use ($deptFilter, $deptName, $deptCode) {
                $like = (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') ? 'ilike' : 'like';
                $q->where('department', $like, "%{$deptFilter}%")
                  ->orWhere('department', $like, "%{$deptName}%");
                if ($deptCode) $q->orWhere('department', $deptCode);
                // juga via department_id jika ada
                if ($dept && isset($dept->id)) $q->orWhere('department_id', $dept->id);
            });
        }
        if (! empty($filters['position'])) {
            $posFilter = $filters['position'];
            $like = (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') ? 'ilike' : 'like';
            $query->where('position', $like, "%{$posFilter}%");
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'username' => $this->resolveUsername($data['email'], $data['username'] ?? null),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'department' => $data['department'],
            'status' => (int) $data['status'],
            'phone' => $data['phone'],
            'position' => $data['position'] ?? null,
        ]);
    }

    public function update(User $user, array $data): User
    {
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'department' => $data['department'],
            'status' => (int) $data['status'],
            'phone' => $data['phone'],
            'position' => $data['position'] ?? null,
        ];
        if (! empty($data['username'])) {
            $payload['username'] = $data['username'];
        } elseif (empty($user->username)) {
            $payload['username'] = $this->resolveUsername($data['email'], null, $user->id);
        }
        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }
        $user->update($payload);

        return $user->fresh();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function dashboardStats(User $user): array
    {
        if ($user->hasAnyPermission(['admin.dashboard', 'ticket.manage'])) {
            return [
                'users' => User::count(),
                'tickets_total' => \App\Models\Ticket::count(),
                'tickets_open' => \App\Models\Ticket::where('status', 'open')->count(),
                'tickets_in_progress' => \App\Models\Ticket::where('status', 'in_progress')->count(),
                'tickets_confirmed' => \App\Models\Ticket::where('status', 'confirmed')->count(),
                'orders_total' => \App\Models\OrderPerbaikan::count(),
            ];
        }

        // default user stats
        return [
            'my_tickets' => \App\Models\Ticket::where('user_id', $user->id)->count(),
            'my_orders' => \App\Models\OrderPerbaikan::where('created_by', $user->id)->count(),
        ];
    }

    private function resolveUsername(string $email, ?string $wanted, ?int $ignoreId = null): string
    {
        if (! empty($wanted)) {
            return $wanted;
        }
        $base = preg_replace('/[^A-Za-z0-9._-]/', '', strtolower(explode('@', $email)[0]));
        $username = $base;
        $i = 1;
        while (User::where('username', $username)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $username = $base.$i++;
        }

        return $username;
    }
}
