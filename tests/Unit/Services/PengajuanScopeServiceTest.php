<?php

namespace Tests\Unit\Services;

use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Models\PengajuanPenugasan;
use App\Models\Role;
use App\Models\RoleKategori;
use App\Models\User;
use App\Services\Pengajuan\PengajuanScopeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengajuanScopeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_user_sees_owned_and_assigned_category_pengajuan(): void
    {
        $jenis = JenisPengajuan::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'Tiket IT / SIRS', 'prefix' => 'T', 'is_active' => true]
        );

        $allowedCategory = KategoriPengajuan::firstOrCreate(
            ['jenis_id' => $jenis->id, 'name' => 'Printer'],
            ['status' => 1]
        );

        $otherCategory = KategoriPengajuan::firstOrCreate(
            ['jenis_id' => $jenis->id, 'name' => 'CCTV'],
            ['status' => 1]
        );

        $role = Role::firstOrCreate(
            ['slug' => 'scope_admin'],
            ['name' => 'Scope Admin']
        );

        RoleKategori::firstOrCreate([
            'role_id' => $role->id,
            'kategori_id' => $allowedCategory->id,
        ]);

        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('secret'),
                'role' => 'scope_admin',
                'status' => 1,
            ]
        );

        $otherUser = User::firstOrCreate(
            ['email' => 'other@example.com'],
            [
                'name' => 'Other User',
                'password' => bcrypt('secret'),
                'role' => 'user',
                'status' => 1,
            ]
        );

        $hiddenUser = User::firstOrCreate(
            ['email' => 'hidden@example.com'],
            [
                'name' => 'Hidden User',
                'password' => bcrypt('secret'),
                'role' => 'user',
                'status' => 1,
            ]
        );

        $allowedPengajuan = Pengajuan::create([
            'nomor' => 'T-0611-001',
            'kategori_id' => $allowedCategory->id,
            'pemohon_id' => $otherUser->id,
            'pemohon_nama' => $otherUser->name,
            'deskripsi' => 'Allowed category',
            'prioritas' => 'medium',
            'status' => 'open',
            'tanggal' => now(),
            'created_by' => $otherUser->id,
        ]);

        $ownedPengajuan = Pengajuan::create([
            'nomor' => 'T-0611-002',
            'kategori_id' => $otherCategory->id,
            'pemohon_id' => $user->id,
            'pemohon_nama' => $user->name,
            'deskripsi' => 'Owned',
            'prioritas' => 'medium',
            'status' => 'open',
            'tanggal' => now(),
            'created_by' => $user->id,
        ]);

        $hiddenPengajuan = Pengajuan::create([
            'nomor' => 'T-0611-003',
            'kategori_id' => $otherCategory->id,
            'pemohon_id' => $hiddenUser->id,
            'pemohon_nama' => $hiddenUser->name,
            'deskripsi' => 'Hidden',
            'prioritas' => 'medium',
            'status' => 'open',
            'tanggal' => now(),
            'created_by' => $hiddenUser->id,
        ]);

        $visibleIds = app(PengajuanScopeService::class)
            ->visibleTo($user)
            ->pluck('id')
            ->all();

        $this->assertContains($allowedPengajuan->id, $visibleIds);
        $this->assertContains($ownedPengajuan->id, $visibleIds);
        $this->assertNotContains($hiddenPengajuan->id, $visibleIds);
    }

    public function test_active_assignment_does_not_grant_visibility(): void
    {
        $jenis = JenisPengajuan::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'Tiket IT / SIRS', 'prefix' => 'T', 'is_active' => true]
        );

        $category = KategoriPengajuan::firstOrCreate(
            ['jenis_id' => $jenis->id, 'name' => 'Printer'],
            ['status' => 1]
        );

        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'User',
                'password' => bcrypt('secret'),
                'role' => 'user',
                'status' => 1,
            ]
        );

        $owner = User::firstOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'Owner',
                'password' => bcrypt('secret'),
                'role' => 'user',
                'status' => 1,
            ]
        );

        $pengajuan = Pengajuan::create([
            'nomor' => 'T-0611-010',
            'kategori_id' => $category->id,
            'pemohon_id' => $owner->id,
            'pemohon_nama' => $owner->name,
            'deskripsi' => 'Assigned',
            'prioritas' => 'medium',
            'status' => 'open',
            'tanggal' => now(),
            'created_by' => $owner->id,
        ]);

        PengajuanPenugasan::create([
            'pengajuan_id' => $pengajuan->id,
            'user_id' => $user->id,
            'assigned_at' => now(),
            'released_at' => null,
        ]);

        $service = app(PengajuanScopeService::class);

        $this->assertFalse($service->canView($user, $pengajuan));
        $this->assertFalse($service->canAct($user, $pengajuan));
        $this->assertNotContains($pengajuan->id, $service->visibleTo($user)->pluck('id')->all());
    }
}
