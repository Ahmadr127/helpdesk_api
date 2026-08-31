<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('status')->default(1);
            $table->timestamps();
        });

        // Migrate existing position data from users table
        $positions = DB::table('users')
            ->whereNotNull('position')
            ->select('position')
            ->distinct()
            ->get()
            ->pluck('position');

        foreach ($positions as $position) {
            DB::table('positions')->insert([
                'name' => $position,
                'code' => $position,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add ADMINISTRASI position
        DB::table('positions')->insert([
            'name' => 'ADMINISTRASI',
            'code' => 'ADMINISTRASI',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('positions');
    }
}; 