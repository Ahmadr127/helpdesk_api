<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'ipsrs' => \App\Http\Middleware\AdministrasiUmumMiddleware::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Upload melebihi post_max_size server: PHP mengosongkan $_POST/$_FILES
        // sebelum validasi — tampilkan pesan ramah, bukan 500.
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, $request) {
            $message = 'Ukuran data yang dikirim terlalu besar untuk server (maksimal '.ini_get('post_max_size').'). Kecilkan file foto/lampiran lalu coba lagi.';
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => $message], 413);
            }

            return redirect()->back()->with('error', $message)->withInput($request->except(['foto', 'lampiran', 'photo']));
        });
    })->create();
