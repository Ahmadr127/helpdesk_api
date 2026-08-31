<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\Location;
use App\Models\Building;
use App\Models\Position;
use App\Models\UnitProses;
use App\Models\User;
use App\Models\OrderPerbaikan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OrderPerbaikanApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $adminAdm;
    protected Location $location;
    protected UnitProses $unitProses;

    protected function setUp(): void
    {
        parent::setUp();
        Department::create(['name'=>'IT','code'=>'IT','status'=>1]);
        Position::create(['name'=>'User','code'=>'user','status'=>true]);
        Position::create(['name'=>'Administrasi','code'=>'Administrasi','status'=>true]);
        Position::create(['name'=>'IT','code'=>'IT','status'=>true]);
        $up = UnitProses::create(['name'=>'Sarana (IPSRS)','code'=>'SRNS','status'=>1]);
        UnitProses::create(['name'=>'Sistem Informasi Rumah Sakit','code'=>'SIRS','status'=>1]);
        UnitProses::create(['name'=>'Logistik Farmasi','code'=>'LOGF','status'=>1]);
        $this->unitProses = $up;
        $building = Building::create(['name'=>'Gedung A','code'=>'A','status'=>1]);
        $this->location = Location::create(['name'=>'UGD','building_id'=>$building->id,'status'=>1]);

        $this->user = User::create([
            'name'=>'Regular','email'=>'user@example.com','password'=>Hash::make('123'),
            'phone'=>'0811','position'=>'user','role'=>'user','department'=>'IT','status'=>1
        ]);
        $this->adminAdm = User::create([
            'name'=>'Admin Administrasi','email'=>'administrasi','password'=>Hash::make('123'),
            'phone'=>'0812','position'=>'Administrasi','role'=>'admin','department'=>'IT','status'=>1
        ]);
    }

    protected function authHeader(User $user): array
    {
        return ['Authorization'=>'Bearer '.$user->createToken('test')->plainTextToken];
    }

    public function test_user_can_create_order_perbaikan()
    {
        $headers = $this->authHeader($this->user);
        $resp = $this->withHeaders($headers)->postJson('/api/order-perbaikan', [
            'unit_proses_code'=> $this->unitProses->code,
            'jenis_barang'=>'Inventaris',
            'kode_inventaris'=>'INV-001',
            'nama_barang'=>'AC Rusak',
            'lokasi'=>$this->location->id,
            'keluhan'=>'Tidak dingin',
            'prioritas'=>'RENDAH',
            'tanggal'=> now()->format('Y-m-d H:i:s'),
        ]);
        $resp->assertStatus(201)->assertJsonPath('success',true);
        $this->assertDatabaseHas('order_perbaikan',['nama_barang'=>'AC Rusak']);
        $this->assertMatchesRegularExpression('/^OP\/RTG\/MTC-\d{8}\d{3}$/', $resp->json('data.nomor'));
    }

    public function test_user_cannot_create_with_sirs()
    {
        $headers = $this->authHeader($this->user);
        $resp = $this->withHeaders($headers)->postJson('/api/order-perbaikan', [
            'unit_proses_code'=>'SIRS',
            'jenis_barang'=>'Umum',
            'kode_inventaris'=>'INV-002',
            'nama_barang'=>'Test',
            'lokasi'=>$this->location->id,
            'keluhan'=>'Test',
            'prioritas'=>'SEDANG',
            'tanggal'=> now()->format('Y-m-d H:i:s'),
        ]);
        $resp->assertStatus(422);
    }

    public function test_user_can_list_own_orders()
    {
        OrderPerbaikan::create([
            'nomor'=>'OP/RTG/MTC-20250101001','tanggal'=>now(),'unit_proses'=>'SRNS','unit_proses_name'=>'Sarana',
            'unit_penerima'=>'MTC','nama_peminta'=>$this->user->name,'jenis_barang'=>'Umum','kode_inventaris'=>'INV-1',
            'nama_barang'=>'Barang 1','lokasi'=>$this->location->id,'keluhan'=>'Keluhan','prioritas'=>'RENDAH','status'=>'open','created_by'=>$this->user->id
        ]);
        $other = User::create(['name'=>'Other','email'=>'other@example.com','password'=>Hash::make('123'),'phone'=>'0813','position'=>'user','role'=>'user','department'=>'IT','status'=>1]);
        OrderPerbaikan::create([
            'nomor'=>'OP/RTG/MTC-20250101002','tanggal'=>now(),'unit_proses'=>'SRNS','unit_proses_name'=>'Sarana',
            'unit_penerima'=>'MTC','nama_peminta'=>$other->name,'jenis_barang'=>'Umum','kode_inventaris'=>'INV-2',
            'nama_barang'=>'Barang 2','lokasi'=>$this->location->id,'keluhan'=>'Keluhan','prioritas'=>'RENDAH','status'=>'open','created_by'=>$other->id
        ]);

        $resp = $this->withHeaders($this->authHeader($this->user))->getJson('/api/order-perbaikan');
        $resp->assertStatus(200);
        $this->assertCount(1, $resp->json('data'));
    }

    public function test_user_can_update_own_open_order()
    {
        $order = OrderPerbaikan::create([
            'nomor'=>'OP/RTG/MTC-20250101003','tanggal'=>now(),'unit_proses'=>'SRNS','unit_proses_name'=>'Sarana',
            'unit_penerima'=>'MTC','nama_peminta'=>$this->user->name,'jenis_barang'=>'Umum','kode_inventaris'=>'INV-3',
            'nama_barang'=>'Old','lokasi'=>$this->location->id,'keluhan'=>'Old','prioritas'=>'RENDAH','status'=>'open','created_by'=>$this->user->id
        ]);
        $resp = $this->withHeaders($this->authHeader($this->user))->putJson("/api/order-perbaikan/{$order->id}", [
            'jenis_barang'=>'Inventaris','kode_inventaris'=>'INV-3','nama_barang'=>'Updated','lokasi'=>$this->location->id,
            'keluhan'=>'Updated keluhan','prioritas'=>'TINGGI/URGENT'
        ]);
        $resp->assertStatus(200)->assertJsonPath('data.nama_barang','Updated');
    }

    public function test_user_cannot_update_in_progress_order()
    {
        $order = OrderPerbaikan::create([
            'nomor'=>'OP/RTG/MTC-20250101004','tanggal'=>now(),'unit_proses'=>'SRNS','unit_proses_name'=>'Sarana',
            'unit_penerima'=>'MTC','nama_peminta'=>$this->user->name,'jenis_barang'=>'Umum','kode_inventaris'=>'INV-4',
            'nama_barang'=>'Test','lokasi'=>$this->location->id,'keluhan'=>'Test','prioritas'=>'RENDAH','status'=>'in_progress','created_by'=>$this->user->id
        ]);
        $resp = $this->withHeaders($this->authHeader($this->user))->putJson("/api/order-perbaikan/{$order->id}", [
            'jenis_barang'=>'Umum','kode_inventaris'=>'INV-4','nama_barang'=>'Try','lokasi'=>$this->location->id,
            'keluhan'=>'Try','prioritas'=>'RENDAH'
        ]);
        $resp->assertStatus(422);
    }

    public function test_user_can_delete_own_open_order()
    {
        $order = OrderPerbaikan::create([
            'nomor'=>'OP/RTG/MTC-20250101005','tanggal'=>now(),'unit_proses'=>'SRNS','unit_proses_name'=>'Sarana',
            'unit_penerima'=>'MTC','nama_peminta'=>$this->user->name,'jenis_barang'=>'Umum','kode_inventaris'=>'INV-5',
            'nama_barang'=>'Del','lokasi'=>$this->location->id,'keluhan'=>'Del','prioritas'=>'RENDAH','status'=>'open','created_by'=>$this->user->id
        ]);
        $resp = $this->withHeaders($this->authHeader($this->user))->deleteJson("/api/order-perbaikan/{$order->id}");
        $resp->assertStatus(200);
        $this->assertDatabaseMissing('order_perbaikan',['id'=>$order->id]);
    }

    public function test_administrasi_can_manage_orders()
    {
        $order = OrderPerbaikan::create([
            'nomor'=>'OP/RTG/MTC-20250101006','tanggal'=>now(),'unit_proses'=>'SRNS','unit_proses_name'=>'Sarana',
            'unit_penerima'=>'MTC','nama_peminta'=>$this->user->name,'jenis_barang'=>'Umum','kode_inventaris'=>'INV-6',
            'nama_barang'=>'Manage','lokasi'=>$this->location->id,'keluhan'=>'Keluhan','prioritas'=>'RENDAH','status'=>'open','created_by'=>$this->user->id
        ]);

        // list as admin
        $list = $this->withHeaders($this->authHeader($this->adminAdm))->getJson('/api/administrasi-umum/order-perbaikan');
        $list->assertStatus(200);

        // update status to in_progress
        $upd = $this->withHeaders($this->authHeader($this->adminAdm))->putJson("/api/administrasi-umum/order-perbaikan/{$order->id}/status", [
            'status'=>'in_progress','follow_up'=>'Sedang dikerjakan','nama_penanggung_jawab'=>'Admin Adm'
        ]);
        $upd->assertStatus(200)->assertJsonPath('data.status','in_progress');

        // confirm
        $conf = $this->withHeaders($this->authHeader($this->adminAdm))->postJson("/api/administrasi-umum/order-perbaikan/{$order->id}/confirm");
        $conf->assertStatus(200)->assertJsonPath('data.status','confirmed');
    }

    public function test_user_cannot_access_administrasi()
    {
        $forbidden = $this->withHeaders($this->authHeader($this->user))->getJson('/api/administrasi-umum/order-perbaikan');
        $forbidden->assertStatus(403);
    }

    public function test_order_with_photo()
    {
        $headers = $this->authHeader($this->user);
        $file = UploadedFile::fake()->image('order.jpg');
        $resp = $this->withHeaders($headers)->post('/api/order-perbaikan', [
            'unit_proses_code'=> $this->unitProses->code,
            'jenis_barang'=>'Umum',
            'kode_inventaris'=>'INV-007',
            'nama_barang'=>'With Photo',
            'lokasi'=>$this->location->id,
            'keluhan'=>'Test photo',
            'prioritas'=>'SEDANG',
            'tanggal'=> now()->format('Y-m-d H:i:s'),
            'foto'=>$file,
        ], ['Accept'=>'application/json']);
        $resp->assertStatus(201);
        $this->assertNotNull($resp->json('data.foto'));
    }
}
