<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // lokasi opsional di alur order perbaikan
        Schema::table('order_perbaikan', function (Blueprint $table) {
            $table->foreignId('lokasi')->nullable()->change();
        });
        // location_id opsional di alur tiket
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_perbaikan', function (Blueprint $table) {
            $table->foreignId('lokasi')->nullable(false)->change();
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable(false)->change();
        });
    }
};
