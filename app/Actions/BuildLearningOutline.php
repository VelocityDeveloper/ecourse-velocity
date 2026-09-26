<?php

namespace App\Actions;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;

class BuildLearningOutline
{
    public function __construct(private CalculateCourseProgress $calculateProgress) {}

    /**
     * Build the course outline shown beside the lesson and quiz screens.
     *
     * Items follow the catalog order: a section's lessons first, then its quizzes.
     *
     * @return array{
     *     course: array{id: int, title: string},
     *     sections: list<array{id: int, title: string, items: list<array<string, mixed>>}>,
     *     items: list<array{type: string, id: int, title: string}>,
     *     progress: array{completed: int, total: int, percent: int}
     * }
     */
    public function __invoke(User $student, Course $course): array
    {
        $course->loadMissing([
            'sections.lessons:id,section_id,title,content_type,duration_minutes,position',
            'sections.quizzes' => fn ($query) => $query->withCount('questions'),
        ]);

        $progress = ($this->calculateProgress)($student, $course);
        $bookmarkedIds = $student->bookmarkedLessons()->pluck('lessons.id')->map(fn (mixed $id): int => (int) $id)->all();

        $sections = [];
        $items = [];

        foreach ($course->sections as $section) {
            $sectionItems = [
                ...$section->lessons->map(fn (Lesson $lesson): array => [
                    'type' => 'lesson',
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'content_type' => $lesson->content_type,
                    'duration_minutes' => $lesson->duration_minutes,
                    'is_done' => in_array($lesson->id, $progress['completed_lesson_ids'], true),
                    'is_bookmarked' => in_array($lesson->id, $bookmarkedIds, true),
                ])->all(),
                ...$section->quizzes->map(fn (Quiz $quiz): array => [
                    'type' => 'quiz',
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'time_limit_minutes' => $quiz->time_limit_minutes,
                    'questions_count' => (int) $quiz->questions_count,
                    'is_done' => in_array($quiz->id, $progress['attempted_quiz_ids'], true),
                ])->all(),
            ];

            $sections[] = $this->section($section, $sectionItems);

            foreach ($sectionItems as $item) {
                $items[] = ['type' => $item['type'], 'id' => $item['id'], 'title' => $item['title']];
            }
        }

        return [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'sections' => $sections,
            'items' => $items,
            'progress' => [
                'completed' => $progress['completed'],
                'total' => $progress['total'],
                'percent' => $progress['percent'],
            ],
        ];
    }

    /**
     * Shape one section of the outline.
     *
     * @param  list<array<string, mixed>>  $items
     * @return array{id: int, title: string, items: list<array<string, mixed>>}
     */
    private function section(Section $section, array $items): array
    {
        return [
            'id' => $section->id,
            'title' => $section->title,
            'items' => $items,
        ];
    }

    /**
     * Find the items directly before and after the given one in the outline.
     *
     * @param  list<array{type: string, id: int, title: string}>  $items
     * @return array{previous: array{type: string, id: int, title: string}|null, next: array{type: string, id: int, title: string}|null}
     */
    public static function neighbours(array $items, string $type, int $id): array
    {
        foreach ($items as $index => $item) {
            if ($item['type'] === $type && $item['id'] === $id) {
                return [
                    'previous' => $items[$index - 1] ?? null,
                    'next' => $items[$index + 1] ?? null,
                ];
            }
        }

        return ['previous' => null, 'next' => null];
    }
}
