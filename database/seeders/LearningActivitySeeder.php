<?php

namespace Database\Seeders;

use App\Actions\GradeQuizAttempt;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LearningActivitySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Sample review comments, picked in turn for each rating.
     *
     * @var list<string>
     */
    private const array REVIEW_COMMENTS = [
        'Penjelasannya runtut dan contohnya relevan dengan pekerjaan sehari-hari.',
        'Materinya padat, quiz di tiap bab sangat membantu mengukur pemahaman.',
        'Bagus untuk pemula, tapi saya berharap ada lebih banyak latihan.',
        'Instruktur cepat menjawab pertanyaan di forum diskusi. Recommended!',
        null,
    ];

    /**
     * Give enrolled students some progress, notes, bookmarks, discussion and reviews.
     *
     * Students who already have finished lessons are left alone, so the seeder
     * can run more than once.
     */
    public function run(GradeQuizAttempt $grade): void
    {
        Enrollment::query()
            ->active()
            ->with(['user', 'course.sections.lessons', 'course.sections.quizzes.questions.options', 'course.sections.quizzes.questions.scores', 'course.instructor'])
            ->whereHas('user', fn ($user) => $user->whereDoesntHave('completedLessons'))
            ->orderBy('id')
            ->get()
            ->each(function (Enrollment $enrollment, int $index) use ($grade): void {
                $this->seedProgress($enrollment, $index, $grade);
            });
    }

    /**
     * Seed one enrollment's learning history.
     */
    private function seedProgress(Enrollment $enrollment, int $index, GradeQuizAttempt $grade): void
    {
        $student = $enrollment->user;
        $course = $enrollment->course;
        $lessons = $course->sections->flatMap->lessons->values();

        if ($lessons->isEmpty()) {
            return;
        }

        // Spread progress out: some students barely started, some are nearly done.
        $finished = $lessons->take(($index % $lessons->count()) + 1);
        $student->completedLessons()->syncWithoutDetaching($finished->pluck('id')->all());

        /** @var Lesson $current */
        $current = $lessons->get($finished->count(), $finished->last());
        $enrollment->forceFill([
            'last_lesson_id' => $current->id,
            'last_accessed_at' => now()->subDays($index % 7)->subHours($index % 5),
        ])->save();

        if ($index % 2 === 0) {
            $student->bookmarkedLessons()->syncWithoutDetaching([$finished->first()->id]);
            $student->lessonNotes()->updateOrCreate(
                ['lesson_id' => $finished->first()->id],
                ['body' => "Poin penting:\n- Ulangi contoh dengan data sendiri\n- Tanyakan di diskusi kalau masih bingung"],
            );
        }

        $firstQuiz = $course->sections->flatMap->quizzes->first();

        if ($firstQuiz !== null && $finished->count() >= 3) {
            $answers = $firstQuiz->questions->mapWithKeys(fn ($question) => [
                $question->id => $question->options
                    ->filter(fn (QuizOption $option): bool => (bool) $option->is_correct || $index % 3 === 0)
                    ->pluck('id')
                    ->all(),
            ])->all();

            $attempt = QuizAttempt::start($student, $firstQuiz);
            $attempt->handIn($answers, $grade);
        }

        if ($index % 3 === 0) {
            $question = $current->questions()->create([
                'user_id' => $student->id,
                'body' => 'Apakah ada contoh kasus nyata untuk materi "'.$current->title.'"?',
            ]);

            if ($course->instructor instanceof User && $index % 2 === 0) {
                $question->replies()->create([
                    'user_id' => $course->instructor->id,
                    'body' => 'Ada! Coba lihat proyek akhir di bab terakhir, di sana konsep ini dipakai langsung.',
                ]);
            }
        }

        if ($finished->count() >= 2) {
            $this->review($course, $student, $index);
        }
    }

    /**
     * Leave a review from the student, skewed towards positive ratings.
     */
    private function review(Course $course, User $student, int $index): void
    {
        CourseReview::query()->firstOrCreate(
            ['course_id' => $course->id, 'user_id' => $student->id],
            [
                'rating' => [5, 4, 5, 3, 4][$index % 5],
                'comment' => self::REVIEW_COMMENTS[$index % count(self::REVIEW_COMMENTS)],
            ],
        );
    }
}
