<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function dec($v) {
    $v = trim((string) $v);
    for ($i = 0; $i < 3; $i++) {
        $d = json_decode($v, true);
        if (is_array($d)) {
            if (count($d) === 1 && isset($d[0]) && is_string($d[0]) && str_starts_with(trim($d[0]), '[')) {
                $v = trim($d[0]);
                continue;
            }
            return $d;
        }
        if (is_string($d) && str_starts_with(trim($d), '[')) {
            $v = trim($d);
            continue;
        }
        return [];
    }
    return [];
}

$t = DB::table('tickets')->where('id', 23)->first();
foreach (dec($t->admin_responses) as $i) {
    echo 'admin_response: ts='.($i['timestamp'] ?? '-').' notes='.mb_strimwidth($i['notes'] ?? $i['message'] ?? '-', 0, 40, '...').PHP_EOL;
}
foreach (dec($t->user_replies) as $i) {
    echo 'user_reply: ts='.($i['timestamp'] ?? '-').' type='.($i['type'] ?? '-').' msg='.mb_strimwidth($i['message'] ?? $i['notes'] ?? '-', 0, 40, '...').PHP_EOL;
}
