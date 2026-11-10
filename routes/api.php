<?php

use App\Http\Controllers\Api\Admin\MasterDataController as AdminMasterController;
use App\Http\Controllers\Api\Admin\UserManagementController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\FirebaseNotificationController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\MonitoringController;
use App\Http\Controllers\Api\NotificationInboxController;
use App\Http\Controllers\Api\V1\AdminPengajuanController;
use App\Http\Controllers\Api\V1\PengajuanController as PengajuanApiController;
use App\Http\Middleware\Api\AdminApiMiddleware;
use App\Http\Middleware\Api\AdministrasiUmumApiMiddleware;
use App\Http\Middleware\Api\PengajuanAdminApiMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Helpdesk RS Azra
|--------------------------------------------------------------------------
| Generated: 2026-08-28
| Auth: Sanctum Bearer Token
| Roles:
|  - guest (no token): login, register
|  - auth (any): me, logout, dashboard/user, lookup, tickets user, orders user, feedback
|  - admin IT (role=admin, permission ticket/dashboard.it): master data, user mgmt, ticket admin, feedback admin, dashboard admin
|  - admin IPSRS (role=ipsrs, permission order/dashboard.ipsrs): order perbaikan admin, dashboard IPSRS
*/

// Public Auth
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('api.auth.me');
        Route::get('/permissions', [AuthController::class, 'permissions'])->name('api.auth.permissions');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('api.auth.logoutAll');
    });

    // Dashboard
    Route::get('/dashboard/user', [DashboardController::class, 'userDashboard'])->name('api.dashboard.user');
    Route::get('/dashboard/admin', [DashboardController::class, 'adminDashboard'])->middleware(AdminApiMiddleware::class)->name('api.dashboard.admin');
    Route::get('/dashboard/administrasi', [DashboardController::class, 'administrasiDashboard'])->middleware(AdministrasiUmumApiMiddleware::class)->name('api.dashboard.administrasi');

    // Lookup (read-only master data for all authenticated users)
    Route::prefix('lookup')->name('api.lookup.')->group(function () {
        Route::get('/categories', [LookupController::class, 'categories'])->name('categories');
        Route::get('/departments', [LookupController::class, 'departments'])->name('departments');
        Route::get('/buildings', [LookupController::class, 'buildings'])->name('buildings');
        Route::get('/locations', [LookupController::class, 'locations'])->name('locations');
        Route::get('/unit-proses', [LookupController::class, 'unitProses'])->name('unit-proses');
        Route::get('/positions', [LookupController::class, 'positions'])->name('positions');
        Route::get('/priorities', [LookupController::class, 'priorities'])->name('priorities');
        Route::get('/kategori-order', [LookupController::class, 'kategoriOrders'])->name('kategori-order');
        Route::get('/ticket-statuses', [LookupController::class, 'ticketStatuses'])->name('ticket-statuses');
        Route::get('/order-statuses', [LookupController::class, 'orderStatuses'])->name('order-statuses');
        Route::get('/users', [LookupController::class, 'users'])->name('users');
    });

    // User Pengajuan — SATU controller untuk tiket IT & order IPSRS.
    // Jenis dititipkan lewat ->defaults('jenis', ...) sehingga prefix lama tetap kompatibel.
    $registerUserPengajuan = function (?string $jenis, string $prefix, string $name, string $permission) {
        Route::prefix($prefix)->name($name)->middleware($permission)->group(function () use ($jenis) {
            $def = fn ($route) => $jenis ? $route->defaults('jenis', $jenis) : $route;

            $def(Route::get('/', [PengajuanApiController::class, 'index']))->name('index');
            $def(Route::post('/', [PengajuanApiController::class, 'store']))->name('store');
            $def(Route::get('/{pengajuan}', [PengajuanApiController::class, 'show']))->name('show');
            $def(Route::put('/{pengajuan}', [PengajuanApiController::class, 'update']))->name('update');
            $def(Route::delete('/{pengajuan}', [PengajuanApiController::class, 'destroy']))->name('destroy');
            $def(Route::post('/{pengajuan}/reply', [PengajuanApiController::class, 'reply']))->name('reply');
            $def(Route::post('/{pengajuan}/confirm', [PengajuanApiController::class, 'confirm']))->name('confirm');
        });
    };

    // Alias endpoint lama yang tidak punya padanan CRUD.
    // WAJIB didaftarkan SEBELUM wildcard {pengajuan} agar tidak tertelan route show.
    Route::get('/tickets/filter/{status}', [PengajuanApiController::class, 'index'])
        ->defaults('jenis', 'IT')
        ->middleware('permission:myticket|ticket')
        ->where('status', 'all|open|pending|in_progress|closed|confirmed|ditutup')
        ->name('api.tickets.filter');
    Route::get('/order-perbaikan/konfirmasi', [PengajuanApiController::class, 'index'])
        ->defaults('jenis', 'UMUM')->defaults('status', 'ditutup')
        ->middleware('permission:myorder|order')
        ->name('api.order-perbaikan.konfirmasi');
    Route::get('/order-perbaikan/rejected', [PengajuanApiController::class, 'index'])
        ->defaults('jenis', 'UMUM')->defaults('status', 'rejected')
        ->middleware('permission:myorder|order')
        ->name('api.order-perbaikan.rejected');
    Route::post('/order-perbaikan/{pengajuan}/konfirmasi-selesai', [PengajuanApiController::class, 'confirm'])
        ->defaults('jenis', 'UMUM')
        ->middleware('permission:myorder|order')
        ->name('api.order-perbaikan.konfirmasiSelesai');

    // Unified: semua jenis pengajuan milik user (endpoint baru untuk Flutter).
    $registerUserPengajuan(null, 'pengajuan', 'api.pengajuan.', 'permission:myticket|ticket|myorder|order');

    // Backward-compat: kontrak v1 (URL tidak berubah).
    $registerUserPengajuan('IT', 'tickets', 'api.tickets.', 'permission:myticket|ticket');
    // `UMUM` (bukan `IPSRS`) karena form order & web memakai scope umum():
    // semua jenis selain SIRS, termasuk RT. Dengan kode `IPSRS` persis, order
    // berkategori RT bisa dibuat tapi tidak pernah muncul di list.
    $registerUserPengajuan('UMUM', 'order-perbaikan', 'api.order-perbaikan.', 'permission:myorder|order');

    // Feedback (dual: user create/list own, admin list all + reply)
    Route::prefix('feedback')->name('api.feedback.')->group(function () {
        Route::get('/', [FeedbackController::class, 'index'])->name('index');
        Route::post('/', [FeedbackController::class, 'store'])->name('store');
        Route::get('/{feedback}', [FeedbackController::class, 'show'])->name('show');
        Route::post('/{feedback}/reply', [FeedbackController::class, 'reply'])->middleware(AdminApiMiddleware::class)->name('reply');
        Route::delete('/{feedback}', [FeedbackController::class, 'destroy'])->middleware(AdminApiMiddleware::class)->name('destroy');
    });

    // Admin Pengajuan — SATU controller untuk tiket IT & order IPSRS.
    // Scope data = PengajuanScopeService (role_kategori), sama seperti web.
    $registerAdminPengajuan = function (?string $jenis, string $prefix, string $name, string $middleware) {
        Route::prefix($prefix)->name($name)->middleware($middleware)->group(function () use ($jenis) {
            $def = fn ($route) => $jenis ? $route->defaults('jenis', $jenis) : $route;

            $def(Route::get('/', [AdminPengajuanController::class, 'index']))->name('index');
            $def(Route::get('/all', [AdminPengajuanController::class, 'index']))->name('all');
            $def(Route::get('/open', [AdminPengajuanController::class, 'index']))->defaults('status', 'open')->name('open');
            $def(Route::get('/in-progress', [AdminPengajuanController::class, 'index']))->defaults('status', 'in_progress')->name('in-progress');
            $def(Route::get('/closed', [AdminPengajuanController::class, 'index']))->defaults('status', 'ditutup')->name('closed');
            $def(Route::get('/confirmed', [AdminPengajuanController::class, 'index']))->defaults('status', 'confirmed')->name('confirmed');
            $def(Route::get('/rejected', [AdminPengajuanController::class, 'index']))->defaults('status', 'rejected')->name('rejected');

            $def(Route::get('/history', [AdminPengajuanController::class, 'history']))->name('history');
            $def(Route::get('/history/{pengajuan}', [AdminPengajuanController::class, 'historyShow']))->name('historyShow');

            $def(Route::get('/{pengajuan}', [AdminPengajuanController::class, 'show']))->name('show');
            $def(Route::put('/{pengajuan}', [AdminPengajuanController::class, 'update']))->name('update');
            $def(Route::put('/{pengajuan}/priority', [AdminPengajuanController::class, 'updatePriority']))->name('priority');
            $def(Route::put('/{pengajuan}/status', [AdminPengajuanController::class, 'updateStatus']))->name('updateStatus');
            $def(Route::put('/{pengajuan}/submission', [AdminPengajuanController::class, 'updateSubmission']))->name('updateSubmission');
            $def(Route::post('/{pengajuan}/respond', [AdminPengajuanController::class, 'respond']))->name('respond');
            $def(Route::post('/{pengajuan}/confirm', [AdminPengajuanController::class, 'confirm']))->name('confirm');
            $def(Route::post('/{pengajuan}/reject', [AdminPengajuanController::class, 'reject']))->name('reject');
            $def(Route::post('/{pengajuan}/start', [AdminPengajuanController::class, 'start']))->name('start');

            // Penugasan PJ — kandidat bersumber dari role_kategori.
            $def(Route::get('/{pengajuan}/penugasan', [AdminPengajuanController::class, 'penugasan']))->name('penugasan');
            $def(Route::put('/{pengajuan}/penugasan', [AdminPengajuanController::class, 'syncPenugasan']))->name('penugasan.sync');
        });
    };

    // Unified admin (endpoint baru untuk Flutter) — bisa dipakai admin IT & IPSRS,
    // pemisahan data tetap lewat role_kategori (PengajuanScopeService).
    $registerAdminPengajuan(null, 'admin/pengajuan', 'api.admin.pengajuan.', PengajuanAdminApiMiddleware::class);

    // Statistik IPSRS (kontrak lama) — WAJIB sebelum wildcard {pengajuan}.
    Route::get('/administrasi-umum/order-perbaikan/statistics', [AdminPengajuanController::class, 'index'])
        ->defaults('jenis', 'UMUM')
        ->middleware(AdministrasiUmumApiMiddleware::class)
        ->name('api.administrasi-umum.order-perbaikan.statistics');

    // Backward-compat: kontrak v1 (URL tidak berubah).
    $registerAdminPengajuan('IT', 'admin/tickets', 'api.admin.tickets.', AdminApiMiddleware::class);
    // `UMUM` agar sama dengan web (AdministrasiUmum\OrderPerbaikanController::umum()).
    $registerAdminPengajuan('UMUM', 'administrasi-umum/order-perbaikan', 'api.administrasi-umum.order-perbaikan.', AdministrasiUmumApiMiddleware::class);

    // Dashboard stats IPSRS (kontrak lama).
    Route::get('/administrasi-umum/stats', [DashboardController::class, 'administrasiDashboard'])
        ->middleware(AdministrasiUmumApiMiddleware::class)
        ->name('api.administrasi-umum.stats');

    // Admin IT: User Management + Master Data
    Route::prefix('admin')->name('api.admin.')->middleware(AdminApiMiddleware::class)->group(function () {
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [AdminUserController::class, 'index'])->name('index');
            Route::post('/', [AdminUserController::class, 'store'])->name('store');
            Route::get('/{user}', [AdminUserController::class, 'show'])->name('show');
            Route::put('/{user}', [AdminUserController::class, 'update'])->name('update');
            Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('destroy');
        });

        $masterTypes = 'categories|departments|buildings|locations|unit-proses|unit_proses|positions|kategori-order|kategori_order';
        Route::prefix('master')->name('master.')->group(function () use ($masterTypes) {
            Route::get('/{type}', [AdminMasterController::class, 'index'])->where('type', $masterTypes)->name('index');
            Route::post('/{type}', [AdminMasterController::class, 'store'])->where('type', $masterTypes)->name('store');
            Route::get('/{type}/{id}', [AdminMasterController::class, 'show'])->where('type', $masterTypes)->name('show');
            Route::put('/{type}/{id}', [AdminMasterController::class, 'update'])->where('type', $masterTypes)->name('update');
            Route::delete('/{type}/{id}', [AdminMasterController::class, 'destroy'])->where('type', $masterTypes)->name('destroy');
            Route::post('/{type}/bulk-action', [AdminMasterController::class, 'bulkAction'])->where('type', $masterTypes)->name('bulk');
        });
    });

    // FCM Token (for Flutter)
    Route::prefix('user')->name('api.user.')->group(function () {
        Route::post('/fcm-token', [FcmTokenController::class, 'store'])->name('fcmToken.store');
        Route::delete('/fcm-token', [FcmTokenController::class, 'destroy'])->name('fcmToken.destroy');
        Route::get('/fcm-tokens', [FcmTokenController::class, 'index'])->name('fcmTokens.index');
        Route::put('/profile', [\App\Http\Controllers\Api\User\ProfileController::class, 'update'])->name('profile.update');
        Route::put('/password', [\App\Http\Controllers\Api\User\ProfileController::class, 'changePassword'])->name('password.update');
    });

    // Notification Inbox (database notifications) - Flutter UI
    Route::prefix('notifications')->name('api.notifications.')->group(function () {
        Route::get('/', [NotificationInboxController::class, 'index'])->name('index');
        Route::get('/unread-count', [NotificationInboxController::class, 'unreadCount'])->name('unreadCount');
        Route::post('/{id}/read', [NotificationInboxController::class, 'markRead'])->name('markRead');
        Route::post('/read-all', [NotificationInboxController::class, 'markAllRead'])->name('markAllRead');
        Route::post('/delete-old', [NotificationInboxController::class, 'deleteOld'])->name('deleteOld');
        Route::post('/settings', [NotificationInboxController::class, 'updateSettings'])->name('updateSettings');
        Route::delete('/{id}', [NotificationInboxController::class, 'destroy'])->name('destroy');
    });

    // Firebase FCM - accessible to any authenticated user (adjust middleware as needed)
    Route::prefix('firebase')->name('api.firebase.')->group(function () {
        Route::prefix('notification')->name('notification.')->group(function () {
            Route::post('/send', [FirebaseNotificationController::class, 'send'])->name('send');
            Route::post('/send-many', [FirebaseNotificationController::class, 'sendMany'])->name('sendMany');
            Route::post('/topic', [FirebaseNotificationController::class, 'sendToTopic'])->name('topic');
        });
        Route::prefix('topic')->name('topic.')->group(function () {
            Route::post('/subscribe', [FirebaseNotificationController::class, 'subscribe'])->name('subscribe');
            Route::post('/unsubscribe', [FirebaseNotificationController::class, 'unsubscribe'])->name('unsubscribe');
        });
    });

    // Monitoring Manual - Logs, Jobs, FCM, Notifikasi (admin only)
    Route::prefix('monitoring')->name('api.monitoring.')->middleware(AdminApiMiddleware::class)->group(function () {
        Route::get('/overview', [MonitoringController::class, 'overview'])->name('overview');
        Route::get('/logs', [MonitoringController::class, 'logs'])->name('logs');
        Route::get('/jobs', [MonitoringController::class, 'jobs'])->name('jobs');
        Route::get('/fcm', [MonitoringController::class, 'fcm'])->name('fcm');
        Route::get('/notifications', [MonitoringController::class, 'notifications'])->name('notifications');
    });
});

// Health check
Route::get('/health', fn () => response()->json(['success' => true, 'message' => 'Helpdesk API running', 'timestamp' => now()]))->name('api.health');
