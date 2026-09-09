<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HrdDashboardController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicLeaveRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (Karyawan - Tanpa Login / Akun)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('public.form');
});

Route::get('/ajukan-izin', [PublicLeaveRequestController::class, 'create'])->name('public.form');
Route::post('/ajukan-izin', [PublicLeaveRequestController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('public.store');

Route::get('/pengajuan/sukses/{request_number}', [PublicLeaveRequestController::class, 'success'])->name('public.success');

Route::get('/cek-status', [StatusController::class, 'index'])->name('status.index');
Route::post('/cek-status', [StatusController::class, 'check'])
    ->middleware('throttle:10,1')
    ->name('status.check');

/*
|--------------------------------------------------------------------------
| Authentication Routes (Admin & HRD)
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Protected Internal Routes (Admin & HRD)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Role redirector
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Admin Dashboard (Admin only)
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
        ->middleware('role:admin')
        ->name('admin.dashboard');

    // HRD Dashboard (HRD only)
    Route::get('/hrd/dashboard', [HrdDashboardController::class, 'index'])
        ->middleware('role:hrd')
        ->name('hrd.dashboard');

    // Shared management routes (accessible by both Admin & HRD)
    Route::middleware('role:admin,hrd')->group(function () {
        Route::get('/pengajuan', [LeaveRequestController::class, 'index'])->name('requests.index');
        Route::get('/pengajuan/{id}', [LeaveRequestController::class, 'show'])->name('requests.show');
        Route::post('/pengajuan/{id}/approve', [LeaveRequestController::class, 'approve'])->name('requests.approve');
        Route::post('/pengajuan/{id}/reject', [LeaveRequestController::class, 'reject'])->name('requests.reject');
        Route::get('/pengajuan/{id}/attachment/{attachment_id}', [LeaveRequestController::class, 'attachment'])->name('requests.attachment');

        Route::get('/riwayat', [LeaveRequestController::class, 'history'])->name('requests.history');
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/export', [ReportController::class, 'exportCsv'])->name('reports.export');
        Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
    });
});
