<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

test('a student can add and remove a course from the wishlist', function () {
    $course = Course::factory()->published()->create();
    $student = User::factory()->student()->create();

    $this->actingAs($student)->post(route('catalog.wishlist.store', $course))->assertRedirect();
    $this->actingAs($student)->post(route('catalog.wishlist.store', $course));

    expect($student->wishlistedCourses()->count())->toBe(1);

    $this->actingAs($student)
        ->get(route('catalog.show', $course->permalinkParameters()))
        ->assertInertia(fn ($page) => $page->where('wishlistCourseIds', [$course->id]));

    $this->actingAs($student)
        ->get(route('learning.wishlist'))
        ->assertInertia(fn ($page) => $page
            ->component('learning/Wishlist')
            ->has('courses', 1)
            ->where('courses.0.id', $course->id)
            ->whereType('courses.0.wishlisted_at', 'string'));

    $this->actingAs($student)->delete(route('catalog.wishlist.destroy', $course))->assertRedirect();

    expect($student->wishlistedCourses()->count())->toBe(0);
});

test('guests are sent to log in before using the wishlist', function () {
    $course = Course::factory()->published()->create();

    $this->post(route('catalog.wishlist.store', $course))->assertRedirect(route('login'));
    $this->get(route('learning.wishlist'))->assertRedirect(route('login'));
});

test('admins, own courses and unpublished courses cannot be wishlisted', function () {
    $instructor = User::factory()->instructor()->create();
    $published = Course::factory()->published()->create();
    $own = Course::factory()->published()->ownedBy($instructor)->create();
    $draft = Course::factory()->create();
    $student = User::factory()->student()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('catalog.wishlist.store', $published))
        ->assertForbidden();

    $this->actingAs($instructor)
        ->post(route('catalog.wishlist.store', $own))
        ->assertForbidden();

    $this->actingAs($instructor)
        ->post(route('catalog.wishlist.store', $published))
        ->assertRedirect();

    expect($instructor->wishlistedCourses()->count())->toBe(1);

    $this->actingAs($student)
        ->post(route('catalog.wishlist.store', $draft))
        ->assertForbidden();

    expect($student->wishlistedCourses()->count())->toBe(0);
});

test('the wishlist hides courses that are no longer published', function () {
    $course = Course::factory()->published()->create();
    $student = User::factory()->student()->create();
    $student->wishlistedCourses()->attach($course->id);

    $course->update(['status' => Course::STATUS_ARCHIVED]);

    $this->actingAs($student)
        ->get(route('learning.wishlist'))
        ->assertInertia(fn ($page) => $page->has('courses', 0));
});

test('enrolling takes the course off the wishlist', function () {
    $course = Course::factory()->published()->create();
    $other = Course::factory()->published()->create();
    $student = User::factory()->student()->create();
    $student->wishlistedCourses()->attach([$course->id, $other->id]);

    Enrollment::enroll($student, $course);

    expect($student->wishlistedCourses()->pluck('courses.id')->all())->toBe([$other->id]);
});
