<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

// Halaman scan QR publik (sebelum login)
Route::get('/', [ScanController::class, 'index'])->name('scan');
Route::post('/scan', [ScanController::class, 'process'])->middleware('throttle:60,1')->name('scan.process');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', fn () => auth()->user()->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('karyawan.dashboard'))->name('dashboard');

    // ---- Karyawan
    Route::prefix('karyawan')->name('karyawan.')->middleware('device')->group(function () {
        Route::get('/', [KaryawanController::class, 'dashboard'])->name('dashboard');
        Route::get('/qr', [KaryawanController::class, 'qr'])->name('qr');
        Route::get('/status', [KaryawanController::class, 'status'])->name('status');
        Route::get('/riwayat', [KaryawanController::class, 'riwayat'])->name('riwayat');
        Route::get('/profil', [KaryawanController::class, 'profil'])->name('profil');
        Route::put('/profil', [KaryawanController::class, 'updateProfil'])->name('profil.update');
        Route::put('/profil/password', [KaryawanController::class, 'updatePassword'])->name('profil.password');
        Route::get('/izin', [KaryawanController::class, 'izinIndex'])->name('izin');
        Route::post('/izin', [KaryawanController::class, 'izinStore'])->name('izin.store');
    });

    // ---- Admin
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::resource('karyawan', Admin\EmployeeController::class)->except('show');

        Route::get('/divisi', [Admin\MasterController::class, 'divisi'])->name('divisi');
        Route::post('/divisi', [Admin\MasterController::class, 'divisiStore'])->name('divisi.store');
        Route::put('/divisi/{divisi}', [Admin\MasterController::class, 'divisiUpdate'])->name('divisi.update');
        Route::delete('/divisi/{divisi}', [Admin\MasterController::class, 'divisiDestroy'])->name('divisi.destroy');

        Route::get('/shift', [Admin\MasterController::class, 'shift'])->name('shift');
        Route::post('/shift', [Admin\MasterController::class, 'shiftStore'])->name('shift.store');
        Route::put('/shift/{shift}', [Admin\MasterController::class, 'shiftUpdate'])->name('shift.update');
        Route::delete('/shift/{shift}', [Admin\MasterController::class, 'shiftDestroy'])->name('shift.destroy');

        Route::get('/izin', [Admin\LeaveApprovalController::class, 'index'])->name('izin');
        Route::put('/izin/{izin}', [Admin\LeaveApprovalController::class, 'decide'])->name('izin.decide');

        Route::get('/jaringan', [Admin\NetworkController::class, 'index'])->name('jaringan');
        Route::post('/jaringan', [Admin\NetworkController::class, 'store'])->name('jaringan.store');
        Route::put('/jaringan/{jaringan}', [Admin\NetworkController::class, 'toggle'])->name('jaringan.toggle');
        Route::delete('/jaringan/{jaringan}', [Admin\NetworkController::class, 'destroy'])->name('jaringan.destroy');

        Route::get('/perangkat', [Admin\DeviceController::class, 'index'])->name('perangkat');
        Route::put('/perangkat/permintaan/{permintaan}', [Admin\DeviceController::class, 'decide'])->name('perangkat.decide');
        Route::delete('/perangkat/{karyawan}', [Admin\DeviceController::class, 'reset'])->name('perangkat.reset');

        Route::get('/laporan', [Admin\ReportController::class, 'index'])->name('laporan');
        Route::get('/laporan/export', [Admin\ReportController::class, 'export'])->name('laporan.export');
    });
});
