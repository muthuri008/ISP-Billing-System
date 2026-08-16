<?php

use App\Http\Controllers\Api\V1\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('subscriptions')->group(function () {
    Route::get('/', [SubscriptionController::class, 'index']);
    Route::post('/', [SubscriptionController::class, 'store']);
    Route::get('/{subscription}', [SubscriptionController::class, 'show']);
    Route::post('/{subscription}/activate', [SubscriptionController::class, 'activate']);
    Route::post('/{subscription}/suspend', [SubscriptionController::class, 'suspend']);
    Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel']);
});
