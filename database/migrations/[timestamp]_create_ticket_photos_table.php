<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy migration: tabel `tickets` di-drop oleh 2026_11_13_000002_drop_unused_master_tables.
        if (! Schema::hasTable('tickets')) {
            return;
        }

        Schema::create('ticket_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->string('photo_path');
            $table->string('type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_photos');
    }
};
