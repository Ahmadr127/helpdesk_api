<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('prefix', 30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('kategori_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_id')->constrained('jenis_pengajuan')->restrictOnDelete();
            $table->string('code', 50)->nullable()->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->smallInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['jenis_id', 'status'], 'kategori_pengajuan_jenis_status_idx');
        });

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX kategori_pengajuan_jenis_name_unique
                ON kategori_pengajuan (jenis_id, LOWER(name))
                WHERE deleted_at IS NULL
        SQL);

        Schema::create('pengajuan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 60)->unique();
            $table->foreignId('kategori_id')->constrained('kategori_pengajuan')->restrictOnDelete();
            $table->foreignId('pemohon_id')->constrained('users')->restrictOnDelete();
            $table->string('pemohon_nama', 255);
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('lokasi_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained('buildings')->nullOnDelete();
            $table->text('deskripsi');
            $table->string('prioritas', 20)->default('medium');
            $table->string('status', 30)->default('open');
            $table->text('follow_up')->nullable();
            $table->timestamp('tanggal')->useCurrent();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->integer('rejection_count')->default(0);
            $table->boolean('user_confirmation')->default(false);
            $table->timestamp('user_confirmed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('legacy_source', 20)->nullable();
            $table->bigInteger('legacy_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kategori_id', 'status'], 'pengajuan_kategori_status_idx');
            $table->index(['pemohon_id', 'created_at'], 'pengajuan_pemohon_created_idx');
            $table->index(['status', 'created_at'], 'pengajuan_status_created_idx');
            $table->index('department_id', 'pengajuan_department_idx');
            $table->index('created_at', 'pengajuan_created_at_idx');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE pengajuan
                ADD CONSTRAINT pengajuan_prioritas_check
                CHECK (prioritas IN ('low','medium','high'))
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE pengajuan
                ADD CONSTRAINT pengajuan_status_check
                CHECK (status IN ('open','in_progress','ditutup','confirmed','rejected'))
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE pengajuan
                ADD CONSTRAINT pengajuan_legacy_source_check
                CHECK (legacy_source IS NULL OR legacy_source IN ('ticket','order'))
        SQL);

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX pengajuan_legacy_unique
                ON pengajuan (legacy_source, legacy_id)
                WHERE legacy_source IS NOT NULL
        SQL);

        Schema::create('pengajuan_order_detail', function (Blueprint $table) {
            $table->foreignId('pengajuan_id')->primary()->constrained('pengajuan')->cascadeOnDelete();
            $table->string('unit_penerima', 255)->nullable();
            $table->string('kode_inventaris', 255)->nullable();
            $table->string('nama_barang', 255)->nullable();
            $table->foreignId('unit_proses_id')->nullable()->constrained('unit_proses')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('pengajuan_histori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuan')->cascadeOnDelete();
            $table->string('status', 30)->nullable();
            $table->string('action', 50);
            $table->text('notes')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_type', 20)->default('user');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['pengajuan_id', 'created_at'], 'pengajuan_histori_pengajuan_created_idx');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE pengajuan_histori
                ADD CONSTRAINT pengajuan_histori_actor_type_check
                CHECK (actor_type IN ('user','system'))
        SQL);

        Schema::create('pengajuan_pesan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuan')->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_type', 20);
            $table->text('body');
            $table->timestamps();

            $table->index(['pengajuan_id', 'created_at'], 'pengajuan_pesan_pengajuan_created_idx');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE pengajuan_pesan
                ADD CONSTRAINT pengajuan_pesan_sender_type_check
                CHECK (sender_type IN ('user','admin','system'))
        SQL);

        Schema::create('pengajuan_lampiran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuan')->cascadeOnDelete();
            $table->foreignId('histori_id')->nullable()->constrained('pengajuan_histori')->cascadeOnDelete();
            $table->foreignId('pesan_id')->nullable()->constrained('pengajuan_pesan')->cascadeOnDelete();
            $table->string('kind', 30);
            $table->string('path', 500);
            $table->string('original_name', 255)->nullable();
            $table->string('mime', 100)->nullable();
            $table->bigInteger('size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('pengajuan_id', 'pengajuan_lampiran_pengajuan_idx');
            $table->index('histori_id', 'pengajuan_lampiran_histori_idx');
            $table->index('pesan_id', 'pengajuan_lampiran_pesan_idx');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE pengajuan_lampiran
                ADD CONSTRAINT pengajuan_lampiran_kind_check
                CHECK (kind IN ('initial','admin_response','user_response','user_rejection','history','support'))
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE pengajuan_lampiran
                ADD CONSTRAINT lampiran_context_check
                CHECK (histori_id IS NULL OR pesan_id IS NULL)
        SQL);

        Schema::create('pengajuan_penugasan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuan')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'released_at'], 'pengajuan_penugasan_user_idx');
        });

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX pengajuan_penugasan_active_unique
                ON pengajuan_penugasan (pengajuan_id, user_id)
                WHERE released_at IS NULL
        SQL);

        Schema::create('role_kategori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('kategori_id')->constrained('kategori_pengajuan')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'kategori_id']);
            $table->index('kategori_id', 'role_kategori_kategori_idx');
        });

        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_id')->constrained('jenis_pengajuan')->cascadeOnDelete();
            $table->string('period', 8);
            $table->integer('last_value')->default(0);
            $table->timestamps();

            $table->unique(['jenis_id', 'period']);
        });

        Schema::create('legacy_map', function (Blueprint $table) {
            $table->id();
            $table->string('source_table', 50);
            $table->bigInteger('source_id');
            $table->foreignId('pengajuan_id')->constrained('pengajuan')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['source_table', 'source_id']);
        });

        Schema::create('migration_runs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('status', 30);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->jsonb('stats')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('migration_orphans', function (Blueprint $table) {
            $table->id();
            $table->string('source_table', 50);
            $table->bigInteger('source_id');
            $table->string('reason', 255);
            $table->jsonb('payload')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_system')->default(false);
            $table->softDeletes();
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->boolean('is_system')->default(false);
            $table->softDeletes();
        });

        Schema::table('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained('roles')->cascadeOnDelete();
        });

        DB::table('role_permissions')->orderBy('id')->get()->each(function ($row) {
            $roleId = DB::table('roles')->where('slug', $row->role)->value('id');

            DB::table('role_permissions')
                ->where('id', $row->id)
                ->update(['role_id' => $roleId]);
        });

        DB::table('role_permissions')->whereNull('role_id')->delete();

        Schema::table('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable(false)->change();
        });

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX role_permissions_role_permission_unique
                ON role_permissions (role_id, permission_id)
        SQL);

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('event');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id'], 'audit_logs_auditable_index');
            $table->index('user_id', 'audit_logs_user_id_index');
            $table->index('event', 'audit_logs_event_index');
            $table->index('created_at', 'audit_logs_created_at_index');
        });

        DB::table('roles')->whereIn('slug', ['user', 'admin', 'ipsrs'])->update(['is_system' => true]);

        DB::table('permissions')->whereIn('slug', [
            'permission', 'layout.admin', 'dashboard.it', 'dashboard.ipsrs',
            'ticket', 'order', 'master', 'user',
        ])->update(['is_system' => true]);

        DB::table('jenis_pengajuan')->insert([
            ['code' => 'IT', 'name' => 'Tiket IT / SIRS', 'prefix' => 'T', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'IPSRS', 'name' => 'Order Perbaikan / IPSRS', 'prefix' => 'OP/RTG/MTC', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_orphans');
        Schema::dropIfExists('migration_runs');
        Schema::dropIfExists('legacy_map');
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('role_kategori');
        Schema::dropIfExists('pengajuan_penugasan');
        Schema::dropIfExists('pengajuan_lampiran');
        Schema::dropIfExists('pengajuan_pesan');
        Schema::dropIfExists('pengajuan_histori');
        Schema::dropIfExists('pengajuan_order_detail');
        Schema::dropIfExists('pengajuan');
        Schema::dropIfExists('kategori_pengajuan');
        Schema::dropIfExists('jenis_pengajuan');

        Schema::dropIfExists('audit_logs');

        DB::statement('DROP INDEX IF EXISTS role_permissions_role_permission_unique');
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('is_system');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('is_system');
        });
    }
};
