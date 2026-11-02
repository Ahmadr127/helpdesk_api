<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('order_perbaikan', function (Blueprint $table) {
            if (Schema::hasColumn('order_perbaikan', 'unit_proses_id')) {
                try {
                    $table->dropForeign(['unit_proses_id']);
                } catch (\Throwable $e) {
                    // ignore if FK not exists
                }
            }
            $columns = [];
            foreach (['unit_proses_id', 'kode_inventaris', 'nama_barang'] as $col) {
                if (Schema::hasColumn('order_perbaikan', $col)) {
                    $columns[] = $col;
                }
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down()
    {
        Schema::table('order_perbaikan', function (Blueprint $table) {
            if (! Schema::hasColumn('order_perbaikan', 'unit_proses_id')) {
                $table->unsignedBigInteger('unit_proses_id')->nullable();
            }
            if (! Schema::hasColumn('order_perbaikan', 'kode_inventaris')) {
                $table->string('kode_inventaris')->nullable();
            }
            if (! Schema::hasColumn('order_perbaikan', 'nama_barang')) {
                $table->string('nama_barang')->nullable();
            }
        });
    }
};
