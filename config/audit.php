<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Logs — daftar model yang akan diaudit secara otomatis.
    |--------------------------------------------------------------------------
    |
    | Setiap model di daftar ini akan otomatis melewati AuditObserver
    | (created/updated/deleted). Menambah model baru cukup menambah
    | entri di sini — tanpa setup observer baru per model.
    |
    */

    'models' => [
        App\Models\Pengajuan::class,
        // App\Models\Ticket::class,
        // App\Models\OrderPerbaikan::class,
    ],
];
