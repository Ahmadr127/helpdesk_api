<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lokasi tidak lagi memiliki building_id maupun floor.
     * (building_id di departemen dikelola oleh 2026_09_18_094842_* lalu
     * dihapus oleh 2026_09_19_090004_*; relasi departemen-gedung diganti
     * location_id melalui 2026_09_19_090003_*.)
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('building_id');
            $table->dropColumn(['floor']);
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->foreignId('building_id')->nullable()->after('name')->constrained('buildings')->nullOnDelete();
            $table->string('floor', 100)->nullable()->after('building_id');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('building_id');
        });
    }
};