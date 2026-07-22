<?php

use ImranDevBd\AiHub\Http\Controllers\StudioController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StudioController::class, 'index'])->name('index');
Route::get('/api/bootstrap', [StudioController::class, 'bootstrap'])->name('api.bootstrap');
Route::post('/api/settings', [StudioController::class, 'saveSettings'])->name('api.settings');
Route::post('/api/priority', [StudioController::class, 'savePriority'])->name('api.priority');
Route::post('/api/test', [StudioController::class, 'test'])->name('api.test');
Route::get('/api/analytics', [StudioController::class, 'analytics'])->name('api.analytics');
