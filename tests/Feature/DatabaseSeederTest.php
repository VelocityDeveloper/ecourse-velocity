<?php

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\LessonQuestion;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('the database seeder creates demo data for every role and can be run twice', function () {
    Storage::fake(Course::THUMBNAIL_DISK);

    $this->seed();
    $this->seed();

    expect(User::query()->where('email', 'admin@ecourse.com')->value('role'))->toBe(User::ROLE_ADMIN)
        ->and(User::query()->where('role', User::ROLE_INSTRUCTOR)->count())->toBe(3)
        ->and(User::query()->where('role', User::ROLE_STUDENT)->count())->toBe(12)
        ->and(Course::query()->published()->count())->toBe(9)
        ->and(Course::query()->whereDoesntHave('sections')->count())->toBe(0)
        ->and(QuizQuestion::query()->distinct()->pluck('answer_mode')->sort()->values()->all())
        ->toBe([QuizQuestion::MODE_MULTIPLE, QuizQuestion::MODE_SINGLE, QuizQuestion::MODE_TRUE_FALSE]);

    $demoStudent = User::query()->where('email', 'student@ecourse.com')->sole();

    expect($demoStudent->enrollments()->active()->count())->toBe(4)
        ->and($demoStudent->enrollments()->where('status', Enrollment::STATUS_CANCELLED)->count())->toBe(1)
        ->and($demoStudent->enrollments()->whereNotNull('enrolled_by')->count())->toBe(1);

    expect(CourseReview::query()->count())->toBeGreaterThan(0)
        ->and(LessonQuestion::query()->count())->toBeGreaterThan(0)
        ->and($demoStudent->completedLessons()->count())->toBeGreaterThan(0)
        ->and($demoStudent->enrollments()->active()->whereNotNull('last_lesson_id')->exists())->toBeTrue();

    $this->post(route('login.store'), ['email' => 'student@ecourse.com', 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($demoStudent);
});
