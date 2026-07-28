<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PasswordController as AdminPasswordController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/admin/recuperar-clave', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('/admin/recuperar-clave', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/admin/restablecer-clave/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('/admin/restablecer-clave', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active'])->group(function () {
    Route::post('/salir', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/cambiar-clave', [AdminPasswordController::class, 'edit'])->name('password.change');
    Route::put('/cambiar-clave', [AdminPasswordController::class, 'update'])->name('password.update');

    Route::middleware('password.changed')->group(function () {
        Route::get('/', AdminDashboardController::class)
            ->middleware('permission:dashboard.view')
            ->name('dashboard');

        Route::middleware('permission:users.view')->group(function () {
            Route::get('/usuarios', [AdminUserController::class, 'index'])->name('users.index');
            Route::get('/usuarios/nuevo', [AdminUserController::class, 'create'])
                ->middleware('permission:users.create.editorial')
                ->name('users.create');
            Route::post('/usuarios', [AdminUserController::class, 'store'])
                ->middleware('permission:users.create.editorial')
                ->name('users.store');
            Route::get('/usuarios/{user}/editar', [AdminUserController::class, 'edit'])
                ->middleware('permission:users.update')
                ->name('users.edit');
            Route::put('/usuarios/{user}', [AdminUserController::class, 'update'])
                ->middleware('permission:users.update')
                ->name('users.update');
        });
    });
});

Route::get('/', HomeController::class)->name('home');

Route::get('/noticias', [PostController::class, 'index'])->name('posts.index');
Route::get('/noticias/{category:slug}', [PostController::class, 'category'])->name('posts.category');
Route::get('/noticias/{category:slug}/{post:slug}', [PostController::class, 'show'])->name('posts.show');

Route::get('/programas', [ProgramController::class, 'index'])->name('programs.index');
Route::get('/programas/{program:slug}', [ProgramController::class, 'show'])->name('programs.show');

Route::get('/programacion', ScheduleController::class)->name('schedule');
Route::get('/en-vivo', LiveController::class)->name('live');
