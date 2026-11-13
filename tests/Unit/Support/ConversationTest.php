<?php

namespace Tests\Unit\Support;

use App\Models\Department;
use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Models\PengajuanPesan;
use App\Models\Role;
use App\Models\RoleKategori;
use App\Models\User;
use App\Services\Pengajuan\PengajuanWorkflowService;
use App\Support\Conversation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected KategoriPengajuan $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        $jenis = JenisPengajuan::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'Tiket IT / SIRS', 'prefix' => 'T', 'is_active' => true]
        );

        $this->kategori = KategoriPengajuan::firstOrCreate(
            ['name' => 'Printer'],
            ['status' => 1]
        );
        $this->kategori->syncJenis([$jenis->id]);

        $role = Role::firstOrCreate(
            ['slug' => 'scope_admin'],
            ['name' => 'Scope Admin']
        );
        RoleKategori::firstOrCreate([
            'role_id' => $role->id,
            'kategori_id' => $this->kategori->id,
        ]);
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'Information Technology', 'status' => 1]);
    }

    private function makeUser(string $email, string $role = 'user'): User
    {
        return User::firstOrCreate(['email' => $email], [
            'name' => 'Test User',
            'password' => bcrypt('secret'),
            'role' => $role,
            'position' => 'user',
            'status' => 1,
            'department' => 'IT',
        ]);
    }

    private function makePengajuan(User $owner, string $deskripsi = 'Printer rusak'): Pengajuan
    {
        return Pengajuan::create([
            'nomor' => 'T-TEST-'.uniqid(),
            'kategori_id' => $this->kategori->id,
            'department_id' => Department::where('code', 'IT')->firstOrFail()->id,
            'pemohon_id' => $owner->id,
            'pemohon_nama' => $owner->name,
            'deskripsi' => $deskripsi,
            'status' => 'open',
            'tanggal' => now(),
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
    }

    public function test_responses_empty_when_no_messages_and_no_matching_history(): void
    {
        $owner = $this->makeUser('nodata@example.com');
        $pengajuan = $this->makePengajuan($owner);

        $this->assertSame([], Conversation::responses($pengajuan));
    }

    public function test_responses_maps_admin_and_user_bubbles_in_order(): void
    {
        $owner = $this->makeUser('bubble@example.com');
        $admin = $this->makeUser('adminhelp@example.com', 'scope_admin');
        $pengajuan = $this->makePengajuan($owner);

        Carbon::setTestNow(Carbon::create(2026, 3, 10, 9, 0, 0));
        $userMsg = PengajuanPesan::create([
            'pengajuan_id' => $pengajuan->id,
            'sender_id' => $owner->id,
            'sender_type' => 'user',
            'body' => 'Printer mati setelah diganti toner.',
        ]);
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 0, 0));
        $adminMsg = PengajuanPesan::create([
            'pengajuan_id' => $pengajuan->id,
            'sender_id' => $admin->id,
            'sender_type' => 'admin',
            'body' => 'Kami cek ke lokasi.',
        ]);

        $responses = Conversation::responses($pengajuan->fresh(['pesans.sender', 'lampirans', 'historis']));

        $this->assertCount(2, $responses);
        $this->assertSame('user', $responses[0]['type']);
        $this->assertSame('Printer mati setelah diganti toner.', $responses[0]['message']);
        $this->assertSame('Test User', $responses[0]['admin_name']);
        $this->assertSame('admin', $responses[1]['type']);
        $this->assertSame('Test User', $responses[1]['admin_name']);
        $this->assertArrayHasKey('status', $responses[0]);
        $this->assertArrayHasKey('status_label', $responses[0]);
    }

    public function test_responses_attaches_photo_to_message_bubble(): void
    {
        $owner = $this->makeUser('photo@example.com');
        $admin = $this->makeUser('adminphoto@example.com', 'scope_admin');
        $pengajuan = $this->makePengajuan($owner);

        $msg = PengajuanPesan::create([
            'pengajuan_id' => $pengajuan->id,
            'sender_id' => $admin->id,
            'sender_type' => 'admin',
            'body' => 'Bukti foto.',
        ]);
        $msg->lampirans()->create([
            'pengajuan_id' => $pengajuan->id,
            'kind' => 'admin_response',
            'path' => 'pengajuan/IT/2026/03/foo.jpg',
            'original_name' => 'foo.jpg',
        ]);

        $responses = Conversation::responses($pengajuan->fresh(['pesans.sender', 'lampirans', 'historis']));

        $this->assertSame('pengajuan/IT/2026/03/foo.jpg', $responses[0]['photo']);
    }

    public function test_responses_merges_user_confirmation_bubbles(): void
    {
        $owner = $this->makeUser('confirm@example.com');
        $admin = $this->makeUser('adminconfirm@example.com', 'scope_admin');
        $pengajuan = $this->makePengajuan($owner);

        PengajuanPesan::create([
            'pengajuan_id' => $pengajuan->id,
            'sender_id' => $owner->id,
            'sender_type' => 'user',
            'body' => 'Sudah saya cek.',
        ]);

        // Histori confirmed dengan notes berisi teks → bubble konfirmasi masuk.
        $pengajuan->historis()->create([
            'status' => 'confirmed',
            'action' => 'confirmed',
            'notes' => 'Oke, ini konfirm saya.',
            'actor_id' => $owner->id,
            'actor_type' => 'user',
        ]);
        // Histori confirmed dengan notes default → TIDAK masuk.
        $pengajuan->historis()->create([
            'status' => 'confirmed',
            'action' => 'confirmed',
            'notes' => 'Pengajuan dikonfirmasi.',
            'actor_id' => $owner->id,
            'actor_type' => 'user',
        ]);

        $responses = Conversation::responses($pengajuan->fresh(['pesans.sender', 'lampirans', 'historis.actor']));

        $confirms = collect($responses)->where('action', 'confirm')->values();
        $this->assertCount(1, $confirms);
        $this->assertSame('Oke, ini konfirm saya.', $confirms->first()['message']);
    }

    public function test_responses_falls_back_to_history_when_no_messages(): void
    {
        $owner = $this->makeUser('fallback@example.com');
        $admin = $this->makeUser('adminfallback@example.com', 'scope_admin');
        $pengajuan = $this->makePengajuan($owner);

        $pengajuan->historis()->create([
            'status' => 'open',
            'action' => 'created',
            'notes' => 'Pengajuan dibuat.',
            'actor_id' => $owner->id,
            'actor_type' => 'user',
        ]);
        $pengajuan->historis()->create([
            'status' => 'in_progress',
            'action' => 'admin_response',
            'notes' => 'Kami cek lokasi.',
            'actor_id' => $admin->id,
            'actor_type' => 'admin',
        ]);

        $responses = Conversation::responses($pengajuan->fresh(['pesans.sender', 'lampirans', 'historis.actor']));

        $this->assertCount(1, $responses);
        $this->assertSame('admin', $responses[0]['type']);
        $this->assertSame('Kami cek lokasi.', $responses[0]['message']);
    }

    public function test_initial_lampirans_only_returns_kind_initial(): void
    {
        $owner = $this->makeUser('initial@example.com');
        $pengajuan = $this->makePengajuan($owner);

        $pengajuan->lampirans()->create([
            'pengajuan_id' => $pengajuan->id,
            'kind' => 'initial',
            'path' => 'pengajuan/IT/initial.jpg',
            'original_name' => 'initial.jpg',
        ]);
        $pengajuan->lampirans()->create([
            'pengajuan_id' => $pengajuan->id,
            'kind' => 'user_response',
            'path' => 'pengajuan/IT/reply.jpg',
            'original_name' => 'reply.jpg',
        ]);

        $lampirans = Conversation::initialLampirans($pengajuan->fresh('lampirans'));

        $this->assertCount(1, $lampirans);
        $this->assertSame('initial.jpg', $lampirans->first()->original_name);
    }
}
