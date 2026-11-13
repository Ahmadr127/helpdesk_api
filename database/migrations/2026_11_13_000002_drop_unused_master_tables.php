<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel-tabel berikut adalah legacy yang digantikan schema unified `pengajuan`
        // (dan master terkaitnya). Drop berurutan agar aman dari FK di PostgreSQL.
        Schema::dropIfExists('kategori_order');
        Schema::dropIfExists('order_perbaikan_histories');
        Schema::dropIfExists('ticket_photos');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('order_perbaikan');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('unit_proses');
    }

    public function down(): void
    {
        // Restore manual dari backup bila diperlukan setelah drop.
    }
};
