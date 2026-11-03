<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ticket_penanggung_jawab', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['ticket_id', 'user_id']);
        });

        Schema::create('order_perbaikan_penanggung_jawab', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_perbaikan_id')->constrained('order_perbaikan')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['order_perbaikan_id', 'user_id']);
        });

        // Migrate existing free-text penanggung jawab (order) to user relation where name matches.
        if (Schema::hasColumn('order_perbaikan', 'nama_penanggung_jawab')) {
            $rows = DB::table('order_perbaikan')
                ->whereNotNull('nama_penanggung_jawab')
                ->get(['id', 'nama_penanggung_jawab']);

            foreach ($rows as $row) {
                $name = trim((string) $row->nama_penanggung_jawab);
                if ($name === '') {
                    continue;
                }
                $userId = DB::table('users')->where('name', $name)->value('id');
                if (! $userId) {
                    continue;
                }
                DB::table('order_perbaikan_penanggung_jawab')->insertOrIgnore([
                    'order_perbaikan_id' => $row->id,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Schema::table('order_perbaikan', function (Blueprint $table) {
                $table->dropColumn('nama_penanggung_jawab');
            });
        }
    }

    public function down()
    {
        if (! Schema::hasColumn('order_perbaikan', 'nama_penanggung_jawab')) {
            Schema::table('order_perbaikan', function (Blueprint $table) {
                $table->string('nama_penanggung_jawab')->nullable();
            });
        }

        Schema::dropIfExists('order_perbaikan_penanggung_jawab');
        Schema::dropIfExists('ticket_penanggung_jawab');
    }
};
