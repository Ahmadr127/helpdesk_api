<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('email');
            }
            // Backfill username from email prefix if column newly added and data exists
            // (handled via migration post-processing below)
        });

        // Backfill existing users where username is null: username = email prefix or email itself
        if (Schema::hasColumn('users', 'username')) {
            try {
                \Illuminate\Support\Facades\DB::table('users')->whereNull('username')->orWhere('username', '')->eachById(function ($user) {
                    $base = $user->email ? explode('@', $user->email)[0] : 'user'.$user->id;
                    $base = preg_replace('/[^A-Za-z0-9._-]/', '', $base);
                    $base = strtolower($base);
                    $username = $base;
                    $i = 1;
                    while (\Illuminate\Support\Facades\DB::table('users')->where('username', $username)->exists()) {
                        $username = $base . $i++;
                    }
                    \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update(['username' => $username]);
                });
            } catch (\Throwable $e) {
                // ignore backfill errors in migration context
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                // Drop unique index first if exists
                try { $table->dropUnique(['username']); } catch (\Throwable $e) {}
                $table->dropColumn('username');
            }
        });
    }
};
