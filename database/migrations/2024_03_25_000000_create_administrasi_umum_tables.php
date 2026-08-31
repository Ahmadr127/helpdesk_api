<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->dateTime('tanggal');
            $table->string('unit_proses_code');
            $table->string('unit_proses_name');
            $table->string('nip_peminta');
            $table->enum('prioritas', ['BIASA', 'OCTO'])->default('BIASA');
            $table->enum('jenis_barang', ['Inventaris', 'Umum']);
            $table->string('kode_inventaris')->nullable();
            $table->string('nama_barang');
            $table->text('deskripsi_kerusakan');
            $table->enum('status', ['open', 'in_progress', 'confirmed', 'rejected'])->default('open');
            $table->text('admin_notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

        });

        Schema::create('order_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->integer('size');
            $table->timestamps();
        });

        Schema::create('order_barang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('item_name');
            $table->integer('quantity');
            $table->string('unit');
            $table->text('purpose');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists('prosedur');
        Schema::dropIfExists('formulir');
        Schema::dropIfExists('dokumen');
        Schema::dropIfExists('order_barang');
        Schema::dropIfExists('order_photos');
        Schema::dropIfExists('orders');
    }
}; 