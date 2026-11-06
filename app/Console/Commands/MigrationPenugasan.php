<?php

namespace App\Console\Commands;

use App\Models\Pengajuan;
use App\Models\PengajuanPenugasan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrationPenugasan extends Command
{
    protected $signature = 'migration:penugasan {--dry-run}';

    protected $description = 'Resolve legacy nama_penanggung_jawab into pengajuan_penugasan';

    public function handle(): int
    {
        $orders = DB::table('order_perbaikan')->whereNotNull('nama_penanggung_jawab')->get();
        $this->info("Orders with PJ name: {$orders->count()}");

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN');

            return self::SUCCESS;
        }

        $assigned = 0;
        $orphans = 0;
        DB::transaction(function () use ($orders, &$assigned, &$orphans): void {
            foreach ($orders as $o) {
                $pengajuan = Pengajuan::where('legacy_source', 'order')->where('legacy_id', $o->id)->first();
                if (! $pengajuan) {
                    continue;
                }

                $name = trim((string) $o->nama_penanggung_jawab);
                if ($name === '') {
                    continue;
                }

                $user = User::where('name', $name)->first();
                if (! $user) {
                    $orphans++;

                    continue;
                }

                $role = Role::where('slug', $user->role)->first();

                PengajuanPenugasan::firstOrCreate([
                    'pengajuan_id' => $pengajuan->id,
                    'user_id' => $user->id,
                    'released_at' => null,
                ], [
                    'role_id' => $role?->id,
                    'assigned_by' => $pengajuan->created_by,
                    'assigned_at' => now(),
                ]);

                $assigned++;
            }
        });

        $this->info("Assigned: {$assigned}; orphans: {$orphans}.");

        return self::SUCCESS;
    }
}