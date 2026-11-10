<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Timeline hanya bisa menampilkan pemohon karena constraint lama membatasi
 * actor_type ke ('user','system'). Resource memakai 'admin' untuk membedakan
 * aksi admin, sehingga perlu diizinkan di level database.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE pengajuan_histori DROP CONSTRAINT IF EXISTS pengajuan_histori_actor_type_check');
        DB::statement(<<<'SQL'
            ALTER TABLE pengajuan_histori
                ADD CONSTRAINT pengajuan_histori_actor_type_check
                CHECK (actor_type IN ('user','admin','system'))
        SQL);

        // Backfill: histori lama yang pelakunya admin tapi tercatat 'user'.
        DB::statement(<<<'SQL'
            UPDATE pengajuan_histori h
            SET actor_type = 'admin'
            FROM users u
            WHERE h.actor_id = u.id
              AND h.actor_type = 'user'
              AND u.role IN ('admin', 'ipsrs')
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE pengajuan_histori DROP CONSTRAINT IF EXISTS pengajuan_histori_actor_type_check');
        DB::statement(<<<'SQL'
            UPDATE pengajuan_histori SET actor_type = 'user' WHERE actor_type = 'admin'
        SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE pengajuan_histori
                ADD CONSTRAINT pengajuan_histori_actor_type_check
                CHECK (actor_type IN ('user','system'))
        SQL);
    }
};
