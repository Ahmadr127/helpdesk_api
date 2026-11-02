<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('order_perbaikan', function (Blueprint $table) {
            if (Schema::hasColumn('order_perbaikan', 'unit_penerima')) {
                $table->dropColumn('unit_penerima');
            }
        });
    }

    public function down()
    {
        Schema::table('order_perbaikan', function (Blueprint $table) {
            if (! Schema::hasColumn('order_perbaikan', 'unit_penerima')) {
                $table->string('unit_penerima')->nullable();
            }
        });
    }
};
