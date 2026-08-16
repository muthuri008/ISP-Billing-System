<?php

use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\FinanceReportController;
use App\Http\Controllers\Api\V1\PackageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json(['status' => 'ok', 'service' => 'isp-billing-api', 'version' => 'v1']));

    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('permission:users.manage')->prefix('admin')->group(function () {
            Route::apiResource('users', UserController::class)->except(['create', 'edit']);
        });

        Route::middleware('permission:customers.manage')->apiResource('customers', CustomerController::class)->except(['create', 'edit']);
        Route::middleware('permission:packages.manage')->apiResource('packages', PackageController::class)->except(['create', 'edit']);

        Route::middleware('permission:reports.view')->prefix('reports')->group(function () {
            Route::get('/finance/summary', [FinanceReportController::class, 'summary']);
            Route::get('/finance/revenue', [FinanceReportController::class, 'revenue']);
        });
    });
});
