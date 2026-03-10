<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

/**
 * Entrada do sistema:
 * - Redireciona para a área principal (agendamentos).
 * - Assim, quem não estiver logado vai cair no login (por causa do middleware).
 */
Route::get('/', function () {
    return redirect()->route('reservations.index');
})->name('home');

/**
 * Compatibilidade com rota do Breeze.
 * A home real do sistema da secretaria e a agenda.
 */
Route::get('/dashboard', function () {
    return redirect()->route('reservations.index');
})->middleware(['auth'])->name('dashboard');

/**
 * 🔐 Área ADMIN (teste do middleware)
 */
Route::get('/admin-teste', function () {
    return 'Área administrativa ✅';
})->middleware('admin');

/**
 * Tudo abaixo exige login.
 */
Route::middleware('auth')->group(function () {

    // Salas
    Route::resource('rooms', RoomController::class)->except(['show']);

    // Historico da agenda (passadas)
    Route::get('reservations/history', [ReservationController::class, 'history'])
        ->name('reservations.history');

    // Agendamentos
    Route::resource('reservations', ReservationController::class);

    // Perfil (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
