<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('unit_proses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Add unit_proses_id to categories table
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('unit_proses_id')->nullable()->constrained('unit_proses')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['unit_proses_id']);
            $table->dropColumn('unit_proses_id');
        });

        Schema::dropIfExists('unit_proses');
    }
}; 