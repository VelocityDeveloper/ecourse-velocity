<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth', 'verified', 'staff'])->group(function () {
    Route::get('dasbor', DashboardController::class)->name('dashboard');
});

Route::get('instruktur/{user:slug}', [UserProfileController::class, 'show'])->name('users.show');

require __DIR__.'/courses.php';
require __DIR__.'/enrollments.php';
require __DIR__.'/blog.php';
require __DIR__.'/settings.php';
require __DIR__.'/legacy.php';
