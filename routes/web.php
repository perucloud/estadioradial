<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/noticias', [PostController::class, 'index'])->name('posts.index');
Route::get('/noticias/{category:slug}', [PostController::class, 'category'])->name('posts.category');
Route::get('/noticias/{category:slug}/{post:slug}', [PostController::class, 'show'])->name('posts.show');

Route::get('/programas', [ProgramController::class, 'index'])->name('programs.index');
Route::get('/programas/{program:slug}', [ProgramController::class, 'show'])->name('programs.show');

Route::get('/programacion', ScheduleController::class)->name('schedule');
Route::get('/en-vivo', LiveController::class)->name('live');
