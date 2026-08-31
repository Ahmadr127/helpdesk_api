<?php

namespace App\Services\Api;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserService
{
    public function list(array $filters = [], int $perPage = 15)
    {
        $query = User::query();
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search){
                $q->where('name','like',"%{$search}%")
                  ->orWhere('email','like',"%{$search}%")
                  ->orWhere('phone','like',"%{$search}%");
            });
        }
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        return $query->latest()->paginate($perPage);
    }

    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'department' => $data['department'],
            'status' => (int)$data['status'],
            'phone' => $data['phone'],
            'position' => $data['position'],
        ]);
    }

    public function update(User $user, array $data): User
    {
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'department' => $data['department'],
            'status' => (int)$data['status'],
            'phone' => $data['phone'],
            'position' => $data['position'],
        ];
        if (!empty($data['password'])) {
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
        // Different stats per role
        if ($user->role === 'admin' && strtolower($user->position) === 'it') {
            return [
                'users' => User::count(),
                'tickets_total' => \App\Models\Ticket::count(),
                'tickets_open' => \App\Models\Ticket::where('status','open')->count(),
                'tickets_in_progress' => \App\Models\Ticket::where('status','in_progress')->count(),
                'tickets_confirmed' => \App\Models\Ticket::where('status','confirmed')->count(),
                'orders_total' => \App\Models\OrderPerbaikan::count(),
            ];
        }
        // default user stats
        return [
            'my_tickets' => \App\Models\Ticket::where('user_id',$user->id)->count(),
            'my_orders' => \App\Models\OrderPerbaikan::where('created_by',$user->id)->count(),
        ];
    }
}
