<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
 * Old id-based and English URLs, kept as permanent redirects so shared links still work.
 */

Route::permanentRedirect('catalog', '/kursus');
Route::get('catalog/{course}', fn (Course $course) => redirect()->route('catalog.show', $course->permalinkParameters(), 301));
Route::get('users/{user}', fn (User $user) => redirect()->route('users.show', $user, 301));
Route::permanentRedirect('login', '/masuk');
Route::permanentRedirect('register', '/daftar');
Route::permanentRedirect('forgot-password', '/lupa-kata-sandi');
Route::permanentRedirect('learning', '/belajar-saya');
Route::permanentRedirect('learning/notes', '/belajar-saya/catatan');
Route::permanentRedirect('learning/bookmarks', '/belajar-saya/markah');
Route::permanentRedirect('learning/wishlist', '/belajar-saya/wishlist');
Route::permanentRedirect('my-courses', '/belajar-saya/kursus');
Route::permanentRedirect('orders', '/belajar-saya/pesanan');
Route::get('orders/{order}', fn (string $order) => redirect()->route('orders.show', $order, 301));
Route::get('learn/{course}', fn (Course $course) => redirect()->route('learn.show', $course, 301));

/*
 * The old English dashboard and account settings paths. Signed-in only, so a
 * draft course's slug is never revealed to a guest.
 */
Route::middleware('auth')->group(function () {
    Route::permanentRedirect('dashboard', '/dasbor');
    Route::permanentRedirect('courses', '/dasbor/kursus');
    Route::permanentRedirect('courses/create', '/dasbor/kursus/buat');
    Route::get('courses/{course}', fn (Course $course) => redirect()->route('courses.show', $course, 301));
    Route::get('courses/{course}/edit', fn (Course $course) => redirect()->route('courses.edit', $course, 301));
    Route::get('courses/{course}/progress', fn (Course $course) => redirect()->route('courses.progress.index', $course, 301));
    Route::get('courses/{course}/grades', fn (Course $course) => redirect()->route('courses.grades.index', $course, 301));
    Route::permanentRedirect('lessons', '/dasbor/materi');
    Route::get('lessons/{lesson}/edit', fn (Lesson $lesson) => redirect()->route('lessons.edit', ['course' => $lesson->section->course, 'lesson' => $lesson], 301));
    Route::permanentRedirect('quizzes', '/dasbor/kuis');
    Route::get('quizzes/{quiz}/edit', fn (Quiz $quiz) => redirect()->route('quizzes.edit', ['course' => $quiz->section->course, 'quiz' => $quiz], 301));
    Route::permanentRedirect('enrollments', '/dasbor/pendaftaran');
    Route::get('enrollments/{enrollment}', fn (string $enrollment) => redirect()->route('enrollments.show', $enrollment, 301));

    Route::permanentRedirect('admin/users', '/dasbor/pengguna');
    Route::permanentRedirect('admin/users/create', '/dasbor/pengguna/tambah');
    Route::get('admin/users/{user}/edit', fn (User $user) => redirect()->route('admin.users.edit', $user, 301));
    Route::permanentRedirect('admin/categories', '/dasbor/kategori');
    Route::permanentRedirect('admin/categories/create', '/dasbor/kategori/tambah');
    Route::get('admin/categories/{category}/edit', fn (Category $category) => redirect()->route('admin.categories.edit', $category, 301));
    Route::permanentRedirect('admin/settings', '/dasbor/pengaturan-situs/identitas');
    Route::get('admin/settings/{section}', fn (string $section) => redirect('/dasbor/pengaturan-situs/'.$section, 301))->where('section', '[a-z]+');
    Route::permanentRedirect('admin/banners', '/dasbor/pengaturan-situs/banner-promo');
    Route::permanentRedirect('admin/testimonials', '/dasbor/pengaturan-situs/testimoni');
    Route::permanentRedirect('admin/orders', '/dasbor/keuangan/pesanan');
    Route::get('admin/orders/{order}', fn (string $order) => redirect()->route('admin.orders.show', $order, 301));
    Route::permanentRedirect('admin/transactions', '/dasbor/keuangan/pesanan?status=paid');
    Route::permanentRedirect('admin/payment-settings', '/dasbor/pengaturan-situs/pembayaran');

    Route::permanentRedirect('settings', '/pengaturan/profil');
    Route::permanentRedirect('settings/profile', '/pengaturan/profil');
    Route::permanentRedirect('settings/security', '/pengaturan/keamanan');
    Route::permanentRedirect('settings/appearance', '/pengaturan/tampilan');
});
