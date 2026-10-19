<?php

use App\Http\Controllers\Api\IngestController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Middleware\ForceJson;
use Illuminate\Support\Facades\Route;

// Prefix "api" ditambahkan otomatis oleh Laravel.
Route::middleware(ForceJson::class)->group(function () {
    Route::get('health', fn () => ['ok' => true]);

    // Alat -> server
    Route::post('ingest', [IngestController::class, 'store'])->middleware('throttle:120,1');

    // Publik: hanya baca
    Route::prefix('public')->middleware('throttle:60,1')->group(function () {
        Route::get('limits', [PublicController::class, 'limits']);
        Route::get('devices', [PublicController::class, 'devices']);
        Route::get('devices/{code}', [PublicController::class, 'device']);
        Route::get('devices/{code}/history', [PublicController::class, 'history']);
        Route::get('devices/{code}/history.csv', [PublicController::class, 'historyCsv']);
        Route::get('devices/{code}/predict', [PublicController::class, 'predict']);
    });
});
