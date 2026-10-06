<?php

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Order;
use App\Models\Section;
use App\Models\Transaction;
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
        ...captcha(),
        'email' => $student->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))->assertRedirect(route('home'));
});

/**
 * A confirmed sale of the course, with the platform keeping 10%.
 */
function dashboardSale(Course $course, int $amount): void
{
    $order = Order::factory()->create([
        'course_id' => $course->id,
        'course_title' => $course->title,
        'price' => $amount,
        'total' => $amount,
        'status' => Order::STATUS_PAID,
        'paid_at' => now(),
    ]);

    Transaction::query()->create([
        'order_id' => $order->id,
        'user_id' => $order->user_id,
        'amount' => $amount,
        ...Transaction::split($amount, 10),
        'payment_method' => Order::METHOD_BANK_TRANSFER,
        'paid_at' => now(),
    ]);
}

test('admins get the platform dashboard', function () {
    $course = Course::factory()->published()->create(['price' => 100000]);
    Enrollment::factory()->count(2)->for($course)->create(['enrolled_at' => now()]);
    Enrollment::factory()->for($course)->create(['enrolled_at' => now()->subDays(40)]);
    CourseReview::factory()->for($course)->create(['rating' => 4]);
    Course::factory()->create(['status' => Course::STATUS_PENDING]);
    dashboardSale($course, 100000);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Admin')
            ->where('period.key', '30h')
            ->where('kpis.enrollments.current', 2)
            ->where('kpis.enrollments.previous', 1)
            ->where('kpis.revenue.current', 100000)
            ->where('kpis.commission.current', 10000)
            ->where('actions.pending_courses', 1)
            ->where('finance.instructor_share', 90000)
            ->where('finance.owed', 90000)
            ->where('platform.rating.average', 4)
            ->has('series', 30)
            ->where('topCourses.0.slug', $course->slug)
            ->where('topCourses.0.revenue', 100000)
            ->where('topInstructors.0.id', $course->instructor_id)
            ->has('recentPayments', 1));
});

test('instructors get a dashboard of their own courses and earnings', function () {
    $instructor = User::factory()->instructor()->create();
    $own = Course::factory()->published()->ownedBy($instructor)->create();
    Enrollment::factory()->for($own)->create(['enrolled_at' => now()]);
    Enrollment::factory()->count(3)->create(['enrolled_at' => now()]);
    dashboardSale($own, 100000);
    dashboardSale(Course::factory()->published()->create(), 50000);

    $this->actingAs($instructor)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Instructor')
            ->where('kpis.enrollments.current', 1)
            ->where('kpis.revenue.current', 90000)
            ->where('balance.available', 90000)
            ->has('courses', 1)
            ->where('courses.0.slug', $own->slug)
            ->where('courses.0.students', 1)
            ->where('courses.0.revenue', 90000)
            ->where('series.29.enrollments', 1)
            ->missing('finance')
            ->missing('topInstructors'));
});

test('the dashboard period can be changed and falls back to thirty days', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard', ['periode' => '7h']))
        ->assertInertia(fn ($page) => $page->where('period.key', '7h')->has('series', 7));

    $this->actingAs($admin)
        ->get(route('dashboard', ['periode' => '12b']))
        ->assertInertia(fn ($page) => $page
            ->where('period.monthly', true)
            ->has('series', 12)
            ->where('series.11.bucket', now()->format('Y-m')));

    $this->actingAs($admin)
        ->get(route('dashboard', ['periode' => 'semua']))
        ->assertInertia(fn ($page) => $page->where('period.key', '30h')->has('series', 30));
});

test('the course table shows the average progress of active students', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->published()->ownedBy($instructor)->create();
    $section = Section::factory()->for($course)->create();
    $lessons = Lesson::factory()->count(4)->for($section)->create();
    $students = User::factory()->student()->count(2)->create();

    foreach ($students as $student) {
        Enrollment::enroll($student, $course);
    }

    // One student finished all four lessons, the other one: 5 of 8 = 63%.
    foreach ($lessons as $lesson) {
        $students[0]->completedLessons()->attach($lesson);
    }
    $students[1]->completedLessons()->attach($lessons[0]);

    $this->actingAs($instructor)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('courses.0.average_progress', 63)
            ->where('learning.lessons_completed.current', 5));
});
