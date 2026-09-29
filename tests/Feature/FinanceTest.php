<?php

use App\Models\Course;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;

function financeSale(Course $course, int $amount, int $commissionRate, string $paidAt): void
{
    $order = Order::factory()->create([
        'course_id' => $course->id,
        'course_title' => $course->title,
        'price' => $amount,
        'total' => $amount,
        'status' => Order::STATUS_PAID,
        'paid_at' => $paidAt,
    ]);

    Transaction::query()->create([
        'order_id' => $order->id,
        'user_id' => $order->user_id,
        'amount' => $amount,
        ...Transaction::split($amount, $commissionRate),
        'payment_method' => Order::METHOD_BANK_TRANSFER,
        'paid_at' => $paidAt,
    ]);
}

test('the finance overview sums sales, balances and what waits for the admin', function () {
    $this->travelTo('2026-09-15 12:00:00');

    $sari = User::factory()->instructor()->create(['name' => 'Sari']);
    $rizky = User::factory()->instructor()->create(['name' => 'Rizky']);
    $sariCourse = Course::factory()->published()->ownedBy($sari)->create();
    $rizkyCourse = Course::factory()->published()->ownedBy($rizky)->create();

    financeSale($sariCourse, 100000, 10, '2026-09-15 09:00:00');
    financeSale($rizkyCourse, 200000, 10, '2026-09-02 09:00:00');
    financeSale($rizkyCourse, 50000, 10, '2026-08-20 09:00:00');

    // Sari earned 90.000; Rizky 225.000, of which 150.000 paid out and 100.000 waiting.
    // Rizky's balance would be negative, so it counts as zero.
    Withdrawal::factory()->for($rizky)->create(['amount' => 150000, 'status' => Withdrawal::STATUS_PAID]);
    Withdrawal::factory()->for($rizky)->create(['amount' => 100000]);
    Order::factory()->create(['status' => Order::STATUS_AWAITING_CONFIRMATION, 'total' => 75000]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.finance.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Finance/Index')
            ->where('sales.today', 100000)
            ->where('sales.today_count', 1)
            ->where('sales.month', 300000)
            ->where('sales.commission_month', 30000)
            ->where('sales.instructor_month', 270000)
            ->where('instructors.balance', 90000)
            ->where('instructors.paid_out', 150000)
            ->where('waiting.withdrawals_count', 1)
            ->where('waiting.withdrawals_total', 100000)
            ->where('waiting.orders_count', 1)
            ->where('waiting.orders_total', 75000)
            ->has('waitingOrders', 1)
            ->where('waitingWithdrawals.0.instructor', 'Rizky')
            ->where('pendingOrders', 1)
            ->where('pendingWithdrawals', 1)
        );
});

test('an instructor finance page opens on their balance', function () {
    $this->actingAs(User::factory()->instructor()->create())
        ->get(route('admin.finance.index'))
        ->assertRedirect(route('admin.withdrawals.index'));

    $this->actingAs(User::factory()->instructor()->create())
        ->get(route('admin.finance.instructors.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->student()->create())
        ->get(route('admin.finance.index'))
        ->assertRedirect();
});

test('the instructor list shows people without money', function () {
    $sari = User::factory()->instructor()->create(['name' => 'Sari Wulandari']);
    User::factory()->instructor()->create(['name' => 'Budi Santoso']);
    financeSale(Course::factory()->published()->ownedBy($sari)->create(), 100000, 0, '2026-09-01 09:00:00');

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.instructors.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Instructors/Index')
            ->has('instructors.data', 2)
            ->where('instructors.data.0.name', 'Budi Santoso')
            ->where('instructors.data.1.published_courses_count', 1)
            ->missing('instructors.data.1.revenue')
            ->missing('instructors.data.1.balance')
        );

    $this->actingAs($admin)
        ->get(route('admin.instructors.index', ['search' => 'sari']))
        ->assertInertia(fn ($page) => $page
            ->has('instructors.data', 1)
            ->where('instructors.data.0.name', 'Sari Wulandari')
        );

    $this->actingAs(User::factory()->instructor()->create())
        ->get(route('admin.instructors.index'))
        ->assertForbidden();
});
