<?php

use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'service' => 'isp-billing-api', 'version' => 'v1']);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('permission:users.manage')->prefix('admin')->group(function () {
        Route::apiResource('users', UserController::class)->except(['create', 'edit']);
    });

    Route::apiResource('customers', CustomerController::class)
        ->except(['create', 'edit'])
        ->middleware('permission:customers.manage');
});
