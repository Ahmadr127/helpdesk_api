<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Group Dashboard
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'group' => 'Dashboard', 'description' => 'Akses dashboard'],
            // Tickets (SIRS) - IT
            ['name' => 'View Tickets', 'slug' => 'ticket.view', 'group' => 'Tiket IT (SIRS)', 'description' => 'Lihat tiket sendiri'],
            ['name' => 'Create Ticket', 'slug' => 'ticket.create', 'group' => 'Tiket IT (SIRS)', 'description' => 'Buat tiket baru'],
            ['name' => 'Edit Own Ticket', 'slug' => 'ticket.edit.own', 'group' => 'Tiket IT (SIRS)', 'description' => 'Edit tiket sendiri (open)'],
            ['name' => 'Manage All Tickets', 'slug' => 'ticket.manage', 'group' => 'Tiket IT (SIRS)', 'description' => 'Kelola semua tiket (admin IT)'],
            ['name' => 'View Ticket History', 'slug' => 'ticket.history', 'group' => 'Tiket IT (SIRS)', 'description' => 'Lihat histori tiket'],
            // Orders Maintenance (IPSRS)
            ['name' => 'View Orders', 'slug' => 'order.view', 'group' => 'Maintenance (IPSRS)', 'description' => 'Lihat order sendiri'],
            ['name' => 'Create Order', 'slug' => 'order.create', 'group' => 'Maintenance (IPSRS)', 'description' => 'Buat order perbaikan'],
            ['name' => 'Edit Own Order', 'slug' => 'order.edit.own', 'group' => 'Maintenance (IPSRS)', 'description' => 'Edit order sendiri'],
            ['name' => 'Manage Maintenance Orders', 'slug' => 'order.manage', 'group' => 'Maintenance (IPSRS)', 'description' => 'Kelola order perbaikan (admin IPSRS)'],
            // Master Data
            ['name' => 'View Master Data', 'slug' => 'master.view', 'group' => 'Master Data', 'description' => 'Lihat master data'],
            ['name' => 'Manage Master Data', 'slug' => 'master.manage', 'group' => 'Master Data', 'description' => 'Kelola master data (categories, departments, buildings, locations, positions, unit proses, kategori order)'],
            // User Management
            ['name' => 'View Users', 'slug' => 'user.view', 'group' => 'User Management', 'description' => 'Lihat daftar user'],
            ['name' => 'Manage Users', 'slug' => 'user.manage', 'group' => 'User Management', 'description' => 'Kelola users (create/edit/delete)'],
            // Reports
            ['name' => 'View Reports', 'slug' => 'report.view', 'group' => 'Reports', 'description' => 'Lihat laporan'],
            ['name' => 'Manage Reports', 'slug' => 'report.manage', 'group' => 'Reports', 'description' => 'Generate & kelola laporan'],
            ['name' => 'View SIRS Report', 'slug' => 'report.sirs', 'group' => 'Reports', 'description' => 'Akses Report SIRS'],
            // Feedback
            ['name' => 'View Feedback', 'slug' => 'feedback.view', 'group' => 'Feedback', 'description' => 'Lihat feedback'],
            ['name' => 'Manage Feedback', 'slug' => 'feedback.manage', 'group' => 'Feedback', 'description' => 'Kelola feedback (reply/delete)'],
            // Knowledge & FAQ
            ['name' => 'View FAQ', 'slug' => 'faq.view', 'group' => 'Knowledge', 'description' => 'Akses FAQ'],
            ['name' => 'View Knowledge Base', 'slug' => 'knowledge.view', 'group' => 'Knowledge', 'description' => 'Akses Knowledge Base'],
            // Notifications & FCM
            ['name' => 'View Notifications', 'slug' => 'notification.view', 'group' => 'Notifications', 'description' => 'Lihat notifikasi'],
            ['name' => 'Manage FCM', 'slug' => 'fcm.manage', 'group' => 'Notifications', 'description' => 'Kelola FCM monitoring'],
            // Admin / IPSRS access
            ['name' => 'Admin Dashboard Access', 'slug' => 'admin.dashboard', 'group' => 'Admin', 'description' => 'Akses dashboard admin IT'],
            ['name' => 'IPSRS Dashboard Access', 'slug' => 'ipsrs.dashboard', 'group' => 'Admin', 'description' => 'Akses dashboard Administrasi Umum (IPSRS)'],
            ['name' => 'Admin Settings', 'slug' => 'admin.settings', 'group' => 'Admin', 'description' => 'Akses pengaturan admin'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }

        // Define role + position -> permissions mapping
        $rolePermissions = [
            // Regular user (role=user, any position)
            'user' => [
                'position' => null,
                'perms' => [
                    'dashboard.view',
                    'ticket.view', 'ticket.create', 'ticket.edit.own', 'ticket.history',
                    'order.view', 'order.create', 'order.edit.own',
                    'faq.view', 'knowledge.view',
                    'notification.view',
                    'feedback.view',
                    'report.view',
                ],
            ],
            // Admin IT (role=admin, position=IT)
            'admin_IT' => [
                'role' => 'admin', 'position' => 'IT',
                'perms' => [
                    'dashboard.view', 'admin.dashboard',
                    'ticket.view', 'ticket.create', 'ticket.edit.own', 'ticket.manage', 'ticket.history',
                    'order.view', // admin IT can view orders but not manage? allow view
                    'master.view', 'master.manage',
                    'user.view', 'user.manage',
                    'report.view', 'report.manage', 'report.sirs',
                    'feedback.view', 'feedback.manage',
                    'faq.view', 'knowledge.view',
                    'notification.view', 'fcm.manage',
                ],
            ],
            // Admin IPSRS / Administrasi Umum (role=admin, position=Administrasi)
            'admin_Administrasi' => [
                'role' => 'admin', 'position' => 'Administrasi',
                'perms' => [
                    'dashboard.view', 'ipsrs.dashboard',
                    'order.view', 'order.create', 'order.edit.own', 'order.manage',
                    'ticket.view', // can view tickets? limited
                    'feedback.view',
                    'faq.view', 'knowledge.view',
                    'notification.view',
                    'report.view',
                ],
            ],
        ];

        // Clear previous role_permissions to re-seed
        DB::table('role_permissions')->delete();

        foreach ($rolePermissions as $key => $rp) {
            $role = $rp['role'] ?? ($key === 'user' ? 'user' : 'admin');
            $position = $rp['position'] ?? ($rp['position'] ?? null);
            // handle legacy keys
            if ($key === 'admin_IT') { $role='admin'; $position='IT'; }
            if ($key === 'admin_Administrasi') { $role='admin'; $position='Administrasi'; }
            if ($key === 'user') { $role='user'; $position=null; }

            foreach ($rp['perms'] as $slug) {
                $perm = Permission::where('slug', $slug)->first();
                if ($perm) {
                    DB::table('role_permissions')->updateOrInsert(
                        ['role' => $role, 'position' => $position, 'permission_id' => $perm->id],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
        }

        $this->command->info('Permissions seeded: '.Permission::count().' permissions, '.DB::table('role_permissions')->count().' role_permissions');
    }
}
