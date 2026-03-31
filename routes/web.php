<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReservationSeriesController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
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
 * Tudo abaixo exige login.
 */
Route::middleware('auth')->group(function () {
    Route::get('availability', [AvailabilityController::class, 'index'])
        ->name('availability.index');

    // Salas
    Route::resource('rooms', RoomController::class)->except(['show']);

    // Usuarios
    Route::resource('users', UserController::class)->except(['show', 'destroy']);

    // Historico da agenda (passadas)
    Route::get('reservations/history', [ReservationController::class, 'history'])
        ->name('reservations.history');

    Route::get('reservation-series', [ReservationSeriesController::class, 'index'])
        ->name('reservation-series.index');
    Route::get('reservation-series/{reservationSeries}', [ReservationSeriesController::class, 'show'])
        ->name('reservation-series.show');
    Route::get('reservation-series/{reservationSeries}/edit', [ReservationSeriesController::class, 'edit'])
        ->name('reservation-series.edit');
    Route::put('reservation-series/{reservationSeries}', [ReservationSeriesController::class, 'update'])
        ->name('reservation-series.update');
    Route::patch('reservation-series/{reservationSeries}/cancel', [ReservationSeriesController::class, 'cancel'])
        ->name('reservation-series.cancel');

    Route::get('reservations/export-selected', [ReservationController::class, 'exportSelected'])
        ->name('reservations.export-selected');
    Route::delete('reservations/destroy-selected', [ReservationController::class, 'destroySelected'])
        ->name('reservations.destroy-selected');

    // Agendamentos
    Route::resource('reservations', ReservationController::class);

    // Perfil (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
