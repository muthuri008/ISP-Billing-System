<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'isp-billing-api',
        'version' => 'v1',
    ]);
});
