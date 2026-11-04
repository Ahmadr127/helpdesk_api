<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $perm = Permission::updateOrCreate(
            ['slug' => 'audit'],
            [
                'name' => 'Audit Log',
                'group' => 'Reports',
                'description' => 'Lihat audit log aktivitas sistem',
            ]
        );

        // Assign ke role admin (jika ada)
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        if ($adminRole) {
            DB::table('role_permissions')->updateOrInsert(
                ['role' => 'admin', 'permission_id' => $perm->id],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        $perm = Permission::where('slug', 'audit')->first();
        if ($perm) {
            DB::table('role_permissions')->where('permission_id', $perm->id)->delete();
            DB::table('permission_user')->where('permission_id', $perm->id)->delete();
            $perm->delete();
        }
    }
};