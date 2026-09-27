<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

test('the catalog lists only published courses', function () {
    $published = Course::factory()->published()->create();
    Course::factory()->create();
    Course::factory()->archived()->create();

    $this->actingAs(User::factory()->student()->create())
        ->get(route('catalog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('catalog/Index')
            ->has('courses.data', 1)
            ->where('courses.data.0.id', $published->id)
            ->where('courses.data.0.is_enrolled', false)
        );
});

test('the catalog flags the courses the student is enrolled in', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->published()->create();
    Enrollment::factory()->for($student)->for($course)->create();

    $this->actingAs($student)
        ->get(route('catalog.index'))
        ->assertInertia(fn ($page) => $page
            ->where('courses.data.0.is_enrolled', true)
            ->where('courses.data.0.students_count', 1)
        );
});

test('the catalog can be searched and filtered by level', function () {
    Course::factory()->published()->create(['title' => 'Laravel Dasar', 'level' => 'beginner']);
    Course::factory()->published()->create(['title' => 'Laravel Lanjutan', 'level' => 'advanced']);
    Course::factory()->published()->create(['title' => 'Vue Dasar', 'level' => 'beginner']);

    $this->actingAs(User::factory()->student()->create())
        ->get(route('catalog.index', ['search' => 'Laravel', 'level' => 'beginner']))
        ->assertInertia(fn ($page) => $page
            ->has('courses.data', 1)
            ->where('courses.data.0.title', 'Laravel Dasar')
        );
});

test('students cannot open an unpublished course in the catalog', function () {
    $course = Course::factory()->create();

    $this->actingAs(User::factory()->student()->create())
        ->get(route('catalog.show', $course))
        ->assertForbidden();
});

test('an enrolled student keeps seeing a course after it is archived', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->archived()->create();
    Enrollment::factory()->for($student)->for($course)->create();

    $this->actingAs($student)
        ->get(route('catalog.show', $course))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('catalog/Show')
            ->where('enrollment.status', Enrollment::STATUS_ACTIVE)
            ->where('can.enroll', false)
            ->where('can.cancel', true)
        );
});

test('a student can enroll in a published course', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->published()->create(['price' => 0]);

    $this->actingAs($student)
        ->post(route('catalog.enroll', $course))
        ->assertRedirect(route('catalog.show', $course));

    $enrollment = Enrollment::sole();

    expect($enrollment->user_id)->toBe($student->id)
        ->and($enrollment->course_id)->toBe($course->id)
        ->and($enrollment->isActive())->toBeTrue()
        ->and($enrollment->isSelfEnrolled())->toBeTrue();
});

test('enrolling twice keeps a single enrollment', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->published()->create(['price' => 0]);

    $this->actingAs($student)->post(route('catalog.enroll', $course));
    $this->actingAs($student)->post(route('catalog.enroll', $course));

    expect(Enrollment::query()->count())->toBe(1);
});

test('enrolling again reactivates a cancelled enrollment', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->published()->create(['price' => 0]);
    $enrollment = Enrollment::factory()->for($student)->for($course)->cancelled('Sibuk')->create();

    $this->actingAs($student)
        ->post(route('catalog.enroll', $course))
        ->assertRedirect();

    $enrollment->refresh();

    expect(Enrollment::query()->count())->toBe(1)
        ->and($enrollment->isActive())->toBeTrue()
        ->and($enrollment->cancelled_at)->toBeNull()
        ->and($enrollment->cancellation_reason)->toBeNull();
});

test('students cannot enroll in an unpublished course', function () {
    $course = Course::factory()->create();

    $this->actingAs(User::factory()->student()->create())
        ->post(route('catalog.enroll', $course))
        ->assertForbidden();

    expect(Enrollment::query()->count())->toBe(0);
});

test('staff cannot self enroll', function () {
    $course = Course::factory()->published()->create();

    $this->actingAs(User::factory()->instructor()->create())
        ->post(route('catalog.enroll', $course))
        ->assertForbidden();

    expect(Enrollment::query()->count())->toBe(0);
});

test('my courses lists only active enrollments', function () {
    $student = User::factory()->student()->create();
    $active = Enrollment::factory()->for($student)->create();
    Enrollment::factory()->for($student)->cancelled()->create();
    Enrollment::factory()->create();

    $this->actingAs($student)
        ->get(route('my-courses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('my-courses/Index')
            ->has('enrollments', 1)
            ->where('enrollments.0.id', $active->id)
        );
});

test('guests can browse the catalog', function () {
    $course = Course::factory()->published()->create();

    $this->get(route('catalog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('courses.data', 1)
            ->where('courses.data.0.is_enrolled', false)
        );

    $this->get(route('catalog.show', $course))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('catalog/Show')
            ->where('enrollment', null)
            ->where('can.enroll', false)
            ->where('can.manage', false)
        );
});

test('guests cannot see unpublished courses', function () {
    $this->get(route('catalog.show', Course::factory()->create()))->assertForbidden();
});

test('guests must log in before enrolling', function () {
    $course = Course::factory()->published()->create();

    $this->post(route('catalog.enroll', $course))->assertRedirect(route('login'));

    expect(Enrollment::query()->count())->toBe(0);
});

test('a guest returns to the course after logging in', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->published()->create();

    $this->get(route('catalog.show', $course))->assertOk();

    $this->post(route('login.store'), [
        'email' => $student->email,
        'password' => 'password',
    ])->assertRedirect(route('catalog.show', $course));
});

test('course managers get a link back to the dashboard from the catalog', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->published()->create();

    $this->actingAs($instructor)
        ->get(route('catalog.show', $course))
        ->assertInertia(fn ($page) => $page->where('can.manage', true));
});

test('the catalogue can be sorted and shows lesson counts on each card', function () {
    $cheap = Course::factory()->published()->create(['price' => '10000', 'title' => 'Murah']);
    $pricey = Course::factory()->published()->create(['price' => '90000', 'title' => 'Mahal']);
    Enrollment::factory()->count(3)->for($pricey)->create();

    $this->get(route('catalog.index', ['sort' => 'termurah']))
        ->assertInertia(fn ($page) => $page
            ->where('filters.sort', 'termurah')
            ->where('courses.data.0.title', 'Murah')
            ->has('courses.data.0.lessons_count')
            ->where('total', 2));

    $this->get(route('catalog.index', ['sort' => 'populer']))
        ->assertInertia(fn ($page) => $page->where('courses.data.0.title', 'Mahal'));

    $this->get(route('catalog.index', ['sort' => 'sembarang']))
        ->assertInertia(fn ($page) => $page->where('filters.sort', 'terbaru'));
});

test('a course page suggests other published courses from the same category', function () {
    $category = Category::factory()->create();
    $course = Course::factory()->published()->create(['category_id' => $category->id]);
    $sibling = Course::factory()->published()->create(['category_id' => $category->id]);
    Course::factory()->create(['category_id' => $category->id]);
    Course::factory()->published()->create();

    $this->get(route('catalog.show', $course))
        ->assertInertia(fn ($page) => $page
            ->has('related', 1)
            ->where('related.0.id', $sibling->id)
            ->has('course.total_minutes'));
});
