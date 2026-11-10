<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambah permission khusus untuk pengelolaan role (terpisah dari permission umum).
     */
    public function up(): void
    {
        $now = now();

        $permission = DB::table('permissions')->where('slug', 'role')->first();

        if (! $permission) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'Kelola Role',
                'slug' => 'role',
                'group' => 'User Management',
                'description' => 'Kelola role dan penugasan permission per role',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $permissionId = $permission->id;
            DB::table('permissions')->where('id', $permissionId)->update([
                'name' => 'Kelola Role',
                'group' => 'User Management',
                'description' => 'Kelola role dan penugasan permission per role',
                'is_system' => true,
                'updated_at' => $now,
            ]);
        }

        $roles = DB::table('roles')->whereIn('slug', ['admin'])->get(['id', 'slug']);

        foreach ($roles as $role) {
            $exists = DB::table('role_permissions')
                ->where('role_id', $role->id)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $exists) {
                DB::table('role_permissions')->insert([
                    'role' => $role->slug,
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Hapus permission pengelolaan role.
     */
    public function down(): void
    {
        $permission = DB::table('permissions')->where('slug', 'role')->first();

        if (! $permission) {
            return;
        }

        DB::table('role_permissions')->where('permission_id', $permission->id)->delete();
        DB::table('permission_user')->where('permission_id', $permission->id)->delete();
        DB::table('permissions')->where('id', $permission->id)->delete();
    }
};
