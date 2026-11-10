<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rows = Illuminate\Support\Facades\DB::table('notifications')->orderByDesc('created_at')->limit(8)->get();
foreach ($rows as $r) {
    echo $r->type, ' || ', $r->data, ' || ', $r->created_at, PHP_EOL;
}
echo 'TOTAL: '.Illuminate\Support\Facades\DB::table('notifications')->count().PHP_EOL;
