<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\LiquidationController;
use App\Http\Controllers\WorkerController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('liquidaciones', [LiquidationController::class, 'index'])->name('liquidations.index');
    Route::get('liquidaciones/{liquidation}', [LiquidationController::class, 'show'])->name('liquidations.show');
    Route::get('liquidaciones/{liquidation}/excel', [LiquidationController::class, 'excel'])->name('liquidations.excel');

    Route::get('contratos/{contract}/liquidar', [LiquidationController::class, 'create'])->name('liquidations.create');
    Route::post('contratos/{contract}/liquidar', [LiquidationController::class, 'store'])->name('liquidations.store');

    Route::get('empresas', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('trabajadores', [WorkerController::class, 'index'])->name('workers.index');

    Route::middleware('admin')->group(function () {
        Route::get('empresas/crear', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('empresas', [CompanyController::class, 'store'])->name('companies.store');
        Route::get('empresas/{company}/editar', [CompanyController::class, 'edit'])->name('companies.edit');
        Route::put('empresas/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::delete('empresas/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');

        Route::get('trabajadores/crear', [WorkerController::class, 'create'])->name('workers.create');
        Route::post('trabajadores', [WorkerController::class, 'store'])->name('workers.store');
        Route::get('trabajadores/{worker}/editar', [WorkerController::class, 'edit'])->name('workers.edit');
        Route::put('trabajadores/{worker}', [WorkerController::class, 'update'])->name('workers.update');
        Route::delete('trabajadores/{worker}', [WorkerController::class, 'destroy'])->name('workers.destroy');
    });
});

require __DIR__.'/settings.php';
