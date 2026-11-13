<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

/**
 * Fitur Audit Logs — auto-capture created/updated/deleted via AuditObserver
 * (daftar model di config/audit.php) + halaman admin (permission: audit.logs).
 */
class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase, SeedsAccessControl;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolePermissions();

        Position::firstOrCreate(['name' => 'User', 'code' => 'user'], ['name' => 'User', 'code' => 'user', 'status' => true]);
        Department::firstOrCreate(['name' => 'IT', 'code' => 'IT'], ['name' => 'IT', 'code' => 'IT', 'status' => 1]);

        $this->admin = User::create([
            'name' => 'Admin Audit', 'email' => 'admin-audit@x.com', 'password' => Hash::make('123'),
            'phone' => '0811', 'position' => 'user', 'role' => 'admin', 'department' => 'IT', 'status' => 1,
        ]);

        $this->grantPermission('audit.logs', 'admin');
    }

    public function test_admin_can_view_audit_logs_page(): void
    {
        $resp = $this->actingAs($this->admin)->get(route('admin.audit-logs.index'));
        $resp->assertStatus(200);
        $resp->assertSee('Riwayat Audit Log');
    }

    public function test_admin_audit_logs_requires_permission(): void
    {
        $userWithoutPermission = User::create([
            'name' => 'Admin No Audit', 'email' => 'admin-noaudit@x.com', 'password' => Hash::make('123'),
            'phone' => '0813', 'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1,
        ]);

        $resp = $this->actingAs($userWithoutPermission)->get(route('admin.audit-logs.index'));
        $resp->assertStatus(403);
    }

    public function test_creating_pengajuan_writes_audit_log(): void
    {
        // Auto-capture via AuditObserver (config/audit.php) harus menulis AuditLog.
        $jenis = JenisPengajuan::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'Tiket IT / SIRS', 'prefix' => 'T', 'is_active' => true]
        );
        $kategori = \App\Models\KategoriPengajuan::firstOrCreate(
            ['name' => 'Printer'],
            ['status' => 1]
        );
        $kategori->syncJenis([$jenis->id]);

        $owner = User::create([
            'name' => 'Owner Audit', 'email' => 'owner-audit@x.com', 'password' => Hash::make('123'),
            'phone' => '0814', 'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1,
        ]);

        $pengajuan = Pengajuan::create([
            'nomor' => 'T-9999-001',
            'kategori_id' => $kategori->id,
            'department_id' => \App\Models\Department::firstOrCreate(['name' => 'IT', 'code' => 'IT'], ['name' => 'IT', 'code' => 'IT', 'status' => 1])->id,
            'pemohon_id' => $owner->id,
            'pemohon_nama' => $owner->name,
            'deskripsi' => 'Audit test',
            'prioritas' => 'medium',
            'status' => 'open',
            'created_by' => $owner->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Pengajuan::class,
            'auditable_id' => $pengajuan->id,
            'event' => 'created',
        ]);
    }
}
