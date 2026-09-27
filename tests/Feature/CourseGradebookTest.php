<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Section;
use App\Models\User;
use App\Support\GradeScale;

/**
 * A course with two quizzes: "UTS" (weight 1, KKM 70) and "UAS" (weight 3, no KKM).
 *
 * @return array{instructor: User, course: Course, uts: Quiz, uas: Quiz}
 */
function gradedCourse(): array
{
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->published()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);

    return [
        'instructor' => $instructor,
        'course' => $course,
        'uts' => Quiz::factory()->create(['section_id' => $section->id, 'title' => 'UTS', 'weight' => 1, 'passing_score' => 70, 'position' => 1]),
        'uas' => Quiz::factory()->create(['section_id' => $section->id, 'title' => 'UAS', 'weight' => 3, 'passing_score' => null, 'position' => 2]),
    ];
}

function enrolledStudent(Course $course, string $name): User
{
    $student = User::factory()->student()->create(['name' => $name]);
    Enrollment::factory()->for($student)->for($course)->create();

    return $student;
}

function submittedAttempt(User $student, Quiz $quiz, int $score, int $max): QuizAttempt
{
    return QuizAttempt::factory()->for($student)->for($quiz)->submitted($score, $max)->create();
}

test('the gradebook uses each best attempt, weights the final grade and applies the passing grades', function () {
    ['instructor' => $instructor, 'course' => $course, 'uts' => $uts, 'uas' => $uas] = gradedCourse();

    $andi = enrolledStudent($course, 'Andi');
    submittedAttempt($andi, $uts, 5, 10);
    submittedAttempt($andi, $uts, 8, 10);
    submittedAttempt($andi, $uas, 18, 20);

    $budi = enrolledStudent($course, 'Budi');
    submittedAttempt($budi, $uts, 6, 10);

    $cancelled = User::factory()->student()->create();
    Enrollment::factory()->for($cancelled)->for($course)->cancelled()->create();

    $this->actingAs($instructor)
        ->get(route('courses.grades.index', $course))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('courses/Grades')
            ->where('course.passing_grade', 70)
            ->where('totalWeight', 4)
            ->has('quizzes', 2)
            ->where('quizzes.0.title', 'UTS')
            ->where('quizzes.0.passing_score', 70)
            ->has('students', 2)
            ->where('students.0.student.name', 'Andi')
            ->where("students.0.grades.{$uts->id}.percent", 80)
            ->where("students.0.grades.{$uts->id}.attempts", 2)
            ->where("students.0.grades.{$uts->id}.passed", true)
            ->where("students.0.grades.{$uas->id}.percent", 90)
            ->where("students.0.grades.{$uas->id}.passed", null)
            // (80 × 1 + 90 × 3) / 4 = 87.5 → 88
            ->where('students.0.final_percent', 88)
            ->where('students.0.letter', 'A')
            ->where('students.0.passed', true)
            ->where('students.1.student.name', 'Budi')
            ->where("students.1.grades.{$uts->id}.passed", false)
            ->where("students.1.grades.{$uas->id}.percent", null)
            // (60 × 1 + 0 × 3) / 4 = 15
            ->where('students.1.final_percent', 15)
            ->where('students.1.letter', 'E')
            ->where('students.1.passed', false)
            ->where('summary.passed', 1)
            ->where('summary.not_passed', 1)
            ->where('summary.average_final', 52)
        );
});

test('a quiz with weight zero does not count towards the final grade', function () {
    ['instructor' => $instructor, 'course' => $course, 'uts' => $uts, 'uas' => $uas] = gradedCourse();
    $uas->update(['weight' => 0]);

    $andi = enrolledStudent($course, 'Andi');
    submittedAttempt($andi, $uts, 7, 10);

    $this->actingAs($instructor)
        ->get(route('courses.grades.index', $course))
        ->assertInertia(fn ($page) => $page
            ->where('totalWeight', 1)
            ->where('students.0.final_percent', 70)
            ->where('students.0.letter', 'B')
            ->where('students.0.passed', true)
        );
});

test('the grade scale turns percentages into letters', function (int $percent, string $letter) {
    expect(GradeScale::letter($percent))->toBe($letter);
})->with([
    [100, 'A'], [85, 'A'], [84, 'B'], [70, 'B'], [69, 'C'], [55, 'C'], [54, 'D'], [40, 'D'], [39, 'E'], [0, 'E'],
]);

test('the instructor can change the course passing grade', function () {
    ['instructor' => $instructor, 'course' => $course] = gradedCourse();

    $this->actingAs($instructor)
        ->patch(route('courses.grading.update', $course), ['passing_grade' => 60])
        ->assertRedirect();

    expect($course->refresh()->passing_grade)->toBe(60);

    $this->actingAs($instructor)
        ->patch(route('courses.grading.update', $course), ['passing_grade' => 101])
        ->assertSessionHasErrors('passing_grade');
});

test('the gradebook downloads as a csv file', function () {
    ['instructor' => $instructor, 'course' => $course, 'uts' => $uts] = gradedCourse();
    $andi = enrolledStudent($course, 'Andi');
    submittedAttempt($andi, $uts, 8, 10);

    $response = $this->actingAs($instructor)->get(route('courses.grades.export', $course));

    $response->assertOk()->assertDownload();
    $csv = $response->streamedContent();

    expect($csv)->toStartWith("\xEF\xBB\xBF")
        ->and($csv)->toContain('Nama;Email;"UTS (%)";"UAS (%)";"Nilai akhir (%)";Predikat;"Status (KKM kursus 70)";"Nomor sertifikat"')
        ->and($csv)->toContain("Andi;{$andi->email};80;;20;E;\"Belum lulus\";");
});

test('other instructors and students cannot open the gradebook', function () {
    ['course' => $course] = gradedCourse();

    $this->actingAs(User::factory()->instructor()->create())
        ->get(route('courses.grades.index', $course))
        ->assertForbidden();

    $this->actingAs(User::factory()->instructor()->create())
        ->get(route('courses.grades.export', $course))
        ->assertForbidden();

    $this->actingAs(User::factory()->instructor()->create())
        ->patch(route('courses.grading.update', $course), ['passing_grade' => 10])
        ->assertForbidden();

    $this->actingAs(User::factory()->student()->create())
        ->get(route('courses.grades.index', $course))
        ->assertRedirect(route('home'));
});

test('a quiz passing score and weight can be saved from the quiz editor', function () {
    ['instructor' => $instructor, 'uts' => $uts] = gradedCourse();

    $this->actingAs($instructor)
        ->put(route('quizzes.update', $uts), [
            'title' => 'UTS',
            'passing_score' => 75,
            'weight' => 2,
        ])
        ->assertSessionHasNoErrors();

    expect($uts->refresh()->passing_score)->toBe(75)->and($uts->weight)->toBe(2);

    $this->actingAs($instructor)
        ->put(route('quizzes.update', $uts), ['title' => 'UTS', 'passing_score' => '', 'weight' => 1])
        ->assertSessionHasNoErrors();

    expect($uts->refresh()->passing_score)->toBeNull();

    $this->actingAs($instructor)
        ->put(route('quizzes.update', $uts), ['title' => 'UTS', 'passing_score' => 120, 'weight' => -1])
        ->assertSessionHasErrors(['passing_score', 'weight']);
});

test('the student sees the passing score and whether the best attempt passed', function () {
    ['course' => $course, 'uts' => $uts] = gradedCourse();
    $andi = enrolledStudent($course, 'Andi');
    $attempt = submittedAttempt($andi, $uts, 6, 10);
    $uts->questions()->create(['question' => 'Soal', 'answer_mode' => 'single', 'points' => 10, 'position' => 1]);

    $this->actingAs($andi)
        ->get(route('learn.quizzes.show', [$course, $uts]))
        ->assertInertia(fn ($page) => $page
            ->where('quiz.passing_score', 70)
            ->where('bestPercent', 60)
        );

    $this->actingAs($andi)
        ->get(route('learn.attempts.show', $attempt))
        ->assertInertia(fn ($page) => $page->where('quiz.passing_score', 70));
});
