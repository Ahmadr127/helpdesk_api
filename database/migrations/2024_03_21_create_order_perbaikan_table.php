<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_perbaikan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->dateTime('tanggal');
            $table->string('unit_proses');
            $table->string('unit_proses_name');
            $table->string('unit_penerima');
            $table->string('nama_peminta');
            $table->enum('jenis_barang', ['Umum', 'Inventaris']);
            $table->string('kode_inventaris');
            $table->string('nama_barang');
            $table->foreignId('lokasi')->constrained('locations');
            $table->text('keluhan');
            $table->enum('prioritas', ['RENDAH', 'SEDANG', 'TINGGI/URGENT']);
            $table->string('foto')->nullable();
            $table->enum('status', ['open', 'in_progress', 'confirmed', 'rejected'])->default('open');
            $table->text('follow_up')->nullable();
            $table->string('nama_penanggung_jawab')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_perbaikan');
    }
}; 