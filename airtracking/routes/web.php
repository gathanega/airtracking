<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DeviceController;
use App\Http\Controllers\Admin\LimitController;
use App\Http\Controllers\Admin\WifiController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

// Sisi publik: dashboard tanpa login (data dimuat dari /api/public/*).
Route::view('/', 'dashboard')->name('home');
Route::view('/lokasi', 'lokasi')->name('lokasi');

// Sisi admin: login + peran admin.
Route::prefix('admin')->group(function () {
    Route::get('login', [AuthController::class, 'show'])->name('admin.login');
    Route::post('login', [AuthController::class, 'login'])->name('admin.login.post');

    Route::middleware(EnsureAdmin::class)->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('admin.logout');
        Route::redirect('/', '/admin/devices')->name('admin');

        Route::get('devices', [DeviceController::class, 'index'])->name('admin.devices');
        Route::post('devices', [DeviceController::class, 'store'])->name('admin.devices.store');
        Route::put('devices/{device}', [DeviceController::class, 'update'])->name('admin.devices.update');
        Route::post('devices/{device}/rotate', [DeviceController::class, 'rotate'])->name('admin.devices.rotate');
        Route::delete('devices/{device}', [DeviceController::class, 'destroy'])->name('admin.devices.destroy');

        Route::get('lokasi', [DeviceController::class, 'locations'])->name('admin.locations');
        Route::put('devices/{device}/lokasi', [DeviceController::class, 'setLocation'])->name('admin.devices.location');

        Route::get('wifi', [WifiController::class, 'show'])->name('admin.wifi');
        Route::get('limits', [LimitController::class, 'index'])->name('admin.limits');
        Route::put('limits', [LimitController::class, 'update'])->name('admin.limits.update');
    });
});
