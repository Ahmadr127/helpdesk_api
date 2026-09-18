<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_perbaikan ALTER COLUMN jenis_barang DROP NOT NULL');
        } else {
            DB::statement('ALTER TABLE order_perbaikan MODIFY jenis_barang VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_perbaikan ALTER COLUMN jenis_barang SET NOT NULL');
        } else {
            DB::statement('ALTER TABLE order_perbaikan MODIFY jenis_barang VARCHAR(255) NOT NULL');
        }
    }
};