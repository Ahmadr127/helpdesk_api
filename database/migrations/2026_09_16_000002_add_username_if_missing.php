<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->unique()->after('email');
            });
        }
        // Backfill null usernames
        if (Schema::hasColumn('users', 'username')) {
            $count = DB::table('users')->whereNull('username')->orWhere('username', '')->count();
            if ($count > 0) {
                DB::table('users')->whereNull('username')->orWhere('username', '')->eachById(function ($user) {
                    $base = $user->email ? explode('@', $user->email)[0] : 'user'.$user->id;
                    $base = preg_replace('/[^A-Za-z0-9._-]/', '', strtolower($base));
                    $username = $base;
                    $i = 1;
                    while (DB::table('users')->where('username', $username)->exists()) {
                        $username = $base.$i++;
                    }
                    DB::table('users')->where('id', $user->id)->update(['username' => $username]);
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->dropUnique(['username']);
                } catch (\Throwable $e) {
                }
                $table->dropColumn('username');
            });
        }
    }
};
