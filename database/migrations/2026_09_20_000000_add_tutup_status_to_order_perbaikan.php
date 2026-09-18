<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambahkan status 'tutup' (menunggu konfirmasi selesai dari user)
        DB::statement('ALTER TABLE order_perbaikan DROP CONSTRAINT order_perbaikan_status_check');
        DB::statement("ALTER TABLE order_perbaikan ADD CONSTRAINT order_perbaikan_status_check CHECK (status IN ('open', 'in_progress', 'tutup', 'confirmed', 'rejected'))");

        DB::statement('ALTER TABLE order_perbaikan_histories DROP CONSTRAINT order_perbaikan_histories_status_check');
        DB::statement("ALTER TABLE order_perbaikan_histories ADD CONSTRAINT order_perbaikan_histories_status_check CHECK (status IN ('open', 'in_progress', 'tutup', 'confirmed', 'rejected'))");

        // Kolom keterangan untuk menampung catatan/tindak lanjut per histori (timeline)
        if (! Schema::hasColumn('order_perbaikan_histories', 'keterangan')) {
            Schema::table('order_perbaikan_histories', function (Blueprint $table) {
                $table->text('keterangan')->nullable()->after('follow_up');
            });
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE order_perbaikan DROP CONSTRAINT order_perbaikan_status_check');
        DB::statement("ALTER TABLE order_perbaikan ADD CONSTRAINT order_perbaikan_status_check CHECK (status IN ('open', 'in_progress', 'confirmed', 'rejected'))");

        DB::statement('ALTER TABLE order_perbaikan_histories DROP CONSTRAINT order_perbaikan_histories_status_check');
        DB::statement("ALTER TABLE order_perbaikan_histories ADD CONSTRAINT order_perbaikan_histories_status_check CHECK (status IN ('open', 'in_progress', 'confirmed', 'rejected'))");

        if (Schema::hasColumn('order_perbaikan_histories', 'keterangan')) {
            Schema::table('order_perbaikan_histories', function (Blueprint $table) {
                $table->dropColumn('keterangan');
            });
        }
    }
};
