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
        if ($user->hasPermission('dashboard.it')) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->hasPermission('dashboard.ipsrs')) {
            return redirect()->route('admin.ipsrs.dashboard');
        }

        return redirect()->route('user.dashboard');
    })->name('dashboard');

    // User routes
    Route::get('/user/dashboard', [UserController::class, 'dashboard'])->name('user.dashboard');
    Route::get('/information', [InformationController::class, 'index'])->name('user.information');

    // User Administrasi Umum routes - GREEN theme (Maintenance) permission protected
    Route::prefix('user/administrasi-umum')->name('user.administrasi-umum.')->middleware('permission:order|myorder')->group(function () {
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
            Route::get('/create', [App\Http\Controllers\User\AdministrasiUmumController::class, 'createOrderPerbaikan'])->name('create')->middleware('permission:order|myorder');
            Route::post('/', [App\Http\Controllers\User\AdministrasiUmumController::class, 'storeOrderPerbaikan'])->name('store')->middleware('permission:order|myorder');
            Route::get('/{orderPerbaikan}', [App\Http\Controllers\User\AdministrasiUmumController::class, 'showOrderPerbaikan'])->name('show');
            Route::post('/{orderPerbaikan}/konfirmasi-selesai', [App\Http\Controllers\User\AdministrasiUmumController::class, 'confirmOrderPerbaikan'])->name('konfirmasi-selesai');
            Route::get('/{orderPerbaikan}/edit', [App\Http\Controllers\User\AdministrasiUmumController::class, 'editOrderPerbaikan'])->name('edit')->middleware('permission:order|myorder');
            Route::put('/{orderPerbaikan}', [App\Http\Controllers\User\AdministrasiUmumController::class, 'updateOrderPerbaikan'])->name('update')->middleware('permission:order|myorder');
            Route::delete('/{orderPerbaikan}', [App\Http\Controllers\User\AdministrasiUmumController::class, 'deleteOrderPerbaikan'])->name('delete')->middleware('permission:order|myorder');
        });
    });

    Route::prefix('ticket')->name('user.ticket.')->middleware('permission:ticket|myticket')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::get('/create', [TicketController::class, 'create'])->name('create')->middleware('permission:ticket|myticket');
        Route::post('/store', [TicketController::class, 'store'])->name('store')->middleware('permission:ticket|myticket');
        Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
        Route::get('/{ticket}/edit', [TicketController::class, 'edit'])->name('edit')->middleware('permission:ticket|myticket');
        Route::put('/{ticket}', [TicketController::class, 'update'])->name('update')->middleware('permission:ticket|myticket');
        Route::get('/status/{status}', [TicketController::class, 'filterByStatus'])
            ->name('filter.status')
            ->where('status', 'all|open|pending|in_progress|closed|confirmed');
        Route::post('/{ticket}/confirm', [TicketController::class, 'confirm'])->name('confirm');
        Route::post('/{ticket}/reply', [TicketController::class, 'reply'])->name('reply');
    });
    // FIX duplicate name user.ticket.reply (sebelumnya bentrok dengan ticket/{ticket}/reply di atas) — ganti jadi user.ticket.reply.legacy agar artisan optimize bisa cache
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('user.ticket.reply.legacy')->middleware('permission:ticket|myticket');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('user.ticket.destroy')->middleware('permission:ticket|myticket');

    Route::get('/faq', [FAQController::class, 'index'])->name('user.faq')->middleware('permission:knowledge');
    Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('user.knowledge-base')->middleware('permission:knowledge');
    Route::get('/profile', [UserController::class, 'profile'])->name('user.profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('user.profile.update');
    Route::put('/change-password', [UserController::class, 'changePassword'])->name('user.password.update');
    Route::get('/settings', [UserSettingsController::class, 'index'])->name('user.settings');
    Route::post('/settings', [UserSettingsController::class, 'update'])->name('user.settings.update');
    Route::get('/report', [UserReportController::class, 'index'])->name('user.report');
    Route::post('/report', [UserReportController::class, 'store'])->name('user.report.store');
    Route::post('/feedback', [UserFeedbackController::class, 'store'])
        ->name('user.feedback.store');

    // Fitur admin — URL datar tanpa prefix (nama route tetap berawalan admin.).
    // Fitur user terpisah (user/ticket/*, user/administrasi-umum/*) karena beda layout.
    Route::name('admin.')->middleware('permission:layout.admin')->group(function () {
        // Dashboard IT - BLUE theme, requires permission
        Route::get('/it-dashboard', [AdminController::class, 'dashboard'])->name('dashboard')->middleware('permission:dashboard.it');

        // Permission Management
        Route::prefix('permissions')->name('permissions.')->middleware('permission:permission')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PermissionController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\PermissionController::class, 'store'])->name('store');
            Route::delete('/{permission}', [\App\Http\Controllers\Admin\PermissionController::class, 'destroy'])->name('destroy');
            Route::post('/role', [\App\Http\Controllers\Admin\PermissionController::class, 'updateRole'])->name('role.update');
            Route::put('/user/{user}', [\App\Http\Controllers\Admin\PermissionController::class, 'updateUser'])->name('user.update');
        });

        // Master Data Routes - GREEN not for admin IT? Keep BLUE for IT but permission protected
        Route::prefix('master')->name('master.')->middleware('permission:master')->group(function () {
            Route::get('/', [MasterDataController::class, 'index'])->name('index');
            Route::resource('categories', CategoryController::class)->middleware('permission:master');
            Route::resource('departments', DepartmentController::class)->middleware('permission:master');
            Route::resource('buildings', BuildingController::class)->middleware('permission:master');
            Route::resource('locations', LocationController::class)->middleware('permission:master');
            Route::resource('unit-proses', UnitProsesController::class)->middleware('permission:master');
            Route::resource('kategori-order', KategoriOrderController::class)->middleware('permission:master');
            Route::resource('positions', PositionController::class)->middleware('permission:master');
            Route::patch('positions/{position}/toggle-status', [PositionController::class, 'toggleStatus'])->name('positions.toggle-status')->middleware('permission:master');

            // Bulk action dan update limit routes
            Route::post('/{type}/bulk-action', [MasterDataController::class, 'bulkAction'])->name('bulk-action')->middleware('permission:master');
            Route::post('/{type}/update-limit', [MasterDataController::class, 'updateLimit'])->name('update-limit')->middleware('permission:master');
            Route::post('/save-settings', [MasterDataController::class, 'saveSettings'])->name('saveSettings')->middleware('permission:master');
            Route::get('/{type}/data', [MasterDataController::class, 'getData'])->name('getData');
        });

        // User Management routes - permission protected
        Route::middleware('permission:user')->group(function () {
            Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create')->middleware('permission:user');
            Route::post('/users', [UserManagementController::class, 'store'])->name('users.store')->middleware('permission:user');
            Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit')->middleware('permission:user');
            Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update')->middleware('permission:user');
            Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy')->middleware('permission:user');
        });

        // Admin ticket routes - BLUE theme (ticket IT) permission protected
        // Tiket milik sendiri di shell admin (wajib sebelum wildcard {ticket}).
        Route::get('/tickets/myticket', [TicketAdminController::class, 'myticket'])
            ->name('tickets.myticket')->middleware('permission:myticket');
        Route::get('/tickets/myticket/create', [TicketAdminController::class, 'createMyticket'])
            ->name('tickets.myticket.create')->middleware('permission:myticket');
        Route::post('/tickets/myticket', [TicketAdminController::class, 'storeMyticket'])
            ->name('tickets.myticket.store')->middleware('permission:myticket');
        Route::get('/tickets/myticket/{ticket}/edit', [TicketAdminController::class, 'editMyticket'])
            ->name('tickets.myticket.edit')->middleware('permission:myticket');
        Route::put('/tickets/myticket/{ticket}', [TicketAdminController::class, 'updateMyticket'])
            ->name('tickets.myticket.update')->middleware('permission:myticket');
        Route::delete('/tickets/myticket/{ticket}', [TicketAdminController::class, 'destroyMyticket'])
            ->name('tickets.myticket.destroy')->middleware('permission:myticket');
        Route::get('/tickets/myticket/{ticket}', [TicketAdminController::class, 'showMyticket'])
            ->name('tickets.myticket.show')->middleware('permission:myticket');
        Route::prefix('tickets')->name('tickets.')->middleware('permission:ticket')->group(function () {
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

        // (grup legacy admin/ticket/* dihapus — admin memakai tickets.* di atas)

        // Reports routes - permission protected
        Route::prefix('reports')->name('reports.')->middleware('permission:report')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/{report}/screenshot', [ReportController::class, 'viewScreenshot'])->name('view-screenshot');
            Route::get('/{report}/download', [ReportController::class, 'download'])->name('download');
            Route::post('/generate', [ReportController::class, 'generate'])->name('generate')->middleware('permission:dashboard.it');
            Route::delete('/{report}', [ReportController::class, 'destroy'])->name('destroy')->middleware('permission:dashboard.it');
        });

        // Report SIRS routes - BLUE theme
        Route::prefix('report-sirs')->name('report-sirs.')->middleware('permission:report.sirs')->group(function () {
            Route::get('/', [ReportSirsController::class, 'index'])->name('index');
            Route::post('/export', [ReportSirsController::class, 'export'])->name('export');
        });

        // Feedback routes - permission protected
        Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index')->middleware('permission:feedback');
        Route::post('/feedback/{feedback}/reply', [FeedbackController::class, 'reply'])->name('feedback.reply')->middleware('permission:dashboard.it');
        Route::delete('/feedback/{feedback}', [FeedbackController::class, 'destroy'])->name('feedback.destroy')->middleware('permission:dashboard.it');

        // Admin Notifications routes - permission
        Route::prefix('notifications')->name('notifications.')->middleware('permission:notification')->group(function () {
            Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
            Route::post('/{id}/mark-as-read', [AdminNotificationController::class, 'markAsRead'])->name('mark-as-read');
            Route::post('/mark-all-as-read', [AdminNotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');
            Route::post('/delete-old', [AdminNotificationController::class, 'deleteOld'])->name('delete-old');
            Route::post('/settings', [AdminNotificationController::class, 'updateSettings'])->name('settings.update');
            Route::delete('/{id}', [AdminNotificationController::class, 'delete'])->name('delete');
            Route::delete('/', [AdminNotificationController::class, 'deleteAll'])->name('delete-all');
        });

        Route::get('/it-dashboard/stats', [AdminController::class, 'getStats'])->name('dashboard.stats')->middleware('permission:dashboard.it');

        // Tickets History (index/show ikut grup tickets.* di atas; di sini tinggal export)
        Route::post('tickets/history/export', [TicketAdminController::class, 'exportHistory'])->name('tickets.history.export')->middleware('permission:ticket');

        // (FCM Monitoring cukup lewat grup /fcm di bawah — nama route fcm.*)

      
        Route::get('/ipsrs-dashboard', [AdministrasiUmumDashboardController::class, 'index'])
            ->name('ipsrs.dashboard')->middleware('permission:dashboard.ipsrs');
        Route::get('/ipsrs-dashboard/stats', [AdministrasiUmumDashboardController::class, 'stats'])
            ->name('ipsrs.stats')->middleware('permission:dashboard.ipsrs');

        // Order milik sendiri — di luar grup order.* agar satu menu cukup satu
        // permission (myorder), tanpa batasan lain. Wajib sebelum wildcard.
        Route::get('/order-perbaikan/myorder', [OrderPerbaikanController::class, 'myorder'])
            ->name('order-perbaikan.myorder')->middleware('permission:myorder');
        Route::get('/order-perbaikan/myorder/create', [OrderPerbaikanController::class, 'createMyorder'])
            ->name('order-perbaikan.myorder.create')->middleware('permission:myorder');
        Route::post('/order-perbaikan/myorder', [OrderPerbaikanController::class, 'storeMyorder'])
            ->name('order-perbaikan.myorder.store')->middleware('permission:myorder');
        Route::get('/order-perbaikan/myorder/{orderPerbaikan}/edit', [OrderPerbaikanController::class, 'editMyorder'])
            ->name('order-perbaikan.myorder.edit')->middleware('permission:myorder');
        Route::put('/order-perbaikan/myorder/{orderPerbaikan}', [OrderPerbaikanController::class, 'updateMyorder'])
            ->name('order-perbaikan.myorder.update')->middleware('permission:myorder');
        Route::delete('/order-perbaikan/myorder/{orderPerbaikan}', [OrderPerbaikanController::class, 'destroyMyorder'])
            ->name('order-perbaikan.myorder.destroy')->middleware('permission:myorder');
        Route::get('/order-perbaikan/myorder/{orderPerbaikan}', [OrderPerbaikanController::class, 'showMyorder'])
            ->name('order-perbaikan.myorder.show')->middleware('permission:myorder');

        Route::prefix('order-perbaikan')->name('order-perbaikan.')->middleware('permission:order')->group(function () {
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

    // FCM Manual Monitoring at /fcm (alias for admin, murni permission)
    Route::get('/fcm', [FcmMonitoringController::class, 'index'])->name('fcm.index')->middleware('permission:fcm');
    Route::get('/fcm/logs', [FcmMonitoringController::class, 'logs'])->name('fcm.logs')->middleware('permission:fcm');
    Route::post('/fcm/logs/clear', [FcmMonitoringController::class, 'clearLog'])->name('fcm.logs.clear')->middleware('permission:fcm');
    Route::post('/fcm/test', [FcmMonitoringController::class, 'sendTest'])->name('fcm.test')->middleware('permission:fcm');
    Route::post('/fcm/jobs/retry/{id}', [FcmMonitoringController::class, 'retryFailed'])->name('fcm.jobs.retryFailed')->middleware('permission:fcm');
    Route::post('/fcm/jobs/forget/{id}', [FcmMonitoringController::class, 'forgetFailed'])->name('fcm.jobs.forgetFailed')->middleware('permission:fcm');
    Route::post('/fcm/jobs/flush', [FcmMonitoringController::class, 'flushFailed'])->name('fcm.jobs.flushFailed')->middleware('permission:fcm');
});

// (Grup legacy administrasi-umum/* dihapus — tanpa prefix, URL lama kini
// jatuh ke fallback login di bawah.)

Route::fallback(function () {
    return redirect()->route('login');
});
