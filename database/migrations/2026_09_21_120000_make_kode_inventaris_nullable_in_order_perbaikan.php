<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // kode_inventaris opsional (nullable): input disembunyikan, submit null
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_perbaikan ALTER COLUMN kode_inventaris DROP NOT NULL');
        } else {
            DB::statement('ALTER TABLE order_perbaikan MODIFY kode_inventaris VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_perbaikan ALTER COLUMN kode_inventaris SET NOT NULL');
        } else {
            DB::statement('ALTER TABLE order_perbaikan MODIFY kode_inventaris VARCHAR(255) NOT NULL');
        }
    }
};
