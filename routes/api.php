<?php

use App\Http\Controllers\AgentConfigController;
use App\Http\Controllers\LocationController;
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
