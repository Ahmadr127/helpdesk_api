<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_perbaikan', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained('buildings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_perbaikan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('building_id');
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
