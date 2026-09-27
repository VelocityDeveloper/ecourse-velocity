<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseGradeController;
use App\Http\Controllers\CourseProgressController;
use App\Http\Controllers\LessonAttachmentController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizQuestionController;
use App\Http\Controllers\SectionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'staff'])->group(function () {
    Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
    Route::put('courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::patch('courses/{course}/status', [CourseController::class, 'updateStatus'])->name('courses.status.update');
    Route::delete('courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    Route::get('courses/{course}/progress', [CourseProgressController::class, 'index'])->name('courses.progress.index');
    Route::get('courses/{course}/progress/{student}', [CourseProgressController::class, 'show'])->name('courses.progress.show');
    Route::get('courses/{course}/grades', [CourseGradeController::class, 'index'])->name('courses.grades.index');
    Route::get('courses/{course}/grades/export', [CourseGradeController::class, 'export'])->name('courses.grades.export');
    Route::patch('courses/{course}/grading', [CourseGradeController::class, 'update'])->name('courses.grading.update');

    Route::post('courses/{course}/sections', [SectionController::class, 'store'])->name('sections.store');
    Route::put('sections/{section}', [SectionController::class, 'update'])->name('sections.update');
    Route::patch('sections/{section}/move', [SectionController::class, 'move'])->name('sections.move');
    Route::delete('sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');

    Route::get('lessons', [LessonController::class, 'index'])->name('lessons.index');
    Route::post('sections/{section}/lessons', [LessonController::class, 'store'])->name('lessons.store');
    Route::get('lessons/{lesson}/edit', [LessonController::class, 'edit'])->name('lessons.edit');
    Route::put('lessons/{lesson}', [LessonController::class, 'update'])->name('lessons.update');
    Route::patch('lessons/{lesson}/move', [LessonController::class, 'move'])->name('lessons.move');
    Route::delete('lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');

    Route::post('lessons/{lesson}/attachments', [LessonAttachmentController::class, 'store'])->name('lesson-attachments.store');
    Route::delete('attachments/{attachment}', [LessonAttachmentController::class, 'destroy'])->name('lesson-attachments.destroy');

    Route::get('quizzes', [QuizController::class, 'index'])->name('quizzes.index');
    Route::post('sections/{section}/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
    Route::get('quizzes/{quiz}/edit', [QuizController::class, 'edit'])->name('quizzes.edit');
    Route::put('quizzes/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');
    Route::patch('quizzes/{quiz}/move', [QuizController::class, 'move'])->name('quizzes.move');
    Route::delete('quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');

    Route::post('quizzes/{quiz}/questions', [QuizQuestionController::class, 'store'])->name('quiz-questions.store');
    Route::put('questions/{question}', [QuizQuestionController::class, 'update'])->name('quiz-questions.update');
    Route::patch('questions/{question}/move', [QuizQuestionController::class, 'move'])->name('quiz-questions.move');
    Route::delete('questions/{question}', [QuizQuestionController::class, 'destroy'])->name('quiz-questions.destroy');
    Route::patch('lessons/{lesson}/section', [LessonController::class, 'moveToSection'])->name('lessons.section.update');
    Route::patch('quizzes/{quiz}/section', [QuizController::class, 'moveToSection'])->name('quizzes.section.update');
});
