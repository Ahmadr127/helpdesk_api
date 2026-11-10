<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'kolom kategori_pengajuan:'.PHP_EOL;
foreach (Illuminate\Support\Facades\Schema::getColumnListing('kategori_pengajuan') as $c) { echo '  - '.$c.PHP_EOL; }
echo 'kolom kategori_jenis (pivot):'.PHP_EOL;
foreach (Illuminate\Support\Facades\Schema::getColumnListing('kategori_jenis') as $c) { echo '  - '.$c.PHP_EOL; }
echo 'jumlah kategori = '.DB::table('kategori_pengajuan')->count().'; jenis_id dipakai '.DB::table('kategori_pengajuan')->distinct()->count('jenis_id').PHP_EOL;
