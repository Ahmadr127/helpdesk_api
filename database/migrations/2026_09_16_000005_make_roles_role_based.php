<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $driver = DB::getDriverName();

        // users.role adalah ENUM (Postgres memakai check constraint users_role_check).
        // Buka constraint dulu agar bisa menyimpan nilai 'ipsrs', lalu pasang ulang.
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('user','admin','ipsrs') NOT NULL DEFAULT 'user'");
        }

        // users: role 'admin' + position IT -> 'admin'; position Administrasi -> 'ipsrs'
        DB::table('users')
            ->where('role', 'admin')
            ->whereRaw('LOWER(COALESCE(position,\'\')) = ?', ['it'])
            ->update(['role' => 'admin']);

        DB::table('users')
            ->where('role', 'admin')
            ->whereRaw('LOWER(COALESCE(position,\'\')) = ?', ['administrasi'])
            ->update(['role' => 'ipsrs']);

        // role_permissions: ubah akses jadi berbasis role (position tidak lagi dipakai)
        DB::table('role_permissions')
            ->where('role', 'admin')
            ->whereRaw('LOWER(COALESCE(position,\'\')) = ?', ['administrasi'])
            ->update(['role' => 'ipsrs', 'position' => null]);

        DB::table('role_permissions')
            ->where('role', 'admin')
            ->whereNotNull('position')
            ->update(['position' => null]);

        // hapus duplikat (role, permission_id) setelah position di-null-kan
        if ($driver === 'pgsql') {
            DB::statement('DELETE FROM role_permissions a USING role_permissions b
                WHERE a.ctid < b.ctid
                  AND a.role = b.role
                  AND a.permission_id = b.permission_id');
        } else {
            DB::statement('DELETE a FROM role_permissions a
                INNER JOIN role_permissions b
                    ON a.role = b.role AND a.permission_id = b.permission_id
                    AND a.id > b.id');
        }

        // pasang ulang constraint role dengan nilai baru
        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('user','admin','ipsrs'))");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        }

        DB::table('users')->where('role', 'ipsrs')->update(['role' => 'admin']);
        DB::table('role_permissions')->where('role', 'ipsrs')->update(['role' => 'admin']);

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('user','admin'))");
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('user','admin') NOT NULL DEFAULT 'user'");
        }
    }
};
