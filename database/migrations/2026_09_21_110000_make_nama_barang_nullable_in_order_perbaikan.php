<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // nama_barang opsional (nullable) di alur order perbaikan
        Schema::table('order_perbaikan', function (Blueprint $table) {
            $table->string('nama_barang')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_perbaikan', function (Blueprint $table) {
            $table->string('nama_barang')->nullable(false)->change();
        });
    }
};
