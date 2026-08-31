<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->string('category')->nullable(); // For display name
            $table->foreignId('department_id')->constrained('departments')->onDelete('restrict');
            $table->string('department')->nullable(); // For display name
            $table->foreignId('building_id')->constrained('buildings')->onDelete('restrict');
            $table->string('building')->nullable(); // For display name
            $table->foreignId('location_id')->constrained('locations')->onDelete('restrict');
            $table->string('location')->nullable(); // For display name
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high'])->default('low');
            $table->enum('status', ['pending', 'open', 'in_progress', 'closed', 'confirmed'])->default('open');
            $table->text('admin_notes')->nullable();
            
            // Timeline fields
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('in_progress_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            
            // Rejection fields
            $table->text('rejection_notes')->nullable();
            $table->string('rejection_photo')->nullable();
            $table->timestamp('last_rejection_at')->nullable();
            $table->integer('rejection_count')->default(0);
            
            // Confirmation fields
            $table->text('confirmation_notes')->nullable();
            $table->string('confirmation_photo')->nullable();
            $table->boolean('user_confirmation')->default(false);
            $table->timestamp('user_confirmed_at')->nullable();
            $table->text('user_confirmation_notes')->nullable();
            
            // Additional fields
            $table->string('photo')->nullable();
            $table->json('user_replies')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tickets');
    }
};