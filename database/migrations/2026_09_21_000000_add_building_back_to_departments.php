<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Re-add departments.building_id yang sempat dihapus oleh
     * 2026_09_19_090004_drop_building_from_departments.php.
     * Fitur "Gedung" di departemen masih dipakai (admin master
     * departments & default building pada form tiket), jadi kolom
     * dikembalikan: gedung tetap single source of truth di departemen.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('building_id')->nullable()->after('location_id')->constrained('buildings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('building_id');
        });
    }
};