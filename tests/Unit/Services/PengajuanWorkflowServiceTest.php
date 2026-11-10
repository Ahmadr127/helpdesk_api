<?php

namespace Tests\Unit\Services;

use App\Models\Department;
use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Models\Role;
use App\Models\RoleKategori;
use App\Models\User;
use App\Services\Pengajuan\PengajuanService;
use App\Services\Pengajuan\PengajuanWorkflowService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengajuanWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $jenis = JenisPengajuan::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'Tiket IT / SIRS', 'prefix' => 'T', 'is_active' => true]
        );

        $kategori = KategoriPengajuan::firstOrCreate(
            ['name' => 'Printer'],
            ['status' => 1]
        );
        $kategori->syncJenis([$jenis->id]);

        $role = Role::firstOrCreate(
            ['slug' => 'scope_admin'],
            ['name' => 'Scope Admin']
        );

        RoleKategori::firstOrCreate([
            'role_id' => $role->id,
            'kategori_id' => $kategori->id,
        ]);

        Department::firstOrCreate(['code' => 'IT'], ['name' => 'Information Technology', 'status' => 1]);
    }

    public function test_start_changes_open_to_in_progress(): void
    {
        $owner = $this->makeUser('owner@example.com');
        $admin = $this->makeUser('admin@example.com', 'scope_admin');
        $kategori = KategoriPengajuan::where('name', 'Printer')->firstOrFail();

        $pengajuan = app(PengajuanService::class)->create($owner, [
            'kategori_id' => $kategori->id,
            'deskripsi' => 'Printer rusak',
        ]);

        $result = app(PengajuanWorkflowService::class)->start($admin, $pengajuan);

        $this->assertSame('in_progress', $result->status);
        $this->assertNotNull($result->started_at);
        $this->assertSame(2, $result->historis()->count());
        $this->assertSame('started', $result->historis()->latest('id')->first()->action);
    }

    public function test_close_changes_to_pending_confirmation(): void
    {
        $owner = $this->makeUser('owner@example.com');
        $admin = $this->makeUser('admin@example.com', 'scope_admin');
        $kategori = KategoriPengajuan::where('name', 'Printer')->firstOrFail();

        $pengajuan = app(PengajuanService::class)->create($owner, [
            'kategori_id' => $kategori->id,
            'deskripsi' => 'Printer rusak',
        ]);

        $result = app(PengajuanWorkflowService::class)->close($admin, $pengajuan, 'Menunggu user.');

        $this->assertSame('ditutup', $result->status);
        $this->assertNotNull($result->closed_at);
        $this->assertSame('closed', $result->historis()->latest('id')->first()->action);
    }

    public function test_admin_confirm_changes_to_confirmed(): void
    {
        $owner = $this->makeUser('owner@example.com');
        $admin = $this->makeUser('admin@example.com', 'scope_admin');
        $kategori = KategoriPengajuan::where('name', 'Printer')->firstOrFail();

        $pengajuan = app(PengajuanService::class)->create($owner, [
            'kategori_id' => $kategori->id,
            'deskripsi' => 'Printer rusak',
        ]);

        app(PengajuanWorkflowService::class)->close($admin, $pengajuan);
        $pengajuan->refresh();

        $result = app(PengajuanWorkflowService::class)->adminConfirm($admin, $pengajuan, 'OK');

        $this->assertSame('confirmed', $result->status);
        $this->assertNotNull($result->confirmed_at);
        $this->assertSame('admin_confirmed', $result->historis()->latest('id')->first()->action);
    }

    public function test_admin_reject_changes_to_rejected(): void
    {
        $owner = $this->makeUser('owner@example.com');
        $admin = $this->makeUser('admin@example.com', 'scope_admin');
        $kategori = KategoriPengajuan::where('name', 'Printer')->firstOrFail();

        $pengajuan = app(PengajuanService::class)->create($owner, [
            'kategori_id' => $kategori->id,
            'deskripsi' => 'Printer rusak',
        ]);

        $result = app(PengajuanWorkflowService::class)->adminReject($admin, $pengajuan, 'Tidak sesuai');

        $this->assertSame('rejected', $result->status);
        $this->assertNotNull($result->rejected_at);
        $this->assertSame('admin_rejected', $result->historis()->latest('id')->first()->action);
    }

    public function test_user_confirm_selesai_changes_to_confirmed(): void
    {
        $owner = $this->makeUser('owner@example.com');
        $admin = $this->makeUser('admin@example.com', 'scope_admin');
        $kategori = KategoriPengajuan::where('name', 'Printer')->firstOrFail();

        $pengajuan = app(PengajuanService::class)->create($owner, [
            'kategori_id' => $kategori->id,
            'deskripsi' => 'Printer rusak',
        ]);

        app(PengajuanWorkflowService::class)->close($admin, $pengajuan);
        $pengajuan->refresh();

        $result = app(PengajuanWorkflowService::class)->userConfirm($owner, $pengajuan, 'Selesai', true);

        $this->assertSame('confirmed', $result->status);
        $this->assertTrue($result->user_confirmation);
        $this->assertNotNull($result->user_confirmed_at);
        $this->assertSame('user_confirmed', $result->historis()->latest('id')->first()->action);
    }

    public function test_user_confirm_belum_selesai_returns_to_in_progress(): void
    {
        $owner = $this->makeUser('owner@example.com');
        $admin = $this->makeUser('admin@example.com', 'scope_admin');
        $kategori = KategoriPengajuan::where('name', 'Printer')->firstOrFail();

        $pengajuan = app(PengajuanService::class)->create($owner, [
            'kategori_id' => $kategori->id,
            'deskripsi' => 'Printer rusak',
        ]);

        app(PengajuanWorkflowService::class)->close($admin, $pengajuan);
        $pengajuan->refresh();

        $result = app(PengajuanWorkflowService::class)->userConfirm($owner, $pengajuan, 'Belum', false);

        $this->assertSame('in_progress', $result->status);
        $this->assertFalse($result->user_confirmation);
        $this->assertSame(1, $result->rejection_count);
        $this->assertSame('user_rejected_confirmation', $result->historis()->latest('id')->first()->action);
    }

    public function test_auto_confirm_stale_pending_pengajuan(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 6, 10, 0, 0));

        $owner = $this->makeUser('owner@example.com');
        $admin = $this->makeUser('admin@example.com', 'scope_admin');
        $kategori = KategoriPengajuan::where('name', 'Printer')->firstOrFail();

        $pengajuan = app(PengajuanService::class)->create($owner, [
            'kategori_id' => $kategori->id,
            'deskripsi' => 'Printer rusak',
        ]);

        app(PengajuanWorkflowService::class)->close($admin, $pengajuan);
        $pengajuan->refresh();
        $pengajuan->update(['closed_at' => now()->subDay()->subMinute()]);

        $count = app(PengajuanWorkflowService::class)->autoConfirmStale(24);

        $this->assertSame(1, $count);
        $this->assertSame('confirmed', $pengajuan->fresh()->status);
        $this->assertNotNull($pengajuan->fresh()->confirmed_at);
        $this->assertSame('auto_confirmed', $pengajuan->fresh()->historis()->latest('id')->first()->action);
        $this->assertNull($pengajuan->fresh()->historis()->latest('id')->first()->actor_id);
        $this->assertSame('system', $pengajuan->fresh()->historis()->latest('id')->first()->actor_type);

        Carbon::setTestNow();
    }

    public function test_guards_reject_invalid_transitions(): void
    {
        $owner = $this->makeUser('owner@example.com');
        $admin = $this->makeUser('admin@example.com', 'scope_admin');
        $other = $this->makeUser('other@example.com');
        $kategori = KategoriPengajuan::where('name', 'Printer')->firstOrFail();

        $pengajuan = app(PengajuanService::class)->create($owner, [
            'kategori_id' => $kategori->id,
            'deskripsi' => 'Printer rusak',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(422);
        app(PengajuanWorkflowService::class)->start($admin, $pengajuan);
        $pengajuan->update(['status' => 'in_progress']);

        app(PengajuanWorkflowService::class)->start($admin, $pengajuan);

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(403);
        app(PengajuanWorkflowService::class)->close($other, $pengajuan);
    }

    private function makeUser(string $email, string $role = 'user'): User
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'Information Technology', 'status' => 1]);

        return User::firstOrCreate(['email' => $email], [
            'name' => 'Test User',
            'password' => bcrypt('secret'),
            'role' => $role,
            'position' => 'user',
            'status' => 1,
            'department' => 'IT',
        ]);
    }
}
