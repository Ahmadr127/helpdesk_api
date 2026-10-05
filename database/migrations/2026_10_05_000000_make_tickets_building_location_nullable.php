<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Selaraskan DB dengan validasi aplikasi: building_id & location_id
 * bersifat opsional (nullable) di Web (TicketController@store),
 * API, dan mobile. Sebelumnya NOT NULL sehingga insert tanpa gedung/
 * lokasi meledak dengan SQLSTATE[23502] di produksi.
 *
 * Memakai statement mentah agar tanpa doctrine/dbal.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tickets ALTER COLUMN building_id DROP NOT NULL');
        DB::statement('ALTER TABLE tickets ALTER COLUMN location_id DROP NOT NULL');
    }

    public function down(): void
    {
        // Kembalikan NOT NULL hanya bila tidak ada baris null;
        // isi null dari gedung/lokasi departemen bila memungkinkan.
        DB::statement('UPDATE tickets t SET building_id = d.building_id FROM departments d WHERE t.building_id IS NULL AND t.department_id = d.id AND d.building_id IS NOT NULL');
        DB::statement('UPDATE tickets t SET location_id = d.location_id FROM departments d WHERE t.location_id IS NULL AND t.department_id = d.id AND d.location_id IS NOT NULL');
        DB::statement('ALTER TABLE tickets ALTER COLUMN building_id SET NOT NULL');
        DB::statement('ALTER TABLE tickets ALTER COLUMN location_id SET NOT NULL');
    }
};
