<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseGradeController;
use App\Http\Controllers\CourseProgressController;
use App\Http\Controllers\CourseReviewController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LessonAttachmentController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizQuestionController;
use App\Http\Controllers\SectionController;
use Illuminate\Support\Facades\Route;

/*
 * The staff dashboard, under /dasbor with paths named after the sidebar menu.
 * Pages use slugs; the form actions behind them (save, move, delete) keep ids.
 */
Route::middleware(['auth', 'staff'])->prefix('dasbor')->group(function () {
    Route::get('kursus', [CourseController::class, 'index'])->name('courses.index');
    Route::get('kursus/buat', [CourseController::class, 'create'])->name('courses.create');
    Route::post('kursus', [CourseController::class, 'store'])->name('courses.store');
    Route::get('kursus/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('kursus/{course:slug}/ubah', [CourseController::class, 'edit'])->name('courses.edit');
    Route::put('kursus/{course:slug}', [CourseController::class, 'update'])->name('courses.update');
    Route::patch('kursus/{course:slug}/status', [CourseController::class, 'updateStatus'])->name('courses.status.update');
    Route::delete('kursus/{course:slug}', [CourseController::class, 'destroy'])->name('courses.destroy');
    Route::get('kursus/{course:slug}/progres', [CourseProgressController::class, 'index'])->name('courses.progress.index');
    Route::get('kursus/{course:slug}/progres/{student:slug}', [CourseProgressController::class, 'show'])->withoutScopedBindings()->name('courses.progress.show');
    Route::get('kursus/{course:slug}/nilai', [CourseGradeController::class, 'index'])->name('courses.grades.index');
    Route::get('kursus/{course:slug}/nilai/ekspor', [CourseGradeController::class, 'export'])->name('courses.grades.export');
    Route::get('kursus/{course:slug}/ulasan', [CourseReviewController::class, 'course'])->name('courses.reviews.index');
    Route::patch('kursus/{course:slug}/penilaian', [CourseGradeController::class, 'update'])->name('courses.grading.update');

    Route::post('kursus/{course:slug}/bab', [SectionController::class, 'store'])->name('sections.store');
    Route::put('bab/{section}', [SectionController::class, 'update'])->name('sections.update');
    Route::patch('bab/{section}/pindah', [SectionController::class, 'move'])->name('sections.move');
    Route::delete('bab/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');

    Route::get('materi', [LessonController::class, 'index'])->name('lessons.index');
    Route::post('bab/{section}/materi', [LessonController::class, 'store'])->name('lessons.store');
    Route::put('materi/{lesson}', [LessonController::class, 'update'])->name('lessons.update');
    Route::patch('materi/{lesson}/pindah', [LessonController::class, 'move'])->name('lessons.move');
    Route::patch('materi/{lesson}/bab', [LessonController::class, 'moveToSection'])->name('lessons.section.update');
    Route::delete('materi/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');

    Route::post('materi/{lesson}/lampiran', [LessonAttachmentController::class, 'store'])->name('lesson-attachments.store');
    Route::delete('lampiran/{attachment}', [LessonAttachmentController::class, 'destroy'])->name('lesson-attachments.destroy');

    Route::get('kuis', [QuizController::class, 'index'])->name('quizzes.index');
    Route::post('bab/{section}/kuis', [QuizController::class, 'store'])->name('quizzes.store');
    Route::put('kuis/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');
    Route::patch('kuis/{quiz}/pindah', [QuizController::class, 'move'])->name('quizzes.move');
    Route::patch('kuis/{quiz}/bab', [QuizController::class, 'moveToSection'])->name('quizzes.section.update');
    Route::delete('kuis/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');

    Route::post('kuis/{quiz}/soal', [QuizQuestionController::class, 'store'])->name('quiz-questions.store');
    Route::put('soal/{question}', [QuizQuestionController::class, 'update'])->name('quiz-questions.update');
    Route::patch('soal/{question}/pindah', [QuizQuestionController::class, 'move'])->name('quiz-questions.move');
    Route::delete('soal/{question}', [QuizQuestionController::class, 'destroy'])->name('quiz-questions.destroy');

    // A lesson or quiz slug is only unique inside its course.
    Route::scopeBindings()->group(function () {
        Route::get('kursus/{course:slug}/materi/{lesson:slug}/ubah', [LessonController::class, 'edit'])->name('lessons.edit');
        Route::get('kursus/{course:slug}/kuis/{quiz:slug}/ubah', [QuizController::class, 'edit'])->name('quizzes.edit');
    });

    Route::get('pendaftaran', [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::post('pendaftaran', [EnrollmentController::class, 'store'])->name('enrollments.store');
    Route::get('pendaftaran/{enrollment}', [EnrollmentController::class, 'show'])->name('enrollments.show');
});
