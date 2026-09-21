<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // jenis_barang dihapus dari alur order perbaikan (sudah tidak ada di form web & API)
        Schema::table('order_perbaikan', function (Blueprint $table) {
            $table->dropColumn('jenis_barang');
        });
    }

    public function down(): void
    {
        Schema::table('order_perbaikan', function (Blueprint $table) {
            $table->string('jenis_barang')->nullable();
        });
    }
};
