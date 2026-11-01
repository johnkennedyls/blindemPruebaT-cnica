<?php

use App\Http\Controllers\LiquidationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('liquidaciones', [LiquidationController::class, 'index'])->name('liquidations.index');
    Route::get('liquidaciones/{liquidation}', [LiquidationController::class, 'show'])->name('liquidations.show');

    Route::get('contratos/{contract}/liquidar', [LiquidationController::class, 'create'])->name('liquidations.create');
    Route::post('contratos/{contract}/liquidar', [LiquidationController::class, 'store'])->name('liquidations.store');
});

require __DIR__.'/settings.php';
