<?php

use App\Http\Controllers\AgentChatController;
use App\Http\Controllers\AgentConfigController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WhatsappConfigController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('auth.login');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/config/usuarios', function () {
        return view('pages.config.users', [
            'users' => User::latest()->paginate(10),
        ]);
    })->name('settings.users');
    Route::get('/config/agente', [AgentConfigController::class, 'index'])->name('settings.agent');
    Route::post('/config/agente', [AgentConfigController::class, 'store'])->name('settings.agent.store');
    Route::get('/config/agente/chat', [AgentChatController::class, 'index'])->name('settings.agent.chat');
    Route::get('/config/agente/chat/history', [AgentChatController::class, 'history'])->name('settings.agent.chat.history');
    Route::post('/config/agente/chat', [AgentChatController::class, 'send'])->name('settings.agent.chat.send');
    Route::get('/config/whatsapp', [WhatsappConfigController::class, 'index'])->name('settings.whatsapp');
    Route::post('/config/whatsapp', [WhatsappConfigController::class, 'store'])->name('settings.whatsapp.store');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.index');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/provedores', [LocationController::class, 'index'])->name('locations.index');
    Route::post('/provedores/puntos', [LocationController::class, 'store'])->name('locations.store');
    Route::delete('/provedores/puntos/{locationPoint}', [LocationController::class, 'destroy'])->name('locations.destroy');

    Route::redirect('/proveedores', '/provedores');
    Route::redirect('/ubicaciones', '/provedores');
    Route::redirect('/mapa', '/provedores');
});

require __DIR__.'/auth.php';
