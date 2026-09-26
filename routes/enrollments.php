<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CourseReviewController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LearnController;
use App\Http\Controllers\LearningDashboardController;
use App\Http\Controllers\LearnQuizController;
use App\Http\Controllers\LessonBookmarkController;
use App\Http\Controllers\LessonNoteController;
use App\Http\Controllers\LessonQuestionController;
use App\Http\Controllers\LessonReplyController;
use App\Http\Controllers\MyCourseController;
use App\Http\Controllers\QuizAttemptController;
use Illuminate\Support\Facades\Route;

Route::get('catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('catalog/{course}', [CatalogController::class, 'show'])->name('catalog.show');

Route::middleware(['auth'])->group(function () {
    Route::post('catalog/{course}/enroll', [CatalogController::class, 'enroll'])->name('catalog.enroll');

    Route::put('catalog/{course}/review', [CourseReviewController::class, 'update'])->name('catalog.review.update');
    Route::delete('reviews/{review}', [CourseReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('my-courses', [MyCourseController::class, 'index'])->name('my-courses.index');

    Route::get('learning', LearningDashboardController::class)->name('learning.dashboard');
    Route::get('learning/notes', [LessonNoteController::class, 'index'])->name('learning.notes');
    Route::get('learning/bookmarks', [LessonBookmarkController::class, 'index'])->name('learning.bookmarks');

    Route::patch('enrollments/{enrollment}/cancel', [EnrollmentController::class, 'cancel'])->name('enrollments.cancel');
});

Route::middleware(['auth', 'staff'])->group(function () {
    Route::get('enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::post('enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
    Route::get('enrollments/{enrollment}', [EnrollmentController::class, 'show'])->name('enrollments.show');
});

Route::middleware(['auth'])->prefix('learn')->name('learn.')->group(function () {
    Route::get('{course}', [LearnController::class, 'show'])->name('show');
    Route::get('{course}/lessons/{lesson}', [LearnController::class, 'lesson'])->name('lessons.show');
    Route::post('{course}/lessons/{lesson}/complete', [LearnController::class, 'complete'])->name('lessons.complete');
    Route::delete('{course}/lessons/{lesson}/complete', [LearnController::class, 'uncomplete'])->name('lessons.uncomplete');
    Route::get('attachments/{attachment}/download', [LearnController::class, 'attachment'])->name('attachments.download');

    Route::put('{course}/lessons/{lesson}/note', [LessonNoteController::class, 'update'])->name('lessons.note.update');
    Route::post('{course}/lessons/{lesson}/bookmark', [LessonBookmarkController::class, 'store'])->name('lessons.bookmark.store');
    Route::delete('{course}/lessons/{lesson}/bookmark', [LessonBookmarkController::class, 'destroy'])->name('lessons.bookmark.destroy');

    Route::post('{course}/lessons/{lesson}/questions', [LessonQuestionController::class, 'store'])->name('questions.store');
    Route::delete('questions/{question}', [LessonQuestionController::class, 'destroy'])->name('questions.destroy');
    Route::post('questions/{question}/replies', [LessonReplyController::class, 'store'])->name('replies.store');
    Route::delete('replies/{reply}', [LessonReplyController::class, 'destroy'])->name('replies.destroy');

    Route::get('{course}/quizzes/{quiz}', [LearnQuizController::class, 'show'])->name('quizzes.show');
    Route::post('{course}/quizzes/{quiz}/attempts', [LearnQuizController::class, 'start'])->name('quizzes.start');

    Route::get('attempts/{attempt}', [QuizAttemptController::class, 'show'])->name('attempts.show');
    Route::post('attempts/{attempt}/submit', [QuizAttemptController::class, 'submit'])->name('attempts.submit');
});
