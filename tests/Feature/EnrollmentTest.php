<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

test('students are sent to the public site instead of enrollment management', function () {
    $this->actingAs(User::factory()->student()->create())
        ->get(route('enrollments.index'))
        ->assertRedirect(route('home'));
});

test('admins see every enrollment', function () {
    Enrollment::factory()->count(3)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('enrollments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrollments/Index')
            ->has('learners.data', 3)
        );
});

test('a student in several courses is listed once with every course', function () {
    $student = User::factory()->student()->create(['name' => 'Budi Santoso']);
    Enrollment::factory()->for($student)->for(Course::factory()->published()->create(['title' => 'Laravel']))->create();
    Enrollment::factory()->for($student)->for(Course::factory()->published()->create(['title' => 'Vue']))->create();
    Enrollment::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('enrollments.index'))
        ->assertInertia(fn ($page) => $page
            ->has('learners.data', 2)
            ->where('learners.total', 2)
            ->where('learners.data', fn ($rows) => collect(collect($rows)->firstWhere('student.name', 'Budi Santoso')['enrollments'])
                ->pluck('course.title')->sort()->values()->all() === ['Laravel', 'Vue'])
        );

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('enrollments.index', ['search' => 'Vue']))
        ->assertInertia(fn ($page) => $page
            ->has('learners.data', 1)
            ->has('learners.data.0.enrollments', 1)
            ->where('learners.data.0.enrollments.0.course.title', 'Vue')
        );
});

test('instructors only see enrollments in their own courses', function () {
    $instructor = User::factory()->instructor()->create();
    $own = Enrollment::factory()->for(Course::factory()->ownedBy($instructor)->published())->create();
    Enrollment::factory()->create();

    $this->actingAs($instructor)
        ->get(route('enrollments.index'))
        ->assertInertia(fn ($page) => $page
            ->has('learners.data', 1)
            ->has('learners.data.0.enrollments', 1)
            ->where('learners.data.0.enrollments.0.id', $own->id)
            ->where('learners.data.0.enrollments.0.can_cancel', true)
        );
});

test('enrollments can be searched by student and filtered by course and status', function () {
    $course = Course::factory()->published()->create();
    $budi = User::factory()->student()->create(['name' => 'Budi Santoso']);
    Enrollment::factory()->for($budi)->for($course)->create();
    Enrollment::factory()->for(User::factory()->student()->create(['name' => 'Siti Aminah']))->for($course)->create();
    Enrollment::factory()->for($budi)->cancelled()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('enrollments.index', [
            'search' => 'Budi',
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]))
        ->assertInertia(fn ($page) => $page
            ->has('learners.data', 1)
            ->where('learners.data.0.student.name', 'Budi Santoso')
            ->has('learners.data.0.enrollments', 1)
        );
});

test('staff can view the detail of an enrollment they manage', function () {
    $instructor = User::factory()->instructor()->create();
    $enrollment = Enrollment::factory()
        ->for(Course::factory()->ownedBy($instructor)->published())
        ->enrolledBy($instructor)
        ->create();

    $this->actingAs($instructor)
        ->get(route('enrollments.show', $enrollment))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrollments/Show')
            ->where('enrollment.id', $enrollment->id)
            ->where('enrollment.is_self_enrolled', false)
            ->where('enrollment.enrolled_by.id', $instructor->id)
        );
});

test('instructors cannot view enrollments of other instructors courses', function () {
    $enrollment = Enrollment::factory()->create();

    $this->actingAs(User::factory()->instructor()->create())
        ->get(route('enrollments.show', $enrollment))
        ->assertForbidden();
});

test('an instructor can manually enroll a student in their own course', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->create();
    $student = User::factory()->student()->create();

    $this->actingAs($instructor)
        ->post(route('enrollments.store'), ['course_id' => $course->id, 'user_id' => $student->id])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $enrollment = Enrollment::sole();

    expect($enrollment->user_id)->toBe($student->id)
        ->and($enrollment->enrolled_by)->toBe($instructor->id)
        ->and($enrollment->isSelfEnrolled())->toBeFalse();
});

test('an instructor cannot manually enroll students in another instructors course', function () {
    $course = Course::factory()->published()->create();

    $this->actingAs(User::factory()->instructor()->create())
        ->post(route('enrollments.store'), [
            'course_id' => $course->id,
            'user_id' => User::factory()->student()->create()->id,
        ])
        ->assertSessionHasErrors('course_id');

    expect(Enrollment::query()->count())->toBe(0);
});

test('manual enrollment rejects invalid targets', function (Closure $payload, string $errorField) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('enrollments.store'), $payload())
        ->assertSessionHasErrors($errorField);

    expect(Enrollment::query()->where('enrolled_by', $admin->id)->count())->toBe(0);
})->with([
    'non student' => [fn () => [
        'course_id' => Course::factory()->published()->create()->id,
        'user_id' => User::factory()->instructor()->create()->id,
    ], 'user_id'],
    'archived course' => [fn () => [
        'course_id' => Course::factory()->archived()->create()->id,
        'user_id' => User::factory()->student()->create()->id,
    ], 'course_id'],
    'already enrolled' => [function () {
        $enrollment = Enrollment::factory()->create();

        return ['course_id' => $enrollment->course_id, 'user_id' => $enrollment->user_id];
    }, 'user_id'],
]);

test('students cannot manually enroll anyone', function () {
    $this->actingAs(User::factory()->student()->create())
        ->post(route('enrollments.store'), [
            'course_id' => Course::factory()->published()->create()->id,
            'user_id' => User::factory()->student()->create()->id,
        ])
        ->assertForbidden();
});

test('a student can cancel their own enrollment', function () {
    $student = User::factory()->student()->create();
    $enrollment = Enrollment::factory()->for($student)->create();

    $this->actingAs($student)
        ->patch(route('enrollments.cancel', $enrollment), ['reason' => 'Tidak ada waktu'])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $enrollment->refresh();

    expect($enrollment->status)->toBe(Enrollment::STATUS_CANCELLED)
        ->and($enrollment->cancelled_by)->toBe($student->id)
        ->and($enrollment->cancellation_reason)->toBe('Tidak ada waktu')
        ->and($enrollment->cancelled_at)->not->toBeNull();
});

test('the course instructor can cancel an enrollment', function () {
    $instructor = User::factory()->instructor()->create();
    $enrollment = Enrollment::factory()->for(Course::factory()->ownedBy($instructor)->published())->create();

    $this->actingAs($instructor)
        ->patch(route('enrollments.cancel', $enrollment))
        ->assertRedirect();

    expect($enrollment->refresh()->status)->toBe(Enrollment::STATUS_CANCELLED)
        ->and($enrollment->cancellation_reason)->toBeNull();
});

test('users cannot cancel enrollments they do not own or manage', function () {
    $enrollment = Enrollment::factory()->create();

    $this->actingAs(User::factory()->student()->create())
        ->patch(route('enrollments.cancel', $enrollment))
        ->assertForbidden();

    $this->actingAs(User::factory()->instructor()->create())
        ->patch(route('enrollments.cancel', $enrollment))
        ->assertForbidden();

    expect($enrollment->refresh()->isActive())->toBeTrue();
});

test('a cancelled enrollment cannot be cancelled again', function () {
    $student = User::factory()->student()->create();
    $enrollment = Enrollment::factory()->for($student)->cancelled()->create();

    $this->actingAs($student)
        ->patch(route('enrollments.cancel', $enrollment))
        ->assertForbidden();
});

test('course detail shows how many students are enrolled', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->published()->create();
    Enrollment::factory()->count(2)->for($course)->create();
    Enrollment::factory()->for($course)->cancelled()->create();

    $this->actingAs($instructor)
        ->get(route('courses.show', $course))
        ->assertInertia(fn ($page) => $page->where('course.students_count', 2));
});
