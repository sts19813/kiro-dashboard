<?php

use App\Http\Controllers\AgentConfigController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\TourismUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('agent.api.key')->group(function () {
    Route::get('/agent/config', [AgentConfigController::class, 'apiShow'])->name('api.agent.config.show');
    Route::put('/agent/config', [AgentConfigController::class, 'apiUpdate'])->name('api.agent.config.update');
    Route::post('/locations', [LocationController::class, 'apiStore'])->name('api.locations.store');
    Route::delete('/locations/{locationPoint}', [LocationController::class, 'apiDestroy'])->name('api.locations.destroy');
});

Route::get('/locations', [LocationController::class, 'apiIndex'])->name('api.locations.index');
Route::get('/locations/{locationId}', [LocationController::class, 'apiShow'])->name('api.locations.show');
Route::get('/points', [LocationController::class, 'apiPoints'])->name('api.points.index');

Route::prefix('tourism/users')->group(function () {
    Route::post('/', [TourismUserController::class, 'upsert'])->name('api.tourism.users.upsert');
    Route::get('/{phone}', [TourismUserController::class, 'show'])->name('api.tourism.users.show');
    Route::post('/{phone}/locations', [TourismUserController::class, 'storeLocation'])->name('api.tourism.users.locations.store');
    Route::get('/{phone}/locations', [TourismUserController::class, 'locationHistory'])->name('api.tourism.users.locations.history');
    Route::get('/{phone}/recommendations', [TourismUserController::class, 'recommendations'])->name('api.tourism.users.recommendations');
    Route::post('/{phone}/chat-messages', [TourismUserController::class, 'storeChatMessage'])->name('api.tourism.users.chat.store');
    Route::get('/{phone}/chat-messages', [TourismUserController::class, 'chatHistory'])->name('api.tourism.users.chat.history');
});

