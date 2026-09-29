<?php

use App\Models\Course;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Support\PaymentSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(Order::PROOF_DISK);
    Storage::fake(PaymentSettings::QRIS_DISK);

    SiteSetting::put(PaymentSettings::BANK_ACCOUNTS, json_encode([
        ['bank' => 'BCA', 'account_number' => '1234567890', 'account_name' => 'PT Velocity'],
        ['bank' => 'Mandiri', 'account_number' => '9876543210', 'account_name' => 'PT Velocity'],
    ]));
});

function paidCourse(int $price = 150000): Course
{
    return Course::factory()->published()->create(['price' => $price, 'title' => 'Laravel Lanjutan']);
}

function proofPayload(array $overrides = []): array
{
    return [
        'bank_index' => 1,
        'payer_name' => 'Nadia Putri',
        'payer_note' => 'Transfer dari m-banking',
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ...$overrides,
    ];
}

test('a paid course cannot be enrolled in for free', function () {
    $course = paidCourse();
    $student = User::factory()->student()->create();

    $this->actingAs($student)->post(route('catalog.enroll', $course))->assertRedirect(route('catalog.show', $course->permalinkParameters()));

    expect($course->enrollments()->count())->toBe(0);
});

test('free courses still enroll straight away', function () {
    $course = Course::factory()->published()->create(['price' => 0]);
    $student = User::factory()->student()->create();

    $this->actingAs($student)->post(route('catalog.enroll', $course));

    expect($course->enrollments()->active()->count())->toBe(1);
});

test('the checkout page shows the course and creates no invoice until the order is placed', function () {
    $course = paidCourse(150000);
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('orders.checkout', $course))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('orders/Checkout')
            ->where('course.price', 150000)
            ->where('methods', ['bank_transfer'])
        );

    expect(Order::query()->count())->toBe(0);

    $response = $this->actingAs($student)->post(route('orders.store', $course), ['payment_method' => 'bank_transfer']);

    $order = Order::sole();
    $response->assertRedirect(route('orders.show', $order));

    expect($order->user_id)->toBe($student->id)
        ->and($order->status)->toBe(Order::STATUS_PENDING)
        ->and($order->payment_method)->toBe(Order::METHOD_BANK_TRANSFER)
        ->and($order->price)->toBe(150000)
        ->and($order->total)->toBe(150000)
        ->and($order->number)->toStartWith('INV-')
        ->and((int) round(now()->diffInHours($order->expires_at)))->toBe(PaymentSettings::DEFAULT_EXPIRY_HOURS);

    // With an open invoice, checkout goes straight to it and no second invoice is made.
    $this->actingAs($student)->get(route('orders.checkout', $course))->assertRedirect(route('orders.show', $order));
    $this->actingAs($student)->post(route('orders.store', $course), ['payment_method' => 'bank_transfer'])->assertRedirect(route('orders.show', $order));

    expect(Order::query()->count())->toBe(1);

    $this->actingAs($student)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('orders/Show')
            ->where('order.number', $order->number)
            ->where('order.total', 150000)
            ->where('buyer.email', $student->email)
            ->has('payment.bank_accounts', 2)
        );

    $this->actingAs($student)
        ->get(route('orders.proof.create', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('orders/Proof')->has('bankAccounts', 2));

    $this->actingAs($student)
        ->get(route('catalog.show', $course->permalinkParameters()))
        ->assertInertia(fn ($page) => $page->where('purchase.open_order.number', $order->number));
});

test('placing an order needs an available payment method', function () {
    $course = paidCourse();
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->post(route('orders.store', $course), ['payment_method' => 'qris'])
        ->assertSessionHasErrors('payment_method');

    expect(Order::query()->count())->toBe(0);
});

test('the student uploads a proof and the admin confirms it, which enrolls the student', function () {
    $course = paidCourse();
    $student = User::factory()->student()->create();
    $admin = User::factory()->admin()->create();
    $this->actingAs($student)->post(route('orders.store', $course), ['payment_method' => 'bank_transfer']);
    $order = Order::sole();

    $this->actingAs($student)
        ->post(route('orders.proof.store', $order), proofPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('orders.show', $order));

    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_AWAITING_CONFIRMATION)
        ->and($order->payment_details)->toBe(['bank' => 'Mandiri', 'account_number' => '9876543210', 'account_name' => 'PT Velocity'])
        ->and($order->payer_name)->toBe('Nadia Putri');
    Storage::disk(Order::PROOF_DISK)->assertExists($order->proof_path);

    $this->actingAs($admin)->get(route('orders.proof.show', $order))->assertOk();
    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertInertia(fn ($page) => $page->where('orders.data.0.number', $order->number)->where('counts.awaiting_confirmation', 1));

    $this->actingAs($admin)
        ->post(route('admin.orders.confirm', $order), ['note' => 'Masuk BCA'])
        ->assertRedirect(route('admin.orders.show', $order));

    $order->refresh();
    $transaction = Transaction::sole();

    expect($order->status)->toBe(Order::STATUS_PAID)
        ->and($order->paid_at)->not->toBeNull()
        ->and($transaction->amount)->toBe($order->total)
        ->and($transaction->confirmed_by)->toBe($admin->id)
        ->and($transaction->note)->toBe('Masuk BCA')
        ->and($course->enrollments()->active()->where('user_id', $student->id)->exists())->toBeTrue();

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['status' => Order::STATUS_PAID]))
        ->assertInertia(fn ($page) => $page
            ->where('paid.count', 1)
            ->where('paid.amount', $order->total)
            ->where('orders.data.0.number', $order->number)
            ->where('orders.data.0.payment.instructor_amount', $transaction->instructor_amount)
        );

    $csv = $this->actingAs($admin)->get(route('admin.orders.export', ['status' => Order::STATUS_PAID]))->streamedContent();
    expect($csv)->toContain($order->number)->toContain((string) $order->total)->toContain('Lunas');
});

test('a rejected proof sends the order back to the student with the reason', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->awaitingConfirmation()->create();

    $this->actingAs($admin)
        ->post(route('admin.orders.reject', $order), ['reason' => 'Nominal tidak sesuai'])
        ->assertRedirect(route('admin.orders.show', $order));

    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_PENDING)
        ->and($order->rejection_reason)->toBe('Nominal tidak sesuai')
        ->and($order->proof_path)->toBeNull()
        ->and($order->acceptsProof())->toBeTrue();

    $this->actingAs($admin)->post(route('admin.orders.reject', $order), ['reason' => 'x'])->assertRedirect();
    expect($order->refresh()->status)->toBe(Order::STATUS_PENDING);
});

test('unpaid orders expire after the deadline and no longer accept a proof', function () {
    $order = Order::factory()->create(['expires_at' => now()->subMinute()]);

    $this->artisan('orders:expire')->assertSuccessful();

    expect($order->refresh()->status)->toBe(Order::STATUS_EXPIRED);

    $this->actingAs($order->user)->get(route('orders.proof.create', $order))->assertRedirect(route('orders.show', $order));

    $this->actingAs($order->user)
        ->post(route('orders.proof.store', $order), proofPayload())
        ->assertRedirect();

    expect($order->refresh()->proof_path)->toBeNull();
});

test('the student can cancel an open order', function () {
    $order = Order::factory()->create();

    $this->actingAs($order->user)->patch(route('orders.cancel', $order))->assertRedirect(route('orders.show', $order));

    expect($order->refresh()->status)->toBe(Order::STATUS_CANCELLED);
});

test('proof uploads are validated', function () {
    $order = Order::factory()->create();

    $this->actingAs($order->user)
        ->post(route('orders.proof.store', $order), proofPayload([
            'bank_index' => 5,
            'proof' => UploadedFile::fake()->create('virus.exe', 10),
            'payer_name' => '',
        ]))
        ->assertSessionHasErrors(['bank_index', 'proof', 'payer_name']);
});

test('orders are private to their student and admins', function () {
    $order = Order::factory()->awaitingConfirmation()->create();
    $other = User::factory()->student()->create();
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($other)->get(route('orders.show', $order))->assertForbidden();
    $this->actingAs($other)->get(route('orders.proof.show', $order))->assertForbidden();
    $this->actingAs($instructor)->get(route('admin.orders.index'))->assertInertia(fn ($page) => $page->has('orders.data', 0));
    $this->actingAs($instructor)->get(route('admin.orders.show', $order))->assertForbidden();
    $this->actingAs($instructor)->post(route('admin.orders.confirm', $order))->assertForbidden();

    expect($order->refresh()->status)->toBe(Order::STATUS_AWAITING_CONFIRMATION);
});

test('a student who paid before can enroll again for free after cancelling', function () {
    $course = paidCourse();
    $student = User::factory()->student()->create();
    Order::factory()->for($student)->for($course)->create(['status' => Order::STATUS_PAID, 'paid_at' => now()]);

    $this->actingAs($student)->post(route('catalog.enroll', $course));

    expect($course->enrollments()->active()->where('user_id', $student->id)->exists())->toBeTrue();
});

test('the admin saves bank accounts, a QRIS image and the deadline', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.payment-settings.update'), [
            'bank_accounts' => [['bank' => 'BRI', 'account_number' => '0011-22-33', 'account_name' => 'Velocity']],
            'qris' => UploadedFile::fake()->image('qris.png'),
            'qris_name' => 'Velocity Developer',
            'instructions' => 'Konfirmasi jam kerja.',
            'expiry_hours' => 48,
        ])
        ->assertSessionHasNoErrors();

    expect(PaymentSettings::bankAccounts())->toBe([['bank' => 'BRI', 'account_number' => '0011-22-33', 'account_name' => 'Velocity']])
        ->and(PaymentSettings::qrisUrl())->not->toBeNull()
        ->and(PaymentSettings::expiryHours())->toBe(48)
        ->and(PaymentSettings::availableMethods())->toBe(['bank_transfer', 'qris']);

    $this->actingAs($admin)
        ->post(route('admin.payment-settings.update'), ['bank_accounts' => [], 'remove_qris' => true, 'expiry_hours' => 24])
        ->assertSessionHasNoErrors();

    expect(PaymentSettings::availableMethods())->toBe([]);
});

test('instructors see only the orders and sales of their own courses, without actions', function () {
    $instructor = User::factory()->instructor()->create();
    $own = Course::factory()->ownedBy($instructor)->published()->create(['price' => 150000]);
    $ownOrder = Order::factory()->for($own)->awaitingConfirmation()->create();
    $otherOrder = Order::factory()->for(paidCourse())->awaitingConfirmation()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.orders.confirm', $ownOrder))->assertRedirect();
    $this->actingAs($admin)->post(route('admin.orders.confirm', $otherOrder))->assertRedirect();

    $this->actingAs($instructor)
        ->get(route('admin.orders.index'))
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.number', $ownOrder->number)
            ->where('canManage', false));

    $this->actingAs($instructor)
        ->get(route('admin.orders.show', $ownOrder))
        ->assertInertia(fn ($page) => $page
            ->where('canManage', false));

    $this->actingAs($instructor)
        ->get(route('admin.orders.index', ['status' => Order::STATUS_PAID]))
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.number', $ownOrder->number)
            ->where('paid.amount', $ownOrder->refresh()->total));

    $this->actingAs($instructor)->post(route('admin.orders.cancel', $ownOrder))->assertForbidden();

    Storage::fake(Order::PROOF_DISK);
    Storage::disk(Order::PROOF_DISK)->put('bukti/milik.jpg', 'x');
    Storage::disk(Order::PROOF_DISK)->put('bukti/lain.jpg', 'x');
    $ownOrder->update(['proof_path' => 'bukti/milik.jpg']);
    $otherOrder->update(['proof_path' => 'bukti/lain.jpg']);

    $this->actingAs($instructor)
        ->get(route('admin.orders.show', $ownOrder))
        ->assertInertia(fn ($page) => $page
            ->where('order.proof_url', null)
            ->where('order.payer_name', null));
    $this->actingAs($instructor)->get(route('orders.proof.show', $ownOrder))->assertForbidden();
    $this->actingAs($instructor)->get(route('orders.proof.show', $otherOrder))->assertForbidden();
    $this->actingAs($instructor)->get(route('admin.payment-settings.edit'))->assertForbidden();
});

test('the order list filters by date: payment date for paid orders, order date for the rest', function () {
    $admin = User::factory()->admin()->create();

    $paidInRange = Order::factory()->create(['status' => Order::STATUS_PAID, 'paid_at' => '2026-09-10 10:00:00', 'created_at' => '2026-08-01 10:00:00']);
    Order::factory()->create(['status' => Order::STATUS_PAID, 'paid_at' => '2026-08-15 10:00:00', 'created_at' => '2026-09-10 10:00:00']);
    $openInRange = Order::factory()->create(['status' => Order::STATUS_PENDING, 'created_at' => '2026-09-12 10:00:00', 'expires_at' => now()->addDay()]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['from' => '2026-09-01', 'to' => '2026-09-30']))
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 2)
            ->where('orders.data', fn ($rows) => collect($rows)->pluck('number')->sort()->values()->all()
                === collect([$paidInRange->number, $openInRange->number])->sort()->values()->all())
        );
});
