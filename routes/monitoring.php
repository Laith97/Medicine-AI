<?php

use Illuminate\Support\Facades\Route;
use App\Services\Monitoring\MetricsService;
use App\Http\Controllers\Api\MonitoringController;

/*
|--------------------------------------------------------------------------
| Monitoring Routes
|--------------------------------------------------------------------------
|
| Routes for application monitoring, metrics collection, and health checks.
| These routes are used by monitoring systems like Prometheus and for
| operational health checking.
|
*/

Route::prefix('api')->group(function () {

    // Health check endpoint
    Route::get('/health', function (MetricsService $metricsService) {
        return response()->json($metricsService->healthCheck());
    });

    // Prometheus metrics endpoint
    Route::get('/metrics', function (MetricsService $metricsService) {
        return response($metricsService->generateMetrics(), 200)
            ->header('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
    });

    // Detailed monitoring endpoints (authenticated)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/monitoring/dashboard', [MonitoringController::class, 'dashboard']);
        Route::get('/monitoring/metrics/{type}', [MonitoringController::class, 'getMetrics']);
        Route::get('/monitoring/alerts', [MonitoringController::class, 'getAlerts']);
        Route::post('/monitoring/alerts/{id}/acknowledge', [MonitoringController::class, 'acknowledgeAlert']);
    });

});
