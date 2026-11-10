<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== 10 pesans terakhir ===".PHP_EOL;
foreach (DB::table('pengajuan_pesan')->orderByDesc('created_at')->orderByDesc('id')->limit(10)->get() as $p) {
    echo "id={$p->id} pengajuan_id={$p->pengajuan_id} type={$p->sender_type} sender={$p->sender_id} at={$p->created_at} body=".mb_strimwidth((string) $p->body, 0, 60, '...').PHP_EOL;
}
echo PHP_EOL."=== 10 histori terakhir ===".PHP_EOL;
foreach (DB::table('pengajuan_histori')->orderByDesc('created_at')->orderByDesc('id')->limit(10)->get() as $h) {
    echo "id={$h->id} pengajuan_id={$h->pengajuan_id} action={$h->action} actor_type={$h->actor_type} at={$h->created_at} notes=".mb_strimwidth((string) $h->notes, 0, 60, '...').PHP_EOL;
}
echo PHP_EOL."=== histori hari ini (bukan migrasi) ===".PHP_EOL;
foreach (DB::table('pengajuan_histori')->where('created_at', '>=', '2026-11-10 15:00:00')->orderByDesc('created_at')->get() as $h) {
    echo "id={$h->id} action={$h->action} pengajuan_id={$h->pengajuan_id} at={$h->created_at} type={$h->actor_type} notes=".mb_strimwidth((string) $h->notes, 0, 80, '...').PHP_EOL;
}
