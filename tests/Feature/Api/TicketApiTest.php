<?php

namespace Tests\Feature\Api;

use App\Models\Building;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Models\Position;
use App\Models\UnitProses;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Category $category;
    protected Location $location;
    protected Department $department;
    protected Building $building;

    protected function setUp(): void
    {
        parent::setUp();
        $this->department = Department::create(['name'=>'IT Department','code'=>'IT','status'=>1]);
        Department::create(['name'=>'Keuangan','code'=>'KEU','status'=>1]);
        Position::create(['name'=>'IT','code'=>'IT','status'=>true]);
        Position::create(['name'=>'UserPos','code'=>'user','status'=>true]);
        $upSirs = UnitProses::create(['name'=>'Sistem Informasi Rumah Sakit','code'=>'SIRS','status'=>1]);
        UnitProses::create(['name'=>'Sarana','code'=>'SRNS','status'=>1]);
        $this->category = Category::create(['name'=>'Hardware','status'=>1,'unit_proses_id'=>$upSirs->id]);
        $this->building = Building::create(['name'=>'Gedung A','code'=>'A','status'=>1]);
        $this->location = Location::create(['name'=>'UGD','building_id'=>$this->building->id,'status'=>1]);

        $this->user = User::create([
            'name'=>'Regular User','email'=>'user@example.com','password'=>Hash::make('123'),
            'phone'=>'0811','position'=>'user','role'=>'user','department'=>'IT','status'=>1
        ]);
        $this->admin = User::create([
            'name'=>'Admin IT','email'=>'admin','password'=>Hash::make('123'),
            'phone'=>'0812','position'=>'IT','role'=>'admin','department'=>'IT','status'=>1
        ]);
    }

    protected function authHeader(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization'=>"Bearer $token"];
    }

    public function test_user_can_create_ticket()
    {
        $headers = $this->authHeader($this->user);
        $resp = $this->withHeaders($headers)->postJson('/api/tickets', [
            'category_id'=>$this->category->id,
            'location_id'=>$this->location->id,
            'description'=>'Komputer rusak',
            'priority'=>'high',
        ]);
        $resp->assertStatus(201)->assertJsonPath('success',true);
        $resp->assertJsonPath('data.description','Komputer rusak');
        $this->assertDatabaseHas('tickets',['description'=>'Komputer rusak']);
        // ticket_number format T-ddmm-xxx
        $this->assertMatchesRegularExpression('/^T-\d{4}-\d{3}$/', $resp->json('data.ticket_number'));
    }

    public function test_user_cannot_create_ticket_without_department()
    {
        $noDeptUser = User::create([
            'name'=>'NoDept','email'=>'nodept@example.com','password'=>Hash::make('123'),
            'phone'=>'0813','position'=>'user','role'=>'user','department'=>null,'status'=>1
        ]);
        $headers = $this->authHeader($noDeptUser);
        $resp = $this->withHeaders($headers)->postJson('/api/tickets', [
            'category_id'=>$this->category->id,
            'location_id'=>$this->location->id,
            'description'=>'Test','priority'=>'low'
        ]);
        $resp->assertStatus(500);
        $this->assertStringContainsString('departemen', strtolower($resp->json('message')));
    }

    public function test_user_can_list_own_tickets_only()
    {
        Ticket::create([
            'user_id'=>$this->user->id,'ticket_number'=>'T-0101-001','category_id'=>$this->category->id,'category'=>$this->category->name,
            'department_id'=>$this->department->id,'department'=>'IT','building_id'=>$this->building->id,'building'=>'Gedung A','location_id'=>$this->location->id,'location'=>'UGD',
            'description'=>'Own ticket','priority'=>'low','status'=>'open'
        ]);
        // other user ticket
        $other = User::create(['name'=>'Other','email'=>'other@example.com','password'=>Hash::make('123'),'phone'=>'0814','position'=>'user','role'=>'user','department'=>'IT','status'=>1]);
        Ticket::create([
            'user_id'=>$other->id,'ticket_number'=>'T-0101-002','category_id'=>$this->category->id,'category'=>$this->category->name,
            'department_id'=>$this->department->id,'department'=>'IT','building_id'=>$this->building->id,'building'=>'Gedung A','location_id'=>$this->location->id,'location'=>'UGD',
            'description'=>'Other ticket','priority'=>'low','status'=>'open'
        ]);

        $resp = $this->withHeaders($this->authHeader($this->user))->getJson('/api/tickets');
        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Own ticket', $data[0]['description']);
    }

    public function test_user_can_update_own_open_ticket()
    {
        $ticket = Ticket::create([
            'user_id'=>$this->user->id,'ticket_number'=>'T-0101-003','category_id'=>$this->category->id,'category'=>$this->category->name,
            'department_id'=>$this->department->id,'department'=>'IT','building_id'=>$this->building->id,'building'=>'Gedung A','location_id'=>$this->location->id,'location'=>'UGD',
            'description'=>'Old','priority'=>'low','status'=>'open'
        ]);
        $resp = $this->withHeaders($this->authHeader($this->user))->putJson("/api/tickets/{$ticket->id}", [
            'description'=>'Updated','category_id'=>$this->category->id,'department_id'=>$this->department->id,'location_id'=>$this->location->id,'priority'=>'high'
        ]);
        $resp->assertStatus(200)->assertJsonPath('data.description','Updated');
    }

    public function test_user_cannot_update_closed_ticket()
    {
        $ticket = Ticket::create([
            'user_id'=>$this->user->id,'ticket_number'=>'T-0101-004','category_id'=>$this->category->id,'category'=>$this->category->name,
            'department_id'=>$this->department->id,'department'=>'IT','building_id'=>$this->building->id,'building'=>'Gedung A','location_id'=>$this->location->id,'location'=>'UGD',
            'description'=>'Closed','priority'=>'low','status'=>'closed'
        ]);
        $resp = $this->withHeaders($this->authHeader($this->user))->putJson("/api/tickets/{$ticket->id}", [
            'description'=>'Try','category_id'=>$this->category->id,'department_id'=>$this->department->id,'location_id'=>$this->location->id,'priority'=>'high'
        ]);
        $resp->assertStatus(422);
    }

    public function test_user_can_reply_and_confirm_ticket()
    {
        $ticket = Ticket::create([
            'user_id'=>$this->user->id,'ticket_number'=>'T-0101-005','category_id'=>$this->category->id,'category'=>$this->category->name,
            'department_id'=>$this->department->id,'department'=>'IT','building_id'=>$this->building->id,'building'=>'Gedung A','location_id'=>$this->location->id,'location'=>'UGD',
            'description'=>'Need reply','priority'=>'medium','status'=>'in_progress'
        ]);

        $reply = $this->withHeaders($this->authHeader($this->user))->postJson("/api/tickets/{$ticket->id}/reply", [
            'message'=>'Mohon segera diperbaiki'
        ]);
        $reply->assertStatus(200);

        $confirm = $this->withHeaders($this->authHeader($this->user))->postJson("/api/tickets/{$ticket->id}/confirm", [
            'confirmation_notes'=>'Sudah bagus','action'=>'confirm'
        ]);
        $confirm->assertStatus(200)->assertJsonPath('data.status','confirmed');
    }

    public function test_user_confirm_reject_flow()
    {
        $ticket = Ticket::create([
            'user_id'=>$this->user->id,'ticket_number'=>'T-0101-006','category_id'=>$this->category->id,'category'=>$this->category->name,
            'department_id'=>$this->department->id,'department'=>'IT','building_id'=>$this->building->id,'building'=>'Gedung A','location_id'=>$this->location->id,'location'=>'UGD',
            'description'=>'Reject test','priority'=>'medium','status'=>'closed'
        ]);
        $resp = $this->withHeaders($this->authHeader($this->user))->postJson("/api/tickets/{$ticket->id}/confirm", [
            'confirmation_notes'=>'Belum selesai','action'=>'reject'
        ]);
        $resp->assertStatus(200)->assertJsonPath('data.status','in_progress');
        $this->assertEquals(1, $resp->json('data.rejection_count'));
    }

    public function test_admin_can_list_and_respond_to_ticket()
    {
        $ticket = Ticket::create([
            'user_id'=>$this->user->id,'ticket_number'=>'T-0101-007','category_id'=>$this->category->id,'category'=>$this->category->name,
            'department_id'=>$this->department->id,'department'=>'IT','building_id'=>$this->building->id,'building'=>'Gedung A','location_id'=>$this->location->id,'location'=>'UGD',
            'description'=>'Admin test','priority'=>'high','status'=>'open'
        ]);

        $list = $this->withHeaders($this->authHeader($this->admin))->getJson('/api/admin/tickets');
        $list->assertStatus(200)->assertJsonPath('success',true);

        $resp = $this->withHeaders($this->authHeader($this->admin))->postJson("/api/admin/tickets/{$ticket->id}/respond", [
            'notes'=>'Kami proses','status'=>'in_progress'
        ]);
        $resp->assertStatus(200)->assertJsonPath('data.status','in_progress');
    }

    public function test_user_cannot_access_admin_tickets()
    {
        $forbidden = $this->withHeaders($this->authHeader($this->user))->getJson('/api/admin/tickets');
        $forbidden->assertStatus(403);
    }

    public function test_ticket_photo_upload()
    {
        $headers = $this->authHeader($this->user);
        $file = UploadedFile::fake()->image('photo.jpg');
        $resp = $this->withHeaders($headers)->postJson('/api/tickets', [
            'category_id'=>$this->category->id,
            'location_id'=>$this->location->id,
            'description'=>'With photo',
            'priority'=>'low',
            'photo'=>$file,
        ]);
        // For JSON we need multipart: use post (not postJson) with file
        // Alternative: test via multipart explicitly
        // We'll redo with post
        $resp2 = $this->withHeaders($headers)->post('/api/tickets', [
            'category_id'=>$this->category->id,
            'location_id'=>$this->location->id,
            'description'=>'With photo 2',
            'priority'=>'low',
            'photo'=>$file,
        ], ['Accept'=>'application/json']);
        $resp2->assertStatus(201);
        $this->assertNotEmpty($resp2->json('data.photos'));
    }

    public function test_filter_by_status()
    {
        Ticket::create([
            'user_id'=>$this->user->id,'ticket_number'=>'T-0101-010','category_id'=>$this->category->id,'category'=>$this->category->name,
            'department_id'=>$this->department->id,'department'=>'IT','building_id'=>$this->building->id,'building'=>'Gedung A','location_id'=>$this->location->id,'location'=>'UGD',
            'description'=>'Open ticket','priority'=>'low','status'=>'open'
        ]);
        Ticket::create([
            'user_id'=>$this->user->id,'ticket_number'=>'T-0101-011','category_id'=>$this->category->id,'category'=>$this->category->name,
            'department_id'=>$this->department->id,'department'=>'IT','building_id'=>$this->building->id,'building'=>'Gedung A','location_id'=>$this->location->id,'location'=>'UGD',
            'description'=>'Confirmed ticket','priority'=>'low','status'=>'confirmed','user_confirmation'=>true
        ]);

        $resp = $this->withHeaders($this->authHeader($this->user))->getJson('/api/tickets/filter/open');
        $resp->assertStatus(200);
        $this->assertCount(1, $resp->json('data'));
        $this->assertEquals('open', $resp->json('data.0.status'));
    }
}
