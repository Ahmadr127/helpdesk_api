<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\KategoriOrder;
use App\Models\Location;
use App\Models\OrderPerbaikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

class OrderPerbaikanFeatureTest extends TestCase
{
    use RefreshDatabase, SeedsAccessControl;

    protected function makeUser(string $role, string $email, ?Department $department = null): User
    {
        return User::create([
            'name' => 'User '.$email,
            'email' => $email,
            'password' => Hash::make('secret123'),
            'phone' => '0811',
            'position' => $role,
            'role' => $role,
            'department' => $department?->code,
            'department_id' => $department?->id,
            'status' => 1,
        ]);
    }

    protected function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    public function test_admin_can_edit_submission_fields_and_multiple_pj(): void
    {
        $this->seedRolePermissions();
        $department = Department::create(['name' => 'IPSRS', 'code' => 'IPSRS', 'status' => 1]);
        $location = Location::create(['name' => 'UGD', 'status' => 1]);
        $admin = $this->makeUser('ipsrs', 'admin@example.com', $department);
        $pj1 = $this->makeUser('ipsrs', 'pj1@example.com', $department);
        $pj2 = $this->makeUser('ipsrs', 'pj2@example.com', $department);
        $kategori = KategoriOrder::create(['name' => 'Elektrik', 'code' => 'ELK', 'status' => 1]);

        $order = OrderPerbaikan::create([
            'nomor' => 'OP/RTG/MTC-20260101001',
            'tanggal' => now(),
            'department_id' => $department->id,
            'nama_peminta' => 'Peminta',
            'kategori_order' => 'Lama',
            'lokasi' => $location->id,
            'keluhan' => 'Lama',
            'prioritas' => 'RENDAH',
            'status' => 'open',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)->put(route('admin.order-perbaikan.update-submission', $order), [
            'keluhan' => 'Keluhan baru',
            'prioritas' => 'SEDANG',
            'kategori_order' => $kategori->name,
            'lokasi' => $location->id,
            'penanggung_jawab_ids' => [$pj1->id, $pj2->id],
        ])->assertRedirect(route('admin.order-perbaikan.show', $order));

        $order->refresh();
        $this->assertSame('Keluhan baru', $order->keluhan);
        $this->assertSame('Elektrik', $order->kategori_order);
        $this->assertEqualsCanonicalizing(
            [$pj1->id, $pj2->id],
            $order->penanggungJawab()->pluck('users.id')->all()
        );
        $this->assertStringContainsString('PJ:', $order->history()->latest('id')->first()->keterangan);
    }

    public function test_api_detail_exposes_multiple_pj_ids(): void
    {
        $this->seedRolePermissions();
        $department = Department::create(['name' => 'IPSRS', 'code' => 'IPSRS', 'status' => 1]);
        $admin = $this->makeUser('ipsrs', 'admin@example.com', $department);
        $pj1 = $this->makeUser('ipsrs', 'pj1@example.com', $department);
        $pj2 = $this->makeUser('ipsrs', 'pj2@example.com', $department);

        $order = OrderPerbaikan::create([
            'nomor' => 'OP/RTG/MTC-20260101002',
            'tanggal' => now(),
            'department_id' => $department->id,
            'nama_peminta' => 'Peminta',
            'keluhan' => 'AC rusak',
            'prioritas' => 'RENDAH',
            'status' => 'open',
            'created_by' => $admin->id,
        ]);
        $order->penanggungJawab()->sync([$pj1->id, $pj2->id]);

        $resp = $this->withHeaders($this->authHeader($admin))
            ->getJson('/api/administrasi-umum/order-perbaikan/'.$order->id);

        $resp->assertStatus(200);
        $this->assertEqualsCanonicalizing([$pj1->id, $pj2->id], $resp->json('data.penanggung_jawab_ids'));
        $this->assertCount(2, $resp->json('data.penanggung_jawab'));
    }

    public function test_category_list_is_global_and_pj_follows_category_assignment(): void
    {
        $this->seedRolePermissions();
        $deptA = Department::create(['name' => 'Unit A', 'code' => 'A', 'status' => 1]);
        $deptB = Department::create(['name' => 'Unit B', 'code' => 'B', 'status' => 1]);
        $location = Location::create(['name' => 'UGD', 'status' => 1]);

        $userA = $this->makeUser('user', 'a@example.com', $deptA);
        $userB = $this->makeUser('user', 'b@example.com', $deptB);
        $userNoUnit = $this->makeUser('user', 'nounit@example.com');

        $catA = KategoriOrder::create(['name' => 'Kategori A', 'code' => 'KA', 'status' => 1]);
        $catA->users()->sync([$userA->id]); // petugas untuk kategori A
        $catB = KategoriOrder::create(['name' => 'Kategori B', 'code' => 'KB', 'status' => 1]);
        KategoriOrder::create(['name' => 'Kategori Umum', 'code' => 'KU', 'status' => 1]);

        // Semua kategori aktif terlihat untuk siapa pun (scoping dihapus).
        $this->app['auth']->forgetGuards();
        $namesA = collect($this->withHeaders($this->authHeader($userA))
            ->getJson('/api/lookup/kategori-order')->json('data'))->pluck('name')->all();
        $this->assertEqualsCanonicalizing(['Kategori A', 'Kategori B', 'Kategori Umum'], $namesA);

        $this->app['auth']->forgetGuards();
        $namesNoUnit = collect($this->withHeaders($this->authHeader($userNoUnit))
            ->getJson('/api/lookup/kategori-order')->json('data'))->pluck('name')->all();
        $this->assertEqualsCanonicalizing(['Kategori A', 'Kategori B', 'Kategori Umum'], $namesNoUnit);

        // User B boleh memakai kategori yang dulu milik unit lain.
        $this->app['auth']->forgetGuards();
        $this->withHeaders($this->authHeader($userB))->postJson('/api/order-perbaikan', [
            'kategori_order' => $catB->name,
            'lokasi' => $location->id,
            'keluhan' => 'Tes kategori',
            'prioritas' => 'RENDAH',
            'tanggal' => now()->format('Y-m-d H:i:s'),
        ])->assertStatus(201);

        // PJ otomatis mengikuti user yang ditugaskan pada kategori.
        $this->app['auth']->forgetGuards();
        $respA = $this->withHeaders($this->authHeader($userB))->postJson('/api/order-perbaikan', [
            'kategori_order' => $catA->name,
            'lokasi' => $location->id,
            'keluhan' => 'Tes PJ kategori',
            'prioritas' => 'RENDAH',
            'tanggal' => now()->format('Y-m-d H:i:s'),
        ]);
        $respA->assertStatus(201);
        $this->assertEqualsCanonicalizing([$userA->id], $respA->json('data.penanggung_jawab_ids'));

        // Kategori nonaktif tetap tidak boleh dipakai.
        $catInactive = KategoriOrder::create(['name' => 'Kategori Nonaktif', 'code' => 'KN', 'status' => 0]);
        $this->app['auth']->forgetGuards();
        $this->withHeaders($this->authHeader($userB))->postJson('/api/order-perbaikan', [
            'kategori_order' => $catInactive->name,
            'lokasi' => $location->id,
            'keluhan' => 'Tes kategori nonaktif',
            'prioritas' => 'RENDAH',
            'tanggal' => now()->format('Y-m-d H:i:s'),
        ])->assertStatus(422);
    }

}

