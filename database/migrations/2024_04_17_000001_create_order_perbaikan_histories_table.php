<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_perbaikan_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_perbaikan_id')->constrained('order_perbaikan')->onDelete('cascade');
            $table->enum('status', ['open', 'in_progress', 'confirmed', 'rejected']);
            $table->text('follow_up')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_perbaikan_histories');
    }
}; 