<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (App\Models\Pengajuan::with('historis.actor')->where('legacy_source', 'order')->get() as $p) {
    echo "order={$p->nomor} status={$p->status}".PHP_EOL;
    foreach ($p->historis as $h) {
        echo "  historis id={$h->id} action={$h->action} status={$h->status} notes=".mb_strimwidth((string) $h->notes, 0, 80, '...')." at={$h->created_at} actor=".($h->actor?->name ?? 'system').PHP_EOL;
    }
}
