<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FcmTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Department::create(['name' => 'IT', 'code' => 'IT', 'status' => 1]);
        Position::create(['name' => 'User', 'code' => 'user', 'status' => true]);
        Position::create(['name' => 'IT', 'code' => 'IT', 'status' => true]);
        Position::create(['name' => 'Administrasi', 'code' => 'Administrasi', 'status' => true]);

        $this->user = User::create([
            'name' => 'Test', 'email' => 'test@example.com',
            'password' => Hash::make('123'), 'phone' => '0811',
            'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1
        ]);
    }

    private function authHeader(User $user = null): array
    {
        $u = $user ?? $this->user;
        return ['Authorization' => 'Bearer '.$u->createToken('test')->plainTextToken, 'Accept' => 'application/json'];
    }

    public function test_store_fcm_token(): void
    {
        $resp = $this->withHeaders($this->authHeader())->postJson('/api/user/fcm-token', [
            'token' => 'valid_fcm_token_1234567890_abcdef',
            'platform' => 'android'
        ]);

        $resp->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseHas('device_tokens', ['token' => 'valid_fcm_token_1234567890_abcdef']);
        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'fcm_token' => 'valid_fcm_token_1234567890_abcdef']);
    }

    public function test_store_fcm_token_validation_fails(): void
    {
        $resp = $this->withHeaders($this->authHeader())->postJson('/api/user/fcm-token', [
            'token' => 'short'
        ]);
        $resp->assertStatus(422)->assertJsonValidationErrors(['token']);
    }

    public function test_store_updates_existing_token(): void
    {
        $this->withHeaders($this->authHeader())->postJson('/api/user/fcm-token', [
            'token' => 'token_1234567890_aaaa', 'platform' => 'android'
        ]);

        $resp = $this->withHeaders($this->authHeader())->postJson('/api/user/fcm-token', [
            'token' => 'token_1234567890_aaaa', 'platform' => 'ios'
        ]);
        $resp->assertStatus(200);
        $this->assertDatabaseHas('device_tokens', ['token' => 'token_1234567890_aaaa', 'platform' => 'ios']);
        $this->assertDatabaseCount('device_tokens', 1);
    }

    public function test_index_returns_tokens(): void
    {
        $this->withHeaders($this->authHeader())->postJson('/api/user/fcm-token', [
            'token' => 'token_1234567890_1111', 'platform' => 'android'
        ]);
        $resp = $this->withHeaders($this->authHeader())->getJson('/api/user/fcm-tokens');
        $resp->assertStatus(200)->assertJsonPath('success', true);
        $this->assertCount(1, $resp->json('data'));
    }

    public function test_destroy_single_token(): void
    {
        $this->withHeaders($this->authHeader())->postJson('/api/user/fcm-token', [
            'token' => 'token_1234567890_2222'
        ]);
        $resp = $this->withHeaders($this->authHeader())->deleteJson('/api/user/fcm-token', [
            'token' => 'token_1234567890_2222'
        ]);
        $resp->assertStatus(200);
        $this->assertDatabaseMissing('device_tokens', ['token' => 'token_1234567890_2222']);
    }

    public function test_destroy_all_tokens(): void
    {
        $this->withHeaders($this->authHeader())->postJson('/api/user/fcm-token', ['token' => 'token_1234567890_3333']);
        $this->withHeaders($this->authHeader())->postJson('/api/user/fcm-token', ['token' => 'token_1234567890_4444']);
        $resp = $this->withHeaders($this->authHeader())->deleteJson('/api/user/fcm-token');
        $resp->assertStatus(200);
        $this->assertDatabaseCount('device_tokens', 0);
        $this->assertNull($this->user->fresh()->fcm_token);
    }

    public function test_unauthenticated_fails(): void
    {
        $resp = $this->postJson('/api/user/fcm-token', ['token' => 'token_1234567890_5555']);
        $resp->assertStatus(401);
    }
}
