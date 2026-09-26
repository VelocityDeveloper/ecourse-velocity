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

    $this->get(route('catalog.show', $course))
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
