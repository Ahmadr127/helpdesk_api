<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Permission untuk fitur audit logs (dari migrations, idempotent).
        if (! DB::table('permissions')->where('slug', 'audit.logs')->exists()) {
            DB::table('permissions')->insert([
                'name' => 'Audit Logs',
                'slug' => 'audit.logs',
                'group' => 'Audit',
                'description' => 'Melihat riwayat audit log (event & perubahan data).',
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('slug', 'audit.logs')->delete();
    }
};
