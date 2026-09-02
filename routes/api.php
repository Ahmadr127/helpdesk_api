<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\User\TicketController as UserTicketController;
use App\Http\Controllers\Api\User\OrderPerbaikanController as UserOrderController;
use App\Http\Controllers\Api\Admin\TicketAdminController as AdminTicketController;
use App\Http\Controllers\Api\Admin\OrderPerbaikanAdminController as AdminOrderController;
use App\Http\Controllers\Api\Admin\MasterDataController as AdminMasterController;
use App\Http\Controllers\Api\Admin\UserManagementController as AdminUserController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FirebaseNotificationController;
use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\NotificationInboxController;
use App\Http\Middleware\Api\AdminApiMiddleware;
use App\Http\Middleware\Api\AdministrasiUmumApiMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes - Helpdesk RS Azra
|--------------------------------------------------------------------------
| Generated: 2026-08-28
| Auth: Sanctum Bearer Token
| Roles:
|  - guest (no token): login, register
|  - auth (any): me, logout, dashboard/user, lookup, tickets user, orders user, feedback
|  - admin IT (role=admin, position=IT): master data, user mgmt, ticket admin, feedback admin, dashboard admin
|  - admin Administrasi (role=admin, position=Administrasi): order perbaikan admin, dashboard administrasi
*/

// Public Auth
Route::prefix('auth')->group(function(){
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
});

// Protected routes
Route::middleware('auth:sanctum')->group(function(){
    Route::prefix('auth')->group(function(){
        Route::get('/me', [AuthController::class, 'me'])->name('api.auth.me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('api.auth.logoutAll');
    });

    // Dashboard
    Route::get('/dashboard/user', [DashboardController::class, 'userDashboard'])->name('api.dashboard.user');
    Route::get('/dashboard/admin', [DashboardController::class, 'adminDashboard'])->middleware(AdminApiMiddleware::class)->name('api.dashboard.admin');
    Route::get('/dashboard/administrasi', [DashboardController::class, 'administrasiDashboard'])->middleware(AdministrasiUmumApiMiddleware::class)->name('api.dashboard.administrasi');

    // Lookup (read-only master data for all authenticated users)
    Route::prefix('lookup')->name('api.lookup.')->group(function(){
        Route::get('/categories', [LookupController::class, 'categories'])->name('categories');
        Route::get('/departments', [LookupController::class, 'departments'])->name('departments');
        Route::get('/buildings', [LookupController::class, 'buildings'])->name('buildings');
        Route::get('/locations', [LookupController::class, 'locations'])->name('locations');
        Route::get('/unit-proses', [LookupController::class, 'unitProses'])->name('unit-proses');
        Route::get('/positions', [LookupController::class, 'positions'])->name('positions');
        Route::get('/priorities', [LookupController::class, 'priorities'])->name('priorities');
        Route::get('/ticket-statuses', [LookupController::class, 'ticketStatuses'])->name('ticket-statuses');
        Route::get('/order-statuses', [LookupController::class, 'orderStatuses'])->name('order-statuses');
    });

    // User Ticket (owner only)
    Route::prefix('tickets')->name('api.tickets.')->group(function(){
        Route::get('/', [UserTicketController::class, 'index'])->name('index');
        Route::post('/', [UserTicketController::class, 'store'])->name('store');
        Route::get('/filter/{status}', [UserTicketController::class, 'filterByStatus'])->where('status','all|open|pending|in_progress|closed|confirmed')->name('filter');
        Route::get('/{ticket}', [UserTicketController::class, 'show'])->name('show');
        Route::put('/{ticket}', [UserTicketController::class, 'update'])->name('update');
        Route::delete('/{ticket}', [UserTicketController::class, 'destroy'])->name('destroy');
        Route::post('/{ticket}/reply', [UserTicketController::class, 'reply'])->name('reply');
        Route::post('/{ticket}/confirm', [UserTicketController::class, 'confirm'])->name('confirm');
    });

    // User Order Perbaikan (owner only)
    Route::prefix('order-perbaikan')->name('api.order-perbaikan.')->group(function(){
        Route::get('/', [UserOrderController::class, 'index'])->name('index');
        Route::post('/', [UserOrderController::class, 'store'])->name('store');
        Route::get('/konfirmasi', [UserOrderController::class, 'konfirmasi'])->name('konfirmasi');
        Route::get('/rejected', [UserOrderController::class, 'rejected'])->name('rejected');
        Route::get('/{orderPerbaikan}', [UserOrderController::class, 'show'])->name('show');
        Route::put('/{orderPerbaikan}', [UserOrderController::class, 'update'])->name('update');
        Route::delete('/{orderPerbaikan}', [UserOrderController::class, 'destroy'])->name('destroy');
    });

    // Feedback (dual: user create/list own, admin list all + reply)
    Route::prefix('feedback')->name('api.feedback.')->group(function(){
        Route::get('/', [FeedbackController::class, 'index'])->name('index');
        Route::post('/', [FeedbackController::class, 'store'])->name('store');
        Route::get('/{feedback}', [FeedbackController::class, 'show'])->name('show');
        Route::post('/{feedback}/reply', [FeedbackController::class, 'reply'])->middleware(AdminApiMiddleware::class)->name('reply');
        Route::delete('/{feedback}', [FeedbackController::class, 'destroy'])->middleware(AdminApiMiddleware::class)->name('destroy');
    });

    // Admin IT routes
    Route::prefix('admin')->name('api.admin.')->middleware(AdminApiMiddleware::class)->group(function(){

        // User Management
        Route::prefix('users')->name('users.')->group(function(){
            Route::get('/', [AdminUserController::class, 'index'])->name('index');
            Route::post('/', [AdminUserController::class, 'store'])->name('store');
            Route::get('/{user}', [AdminUserController::class, 'show'])->name('show');
            Route::put('/{user}', [AdminUserController::class, 'update'])->name('update');
            Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('destroy');
        });

        // Master Data (categories, departments, buildings, locations, unit-proses, positions)
        Route::prefix('master')->name('master.')->group(function(){
            Route::get('/{type}', [AdminMasterController::class, 'index'])->where('type','categories|departments|buildings|locations|unit-proses|unit_proses|positions')->name('index');
            Route::post('/{type}', [AdminMasterController::class, 'store'])->where('type','categories|departments|buildings|locations|unit-proses|unit_proses|positions')->name('store');
            Route::get('/{type}/{id}', [AdminMasterController::class, 'show'])->where('type','categories|departments|buildings|locations|unit-proses|unit_proses|positions')->name('show');
            Route::put('/{type}/{id}', [AdminMasterController::class, 'update'])->where('type','categories|departments|buildings|locations|unit-proses|unit_proses|positions')->name('update');
            Route::delete('/{type}/{id}', [AdminMasterController::class, 'destroy'])->where('type','categories|departments|buildings|locations|unit-proses|unit_proses|positions')->name('destroy');
            Route::post('/{type}/bulk-action', [AdminMasterController::class, 'bulkAction'])->where('type','categories|departments|buildings|locations|unit-proses|unit_proses|positions')->name('bulk');
        });

        // Ticket Admin
        Route::prefix('tickets')->name('tickets.')->group(function(){
            Route::get('/', [AdminTicketController::class, 'index'])->name('index');
            Route::get('/all', [AdminTicketController::class, 'all'])->name('all');
            Route::get('/open', [AdminTicketController::class, 'open'])->name('open');
            Route::get('/in-progress', [AdminTicketController::class, 'inProgress'])->name('in-progress');
            Route::get('/closed', [AdminTicketController::class, 'closed'])->name('closed');
            Route::get('/history', [AdminTicketController::class, 'history'])->name('history');
            Route::get('/history/{ticket}', [AdminTicketController::class, 'historyShow'])->name('historyShow');
            Route::get('/{ticket}', [AdminTicketController::class, 'show'])->name('show');
            Route::post('/{ticket}/respond', [AdminTicketController::class, 'respond'])->name('respond');
            Route::put('/{ticket}', [AdminTicketController::class, 'update'])->name('update');
        });
    });

    // Administrasi Umum routes
    Route::prefix('administrasi-umum')->name('api.administrasi-umum.')->middleware(AdministrasiUmumApiMiddleware::class)->group(function(){
        Route::prefix('order-perbaikan')->name('order-perbaikan.')->group(function(){
            Route::get('/', [AdminOrderController::class, 'index'])->name('index');
            Route::get('/statistics', [AdminOrderController::class, 'statistics'])->name('statistics');
            Route::get('/in-progress', [AdminOrderController::class, 'inProgress'])->name('in-progress');
            Route::get('/confirmed', [AdminOrderController::class, 'confirmed'])->name('confirmed');
            Route::get('/rejected', [AdminOrderController::class, 'rejected'])->name('rejected');
            Route::get('/{orderPerbaikan}', [AdminOrderController::class, 'show'])->name('show');
            Route::put('/{orderPerbaikan}/status', [AdminOrderController::class, 'updateStatus'])->name('updateStatus');
            Route::post('/{orderPerbaikan}/confirm', [AdminOrderController::class, 'confirm'])->name('confirm');
            Route::post('/{orderPerbaikan}/reject', [AdminOrderController::class, 'reject'])->name('reject');
            Route::post('/{orderPerbaikan}/start', [AdminOrderController::class, 'start'])->name('start');
        });
        Route::get('/stats', [DashboardController::class, 'administrasiDashboard'])->name('stats');
    });

    // FCM Token (for Flutter)
    Route::prefix('user')->name('api.user.')->group(function(){
        Route::post('/fcm-token', [FcmTokenController::class, 'store'])->name('fcmToken.store');
        Route::delete('/fcm-token', [FcmTokenController::class, 'destroy'])->name('fcmToken.destroy');
        Route::get('/fcm-tokens', [FcmTokenController::class, 'index'])->name('fcmTokens.index');
    });

    // Notification Inbox (database notifications) - Flutter UI
    Route::prefix('notifications')->name('api.notifications.')->group(function(){
        Route::get('/', [NotificationInboxController::class, 'index'])->name('index');
        Route::get('/unread-count', [NotificationInboxController::class, 'unreadCount'])->name('unreadCount');
        Route::post('/{id}/read', [NotificationInboxController::class, 'markRead'])->name('markRead');
        Route::post('/read-all', [NotificationInboxController::class, 'markAllRead'])->name('markAllRead');
        Route::delete('/{id}', [NotificationInboxController::class, 'destroy'])->name('destroy');
    });

    // Firebase FCM - accessible to any authenticated user (adjust middleware as needed)
    Route::prefix('firebase')->name('api.firebase.')->group(function(){
        Route::prefix('notification')->name('notification.')->group(function(){
            Route::post('/send', [FirebaseNotificationController::class, 'send'])->name('send');
            Route::post('/send-many', [FirebaseNotificationController::class, 'sendMany'])->name('sendMany');
            Route::post('/topic', [FirebaseNotificationController::class, 'sendToTopic'])->name('topic');
        });
        Route::prefix('topic')->name('topic.')->group(function(){
            Route::post('/subscribe', [FirebaseNotificationController::class, 'subscribe'])->name('subscribe');
            Route::post('/unsubscribe', [FirebaseNotificationController::class, 'unsubscribe'])->name('unsubscribe');
        });
    });
});

// Health check
Route::get('/health', fn()=> response()->json(['success'=>true,'message'=>'Helpdesk API running','timestamp'=>now()]))->name('api.health');
