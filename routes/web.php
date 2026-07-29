<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\HomepageController as AdminHomepageController;
use App\Http\Controllers\Admin\LocationController as AdminLocationController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\PasswordController as AdminPasswordController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Admin\ScheduleController as AdminScheduleController;
use App\Http\Controllers\Admin\StreamController as AdminStreamController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
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
        Route::get('/ubicaciones/opciones', [AdminLocationController::class, 'options'])
            ->name('locations.options');

        Route::get('/', AdminDashboardController::class)
            ->middleware('permission:dashboard.view')
            ->name('dashboard');

        Route::middleware('permission:media.manage')->group(function () {
            Route::get('/multimedia', [AdminMediaController::class, 'index'])->name('media.index');
            Route::get('/multimedia/biblioteca', [AdminMediaController::class, 'library'])->name('media.library');
            Route::post('/multimedia', [AdminMediaController::class, 'store'])->name('media.store');
            Route::put('/multimedia/{media}', [AdminMediaController::class, 'update'])->name('media.update');
            Route::delete('/multimedia/{media}', [AdminMediaController::class, 'destroy'])->name('media.destroy');
        });

        Route::middleware('permission:news.view')->group(function () {
            Route::get('/noticias', [AdminPostController::class, 'index'])->name('posts.index');
            Route::get('/noticias/nueva', [AdminPostController::class, 'create'])
                ->middleware('permission:news.create')
                ->name('posts.create');
            Route::post('/noticias', [AdminPostController::class, 'store'])
                ->middleware('permission:news.create')
                ->name('posts.store');
            Route::get('/noticias/{post}/editar', [AdminPostController::class, 'edit'])
                ->middleware('permission:news.update')
                ->name('posts.edit');
            Route::put('/noticias/{post}', [AdminPostController::class, 'update'])
                ->middleware('permission:news.update')
                ->name('posts.update');
            Route::get('/noticias/{post}/vista-previa', [AdminPostController::class, 'preview'])
                ->name('posts.preview');
            Route::post('/noticias/{post}/archivar', [AdminPostController::class, 'archive'])
                ->middleware('permission:news.update')
                ->name('posts.archive');
            Route::post('/noticias/{post}/recuperar', [AdminPostController::class, 'restore'])
                ->middleware('permission:news.update')
                ->name('posts.restore');
            Route::delete('/noticias/{post}', [AdminPostController::class, 'destroy'])
                ->middleware('permission:news.update')
                ->name('posts.destroy');
            Route::post('/noticias/{post}/restaurar-papelera', [AdminPostController::class, 'restoreDeleted'])
                ->withTrashed()
                ->middleware('permission:news.update')
                ->name('posts.restore-deleted');
            Route::post('/noticias/{post}/duplicar', [AdminPostController::class, 'duplicate'])
                ->middleware('permission:news.create')
                ->name('posts.duplicate');
        });

        Route::middleware('permission:categories.manage')->group(function () {
            Route::get('/categorias', [AdminCategoryController::class, 'index'])->name('categories.index');
            Route::post('/categorias', [AdminCategoryController::class, 'store'])->name('categories.store');
            Route::put('/categorias/{category:id}', [AdminCategoryController::class, 'update'])->name('categories.update');
            Route::delete('/categorias/{category:id}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
            Route::post('/categorias/orden', [AdminCategoryController::class, 'reorder'])->name('categories.reorder');
            Route::post('/categorias/{category}/restaurar', [AdminCategoryController::class, 'restore'])
                ->name('categories.restore');
            Route::delete('/categorias/{category}/eliminar-definitivamente', [AdminCategoryController::class, 'forceDestroy'])
                ->name('categories.force-destroy');

            Route::get('/etiquetas', [AdminTagController::class, 'index'])->name('tags.index');
            Route::post('/etiquetas', [AdminTagController::class, 'store'])->name('tags.store');
            Route::put('/etiquetas/{tag:id}', [AdminTagController::class, 'update'])->name('tags.update');
            Route::post('/etiquetas/{tag:id}/combinar', [AdminTagController::class, 'merge'])->name('tags.merge');
            Route::delete('/etiquetas/{tag:id}', [AdminTagController::class, 'destroy'])->name('tags.destroy');
        });

        Route::middleware('permission:locations.manage')->group(function () {
            Route::get('/ubicaciones', [AdminLocationController::class, 'index'])->name('locations.index');
            Route::post('/ubicaciones', [AdminLocationController::class, 'store'])->name('locations.store');
            Route::post('/ubicaciones/orden', [AdminLocationController::class, 'reorder'])->name('locations.reorder');
            Route::put('/ubicaciones/{location:id}', [AdminLocationController::class, 'update'])->name('locations.update');
            Route::delete('/ubicaciones/{location:id}', [AdminLocationController::class, 'destroy'])->name('locations.destroy');
            Route::post('/ubicaciones/{location}/restaurar', [AdminLocationController::class, 'restore'])
                ->name('locations.restore');
            Route::delete('/ubicaciones/{location}/eliminar-definitivamente', [AdminLocationController::class, 'forceDestroy'])
                ->name('locations.force-destroy');
        });

        Route::middleware('permission:programs.manage')->group(function () {
            Route::get('/programas', [AdminProgramController::class, 'index'])->name('programs.index');
            Route::get('/programas/nuevo', [AdminProgramController::class, 'create'])->name('programs.create');
            Route::post('/programas', [AdminProgramController::class, 'store'])->name('programs.store');
            Route::get('/programas/{program}/editar', [AdminProgramController::class, 'edit'])->name('programs.edit');
            Route::put('/programas/{program}', [AdminProgramController::class, 'update'])->name('programs.update');
            Route::delete('/programas/{program}', [AdminProgramController::class, 'destroy'])->name('programs.destroy');
        });

        Route::middleware('permission:schedule.manage')->group(function () {
            Route::get('/programacion-radial', [AdminScheduleController::class, 'index'])->name('schedule.index');
            Route::post('/programacion-radial', [AdminScheduleController::class, 'store'])->name('schedule.store');
            Route::put('/programacion-radial/{schedule}', [AdminScheduleController::class, 'update'])->name('schedule.update');
            Route::delete('/programacion-radial/{schedule}', [AdminScheduleController::class, 'destroy'])->name('schedule.destroy');
        });

        Route::middleware('permission:stream.manage')->group(function () {
            Route::get('/streaming', [AdminStreamController::class, 'index'])->name('streams.index');
            Route::post('/streaming', [AdminStreamController::class, 'store'])->name('streams.store');
            Route::put('/streaming/{stream}', [AdminStreamController::class, 'update'])->name('streams.update');
            Route::delete('/streaming/{stream}', [AdminStreamController::class, 'destroy'])->name('streams.destroy');
        });

        Route::middleware('permission:appearance.manage')->group(function () {
            Route::get('/apariencia/portada', [AdminHomepageController::class, 'edit'])->name('appearance.homepage.edit');
            Route::put('/apariencia/portada', [AdminHomepageController::class, 'update'])->name('appearance.homepage.update');
        });

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
Route::get('/noticias/territorios', [PostController::class, 'regional'])->name('posts.locations.index');
Route::get('/noticias/territorios/{path}', [PostController::class, 'location'])
    ->where('path', '[a-z0-9-]+(?:/[a-z0-9-]+)*')
    ->name('posts.locations.show');
Route::get('/noticias/{category:slug}', [PostController::class, 'category'])->name('posts.category');
Route::get('/noticias/{category:slug}/{post:slug}', [PostController::class, 'show'])->name('posts.show');

Route::get('/programas', [ProgramController::class, 'index'])->name('programs.index');
Route::get('/programas/{program:slug}', [ProgramController::class, 'show'])->name('programs.show');

Route::get('/programacion', ScheduleController::class)->name('schedule');
Route::get('/en-vivo', LiveController::class)->name('live');
