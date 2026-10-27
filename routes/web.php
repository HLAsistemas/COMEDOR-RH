<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsumosController;
use App\Http\Controllers\MenuDiaController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(Auth::check() ? 'menu' : 'login'));

Route::prefix('comedor')->group(function () {
    Route::get('/', fn () => redirect()->route(Auth::check() ? 'menu' : 'login'));

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware(['guest', 'throttle:5,1'])
        ->name('login.store');

    Route::middleware('auth')->group(function () {
        Route::view('/menu', 'menu')->name('menu');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::middleware('admin')->group(function () {
            Route::get('/usuarios', [UsersController::class, 'index'])->name('usuarios.index');
            Route::post('/usuarios', [UsersController::class, 'store'])->name('usuarios.store');
        });
        Route::get('/menu-dia', [MenuDiaController::class, 'index'])->name('menu-dia.index');
        Route::post('/menu-dia', [MenuDiaController::class, 'store'])->name('menu-dia.store');
        Route::post('/menu-dia/extras', [MenuDiaController::class, 'storeExtras'])->name('menu-dia.extras.store');
        Route::post('/menu-dia/extras-fijos', [MenuDiaController::class, 'storeFixedExtra'])->name('menu-dia.extras-fijos.store');
        Route::post('/menu-dia/extras-fijos/{extraFijo}/estado', [MenuDiaController::class, 'toggleFixedExtra'])->name('menu-dia.extras-fijos.toggle');
        Route::get('/consumos', [ConsumosController::class, 'index'])->name('consumos.index');
        Route::get('/reporte-comidas', [ConsumosController::class, 'reporte'])->name('reportes.index');
        Route::get('/reporte-comidas/exportar', [ConsumosController::class, 'exportarReporte'])->name('reportes.exportar');
        Route::get('/empleados/buscar', [ConsumosController::class, 'buscarEmpleados'])->name('empleados.buscar');
        Route::post('/consumos', [ConsumosController::class, 'store'])->name('consumos.store');
    });
});
