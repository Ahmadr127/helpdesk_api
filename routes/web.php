<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BuildingController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\FcmMonitoringController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\KategoriOrderController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReportSirsController;
use App\Http\Controllers\Admin\TicketAdminController;
use App\Http\Controllers\Admin\UnitProsesController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AdministrasiUmum\DashboardController as AdministrasiUmumDashboardController;
use App\Http\Controllers\AdministrasiUmum\OrderPerbaikanController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\User\FAQController;
use App\Http\Controllers\User\FeedbackController as UserFeedbackController;
use App\Http\Controllers\User\InformationController;
use App\Http\Controllers\User\KnowledgeBaseController;
use App\Http\Controllers\User\NotificationController as UserNotificationController;
use App\Http\Controllers\User\TicketController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserReportController;
use App\Http\Controllers\User\UserSettingsController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LogoutController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// Middleware auth group untuk semua user yang sudah login
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();

        // Dinamis: pilih dashboard sesuai permission di DB
        if ($user->hasPermission('admin.dashboard')) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->hasPermission('ipsrs.dashboard')) {
            return redirect()->route('admin.ipsrs.dashboard');
        }

        return redirect()->route('user.dashboard');
    })->name('dashboard');

    // User routes
    Route::get('/user/dashboard', [UserController::class, 'dashboard'])->name('user.dashboard');
    Route::get('/information', [InformationController::class, 'index'])->name('user.information');

    // User Administrasi Umum routes - GREEN theme (Maintenance) permission protected
    Route::prefix('user/administrasi-umum')->name('user.administrasi-umum.')->middleware('permission:order.view')->group(function () {
        Route::get('/', [App\Http\Controllers\User\AdministrasiUmumController::class, 'index'])->name('index');
        Route::get('/order-barang', [App\Http\Controllers\User\AdministrasiUmumController::class, 'orderBarang'])->name('order-barang');
        Route::get('/order-barang/konfirmasi', [App\Http\Controllers\User\AdministrasiUmumController::class, 'orderBarangKonfirmasi'])->name('order-barang.konfirmasi');
        Route::get('/order-barang/tutup', [App\Http\Controllers\User\AdministrasiUmumController::class, 'orderBarangTutup'])->name('order-barang.tutup');
        Route::get('/order-barang/reject', [App\Http\Controllers\User\AdministrasiUmumController::class, 'orderBarangReject'])->name('order-barang.reject');
        Route::get('/dokumen', [App\Http\Controllers\User\AdministrasiUmumController::class, 'dokumen'])->name('dokumen');
        Route::get('/formulir', [App\Http\Controllers\User\AdministrasiUmumController::class, 'formulir'])->name('formulir');
        Route::get('/prosedur', [App\Http\Controllers\User\AdministrasiUmumController::class, 'prosedur'])->name('prosedur');

        // Order Perbaikan Routes - page terpisah for create (GREEN)
        Route::prefix('order-perbaikan')->name('order-perbaikan.')->group(function () {
            Route::get('/', [App\Http\Controllers\User\AdministrasiUmumController::class, 'indexOrderPerbaikan'])->name('index');
            Route::get('/create', [App\Http\Controllers\User\AdministrasiUmumController::class, 'createOrderPerbaikan'])->name('create')->middleware('permission:order.create');
            Route::post('/', [App\Http\Controllers\User\AdministrasiUmumController::class, 'storeOrderPerbaikan'])->name('store')->middleware('permission:order.create');
            Route::get('/{orderPerbaikan}', [App\Http\Controllers\User\AdministrasiUmumController::class, 'showOrderPerbaikan'])->name('show');
            Route::post('/{orderPerbaikan}/konfirmasi-selesai', [App\Http\Controllers\User\AdministrasiUmumController::class, 'confirmOrderPerbaikan'])->name('konfirmasi-selesai');
            Route::get('/{orderPerbaikan}/edit', [App\Http\Controllers\User\AdministrasiUmumController::class, 'editOrderPerbaikan'])->name('edit')->middleware('permission:order.edit.own|order.manage');
            Route::put('/{orderPerbaikan}', [App\Http\Controllers\User\AdministrasiUmumController::class, 'updateOrderPerbaikan'])->name('update')->middleware('permission:order.edit.own|order.manage');
            Route::delete('/{orderPerbaikan}', [App\Http\Controllers\User\AdministrasiUmumController::class, 'deleteOrderPerbaikan'])->name('delete')->middleware('permission:order.edit.own|order.manage');
        });
    });

    Route::prefix('ticket')->name('user.ticket.')->middleware('permission:ticket.view')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::get('/create', [TicketController::class, 'create'])->name('create')->middleware('permission:ticket.create');
        Route::post('/store', [TicketController::class, 'store'])->name('store')->middleware('permission:ticket.create');
        Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
        Route::get('/{ticket}/edit', [TicketController::class, 'edit'])->name('edit')->middleware('permission:ticket.edit.own|ticket.manage');
        Route::put('/{ticket}', [TicketController::class, 'update'])->name('update')->middleware('permission:ticket.edit.own|ticket.manage');
        Route::get('/status/{status}', [TicketController::class, 'filterByStatus'])
            ->name('filter.status')
            ->where('status', 'all|open|pending|in_progress|closed|confirmed');
        Route::post('/{ticket}/confirm', [TicketController::class, 'confirm'])->name('confirm');
        Route::post('/{ticket}/reply', [TicketController::class, 'reply'])->name('reply');
    });
    // FIX duplicate name user.ticket.reply (sebelumnya bentrok dengan ticket/{ticket}/reply di atas) — ganti jadi user.ticket.reply.legacy agar artisan optimize bisa cache
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('user.ticket.reply.legacy')->middleware('permission:ticket.view');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('user.ticket.destroy')->middleware('permission:ticket.edit.own|ticket.manage');

    Route::get('/faq', [FAQController::class, 'index'])->name('user.faq')->middleware('permission:faq.view');
    Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('user.knowledge-base')->middleware('permission:knowledge.view');
    Route::get('/profile', [UserController::class, 'profile'])->name('user.profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('user.profile.update');
    Route::put('/change-password', [UserController::class, 'changePassword'])->name('user.password.update');
    Route::get('/settings', [UserSettingsController::class, 'index'])->name('user.settings');
    Route::post('/settings', [UserSettingsController::class, 'update'])->name('user.settings.update');
    Route::get('/report', [UserReportController::class, 'index'])->name('user.report');
    Route::post('/report', [UserReportController::class, 'store'])->name('user.report.store');
    Route::post('/feedback', [UserFeedbackController::class, 'store'])
        ->name('user.feedback.store');

    // Admin routes
    Route::prefix('admin')->name('admin.')->middleware(AdminMiddleware::class)->group(function () {
        // Dashboard - IT blue theme, requires permission
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard')->middleware('permission:admin.dashboard|ticket.manage');

        // Permission Management
        Route::prefix('permissions')->name('permissions.')->middleware('permission:user.manage')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PermissionController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\PermissionController::class, 'store'])->name('store');
            Route::delete('/{permission}', [\App\Http\Controllers\Admin\PermissionController::class, 'destroy'])->name('destroy');
            Route::post('/role', [\App\Http\Controllers\Admin\PermissionController::class, 'updateRole'])->name('role.update');
            Route::put('/user/{user}', [\App\Http\Controllers\Admin\PermissionController::class, 'updateUser'])->name('user.update');
        });

        // Master Data Routes - GREEN not for admin IT? Keep BLUE for IT but permission protected
        Route::prefix('master')->name('master.')->middleware('permission:master.view|master.manage')->group(function () {
            Route::get('/', [MasterDataController::class, 'index'])->name('index');
            Route::resource('categories', CategoryController::class)->middleware('permission:master.manage');
            Route::resource('departments', DepartmentController::class)->middleware('permission:master.manage');
            Route::resource('buildings', BuildingController::class)->middleware('permission:master.manage');
            Route::resource('locations', LocationController::class)->middleware('permission:master.manage');
            Route::resource('unit-proses', UnitProsesController::class)->middleware('permission:master.manage');
            Route::resource('kategori-order', KategoriOrderController::class)->middleware('permission:master.manage');
            Route::resource('positions', PositionController::class)->middleware('permission:master.manage');
            Route::patch('positions/{position}/toggle-status', [PositionController::class, 'toggleStatus'])->name('positions.toggle-status')->middleware('permission:master.manage');

            // Bulk action dan update limit routes
            Route::post('/{type}/bulk-action', [MasterDataController::class, 'bulkAction'])->name('bulk-action')->middleware('permission:master.manage');
            Route::post('/{type}/update-limit', [MasterDataController::class, 'updateLimit'])->name('update-limit')->middleware('permission:master.manage');
            Route::post('/save-settings', [MasterDataController::class, 'saveSettings'])->name('saveSettings')->middleware('permission:master.manage');
            Route::get('/{type}/data', [MasterDataController::class, 'getData'])->name('getData');
        });

        // User Management routes - permission protected
        Route::middleware('permission:user.view|user.manage')->group(function () {
            Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create')->middleware('permission:user.manage');
            Route::post('/users', [UserManagementController::class, 'store'])->name('users.store')->middleware('permission:user.manage');
            Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit')->middleware('permission:user.manage');
            Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update')->middleware('permission:user.manage');
            Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy')->middleware('permission:user.manage');
        });

        // Admin ticket routes - BLUE theme (ticket IT) permission protected
        Route::prefix('tickets')->name('tickets.')->middleware('permission:ticket.manage')->group(function () {
            Route::get('/all', [TicketAdminController::class, 'all'])->name('all');
            Route::get('/open', [TicketAdminController::class, 'open'])->name('open');
            Route::get('/in-progress', [TicketAdminController::class, 'inProgress'])->name('in-progress');
            Route::get('/closed', [TicketAdminController::class, 'closed'])->name('closed');
            Route::prefix('history')->name('history.')->group(function () {
                Route::get('/', [TicketAdminController::class, 'history'])->name('index');
                Route::get('/{ticket}', [TicketAdminController::class, 'historyShow'])->name('show');
            });

            Route::get('/', [TicketAdminController::class, 'index'])->name('index');
            Route::get('/{ticket}', [TicketAdminController::class, 'show'])->name('show');
            Route::put('/{ticket}', [TicketAdminController::class, 'update'])->name('update');
            Route::post('/{ticket}/respond', [TicketAdminController::class, 'respond'])->name('respond');
        });

        // Admin ticket routes (legacy) - BLUE
        Route::prefix('ticket')->name('ticket.')->middleware('permission:ticket.manage')->group(function () {
            Route::get('/', [TicketController::class, 'index'])->name('index');
            Route::get('/create', [TicketController::class, 'create'])->name('create');
            Route::post('/store', [TicketController::class, 'store'])->name('store');
            Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
            Route::get('/{ticket}/edit', [TicketController::class, 'edit'])->name('edit');
            Route::put('/{ticket}', [TicketController::class, 'update'])->name('update');
            Route::post('/{ticket}/reply', [TicketController::class, 'reply'])->name('reply');
            Route::post('/{ticket}/confirm', [TicketController::class, 'confirm'])->name('confirm');
        });

        // Reports routes - permission protected
        Route::prefix('reports')->name('reports.')->middleware('permission:report.view|report.manage')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/{report}/screenshot', [ReportController::class, 'viewScreenshot'])->name('view-screenshot');
            Route::get('/{report}/download', [ReportController::class, 'download'])->name('download');
            Route::post('/generate', [ReportController::class, 'generate'])->name('generate')->middleware('permission:report.manage');
            Route::delete('/{report}', [ReportController::class, 'destroy'])->name('destroy')->middleware('permission:report.manage');
        });

        // Report SIRS routes - BLUE theme
        Route::prefix('report-sirs')->name('report-sirs.')->middleware('permission:report.sirs|report.manage')->group(function () {
            Route::get('/', [ReportSirsController::class, 'index'])->name('index');
            Route::post('/export', [ReportSirsController::class, 'export'])->name('export');
        });

        // Feedback routes - permission protected
        Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index')->middleware('permission:feedback.view|feedback.manage');
        Route::post('/feedback/{feedback}/reply', [FeedbackController::class, 'reply'])->name('feedback.reply')->middleware('permission:feedback.manage');
        Route::delete('/feedback/{feedback}', [FeedbackController::class, 'destroy'])->name('feedback.destroy')->middleware('permission:feedback.manage');

        // Admin Notifications routes - permission
        Route::prefix('notifications')->name('notifications.')->middleware('permission:notification.view')->group(function () {
            Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
            Route::post('/{id}/mark-as-read', [AdminNotificationController::class, 'markAsRead'])->name('mark-as-read');
            Route::post('/mark-all-as-read', [AdminNotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');
            Route::post('/delete-old', [AdminNotificationController::class, 'deleteOld'])->name('delete-old');
            Route::post('/settings', [AdminNotificationController::class, 'updateSettings'])->name('settings.update');
            Route::delete('/{id}', [AdminNotificationController::class, 'delete'])->name('delete');
            Route::delete('/', [AdminNotificationController::class, 'deleteAll'])->name('delete-all');
        });

        Route::get('/dashboard/stats', [AdminController::class, 'getStats']);

        // Tickets History
        Route::get('tickets/history', [TicketAdminController::class, 'history'])->name('tickets.history.index');
        Route::post('tickets/history/export', [TicketAdminController::class, 'exportHistory'])->name('tickets.history.export');
        Route::get('tickets/history/{ticket}', [TicketAdminController::class, 'historyShow'])->name('tickets.history.show');

        // FCM Monitoring - Manual Logs/Jobs/FCM/Notifications - BLUE
        Route::prefix('fcm')->name('fcm.')->middleware('permission:fcm.manage')->group(function () {
            Route::get('/', [FcmMonitoringController::class, 'index'])->name('index');
            Route::get('/logs', [FcmMonitoringController::class, 'logs'])->name('logs');
            Route::post('/logs/clear', [FcmMonitoringController::class, 'clearLog'])->name('logs.clear');
            Route::post('/jobs/retry/{id}', [FcmMonitoringController::class, 'retryFailed'])->name('jobs.retryFailed');
            Route::post('/jobs/forget/{id}', [FcmMonitoringController::class, 'forgetFailed'])->name('jobs.forgetFailed');
            Route::post('/jobs/flush', [FcmMonitoringController::class, 'flushFailed'])->name('jobs.flushFailed');
            Route::post('/test', [FcmMonitoringController::class, 'sendTest'])->name('test');
        });

        // IPSRS dashboard + Order Perbaikan di shell admin (pusat).
        // Controller + logic tetap milik AdministrasiUmum; hanya namespace view
        // yang bervariasi per route (lihat viewNamespace() di controller).
        Route::get('/ipsrs-dashboard', [AdministrasiUmumDashboardController::class, 'index'])
            ->name('ipsrs.dashboard')->middleware('permission:ipsrs.dashboard|order.manage');
        Route::get('/ipsrs-dashboard/stats', [AdministrasiUmumDashboardController::class, 'stats'])
            ->name('ipsrs.stats')->middleware('permission:ipsrs.dashboard|order.manage');

        Route::prefix('order-perbaikan')->name('order-perbaikan.')->middleware('permission:order.manage|order.view')->group(function () {
            Route::get('/', [OrderPerbaikanController::class, 'index'])->name('index');
            Route::get('/filter/{type}/{value}', [OrderPerbaikanController::class, 'filterOrders'])->name('filter');
            Route::get('/in-progress', [OrderPerbaikanController::class, 'inProgress'])->name('in-progress');
            Route::get('/confirmed', [OrderPerbaikanController::class, 'confirmed'])->name('confirmed');
            Route::get('/rejected', [OrderPerbaikanController::class, 'rejected'])->name('rejected');
            Route::get('/rendah', [OrderPerbaikanController::class, 'rendah'])->name('rendah');
            Route::get('/sedang', [OrderPerbaikanController::class, 'sedang'])->name('sedang');
            Route::get('/tinggi', [OrderPerbaikanController::class, 'tinggi'])->name('tinggi');
            Route::get('/total', [OrderPerbaikanController::class, 'total'])->name('total');

            Route::post('/export', [OrderPerbaikanController::class, 'export'])->name('export-data');

            Route::get('/{orderPerbaikan}', [OrderPerbaikanController::class, 'show'])->name('show');
            Route::put('/{orderPerbaikan}/update-status', [OrderPerbaikanController::class, 'updateStatus'])->name('update-status');
            Route::post('/{orderPerbaikan}/confirm', [OrderPerbaikanController::class, 'confirm'])->name('confirm');
            Route::post('/{orderPerbaikan}/reject', [OrderPerbaikanController::class, 'reject'])->name('reject');
            Route::post('/{orderPerbaikan}/complete', [OrderPerbaikanController::class, 'complete'])->name('complete');
            Route::post('/{orderPerbaikan}/start', [OrderPerbaikanController::class, 'start'])->name('start');
        });
    });

    // User Notification Routes
    Route::prefix('user/notifications')->name('user.notifications.')->group(function () {
        Route::get('/', [UserNotificationController::class, 'index'])->name('index');
        Route::post('/{id}/mark-as-read', [UserNotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::post('/mark-all-as-read', [UserNotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');
        Route::post('/delete-old', [UserNotificationController::class, 'deleteOld'])->name('delete-old');
        Route::post('/settings', [UserNotificationController::class, 'updateSettings'])->name('settings.update');
    });

    // FCM Manual Monitoring at /fcm (alias for admin, requires admin)
    Route::get('/fcm', [FcmMonitoringController::class, 'index'])->name('fcm.index')->middleware(AdminMiddleware::class);
    Route::get('/fcm/logs', [FcmMonitoringController::class, 'logs'])->name('fcm.logs')->middleware(AdminMiddleware::class);
    Route::post('/fcm/logs/clear', [FcmMonitoringController::class, 'clearLog'])->name('fcm.logs.clear')->middleware(AdminMiddleware::class);
    Route::post('/fcm/test', [FcmMonitoringController::class, 'sendTest'])->name('fcm.test')->middleware(AdminMiddleware::class);
    Route::post('/fcm/jobs/retry/{id}', [FcmMonitoringController::class, 'retryFailed'])->name('fcm.jobs.retryFailed')->middleware(AdminMiddleware::class);
    Route::post('/fcm/jobs/forget/{id}', [FcmMonitoringController::class, 'forgetFailed'])->name('fcm.jobs.forgetFailed')->middleware(AdminMiddleware::class);
    Route::post('/fcm/jobs/flush', [FcmMonitoringController::class, 'flushFailed'])->name('fcm.jobs.flushFailed')->middleware(AdminMiddleware::class);
});

// Administrasi Umum Routes - LEGACY, redirect permanen ke padanan admin.*.
// Dipertahankan sementara untuk bookmark/Flutter webview lama.
Route::prefix('administrasi-umum')->name('administrasi-umum.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.ipsrs.dashboard', [], 301))->name('dashboard');
    Route::get('/stats', fn () => redirect()->route('admin.ipsrs.stats', [], 301))->name('dashboard.stats');

    Route::prefix('order-perbaikan')->name('order-perbaikan.')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.order-perbaikan.index', [], 301))->name('index');
        Route::get('/filter/{type}/{value}', fn (string $type, string $value) => redirect()->route('admin.order-perbaikan.filter', [$type, $value], 301))->name('filter');
        Route::get('/in-progress', fn () => redirect()->route('admin.order-perbaikan.in-progress', [], 301))->name('in-progress');
        Route::get('/confirmed', fn () => redirect()->route('admin.order-perbaikan.confirmed', [], 301))->name('confirmed');
        Route::get('/rejected', fn () => redirect()->route('admin.order-perbaikan.rejected', [], 301))->name('rejected');
        Route::get('/rendah', fn () => redirect()->route('admin.order-perbaikan.rendah', [], 301))->name('rendah');
        Route::get('/sedang', fn () => redirect()->route('admin.order-perbaikan.sedang', [], 301))->name('sedang');
        Route::get('/tinggi', fn () => redirect()->route('admin.order-perbaikan.tinggi', [], 301))->name('tinggi');
        Route::get('/total', fn () => redirect()->route('admin.order-perbaikan.total', [], 301))->name('total');
        Route::get('/{orderPerbaikan}', fn (\App\Models\OrderPerbaikan $orderPerbaikan) => redirect()->route('admin.order-perbaikan.show', $orderPerbaikan, 301))->name('show');
    });

    Route::get('/profile', fn () => redirect()->route('admin.ipsrs.dashboard', [], 301))->name('profile');
    Route::get('/settings', fn () => redirect()->route('admin.ipsrs.dashboard', [], 301))->name('settings');
    Route::get('/notifications', fn () => redirect()->route('admin.notifications.index', [], 301))->name('notifications');
    Route::get('/dokumen', fn () => redirect()->route('admin.ipsrs.dashboard', [], 301))->name('dokumen');
    Route::get('/formulir', fn () => redirect()->route('admin.ipsrs.dashboard', [], 301))->name('formulir');
    Route::get('/prosedur', fn () => redirect()->route('admin.ipsrs.dashboard', [], 301))->name('prosedur');
});

Route::fallback(function () {
    return redirect()->route('login');
});
