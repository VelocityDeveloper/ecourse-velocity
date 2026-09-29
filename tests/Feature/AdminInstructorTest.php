<?php

use App\Models\Course;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;

function paidTransaction(Course $course, int $amount, string $paidAt): Transaction
{
    $order = Order::factory()->create([
        'course_id' => $course->id,
        'course_title' => $course->title,
        'price' => $amount,
        'total' => $amount,
        'status' => Order::STATUS_PAID,
        'paid_at' => $paidAt,
    ]);

    return Transaction::query()->create([
        'order_id' => $order->id,
        'user_id' => $order->user_id,
        'amount' => $amount,
        ...Transaction::split($amount, 0),
        'payment_method' => Order::METHOD_BANK_TRANSFER,
        'paid_at' => $paidAt,
    ]);
}

test('only admins can open the instructor list', function () {
    $this->actingAs(User::factory()->instructor()->create())
        ->get(route('admin.finance.instructors.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.finance.instructors.index'))
        ->assertOk();
});

test('the list shows each instructor revenue, filtered by period and sales', function () {
    $sari = User::factory()->instructor()->create(['name' => 'Sari Wulandari']);
    $rizky = User::factory()->instructor()->create(['name' => 'Rizky Pratama']);
    User::factory()->instructor()->create(['name' => 'Dimas Aditya']);

    $sariCourse = Course::factory()->published()->ownedBy($sari)->create();
    $rizkyCourse = Course::factory()->published()->ownedBy($rizky)->create();

    paidTransaction($sariCourse, 100000, '2026-08-10 10:00:00');
    paidTransaction($sariCourse, 50000, '2026-09-05 10:00:00');
    paidTransaction($rizkyCourse, 300000, '2026-08-20 10:00:00');

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.finance.instructors.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/Finance/Instructors/Index')
            ->has('instructors.data', 3)
            ->where('instructors.data.0.name', 'Rizky Pratama')
            ->where('instructors.data.0.revenue', 300000)
            ->where('instructors.data.1.revenue', 150000)
            ->where('instructors.data.1.sales_count', 2)
            ->where('summary.revenue', 450000)
            ->where('summary.with_sales', 2)
        );

    $this->actingAs($admin)
        ->get(route('admin.finance.instructors.index', ['from' => '2026-09-01', 'to' => '2026-09-30']))
        ->assertInertia(fn ($page) => $page
            ->where('instructors.data.0.name', 'Sari Wulandari')
            ->where('instructors.data.0.revenue', 50000)
            ->where('instructors.data.0.revenue_all_time', 150000)
            ->where('summary.revenue', 50000)
        );

    $this->actingAs($admin)
        ->get(route('admin.finance.instructors.index', ['sales' => 'without_sales']))
        ->assertInertia(fn ($page) => $page
            ->has('instructors.data', 1)
            ->where('instructors.data.0.name', 'Dimas Aditya')
            ->where('instructors.total', 1)
        );

    $this->actingAs($admin)
        ->get(route('admin.finance.instructors.index', ['search' => 'rizky']))
        ->assertInertia(fn ($page) => $page->has('instructors.data', 1));
});

test('an instructor page breaks revenue down per course and month', function () {
    $sari = User::factory()->instructor()->create();
    $laravel = Course::factory()->published()->ownedBy($sari)->create(['title' => 'Laravel']);
    Course::factory()->ownedBy($sari)->create(['title' => 'Vue']);

    paidTransaction($laravel, 120000, now()->toDateTimeString());

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.finance.instructors.show', $sari->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Finance/Instructors/Show')
            ->where('instructor.revenue', 120000)
            ->has('courses', 2)
            ->where('courses.0.title', 'Laravel')
            ->where('courses.0.revenue', 120000)
            ->where('courses.1.revenue', 0)
            ->has('monthly', 12)
            ->where('monthly.11.revenue', 120000)
            ->has('transactions', 1)
        );
});

test('the list shows today, this month, withdrawals and the balance', function () {
    $this->travelTo('2026-09-15 12:00:00');

    $sari = User::factory()->instructor()->create();
    $course = Course::factory()->published()->ownedBy($sari)->create();

    paidTransaction($course, 40000, '2026-09-15 08:00:00');
    paidTransaction($course, 60000, '2026-09-03 08:00:00');
    paidTransaction($course, 200000, '2026-08-01 08:00:00');

    Withdrawal::factory()->for($sari)->create(['amount' => 120000, 'status' => Withdrawal::STATUS_PAID]);
    Withdrawal::factory()->for($sari)->create(['amount' => 50000, 'status' => Withdrawal::STATUS_PENDING]);
    Withdrawal::factory()->for($sari)->create(['amount' => 90000, 'status' => Withdrawal::STATUS_REJECTED]);

    $admin = User::factory()->admin()->create();

    $expect = fn ($page) => $page
        ->where('revenue_today', 40000)
        ->where('revenue_this_month', 100000)
        ->where('withdrawn', 120000)
        ->where('withdrawal_pending', 50000)
        ->where('balance', 130000)
        ->etc();

    $this->actingAs($admin)
        ->get(route('admin.finance.instructors.index'))
        ->assertInertia(fn ($page) => $page->has('instructors.data.0', $expect));

    $this->actingAs($admin)
        ->get(route('admin.finance.instructors.show', $sari))
        ->assertInertia(fn ($page) => $page->has('instructor', $expect));
});

test('a non-instructor has no instructor page', function () {
    $student = User::factory()->student()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.finance.instructors.show', $student->slug))
        ->assertNotFound();
});

test('the instructor list downloads as CSV', function () {
    $sari = User::factory()->instructor()->create(['name' => 'Sari Wulandari']);
    paidTransaction(Course::factory()->ownedBy($sari)->create(), 75000, now()->toDateTimeString());

    $response = $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.finance.instructors.export'));

    $response->assertOk();
    expect($response->streamedContent())->toContain('Sari Wulandari')->toContain('75000');
});
