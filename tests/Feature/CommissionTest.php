<?php

use App\Models\Course;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Support\PaymentSettings;

function confirmSale(User $admin, Course $course, int $price): Transaction
{
    $order = Order::factory()->for($course)->awaitingConfirmation()->create([
        'course_title' => $course->title,
        'price' => $price,
        'total' => $price,
    ]);

    test()->actingAs($admin)->post(route('admin.orders.confirm', $order))->assertRedirect();

    return $order->refresh()->transaction;
}

test('the commission splits a sale, rounding to whole rupiah', function () {
    expect(Transaction::split(150000, 12.5))->toBe(['commission_rate' => '12.50', 'commission_amount' => 18750, 'instructor_amount' => 131250])
        ->and(Transaction::split(99999, 10))->toBe(['commission_rate' => '10.00', 'commission_amount' => 10000, 'instructor_amount' => 89999])
        ->and(Transaction::split(50000, 0)['instructor_amount'])->toBe(50000);
});

test('admins set the commission, within 0 to 100 percent', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.payment-settings.update'), ['expiry_hours' => 24, 'commission_rate' => '15.5'])
        ->assertSessionHasNoErrors();

    expect(PaymentSettings::commissionRate())->toBe(15.5);

    $this->actingAs($admin)
        ->post(route('admin.payment-settings.update'), ['expiry_hours' => 24, 'commission_rate' => 120])
        ->assertSessionHasErrors('commission_rate');

    $this->actingAs($admin)
        ->get(route('admin.payment-settings.edit'))
        ->assertInertia(fn ($page) => $page->where('commissionRate', 15.5));
});

test('instructors cannot change the commission', function () {
    $this->actingAs(User::factory()->instructor()->create())
        ->post(route('admin.payment-settings.update'), ['expiry_hours' => 24, 'commission_rate' => 0])
        ->assertForbidden();
});

test('a confirmed sale keeps the commission in force at the time', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->published()->create(['price' => 200000]);

    SiteSetting::put(PaymentSettings::COMMISSION_RATE, '20.00');
    $first = confirmSale($admin, $course, 200000);

    SiteSetting::put(PaymentSettings::COMMISSION_RATE, '10.00');
    $second = confirmSale($admin, $course, 200000);

    expect($first->refresh()->commission_amount)->toBe(40000)
        ->and($first->instructor_amount)->toBe(160000)
        ->and($second->commission_amount)->toBe(20000)
        ->and($second->instructor_amount)->toBe(180000);

    // Admins count what students paid; the instructor counts their share.
    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertInertia(fn ($page) => $page
            ->where('paid.amount', 400000)
            ->where('paid.commission', 60000)
            ->where('paid.instructor', 340000)
        );

    $this->actingAs($instructor)
        ->get(route('admin.orders.index'))
        ->assertInertia(fn ($page) => $page->where('paid.instructor', 340000));

    $this->actingAs($instructor)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('stats.revenue.total', 340000));

    $this->actingAs($admin)
        ->get(route('admin.finance.instructors.show', $instructor->slug))
        ->assertInertia(fn ($page) => $page
            ->where('instructor.gross', 400000)
            ->where('instructor.commission', 60000)
            ->where('instructor.revenue', 340000)
            ->where('courses.0.revenue', 340000)
        );
});

test('admins can charge the commission on past sales that had none', function () {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->published()->create(['price' => 100000]);

    $old = confirmSale($admin, $course, 100000);
    SiteSetting::put(PaymentSettings::COMMISSION_RATE, '5.00');
    $charged = confirmSale($admin, $course, 100000);

    $this->actingAs($admin)
        ->get(route('admin.payment-settings.edit'))
        ->assertInertia(fn ($page) => $page->where('uncommissionedCount', 1));

    $this->actingAs($admin)
        ->post(route('admin.payment-settings.update'), ['expiry_hours' => 24, 'commission_rate' => 20, 'apply_to_past' => true])
        ->assertSessionHasNoErrors();

    // The sale without commission gets today's 20%; the one already charged 5% keeps it.
    expect($old->refresh()->commission_amount)->toBe(20000)
        ->and($old->instructor_amount)->toBe(80000)
        ->and($charged->refresh()->commission_amount)->toBe(5000);

    $paidAt = $old->paid_at->toDateTimeString();
    $this->actingAs($admin)
        ->post(route('admin.payment-settings.update'), ['expiry_hours' => 24, 'commission_rate' => 30, 'apply_to_past' => false]);

    expect($old->refresh()->commission_amount)->toBe(20000)
        ->and($old->paid_at->toDateTimeString())->toBe($paidAt);
});
