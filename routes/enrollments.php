<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CourseReviewController;
use App\Http\Controllers\CourseWishlistController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LearnController;
use App\Http\Controllers\LearningDashboardController;
use App\Http\Controllers\LearnQuizController;
use App\Http\Controllers\LessonBookmarkController;
use App\Http\Controllers\LessonNoteController;
use App\Http\Controllers\LessonQuestionController;
use App\Http\Controllers\LessonReplyController;
use App\Http\Controllers\MyCourseController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\QuizAttemptController;
use Illuminate\Support\Facades\Route;

/*
 * Public and student URLs follow the menu labels, and courses, lessons and quizzes
 * are addressed by slug. A course page itself lives at /{category}/{course}; that
 * route is registered last, in routes/permalinks.php, so it never shadows these.
 */

Route::get('kursus', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('sertifikat/{certificate}', [CertificateController::class, 'show'])->name('certificates.show');

Route::middleware(['auth'])->group(function () {
    Route::post('kursus/{course:slug}/daftar', [CatalogController::class, 'enroll'])->name('catalog.enroll');
    Route::post('kursus/{course:slug}/wishlist', [CourseWishlistController::class, 'store'])->name('catalog.wishlist.store');
    Route::delete('kursus/{course:slug}/wishlist', [CourseWishlistController::class, 'destroy'])->name('catalog.wishlist.destroy');
    Route::put('kursus/{course:slug}/ulasan', [CourseReviewController::class, 'update'])->name('catalog.review.update');
    Route::delete('ulasan/{review}', [CourseReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('beli/{course:slug}', [OrderController::class, 'checkout'])->name('orders.checkout');
    Route::post('beli/{course:slug}', [OrderController::class, 'store'])->name('orders.store');

    // "Belajar Saya" and its tabs
    Route::get('belajar-saya', LearningDashboardController::class)->name('learning.dashboard');
    Route::get('belajar-saya/kursus', [MyCourseController::class, 'index'])->name('my-courses.index');
    Route::get('belajar-saya/catatan', [LessonNoteController::class, 'index'])->name('learning.notes');
    Route::get('belajar-saya/markah', [LessonBookmarkController::class, 'index'])->name('learning.bookmarks');
    Route::get('belajar-saya/wishlist', [CourseWishlistController::class, 'index'])->name('learning.wishlist');
    Route::get('belajar-saya/pesanan', [OrderController::class, 'index'])->name('orders.index');
    Route::get('belajar-saya/pesanan/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('belajar-saya/pesanan/{order}/konfirmasi-pembayaran', [OrderController::class, 'createProof'])->name('orders.proof.create');
    Route::post('belajar-saya/pesanan/{order}/bukti', [OrderController::class, 'submitProof'])->name('orders.proof.store');
    Route::get('belajar-saya/pesanan/{order}/bukti', [OrderController::class, 'proof'])->name('orders.proof.show');
    Route::patch('belajar-saya/pesanan/{order}/batalkan', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::patch('pendaftaran/{enrollment}/batalkan', [EnrollmentController::class, 'cancel'])->name('enrollments.cancel');

    Route::post('belajar/{course:slug}/sertifikat', [CertificateController::class, 'store'])->name('certificates.store');
    Route::get('sertifikat/{certificate}/pdf', [CertificateController::class, 'download'])->name('certificates.download');
});

// The course player
Route::middleware(['auth'])->prefix('belajar')->name('learn.')->group(function () {
    Route::get('percobaan/{attempt}', [QuizAttemptController::class, 'show'])->name('attempts.show');
    Route::post('percobaan/{attempt}/kirim', [QuizAttemptController::class, 'submit'])->name('attempts.submit');
    Route::get('lampiran/{attachment}/unduh', [LearnController::class, 'attachment'])->name('attachments.download');
    Route::delete('diskusi/{question}', [LessonQuestionController::class, 'destroy'])->name('questions.destroy');
    Route::post('diskusi/{question}/balasan', [LessonReplyController::class, 'store'])->name('replies.store');
    Route::delete('balasan/{reply}', [LessonReplyController::class, 'destroy'])->name('replies.destroy');

    Route::scopeBindings()->group(function () {
        Route::get('{course:slug}', [LearnController::class, 'show'])->name('show');
        Route::get('{course:slug}/materi/{lesson:slug}', [LearnController::class, 'lesson'])->name('lessons.show');
        Route::post('{course:slug}/materi/{lesson:slug}/selesai', [LearnController::class, 'complete'])->name('lessons.complete');
        Route::delete('{course:slug}/materi/{lesson:slug}/selesai', [LearnController::class, 'uncomplete'])->name('lessons.uncomplete');
        Route::put('{course:slug}/materi/{lesson:slug}/catatan', [LessonNoteController::class, 'update'])->name('lessons.note.update');
        Route::post('{course:slug}/materi/{lesson:slug}/markah', [LessonBookmarkController::class, 'store'])->name('lessons.bookmark.store');
        Route::delete('{course:slug}/materi/{lesson:slug}/markah', [LessonBookmarkController::class, 'destroy'])->name('lessons.bookmark.destroy');
        Route::post('{course:slug}/materi/{lesson:slug}/diskusi', [LessonQuestionController::class, 'store'])->name('questions.store');

        Route::get('{course:slug}/kuis/{quiz:slug}', [LearnQuizController::class, 'show'])->name('quizzes.show');
        Route::post('{course:slug}/kuis/{quiz:slug}/mulai', [LearnQuizController::class, 'start'])->name('quizzes.start');
    });
});
