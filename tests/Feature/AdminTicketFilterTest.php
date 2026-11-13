<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

/**
 * Filter daftar pengajuan (admin tickets) — filter melekat pada tabel:
 * satu kontrol per kolom tabel + pencarian global di toolbar.
 */
class AdminTicketFilterTest extends TestCase
{
    use RefreshDatabase, SeedsAccessControl;

    protected User $admin;

    protected User $pemohon;

    protected KategoriPengajuan $kategoriPrinter;

    protected KategoriPengajuan $kategoriJaringan;

    protected Department $departemenIt;

    protected Department $departemenKeuangan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolePermissions();

        Position::firstOrCreate(['name' => 'User', 'code' => 'user'], ['name' => 'User', 'code' => 'user', 'status' => true]);
        $this->departemenIt = Department::firstOrCreate(['name' => 'IT', 'code' => 'IT'], ['name' => 'IT', 'code' => 'IT', 'status' => 1]);
        $this->departemenKeuangan = Department::firstOrCreate(['name' => 'Keuangan', 'code' => 'KEU'], ['name' => 'Keuangan', 'code' => 'KEU', 'status' => 1]);

        $jenisIt = JenisPengajuan::where('code', 'IT')->firstOrFail();

        $this->kategoriPrinter = KategoriPengajuan::create(['name' => 'Printer', 'code' => 'PRN', 'status' => 1]);
        $this->kategoriPrinter->syncJenis([$jenisIt->id]);

        $this->kategoriJaringan = KategoriPengajuan::create(['name' => 'Jaringan', 'code' => 'JRG', 'status' => 1]);
        $this->kategoriJaringan->syncJenis([$jenisIt->id]);

        // Admin memegang scope kategori (role_kategori) → bisa melihat semua pengajuan SIRS.
        Role::where('slug', 'admin')->firstOrFail()->kategoris()->sync([
            $this->kategoriPrinter->id,
            $this->kategoriJaringan->id,
        ]);

        $this->admin = User::create([
            'name' => 'Admin IT', 'email' => 'admin@x.com', 'password' => Hash::make('123'),
            'phone' => '0811', 'position' => 'user', 'role' => 'admin', 'department' => 'IT', 'status' => 1,
        ]);

        $this->pemohon = User::create([
            'name' => 'Budi Santoso', 'email' => 'budi@x.com', 'password' => Hash::make('123'),
            'phone' => '0812', 'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1,
        ]);
    }

    protected function makePengajuan(
        string $nomor,
        string $status,
        string $deskripsi,
        ?KategoriPengajuan $kategori = null,
        ?Department $departemen = null,
        ?User $pemohon = null,
        ?string $createdAt = null,
    ): Pengajuan {
        $kategori ??= $this->kategoriPrinter;
        $departemen ??= $this->departemenIt;
        $pemohon ??= $this->pemohon;

        $pengajuan = Pengajuan::create([
            'nomor' => $nomor,
            'kategori_id' => $kategori->id,
            'department_id' => $departemen->id,
            'pemohon_id' => $pemohon->id,
            'pemohon_nama' => $pemohon->name,
            'deskripsi' => $deskripsi,
            'prioritas' => 'medium',
            'status' => $status,
            'tanggal' => $createdAt ?? now(),
            'created_by' => $pemohon->id,
        ]);

        // created_at tidakfillable → set manual untuk menguji filter tanggal.
        if ($createdAt) {
            $pengajuan->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
        }

        return $pengajuan;
    }

    public function test_halaman_tiket_memiliki_filter_per_kolom_tabel(): void
    {
        $this->makePengajuan('T-001', 'open', 'Printer rusak');

        $resp = $this->actingAs($this->admin)->get(route('admin.tickets.all'));
        $resp->assertStatus(200);

        // Satu kontrol filter untuk setiap kolom tabel.
        $resp->assertSee('name="nomor"', false);
        $resp->assertSee('name="pemohon"', false);
        $resp->assertSee('name="kategori_id"', false);
        $resp->assertSee('name="department_id"', false);
        $resp->assertSee('name="deskripsi"', false);
        $resp->assertSee('name="status"', false);
        $resp->assertSee('name="date_from"', false);
        $resp->assertSee('name="date_to"', false);
        // Pencarian global (toolbar) tidak lagi ada — hanya kolom filter tabel.
        $resp->assertDontSee('name="q"', false);
        $resp->assertSee('data-pengajuan-filter-form', false);
    }

    public function test_filter_kolom_menyaring_baris(): void
    {
        $this->makePengajuan('T-001', 'open', 'Printer rusak');
        $this->makePengajuan('T-002', 'open', 'Jaringan putus', $this->kategoriJaringan, $this->departemenKeuangan);

        $admin = $this->admin;

        // Nomor tiket.
        $this->actingAs($admin)->get(route('admin.tickets.all', ['nomor' => '002']))
            ->assertSee('T-002')->assertDontSee('T-001');

        // Nama pemohon.
        $this->actingAs($admin)->get(route('admin.tickets.all', ['pemohon' => 'budi']))
            ->assertSee('T-001')->assertSee('T-002');

        // Kategori.
        $this->actingAs($admin)->get(route('admin.tickets.all', ['kategori_id' => $this->kategoriJaringan->id]))
            ->assertSee('T-002')->assertDontSee('T-001');

        // Departemen.
        $this->actingAs($admin)->get(route('admin.tickets.all', ['department_id' => $this->departemenKeuangan->id]))
            ->assertSee('T-002')->assertDontSee('T-001');

        // Deskripsi.
        $this->actingAs($admin)->get(route('admin.tickets.all', ['deskripsi' => 'putus']))
            ->assertSee('T-002')->assertDontSee('T-001');

        // Pencarian global (q) lintas kolom.
        $this->actingAs($admin)->get(route('admin.tickets.all', ['q' => 'T-002']))
            ->assertSee('T-002')->assertDontSee('T-001');
    }

    public function test_filter_status_dan_tanggal(): void
    {
        $this->makePengajuan('T-001', 'open', 'Printer rusak');
        $this->makePengajuan('T-002', 'in_progress', 'Jaringan putus');
        $this->makePengajuan('T-003', 'confirmed', 'Selesai', null, null, null, now()->subMonths(3)->toDateTimeString());

        $admin = $this->admin;

        $this->actingAs($admin)->get(route('admin.tickets.all', ['status' => 'in_progress']))
            ->assertSee('T-002')->assertDontSee('T-001')->assertDontSee('T-003');

        $this->actingAs($admin)->get(route('admin.tickets.all', [
            'date_from' => now()->subMonth()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))->assertSee('T-001')->assertDontSee('T-003');
    }

    public function test_halaman_status_menampilkan_hanya_statusnya(): void
    {
        $this->makePengajuan('T-001', 'open', 'Printer rusak');
        $this->makePengajuan('T-002', 'in_progress', 'Jaringan putus');

        $admin = $this->admin;

        $this->actingAs($admin)->get(route('admin.tickets.open'))
            ->assertSee('T-001')->assertDontSee('T-002');

        $this->actingAs($admin)->get(route('admin.tickets.in-progress'))
            ->assertSee('T-002')->assertDontSee('T-001');
    }

    public function test_admin_respond_menghasilkan_started_at(): void
    {
        $this->makePengajuan('T-001', 'open', 'Printer rusak');
        $ticket = Pengajuan::where('nomor', 'T-001')->firstOrFail();

        // Admin merespons tiket yang masih open → harus via workflow.start
        // supaya started_at terisi (basis "Status Kinerja" / waktu respon di report).
        $this->actingAs($this->admin)->post(route('admin.tickets.respond', $ticket), [
            'notes' => 'Sudah ditangani',
            'status' => 'in_progress',
        ]);

        $ticket->refresh();

        $this->assertSame('in_progress', $ticket->status);
        $this->assertNotNull($ticket->started_at);

        // Admin merespons lalu menutup (pending_confirmation → ditutup) → started_at juga wajib terisi.
        $this->makePengajuan('T-002', 'open', 'Jaringan putus');
        $ticket2 = Pengajuan::where('nomor', 'T-002')->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.tickets.respond', $ticket2), [
            'notes' => 'Sudah ditangani',
            'status' => 'pending_confirmation',
        ]);

        $ticket2->refresh();
        $this->assertSame('ditutup', $ticket2->status);
        $this->assertNotNull($ticket2->started_at);
    }

    public function test_alias_parameter_lama_tetap_berfungsi(): void
    {
        $this->makePengajuan('T-001', 'open', 'Printer rusak');
        $this->makePengajuan('T-002', 'open', 'Jaringan putus', $this->kategoriJaringan, null, null, now()->subMonths(2)->toDateTimeString());

        $admin = $this->admin;

        // search → q
        $this->actingAs($admin)->get(route('admin.tickets.all', ['search' => 'T-002']))
            ->assertSee('T-002')->assertDontSee('T-001');

        // start_date / end_date → date_from / date_to
        $this->actingAs($admin)->get(route('admin.tickets.all', [
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]))->assertSee('T-001')->assertDontSee('T-002');

        // month + year (halaman status terkunci)
        $this->actingAs($admin)->get(route('admin.tickets.open', [
            'month' => (int) now()->subMonths(2)->format('m'),
            'year' => (int) now()->subMonths(2)->format('Y'),
        ]))->assertSee('T-002')->assertDontSee('T-001');
    }
}
