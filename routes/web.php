<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstructorApplicationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth', 'verified', 'staff'])->group(function () {
    Route::get('dasbor', DashboardController::class)->name('dashboard');
});

Route::get('jadi-instruktur', [InstructorApplicationController::class, 'create'])->name('instructor-applications.create');
Route::get('jadi-instruktur/masuk', [InstructorApplicationController::class, 'login'])
    ->middleware('auth')
    ->name('instructor-applications.login');
Route::post('jadi-instruktur', [InstructorApplicationController::class, 'store'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('instructor-applications.store');

Route::middleware('auth')->group(function () {
    Route::get('notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifikasi/{notification}', [NotificationController::class, 'open'])->whereUuid('notification')->name('notifications.open');
});

Route::get('instruktur/{user:slug}', [UserProfileController::class, 'show'])->name('users.show');

require __DIR__.'/courses.php';
require __DIR__.'/enrollments.php';
require __DIR__.'/blog.php';
require __DIR__.'/settings.php';
require __DIR__.'/legacy.php';
