<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        $now = now();
        foreach ([
            ['slug' => 'user', 'name' => 'User (Regular)', 'description' => 'Pengguna biasa'],
            ['slug' => 'admin', 'name' => 'Admin IT', 'description' => 'Administrator IT (tiket SIRS)'],
            ['slug' => 'ipsrs', 'name' => 'Admin IPSRS / Administrasi Umum', 'description' => 'Administrator IPSRS (order perbaikan)'],
        ] as $role) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $role['slug']],
                array_merge($role, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        // role_permissions.position is dead (always null since 000005) — drop it.
        if (Schema::hasTable('role_permissions') && Schema::hasColumn('role_permissions', 'position')) {
            // Drop old unique that includes position first (name varies by driver).
            try {
                DB::table('role_permissions')
                    ->whereNotNull('position')
                    ->update(['position' => null]);
            } catch (\Throwable $e) {
            }

            Schema::table('role_permissions', function (Blueprint $table) {
                try {
                    $table->dropUnique(['role', 'position', 'permission_id']);
                } catch (\Throwable $e) {
                }
            });

            Schema::table('role_permissions', function (Blueprint $table) {
                $table->dropColumn('position');
            });

            Schema::table('role_permissions', function (Blueprint $table) {
                $table->unique(['role', 'permission_id']);
            });
        }

        // users.role: keep string slug, point FK to roles.slug for dynamic roles.
        if (Schema::hasTable('users')) {
            $driver = DB::getDriverName();

            $knownSlugs = DB::table('roles')->pluck('slug')->all();
            if (! empty($knownSlugs)) {
                DB::table('users')->whereNotIn('role', $knownSlugs)->update(['role' => 'user']);
            }

            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
                DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_foreign');
                // Backfill any role not present in roles table before adding FK.
                $valid = DB::table('roles')->pluck('slug')->all();
                if (! empty($valid)) {
                    $placeholders = implode(',', array_fill(0, count($valid), '?'));
                    DB::statement(
                        "UPDATE users SET role = 'user' WHERE role NOT IN ({$placeholders})",
                        $valid
                    );
                }
                DB::statement('ALTER TABLE users ADD CONSTRAINT users_role_foreign FOREIGN KEY (role) REFERENCES roles(slug) ON UPDATE CASCADE');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_foreign');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('user','admin','ipsrs'))");
        }

        if (Schema::hasTable('role_permissions') && ! Schema::hasColumn('role_permissions', 'position')) {
            Schema::table('role_permissions', function (Blueprint $table) {
                $table->string('position')->nullable();
            });
        }

        Schema::dropIfExists('roles');
    }
};
