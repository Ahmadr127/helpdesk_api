<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdministrasiUmumController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\User\InformationController;
use App\Http\Controllers\User\TicketController;
use App\Http\Controllers\User\FAQController;
use App\Http\Controllers\User\KnowledgeBaseController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\TicketAdminController;
use App\Http\Controllers\User\UserSettingsController;
use App\Http\Controllers\User\UserReportController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\BuildingController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\User\NotificationController as UserNotificationController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\AdministrasiUmum\DashboardController as AdministrasiUmumDashboardController;
use App\Http\Controllers\AdministrasiUmum\OrderController as AdministrasiUmumOrderController;
use App\Http\Middleware\AdministrasiUmumMiddleware;
use App\Http\Controllers\AdministrasiUmum\OrderPerbaikanController;
use App\Http\Controllers\Admin\UnitProsesController;
use App\Http\Controllers\User\FeedbackController as UserFeedbackController;
use App\Http\Controllers\Admin\ReportSirsController;
use App\Http\Controllers\Admin\PositionController;

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
    Route::get('/dashboard', function() {
        if (auth()->user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('user.dashboard');
    })->name('dashboard');
    
    // User routes
    Route::get('/user/dashboard', [UserController::class, 'dashboard'])->name('user.dashboard');
    Route::get('/information', [InformationController::class, 'index'])->name('user.information');
    
    // User Administrasi Umum routes
    Route::prefix('user/administrasi-umum')->name('user.administrasi-umum.')->group(function () {
        Route::get('/', [App\Http\Controllers\User\AdministrasiUmumController::class, 'index'])->name('index');
        Route::get('/order-barang', [App\Http\Controllers\User\AdministrasiUmumController::class, 'orderBarang'])->name('order-barang');
        Route::get('/order-barang/konfirmasi', [App\Http\Controllers\User\AdministrasiUmumController::class, 'orderBarangKonfirmasi'])->name('order-barang.konfirmasi');
        Route::get('/order-barang/reject', [App\Http\Controllers\User\AdministrasiUmumController::class, 'orderBarangReject'])->name('order-barang.reject');
        Route::get('/dokumen', [App\Http\Controllers\User\AdministrasiUmumController::class, 'dokumen'])->name('dokumen');
        Route::get('/formulir', [App\Http\Controllers\User\AdministrasiUmumController::class, 'formulir'])->name('formulir');
        Route::get('/prosedur', [App\Http\Controllers\User\AdministrasiUmumController::class, 'prosedur'])->name('prosedur');
        
        // Order Perbaikan Routes
        Route::prefix('order-perbaikan')->name('order-perbaikan.')->group(function () {
            Route::get('/', [App\Http\Controllers\User\AdministrasiUmumController::class, 'indexOrderPerbaikan'])->name('index');
            Route::get('/create', [App\Http\Controllers\User\AdministrasiUmumController::class, 'createOrderPerbaikan'])->name('create');
            Route::post('/', [App\Http\Controllers\User\AdministrasiUmumController::class, 'storeOrderPerbaikan'])->name('store');
            Route::get('/{orderPerbaikan}', [App\Http\Controllers\User\AdministrasiUmumController::class, 'showOrderPerbaikan'])->name('show');
            Route::get('/{orderPerbaikan}/edit', [App\Http\Controllers\User\AdministrasiUmumController::class, 'editOrderPerbaikan'])->name('edit');
            Route::put('/{orderPerbaikan}', [App\Http\Controllers\User\AdministrasiUmumController::class, 'updateOrderPerbaikan'])->name('update');
            Route::delete('/{orderPerbaikan}', [App\Http\Controllers\User\AdministrasiUmumController::class, 'deleteOrderPerbaikan'])->name('delete');
        });
    });

    Route::prefix('ticket')->name('user.ticket.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::get('/create', [TicketController::class, 'create'])->name('create');
        Route::post('/store', [TicketController::class, 'store'])->name('store');
        Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
        Route::get('/{ticket}/edit', [TicketController::class, 'edit'])->name('edit');
        Route::put('/{ticket}', [TicketController::class, 'update'])->name('update');
        Route::get('/status/{status}', [TicketController::class, 'filterByStatus'])
            ->name('filter.status')
            ->where('status', 'all|open|pending|in_progress|closed|confirmed');
        Route::post('/{ticket}/confirm', [TicketController::class, 'confirm'])->name('confirm');
        Route::post('/{ticket}/reply', [TicketController::class, 'reply'])->name('reply');
    });
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('user.ticket.reply');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('user.ticket.destroy');
    
    Route::get('/faq', [FAQController::class, 'index'])->name('user.faq');
    Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('user.knowledge-base');
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
        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        
        // Master Data Routes
        Route::prefix('master')->name('master.')->group(function () {
            Route::get('/', [MasterDataController::class, 'index'])->name('index');
            Route::resource('categories', CategoryController::class);
            Route::resource('departments', DepartmentController::class);
            Route::resource('buildings', BuildingController::class);
            Route::resource('locations', LocationController::class);
            Route::resource('unit-proses', UnitProsesController::class);
            Route::resource('positions', PositionController::class);
            Route::patch('positions/{position}/toggle-status', [PositionController::class, 'toggleStatus'])->name('positions.toggle-status');
            
            // Bulk action dan update limit routes
            Route::post('/{type}/bulk-action', [MasterDataController::class, 'bulkAction'])->name('bulk-action');
            Route::post('/{type}/update-limit', [MasterDataController::class, 'updateLimit'])->name('update-limit');
            Route::post('/save-settings', [MasterDataController::class, 'saveSettings'])->name('saveSettings');
            Route::get('/{type}/data', [MasterDataController::class, 'getData'])->name('getData');
        });

        // User Management routes
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        
        // Admin ticket routes
        Route::prefix('tickets')->name('tickets.')->group(function () {
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

        // Admin ticket routes
        Route::prefix('ticket')->name('ticket.')->group(function () {
            Route::get('/', [TicketController::class, 'index'])->name('index');
            Route::get('/create', [TicketController::class, 'create'])->name('create');
            Route::post('/store', [TicketController::class, 'store'])->name('store');
            Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
            Route::get('/{ticket}/edit', [TicketController::class, 'edit'])->name('edit');
            Route::put('/{ticket}', [TicketController::class, 'update'])->name('update');
            Route::post('/{ticket}/reply', [TicketController::class, 'reply'])->name('reply');
            Route::post('/{ticket}/confirm', [TicketController::class, 'confirm'])->name('confirm');
        });

        // Reports routes
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/{report}/screenshot', [ReportController::class, 'viewScreenshot'])->name('view-screenshot');
            Route::get('/{report}/download', [ReportController::class, 'download'])->name('download');
            Route::post('/generate', [ReportController::class, 'generate'])->name('generate');
            Route::delete('/{report}', [ReportController::class, 'destroy'])->name('destroy');
        });

        // Report SIRS routes
        Route::prefix('report-sirs')->name('report-sirs.')->group(function () {
            Route::get('/', [ReportSirsController::class, 'index'])->name('index');
            Route::post('/export', [ReportSirsController::class, 'export'])->name('export');
        });
        
        // Feedback routes
        Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
        Route::post('/feedback/{feedback}/reply', [FeedbackController::class, 'reply'])->name('feedback.reply');
        Route::delete('/feedback/{feedback}', [FeedbackController::class, 'destroy'])->name('feedback.destroy');

        // Admin Notifications routes
        Route::prefix('notifications')->name('notifications.')->group(function () {
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
    });

    // User Notification Routes
    Route::prefix('user/notifications')->name('user.notifications.')->group(function () {
        Route::get('/', [UserNotificationController::class, 'index'])->name('index');
        Route::post('/{id}/mark-as-read', [UserNotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::post('/mark-all-as-read', [UserNotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');
        Route::post('/delete-old', [UserNotificationController::class, 'deleteOld'])->name('delete-old');
        Route::post('/settings', [UserNotificationController::class, 'updateSettings'])->name('settings.update');
    });
});

// Administrasi Umum Routes
Route::prefix('administrasi-umum')->name('administrasi-umum.')->middleware(AdministrasiUmumMiddleware::class)->group(function () {
    Route::get('/', [AdministrasiUmumDashboardController::class, 'index'])->name('dashboard');
    Route::get('/stats', [AdministrasiUmumDashboardController::class, 'stats'])->name('dashboard.stats');
    
    // Order Perbaikan routes
    Route::prefix('order-perbaikan')->name('order-perbaikan.')->group(function () {
        Route::get('/', [OrderPerbaikanController::class, 'index'])->name('index');
        Route::get('/filter/{type}/{value}', [OrderPerbaikanController::class, 'filterOrders'])->name('filter');
        Route::get('/in-progress', [OrderPerbaikanController::class, 'inProgress'])->name('in-progress');
        Route::get('/confirmed', [OrderPerbaikanController::class, 'confirmed'])->name('confirmed');
        Route::get('/rejected', [OrderPerbaikanController::class, 'rejected'])->name('rejected');
        Route::get('/rendah', [OrderPerbaikanController::class, 'rendah'])->name('rendah');
        Route::get('/sedang', [OrderPerbaikanController::class, 'sedang'])->name('sedang');
        Route::get('/tinggi', [OrderPerbaikanController::class, 'tinggi'])->name('tinggi');
        Route::get('/total', [OrderPerbaikanController::class, 'total'])->name('total');
        
        // Export route
        Route::post('/export', [OrderPerbaikanController::class, 'export'])->name('export-data');
        
        Route::get('/{orderPerbaikan}', [OrderPerbaikanController::class, 'show'])->name('show');
        Route::put('/{orderPerbaikan}/update-status', [OrderPerbaikanController::class, 'updateStatus'])->name('update-status');
        Route::post('/{orderPerbaikan}/confirm', [OrderPerbaikanController::class, 'confirm'])->name('confirm');
        Route::post('/{orderPerbaikan}/reject', [OrderPerbaikanController::class, 'reject'])->name('reject');
        Route::post('/{orderPerbaikan}/complete', [OrderPerbaikanController::class, 'complete'])->name('complete');
        Route::post('/{orderPerbaikan}/start', [OrderPerbaikanController::class, 'start'])->name('start');
    });
    
    // General Routes
    Route::get('/profile', [App\Http\Controllers\AdministrasiUmum\AdministrasiUmumController::class, 'profile'])->name('profile');
    Route::get('/settings', [App\Http\Controllers\AdministrasiUmum\AdministrasiUmumController::class, 'settings'])->name('settings');
    Route::get('/notifications', [App\Http\Controllers\AdministrasiUmum\AdministrasiUmumController::class, 'notifications'])->name('notifications');
    Route::get('/dokumen', [App\Http\Controllers\AdministrasiUmum\AdministrasiUmumController::class, 'dokumen'])->name('dokumen');
    Route::get('/formulir', [App\Http\Controllers\AdministrasiUmum\AdministrasiUmumController::class, 'formulir'])->name('formulir');
    Route::get('/prosedur', [App\Http\Controllers\AdministrasiUmum\AdministrasiUmumController::class, 'prosedur'])->name('prosedur');
});

Route::fallback(function () {
    return redirect()->route('login');
});