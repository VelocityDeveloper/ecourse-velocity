<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CurriculumSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Give every course that has no curriculum yet three sections of lessons and quizzes.
     */
    public function run(): void
    {
        Course::query()
            ->whereDoesntHave('sections')
            ->each(function (Course $course): void {
                $this->seedCurriculum($course);
            });
    }

    /**
     * Build the sections, lessons and quizzes of a single course.
     */
    private function seedCurriculum(Course $course): void
    {
        $outline = [
            [
                'title' => 'Memulai',
                'description' => 'Kenalan dengan materi dan siapkan alat yang dibutuhkan.',
                'lessons' => [
                    ['Selamat datang di '.$course->title, Lesson::TYPE_VIDEO, 5],
                    ['Menyiapkan lingkungan kerja', Lesson::TYPE_ARTICLE, 10],
                    ['Gambaran materi dan target belajar', Lesson::TYPE_VIDEO, 8],
                ],
                'quiz' => null,
            ],
            [
                'title' => 'Konsep Inti',
                'description' => 'Fondasi yang dipakai di sepanjang kursus.',
                'lessons' => [
                    ['Konsep dasar yang wajib dipahami', Lesson::TYPE_VIDEO, 18],
                    ['Praktik langsung: contoh pertama', Lesson::TYPE_VIDEO, 24],
                    ['Rangkuman dan kesalahan umum', Lesson::TYPE_ARTICLE, 12],
                ],
                'quiz' => ['Kuis Konsep Inti', 15],
            ],
            [
                'title' => 'Proyek Akhir',
                'description' => 'Terapkan semuanya dalam satu proyek utuh.',
                'lessons' => [
                    ['Merancang proyek', Lesson::TYPE_ARTICLE, 15],
                    ['Membangun proyek langkah demi langkah', Lesson::TYPE_VIDEO, 35],
                ],
                'quiz' => ['Ujian Akhir', 30],
            ],
        ];

        foreach ($outline as $sectionIndex => $sectionData) {
            $section = $course->sections()->create([
                'title' => $sectionData['title'],
                'description' => $sectionData['description'],
                'position' => $sectionIndex + 1,
            ]);

            foreach ($sectionData['lessons'] as $lessonIndex => [$title, $type, $minutes]) {
                $this->createLesson($section, $title, $type, $minutes, $lessonIndex + 1);
            }

            if ($sectionData['quiz'] !== null) {
                [$quizTitle, $timeLimit] = $sectionData['quiz'];
                $this->createQuiz($section, $quizTitle, $timeLimit);
            }
        }
    }

    /**
     * Create a video lesson with a link, or an article lesson with written content.
     */
    private function createLesson(Section $section, string $title, string $type, int $minutes, int $position): void
    {
        $section->lessons()->create([
            'title' => $title,
            'content_type' => $type,
            'content_url' => $type === Lesson::TYPE_VIDEO ? 'https://example.com/videos/'.Str::slug($title) : null,
            'content' => $type === Lesson::TYPE_ARTICLE
                ? '<h2>'.e($title).'</h2><p>Bacalah materi ini dengan teliti lalu coba praktikkan contohnya sendiri.</p><ul><li>Pahami tujuannya</li><li>Ikuti langkah demi langkah</li><li>Catat hal yang belum jelas</li></ul>'
                : null,
            'duration_minutes' => $minutes,
            'position' => $position,
        ]);
    }

    /**
     * Create a quiz with one question of every answer mode.
     */
    private function createQuiz(Section $section, string $title, int $timeLimitMinutes): void
    {
        $quiz = Quiz::query()->create([
            'section_id' => $section->id,
            'title' => $title,
            'description' => 'Uji pemahamanmu tentang materi pada bab ini.',
            'time_limit_minutes' => $timeLimitMinutes,
            'position' => 1,
        ]);

        $this->createQuestion($quiz, 1, 'Materi sebaiknya langsung dipraktikkan, bukan hanya dibaca.', QuizQuestion::MODE_TRUE_FALSE, 5, [
            ['True', true],
            ['False', false],
        ]);

        $this->createQuestion($quiz, 2, 'Apa langkah terbaik ketika menemui error yang belum pernah dilihat?', QuizQuestion::MODE_SINGLE, 10, [
            ['Membaca pesan error dan mencari penyebabnya', true],
            ['Menghapus kode yang error', false],
            ['Mengabaikannya', false],
        ]);

        $this->createQuestion($quiz, 3, 'Mana saja kebiasaan belajar yang efektif?', QuizQuestion::MODE_MULTIPLE, 0, [
            ['Mencoba ulang contoh dengan variasi sendiri', true],
            ['Membuat catatan ringkas', true],
            ['Melewati bagian latihan', false],
        ], [5, 10]);
    }

    /**
     * Create a question with its options and, for multiple answer questions, its score tiers.
     *
     * @param  list<array{string, bool}>  $options
     * @param  list<int>  $scores
     */
    private function createQuestion(Quiz $quiz, int $position, string $text, string $mode, int $points, array $options, array $scores = []): void
    {
        $question = $quiz->questions()->create([
            'question' => $text,
            'answer_mode' => $mode,
            'points' => $points,
            'position' => $position,
        ]);

        foreach ($options as $index => [$optionText, $isCorrect]) {
            $question->options()->create([
                'text' => $optionText,
                'is_correct' => $isCorrect,
                'position' => $index + 1,
            ]);
        }

        foreach ($scores as $index => $scorePoints) {
            $question->scores()->create([
                'correct_count' => $index + 1,
                'points' => $scorePoints,
            ]);
        }
    }
}
