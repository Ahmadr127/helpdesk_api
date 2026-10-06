<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Production-safe: idempoten, transaksional. Permission kanonis di-upsert
        // (nama/grup/deskripsi ikut diperbarui), slug usang di-prune bersama
        // pivot-nya, lalu peta role di-sinkron ulang.
        DB::transaction(function () {
            foreach ($this->permissions() as $p) {
                Permission::updateOrCreate(['slug' => $p['slug']], $p);
            }

            $obsoleteIds = Permission::whereIn('slug', self::obsoleteSlugs())->pluck('id');
            if ($obsoleteIds->isNotEmpty()) {
                DB::table('role_permissions')->whereIn('permission_id', $obsoleteIds)->delete();
                DB::table('permission_user')->whereIn('permission_id', $obsoleteIds)->delete();
                Permission::whereIn('id', $obsoleteIds)->delete();
            }

            $this->syncRoleMap();
        });

        $this->command->info('Permissions seeded: '.Permission::count().' permissions, '.DB::table('role_permissions')->count().' role_permissions');
    }

    /**
     * Slug usang yang dibersihkan saat seed. Daftar eksplisit (bukan
     * "semua di luar kanonis") agar permission custom dari halaman
     * Permissions tidak ikut terhapus di production.
     */
    public static function obsoleteSlugs(): array
    {
        return [
            'dashboard.view', 'ticket.history', 'admin.settings',
            'ticket.view', 'ticket.create', 'ticket.edit.own', 'ticket.manage',
            'order.view', 'order.create', 'order.edit.own', 'order.manage',
            'master.view', 'master.manage',
            'user.view', 'user.manage',
            'report.view', 'report.manage',
            'feedback.view', 'feedback.manage',
            'faq.view', 'knowledge.view',
            'notification.view', 'fcm.manage',
            'admin.dashboard', 'ipsrs.dashboard',
        ];
    }

    /** Daftar permission kanonis yang di-sync ke production. */
    protected function permissions(): array
    {
        return [
            // Tiket IT (SIRS)
            ['name' => 'Tiket IT', 'slug' => 'ticket', 'group' => 'Tiket IT (SIRS)', 'description' => 'Akses fitur tiket (list, buat, ubah, kelola)'],
            // Order Perbaikan (IPSRS)
            ['name' => 'Order Perbaikan', 'slug' => 'order', 'group' => 'Maintenance (IPSRS)', 'description' => 'Akses fitur order perbaikan (list, buat, ubah, kelola)'],
            // Master Data
            ['name' => 'Master Data', 'slug' => 'master', 'group' => 'Master Data', 'description' => 'Akses master data (categories, departments, buildings, locations, positions, unit proses, kategori order)'],
            // User Management
            ['name' => 'Kelola User', 'slug' => 'user', 'group' => 'User Management', 'description' => 'Lihat & kelola users'],
            // Kelola matriks permission per role
            ['name' => 'Kelola Permission', 'slug' => 'permission', 'group' => 'User Management', 'description' => 'Kelola permission per role & user'],
            // Reports
            ['name' => 'Laporan', 'slug' => 'report', 'group' => 'Reports', 'description' => 'Lihat & generate laporan'],
            ['name' => 'Report SIRS', 'slug' => 'report.sirs', 'group' => 'Reports', 'description' => 'Akses Report SIRS'],
            // Feedback
            ['name' => 'Feedback', 'slug' => 'feedback', 'group' => 'Feedback', 'description' => 'Akses feedback (buat, lihat, balas, hapus)'],
            // Knowledge & FAQ
            ['name' => 'Knowledge Base', 'slug' => 'knowledge', 'group' => 'Knowledge', 'description' => 'Akses FAQ & Knowledge Base'],
            // Notifications & FCM
            ['name' => 'Notifikasi', 'slug' => 'notification', 'group' => 'Notifications', 'description' => 'Lihat notifikasi'],
            ['name' => 'FCM Monitoring', 'slug' => 'fcm', 'group' => 'Notifications', 'description' => 'Kelola FCM monitoring'],
            // Dashboard & layout
            ['name' => 'Dashboard IT', 'slug' => 'dashboard.it', 'group' => 'Admin', 'description' => 'Akses dashboard admin IT'],
            ['name' => 'Dashboard IPSRS', 'slug' => 'dashboard.ipsrs', 'group' => 'Admin', 'description' => 'Akses dashboard Administrasi Umum (IPSRS)'],
            // Tiket & Order milik sendiri di shell admin
            ['name' => 'Tiket Saya', 'slug' => 'myticket', 'group' => 'Tiket & Order Saya', 'description' => 'Akses Tiket Saya (list, buat, ubah, hapus tiket milik sendiri)'],
            ['name' => 'Order Saya', 'slug' => 'myorder', 'group' => 'Tiket & Order Saya', 'description' => 'Akses Order Saya (list, buat, ubah, hapus order milik sendiri)'],
            // Layout: pemegangnya memakai shell admin (sidebar), selainnya shell user (topbar)
            ['name' => 'Akses Shell Admin', 'slug' => 'layout.admin', 'group' => 'Admin', 'description' => 'Pilih layout shell admin'],
        ];
    }

    /** Sinkron ulang peta role → permission (idempoten). */
    protected function syncRoleMap(): void
    {
        // Clear previous role_permissions to re-seed
        DB::table('role_permissions')->delete();

        $hasPositionColumn = Schema::hasColumn('role_permissions', 'position');

        foreach (self::rolePermissionsMap() as $role => $slugs) {
            // Skip unknown roles so dynamic roles stay manageable via RoleSeeder.
            if (! Role::where('slug', $role)->exists()) {
                continue;
            }
            foreach ($slugs as $slug) {
                $perm = Permission::where('slug', $slug)->first();
                if ($perm) {
                    $key = ['role' => $role, 'permission_id' => $perm->id];
                    if ($hasPositionColumn) {
                        $key['position'] = null;
                    }
                    DB::table('role_permissions')->updateOrInsert(
                        $key,
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
        }
    }

    public static function rolePermissionsMap(): array
    {
        return [
            'user' => [
                'ticket', 'order',
                'knowledge', 'notification', 'feedback', 'report',
            ],
            'admin' => [
                'dashboard.it', 'layout.admin',
                'ticket', 'order',
                'myticket', 'myorder',
                'master', 'user', 'permission',
                'report', 'report.sirs',
                'feedback', 'knowledge',
                'notification', 'fcm',
            ],
            'ipsrs' => [
                'dashboard.ipsrs', 'layout.admin',
                'order', 'ticket',
                'myticket', 'myorder',
                'feedback', 'knowledge',
                'notification', 'report',
            ],
        ];
    }
}
