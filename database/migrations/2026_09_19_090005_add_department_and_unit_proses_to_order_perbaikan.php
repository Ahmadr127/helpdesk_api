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
            $table->unsignedBigInteger('department_id')->nullable()->after('unit_penerima');
            $table->unsignedBigInteger('unit_proses_id')->nullable()->after('department_id');
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('unit_proses_id')->references('id')->on('unit_proses')->nullOnDelete();
        });

        // Backfill: unit_proses lama bisa berisi kode departemen (web) atau kode UnitProses (API)
        $deptByCode = DB::table('departments')->pluck('id', 'code');
        $deptByName = DB::table('departments')->pluck('id', 'name');
        $upByCode = DB::table('unit_proses')->pluck('id', 'code');

        DB::table('order_perbaikan')->orderBy('id')->get(['id', 'unit_proses'])->each(function ($row) use ($deptByCode, $deptByName, $upByCode) {
            $value = $row->unit_proses;
            if (! $value) {
                return;
            }
            $ids = [];
            if (isset($deptByCode[$value])) {
                $ids['department_id'] = $deptByCode[$value];
            } elseif (isset($deptByName[$value])) {
                $ids['department_id'] = $deptByName[$value];
            }
            if (isset($upByCode[$value])) {
                $ids['unit_proses_id'] = $upByCode[$value];
            }
            if ($ids) {
                DB::table('order_perbaikan')->where('id', $row->id)->update($ids);
            }
        });

        Schema::table('order_perbaikan', function (Blueprint $table) {
            $table->dropColumn(['unit_proses', 'unit_proses_name']);
        });
    }

    public function down()
    {
        Schema::table('order_perbaikan', function (Blueprint $table) {
            $table->string('unit_proses')->nullable()->after('tanggal');
            $table->string('unit_proses_name')->nullable()->after('unit_proses');
        });

        DB::table('order_perbaikan')->orderBy('id')->get(['id', 'department_id', 'unit_proses_id'])->each(function ($row) {
            $code = null;
            if ($row->department_id) {
                $code = DB::table('departments')->where('id', $row->department_id)->value('code');
            }
            if (! $code && $row->unit_proses_id) {
                $code = DB::table('unit_proses')->where('id', $row->unit_proses_id)->value('code');
            }
            if ($code) {
                DB::table('order_perbaikan')->where('id', $row->id)->update([
                    'unit_proses' => $code,
                    'unit_proses_name' => $code,
                ]);
            }
        });

        Schema::table('order_perbaikan', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['unit_proses_id']);
            $table->dropColumn(['department_id', 'unit_proses_id']);
        });
    }
};
