<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth', 'verified', 'staff'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::get('users/{user}', [UserProfileController::class, 'show'])->name('users.show');

require __DIR__.'/courses.php';
require __DIR__.'/enrollments.php';
require __DIR__.'/settings.php';
