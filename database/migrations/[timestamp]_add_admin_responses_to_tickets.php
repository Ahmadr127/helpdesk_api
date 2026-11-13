<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy migration: tabel `tickets` di-drop oleh 2026_11_13_000002_drop_unused_master_tables.
        if (! Schema::hasTable('tickets')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->json('admin_responses')->nullable()->after('admin_notes');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('admin_responses');
        });
    }
};
