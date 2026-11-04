<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Backfill single-string foto/lampiran into JSON arrays for multi-image support.
        DB::table('order_perbaikan')->whereNotNull('foto')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                $val = $row->foto;
                if (is_string($val) && ! str_starts_with(trim($val), '[')) {
                    DB::table('order_perbaikan')->where('id', $row->id)->update([
                        'foto' => json_encode([$val]),
                    ]);
                }
            }
        });

        if (Schema::hasTable('order_perbaikan_histories')) {
            DB::table('order_perbaikan_histories')->whereNotNull('lampiran')->orderBy('id')->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    $val = $row->lampiran;
                    if (is_string($val) && ! str_starts_with(trim($val), '[')) {
                        DB::table('order_perbaikan_histories')->where('id', $row->id)->update([
                            'lampiran' => json_encode([$val]),
                        ]);
                    }
                }
            });
        }
    }

    public function down()
    {
        // no-op: keep arrays
    }
};