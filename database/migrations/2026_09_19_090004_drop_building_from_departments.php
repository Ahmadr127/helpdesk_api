<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['building_id']);
            $table->dropColumn('building_id');
        });
    }

    public function down()
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedBigInteger('building_id')->nullable()->after('id');
            $table->foreign('building_id')->references('id')->on('buildings')->nullOnDelete();
        });
    }
};
