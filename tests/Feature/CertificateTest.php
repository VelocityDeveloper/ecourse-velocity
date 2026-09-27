<?php

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Section;
use App\Models\User;

/**
 * A course with one lesson and one quiz (weight 1), passing grade 70, and an enrolled student.
 *
 * @return array{instructor: User, course: Course, lesson: Lesson, quiz: Quiz, student: User}
 */
function certificateCourse(): array
{
    $instructor = User::factory()->instructor()->create(['name' => 'Rizky Pratama']);
    $course = Course::factory()->ownedBy($instructor)->published()->create(['title' => 'Laravel 12 dari Nol']);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $student = User::factory()->student()->create(['name' => 'Nadia Putri']);
    Enrollment::factory()->for($student)->for($course)->create();

    return [
        'instructor' => $instructor,
        'course' => $course,
        'lesson' => Lesson::factory()->create(['section_id' => $section->id]),
        'quiz' => Quiz::factory()->create(['section_id' => $section->id]),
        'student' => $student,
    ];
}

/**
 * Complete the lesson and hand the quiz in with the given score out of 10.
 */
function finishCourse(User $student, Lesson $lesson, Quiz $quiz, int $score): void
{
    $student->completedLessons()->attach($lesson->id);
    QuizAttempt::factory()->for($student)->for($quiz)->submitted($score, 10)->create();
}

test('a student who finished the course with a passing grade can claim a certificate', function () {
    ['course' => $course, 'lesson' => $lesson, 'quiz' => $quiz, 'student' => $student] = certificateCourse();
    finishCourse($student, $lesson, $quiz, 9);

    $this->actingAs($student)
        ->get(route('my-courses.index'))
        ->assertInertia(fn ($page) => $page
            ->where('enrollments.0.certificate.eligible', true)
            ->where('enrollments.0.certificate.code', null)
        );

    $response = $this->actingAs($student)->post(route('certificates.store', $course));

    $certificate = Certificate::sole();
    $response->assertRedirect(route('certificates.show', $certificate));

    expect($certificate->user_id)->toBe($student->id)
        ->and($certificate->code)->toMatch('/^EV-\d{4}-[A-HJ-NP-Z2-9]{8}$/')
        ->and($certificate->student_name)->toBe('Nadia Putri')
        ->and($certificate->course_title)->toBe('Laravel 12 dari Nol')
        ->and($certificate->instructor_name)->toBe('Rizky Pratama')
        ->and($certificate->final_percent)->toBe(90)
        ->and($certificate->letter)->toBe('A');

    // Claiming again returns the same certificate.
    $this->actingAs($student)->post(route('certificates.store', $course))
        ->assertRedirect(route('certificates.show', $certificate));

    expect(Certificate::query()->count())->toBe(1);

    $this->actingAs($student)
        ->get(route('my-courses.index'))
        ->assertInertia(fn ($page) => $page->where('enrollments.0.certificate.code', $certificate->code));
});

test('no certificate before every lesson and quiz is done', function () {
    ['course' => $course, 'quiz' => $quiz, 'student' => $student] = certificateCourse();
    QuizAttempt::factory()->for($student)->for($quiz)->submitted(10, 10)->create();

    $this->actingAs($student)
        ->post(route('certificates.store', $course))
        ->assertRedirect();

    expect(Certificate::query()->count())->toBe(0);
});

test('no certificate while the final grade is below the course passing grade', function () {
    ['course' => $course, 'lesson' => $lesson, 'quiz' => $quiz, 'student' => $student] = certificateCourse();
    finishCourse($student, $lesson, $quiz, 6);

    $this->actingAs($student)
        ->get(route('my-courses.index'))
        ->assertInertia(fn ($page) => $page
            ->where('enrollments.0.certificate.eligible', false)
            ->where('enrollments.0.certificate.final_percent', 60)
            ->where('enrollments.0.certificate.passing_grade', 70)
        );

    $this->actingAs($student)->post(route('certificates.store', $course));

    expect(Certificate::query()->count())->toBe(0);

    $course->update(['passing_grade' => 60]);

    $this->actingAs($student)->post(route('certificates.store', $course));

    expect(Certificate::query()->count())->toBe(1);
});

test('a student who is not enrolled cannot get a certificate', function () {
    ['course' => $course] = certificateCourse();
    $stranger = User::factory()->student()->create();

    $this->actingAs($stranger)->post(route('certificates.store', $course));

    expect(Certificate::query()->count())->toBe(0);
});

test('anyone can verify a certificate by its code, but only the owner and course staff can download it', function () {
    ['instructor' => $instructor, 'course' => $course, 'student' => $student] = certificateCourse();
    $certificate = Certificate::factory()->for($student)->for($course)->create(['student_name' => 'Nadia Putri']);

    $this->get(route('certificates.show', $certificate))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('certificates/Show')
            ->where('certificate.code', $certificate->code)
            ->where('certificate.student_name', 'Nadia Putri')
            ->where('canDownload', false)
        );

    $this->get(route('certificates.download', $certificate))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->student()->create())
        ->get(route('certificates.download', $certificate))
        ->assertForbidden();

    $this->actingAs($student)
        ->get(route('certificates.show', $certificate))
        ->assertInertia(fn ($page) => $page->where('canDownload', true));

    $pdf = $this->actingAs($student)->get(route('certificates.download', $certificate));
    $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
    expect(substr($pdf->getContent(), 0, 5))->toBe('%PDF-');

    $this->actingAs($instructor)->get(route('certificates.download', $certificate))->assertOk();
});

test('an unknown certificate code is not found', function () {
    $this->get(route('certificates.show', 'EV-2026-NOTREAL1'))->assertNotFound();
});

test('the gradebook lists the certificate code of each student', function () {
    ['instructor' => $instructor, 'course' => $course, 'student' => $student] = certificateCourse();
    $certificate = Certificate::factory()->for($student)->for($course)->create();

    $this->actingAs($instructor)
        ->get(route('courses.grades.index', $course))
        ->assertInertia(fn ($page) => $page->where('students.0.certificate_code', $certificate->code));
});
