<?php

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('staff can visit the dashboard', function (string $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get(route('dashboard'))
        ->assertOk();
})->with([User::ROLE_ADMIN, User::ROLE_INSTRUCTOR]);

test('students are sent to the public site instead of the dashboard', function () {
    $this->actingAs(User::factory()->student()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('home'));
});

test('students land on the public site after logging in', function () {
    $student = User::factory()->student()->create();

    $this->post(route('login.store'), [
        'email' => $student->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))->assertRedirect(route('home'));
});

test('admins see numbers for the whole platform', function () {
    $course = Course::factory()->published()->create();
    Enrollment::factory()->count(2)->for($course)->create(['enrolled_at' => now()]);
    CourseReview::factory()->for($course)->create(['rating' => 4]);
    Course::factory()->create(['status' => Course::STATUS_PENDING]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('isAdmin', true)
            ->where('stats.enrollments.month', 2)
            ->where('stats.students', 2)
            ->where('stats.rating.average', 4)
            ->has('stats.users')
            ->has('chart', 30)
            ->has('pendingCourses', 1)
            ->has('topCourses', 2)
            ->has('recentEnrollments', 2));
});

test('instructors only see numbers for their own courses', function () {
    $instructor = User::factory()->instructor()->create();
    $own = Course::factory()->published()->ownedBy($instructor)->create();
    Enrollment::factory()->for($own)->create(['enrolled_at' => now()]);
    Enrollment::factory()->count(3)->create(['enrolled_at' => now()]);

    $this->actingAs($instructor)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('isAdmin', false)
            ->where('stats.enrollments.month', 1)
            ->where('stats.users', null)
            ->has('topCourses', 1)
            ->where('topCourses.0.slug', $own->slug)
            ->has('recentEnrollments', 1)
            ->where('chart.29.enrollments', 1));
});
