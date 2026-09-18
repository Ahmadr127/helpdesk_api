<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tickets DROP CONSTRAINT IF EXISTS tickets_department_id_foreign');
            DB::statement('ALTER TABLE tickets ALTER COLUMN department_id DROP NOT NULL');
            DB::statement('ALTER TABLE tickets ADD CONSTRAINT tickets_department_id_foreign FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT');
        } else {
            Schema::table('tickets', function ($table) {
                $table->unsignedBigInteger('department_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tickets DROP CONSTRAINT IF EXISTS tickets_department_id_foreign');
            DB::statement('ALTER TABLE tickets ALTER COLUMN department_id SET NOT NULL');
            DB::statement('ALTER TABLE tickets ADD CONSTRAINT tickets_department_id_foreign FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT');
        } else {
            Schema::table('tickets', function ($table) {
                $table->unsignedBigInteger('department_id')->nullable(false)->change();
            });
        }
    }
};
