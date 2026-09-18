<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('UPDATE tickets SET department_id = (SELECT id FROM departments ORDER BY id LIMIT 1) WHERE department_id IS NULL');
            DB::statement('ALTER TABLE tickets DROP CONSTRAINT IF EXISTS tickets_department_id_foreign');
            DB::statement('ALTER TABLE tickets ALTER COLUMN department_id SET NOT NULL');
            DB::statement('ALTER TABLE tickets ADD CONSTRAINT tickets_department_id_foreign FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tickets DROP CONSTRAINT IF EXISTS tickets_department_id_foreign');
            DB::statement('ALTER TABLE tickets ALTER COLUMN department_id DROP NOT NULL');
            DB::statement('ALTER TABLE tickets ADD CONSTRAINT tickets_department_id_foreign FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT');
        }
    }
};
