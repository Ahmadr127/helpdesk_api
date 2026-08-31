<?php

namespace Tests\Unit\Api;

use App\Models\Building;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Models\UnitProses;
use App\Models\User;
use App\Services\Api\TicketService;
use App\Services\Api\OrderPerbaikanService;
use App\Services\Api\MasterDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_service_generates_number()
    {
        Department::create(['name'=>'IT','code'=>'IT','status'=>1]);
        $up = UnitProses::create(['name'=>'SIRS','code'=>'SIRS','status'=>1]);
        $cat = Category::create(['name'=>'Hardware','status'=>1,'unit_proses_id'=>$up->id]);
        $b = Building::create(['name'=>'Gedung A','code'=>'A','status'=>1]);
        $loc = Location::create(['name'=>'UGD','building_id'=>$b->id,'status'=>1]);
        $user = User::create(['name'=>'User','email'=>'user@example.com','password'=>Hash::make('123'),'phone'=>'0811','position'=>'user','role'=>'user','department'=>'IT','status'=>1]);

        $service = new TicketService();
        $ticket = $service->create($user, [
            'category_id'=>$cat->id,
            'location_id'=>$loc->id,
            'description'=>'Test service',
            'priority'=>'low'
        ]);
        $this->assertNotNull($ticket->ticket_number);
        $this->assertEquals('open', $ticket->status);
    }

    public function test_order_service_create()
    {
        Department::create(['name'=>'IT','code'=>'IT','status'=>1]);
        UnitProses::create(['name'=>'SIRS','code'=>'SIRS','status'=>1]);
        $up2 = UnitProses::create(['name'=>'Sarana','code'=>'SRNS','status'=>1]);
        $b = Building::create(['name'=>'Gedung A','code'=>'A','status'=>1]);
        $loc = Location::create(['name'=>'UGD','building_id'=>$b->id,'status'=>1]);
        $user = User::create(['name'=>'User','email'=>'user@example.com','password'=>Hash::make('123'),'phone'=>'0811','position'=>'user','role'=>'user','department'=>'IT','status'=>1]);

        $service = new OrderPerbaikanService();
        $order = $service->create($user, [
            'unit_proses_code'=>'SRNS',
            'jenis_barang'=>'Umum',
            'kode_inventaris'=>'INV-001',
            'nama_barang'=>'AC',
            'lokasi'=>$loc->id,
            'keluhan'=>'Rusak',
            'prioritas'=>'RENDAH',
            'tanggal'=>now()->format('Y-m-d H:i:s')
        ]);
        $this->assertNotNull($order->nomor);
        $this->assertEquals('open',$order->status);
    }

    public function test_master_data_service_bulk()
    {
        $service = new MasterDataService();
        $b1 = Building::create(['name'=>'Gedung X','code'=>'X','status'=>1]);
        $b2 = Building::create(['name'=>'Gedung Y','code'=>'Y','status'=>1]);
        $affected = $service->bulkAction('buildings','deactivate',[$b1->id,$b2->id]);
        $this->assertEquals(2,$affected);
        $this->assertDatabaseHas('buildings',['id'=>$b1->id,'status'=>0]);
    }
}
