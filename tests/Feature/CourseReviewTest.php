<?php

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\User;

function reviewerFor(Course $course): User
{
    $student = User::factory()->student()->create();
    Enrollment::factory()->for($student)->for($course)->create();

    return $student;
}

test('an enrolled student can rate a course and update their review', function () {
    $course = Course::factory()->published()->create();
    $student = reviewerFor($course);

    $this->actingAs($student)
        ->put(route('catalog.review.update', $course), ['rating' => 4, 'comment' => 'Materinya jelas.'])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $this->actingAs($student)
        ->put(route('catalog.review.update', $course), ['rating' => 5, 'comment' => '']);

    $review = CourseReview::query()->sole();

    expect($review->rating)->toBe(5)
        ->and($review->comment)->toBeNull()
        ->and($review->user_id)->toBe($student->id);
});

test('ratings must be between one and five stars', function (int $rating) {
    $course = Course::factory()->published()->create();

    $this->actingAs(reviewerFor($course))
        ->put(route('catalog.review.update', $course), ['rating' => $rating])
        ->assertSessionHasErrors('rating');

    expect(CourseReview::query()->count())->toBe(0);
})->with([0, 6]);

test('only students with an active enrollment can review', function (Closure $makeUser) {
    $course = Course::factory()->published()->create();

    $this->actingAs($makeUser($course))
        ->put(route('catalog.review.update', $course), ['rating' => 5])
        ->assertForbidden();

    expect(CourseReview::query()->count())->toBe(0);
})->with([
    'not enrolled' => [fn () => User::factory()->student()->create()],
    'cancelled enrollment' => [function (Course $course) {
        $student = User::factory()->student()->create();
        Enrollment::factory()->for($student)->for($course)->cancelled()->create();

        return $student;
    }],
    'course instructor' => [fn (Course $course) => $course->instructor],
]);

test('the catalog shows the rating summary and written reviews', function () {
    $course = Course::factory()->published()->create();
    CourseReview::factory()->for($course)->create(['rating' => 5, 'comment' => 'Mantap']);
    CourseReview::factory()->for($course)->create(['rating' => 4, 'comment' => null]);
    CourseReview::factory()->for($course)->create(['rating' => 3, 'comment' => 'Cukup']);

    $this->get(route('catalog.show', $course->permalinkParameters()))
        ->assertInertia(fn ($page) => $page
            ->where('rating.count', 3)
            ->where('rating.average', 4)
            ->where('rating.distribution.5', 1)
            ->where('rating.distribution.1', 0)
            ->has('reviews', 2)
            ->where('myReview', null)
            ->where('can.review', false)
        );

    $this->get(route('catalog.index'))
        ->assertInertia(fn ($page) => $page
            ->where('courses.data.0.reviews_count', 3)
            ->where('courses.data.0.rating_average', 4)
        );
});

test('students can remove their own review and admins can remove any', function () {
    $course = Course::factory()->published()->create();
    $student = reviewerFor($course);
    $own = CourseReview::factory()->for($course)->for($student)->create();
    $other = CourseReview::factory()->for($course)->create();

    $this->actingAs($student)->delete(route('reviews.destroy', $other))->assertForbidden();
    $this->actingAs($student)->delete(route('reviews.destroy', $own))->assertRedirect();
    $this->actingAs(User::factory()->admin()->create())->delete(route('reviews.destroy', $other))->assertRedirect();

    expect(CourseReview::query()->count())->toBe(0);
});

test('admins see and can remove the reviews of any course', function () {
    $course = Course::factory()->create();
    CourseReview::factory()->for($course)->count(2)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('courses.reviews.index', $course))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('reviews.data', 2)
            ->where('summary.count', 2)
            ->where('reviews.data.0.can_delete', true));
});

test('instructors cannot remove reviews of their own courses', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->create();
    CourseReview::factory()->for($course)->create(['rating' => 5]);

    $this->actingAs($instructor)
        ->get(route('courses.reviews.index', $course))
        ->assertInertia(fn ($page) => $page
            ->where('reviews.data.0.can_delete', false)
            ->where('summary.average', 5));
});

test('course reviews can be filtered by stars', function () {
    $course = Course::factory()->create();
    CourseReview::factory()->for($course)->create(['rating' => 5]);
    CourseReview::factory()->for($course)->create(['rating' => 2]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('courses.reviews.index', ['course' => $course, 'rating' => 5]))
        ->assertInertia(fn ($page) => $page
            ->has('reviews.data', 1)
            ->where('summary.count', 2));
});

test('students are sent away from the dashboard reviews', function () {
    $this->actingAs(User::factory()->student()->create())
        ->get(route('courses.reviews.index', Course::factory()->create()))
        ->assertRedirect(route('home'));
});

test('there is no combined reviews page in the dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/dasbor/ulasan')
        ->assertNotFound();
});

test('a course has its own reviews page in the dashboard', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->create();
    CourseReview::factory()->for($course)->count(2)->create();
    CourseReview::factory()->create();

    $this->actingAs($instructor)
        ->get(route('courses.reviews.index', $course))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('courses/Reviews')
            ->where('course.slug', $course->slug)
            ->has('reviews.data', 2)
            ->where('summary.count', 2));

    $this->actingAs(User::factory()->instructor()->create())
        ->get(route('courses.reviews.index', $course))
        ->assertForbidden();
});
