<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportSimutuUsers extends Command
{
    /**
     * @var string
     */
    protected $signature = 'users:import-simutu
        {--path= : Path ke file simutu_backup.sql (default: sql/simut... -> base_path/sql/simut... )}
        {--dry-run : Preview mapping tanpa insert ke DB}
        {--update-existing : Update user jika email sudah ada (default: skip)}
        {--only-active : Hanya import user dengan status_user=aktif}
        {--limit= : Batasi jumlah user yang diproses (untuk testing, 0 = semua)}
        {--chunk=100 : Jumlah batch insert per transaksi}
        {--preserve-ids : Pertahankan ID asli dari SIMUTU (hati-hati tabrakan PK)}
        {--create-departments : Auto-create departments dari tbl_unit jika belum ada (default: true)}
        {--admin-role-ids=1 : Daftar role_id SIMUTU yang dianggap admin di helpdesk (comma separated, default: 1)}';

    protected $description = 'Import data user dari dump PostgreSQL simutu_backup.sql (SIMUTU) ke tabel users helpdesk_app dengan penyesuaian struktur';

    public function handle(): int
    {
        // langsung /sql -> base_path('sql/simut_backup.sql') = e:\...\helpdesk_app\sql\simutu_backup.sql
        $defaultPath = base_path('sql'.DIRECTORY_SEPARATOR.'simutu_backup.sql');
        // juga support path linux style jika dijalankan di WSL/docker
        $path = $this->option('path') ?: $defaultPath;
        $path = trim($path, '"\'');
        // normalize slashes
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        if (! file_exists($path)) {
            // fallback absolut lama
            $alt1 = 'e:\\kerjaan\\Programming\\download-2026.8.28_9.10.18-server233-(server233-Standard-PC-i440FX-PIIX-1996).tar\\helpdesk_app\\sql\\simutu_backup.sql';
            $alt2 = 'E:\\kerjaan\\Programming\\sql\\simutu_backup.sql';
            $alt3 = base_path('..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'sql'.DIRECTORY_SEPARATOR.'simutu_backup.sql');
            foreach ([$alt1, $alt2, $alt3] as $alt) {
                if (file_exists($alt)) {
                    $path = $alt;
                    break;
                }
            }
            if (! file_exists($path)) {
                $this->error("File tidak ditemukan: {$path}");
                $this->line('Gunakan --path="sql/simut..." relatif atau path absolut lain.');
                $this->line('Contoh: php artisan users:import-simutu --path="sql/simut..." --dry-run');
                $this->line("Default: {$defaultPath}");

                return self::FAILURE;
            }
        }

        $dryRun = (bool) $this->option('dry-run');
        $updateExisting = (bool) $this->option('update-existing');
        $onlyActive = (bool) $this->option('only-active');
        $limit = (int) ($this->option('limit') ?? 0);
        $chunkSize = max(1, (int) ($this->option('chunk') ?? 100));
        $preserveIds = (bool) $this->option('preserve-ids');
        $createDepartments = $this->option('create-departments') !== 'false';
        if ($this->option('create-departments') === null) {
            $createDepartments = true; // default true
        }
        $adminRoleIds = array_filter(array_map('trim', explode(',', $this->option('admin-role-ids') ?? '1')));
        $adminRoleIds = array_map('intval', $adminRoleIds);

        $this->info('=== Import SIMUTU Users -> helpdesk.users ===');
        $this->line("Source : {$path}");
        $this->line('Dry-run: '.($dryRun ? 'YA' : 'TIDAK'));
        $this->line('Mode   : '.($updateExisting ? 'update jika email ada' : 'skip jika email ada'));
        $this->line('Filter : '.($onlyActive ? 'hanya status_user=aktif' : 'semua status'));
        $this->newLine();

        // --- Inspect target schema ---
        $hasUsernameCol = Schema::hasColumn('users', 'username');
        $hasDepartmentCol = Schema::hasColumn('users', 'department');
        $hasDepartmentIdCol = Schema::hasColumn('users', 'department_id');
        $hasPhoneCol = Schema::hasColumn('users', 'phone');
        $hasPositionCol = Schema::hasColumn('users', 'position');

        $this->line('Target schema (helpdesk.public.users):');
        $this->line(' - username      : '.($hasUsernameCol ? 'ADA' : 'TIDAK ADA (akan di-skip, email tetap dipakai sebagai identifier)'));
        $this->line(' - phone         : '.($hasPhoneCol ? 'ADA (nip -> phone)' : 'TIDAK ADA'));
        $this->line(' - position      : '.($hasPositionCol ? 'ADA (profesi/nama_role -> position)' : 'TIDAK ADA'));
        $this->line(' - department    : '.($hasDepartmentCol ? 'ADA (nama_unit -> department)' : 'TIDAK ADA'));
        $this->line(' - department_id : '.($hasDepartmentIdCol ? 'ADA (FK departments)' : 'TIDAK ADA'));
        $this->newLine();

        // --- Parse dump ---
        $this->line('Membaca dump (streaming)...');
        $unitMap = []; // id => ['kode_unit'=>..., 'nama_unit'=>...]
        $roleMap = []; // id => nama_role
        $usersRaw = []; // array of associative rows

        $handle = fopen($path, 'r');
        if (! $handle) {
            $this->error("Gagal membuka file: {$path}");

            return self::FAILURE;
        }

        $currentCopy = null; // ['table'=>'users'|'tbl_unit'|'tbl_role', 'columns'=>[]]
        $lineNo = 0;
        $counts = ['tbl_unit' => 0, 'tbl_role' => 0, 'users' => 0];

        while (($line = fgets($handle)) !== false) {
            $lineNo++;
            $trim = trim($line);

            // detect COPY header
            if (str_starts_with($trim, 'COPY public.')) {
                // example: COPY public.users (id, nama_lengkap, ...) FROM stdin;
                if (preg_match('/COPY public\.(\w+)\s*\(([^)]+)\)/', $trim, $m)) {
                    $tbl = $m[1];
                    $cols = array_map('trim', explode(',', $m[2]));
                    if (in_array($tbl, ['users', 'tbl_unit', 'tbl_role'], true)) {
                        $currentCopy = ['table' => $tbl, 'columns' => $cols];
                        $this->line(" -> Detected COPY {$tbl} (".count($cols)." cols) di baris {$lineNo}");
                    } else {
                        $currentCopy = null;
                    }
                }

                continue;
            }

            if ($currentCopy !== null) {
                if ($trim === '\.') {
                    $currentCopy = null;

                    continue;
                }
                // data line = tab separated, \N = NULL
                // Guard: empty line skip
                if ($trim === '') {
                    continue;
                }

                // tab split - PostgreSQL COPY uses tab delimiter, backslash escapes
                $parts = explode("\t", rtrim($line, "\r\n"));
                // parts count must match columns, but fallback if mismatch
                $cols = $currentCopy['columns'];
                if (count($parts) !== count($cols)) {
                    // try to handle if line contains escaped tabs? not needed for SIMUTU (3.5MB simple)
                    $this->warn("Baris {$lineNo}: kolom tidak match (".count($parts).' vs '.count($cols).") di {$currentCopy['table']} - skip");

                    continue;
                }
                $row = [];
                foreach ($cols as $i => $col) {
                    $val = $parts[$i] ?? null;
                    if ($val === '\N') {
                        $val = null;
                    }
                    $row[$col] = $val;
                }

                if ($currentCopy['table'] === 'tbl_unit') {
                    $id = (int) $row['id'];
                    $unitMap[$id] = [
                        'kode_unit' => $row['kode_unit'],
                        'nama_unit' => $row['nama_unit'],
                    ];
                    $counts['tbl_unit']++;
                } elseif ($currentCopy['table'] === 'tbl_role') {
                    $id = (int) $row['id'];
                    $roleMap[$id] = $row['nama_role'];
                    $counts['tbl_role']++;
                } elseif ($currentCopy['table'] === 'users') {
                    // early filter if onlyActive
                    if ($onlyActive && ($row['status_user'] ?? null) !== 'aktif') {
                        continue;
                    }
                    $counts['users']++;
                    // respect --limit: stop collecting after limit but keep scanning file
                    if ($limit > 0 && count($usersRaw) >= $limit) {
                        continue;
                    }
                    $usersRaw[] = $row;
                }
            }
        }
        fclose($handle);

        $this->newLine();
        $this->info('Parse selesai:');
        $this->line(' - tbl_unit : '.count($unitMap).' entries');
        $this->line(' - tbl_role : '.count($roleMap).' entries');
        $this->line(' - users    : '.count($usersRaw).' rows'.($limit ? " (limit {$limit})" : " (total di dump: {$counts['users']})"));
        if (count($usersRaw) === 0) {
            $this->error('Tidak ada data users yang bisa diimport. Cek filter --only-active atau --limit.');

            return self::FAILURE;
        }

        // tampilkan sample mapping role & unit
        $this->newLine();
        $this->line('Sample tbl_role mapping:');
        foreach (array_slice($roleMap, 0, 5, true) as $rid => $rname) {
            $helpRole = in_array((int) $rid, $adminRoleIds, true) ? 'admin' : 'user';
            $this->line("  role_id {$rid} '{$rname}' => helpdesk.role='{$helpRole}'");
        }
        $this->line('Sample tbl_unit mapping:');
        foreach (array_slice($unitMap, 0, 5, true) as $uid => $u) {
            $this->line("  unit_id {$uid} '{$u['kode_unit']}' / '{$u['nama_unit']}' => helpdesk.department='{$u['nama_unit']}' + department_id via departments(code='{$u['kode_unit']}', name='{$u['nama_unit']}')");
        }

        // --- Preview mapping for users (first 5) ---
        $this->newLine();
        $this->info('Preview mapping SIMUTU -> helpdesk (5 sample):');
        $headers = ['SIMUTU nama_lengkap', 'username', 'email', 'nip->phone', 'role_id->role', 'status_user->status', 'unit->dept', 'role/profesi -> position'];
        $rowsPreview = [];
        foreach (array_slice($usersRaw, 0, 5) as $r) {
            $helpRole = in_array((int) ($r['role_id'] ?? 0), $adminRoleIds, true) ? 'admin' : 'user';
            $status = ($r['status_user'] === 'aktif') ? '1' : '0';
            $unit = $unitMap[(int) ($r['unit_id'] ?? 0)] ?? null;
            $dept = $unit['nama_unit'] ?? '-';
            $roleName = $roleMap[(int) ($r['role_id'] ?? 0)] ?? null;
            $rawPos = $roleName ?? $r['profesi'] ?? '-';
            $posPreview = $this->normalizePosition($rawPos);
            if ($posPreview === 'Staff' && $rawPos !== 'Staff' && $rawPos !== '-') {
                $posPreview .= " ({$rawPos})";
            }
            $rowsPreview[] = [
                Str::limit($r['nama_lengkap'] ?? '-', 24),
                $r['username'] ?? '-',
                Str::limit($r['email'] ?? '-', 22),
                $r['nip'] ?? '-',
                ($r['role_id'] ?? '-')."->{$helpRole}",
                ($r['status_user'] ?? '-')."->{$status}",
                $dept,
                Str::limit($posPreview, 22),
            ];
        }
        $this->table($headers, $rowsPreview);

        if ($dryRun) {
            $this->warn('DRY-RUN: Tidak ada data yang ditulis ke DB. Hapus --dry-run untuk eksekusi nyata.');
            // also show what would be inserted for first row as full associative
            $sample = $usersRaw[0];
            $mapped = $this->mapUserRow($sample, $unitMap, $roleMap, $adminRoleIds, $hasUsernameCol, $hasPhoneCol, $hasPositionCol, $hasDepartmentCol);
            $this->newLine();
            $this->line("Contoh payload insert (row id SIMUTU {$sample['id']}):");
            foreach ($mapped as $k => $v) {
                $this->line("  {$k} => ".var_export($v, true));
            }
            $this->line('Catatan:');
            $this->line(" - password sudah bcrypt \$2y\$, tidak di-hash ulang (insert via DB::table untuk hindari cast 'hashed').");
            $this->line(' - email_verified_at, remember_token, created_at/updated_at akan diisi jika ada, else null/now().');
            $this->line(' - Jika email duplikat: '.($updateExisting ? 'akan di-UPDATE' : 'akan di-SKIP'));

            return self::SUCCESS;
        }

        // --- Prepare departments (auto-create) ---
        // users.department akan diisi nama_unit (bukan kode), department_id lookup via departments.name / code
        $deptCacheByName = []; // nama_unit => id
        $deptCacheByCode = []; // kode_unit => id
        if ($hasDepartmentIdCol) {
            $deptCacheByName = DB::table('departments')->pluck('id', 'name')->toArray();
            $deptCacheByCode = DB::table('departments')->pluck('id', 'code')->toArray();
        }
        if ($hasDepartmentIdCol && $createDepartments) {
            $needed = []; // kode_unit => nama_unit (unique by kode)
            foreach ($usersRaw as $r) {
                $uid = (int) ($r['unit_id'] ?? 0);
                $unit = $unitMap[$uid] ?? null;
                if ($unit) {
                    $code = $unit['kode_unit'];
                    $nama = $unit['nama_unit'];
                    $existsByCode = isset($deptCacheByCode[$code]);
                    $existsByName = isset($deptCacheByName[$nama]);
                    // juga cek case-insensitive fallback? sederhanakan: jika salah satu exists, anggap sudah ada
                    if (! $existsByCode && ! $existsByName && ! isset($needed[$code])) {
                        $needed[$code] = $nama;
                    }
                }
            }
            if (count($needed) > 0) {
                $this->line('Auto-create departments untuk '.count($needed).' unit baru (code=kode_unit, name=nama_unit)...');
                foreach ($needed as $code => $nama) {
                    try {
                        $id = DB::table('departments')->insertGetId([
                            'code' => $code,
                            'name' => $nama,
                            'status' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $deptCacheByCode[$code] = $id;
                        $deptCacheByName[$nama] = $id;
                        $this->line("  + departments '{$code}' / '{$nama}' -> id {$id}");
                    } catch (\Throwable $e) {
                        // unique violation -> fetch existing by code or name
                        $existing = DB::table('departments')->where('code', $code)->orWhere('name', $nama)->first();
                        if ($existing) {
                            $deptCacheByCode[$existing->code] = $existing->id;
                            $deptCacheByName[$existing->name] = $existing->id;
                        } else {
                            $this->warn("  Gagal create department {$code} / {$nama}: ".$e->getMessage());
                        }
                    }
                }
            }
        }
        // untuk backward compat, buat $deptCache alias ke byName (karena users.department = nama_unit)
        $deptCache = $deptCacheByName;

        // --- Ensure simplified positions exist (Staff, Manager, Direktur Utama) ---
        if ($hasPositionCol) {
            $requiredPositions = [
                ['code' => 'STAFF', 'name' => 'Staff'],
                ['code' => 'MANAGER', 'name' => 'Manager'],
                ['code' => 'DIR_UT', 'name' => 'Direktur Utama'],
            ];
            foreach ($requiredPositions as $pos) {
                $exists = DB::table('positions')->where('code', $pos['code'])->exists();
                if (! $exists) {
                    try {
                        DB::table('positions')->insert([
                            'code' => $pos['code'],
                            'name' => $pos['name'],
                            'status' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $this->line("  + positions '{$pos['code']}' / '{$pos['name']}' dibuat");
                    } catch (\Throwable $e) {
                        $this->warn("  Gagal create position {$pos['code']}: ".$e->getMessage());
                    }
                }
            }
        }

        // --- Check existing emails for skip/update decision ---
        $existingEmails = DB::table('users')->pluck('id', 'email')->toArray(); // email => id
        // also check username uniqueness if column exists
        $existingUsernames = $hasUsernameCol ? DB::table('users')->pluck('id', 'username')->toArray() : [];

        // --- Process in chunks ---
        $this->newLine();
        $this->info("Mulai import (chunk {$chunkSize})...");
        $bar = $this->output->createProgressBar(count($usersRaw));
        $bar->start();

        $stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];
        $chunks = array_chunk($usersRaw, $chunkSize);

        foreach ($chunks as $chunk) {
            DB::beginTransaction();
            try {
                foreach ($chunk as $raw) {
                    $mapped = $this->mapUserRow($raw, $unitMap, $roleMap, $adminRoleIds, $hasUsernameCol, $hasPhoneCol, $hasPositionCol, $hasDepartmentCol);

                    // resolve department_id (users.department = nama_unit)
                    if ($hasDepartmentIdCol && $mapped['department'] !== null) {
                        $namaDept = $mapped['department'];
                        // coba by name dulu (karena department = nama_unit)
                        $deptId = $deptCacheByName[$namaDept] ?? null;
                        if ($deptId === null) {
                            // fallback by code (kode_unit dari unitMap)
                            $unitTmp = $unitMap[(int) ($raw['unit_id'] ?? 0)] ?? null;
                            $kodeTmp = $unitTmp['kode_unit'] ?? null;
                            if ($kodeTmp !== null) {
                                $deptId = $deptCacheByCode[$kodeTmp] ?? null;
                            }
                        }
                        $mapped['department_id'] = $deptId;
                        if ($mapped['department_id'] === null) {
                            // last fallback: query DB case-insensitive by name atau code
                            $unitTmp = $unitMap[(int) ($raw['unit_id'] ?? 0)] ?? null;
                            $kodeTmp = $unitTmp['kode_unit'] ?? null;
                            $found = null;
                            if ($namaDept !== null) {
                                $found = DB::table('departments')->whereRaw('LOWER(name) = ?', [strtolower($namaDept)])->first();
                            }
                            if (! $found && $kodeTmp !== null) {
                                $found = DB::table('departments')->whereRaw('LOWER(code) = ?', [strtolower($kodeTmp)])->first();
                            }
                            if ($found) {
                                $mapped['department_id'] = $found->id;
                                $deptCacheByName[$found->name] = $found->id;
                                $deptCacheByCode[$found->code] = $found->id;
                            }
                        }
                    }

                    // handle username collision check
                    $email = $mapped['email'];
                    $existsId = $existingEmails[$email] ?? null;

                    if ($existsId) {
                        if ($updateExisting) {
                            // update existing user, but don't overwrite id
                            $updatePayload = $mapped;
                            // jangan update id dan created_at jika preserve-ids tidak diminta? keep created_at original? but update should keep updated_at
                            unset($updatePayload['id']);
                            // if username collision with other user, skip username
                            if ($hasUsernameCol && isset($updatePayload['username'])) {
                                $uname = $updatePayload['username'];
                                $otherId = $existingUsernames[$uname] ?? null;
                                if ($otherId && $otherId !== $existsId) {
                                    unset($updatePayload['username']);
                                }
                            }
                            // avoid re-hashing password via Eloquent - use DB directly
                            // ensure updated_at now
                            $updatePayload['updated_at'] = $raw['updated_at'] ?? now();
                            // remove null department_id if not resolvable to avoid FK error
                            try {
                                DB::table('users')->where('id', $existsId)->update($updatePayload);
                                $stats['updated']++;
                                // update cache for next rows if email changed? email is key so same
                            } catch (\Throwable $e) {
                                $stats['failed']++;
                                $stats['errors'][] = "Update email {$email}: ".$e->getMessage();
                            }
                        } else {
                            $stats['skipped']++;
                        }
                        $bar->advance();

                        continue;
                    }

                    // check username duplicate before insert
                    if ($hasUsernameCol && isset($mapped['username']) && isset($existingUsernames[$mapped['username']])) {
                        // generate variant or skip username
                        // we will unset username to avoid unique violation, keep email as identifier
                        unset($mapped['username']);
                    }

                    // handle preserve-ids: if not preserving, unset id to let auto-increment
                    if (! $preserveIds) {
                        unset($mapped['id']);
                    } else {
                        // check if id already exists (PK collision)
                        $checkId = $mapped['id'] ?? null;
                        if ($checkId && DB::table('users')->where('id', $checkId)->exists()) {
                            // if id collision, unset to auto generate
                            unset($mapped['id']);
                        }
                    }

                    // ensure required fields not null
                    if (empty($mapped['email'])) {
                        $mapped['email'] = ($raw['username'] ?? 'user'.$raw['id']).'@rsazra.co.id';
                    }
                    if (empty($mapped['name'])) {
                        $mapped['name'] = $raw['username'] ?? $mapped['email'];
                    }

                    try {
                        // Use DB::table to avoid Eloquent 'hashed' cast double hashing
                        $newId = DB::table('users')->insertGetId($mapped);
                        $existingEmails[$mapped['email']] = $newId;
                        if ($hasUsernameCol && isset($mapped['username'])) {
                            $existingUsernames[$mapped['username']] = $newId;
                        }
                        $stats['inserted']++;
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        $msg = $e->getMessage();
                        // truncate long msg
                        if (strlen($msg) > 300) {
                            $msg = substr($msg, 0, 300).'...';
                        }
                        $stats['errors'][] = "Insert {$email} (SIMUTU id {$raw['id']}): {$msg}";
                    }

                    $bar->advance();
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error('Chunk gagal: '.$e->getMessage());
                $stats['failed'] += count($chunk);
                $bar->advance(count($chunk));
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('=== Ringkasan Import ===');
        $this->table(
            ['Metric', 'Jumlah'],
            [
                ['Total di dump (terfilter)', count($usersRaw)],
                ['Inserted (baru)', $stats['inserted']],
                ['Updated (email exists)', $stats['updated']],
                ['Skipped (email exists)', $stats['skipped']],
                ['Failed', $stats['failed']],
            ]
        );

        if (count($stats['errors']) > 0) {
            $this->warn('Errors ('.count($stats['errors']).'):');
            foreach (array_slice($stats['errors'], 0, 10) as $err) {
                $this->line(" - {$err}");
            }
            if (count($stats['errors']) > 10) {
                $this->line(' ... dan '.(count($stats['errors']) - 10).' error lagi.');
            }
        }

        $newCount = DB::table('users')->count();
        $this->line("Total users di helpdesk sekarang: {$newCount}");
        $this->newLine();
        $this->line('Mapping yang digunakan:');
        $this->line('  SIMUTU.nama_lengkap  -> helpdesk.name');
        $this->line('  SIMUTU.email         -> helpdesk.email (unique, key untuk skip/update)');
        $this->line('  SIMUTU.nip           -> helpdesk.phone (jika kolom ada)');
        $this->line('  SIMUTU.username      -> helpdesk.username (jika kolom ada, else skip)');
        $this->line('  SIMUTU.password      -> helpdesk.password (bcrypt $2y$ dipertahankan, tidak re-hash)');
        $this->line('  SIMUTU.role_id       -> helpdesk.role ('.implode(',', $adminRoleIds)." => 'admin', lainnya => 'user')");
        $this->line('  tbl_role.nama_role   -> helpdesk.position (simplified: Direktur Utama / Manager dari Kepala* / Staff lainnya, fallback profesi)');
        $this->line('  SIMUTU.status_user   -> helpdesk.status (aktif=1, else 0)');
        $this->line('  tbl_unit.nama_unit   -> helpdesk.department (string) ; tbl_unit.kode_unit+nama_unit -> departments(code=kode_unit, name=nama_unit) + department_id');
        $this->line('  SIMUTU.created_at    -> helpdesk.created_at');
        $this->line('  SIMUTU.updated_at    -> helpdesk.updated_at');
        $this->newLine();
        $this->info('Selesai. Jalankan ulang dengan --dry-run untuk preview atau --update-existing untuk sinkronisasi ulang.');
        $this->line('Contoh:');
        $this->line('  php artisan users:import-simutu --dry-run                         # langsung sql/simut... (base_path/sql/...)');
        $this->line('  php artisan users:import-simutu --update-existing --only-active');
        $this->line('  php artisan users:import-simutu --path="sql/simut..." --limit=10 --dry-run');
        $this->line('  php artisan users:import-simutu --path="e:\\...\\helpdesk_app\\sql\\simutu_backup.sql" --dry-run  # absolut juga bisa');

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Map satu baris SIMUTU users ke struktur helpdesk.users
     */
    private function mapUserRow(array $raw, array $unitMap, array $roleMap, array $adminRoleIds, bool $hasUsernameCol, bool $hasPhoneCol, bool $hasPositionCol, bool $hasDepartmentCol): array
    {
        $unit = $unitMap[(int) ($raw['unit_id'] ?? 0)] ?? null;
        $roleName = $roleMap[(int) ($raw['role_id'] ?? 0)] ?? null;

        $helpRole = in_array((int) ($raw['role_id'] ?? 0), $adminRoleIds, true) ? 'admin' : 'user';
        $status = ($raw['status_user'] === 'aktif') ? 1 : 0;

        // position: simplify hanya 3 nilai: Staff, Manager (dari Kepala), Direktur Utama
        $rawPosition = $roleName ?? $raw['profesi'] ?? null;
        $position = $this->normalizePosition($rawPosition);

        // department string = nama_unit (sesuai request: department ambil nama unit)
        $department = $unit['nama_unit'] ?? null;

        // handle timestamps: \N => null, else keep as string (PG timestamp without tz)
        $emailVerifiedAt = $this->parseTimestamp($raw['email_verified_at'] ?? null);
        $createdAt = $this->parseTimestamp($raw['created_at'] ?? null) ?? now();
        $updatedAt = $this->parseTimestamp($raw['updated_at'] ?? null) ?? now();

        $payload = [
            'id' => isset($raw['id']) ? (int) $raw['id'] : null,
            'name' => $raw['nama_lengkap'] ?? $raw['username'] ?? 'Unknown',
            'email' => $raw['email'] ?? null,
            'password' => $raw['password'] ?? bcrypt('password'),
            'role' => $helpRole,
            'status' => $status,
            'email_verified_at' => $emailVerifiedAt,
            'remember_token' => $raw['remember_token'] ?? null,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];

        if ($hasPhoneCol) {
            $payload['phone'] = $raw['nip'] ?? null;
        }
        if ($hasPositionCol) {
            $payload['position'] = $position;
        }
        if ($hasDepartmentCol) {
            $payload['department'] = $department;
        }
        if ($hasUsernameCol) {
            $payload['username'] = $raw['username'] ?? null;
        }

        // department_id akan diisi di handle() setelah resolve departments
        // hapus null id jika akan di-auto increment? biar handle yang unset
        // tapi kita butuh id untuk preserve-ids mode

        // bersihkan null string kosong? biarkan null untuk nullable cols

        return $payload;
    }

    private function parseTimestamp(?string $val): ?string
    {
        if ($val === null || $val === '' || $val === '\N') {
            return null;
        }
        // PG dump format: 2026-04-15 08:29:31 (tanpa timezone)
        // Biarkan sebagai string, Laravel/Carbon akan parse
        $val = trim($val);
        if ($val === '') {
            return null;
        }
        // validasi simpel: coba create DateTime
        try {
            $dt = new \DateTime($val);

            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Sederhanakan position menjadi hanya 3 nilai:
     * - Direktur Utama (jika mengandung direktur utama / DIR_UT)
     * - Manager (jika mengandung kepala / KA_)
     * - Staff (lainnya)
     */
    private function normalizePosition(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '' || $raw === '\N') {
            return 'Staff';
        }
        $low = strtolower(trim($raw));
        // Direktur Utama -> DIR_UT
        if (str_contains($low, 'direktur utama') || $low === 'dir_ut' || str_contains($low, 'dir ut')) {
            return 'Direktur Utama';
        }
        // Kepala -> Manager (semua KA_*, kepala xxx)
        if (str_contains($low, 'kepala') || str_starts_with($low, 'ka_') || str_starts_with($low, 'ka ')) {
            return 'Manager';
        }
        // Direktur medis/spesialis etc bukan direktur utama -> anggap Manager? tetap Staff sesuai permintaan hanya 3, direktur selain utama = Manager
        if (str_contains($low, 'direktur')) {
            return 'Manager';
        }
        return 'Staff';
    }
}
