<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationReadController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\DemoController as AdminDemoController;
use App\Http\Controllers\Admin\DownloadSalesController;
use App\Http\Controllers\Admin\SamplingRequestController as AdminSamplingRequestController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\StyleSamplingController as AdminStyleSamplingController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Customer\DemoController as CustomerDemoController;
use App\Http\Controllers\Customer\SamplingRequestController as CustomerSamplingRequestController;
use App\Http\Controllers\Customer\StyleSamplingController as CustomerStyleSamplingController;
use App\Http\Controllers\Customer\SubscriptionController as CustomerSubscriptionController;

/*
|--------------------------------------------------------------------------
| AUTH / LOGIN
|--------------------------------------------------------------------------
*/

Route::get('/', [CustomerDemoController::class, 'index'])->name('demo');
Route::redirect('/demo', '/');
Route::post('/demo/{musicDemo}/play', [CustomerDemoController::class, 'recordPlay'])->name('demo.play');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
Route::get('/auth/google', [SocialAuthController::class, 'redirect'])->defaults('provider', 'google')->name('auth.google.redirect');
Route::get('/auth/google/callback', [SocialAuthController::class, 'callback'])->defaults('provider', 'google')->name('auth.google.callback');
Route::get('/auth/facebook', [SocialAuthController::class, 'redirect'])->defaults('provider', 'facebook')->name('auth.facebook.redirect');
Route::get('/auth/facebook/callback', [SocialAuthController::class, 'callback'])->defaults('provider', 'facebook')->name('auth.facebook.callback');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/notifications/read', [NotificationReadController::class, 'store'])->name('notifications.read');

Route::get('/subcription', [CustomerSubscriptionController::class, 'index'])->name('subcription');
Route::post('/subcription/payment', [CustomerSubscriptionController::class, 'storePayment'])->name('subcription.payment');
Route::get('/payment/midtrans/finish', [CustomerSubscriptionController::class, 'midtransFinish'])->name('payment.midtrans.finish');
Route::post('/payment/midtrans/notification', [CustomerSubscriptionController::class, 'midtransNotification'])->name('payment.midtrans.notification');


/*
|--------------------------------------------------------------------------
| CUSTOMER ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('customer.access')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Style Sampling
    Route::get('/stylesampling', [CustomerStyleSamplingController::class, 'index'])->name('stylesampling');
    Route::get('/stylesampling/{styleSampling}/download/style', [CustomerStyleSamplingController::class, 'downloadStyle'])
        ->name('stylesampling.download.style');
    Route::post('/sampling-requests', [CustomerSamplingRequestController::class, 'store'])
        ->name('sampling-requests.store');
    Route::post('/sampling-requests/{samplingRequest}/payment', [CustomerSamplingRequestController::class, 'pay'])
        ->name('sampling-requests.payment');
    Route::post('/sampling-requests/{samplingRequest}/payment/sync', [CustomerSamplingRequestController::class, 'syncPayment'])
        ->name('sampling-requests.payment.sync');
    Route::post('/sampling-requests/{samplingRequest}/n27', [CustomerSamplingRequestController::class, 'uploadN27'])
        ->name('sampling-requests.n27.upload');

    // Subscription
});


/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('verified.admin')->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/admin/demo', [AdminDemoController::class, 'index'])->name('admin.demo');
    Route::post('/admin/demo', [AdminDemoController::class, 'store'])->name('admin.demo.store');
    Route::put('/admin/demo/{demo}', [AdminDemoController::class, 'update'])->name('admin.demo.update');
    Route::patch('/admin/demo/{demo}/trending', [AdminDemoController::class, 'toggleTrending'])->name('admin.demo.trending');
    Route::delete('/admin/demo/{demo}', [AdminDemoController::class, 'destroy'])->name('admin.demo.destroy');

    Route::get('/admin/style-sampling', [AdminStyleSamplingController::class, 'index'])->name('admin.stylesampling');
    Route::post('/admin/style-sampling', [AdminStyleSamplingController::class, 'store'])->name('admin.stylesampling.store');
    Route::put('/admin/style-sampling/{styleSampling}', [AdminStyleSamplingController::class, 'update'])->name('admin.stylesampling.update');
    Route::patch('/admin/style-sampling/{styleSampling}/activate', [AdminStyleSamplingController::class, 'activate'])->name('admin.stylesampling.activate');
    Route::patch('/admin/style-sampling/{styleSampling}/deactivate', [AdminStyleSamplingController::class, 'deactivate'])->name('admin.stylesampling.deactivate');
    Route::delete('/admin/style-sampling/{styleSampling}', [AdminStyleSamplingController::class, 'destroy'])->name('admin.stylesampling.destroy');

    Route::get('/admin/sampling-requests', [AdminSamplingRequestController::class, 'index'])->name('admin.sampling-requests');
    Route::get('/admin/sampling-requests/{samplingRequest}/download-n27', [AdminSamplingRequestController::class, 'downloadN27'])->name('admin.sampling-requests.n27.download');
    Route::patch('/admin/sampling-requests/{samplingRequest}/payment', [AdminSamplingRequestController::class, 'markPaid'])->name('admin.sampling-requests.payment');
    Route::patch('/admin/sampling-requests/{samplingRequest}/processing', [AdminSamplingRequestController::class, 'markProcessing'])->name('admin.sampling-requests.processing');
    Route::patch('/admin/sampling-requests/{samplingRequest}/delivery', [AdminSamplingRequestController::class, 'saveDelivery'])->name('admin.sampling-requests.delivery');

    Route::get('/admin/download-sales', [DownloadSalesController::class, 'index'])->name('admin.downloadsales');

    Route::get('/admin/user-management', [UserManagementController::class, 'index'])->name('admin.usermanagement');
    Route::patch('/admin/user-management/{user}/sync-access', [UserManagementController::class, 'syncAccess'])->name('admin.usermanagement.sync-access');
    Route::patch('/admin/user-management/{user}/status', [UserManagementController::class, 'updateStatus'])->name('admin.usermanagement.status');
    Route::patch('/admin/user-management/{user}/plan', [UserManagementController::class, 'updatePlan'])->name('admin.usermanagement.plan');
    Route::patch('/admin/user-management/{user}/password', [UserManagementController::class, 'resetPassword'])->name('admin.usermanagement.password');

    Route::get('/admin/data-table', function () {
        return view('layouts.admin.admin-data-table');
    })->name('admin.datatable');

    Route::get('/admin/setting', [AdminSettingController::class, 'index'])->name('admin.setting');
    Route::post('/admin/setting', [AdminSettingController::class, 'update'])->name('admin.setting.update');

    Route::get('/admin/subcription', [AdminSubscriptionController::class, 'index'])->name('admin.subcription');
    Route::patch('/admin/subcription/{subscription}/renew', [AdminSubscriptionController::class, 'renew'])->name('admin.subcription.renew');
    Route::patch('/admin/subcription/{subscription}/suspend', [AdminSubscriptionController::class, 'suspend'])->name('admin.subcription.suspend');
});
