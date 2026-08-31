<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Hapus constraint lama jika menggunakan PostgreSQL
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tickets DROP CONSTRAINT IF EXISTS tickets_category_check');
        }
        
        // Migrasi data lama ke kolom yang sudah ada
        DB::table('tickets')->chunkById(100, function ($tickets) {
            foreach ($tickets as $ticket) {
                $categoryId = DB::table('categories')->where('name', $ticket->category)->value('id');
                $departmentId = DB::table('departments')->where('code', $ticket->department)->value('id');
                $buildingId = DB::table('buildings')->where('code', $ticket->building)->value('id');
                $locationId = DB::table('locations')->where('name', $ticket->location)->value('id');
                
                DB::table('tickets')
                    ->where('id', $ticket->id)
                    ->update([
                        'category_id' => $categoryId,
                        'department_id' => $departmentId,
                        'building_id' => $buildingId,
                        'location_id' => $locationId
                    ]);
            }
        });
    }

    public function down()
    {
        // Kembalikan constraint enum jika perlu
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE tickets ADD CONSTRAINT tickets_category_check CHECK (category IN ('hardware', 'software', 'network', 'termedik', 'printer', 'cctv'))");
        }
    }
}; 