<?php

use App\Http\Controllers\FailedJobController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('notifications')->group(function () {
        Route::post('/', [NotificationController::class, 'store']);
        Route::post('/batch', [NotificationController::class, 'batchStore']);
        Route::get('/batch/{batchId}', [NotificationController::class, 'showBatch']);
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::post('/{id}/cancel', [NotificationController::class, 'cancel']);
    });

    Route::prefix('failed-jobs')->group(function () {
        Route::get('/', [FailedJobController::class, 'index']);
        Route::get('/{id}', [FailedJobController::class, 'show']);
        Route::post('/{id}/retry', [FailedJobController::class, 'retry']);
        Route::post('/retry-all', [FailedJobController::class, 'retryAll']);
        Route::delete('/{id}', [FailedJobController::class, 'delete']);
        Route::post('/flush', [FailedJobController::class, 'flush']);
    });

    Route::get('/metrics', MetricsController::class);
    Route::get('/health', HealthCheckController::class);
});
