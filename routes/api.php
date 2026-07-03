<?php

use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/properties', [PropertyController::class, 'index']);
Route::get('/properties/{id}', [PropertyController::class, 'show']);
Route::get('/properties/{id}/summary', [PropertyController::class, 'summary'])
    ->middleware('throttle:10,1'); // AI-backed endpoint, keep it modest

Route::get('/work-orders', [WorkOrderController::class, 'index']);
Route::post('/work-orders', [WorkOrderController::class, 'store'])
    ->middleware('throttle:10,1'); // each request may spend AI quota, keep it modest
