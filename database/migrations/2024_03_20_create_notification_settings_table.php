<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable');
            $table->integer('read_expiry_days')->default(7);
            $table->integer('unread_expiry_days')->default(30);
            $table->boolean('auto_delete_read')->default(true);
            $table->boolean('auto_delete_unread')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
}; 