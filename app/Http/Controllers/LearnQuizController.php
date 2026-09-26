<?php

namespace App\Http\Controllers;

use App\Actions\BuildLearningOutline;
use App\Actions\GradeQuizAttempt;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LearnQuizController extends Controller
{
    /**
     * Show the quiz introduction with the student's previous attempts.
     */
    public function show(Request $request, Course $course, Quiz $quiz, BuildLearningOutline $buildOutline, GradeQuizAttempt $grade): Response|RedirectResponse
    {
        if (Gate::denies('learn', $course)) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('Enroll in this course to open its lessons.')]);

            return to_route('catalog.show', $course);
        }

        $this->ensureQuizBelongsToCourse($course, $quiz);

        $actor = $this->actor($request);
        $this->closeExpiredAttempts($actor, $quiz, $grade);
        $course->enrollments()->active()->where('user_id', $actor->id)->first()?->recordActivity();

        $quiz->load('questions.scores');
        $outline = $buildOutline($actor, $course);
        $attempts = $quiz->attempts()->where('user_id', $actor->id)->latest('started_at')->get();
        $openAttempt = $attempts->first(fn (QuizAttempt $attempt): bool => ! $attempt->isSubmitted());
        $submitted = $attempts->filter(fn (QuizAttempt $attempt): bool => $attempt->isSubmitted());

        return Inertia::render('learn/Quiz', [
            'outline' => $outline,
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'questions_count' => $quiz->questions->count(),
                'max_score' => $quiz->questions->sum(fn (QuizQuestion $question): int => $question->maxPoints()),
                'section_title' => $quiz->section->title,
            ],
            'attempts' => $submitted
                ->map(fn (QuizAttempt $attempt): array => [
                    'id' => $attempt->id,
                    'score' => (int) $attempt->score,
                    'max_score' => $attempt->max_score,
                    'is_late' => $attempt->is_late,
                    'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'bestScore' => $submitted->max('score'),
            'openAttemptId' => $openAttempt?->id,
            'neighbours' => BuildLearningOutline::neighbours($outline['items'], 'quiz', $quiz->id),
        ]);
    }

    /**
     * Start a new attempt, or resume the one that is still open.
     */
    public function start(Request $request, Course $course, Quiz $quiz, GradeQuizAttempt $grade): RedirectResponse
    {
        Gate::authorize('learn', $course);
        $this->ensureQuizBelongsToCourse($course, $quiz);

        if ($quiz->questions()->doesntExist()) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('This quiz has no questions yet.')]);

            return back();
        }

        $actor = $this->actor($request);
        $this->closeExpiredAttempts($actor, $quiz, $grade);

        $attempt = $quiz->attempts()->where('user_id', $actor->id)->inProgress()->first()
            ?? QuizAttempt::start($actor, $quiz);

        return to_route('learn.attempts.show', $attempt);
    }

    /**
     * Close any open attempt whose time ran out while the student was away.
     */
    private function closeExpiredAttempts(User $student, Quiz $quiz, GradeQuizAttempt $grade): void
    {
        $quiz->attempts()
            ->where('user_id', $student->id)
            ->inProgress()
            ->get()
            ->filter(fn (QuizAttempt $attempt): bool => $attempt->isPastDeadline())
            ->each(fn (QuizAttempt $attempt) => $attempt->handIn([], $grade));
    }

    /**
     * Refuse a quiz that is not part of the course in the URL.
     */
    private function ensureQuizBelongsToCourse(Course $course, Quiz $quiz): void
    {
        abort_unless($quiz->belongsToCourse($course), 404);
    }

    /**
     * Get the authenticated user making the request.
     */
    private function actor(Request $request): User
    {
        $actor = $request->user();

        assert($actor instanceof User);

        return $actor;
    }
}
