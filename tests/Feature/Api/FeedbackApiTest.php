<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use App\Models\Feedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FeedbackApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Department::create(['name'=>'IT','code'=>'IT','status'=>1]);
        Position::create(['name'=>'IT','code'=>'IT','status'=>true]);
        Position::create(['name'=>'User','code'=>'user','status'=>true]);
        $this->user = User::create(['name'=>'User','email'=>'user@example.com','password'=>Hash::make('123'),'phone'=>'0811','position'=>'user','role'=>'user','department'=>'IT','status'=>1]);
        $this->admin = User::create(['name'=>'Admin','email'=>'admin','password'=>Hash::make('123'),'phone'=>'0812','position'=>'IT','role'=>'admin','department'=>'IT','status'=>1]);
    }

    protected function auth(User $u){ return ['Authorization'=>'Bearer '.$u->createToken('test')->plainTextToken]; }

    public function test_user_can_create_feedback()
    {
        $resp = $this->withHeaders($this->auth($this->user))->postJson('/api/feedback', [
            'rating'=>5,'category'=>'Pelayanan','subject'=>'Bagus','message'=>'Pelayanan sangat bagus'
        ]);
        $resp->assertStatus(201)->assertJsonPath('data.rating',5);
        $this->assertDatabaseHas('feedback',['subject'=>'Bagus']);
    }

    public function test_user_can_list_own_feedback_admin_sees_all()
    {
        Feedback::create(['user_id'=>$this->user->id,'rating'=>4,'subject'=>'User FB','message'=>'msg','category'=>'Umum']);
        $other = User::create(['name'=>'Other','email'=>'other@example.com','password'=>Hash::make('123'),'phone'=>'0813','position'=>'user','role'=>'user','department'=>'IT','status'=>1]);
        Feedback::create(['user_id'=>$other->id,'rating'=>3,'subject'=>'Other FB','message'=>'msg','category'=>'Umum']);

        $userList = $this->actingAs($this->user, 'sanctum')->getJson('/api/feedback');
        $userList->assertStatus(200);
        $this->assertCount(1, $userList->json('data'));

        $adminList = $this->actingAs($this->admin, 'sanctum')->getJson('/api/feedback');
        $adminList->assertStatus(200);
        $this->assertCount(2, $adminList->json('data'));
    }

    public function test_admin_can_reply_and_delete_feedback()
    {
        $fb = Feedback::create(['user_id'=>$this->user->id,'rating'=>5,'subject'=>'Need reply','message'=>'msg','category'=>'Umum']);
        $reply = $this->actingAs($this->admin, 'sanctum')->postJson("/api/feedback/{$fb->id}/reply", ['admin_reply'=>'Terima kasih']);
        $reply->assertStatus(200)->assertJsonPath('data.admin_reply','Terima kasih');

        $del = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/feedback/{$fb->id}");
        $del->assertStatus(200);
        $this->assertDatabaseMissing('feedback',['id'=>$fb->id]);
    }

    public function test_user_cannot_reply_feedback()
    {
        $fb = Feedback::create(['user_id'=>$this->user->id,'rating'=>5,'subject'=>'Need reply','message'=>'msg','category'=>'Umum']);
        $userReply = $this->actingAs($this->user, 'sanctum')->postJson("/api/feedback/{$fb->id}/reply", ['admin_reply'=>'Try']);
        $userReply->assertStatus(403);
    }
}
