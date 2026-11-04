<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('order_perbaikan_id')->constrained('order_perbaikan')->cascadeOnDelete();
            $table->string('kategori_name');
            $table->boolean('sync_enabled')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('ticket_id');
            $table->unique('order_perbaikan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_links');
    }
};
