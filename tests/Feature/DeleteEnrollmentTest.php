<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Order;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * A paid course the student bought and enrolled in.
 *
 * @return array{0: User, 1: Course, 2: Enrollment, 3: Order}
 */
function boughtCourse(): array
{
    $student = User::factory()->student()->create();
    $course = Course::factory()->published()->create(['price' => 150000]);
    $order = Order::factory()->for($student)->for($course)->create([
        'status' => Order::STATUS_PAID,
        'paid_at' => now(),
    ]);
    $enrollment = Enrollment::enroll($student, $course);

    return [$student, $course, $enrollment, $order];
}

test('an admin can delete an enrollment so the student has to pay again', function () {
    [$student, $course, $enrollment, $order] = boughtCourse();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('enrollments.destroy', $enrollment))
        ->assertRedirect(route('enrollments.index'));

    expect(Enrollment::query()->whereKey($enrollment->id)->exists())->toBeFalse()
        ->and($order->refresh()->status)->toBe(Order::STATUS_PAID)
        ->and($order->access_revoked_at)->not->toBeNull();

    // Enrolling for free is refused again, and the course can be bought anew.
    $this->actingAs($student)->post(route('catalog.enroll', $course));
    expect($course->enrollments()->count())->toBe(0);

    $this->actingAs($student)
        ->get(route('orders.checkout', $course))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('orders/Checkout'));
});

test('a cancelled enrollment of a paid course can still be reactivated for free', function () {
    [$student, $course, $enrollment] = boughtCourse();

    $enrollment->cancel(User::factory()->admin()->create());

    $this->actingAs($student)->post(route('catalog.enroll', $course));

    expect($enrollment->refresh()->isActive())->toBeTrue();
});

test('deleting an enrollment removes the students progress in that course only', function () {
    [$student, $course, $enrollment] = boughtCourse();
    $section = Section::factory()->for($course)->create();
    $lesson = Lesson::factory()->for($section)->create();
    $quiz = Quiz::factory()->for($section)->create();
    $otherLesson = Lesson::factory()->create();

    foreach (['lesson_completions', 'lesson_bookmarks'] as $table) {
        DB::table($table)->insert([
            ['user_id' => $student->id, 'lesson_id' => $lesson->id, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $student->id, 'lesson_id' => $otherLesson->id, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
    QuizAttempt::factory()->for($student)->for($quiz)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('enrollments.destroy', $enrollment));

    expect(DB::table('lesson_completions')->where('user_id', $student->id)->pluck('lesson_id')->all())->toBe([$otherLesson->id])
        ->and(DB::table('lesson_bookmarks')->where('user_id', $student->id)->pluck('lesson_id')->all())->toBe([$otherLesson->id])
        ->and(QuizAttempt::query()->where('user_id', $student->id)->count())->toBe(0);
});

test('only admins can delete an enrollment', function () {
    [$student, $course, $enrollment] = boughtCourse();

    $this->actingAs($course->instructor)
        ->delete(route('enrollments.destroy', $enrollment))
        ->assertForbidden();

    $this->actingAs($student)
        ->delete(route('enrollments.destroy', $enrollment))
        ->assertForbidden();

    expect(Enrollment::query()->whereKey($enrollment->id)->exists())->toBeTrue();
});

test('the enrollment pages tell only admins they may delete', function () {
    [, $course, $enrollment] = boughtCourse();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('enrollments.show', $enrollment))
        ->assertInertia(fn ($page) => $page->where('can.delete', true));

    $this->actingAs($course->instructor)
        ->get(route('enrollments.index'))
        ->assertInertia(fn ($page) => $page->where('learners.data.0.enrollments.0.can_delete', false));
});
