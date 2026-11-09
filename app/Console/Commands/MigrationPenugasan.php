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
        $orders = DB::table('order_perbaikan')->orderBy('created_at')->orderBy('id')->get();
        $withName = $orders->filter(fn ($o) => trim((string) ($o->nama_penanggung_jawab ?? '')) !== '')->values();
        $this->info("Orders with PJ name: {$withName->count()} (total orders: {$orders->count()})");

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN');

            $would = 0;
            $orphans = 0;
            foreach ($withName as $o) {
                $pengajuan = Pengajuan::where('legacy_source', 'order')->where('legacy_id', $o->id)->first();
                if (! $pengajuan) {
                    $orphans++;
                    continue;
                }
                $users = User::where('name', trim((string) $o->nama_penanggung_jawab))->get();
                if ($users->count() !== 1) {
                    $orphans++;
                    continue;
                }
                $would++;
            }
            $this->line("would assign: {$would}; orphans: {$orphans}.");

            return self::SUCCESS;
        }

        $assigned = 0;
        $orphans = 0;
        $skipped = 0;
        DB::transaction(function () use ($withName, &$assigned, &$orphans, &$skipped): void {
            foreach ($withName as $o) {
                $pengajuan = Pengajuan::where('legacy_source', 'order')->where('legacy_id', $o->id)->first();
                if (! $pengajuan) {
                    $orphans++;
                    continue;
                }

                $name = trim((string) $o->nama_penanggung_jawab);
                if ($name === '') {
                    continue;
                }

                $users = User::where('name', $name)->get();
                if ($users->count() !== 1) {
                    $orphans++;
                    continue;
                }

                $user = $users->first();
                $role = Role::where('slug', $user->role)->first();

                $existing = PengajuanPenugasan::firstOrCreate([
                    'pengajuan_id' => $pengajuan->id,
                    'user_id' => $user->id,
                    'released_at' => null,
                ], [
                    'role_id' => $role?->id,
                    'assigned_by' => $pengajuan->created_by,
                    'assigned_at' => now(),
                ]);

                if ($existing->wasRecentlyCreated) {
                    $assigned++;
                } else {
                    $skipped++;
                }
            }
        });

        $this->info("Assigned: {$assigned}; orphans: {$orphans}; skipped (already assigned): {$skipped}.");

        return self::SUCCESS;
    }
}
