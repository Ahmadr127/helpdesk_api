<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Location;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

class ConfirmationPopupTest extends TestCase
{
    use RefreshDatabase, SeedsAccessControl;

    private Department $departemenIt;
    private KategoriPengajuan $kategoriSirs;
    private KategoriPengajuan $kategoriUmum;
    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolePermissions();

        Position::firstOrCreate(['name' => 'User', 'code' => 'user'], ['name' => 'User', 'code' => 'user', 'status' => true]);
        $this->departemenIt = Department::firstOrCreate(['name' => 'IT', 'code' => 'IT'], ['name' => 'IT', 'code' => 'IT', 'status' => 1]);
        $this->location = Location::firstOrCreate(['name' => 'UGD'], ['name' => 'UGD', 'status' => 1]);

        $jenisIt = JenisPengajuan::where('code', 'IT')->firstOrFail();
        $jenisUmum = JenisPengajuan::where('code', 'IPSRS')->firstOrFail();

        $this->kategoriSirs = KategoriPengajuan::create(['name' => 'Hardware', 'code' => 'HW', 'status' => 1]);
        $this->kategoriSirs->syncJenis([$jenisIt->id]);

        $this->kategoriUmum = KategoriPengajuan::create(['name' => 'Perbaikan', 'code' => 'PRB', 'status' => 1]);
        $this->kategoriUmum->syncJenis([$jenisUmum->id]);
    }

    private function makeUser(string $email): User
    {
        return User::create([
            'name' => 'U', 'email' => $email, 'password' => Hash::make('123'),
            'phone' => '0811', 'position' => 'user', 'role' => 'user', 'department' => 'IT',
            'department_id' => $this->departemenIt->id,
            'status' => 1,
        ]);
    }

    public function test_popup_muncul_saat_ada_tutup()
    {
        $u = $this->makeUser('a@x.com');

        $this->actingAs($u, 'sanctum')->postJson('/api/tickets', [
            'kategori_id' => $this->kategoriSirs->id, 'location_id' => $this->location->id,
            'description' => 'Tiket A', 'priority' => 'high',
        ])->assertStatus(201);
        $this->actingAs($u, 'sanctum')->postJson('/api/order-perbaikan', [
            'kategori_id' => $this->kategoriUmum->id, 'keluhan' => 'rusak', 'prioritas' => 'low',
            'tanggal' => now()->format('Y-m-d H:i:s'), 'lokasi' => $this->location->id,
        ])->assertStatus(201);

        \App\Models\Pengajuan::where('pemohon_id', $u->id)->update(['status' => 'ditutup']);

        $r = $this->actingAs($u)->get('/user/dashboard');
        $r->assertStatus(200);
        $r->assertSee('Perlu Konfirmasi (2)', false);
    }

    public function test_popup_tidak_muncul_saat_tidak_ada_tutup()
    {
        $u = $this->makeUser('b@x.com');
        $r = $this->actingAs($u)->get('/user/dashboard');
        $r->assertStatus(200);
        $r->assertDontSee('Perlu Konfirmasi', false);
    }
}
