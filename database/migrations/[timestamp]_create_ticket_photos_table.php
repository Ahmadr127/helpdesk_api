<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ticket_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->string('photo_path');
            $table->enum('type', ['initial', 'admin_response', 'user_response', 'user_rejection']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_photos');
    }
}; 