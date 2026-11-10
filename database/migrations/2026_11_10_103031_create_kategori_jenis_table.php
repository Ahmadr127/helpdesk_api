<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot kategori_pengajuan ↔ jenis_pengajuan (banyak-banyak).
     *
     * Kolom `kategori_pengajuan.jenis_id` tetap dipertahankan sebagai BC.
     * Data lama di-backfill ke pivot agar kategori yang ada tetap bekerja.
     */
    public function up(): void
    {
        Schema::create('kategori_jenis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori_id');
            $table->unsignedBigInteger('jenis_id');
            $table->timestamps();

            $table->foreign('kategori_id')->references('id')->on('kategori_pengajuan')->cascadeOnDelete();
            $table->foreign('jenis_id')->references('id')->on('jenis_pengajuan')->cascadeOnDelete();
            $table->unique(['kategori_id', 'jenis_id']);
        });

        // Backfill: isi pivot dari relasi single yang sudah ada.
        DB::table('kategori_pengajuan')
            ->whereNotNull('jenis_id')
            ->orderBy('id')
            ->each(function ($row) {
                DB::table('kategori_jenis')->insertOrIgnore([
                    'kategori_id' => $row->id,
                    'jenis_id' => $row->jenis_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_jenis');
    }
};
