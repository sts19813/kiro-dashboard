<?php

use App\Http\Controllers\AgentConfigController;
use Illuminate\Support\Facades\Route;

Route::middleware('agent.api.key')->group(function () {
    Route::get('/agent/config', [AgentConfigController::class, 'apiShow'])->name('api.agent.config.show');
    Route::put('/agent/config', [AgentConfigController::class, 'apiUpdate'])->name('api.agent.config.update');
});


