<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsumosController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(Auth::check() ? 'menu' : 'login'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('login.store');

Route::middleware('auth')->group(function () {
    Route::view('/menu', 'menu')->name('menu');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/consumos', [ConsumosController::class, 'index'])->name('consumos.index');
    Route::get('/reporte-comidas', [ConsumosController::class, 'reporte'])->name('reportes.index');
    Route::get('/reporte-comidas/exportar', [ConsumosController::class, 'exportarReporte'])->name('reportes.exportar');
    Route::get('/empleados/buscar', [ConsumosController::class, 'buscarEmpleados'])->name('empleados.buscar');
    Route::post('/consumos', [ConsumosController::class, 'store'])->name('consumos.store');
});
