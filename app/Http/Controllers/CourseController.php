<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Requests\UpdateCourseStatusRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    /**
     * List the courses the current user is allowed to manage.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Course::class);

        $actor = $this->actor($request);
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $courses = Course::query()
            ->manageableBy($actor)
            ->with(['category:id,name', 'instructor:id,name,slug'])
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $inner) => $inner
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
            ))
            ->when(in_array($status, Course::STATUSES, true), fn (Builder $query) => $query->where('status', $status))
            ->when($request->filled('category_id'), fn (Builder $query) => $query->where('category_id', $request->integer('category_id')))
            ->when(
                $actor->isAdmin() && $request->filled('instructor_id'),
                fn (Builder $query) => $query->where('instructor_id', $request->integer('instructor_id'))
            )
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Course $course): array => $this->summarize($course));

        return Inertia::render('courses/Index', [
            'courses' => $courses,
            'filters' => $request->only(['search', 'status', 'category_id', 'instructor_id']),
            'categories' => $this->categoryOptions(),
            'instructors' => $actor->isAdmin() ? $this->instructorOptions() : [],
            'statuses' => Course::STATUSES,
            'can' => [
                'create' => Gate::allows('create', Course::class),
                'manageAllCourses' => $actor->isAdmin(),
            ],
        ]);
    }

    /**
     * Show the form for creating a course.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', Course::class);

        $actor = $this->actor($request);

        return Inertia::render('courses/Create', [
            'categories' => $this->categoryOptions(),
            'instructors' => $actor->isAdmin() ? $this->instructorOptions() : [],
            'levels' => Course::LEVELS,
            'statuses' => Course::statusesFor($actor),
            'isAdmin' => $actor->isAdmin(),
        ]);
    }

    /**
     * Store a newly created course.
     */
    public function store(StoreCourseRequest $request): RedirectResponse
    {
        Gate::authorize('create', Course::class);

        $attributes = $request->courseAttributes();
        $attributes['thumbnail_path'] = $this->storeThumbnail($request);

        $course = Course::create($attributes);

        return to_route('courses.show', $course);
    }

    /**
     * Show a single course.
     */
    public function show(Request $request, Course $course): Response
    {
        Gate::authorize('view', $course);

        $course->load([
            'category:id,name',
            'instructor:id,name,slug,email',
            'sections.lessons',
            'sections.quizzes.questions.scores',
        ]);

        return Inertia::render('courses/Show', [
            'course' => [
                ...$this->summarize($course),
                'description' => $course->description,
                'instructor_email' => $course->instructor?->email,
                'students_count' => $course->enrollments()->active()->count(),
                'updated_at' => $course->updated_at?->toIso8601String(),
            ],
            'sections' => $this->curriculum($course),
            'movableLessons' => Inertia::optional(
                fn (): array => $this->movableLessons($this->actor($request)),
            ),
            'movableQuizzes' => Inertia::optional(
                fn (): array => $this->movableQuizzes($this->actor($request)),
            ),
            'statuses' => Course::statusesFor($this->actor($request), $course),
            'contentTypes' => Lesson::CONTENT_TYPES,
            'form' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->description,
                'category_id' => $course->category_id,
                'instructor_id' => $course->instructor_id,
                'price' => $course->price,
                'level' => $course->level,
                'status' => $course->status,
                'thumbnail_url' => $course->thumbnail_url,
            ],
            'categories' => $this->categoryOptions(),
            'instructors' => $this->actor($request)->isAdmin() ? $this->instructorOptions() : [],
            'levels' => Course::LEVELS,
            'isAdmin' => $this->actor($request)->isAdmin(),
        ]);
    }

    /**
     * Send the old edit link to the course page's edit tabs.
     */
    public function edit(Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        // Editing lives in the tabs of the course page.
        return to_route('courses.show', ['course' => $course, 'tab' => 'informasi']);
    }

    /**
     * Update the given course.
     */
    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $attributes = $request->courseAttributes();

        if ($request->hasFile('thumbnail')) {
            $attributes['thumbnail_path'] = $this->storeThumbnail($request, $course);
        }

        $course->update($attributes);

        return to_route('courses.show', $course);
    }

    /**
     * Change only the status of the given course.
     */
    public function updateStatus(UpdateCourseStatusRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('updateStatus', $course);

        $course->update(['status' => $request->string('status')->toString()]);

        return back();
    }

    /**
     * Delete the given course along with its thumbnail.
     */
    public function destroy(Course $course): RedirectResponse
    {
        Gate::authorize('delete', $course);

        if ($course->thumbnail_path !== null) {
            Storage::disk(Course::THUMBNAIL_DISK)->delete($course->thumbnail_path);
        }

        $course->delete();

        return to_route('courses.index');
    }

    /**
     * Build the row payload shared by the list and detail screens.
     *
     * @return array<string, mixed>
     */
    private function summarize(Course $course): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'status' => $course->status,
            'level' => $course->level,
            'price' => $course->price,
            'thumbnail_url' => $course->thumbnail_url,
            'category' => $course->category?->only(['id', 'name']),
            'instructor' => $course->instructor?->only(['id', 'name', 'slug']),
            'created_at' => $course->created_at?->toIso8601String(),
            'can' => [
                'update' => Gate::allows('update', $course),
                'delete' => Gate::allows('delete', $course),
            ],
        ];
    }

    /**
     * List every lesson the user may relocate, newest course first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function movableLessons(User $actor): array
    {
        return Lesson::query()
            ->with(['section:id,title,course_id', 'section.course:id,title'])
            ->whereHas('section.course', fn (Builder $query) => $query->manageableBy($actor))
            ->orderBy('title')
            ->get()
            ->map(fn (Lesson $lesson): array => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'section_id' => $lesson->section_id,
                'section_title' => $lesson->section->title,
                'course_title' => $lesson->section->course->title,
            ])
            ->all();
    }

    /**
     * List every quiz the user may relocate.
     *
     * @return array<int, array<string, mixed>>
     */
    private function movableQuizzes(User $actor): array
    {
        return Quiz::query()
            ->with(['section:id,title,course_id', 'section.course:id,title'])
            ->whereHas('section.course', fn (Builder $query) => $query->manageableBy($actor))
            ->orderBy('title')
            ->get()
            ->map(fn (Quiz $quiz): array => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'section_id' => $quiz->section_id,
                'section_title' => $quiz->section->title,
                'course_title' => $quiz->section->course->title,
            ])
            ->all();
    }

    /**
     * Build the curriculum payload: sections with their lessons, in order.
     *
     * @return array<int, array<string, mixed>>
     */
    private function curriculum(Course $course): array
    {
        return $course->sections
            ->map(fn (Section $section): array => [
                'id' => $section->id,
                'title' => $section->title,
                'description' => $section->description,
                'position' => $section->position,
                'duration_minutes' => (int) $section->lessons->sum('duration_minutes'),
                'lessons' => $section->lessons
                    ->map(fn (Lesson $lesson): array => [
                        'id' => $lesson->id,
                        'slug' => $lesson->slug,
                        'title' => $lesson->title,
                        'content_type' => $lesson->content_type,
                        'content_url' => $lesson->content_url,
                        'duration_minutes' => $lesson->duration_minutes,
                        'position' => $lesson->position,
                    ])
                    ->all(),
                'quizzes' => $section->quizzes
                    ->map(fn (Quiz $quiz): array => [
                        'id' => $quiz->id,
                        'slug' => $quiz->slug,
                        'title' => $quiz->title,
                        'description' => $quiz->description,
                        'time_limit_minutes' => $quiz->time_limit_minutes,
                        'position' => $quiz->position,
                        'questions_count' => $quiz->questions->count(),
                        'total_points' => $quiz->questions
                            ->map(fn (QuizQuestion $question): int => $question->maxPoints())
                            ->sum(),
                    ])
                    ->all(),
            ])
            ->all();
    }

    /**
     * Get every category as a select option.
     *
     * @return array<int, array<string, mixed>>
     */
    private function categoryOptions(): array
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    /**
     * Get every instructor as a select option.
     *
     * @return array<int, array<string, mixed>>
     */
    private function instructorOptions(): array
    {
        return User::query()
            ->where('role', User::ROLE_INSTRUCTOR)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    /**
     * Move the uploaded thumbnail onto disk, replacing the previous one.
     */
    private function storeThumbnail(Request $request, ?Course $course = null): ?string
    {
        $thumbnail = $request->file('thumbnail');

        if ($thumbnail === null) {
            return $course?->thumbnail_path;
        }

        if ($course?->thumbnail_path !== null) {
            Storage::disk(Course::THUMBNAIL_DISK)->delete($course->thumbnail_path);
        }

        return $thumbnail->store(Course::THUMBNAIL_DIRECTORY, Course::THUMBNAIL_DISK) ?: null;
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
