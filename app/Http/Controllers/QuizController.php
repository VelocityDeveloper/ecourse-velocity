<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveRequest;
use App\Http\Requests\MoveToSectionRequest;
use App\Http\Requests\QuizRequest;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    /**
     * List every quiz across the courses the user may manage.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Course::class);

        $actor = $this->actor($request);
        $search = $request->string('search')->toString();

        $quizzes = Quiz::query()
            ->with([
                'section:id,title,course_id',
                'section.course:id,slug,title,instructor_id',
                'questions.scores',
            ])
            ->withCount('questions')
            ->whereHas('section.course', fn (Builder $query) => $query->manageableBy($actor))
            ->when($search !== '', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
            ->when($request->filled('course_id'), fn (Builder $query) => $query->whereHas(
                'section',
                fn (Builder $section) => $section->where('course_id', $request->integer('course_id'))
            ))
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Quiz $quiz): array => [
                'id' => $quiz->id,
                'slug' => $quiz->slug,
                'title' => $quiz->title,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'questions_count' => (int) $quiz->questions_count,
                'total_points' => $this->totalPoints($quiz),
                'section' => ['id' => $quiz->section->id, 'title' => $quiz->section->title],
                'course' => ['id' => $quiz->section->course->id, 'slug' => $quiz->section->course->slug, 'title' => $quiz->section->course->title],
            ]);

        return Inertia::render('quizzes/Index', [
            'quizzes' => $quizzes,
            'filters' => $request->only(['search', 'course_id']),
            'courses' => $this->courseOptions($actor),
        ]);
    }

    /**
     * Append a quiz to the section.
     */
    public function store(QuizRequest $request, Section $section): RedirectResponse
    {
        Gate::authorize('update', $section->course);

        $section->quizzes()->create([
            ...$request->validated(),
            'position' => ((int) $section->quizzes()->max('position')) + 1,
        ]);

        return back();
    }

    /**
     * Show the quiz builder.
     */
    public function edit(Course $course, Quiz $quiz): Response
    {
        $quiz->load(['section.course', 'questions.options', 'questions.scores']);
        Gate::authorize('update', $quiz->section->course);

        return Inertia::render('quizzes/Edit', [
            'quiz' => [
                'id' => $quiz->id,
                'slug' => $quiz->slug,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'passing_score' => $quiz->passing_score,
                'weight' => $quiz->weight,
                'total_points' => $this->totalPoints($quiz),
                'questions' => $quiz->questions
                    ->map(fn (QuizQuestion $question): array => [
                        'id' => $question->id,
                        'question' => $question->question,
                        'answer_mode' => $question->answer_mode,
                        'points' => $question->points,
                        'max_points' => $question->maxPoints(),
                        'position' => $question->position,
                        'options' => $question->options
                            ->map(fn (QuizOption $option): array => [
                                'id' => $option->id,
                                'text' => $option->text,
                                'is_correct' => $option->is_correct,
                            ])
                            ->all(),
                        'scores' => $question->scores->pluck('points')->all(),
                    ])
                    ->all(),
            ],
            'section' => ['id' => $quiz->section->id, 'title' => $quiz->section->title],
            'course' => ['id' => $quiz->section->course->id, 'slug' => $quiz->section->course->slug, 'title' => $quiz->section->course->title],
            'answerModes' => QuizQuestion::ANSWER_MODES,
            'maxOptions' => QuizQuestion::MAX_OPTIONS,
            'maxTimeLimitMinutes' => Quiz::MAX_TIME_LIMIT_MINUTES,
            'maxWeight' => Quiz::MAX_WEIGHT,
        ]);
    }

    /**
     * Update the quiz title, description and time limit.
     */
    public function update(QuizRequest $request, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('update', $quiz->section->course);

        $quiz->update($request->validated());

        return back();
    }

    /**
     * Move the quiz one step up or down within its section.
     */
    public function move(MoveRequest $request, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('update', $quiz->section->course);

        $quiz->move($request->string('direction')->toString());

        return back();
    }

    /**
     * Delete the quiz along with its questions.
     */
    public function destroy(Quiz $quiz): RedirectResponse
    {
        Gate::authorize('update', $quiz->section->course);

        $quiz->delete();
        $quiz->resequenceSiblings();

        return back();
    }

    /**
     * Move an existing quiz into another section.
     *
     * The quiz keeps its questions, options and answer key; only its place in
     * the curriculum changes. The section it leaves is resequenced.
     */
    public function moveToSection(MoveToSectionRequest $request, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('update', $quiz->section->course);

        $destination = $request->destination();
        $formerSectionId = $quiz->section_id;

        if ($formerSectionId === $destination->id) {
            return back();
        }

        DB::transaction(function () use ($quiz, $destination, $formerSectionId): void {
            $quiz->update([
                'section_id' => $destination->id,
                'position' => ((int) $destination->quizzes()->max('position')) + 1,
            ]);

            $this->resequenceQuizzes($formerSectionId);
        });

        return back();
    }

    /**
     * Close the gaps left in a section after a quiz moved away.
     */
    private function resequenceQuizzes(int $sectionId): void
    {
        Quiz::query()
            ->where('section_id', $sectionId)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->each(function (Quiz $quiz, int $index): void {
                $quiz->update(['position' => $index + 1]);
            });
    }

    /**
     * Sum the most points every question in the quiz can award.
     */
    private function totalPoints(Quiz $quiz): int
    {
        return $quiz->questions
            ->map(fn (QuizQuestion $question): int => $question->maxPoints())
            ->sum();
    }

    /**
     * Get the courses the user may manage, as select options.
     *
     * @return array<int, array<string, mixed>>
     */
    private function courseOptions(User $actor): array
    {
        return Course::query()
            ->manageableBy($actor)
            ->orderBy('title')
            ->get(['id', 'title'])
            ->toArray();
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
