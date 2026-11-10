<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus kolom single jenis `kategori_pengajuan.jenis_id`.
     * Semua kode berpindah ke pivot `kategori_jenis` (many-to-many).
     * Kolom direct dipakai sebagai BC; setelah kode migrasi penuh, kolom dibuang.
     */
    public function up(): void
    {
        // Pastikan setiap kategori punya minimal satu pivot row sebelum kolom dihapus.
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

        Schema::table('kategori_pengajuan', function (Blueprint $table) {
            // Drop FK dulu, lalu drop unique/index yang bergantung pada kolom.
            $table->dropForeign(['jenis_id']);
        });

        DB::statement('DROP INDEX IF EXISTS kategori_pengajuan_jenis_name_unique');
        DB::statement('DROP INDEX IF EXISTS kategori_pengajuan_jenis_status_idx');

        Schema::table('kategori_pengajuan', function (Blueprint $table) {
            $table->dropColumn('jenis_id');
        });
    }

    public function down(): void
    {
        Schema::table('kategori_pengajuan', function (Blueprint $table) {
            $table->unsignedBigInteger('jenis_id')->nullable()->after('id');
            $table->foreign('jenis_id')->references('id')->on('jenis_pengajuan')->nullOnDelete();
        });

        // Backfill jenis_id dari pivot (ambil jenis pertama).
        foreach (DB::table('kategori_pengajuan')->get() as $row) {
            $firstJenis = DB::table('kategori_jenis')
                ->where('kategori_id', $row->id)
                ->orderBy('jenis_id')
                ->value('jenis_id');
            if ($firstJenis) {
                DB::table('kategori_pengajuan')->where('id', $row->id)->update(['jenis_id' => $firstJenis]);
            }
        }
    }
};

